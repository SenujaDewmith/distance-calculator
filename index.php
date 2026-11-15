<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/includes/distance.php';
require_once __DIR__ . '/includes/database.php';

$distanceKm = null;
$calculationResult = null;
$errorMessage = '';
$errorField = '';

// Get user input safely
function getPostedValue(string $fieldName): string
{
    $value = $_POST[$fieldName] ?? '';

    return is_string($value) ? trim($value) : '';
}

// Validate coordinate values
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

// Read submitted values
$latitudeAInput = getPostedValue('latitude_a');
$longitudeAInput = getPostedValue('longitude_a');
$latitudeBInput = getPostedValue('latitude_b');
$longitudeBInput = getPostedValue('longitude_b');

// Retrieve successful result after PRG redirect
if (
    $_SERVER['REQUEST_METHOD'] === 'GET'
    && isset($_SESSION['calculationResult'])
) {
    $calculationResult = $_SESSION['calculationResult'];

    unset($_SESSION['calculationResult']);
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $errorField = 'latitude-a';

        $latitudeA = validateCoordinate(
            $latitudeAInput,
            'Location A latitude',
            -90.0,
            90.0
        );

        $errorField = 'longitude-a';

        $longitudeA = validateCoordinate(
            $longitudeAInput,
            'Location A longitude',
            -180.0,
            180.0
        );

        $errorField = 'latitude-b';

        $latitudeB = validateCoordinate(
            $latitudeBInput,
            'Location B latitude',
            -90.0,
            90.0
        );

        $errorField = 'longitude-b';

        $longitudeB = validateCoordinate(
            $longitudeBInput,
            'Location B longitude',
            -180.0,
            180.0
        );

        $errorField = '';

        // Calculate distance
        $distanceKm = calculateDistanceKm(
            $latitudeA,
            $longitudeA,
            $latitudeB,
            $longitudeB
        );

        // Save calculation to MySQL
        $pdo = getDatabaseConnection();

        saveDistanceCalculation(
            $pdo,
            $latitudeA,
            $longitudeA,
            $latitudeB,
            $longitudeB,
            $distanceKm
        );

        // Store result data for the redirected GET page
        $_SESSION['calculationResult'] = [
            'latitudeA' => $latitudeAInput,
            'longitudeA' => $longitudeAInput,
            'latitudeB' => $latitudeBInput,
            'longitudeB' => $longitudeBInput,
            'distanceKm' => $distanceKm
        ];

        // Redirect only after successful database saving
        header('Location: index.php', true, 303);
        exit;

    } catch (InvalidArgumentException $exception) {
        $errorMessage = $exception->getMessage();

    } catch (PDOException $exception) {
        error_log($exception->getMessage());

        $distanceKm = null;
        $errorField = 'calculate-button';
        $errorMessage = 'Unable to save the calculation. Please try again.';

    } catch (Throwable $exception) {
        error_log($exception->getMessage());

        $distanceKm = null;
        $errorField = 'calculate-button';
        $errorMessage = 'An unexpected error occurred. Please try again.';
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
    <link rel="icon" type="image/png" href="assets/favicon.png">

</head>
<body>

    <main class="calculator-container">
        <h1>Geographic Distance Calculator</h1>

        <p class="description">
            Calculate the distance between two geographic locations.
        </p>

        <form method="POST" action="" novalidate autocomplete="off">

            <div class="locations-grid">

                <fieldset class="location-card">
                    <legend title="Enter the coordinates of the first geographic location.">
                        Location A
                    </legend>

                    <label for="latitude-a">Latitude</label>
                    <input
                        type="text"
                        inputmode="decimal"
                        id="latitude-a"
                        name="latitude_a"
                        placeholder="e.g. 6.9271"
                        title="Enter latitude between -90 and 90 degrees."
                        value="<?= htmlspecialchars($latitudeAInput, ENT_QUOTES, 'UTF-8') ?>"
                        aria-invalid="<?= $errorField === 'latitude-a' ? 'true' : 'false' ?>"
                        <?= $errorField === 'latitude-a' ? 'autofocus aria-describedby="form-error"' : '' ?>
                    >

                    <label for="longitude-a">Longitude</label>
                    <input
                        type="text"
                        inputmode="decimal"
                        id="longitude-a"
                        name="longitude_a"
                        placeholder="e.g. 79.8612"
                        title="Enter longitude between -180 and 180 degrees."
                        value="<?= htmlspecialchars($longitudeAInput, ENT_QUOTES, 'UTF-8') ?>"
                        aria-invalid="<?= $errorField === 'longitude-a' ? 'true' : 'false' ?>"
                        <?= $errorField === 'longitude-a' ? 'autofocus aria-describedby="form-error"' : '' ?>
                    >
                </fieldset>

                <fieldset class="location-card">
                    <legend title="Enter the coordinates of the second geographic location.">
                        Location B
                    </legend>

                    <label for="latitude-b">Latitude</label>
                    <input
                        type="text"
                        inputmode="decimal"
                        id="latitude-b"
                        name="latitude_b"
                        placeholder="e.g. 7.2906"
                        title="Enter latitude between -90 and 90 degrees."
                        value="<?= htmlspecialchars($latitudeBInput, ENT_QUOTES, 'UTF-8') ?>"
                        aria-invalid="<?= $errorField === 'latitude-b' ? 'true' : 'false' ?>"
                        <?= $errorField === 'latitude-b' ? 'autofocus aria-describedby="form-error"' : '' ?>
                    >

                    <label for="longitude-b">Longitude</label>
                    <input
                        type="text"
                        inputmode="decimal"
                        id="longitude-b"
                        name="longitude_b"
                        placeholder="e.g. 80.6337"
                        title="Enter longitude between -180 and 180 degrees."
                        value="<?= htmlspecialchars($longitudeBInput, ENT_QUOTES, 'UTF-8') ?>"
                        aria-invalid="<?= $errorField === 'longitude-b' ? 'true' : 'false' ?>"
                        <?= $errorField === 'longitude-b' ? 'autofocus aria-describedby="form-error"' : '' ?>
                    >
                </fieldset>

            </div>

            <button
                type="submit"
                id="calculate-button"
                title="Calculate the shortest approximate distance between the two geographic locations."
                <?= $errorField === 'calculate-button' ? 'autofocus' : '' ?>
            >
                Calculate Distance
            </button>

        </form>

        <section class="result" aria-live="polite">
            <h2>Calculated Distance</h2>

            <?php if ($errorMessage !== ''): ?>

                <p class="error-message" id="form-error" role="alert">
                    <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                </p>

            <?php elseif ($calculationResult !== null): ?>

                <div class="result-coordinates">

                    <div class="result-location">
                        <h3>Location A</h3>
                        <p>
                            Latitude:
                            <?= htmlspecialchars($calculationResult['latitudeA'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <p>
                            Longitude:
                            <?= htmlspecialchars($calculationResult['longitudeA'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>

                    <div class="result-location">
                        <h3>Location B</h3>
                        <p>
                            Latitude:
                            <?= htmlspecialchars($calculationResult['latitudeB'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <p>
                            Longitude:
                            <?= htmlspecialchars($calculationResult['longitudeB'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>

                </div>

                <p class="distance-value">
                    <?= number_format($calculationResult['distanceKm'], 2) ?> km
                </p>


                <!-- Calculation Explanation -->
                <details class="calculation-explanation">
                    <summary>How was this distance calculated?</summary>

                    <div class="explanation-content">
                        <h3>Understanding the Calculation</h3>

                        <p>
                            Earth is curved, so we use the Haversine formula
                            to estimate the shortest distance between two
                            locations along Earth's surface.
                        </p>

                        <ol class="calculation-steps">
                            <li>
                                <strong>Identify the two locations</strong>
                                <p>
                                    We take the latitude and longitude of
                                    Location A and Location B.
                                </p>
                            </li>

                            <li>
                                <strong>Convert degrees to radians</strong>
                                <p>
                                    The coordinates are converted into radians,
                                    which are used in mathematical calculations.
                                </p>
                            </li>

                            <li>
                                <strong>Apply the Haversine formula</strong>
                                <p>
                                    The formula uses the coordinates to find
                                    the angle between the two locations
                                    at the centre of Earth.
                                </p>
                            </li>

                            <li>
                                <strong>Calculate the distance</strong>
                                <p>
                                    We multiply the angle by Earth's
                                    approximate radius of 6,371 km
                                    to get the distance in kilometres.
                                </p>
                            </li>
                        </ol>

                        <h3>Mathematical Formula</h3>

                        <div class="formula-box">
                            <p>
                                a = sin²(Δφ / 2) +
                                cos(φ₁) × cos(φ₂) × sin²(Δλ / 2)
                            </p>

                            <p>
                                d = 2 × R × atan2(√a, √(1 − a))
                            </p>
                        </div>

                        <p class="formula-description">
                            R = Earth's approximate radius (6,371 km).
                            φ represents latitude, λ represents longitude,
                            and Δ means the difference between two values.
                            Angles are expressed in radians.
                        </p>

                        <p class="distance-note">
                            Note: This is the approximate shortest distance
                            along Earth's surface, not the driving distance.
                        </p>
                    </div>
                </details>


            <?php else: ?>

                <p>The distance will appear here.</p>

            <?php endif; ?>
        </section>

    </main>

</body>
</html>
