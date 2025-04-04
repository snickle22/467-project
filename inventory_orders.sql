USE z1946034;

-- Drop tables if they exist already
DROP TABLE IF EXISTS inventory;
DROP TABLE IF EXISTS orders;

-- Create tables
CREATE TABLE inventory (
    part_number VARCHAR(50) PRIMARY KEY,
    quantity INT NOT NULL DEFAULT 0
);

CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,        -- Unique identifier for each order
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Date and time the order was placed
    total_price DECIMAL(10, 2) NOT NULL,            -- Total price for the order, including shipping/handling
    shipping_address VARCHAR(255) NOT NULL,         -- Shipping address for the order
    order_status ENUM('pending', 'shipped', 'completed', 'cancelled') DEFAULT 'pending', -- Order status
    shipping_charge DECIMAL(10, 2) DEFAULT 0.00,    -- Shipping charge based on weight and method
    handling_charge DECIMAL(10, 2) DEFAULT 0.00,    -- Handling charge, if applicable
    tracking_number VARCHAR(100),                   -- Tracking number for shipment
    email_confirmation_sent BOOLEAN DEFAULT FALSE,  -- Whether email confirmation has been sent to customer
    shipment_confirmation_sent BOOLEAN DEFAULT FALSE -- Whether shipment confirmation has been sent
);

-- Insert sample data into inventory table
INSERT INTO inventory (part_number, quantity) VALUES
('1', 100),
('2', 200),
('3', 150),
('4', 50),
('5', 75);



