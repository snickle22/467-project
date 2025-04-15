START TRANSACTION;

-- Drop old tables
DROP TABLE IF EXISTS inventory;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS order_items;

-- Create standalone inventory table
CREATE TABLE inventory (
    part_number INT NOT NULL PRIMARY KEY,      -- Refers to parts.number from blitz DB
    description VARCHAR(50),                   -- Optional snapshot (can remove later if unnecessary)
    stock INT NOT NULL DEFAULT 0               -- Current stock level
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create main orders table
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,        -- Unique identifier for each order
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Date and time the order was placed
    total_price DECIMAL(10, 2) NOT NULL,            -- Total price for the order, including shipping/handling
    shipping_address VARCHAR(255) NOT NULL,         -- Shipping address for the order
    order_status ENUM('pending', 'shipped', 'completed', 'cancelled') DEFAULT 'pending', -- Order status
    shipping_handling_charge DECIMAL(10, 2) DEFAULT 0.00,    -- Shipping charge based on weight and method
    customer_email VARCHAR(50) NOT NULL,    -- Customer email for the order
    tracking_number VARCHAR(100),                   -- Tracking number for shipment
    email_confirmation_sent BOOLEAN DEFAULT FALSE,  -- Whether email confirmation has been sent to customer
    shipment_confirmation_sent BOOLEAN DEFAULT FALSE -- Whether shipment confirmation has been sent
);

-- Create Order Contents table
CREATE TABLE order_items (
    order_id INT,
    product_id INT,
    quantity INT,
    PRIMARY KEY (order_id, product_id)
);

