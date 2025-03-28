<?php

namespace Drupal\events_v2\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\events_v2\Enums\EventFields;
use Drupal\events_v2\Form\RegistrationForm;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block to display the event registration form.
 */
#[Block(
  id: 'event_registration_block',
  admin_label: new TranslatableMarkup('Adds registration form'),
)]
class RegistrationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected RouteMatchInterface $routeMatch,
    protected AccountInterface $currentUser,
    protected FormBuilderInterface $formBuilder
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('current_user'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $node = $this->routeMatch->getParameter('node');

    $maxParticipants = $node->get(EventFields::MAX_PARTICIPANTS)->value;
    $currentParticipants = $node->get(EventFields::PARTICIPANTS);
    $currentUserId = $this->currentUser->id();

    $participantsIds = array_column(
      $currentParticipants->getValue(),
      'target_id'
    );

    if ($node instanceof NodeInterface) {
      if (in_array($currentUserId, $participantsIds)) {
        return [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $this->t('You have already registered for this event.'),
          '#attributes' => [
            'style' => 'color: green; font-weight: bold; margin-bottom: 10px;',
          ],
        ];
      }
      if ($currentParticipants->count() >= $maxParticipants) {
        return [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $this->t('Sorry, this event is full.'),
          '#attributes' => [
            'style' => 'color: red; font-weight: bold; margin-bottom: 10px;',
          ],
        ];
      }

      return $this->formBuilder->getForm(RegistrationForm::class , $node);
    }

    return [
      '#markup' => $this->t('This form is only available on node pages.'),
    ];
  }

}
