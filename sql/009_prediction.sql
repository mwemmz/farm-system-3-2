-- 009_prediction.sql

CREATE TABLE IF NOT EXISTS price_trends (
    id INT AUTO_INCREMENT PRIMARY KEY,
    crop_id INT NOT NULL,
    market VARCHAR(100),
    date DATE,
    price DECIMAL(15, 2)
);

CREATE TABLE IF NOT EXISTS revenue_forecasts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    planting_id INT NOT NULL,
    expected_price DECIMAL(15, 2),
    expected_revenue DECIMAL(15, 2)
);
