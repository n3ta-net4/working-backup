
CREATE TABLE accommodations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type ENUM('cage', 'room') NOT NULL,
    number INT NOT NULL,
    is_available BOOLEAN DEFAULT 1,
    UNIQUE KEY unique_accommodation (type, number)
);

CREATE TABLE pet_boarding (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    pet_name VARCHAR(100) NOT NULL,
    pet_type ENUM('dog', 'cat') NOT NULL,
    accommodation_id INT NOT NULL,
    check_in DATETIME NOT NULL,
    check_out DATETIME NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (accommodation_id) REFERENCES accommodations(id)
);

-- Insert default accommodations
INSERT INTO accommodations (type, number) VALUES 
('cage', 1), ('cage', 2), ('cage', 3), ('cage', 4), ('cage', 5), ('cage', 6),
('room', 1), ('room', 2), ('room', 3), ('room', 4), ('room', 5), ('room', 6);