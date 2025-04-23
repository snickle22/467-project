DROP TABLE IF EXISTS shipping_costs;

-- Create shipping costs table

CREATE TABLE shipping_costs (
    cost_id INT AUTO_INCREMENT PRIMARY KEY,        -- Unique identifier for each order
    start_weight INT NOT NULL,
    end_weight INT NOT NULL,
    shipping_cost DECIMAL(10, 2) NOT NULL

);
