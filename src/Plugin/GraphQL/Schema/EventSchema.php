<?php

namespace Drupal\events_v2\Plugin\GraphQL\Schema;

use Drupal\events_v2\Enums\EventFields;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistry;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\GraphQL\Schema\SdlSchemaPluginBase;

/**
 * Provides a GraphQL schema for event functionality.
 *
 * @Schema(
 *   id = "event_schema",
 *   name = "Event Schema",
 *   path = "/graphql/event"
 * )
 */
final class EventSchema extends SdlSchemaPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getSchemaDefinition(): string {
    $module_path = \Drupal::service('extension.list.module')
      ->getPath('events_v2');

    $schema_path = $module_path . '/events_v2.graphqls';

    if (!file_exists($schema_path)) {
      throw new \Exception('Schema file not found at: ' . $schema_path);
    }

    return file_get_contents($schema_path);
  }

  /**
   * {@inheritdoc}
   */
  public function getResolverRegistry(): ResolverRegistryInterface {
    $registry = new ResolverRegistry();
    $builder = new ResolverBuilder();

    $registry->addFieldResolver('Query', 'activeEvents',
      $builder->produce('event_list')
    );

    $registry->addFieldResolver('Query', 'event',
      $builder->produce('event_by_id')
        ->map('id', $builder->fromArgument('id'))
    );

    $registry->addFieldResolver('Mutation', 'registerForEvent',
      $builder->produce('register_for_event')
        ->map('event_id', $builder->fromArgument('eventId'))
    );

    $registry->addFieldResolver('Event', 'id',
      $builder->produce('entity_id')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Event', 'title',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Event', 'startDate',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue(EventFields::START_DATE . '.value'))
    );

    $registry->addFieldResolver('Event', 'endDate',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue(EventFields::END_DATE . '.value'))
    );

    $registry->addFieldResolver('Event', 'description',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue(EventFields::DESCRIPTION . '.value'))
    );

    $registry->addFieldResolver('Event', 'maxParticipants',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map(
          'path',
          $builder->fromValue(EventFields::MAX_PARTICIPANTS . '.value')
        )
    );

    $registry->addFieldResolver('Event', 'status',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue(EventFields::STATUS . '.value'))
    );

    $registry->addFieldResolver('Event', 'location',
      $builder->callback(function ($node) {
        if (!$node->hasField(EventFields::LOCATION)
          || $node->get(EventFields::LOCATION)->isEmpty()
        ) {
          return NULL;
        }

        $location = $node->get(EventFields::LOCATION)->first();

        return [
          'addressLine1' => $location->get('address_line1')->getValue(),
          'locality' => $location->get('locality')->getValue(),
          'administrativeArea' => $location
            ->get('administrative_area')
            ->getValue(),
          'countryCode' => $location->get('country_code')->getValue(),
        ];
      })
    );

    $registry->addFieldResolver('Address', 'addressLine1',
      $builder->callback(function ($address) {
        return $address['addressLine1'] ?? NULL;
      })
    );

    $registry->addFieldResolver('Address', 'locality',
      $builder->callback(function ($address) {
        return $address['locality'] ?? NULL;
      })
    );

    $registry->addFieldResolver('Address', 'administrativeArea',
      $builder->callback(function ($address) {
        return $address['administrativeArea'] ?? NULL;
      })
    );

    $registry->addFieldResolver('Address', 'countryCode',
      $builder->callback(function ($address) {
        return $address['countryCode'] ?? NULL;
      })
    );

    $registry->addFieldResolver('Event', 'coordinates',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue(EventFields::COORDINATES. '.value'))
    );

    return $registry;
  }

}
