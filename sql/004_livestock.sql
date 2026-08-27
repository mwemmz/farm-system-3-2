-- 004_livestock.sql

CREATE TABLE IF NOT EXISTS animals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farm_id INT NOT NULL,
    tag_id VARCHAR(50) UNIQUE,
    breed VARCHAR(100),
    dob DATE,
    sex ENUM('male', 'female'),
    status VARCHAR(50)
);

CREATE TABLE IF NOT EXISTS vaccinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT NOT NULL,
    vaccine VARCHAR(100),
    date_given DATE,
    next_due DATE,
    FOREIGN KEY (animal_id) REFERENCES animals(id)
);

CREATE TABLE IF NOT EXISTS animal_treatments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT NOT NULL,
    condition VARCHAR(255),
    treatment TEXT,
    date DATE,
    FOREIGN KEY (animal_id) REFERENCES animals(id)
);

CREATE TABLE IF NOT EXISTS weight_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT NOT NULL,
    weight_kg DECIMAL(10, 2),
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animals(id)
);

CREATE TABLE IF NOT EXISTS breeding_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    female_id INT NOT NULL,
    male_id INT,
    mating_date DATE,
    expected_birth DATE,
    FOREIGN KEY (female_id) REFERENCES animals(id)
);
