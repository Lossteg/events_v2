<?php

declare(strict_types=1);

namespace Drupal\events_v2\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\events_v2\Enums\EventFields;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\events_v2\Enums\EntityType;

/**
 * Registers a user for an event.
 *
 * @DataProducer(
 *   id = "register_for_event",
 *   name = @Translation("Register for event"),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Registration result")
 *   ),
 *   consumes = {
 *     "event_id" = @ContextDefinition("integer",
 *       label = @Translation("Event ID")
 *     )
 *   }
 * )
 */
final class RegisterForEvent extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a RegisterForEvent object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected AccountInterface $currentUser,
    protected EntityTypeManagerInterface $entityTypeManager,
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
    $plugin_definition,
  ): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Registers user for an event.
   *
   * @param int $event_id
   *   The ID of the event.
   *
   * @return array
   *   The registration result with success status and message.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function resolve(int $event_id): array {
    $node = $this->entityTypeManager
      ->getStorage(EntityType::NODE)
      ->load($event_id);

    if (!$node) {
      return [
        'success' => FALSE,
        'message' => 'Event not found',
      ];
    }

    $currentUser = User::load($this->currentUser->id());

    $maxParticipants = $node->get(EventFields::MAX_PARTICIPANTS)->value;
    $currentParticipants = $node->get(EventFields::PARTICIPANTS);
    $currentUserId = $this->currentUser->id();

    $participantsIds = array_column(
      $currentParticipants->getValue(),
      'target_id'
    );

    if (in_array($currentUserId, $participantsIds)) {
      return [
        'success' => FALSE,
        'message' => 'You are already registered for this event ',
      ];
    }
    if ($currentParticipants->count() >= $maxParticipants) {
      return [
        'success' => FALSE,
        'message' => 'Sorry, this event is full. ',
      ];
    }

    try {
      $node->get(EventFields::PARTICIPANTS)->appendItem($currentUser);
      $node->save();

      $response = [
        'success' => TRUE,
        'message' => 'You`ve successfully registered for this event ',
      ];
    }
    catch (\Exception $e) {
      $response = [
        'success' => FALSE,
        'message' => 'Registration failed: @error', ['@error' => $e->getMessage()],
      ];
    } finally {
      return $response;
    }
  }

}
