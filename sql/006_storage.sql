-- 006_storage.sql

CREATE TABLE IF NOT EXISTS storage_facilities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farm_id INT NOT NULL,
    type VARCHAR(100),
    capacity DECIMAL(15, 2)
);

CREATE TABLE IF NOT EXISTS stored_produce (
    id INT AUTO_INCREMENT PRIMARY KEY,
    facility_id INT NOT NULL,
    planting_id INT NOT NULL,
    quantity DECIMAL(15, 2),
    grade VARCHAR(50),
    stored_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (facility_id) REFERENCES storage_facilities(id)
);

CREATE TABLE IF NOT EXISTS dispatch_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stored_produce_id INT NOT NULL,
    quantity DECIMAL(15, 2),
    dispatched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    destination VARCHAR(255),
    FOREIGN KEY (stored_produce_id) REFERENCES stored_produce(id)
);
