<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/includes/distance.php';
require_once __DIR__ . '/includes/database.php';

$distanceKm = null;
$errorMessage = '';

// Read a submitted form value safely
function getPostedValue(string $fieldName): string
{
    $value = $_POST[$fieldName] ?? '';

    return is_string($value) ? trim($value) : '';
}

// Validate a numeric coordinate and its allowed range
function validateCoordinate(
    string $input,
    string $fieldName,
    float $minimum,
    float $maximum
): float {
    if ($input === '') {
        throw new InvalidArgumentException(
            "$fieldName is required."
        );
    }

    if (!is_numeric($input)) {
        throw new InvalidArgumentException(
            "$fieldName must be a valid number."
        );
    }

    $coordinate = (float) $input;

    if (!is_finite($coordinate)) {
        throw new InvalidArgumentException(
            "$fieldName must be a finite number."
        );
    }

    if ($coordinate < $minimum || $coordinate > $maximum) {
        throw new InvalidArgumentException(
            "$fieldName must be between $minimum and $maximum."
        );
    }

    return $coordinate;
}

// Keep the entered values when the form is submitted
$latitudeAInput = getPostedValue('latitude_a');
$longitudeAInput = getPostedValue('longitude_a');
$latitudeBInput = getPostedValue('latitude_b');
$longitudeBInput = getPostedValue('longitude_b');


if (
    $_SERVER['REQUEST_METHOD'] === 'GET'
    && isset($_SESSION['calculationResult'])
) {
    $result = $_SESSION['calculationResult'];

    unset($_SESSION['calculationResult']);

    $latitudeAInput = $result['latitudeA'];
    $longitudeAInput = $result['longitudeA'];
    $latitudeBInput = $result['latitudeB'];
    $longitudeBInput = $result['longitudeB'];
    $distanceKm = $result['distanceKm'];
}


// Process the form only after submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $latitudeA = validateCoordinate(
            $latitudeAInput, 'Location A latitude', -90.0, 90.0
        );

        $longitudeA = validateCoordinate(
            $longitudeAInput, 'Location A longitude', -180.0, 180.0
        );

        $latitudeB = validateCoordinate(
            $latitudeBInput, 'Location B latitude', -90.0, 90.0
        );

        $longitudeB = validateCoordinate(
            $longitudeBInput, 'Location B longitude', -180.0, 180.0
        );

        $distanceKm = calculateDistanceKm(
            $latitudeA,
            $longitudeA,
            $latitudeB,
            $longitudeB
        );

        
        $pdo = getDatabaseConnection();

        saveDistanceCalculation(
            $pdo,
            $latitudeA,
            $longitudeA,
            $latitudeB,
            $longitudeB,
            $distanceKm
        );


    $_SESSION['calculationResult'] = [
        'latitudeA' => $latitudeAInput,
        'longitudeA' => $longitudeAInput,
        'latitudeB' => $latitudeBInput,
        'longitudeB' => $longitudeBInput,
        'distanceKm' => $distanceKm
    ];

    header('Location: index.php', true, 303);
    exit;



    } catch (InvalidArgumentException $exception) {
        $errorMessage = $exception->getMessage();
    } catch (PDOException $exception) {
        error_log($exception->getMessage());

        $distanceKm = null;

        $errorMessage = "Unable to save the calculation. Please try again.";
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geographic Distance Calculator</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

    <main class="calculator-container">
        <h1>Geographic Distance Calculator</h1>

        <p class="description">
            Calculate the distance between two geographic locations.
        </p>

        <form method="POST" action="">

            <div class="locations-grid">

                <fieldset class="location-card">
                    <legend>Location A</legend>

                    <label for="latitude-a">Latitude</label>
                    <input
                        type="number"
                        id="latitude-a"
                        name="latitude_a"
                        min="-90"
                        max="90"
                        step="any"
                        placeholder="e.g. 6.9271"
                        required
                        value="<?= htmlspecialchars($latitudeAInput, ENT_QUOTES, 'UTF-8') ?>"
                    >

                    <label for="longitude-a">Longitude</label>
                    <input
                        type="number"
                        id="longitude-a"
                        name="longitude_a"
                        min="-180"
                        max="180"
                        step="any"
                        placeholder="e.g. 79.8612"
                        required
                        value="<?= htmlspecialchars($longitudeAInput, ENT_QUOTES, 'UTF-8') ?>"
                    >
                </fieldset>

                <fieldset class="location-card">
                    <legend>Location B</legend>

                    <label for="latitude-b">Latitude</label>
                    <input
                        type="number"
                        id="latitude-b"
                        name="latitude_b"
                        min="-90"
                        max="90"
                        step="any"
                        placeholder="e.g. 7.2906"
                        required
                        value="<?= htmlspecialchars($latitudeBInput, ENT_QUOTES, 'UTF-8') ?>"
                >

                    <label for="longitude-b">Longitude</label>
                    <input
                        type="number"
                        id="longitude-b"
                        name="longitude_b"
                        min="-180"
                        max="180"
                        step="any"
                        placeholder="e.g. 80.6337"
                        required
                        value="<?= htmlspecialchars($longitudeBInput, ENT_QUOTES, 'UTF-8') ?>"
                    >
                </fieldset>

            </div>

            <button type="submit">Calculate Distance</button>

        </form>

        
    <section class="result" aria-live="polite">
        <h2>Calculated Distance</h2>

        <?php if ($errorMessage !== ''): ?>

            <p class="error-message" role="alert">
            <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
            </p>

        <?php elseif ($distanceKm !== null): ?>

            <p class="distance-value">
            <?= number_format($distanceKm, 2) ?> km
            </p>

        <?php else: ?>

            <p>The distance will appear here.</p>

        <?php endif; ?>
    </section>


    </main>

</body>
</html>
