<?php

namespace Drupal\soda_scs_manager\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\soda_scs_manager\Entity\SodaScsComponentInterface;
use Drupal\user\EntityOwnerTrait;
use Drupal\user\UserInterface;

/**
 * SODa SCS Component.
 *
 * @ContentEntityType(
 *   id = "soda_scs_component",
 *   label = @Translation("SODa SCS Component"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\soda_scs_manager\SodaScsComponentListBuilder",
 *     "form" = {
 *       "default" = "Drupal\soda_scs_manager\Form\SodaScsComponentCreateForm",
 *       "add" = "Drupal\soda_scs_manager\Form\SodaScsComponentCreateForm",
 *       "edit" = "Drupal\soda_scs_manager\Form\SodaScsComponentEditForm",
 *       "delete" = "\Drupal\soda_scs_manager\Form\SodaScsComponentDeleteForm",
 *     },
 *     "access" = "Drupal\soda_scs_manager\SodaScsComponentAccessControlHandler",
 *   },
 *   bundle_entity_type = "soda_scs_component_bundle",
 *   base_table = "soda_scs_component",
 *   data_table = "soda_scs_component_field_data",
 *   admin_permission = "administer soda scs component entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *     "bundle" = "bundle",
 *   },
 *   links = {
 *     "canonical" = "/soda-scs-manager/component/{soda_scs_component}",
 *     "add-form" = "/soda-scs-manager/component/add",
 *     "edit-form" = "/soda-scs-manager/component/{soda_scs_component}/edit",
 *     "delete-form" = "/soda-scs-manager/component/{soda_scs_component}/delete",
 *     "collection" = "/soda-scs-manager/components",
 *   },
 *   field_ui_base_route = "entity.soda_scs_component_bundle.edit_form",
 *   fieldable = TRUE,
 *
 *   config_export = {
 *    "bundle",
 *    "created",
 *    "description",
 *    "id",
 *    "imageUrl",
 *    "label",
 *    "serviceProcessUuid",
 *    "subdomain",
 *    "uuid",
 *    "updated",
 *    "user",
 *    }
 * )
 */
class SodaScsComponent extends ContentEntityBase implements SodaScsComponentInterface {

  use EntityOwnerTrait;

  /**
   * The SODa SCS Component Bundle.
   *
   * @var string
   */
  protected string $bundle;

  /**
   * The description of the SODa SCS Component.
   *
   * @var array
   */
  protected array $description;

  /**
   * The external service ID of the SODa SCS Component.
   *
   * Used to identify the component in the external system.
   * Can be an integer or string in ext. system, so we
   * may have to parse it.
   *
   * @var string
   */
  protected string $externalId;


  /**
   * The SODa SCS Component ID.
   *
   * @var int
   */
  protected int $id;


  /**
   * The Image URL of the SODa SCS Component.
   *
   * @var string
   */

  protected string $imageUrl;


  /**
   * The SODa SCS Component label.
   *
   * @var string
   */
  protected string $label;


  /**
   * The API options of the SODa SCS Component.
   *
   * @var string
   */
  protected string $optionsUrl;

  /**
   * The uuid of the SODa SCS Component.
   *
   * @var string
   */
  protected string $uuid;

  /**
   * Returns the description of the SODa SCS Component.
   *
   * @return string
   *   The description of the SODa SCS Component.
   */
  public function getDescription(EntityInterface $entity) {
    return $this->description;
  }

  /**
   * Sets the description of the SODa SCS Component.
   *
   * @param array $description
   *   The description of the SODa SCS Component.
   *
   * @return $this
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * Returns the image of the SODa SCS Component.
   *
   * @return string
   *   The image of the SODa SCS Component.
   */
  public function getImageUrl() {
    return $this->imageUrl;
  }

  /**
   * Sets the image of the SODa SCS Component.
   *
   * @param string $imageUrl
   *   The image of the SODa SCS Component.
   *
   * @return $this
   */
  public function setImageUrl($imageUrl) {
    $this->imageUrl = $imageUrl;
    return $this;
  }

  /**
   * Returns the label of the SODa SCS Component.
   *
   * @return string
   *   The label of the SODa SCS Component.
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Sets the label of the SODa SCS Component.
   *
   * @param string $label
   *   The label of the SODa SCS Component.
   *
   * @return $this
   */
  public function setLabel($label) {
    $this->label = $label;
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
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    // Fetch any existing base field definitions from the parent class.
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['bundle'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Bundle'))
      ->setDescription(new TranslatableMarkup('The bundle of the SODa SCS Component.'))
      ->setSetting('target_type', 'soda_scs_component_bundle')
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setTranslatable(FALSE)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('view', FALSE);

    // @todo Implement the reuse of dangling components
    $fields['referencedComponents'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Connect with dangling component(s)'))
      ->setSetting('target_type', 'soda_scs_component')
      ->setSetting('handler', 'default')
      ->setRequired(FALSE)
      ->setReadOnly(TRUE)
      ->setTranslatable(FALSE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

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
      ])
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 3,
      ]);

    $fields['externalId'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('External ID'))
      ->setDescription(new TranslatableMarkup('The external ID of the SODa SCS Component.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setTranslatable(FALSE)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('ID'))
      ->setDescription(new TranslatableMarkup('The ID of the SCS component entity.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setTranslatable(FALSE)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['imageUrl'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Image'))
      ->setDescription(new TranslatableMarkup('The image of the SODa SCS Component.'))
      ->setRequired(FALSE)
      ->setReadOnly(TRUE)
      ->setTranslatable(FALSE)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'image',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', FALSE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Label'))
      ->setDescription(new TranslatableMarkup('The name of the component.'))
      ->setRequired(TRUE)
      ->setReadOnly(FALSE)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ]);

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
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 1,
      ]);

    $fields['notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Notes'))
      ->setDescription(new TranslatableMarkup('Notes about the SODa SCS Component.'))
      ->setRequired(FALSE)
      ->setReadOnly(FALSE)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 10,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 10,
      ]);

    $fields['serviceKey'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Service Key'))
      ->setDescription(new TranslatableMarkup('The service key of the SODa SCS Component.'))
      ->setSetting('target_type', 'soda_scs_service_key')
      ->setSetting('handler', 'default')
      ->setRequired(TRUE)
      ->setReadOnly(FALSE)
      ->setTranslatable(FALSE)
      ->setCardinality(1)
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 5,
      ]);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Status'))
      ->setDescription(new TranslatableMarkup('The status of the SODa SCS Component.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setTranslatable(FALSE)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'boolean',
        'weight' => 30,
      ]);

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

    $userRefHidden = self::isAdmin() ? ['region' => 'body'] : ['region' => 'hidden'];
    $fields['user'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Owned by'))
      ->setDescription(new TranslatableMarkup('The user ID of the author of the SODa SCS Component.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default:user_reference')
      ->setRequired(TRUE)
      ->setReadOnly(FALSE)
      ->setTranslatable(FALSE)
      ->setCardinality(1)
      ->setDefaultValueCallback('\Drupal\soda_scs_manager\Entity\SodaScsComponent::getDefaultUserId')
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => 9,
        'settings' => $userRefHidden,
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
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setTranslatable(FALSE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The node language code.'));

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
