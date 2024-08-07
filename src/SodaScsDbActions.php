<?php

namespace Drupal\soda_scs_manager;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;

/**
 * Handles the communication with the SCS user manager daemon.
 */
class SodaScsDbActions {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $loggerFactory;

  /**
   * The settings config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $settings;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected TranslationInterface $stringTranslation;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  public function __construct(ConfigFactoryInterface $configFactory, Connection $database, LoggerChannelFactoryInterface $loggerFactory, MessengerInterface $messenger, TranslationInterface $stringTranslation,) {
    $this->settings = $configFactory
      ->getEditable('soda_scs_manager.settings');
    $this->database = $database;
    $this->loggerFactory = $loggerFactory;
    $this->messenger = $messenger;
    $this->stringTranslation = $stringTranslation;
  }

  /**
   * Creates a new database.
   *
   * @param string $dbName
   *   The name of the database.
   * @param int $dbUserId
   *   The id of the database user.
   *
   * @return array
   *  Success result.
   *
   * @throws MissingDataException
   */
  public function createDb(string $dbName, int $dbUserId): array {
    // All settings available?

    // Username.
    $dbUserName = $this->getUserNameById($dbUserId);
    if (!$dbUserName) {
      throw new MissingDataException('User name not found');
    }
    // Database host.
    $dbHost = $this->settings->get('dbHost');
    if (empty($dbHost)) {
      throw new MissingDataException('Database Host setting missing');
    }

    // Database root password.
    $dbRootPassword = $this->settings->get('dbRootPassword');
    if (empty($dbRootPassword)) {
      throw new MissingDataException('Database root password setting missing');
    }

    // Check if the database exists.
    $checkDbExistsResult = $this->checkDbExists($dbName);

    // Command failed.
    if ($checkDbExistsResult['execStatus'] != 0) {
      return $this->handleCommandFailure($checkDbExistsResult, 'check if database', $dbName);
    }

    if ($checkDbExistsResult['result']) {
      // Database already exists
      $this->messenger->addError(t('Database already exists. See logs for more details.'));
      return [
        'message' => t("Database \"@dbName\" already exists. Pick another name.", ['@dbName' => $dbName]),
        'data' => [],
        'error' => NULL,
        'success' => FALSE,
      ];
    }

    // Check if the user exists
    $checkDbUserExistsResult = $this->checkDbUserExists($dbUserName);

    // Command failed
    if ($checkDbUserExistsResult['execStatus'] != 0) {
      return $this->handleCommandFailure($checkDbUserExistsResult, 'check if user', $dbUserName);
    }



    if ($checkDbUserExistsResult['result'] == 0) {
      // Database user does not exist
      // Create the database user
      $dbUserPassword = $this->generateRandomPassword();
      $createDbUserResult = $this->createDbUser($dbUserName, $dbUserPassword);

      // Command failed
      if ($createDbUserResult['execStatus'] != 0) {
        return $this->handleCommandFailure($createDbUserResult, 'create user', $dbUserName);
      }

    } else {
      // Database user does exist
      // Check if current user is the same user for the database service
      $conditions = [
        'scs_user_id' => $dbUserId,
        'stack_host' => $dbHost,
        'service_name' => 'mariadb',
        'service_entity_type' => 'database',
        'service_entity_name' => $dbName,
      ];
      $serviceData = $this->queryServiceData($conditions);

      // Command failed
      if ($serviceData['execStatus'] != 0) {
        return $this->handleCommandFailure($serviceData, 'query service data for', implode(', ', $conditions));
      }

      if (isset($serviceData['service_username']) && $serviceData['service_username'] != $dbUserName) {
        // User is not the same as the service user
        $this->messenger->addError(t('Scs user is not the same as the service user. See logs for more details.'));
        $this->loggerFactory->get('soda_scs_manager')
          ->error(t("User \"@currentUser\" is not the same as the service user \"serviceUser\". Cannot grant privileges."), [
            '@currentUser' => $dbUserName,
            '@serviceUser' => $serviceData['service_username'],
          ]);
        return [
          'message' => t("User is not the same as the service user. Cannot create database or grant privileges."),
          'data' => [],
          'error' => NULL,
          'success' => FALSE,
        ];
      }

      // Get password for SCS service user
      $conditions = [
        'scs_user_id' => $dbUserId,
        'stack_host' => $dbHost,
        'service_name' => 'mariadb',
        'service_entity_type' => 'database',
      ];
      $serviceData = $this->queryServiceData($conditions);
      $dbUserPassword = $serviceData['result']['service_user_password'];
    }

    // Insert user service data
    $this->insertUserServiceData([
      'scsUserId' => $dbUserId,
      'stackHost' => $dbHost,
      'stackId' => NULL,
      'serviceName' => 'mariadb',
      'serviceEntityType' => 'database',
      'serviceEntityName' => NULL,
      'serviceUsername' => $dbUserName,
      'serviceUserPassword' => $dbUserPassword,
    ]);



    // Grant rights to the database user
    $grantRights2DbResult = $this->grantRights2DbUser($dbUserName, $dbName, ['ALL']);

    // Command failed
    if ($grantRights2DbResult['execStatus'] != 0) {
      return $this->handleCommandFailure($grantRights2DbResult, 'grant rights to user', 'user', 'dbUser');
    }

    // Create the database
    $createDbCommand = "mysql -h $dbHost -uroot -p$dbRootPassword -e 'CREATE DATABASE $dbName;'";
    $dbCreated = exec($createDbCommand, $createDbOutput, $createDbReturnVar);

    // Command failed
    if ($createDbReturnVar != 0) {
      return $this->handleCommandFailure([
        'command' => $createDbCommand,
        'execStatus' => $createDbReturnVar,
        'output' => $createDbOutput,
        'result' => $dbCreated
      ], 'create database', $dbName);
    }

    // Command succeeded
    $this->updateUserServiceData(
      [
      'service_entity_name' => $dbName
      ],
      [
        'scs_user_id' => $dbUserId,
        'stack_host' => $dbHost,
        'service_name' => 'mariadb',
        'service_entity_type' => 'database',
      ]
    );

    return [
      'command' => $createDbCommand,
      'execStatus' => $createDbReturnVar,
      'output' => $createDbOutput,
      'result' => $dbCreated,
    ];
  }

