<?php

declare(strict_types=1);

function getDatabaseConnection(): PDO
{
    $configPath = dirname(__DIR__, 3)
        . '/distance-calculator-config.php';

    $config = require $configPath;

    $dsn = "mysql:host={$config['host']};"
        . "port={$config['port']};"
        . "dbname={$config['database']};"
        . "charset=utf8mb4";

    return new PDO(
        $dsn,
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
}

function saveDistanceCalculation(
    PDO $pdo,
    float $latitudeA,
    float $longitudeA,
    float $latitudeB,
    float $longitudeB,
    float $distanceKm
): void {
    $sql = "CALL save_distance_calculation(?, ?, ?, ?, ?)";

    $statement = $pdo->prepare($sql);

    $statement->execute([
        $latitudeA,
        $longitudeA,
        $latitudeB,
        $longitudeB,
        $distanceKm
    ]);

    $statement->closeCursor();
}
