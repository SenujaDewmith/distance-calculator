<?php

declare(strict_types=1);

const EARTH_RADIUS_KM = 6371.0;

function calculateDistanceKm(
    float $latitudeA,
    float $longitudeA,
    float $latitudeB,
    float $longitudeB
): float {
    // Convert latitude values to radians (degrees to radians)
    $latitudeARadians = deg2rad($latitudeA);
    $latitudeBRadians = deg2rad($latitudeB);

    // Calculate coordinate differences in radians
    $latitudeDifference = deg2rad($latitudeB - $latitudeA);
    $longitudeDifference = deg2rad($longitudeB - $longitudeA);

    // Apply the Haversine formula
    $a = sin($latitudeDifference / 2) ** 2
        + cos($latitudeARadians)
        * cos($latitudeBRadians)
        * sin($longitudeDifference / 2) ** 2;

    // Prevent floating-point rounding errors
    $a = max(0.0, min(1.0, $a));

    // Calculate the central angle
    $centralAngle = 2 * atan2(
        sqrt($a),
        sqrt(1.0 - $a)
    );

    // Return the distance in kilometres
    return EARTH_RADIUS_KM * $centralAngle;
}