  /**
   * Checks if a database exists.
   *
   * @param string $dbName
   *   The name of the database.
   *
   * @return array
   *  Command, execution status (0 = success >0 = failure) and last line of
   *
   */
  public function checkDbExists(string $dbName): array {
    $dbHost = $this->settings->get('dbHost');
    $dbRootPassword = $this->settings->get('dbRootPassword');

    // Check if the database exists
    $checkDbCommand = "mysql -h $dbHost -uroot -p$dbRootPassword -e 'SHOW DATABASES LIKE \"$dbName\";' 2>&1";
    exec($checkDbCommand, $databaseExists, $checkDbReturnVar);

    // Check if the output contains the database name
    $dbExists = !empty($databaseExists) && in_array($dbName, $databaseExists);

    return [
      'command' => $checkDbCommand,
      'execStatus' => $checkDbReturnVar,
      'output' => $databaseExists,
      'result' => $dbExists,
    ];
  }

  public function updateDb() {}

  /**
   * Deletes a database.
   *
   * @param string $dbName
   *   The name of the database.
   * @param string $dbUser
   *   The name of the database user.
   *
   * @return array
   *  Success result.
   *
   * @throws MissingDataException
   */
  public function deleteDb(string $dbName, string $dbUser): array {
    // Database host.
    $dbHost = $this->settings->get('dbHost');
    if (empty($dbHost)) {
      throw new MissingDataException('Database Host setting missing');
    }

    // Database root password.
    $dbRootPassword = $this->settings->get('dbRootPassword');
    if (empty($dbRootPassword)) {
      throw new MissingDataException('Database root password setting missing');
    }

    // Delete the database
    $deleteDbCommand = "mysql -h $dbHost -uroot -p$dbRootPassword -e 'DROP DATABASE $dbName; FLUSH PRIVILEGES;'";
    $dbDeleted = exec($deleteDbCommand, $deleteDbOutput, $deleteDbReturnVar);


    return [
      'command' => $deleteDbCommand,
      'execStatus' => $deleteDbReturnVar,
      'output' => $deleteDbOutput,
      'result' => $dbDeleted,
    ];
  }

