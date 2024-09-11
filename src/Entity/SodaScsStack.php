<?php

namespace Drupal\soda_scs_manager\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerTrait;
use Drupal\user\UserInterface;

/**
 * Defines the ComponentCredentials entity.
 *
 * @ContentEntityType(
 *   id = "soda_scs_stack",
 *   label = @Translation("Stack"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\soda_scs_manager\Form\SodaScsStackCreateForm",
 *       "add" = "Drupal\soda_scs_manager\Form\SodaScsStackCreateForm",
 *       "edit" = "Drupal\soda_scs_manager\Form\SodaScsStackEditForm",
 *       "delete" = "\Drupal\soda_scs_manager\Form\SodaScsStackDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   links = {
 *     "canonical" = "/soda-scs-manager/stack/{soda_scs_stack}",
 *     "add-form" = "/soda-scs-manager/stack/add",
 *     "edit-form" = "/soda-scs-manager/stack/{soda_scs_stack}/edit",
 *     "delete-form" = "/soda-scs-manager/stack/{soda_scs_stack}/delete",
 *     "collection" = "/soda-scs-manager/stacks",
 *   },
 *   base_table = "soda_scs_stack",
 *   data_table = "soda_scs_stack_field_data",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class SodaScsStack extends ContentEntityBase implements SodaScsStackInterface {

  use EntityOwnerTrait;
  /**
   * The entity relation to the Soda SCS Component.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $component;

  /**
   * Get the included Soda SCS Components.
   */
  public function getIncludedComponents() {
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $referencedEntities */
    $referencedEntities = $this->get('includedComponents');
    return $referencedEntities->referencedEntities();
  }

  /**
   * Add included Soda SCS Component.
   */
  public function addIncludedComponent($component) {
    $this->set('includedComponents', $component);
    return $this;
  }

  /**
   * Get the owner of the SODa SCS Component.
   *
   * @return \Drupal\user\Entity\User
   *   The owner of the SODa SCS Component.
   */
  public function getOwner() {
    return $this->get('user')->entity;
  }

  /**
   * Get the owner ID of the SODa SCS Component.
   *
   * @return int
   *   The owner ID of the SODa SCS Component.
   */
  public function getOwnerId() {
    return $this->get('user')->target_id;
  }

  /**
   * Set the owner of the SODa SCS Component.
   *
   * @param \Drupal\user\Entity\User $account
   *   The owner of the SODa SCS Component.
   *
   * @return $this
   */
  public function setOwner(UserInterface $account) {
    $this->set('user', $account);
    return $this;
  }

  /**
   * Set the owner ID of the SODa SCS Component.
   *
   * @param int $uid
   *   The owner ID of the SODa SCS Component.
   *
   * @return $this
   */
  public function setOwnerId($uid): self {
    $this->set('user', $this->get('user')->target_id);
    return $this;
  }

  /**
   * Get the type of the Soda SCS Stack.
   *
   * @return string
   *   The type of the Soda SCS Stack.
   */
  public function getType() {
    return $this->get('type')->value;
  }

  /**
   * Set the type of the Soda SCS Stack.
   *
   * @param string $type
   *   The type of the Soda SCS Stack.
   *
   * @return $this
   */
  public function setType($type) {
    $this->set('type', $type);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(new TranslatableMarkup('The time that the SODa SCS Component was created.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setTranslatable(FALSE)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 7,
      ]);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Description'))
      ->setDescription(new TranslatableMarkup('The description of the SODa SCS Component.'))
      ->setRequired(FALSE)
      ->setReadOnly(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'label' => 'above',
        'region' => 'hidden',
        'weight' => 3,
        'settings' => [
          'rows' => 10,
          'cols' => 100,
          'format' => 'full_html',
        ],
      ])
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 3,
      ]);

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('ID'))
      ->setDescription(new TranslatableMarkup('The ID of the SCS component entity.'))
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Label'))
      ->setDescription(new TranslatableMarkup('The name of the Stack.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ]);

    // @todo Insure to have only dangling components as references.
    $fields['includedComponents'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Included components'))
      ->setSetting('target_type', 'soda_scs_component')
      ->setSetting('handler', 'default')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['subdomain'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Subdomain'))
      ->setDescription(new TranslatableMarkup('Used for "subdomain".soda-scs.org.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setTranslatable(FALSE)
      ->setCardinality(1)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
        'settings' => [
          'region' => 'header',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 1,
      ]);

    $bundle_options = array_reduce(SodaScsComponentBundle::loadMultiple(), function ($carry, $bundle) {
      $carry[$bundle->id()] = $bundle->label();
      return $carry;
    }, []);
    $fields['type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Bundle'))
      ->setSetting('allowed_values', $bundle_options)
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['updated'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Updated'))
      ->setDescription(new TranslatableMarkup('The time that the SODa SCS Component was last updated.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setTranslatable(FALSE)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 8,
      ]);

    $userRefHidden = self::isAdmin() ? 'body' : 'hidden';
    $fields['user'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Owned by'))
      ->setDescription(new TranslatableMarkup('The user ID of the author of the SODa SCS Component.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default:user_reference')
      ->setRequired(TRUE)
      ->setReadOnly(FALSE)
      ->setTranslatable(FALSE)
      ->setCardinality(1)
      ->setDefaultValueCallback('\Drupal\soda_scs_manager\Entity\SodaScsStack::getDefaultUserId')
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => 9,
        'settings' => [
          'region' => $userRefHidden,
        ],
      ])
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 20,
      ]);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(new TranslatableMarkup('UUID'))
      ->setDescription(new TranslatableMarkup('The UUID of the SODa SCS Component entity.'))
      ->setReadOnly(TRUE);

    return $fields;

  }

  /**
   * Get the default user ID.
   *
   * @return int
   *   The default user ID.
   */
  public static function getDefaultUserId() {
    return \Drupal::currentUser()->isAnonymous() ? NULL : \Drupal::currentUser()->id();
  }

  /**
   * Check if the current user is an admin.
   *
   * @return bool
   *   TRUE if the current user is an admin, FALSE otherwise.
   */
  public static function isAdmin() {
    return \Drupal::currentUser()->hasPermission('administer sodasc components');

  }

}