-- 005_financials_weather.sql

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farm_id INT NOT NULL,
    type ENUM('income', 'expense'),
    category VARCHAR(100),
    amount DECIMAL(15, 2),
    date DATE
);

CREATE TABLE IF NOT EXISTS loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farm_id INT NOT NULL,
    lender VARCHAR(255),
    amount DECIMAL(15, 2),
    interest_rate DECIMAL(5, 2),
    due_date DATE
);

CREATE TABLE IF NOT EXISTS budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farm_id INT NOT NULL,
    period VARCHAR(50),
    category VARCHAR(100),
    planned_amount DECIMAL(15, 2)
);

CREATE TABLE IF NOT EXISTS weather_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farm_id INT NOT NULL,
    date DATE,
    rainfall_mm DECIMAL(10, 2),
    temp_c DECIMAL(5, 2),
    humidity DECIMAL(5, 2),
    wind_kmh DECIMAL(5, 2)
);
