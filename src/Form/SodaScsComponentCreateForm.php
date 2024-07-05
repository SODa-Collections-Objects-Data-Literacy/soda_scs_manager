<?php

namespace Drupal\soda_scs_manager\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\soda_scs_manager\SodaScsApiActions;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Form controller for the ScsComponent entity create form.
 *
 * The form is used to create a new component entity.
 * It saves the entity with the fields:
 * - user: The user ID of the user who created the entity.
 * - created: The time the entity was created.
 * - updated: The time the entity was updated.
 * - label: The label of the entity.
 * - notes: Private notes of the user for the entity.
 * - description: The description of the entity (comes from bundle).
 * - image: The image of the entity (comes from bundle).
 * and redirects to the components page.
 */
class SodaScsComponentCreateForm extends ContentEntityForm {

  /**
   * The Soda SCS API Actions service.
   *
   * @var \Drupal\soda_scs_manager\SodaScsApiActions
   */
  protected SodaScsApiActions $sodaScsApiActions;

  /**
   * Constructs a new SodaScsComponentCreateForm.
   *
   * @param \Drupal\soda_scs_manager\SodaScsApiActions $sodaScsApiActions
   *   The Soda SCS API Actions service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, SodaScsApiActions $sodaScsApiActions) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->entityRepository = $entity_repository;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->time = $time;
    $this->sodaScsApiActions = $sodaScsApiActions;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('soda_scs_manager.api.actions')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form_id(): string {
    return 'soda_scs_component_create_form';
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the bundle of the entity
    $bundle = \Drupal::service('entity_type.manager')->getStorage('soda_scs_component_bundle')->load($this->entity->bundle());

    // Build the form
    $form = parent::buildForm($form, $form_state);

    // Change the title of the page.
    $form['#title'] = $this->t('Create a new @component', ['@component' => $bundle->label()]);

    // Add the bundle information
    $options = [
      'user' => \Drupal::currentUser()->getDisplayName(),
      'date' => date('Y-m-d H:i:s'),
      'component' => $bundle->label(),
      'description' => $bundle->getDescription(),
    ];
    $form['info'] = [
      '#type' => 'item',
      '#markup' => $this->t('<h3 class="text-center">This creates a <em>@component</em> for the user <em>@user</em></h3> <p class="text-justify">@description</p>', [
        '@component' => $options['component'],
        '@user' => $options['user'],
        '@description' => $options['description'],
      ]),
    ];

    // Change the label of the submit button.
    $form['actions']['submit']['#value'] = $this->t('CREATE COMPONENT');
    $form['actions']['submit']['#attributes']['class'][] = 'soda-scs-component--component--form-submit';

    $form['#attached']['library'][] = 'soda_scs_manager/globalStyling';

    return $form;
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function save(array $form, FormStateInterface $form_state): void {

    // Get the entity and bundle.
    $entity = $this->entity;
    /** @var \Drupal\soda_scs_manager\Entity\SodaScsComponentBundle $bundle */
    $bundle = \Drupal::service('entity_type.manager')->getStorage('soda_scs_component_bundle')->load($this->entity->bundle());

    // Set the user, created/updated time, and label.
    $entity->set('user', \Drupal::currentUser()->id());

    if ($entity->isNew()) {
      $entity->set('created', time());
    } else {
      $entity->set('updated', time());
    }

    $entity->set('label', $bundle->label());

    // Set the bundle information.
    $entity->set('description', $bundle->getDescription());
    $entity->set('imageUrl', $bundle->getImageUrl());

    // Save the entity.
    $status = $entity->save();

    // Check if the entity was saved.
    if ($status) {
      // Setting a message with the entity label
      $this->messenger()->addMessage($this->t('The @label component for @username has been created.', [
        '@label' => $entity->label(),
        '@username' => \Drupal::currentUser()->getDisplayName(),
      ]));
    } else {
      $this->messenger()->addMessage($this->t('The @label component for @username could not be created.', [
        '@label' => $entity->label(),
        '@username' => \Drupal::currentUser()->getDisplayName(),
      ]), 'error');
    }

    $options = [
      'body' => [
        'user' => \Drupal::currentUser()->getDisplayName(),
        'subdomain' => $entity->get('subdomain')->getValue(),
        'project' => 'my_project',
        ],
      'route' => 'http://localhost:2912/dummy-daemon/api/v1/wisski',

    ];
    // Make request to component
    $resultArray = $this->sodaScsApiActions->crudComponent('create', $options);

    // Redirect to the components page.
    $form_state->setRedirect('soda_scs_manager.components');
  }
}