  /**
   * Creates a new database user.
   *
   * @param string $dbUser
   *   The name of the database user.
   * @param string $dbUserPassword
   *   The password of the database user.
   *
   * @return array
   *  Command, execution status (0 = success >0 = failure) and last line of
   *   output as result.
   *
   */
  public function createDbUser(string $dbUser, string $dbUserPassword): array {
    $dbHost = $this->settings->get('dbHost');
    $dbRootPassword = $this->settings->get('dbRootPassword');

    $createDbUserCommand = "mysql -h $dbHost -uroot -p$dbRootPassword -e 'CREATE USER \"$dbUser\"@\"%\" IDENTIFIED BY \"$dbUserPassword\"; FLUSH PRIVILEGES;' 2>&1";
    $dbUserCreated = exec($createDbUserCommand, $createDbUserOutput, $createDbUserReturnVar);

    return [
      'command' => $createDbUserCommand,
      'execStatus' => $createDbUserReturnVar,
      'output' => $createDbUserOutput,
      'result' => $dbUserCreated,
    ];
  }

  /**
   * Checks if a database user exists.
   *
   * @param string $dbUser
   *   The name of the database user.
   *
   * @return array
   *  Command, execution status (0 = success >0 = failure) and last line of
   *   output as result.
   */
  public function checkDbUserExists(string $dbUser): array {
    $dbHost = $this->settings->get('dbHost');
    $dbRootPassword = $this->settings->get('dbRootPassword');

    // Check if the user exists
    $checkUserCommand = "mysql -h $dbHost -uroot -p$dbRootPassword -e 'SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user = \"$dbUser\");' 2>&1";
    $dbUserRead = exec($checkUserCommand, $checkUserCommandOutput, $checkUserReturnVar);
    return [
      'command' => $checkUserCommand,
      'execStatus' => $checkUserReturnVar,
      'output' => $checkUserCommandOutput,
      'result' => $dbUserRead,
    ];
  }

  /**
   * Grants rights to a database user.
   *
   * @param string $dbUser
   * @param string $dbName
   * @param array $rights
   *
   * @return array
   */
  public function grantRights2DbUser(string $dbUser, string $dbName, array $rights): array {
    $dbHost = $this->settings->get('dbHost');
    $dbRootPassword = $this->settings->get('dbRootPassword');
    $rights = implode(', ', $rights);

    $grantRightsCommand = "mysql -h $dbHost -uroot -p$dbRootPassword -e 'GRANT $rights PRIVILEGES ON $dbName.* TO \"$dbUser\"@\"%\"; FLUSH PRIVILEGES;' 2>&1";
    $grantRightsCommandResult = exec($grantRightsCommand, $grantRightsCommandOutput, $grantRightsCommandReturnVar);
    return [
      'command' => $grantRightsCommand,
      'execStatus' => $grantRightsCommandReturnVar,
      'output' => $grantRightsCommandOutput,
      'result' => $grantRightsCommandResult,
    ];
  }

