<?php

namespace Drupal\events_v2\Traits;

trait FormatCoordinatesTrait {

  /**
   * Extracts coordinates from a WKT POINT string.
   *
   * @param string $wkt
   *   The WKT POINT string (e.g., "POINT (30.5 50.2)").
   *
   * @return array
   *   Array with 'latitude' and 'longitude' keys, or empty array if invalid.
   */
  protected function extractCoordinatesFromWKT(string|null $wkt): array {
    if (empty($wkt)) {
      return [];
    }

    if (preg_match('/POINT\s*\(([\d.-]+) ([\d.-]+)\)/', $wkt, $matches)) {
      return [
        'latitude' => (float) $matches[1],
        'longitude' => (float) $matches[2],
      ];
    }

    return [];
  }
}
