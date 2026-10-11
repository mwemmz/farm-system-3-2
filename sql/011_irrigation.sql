-- Developer 1 Phase 2A: irrigation and water management.
CREATE TABLE IF NOT EXISTS irrigation_systems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farm_id INT NOT NULL,
    field_id INT NULL,
    name VARCHAR(255) NOT NULL,
    system_type ENUM('drip','sprinkler','flood','manual','other') NOT NULL,
    water_source_name VARCHAR(255) NULL,
    water_source_type ENUM('borehole','well','river','reservoir','tank','municipal','other') NULL,
    water_source_capacity_liters DECIMAL(15,2) NULL,
    pump_name VARCHAR(255) NULL,
    pump_status ENUM('operational','maintenance','fault','offline') NULL,
    flow_rate_lph DECIMAL(15,2) NULL,
    installation_date DATE NULL,
    status ENUM('active','inactive','maintenance') NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_irrigation_farm_status (farm_id,status),
    INDEX idx_irrigation_field (field_id),
    CONSTRAINT fk_irrigation_farm FOREIGN KEY (farm_id) REFERENCES farms(id) ON DELETE RESTRICT,
    CONSTRAINT fk_irrigation_field FOREIGN KEY (field_id) REFERENCES fields(id) ON DELETE RESTRICT,
    CONSTRAINT chk_irrigation_capacity CHECK (water_source_capacity_liters IS NULL OR water_source_capacity_liters > 0),
    CONSTRAINT chk_irrigation_flow CHECK (flow_rate_lph IS NULL OR flow_rate_lph > 0)
);

CREATE TABLE IF NOT EXISTS irrigation_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    irrigation_system_id INT NOT NULL,
    scheduled_at DATETIME NOT NULL,
    duration_minutes INT NOT NULL,
    planned_volume_liters DECIMAL(15,2) NULL,
    status ENUM('scheduled','completed','skipped','cancelled') NOT NULL DEFAULT 'scheduled',
    weather_adjusted BOOLEAN NOT NULL DEFAULT FALSE,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_schedule_system_time (irrigation_system_id,scheduled_at),
    CONSTRAINT fk_schedule_system FOREIGN KEY (irrigation_system_id) REFERENCES irrigation_systems(id) ON DELETE RESTRICT,
    CONSTRAINT chk_schedule_duration CHECK (duration_minutes > 0),
    CONSTRAINT chk_schedule_volume CHECK (planned_volume_liters IS NULL OR planned_volume_liters > 0)
);

CREATE TABLE IF NOT EXISTS water_usage_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    irrigation_system_id INT NOT NULL,
    schedule_id INT NULL,
    logged_at DATETIME NOT NULL,
    volume_liters DECIMAL(15,2) NOT NULL,
    duration_minutes INT NOT NULL,
    energy_kwh DECIMAL(15,2) NULL,
    cost_amount DECIMAL(15,2) NULL,
    pump_status ENUM('operational','maintenance','fault','offline') NULL,
    issue_notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usage_system_time (irrigation_system_id,logged_at),
    INDEX idx_usage_schedule (schedule_id),
    CONSTRAINT fk_usage_system FOREIGN KEY (irrigation_system_id) REFERENCES irrigation_systems(id) ON DELETE RESTRICT,
    CONSTRAINT fk_usage_schedule FOREIGN KEY (schedule_id) REFERENCES irrigation_schedules(id) ON DELETE RESTRICT,
    CONSTRAINT chk_usage_volume CHECK (volume_liters > 0),
    CONSTRAINT chk_usage_duration CHECK (duration_minutes > 0),
    CONSTRAINT chk_usage_energy CHECK (energy_kwh IS NULL OR energy_kwh >= 0),
    CONSTRAINT chk_usage_cost CHECK (cost_amount IS NULL OR cost_amount >= 0)
);