  /**
   * Checks if a database user owns any databases.
   *
   * If the user does not own any databases, the user is deleted.
   *
   * @param string $dbUser
   *
   * @return array
   */
  public function cleanDbUser(string $dbUser) {
    $dbHost = $this->settings->get('dbHost');
    $dbRootPassword = $this->settings->get('dbRootPassword');

    // Check if the user owns any databases
    $checkUserDatabasesCommand = "mysql -h $dbHost -uroot -p$dbRootPassword -e 'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME IN (SELECT DISTINCT table_schema FROM information_schema.tables WHERE table_schema NOT IN (\"information_schema\", \"mysql\", \"performance_schema\", \"sys\") AND table_schema = \"$dbUser\");' 2>&1";
    $userDatabases = exec($checkUserDatabasesCommand, $checkUserDatabasesOutput, $checkUserDatabasesReturnVar);

    if ($checkUserDatabasesReturnVar != 0) {
      // Command failed
      $this->loggerFactory->get('soda_scs_manager')
        ->error('Failed to execute MySQL command to check if user owns any databases. Are the database credentials correct and the select permissions set?');
      $this->messenger->addError($this->stringTranslation->translate('Failed to execute MySQL command to check if the user owns any databases. See logs for more details.'));
      return [
        'message' => t('Could not check if user %s owns any databases', ['@dbUser' => $dbUser]),
        'error' => 'Failed to execute MySQL command to check if the user owns any databases',
        'success' => FALSE,
      ];
    }

    if ($checkUserDatabasesOutput == 0) {
      // User does not own any databases, delete the user
      $deleteUserCommand = "mysql -h $dbHost -uroot -p$dbRootPassword -e 'DROP USER \"$dbUser\"@\"%\"; FLUSH PRIVILEGES;'";
      $deleteUserResult = exec($deleteUserCommand, $deleteUserOutput, $deleteUserReturnVar);

      if ($deleteUserResult === NULL) {
        // Command failed
        $this->loggerFactory->get('soda_scs_manager')
          ->error('Failed to execute MySQL command to delete user. Are the database credentials correct and the delete permissions set?');
        $this->messenger->addError($this->stringTranslation->translate('Failed to execute MySQL command to delete the user. See logs for more details.'));
        return [
          'message' => t('Could not delete user @dbUser. See logs for more.', ['@dbUser' => $dbUser]),
          'error' => 'Failed to execute MySQL command to delete the user',
          'success' => FALSE,
        ];
      }
      else {
        // Command succeeded
        return [
          'message' => t('User @dbUser has no databases left and was deleted.', ['@dbUser' => $dbUser]),
          'error' => NULL,
          'success' => TRUE,
        ];
      }
    }
    else {
      // User owns databases, do not delete
      return [
        'message' => t('User @dbUser owns databases and will not be deleted', ['@dbUser' => $dbUser]),
        'error' => NULL,
        'success' => TRUE,
      ];
    }
  }

  private function handleCommandFailure(array $commandResult, string $action, string $entityName): array {
    $this->loggerFactory->get('soda_scs_manager')
      ->error(t("Failed to execute MySQL command \"@command\" to @action \"@entityName\". Error: @error", [
        '@action' => $action,
        '@command' => $commandResult['command'],
        '@entityName' => $entityName,
        '@error' => $commandResult['output'],
      ]));
    $this->messenger->addError(t("Failed to execute MySQL command to @action. See logs for more details.", ['@action' => $action]));
    return [
      'message' => t("Cannot @action \"@$entityName\". See log for details.", [
        '@action' => $action,
        '@entityName' => $entityName]),
      'data' => [],
      'error' => $commandResult['output'],
      'success' => FALSE,
    ];
  }

