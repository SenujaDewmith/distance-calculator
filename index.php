
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
                    >
                </fieldset>

            </div>

            <button type="submit">Calculate Distance</button>

        </form>

        <section class="result" aria-live="polite">
            <h2>Calculated Distance</h2>
            <p>The distance will appear here.</p>
        </section>

    </main>

</body>
</html>
