<?php

namespace Drupal\events_v2\Services;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\events_v2\Enums\WeatherApiCredentials;
use Drupal\events_v2\Traits\FormatCoordinatesTrait;
use GuzzleHttp\Exception\GuzzleException;

class WeatherApiService {

  use FormatCoordinatesTrait;

  /**
   * The API key for the weather service.
   *
   * @var string
   */
  protected string $apiKey;

  /**
   * The API base URL.
   *
   * @var string
   */
  protected string $apiUrl;

  /**
   * Constructs a new WeatherService.
   *
   * @param \Drupal\Core\Http\ClientFactory $httpClientFactory
   *   The HTTP client factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    protected ClientFactory $httpClientFactory,
    protected ConfigFactoryInterface $configFactory
  ) {
    $config = $this->configFactory->get(WeatherApiCredentials::CONFIG);
    $this->apiKey = $config->get(WeatherApiCredentials::KEY);
    $this->apiUrl = $config->get(WeatherApiCredentials::URL);
  }

  /**
   * Gets weather data for specific coordinates.
   *
   * @param string $wkt
   * The WKT POINT string (e.g., "POINT (30.5 50.2)").
   *
   * @return array
   *   Weather data.
   */
  public function getWeatherByCoordinates(string|null $wkt): array {
    $coordinates = $this->extractCoordinatesFromWKT($wkt);

    if (empty($coordinates)) {
      return [];
    }

    try {
      $client = $this->httpClientFactory->fromOptions();
      $response = $client->request('GET', $this->apiUrl, [
        'query' => [
          'key' => $this->apiKey,
          'q' => $coordinates['latitude'] . "," .$coordinates['longitude'],
          'aqi' => 'no',
        ],
      ]);

      $data = Json::decode((string) $response->getBody());

      if (!isset($data['current'])) {
        return [];
      }

      return [
        'temperature' => $data['current']['temp_c'],
        'condition' => $data['current']['condition']['text'],
        'icon' => $data['current']['condition']['icon'],
        'wind_speed' => $data['current']['wind_kph'],
        'humidity' => $data['current']['humidity'],
      ];
    }
    catch (GuzzleException $e) {
      return [];
    }
  }

}
