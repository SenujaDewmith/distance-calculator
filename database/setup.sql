-- Geographic Distance Calculator
-- Database setup script

-- 1. Create the database
CREATE DATABASE IF NOT EXISTS distance_calculator;

-- 2. Select the database
USE distance_calculator;

-- 3. Create the table
CREATE TABLE IF NOT EXISTS distance_calculations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    latitude_a DECIMAL(9, 6) NOT NULL,
    longitude_a DECIMAL(9, 6) NOT NULL,
    latitude_b DECIMAL(9, 6) NOT NULL,
    longitude_b DECIMAL(9, 6) NOT NULL,
    distance_km DECIMAL(12, 3) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Remove the existing procedure if present
DROP PROCEDURE IF EXISTS save_distance_calculation;

-- 5. Create the stored procedure
DELIMITER //

CREATE PROCEDURE save_distance_calculation(
    IN p_latitude_a DECIMAL(9, 6),
    IN p_longitude_a DECIMAL(9, 6),
    IN p_latitude_b DECIMAL(9, 6),
    IN p_longitude_b DECIMAL(9, 6),
    IN p_distance_km DECIMAL(12, 3)
)
BEGIN
    INSERT INTO distance_calculations (
        latitude_a,
        longitude_a,
        latitude_b,
        longitude_b,
        distance_km
    )
    VALUES (
        p_latitude_a,
        p_longitude_a,
        p_latitude_b,
        p_longitude_b,
        p_distance_km
    );
END //

DELIMITER ;
