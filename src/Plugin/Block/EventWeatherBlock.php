<?php

namespace Drupal\events_v2\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\events_v2\Enums\EventFields;
use Drupal\events_v2\Services\WeatherApiService;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[Block(
  id: 'event_weather_block',
  admin_label: new TranslatableMarkup('Adds weather block'),
)]
class EventWeatherBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected RouteMatchInterface $routeMatch,
    protected WeatherApiService $weatherApiService
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
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('events_v2.weather_api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->routeMatch->getParameter('node');

    if ($node->hasField(EventFields::COORDINATES) && $node instanceof NodeInterface) {
      $coordinates = $node->get(EventFields::COORDINATES)->value;

      $weather = $this->weatherApiService->getWeatherByCoordinates($coordinates);

      if ($weather) {
        return [
          '#theme' => 'weather_block',
          '#temperature' => $weather['temperature'],
          '#condition' => $weather['condition'],
          '#icon' => $weather['icon'],
          '#wind_speed' => $weather['wind_speed'],
          '#humidity' => $weather['humidity'],
          '#cache' => [
            'max-age' => 600,
            'contexts' => ['url.path'],
            'tags' => ['node:' . $node->id()],
          ]
        ];
      }

      return [
        '#markup' => t('Weather is not available.'),
      ];
    }

    return [
      '#markup' => $this->t('This form is only available on node pages.'),
    ];
  }
}
