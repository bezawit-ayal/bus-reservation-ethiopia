-- Ethiopian Bus Reservation System Database
-- Database: bus_reservation_ethiopia

CREATE DATABASE IF NOT EXISTS bus_reservation_ethiopia;
USE bus_reservation_ethiopia;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admins Table
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Routes Table (Ethiopian Cities)
CREATE TABLE routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    origin VARCHAR(100) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    distance_km INT NOT NULL,
    duration_hours DECIMAL(4,1) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Buses Table
CREATE TABLE buses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bus_name VARCHAR(100) NOT NULL,
    bus_number VARCHAR(20) NOT NULL UNIQUE,
    bus_type ENUM('Standard', 'Luxury', 'Semi-Luxury') DEFAULT 'Standard',
    total_seats INT NOT NULL DEFAULT 45,
    route_id INT NOT NULL,
    departure_time TIME NOT NULL,
    arrival_time TIME NOT NULL,
    price_birr DECIMAL(10,2) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE
);

-- Seats Table
CREATE TABLE seats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bus_id INT NOT NULL,
    seat_number VARCHAR(10) NOT NULL,
    seat_type ENUM('window', 'aisle', 'middle') DEFAULT 'aisle',
    FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_seat (bus_id, seat_number)
);

-- Bookings Table (one row per seat; same booking_reference for multiple seats)
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_reference VARCHAR(20) NOT NULL,
    INDEX idx_booking_ref (booking_reference),
    user_id INT NOT NULL,
    bus_id INT NOT NULL,
    seat_id INT NOT NULL,
    travel_date DATE NOT NULL,
    passenger_name VARCHAR(100) NOT NULL,
    passenger_phone VARCHAR(20) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    booking_status ENUM('confirmed', 'cancelled', 'completed') DEFAULT 'confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
    FOREIGN KEY (seat_id) REFERENCES seats(id) ON DELETE CASCADE
);

-- Insert Default Admin
INSERT INTO admins (username, email, password) VALUES 
('admin', 'admin@busethiopia.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
-- Default password: password

-- Insert Ethiopian Cities Routes
INSERT INTO routes (origin, destination, distance_km, duration_hours) VALUES 
('Addis Ababa', 'Bahir Dar', 510, 9.0),
('Addis Ababa', 'Gondar', 738, 12.0),
('Addis Ababa', 'Hawassa', 275, 4.5),
('Addis Ababa', 'Dire Dawa', 515, 8.0),
('Addis Ababa', 'Mekelle', 780, 13.0),
('Addis Ababa', 'Jimma', 352, 6.0),
('Addis Ababa', 'Adama', 99, 1.5),
('Addis Ababa', 'Harar', 526, 8.5),
('Bahir Dar', 'Gondar', 180, 3.0),
('Hawassa', 'Arba Minch', 275, 5.0),
('Dire Dawa', 'Harar', 55, 1.0),
('Addis Ababa', 'Dessie', 401, 7.0),
('Addis Ababa', 'Axum', 1024, 16.0),
('Mekelle', 'Axum', 247, 4.0),
('Addis Ababa', 'Gambella', 766, 12.0);

-- Insert Sample Buses
INSERT INTO buses (bus_name, bus_number, bus_type, total_seats, route_id, departure_time, arrival_time, price_birr) VALUES 
('Selam Bus', 'ETH-001', 'Luxury', 45, 1, '06:00:00', '15:00:00', 850.00),
('Sky Bus', 'ETH-002', 'Semi-Luxury', 45, 1, '07:00:00', '16:00:00', 650.00),
('Golden Bus', 'ETH-003', 'Standard', 49, 2, '05:30:00', '17:30:00', 950.00),
('Abay Bus', 'ETH-004', 'Luxury', 45, 3, '08:00:00', '12:30:00', 450.00),
('Ethio Bus', 'ETH-005', 'Semi-Luxury', 45, 4, '06:30:00', '14:30:00', 750.00),
('Habesha Express', 'ETH-006', 'Luxury', 45, 5, '05:00:00', '18:00:00', 1100.00),
('Jimma Star', 'ETH-007', 'Standard', 49, 6, '07:30:00', '13:30:00', 500.00),
('Adama Express', 'ETH-008', 'Standard', 49, 7, '09:00:00', '10:30:00', 150.00),
('Harar Bus', 'ETH-009', 'Semi-Luxury', 45, 8, '06:00:00', '14:30:00', 800.00),
('Tana Transport', 'ETH-010', 'Standard', 45, 9, '08:00:00', '11:00:00', 300.00);

-- Generate Seats for Each Bus
DELIMITER //
CREATE PROCEDURE GenerateSeats()
BEGIN
    DECLARE bus_cursor_id INT;
    DECLARE total INT;
    DECLARE i INT;
    DECLARE seat_t VARCHAR(10);
    DECLARE done INT DEFAULT FALSE;
    
    DECLARE bus_cursor CURSOR FOR SELECT id, total_seats FROM buses;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN bus_cursor;
    
    read_loop: LOOP
        FETCH bus_cursor INTO bus_cursor_id, total;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        SET i = 1;
        WHILE i <= total DO
            IF i % 4 = 1 OR i % 4 = 0 THEN
                SET seat_t = 'window';
            ELSE
                SET seat_t = 'aisle';
            END IF;
            
            INSERT INTO seats (bus_id, seat_number, seat_type) VALUES (bus_cursor_id, i, seat_t);
            SET i = i + 1;
        END WHILE;
    END LOOP;
    
    CLOSE bus_cursor;
END //
DELIMITER ;

-- Execute the procedure
CALL GenerateSeats();

-- Drop the procedure after use
DROP PROCEDURE IF EXISTS GenerateSeats;