INSERT INTO `inventory` (`part_number`, `description`, `stock`) VALUES
(1, 'windshield w/ polymer', 6),
(2, 'wiper blade pair', 4),
(3, 'solenoid', 4),
(4, 'tiresome mettle', 1),
(5, 'bucket seat pair', 0),
(6, '5 point harness', 1),
(7, 'turbo intake valve', 0),
(8, 'supercharger', 1),
(9, 'inter cooler sweep', 0),
(10, 'gas cap - chrome', 0),
(11, 'chrome brake pedals kit-manual', 0),
(12, 'chrome brake pedals kit-automatic', 0),
(13, 'intel inside window decal', 0),
(14, 'niu alumni window decal', 0),
(15, 'air freshener - lemon', 0),
(16, 'air freshener - cherry', 0),
(17, 'air freshener - new car smell', 0),
(18, 'cargo net (new model)', 0),
(19, 'trunk liner', 0),
(20, 'floor mat - driver side', 0),
(21, 'floor mat - passenger side', 0),
(22, 'car detail kit', 0),
(23, 'tachometer', 0),
(24, 'speedometer mph edition', 0),
(25, 'gps navigation', 0),
(26, 'CD/DVD/DB holder', 0),
(27, 'car charger - micro usb, 2 ft', 0),
(28, 'Backup camera peephole', 0),
(30, 'USB car adapter, NaviPro 2.1', 0),
(31, 'Reverse Sensor, mitigatable', 0),
(33, 'Old Part: 25-03-25 18:38', 0),
(40, '1969 Harley Davidson Ultimate Chopper', 0),
(41, '1952 Alpine Renault 1300', 0),
(42, '1996 Moto Guzzi 1100i', 0),
(43, '2003 Harley-Davidson Eagle Drag Bike', 0),
(44, '1972 Alfa Romeo GTA', 0),
(45, '1962 LanciaA Delta 16V', 0),
(46, '1968 Ford Mustang', 0),
(47, '2001 Ferrari Enzo', 0),
(48, '1958 Setra Bus', 0),
(49, '2002 Suzuki XERO', 0),
(50, '1969 Corvair Monza', 0),
(51, '1968 Dodge Charger', 0),
(52, '1969 Ford Falcon', 0),
(53, '1970 Plymouth Hemi Cuda', 0),
(54, '1957 Chevy Pickup', 0),
(55, '1969 Dodge Charger', 0),
(56, '1940 Ford Pickup Truck', 0),
(57, '1993 Mazda RX-7', 0),
(58, '1937 Lincoln Berline', 0),
(59, '1936 Mercedes-Benz 500K Special Roadster', 0),
(60, '1965 Aston Martin DB5', 0),
(61, '1980s Black Hawk Helicopter', 0),
(62, '1917 Grand Touring Sedan', 0),
(63, '1948 Porsche 356-A Roadster', 0),
(64, '1995 Honda Civic', 0),
(65, '1998 Chrysler Plymouth Prowler', 0),
(66, '1911 Ford Town Car', 0),
(67, '1964 Mercedes Tour Bus', 0),
(68, '1932 Model A Ford J-Coupe', 0),
(69, '1926 Ford Fire Engine', 0),
(70, 'P-51-D Mustang', 0),
(71, '1936 Harley Davidson El Knucklehead', 0),
(72, '1928 Mercedes-Benz SSK', 0),
(73, '1999 Indy 500 Monte Carlo SS', 0),
(74, '1913 Ford Model T Speedster', 0),
(75, '1934 Ford V8 Coupe', 0),
(76, '1999 Yamaha Speed Boat', 0),
(77, '18th Century Vintage Horse Carriage', 0),
(78, '1903 Ford Model A', 0),
(79, '1992 Ferrari 360 Spider red', 0),
(80, '1985 Toyota Supra', 0),
(81, 'Collectable Wooden Train', 0),
(82, '1969 Dodge Super Bee', 0),
(83, '1917 Maxwell Touring Car', 0),
(84, '1976 Ford Gran Torino', 0),
(85, '1948 Porsche Type 356 Roadster', 0),
(86, '1957 Vespa GS150', 0),
(87, '1941 Chevrolet Special Deluxe Cabriolet', 0),
(88, '1970 Triumph Spitfire', 0),
(89, '1932 Alfa Romeo 8C2300 Spider Sport', 0),
(90, '1904 Buick Runabout', 0),
(91, '1940s Ford truck', 0),
(92, '1939 Cadillac Limousine', 0),
(93, '1957 Corvette Convertible', 0),
(94, '1957 Ford Thunderbird', 0),
(95, '1970 Chevy Chevelle SS 454', 0),
(96, '1970 Dodge Coronet', 0),
(97, '1997 BMW R 1100 S', 0),
(98, '1966 Shelby Cobra 427 S/C', 0),
(99, '1928 British Royal Navy Airplane', 0),
(100, '1939 Chevrolet Deluxe Coupe', 0),
(101, '1960 BSA Gold Star DBD34', 0),
(102, '18th century schooner', 0),
(103, '1938 Cadillac V-16 Presidential Limousine', 0),
(104, '1962 Volkswagen Microbus', 0),
(105, '1982 Ducati 900 Monster', 0),
(106, '1949 Jaguar XK 120', 0),
(107, '1958 Chevy Corvette Limited Edition', 0),
(108, '1900s Vintage Bi-Plane', 0),
(109, '1952 Citroen-15CV', 0),
(110, '1982 Lamborghini Diablo', 0),
(111, '1912 Ford Model T Delivery Wagon', 0),
(112, '1969 Chevrolet Camaro Z28', 0),
(113, '1971 Alpine Renault 1600s', 0),
(114, '1937 Horch 930V Limousine', 0),
(115, '2002 Chevy Corvette', 0),
(116, '1940 Ford Delivery Sedan', 0),
(117, '1956 Porsche 356A Coupe', 0),
(118, 'Corsair F4U ( Bird Cage)', 0),
(119, '1936 Mercedes Benz 500k Roadster', 0),
(120, '1992 Porsche Cayenne Turbo Silver', 0),
(121, '1936 Chrysler Airflow', 0),
(122, '1900s Vintage Tri-Plane', 0),
(123, '1961 Chevrolet Impala', 0),
(124, '1980 GM Manhattan Express', 0),
(125, '1997 BMW F650 ST', 0),
(126, '1982 Ducati 996 R', 0),
(127, '1954 Greyhound Scenicruiser', 0),
(128, '1950 Chicago Surface Lines Streetcar', 0),
(129, '1996 Peterbilt 379 Stake Bed with Outrigger', 0),
(130, '1928 Ford Phaeton Deluxe', 0),
(131, '1974 Ducati 350 Mk3 Desmo', 0),
(132, '1930 Buick Marquette Phaeton', 0),
(133, 'Diamond T620 Semi-Skirted Tanker', 0),
(134, '1962 City of Detroit Streetcar', 0),
(135, '2002 Yamaha YZR M1', 0),
(136, 'The Schooner Bluenose', 0),
(137, 'American Airlines: B767-300', 0),
(138, 'The Mayflower', 0),
(139, 'HMS Bounty', 0),
(140, 'America West Airlines B757-200', 0),
(141, 'The USS Constitution Ship', 0),
(142, '1982 Camaro Z28', 0),
(143, 'ATA: B757-300', 0),
(144, 'F/A 18 Hornet 1/72', 0),
(145, 'The Titanic', 0),
(146, 'The Queen Mary', 0),
(147, 'American Airlines: MD-11S', 0),
(148, 'Boeing X-32A JSF', 0),
(149, 'Pont Yacht', 0);

COMMIT;

