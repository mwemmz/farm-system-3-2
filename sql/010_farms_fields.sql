-- Developer 1 Phase 1: farms, fields and field history.
CREATE TABLE IF NOT EXISTS farms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    location_name VARCHAR(255) NULL,
    latitude DECIMAL(10, 7) NULL,
    longitude DECIMAL(10, 7) NULL,
    total_area_hectares DECIMAL(15, 4) NULL,
    description TEXT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_farms_owner_status (owner_user_id, status),
    CONSTRAINT fk_farms_owner FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT chk_farms_area CHECK (total_area_hectares IS NULL OR total_area_hectares > 0)
);

CREATE TABLE IF NOT EXISTS fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farm_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(100) NULL,
    area_hectares DECIMAL(15, 4) NOT NULL,
    latitude DECIMAL(10, 7) NULL,
    longitude DECIMAL(10, 7) NULL,
    boundary_geojson LONGTEXT NULL,
    land_use VARCHAR(100) NULL,
    land_use_notes TEXT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_fields_farm_status (farm_id, status),
    UNIQUE KEY uq_fields_farm_code (farm_id, code),
    CONSTRAINT fk_fields_farm FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE RESTRICT,
    CONSTRAINT chk_fields_area CHECK (area_hectares > 0)
);

CREATE TABLE IF NOT EXISTS soil_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    field_id INT NOT NULL,
    soil_type VARCHAR(100) NULL,
    ph_level DECIMAL(4, 2) NULL,
    moisture_percent DECIMAL(5, 2) NULL,
    condition_notes TEXT NULL,
    recorded_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_soil_field_recorded (field_id, recorded_at),
    CONSTRAINT fk_soil_field FOREIGN KEY (field_id) REFERENCES fields(id) ON DELETE RESTRICT,
    CONSTRAINT chk_soil_ph CHECK (ph_level IS NULL OR ph_level BETWEEN 0 AND 14),
    CONSTRAINT chk_soil_moisture CHECK (moisture_percent IS NULL OR moisture_percent BETWEEN 0 AND 100)
);

CREATE TABLE IF NOT EXISTS crop_rotation_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    field_id INT NOT NULL,
    crop_name VARCHAR(255) NOT NULL,
    season VARCHAR(100) NULL,
    planted_at DATE NULL,
    harvested_at DATE NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rotation_field_planted (field_id, planted_at),
    CONSTRAINT fk_rotation_field FOREIGN KEY (field_id) REFERENCES fields(id) ON DELETE RESTRICT,
    CONSTRAINT chk_rotation_dates CHECK (planted_at IS NULL OR harvested_at IS NULL OR harvested_at >= planted_at)
);
