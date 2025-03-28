<?php

declare(strict_types=1);

namespace Drupal\events_v2\Plugin\GraphQL\DataProducer;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\events_v2\Enums\EntityBundle;
use Drupal\events_v2\Enums\EntityType;
use Drupal\events_v2\Enums\EventFields;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a data producer for loading active events.
 *
 * @DataProducer(
 *   id = "event_list",
 *   name = @Translation("Load active events"),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Event list")
 *   )
 * )
 */
final class EventsList extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs an EventsList object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
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
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Returns a list of active events.
   *
   * @return array<\Drupal\node\NodeInterface>
   *   An array of node entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function resolve(): array {
    $metadata = new CacheableMetadata();
    $metadata->addCacheTags(['node_list:event']);

    $query = $this->entityTypeManager->getStorage(EntityType::NODE)
      ->getQuery()
      ->condition('type', EntityBundle::BUNDLE)
      ->condition(EventFields::STATUS, 'active')
      ->sort(EventFields::START_DATE, 'ASC')
      ->accessCheck(TRUE);

    $nids = $query->execute();

    if (empty($nids)) {
      return [];
    }

    /** @var array<\Drupal\node\NodeInterface> $nodes */
    $nodes = $this->entityTypeManager
      ->getStorage(EntityType::NODE)
      ->loadMultiple($nids);

    return $nodes;
  }

}