  /**
   * Insert new user with service credentials.
   *
   *
   * @param array $data
   *
   * @return array
   */
  public function insertUserServiceData(array $data): array {
    try {
      $this->database->insert('soda_scs_manager__user_service_data')
        ->fields([
          'scs_user_id' => $data['scsUserId'],
          'stack_host' => $data['stackHost'],
          'stack_id' => $data['stackId'],
          'service_name' => $data['serviceName'],
          'service_entity_type' => $data['serviceEntityType'],
          'service_entity_name' => $data['serviceEntityName'],
          'service_username' => $data['serviceUsername'],
          'service_user_password' => $data['serviceUserPassword'],
        ])
        ->execute();

      return [
        'message' => t('User service data for user @userId has been inserted successfully.', ['@userId' => $data['serviceUsername']]),
        'error' => NULL,
        'success' => TRUE,
      ];
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('soda_scs_manager')
        ->error('Failed to insert user service data: ' . $e->getMessage());
      $this->messenger->addError($this->stringTranslation->translate('Failed to insert user service data. See logs for more details.'));
      return [
        'message' => t('Could not insert user service data for user @userId', ['@userId' => $data['serviceUsername']]),
        'error' => 'Failed to insert user service data',
        'success' => FALSE,
      ];
    }
  }

  /**
   * Query service data.
   *
   * @param array $conditions
   *  The conditions to query the service data.
   *  scs_user_id: int (1)
   *  stack_host: string (db.example.org)
   *  stack_id: int (38)
   *  service_name: string (mariadb)
   *  service_entity_type: string (database)
   *  service_entity_name: string (my_database)
   *  service_username: string (my_user)
   *  service_user_password: string (my_password)
   *
   * @return array|bool
   */
  public function queryServiceData(array $conditions): array|bool {
    $query = $this->database->select('soda_scs_manager__user_service_data', 'usd')
      ->fields('usd');
    foreach ($conditions as $field => $value) {
      $query->condition($field, $value);
    }
    try {
      $result = $query->execute()->fetchAssoc();
      return [
        'command' => $query->__toString(),
        'execStatus' => 0,
        'output' => NULL,
        'result' => $result,
        ];
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('soda_scs_manager')
        ->error(t('Failed to query service data. Query: @query. Error: @error ', ['@query' => $query->__toString(), '@error' => $e->getMessage()]));
      $this->messenger->addError($this->stringTranslation->translate('Failed to query service data. See logs for more details.'));
      return [
        'command' => $query->__toString(),
        'execStatus' => 1,
        'output' => NULL,
        'result' => NULL,
      ];
    }
  }

  /**
   * Updates the user service data.
   *
   * @param array $fields
   *   Fields/values pairs to update.
   * @param array $conditions
   *   Conditions for rows
   *
   * @return array
   */
  public function updateUserServiceData(array $fields, array $conditions): array {
    try {
      $query = $this->database->update('soda_scs_manager__user_service_data');

      // Add fields to update
      foreach ($fields as $field => $value) {
        $query->fields([$field => $value]);
      }

      // Add conditions
      foreach ($conditions as $field => $value) {
        $query->condition($field, $value);
      }

      // Execute the update query
      $result = $query->execute();

      return [
        'message' => t('User service data updated successfully.'),
        'error' => NULL,
        'success' => TRUE,
        'result' => $result,
      ];
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('soda_scs_manager')
        ->error('Failed to update user service data: ' . $e->getMessage());
      $this->messenger->addError($this->stringTranslation->translate('Failed to update user service data. See logs for more details.'));
      return [
        'message' => t('Could not update user service data.'),
        'error' => 'Failed to update user service data',
        'success' => FALSE,
        'result' => NULL
      ];
    }
  }

  /**
   * Generates a random password.
   *
   * @return string
   */
  function generateRandomPassword(): string {
    $password = '';
    while (strlen($password) < 32) {
      $password .= base_convert(random_int(0, 35), 10, 36);
    }
    return substr($password, 0, 32);
  }

  public function getUserNameById(int $userId): ?string {
    try {
      $query = $this->database->select('users_field_data', 'ufd')
        ->fields('ufd', ['name'])
        ->condition('uid', $userId)
        ->execute();

      $result = $query->fetchField();

      return $result ?: NULL;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('soda_scs_manager')
        ->error('Failed to query user name: ' . $e->getMessage());
      return NULL;
    }
  }
}
