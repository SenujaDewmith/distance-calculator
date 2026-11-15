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
                    <legend>Location A</legend>

                    <label for="latitude-a">Latitude</label>
                    <input
                        type="text"
                        inputmode="decimal"
                        id="latitude-a"
                        name="latitude_a"
                        placeholder="e.g. 6.9271"
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
                        value="<?= htmlspecialchars($longitudeAInput, ENT_QUOTES, 'UTF-8') ?>"
                        aria-invalid="<?= $errorField === 'longitude-a' ? 'true' : 'false' ?>"
                        <?= $errorField === 'longitude-a' ? 'autofocus aria-describedby="form-error"' : '' ?>
                    >
                </fieldset>

                <fieldset class="location-card">
                    <legend>Location B</legend>

                    <label for="latitude-b">Latitude</label>
                    <input
                        type="text"
                        inputmode="decimal"
                        id="latitude-b"
                        name="latitude_b"
                        placeholder="e.g. 7.2906"
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
                        value="<?= htmlspecialchars($longitudeBInput, ENT_QUOTES, 'UTF-8') ?>"
                        aria-invalid="<?= $errorField === 'longitude-b' ? 'true' : 'false' ?>"
                        <?= $errorField === 'longitude-b' ? 'autofocus aria-describedby="form-error"' : '' ?>
                    >
                </fieldset>

            </div>

            <button
                type="submit"
                id="calculate-button"
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

            <?php else: ?>

                <p>The distance will appear here.</p>

            <?php endif; ?>
        </section>

    </main>

</body>
</html>
