DROP DATABASE IF EXISTS assignment1;
CREATE DATABASE assignment1;
use assignment1;

-- Create categories table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL
);

-- Create subcategories table
CREATE TABLE subcategories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Create products table
CREATE TABLE products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    product_name VARCHAR(255) NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    unit_quantity VARCHAR(50) NOT NULL,
    in_stock INT NOT NULL,
    category_id INT NOT NULL,
    subcategory_id INT,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id)
);

-- Insert categories
INSERT INTO categories (id, name) VALUES
(1, 'Frozen Foods'),
(2, 'Health & Beauty'),
(3, 'Dairy & Meat'),
(4, 'Fruits & Vegetables'),
(5, 'Beverages & Snacks'),
(6, 'Pet Supplies');

-- Insert subcategories
INSERT INTO subcategories (id, category_id, name) VALUES
-- Frozen Foods subcategories
(101, 1, 'Frozen Meals'),
(102, 1, 'Ice Cream'),
(103, 1, 'Frozen Seafood'),
-- Health & Beauty subcategories
(201, 2, 'Medicines'),
(202, 2, 'Personal Care'),
-- Dairy & Meat subcategories
(301, 3, 'Dairy'),
(302, 3, 'Meat'),
(303, 3, 'Seafood'),
-- Fruits & Vegetables subcategories
(401, 4, 'Fruits'),
(402, 4, 'Vegetables'),
-- Beverages & Snacks subcategories
(501, 5, 'Beverages'),
(502, 5, 'Snacks'),
(503, 5, 'Tea & Coffee'),
-- Pet Supplies subcategories
(601, 6, 'Dog Food'),
(602, 6, 'Cat Food'),
(603, 6, 'Other Pet Food');

-- Insert products with category and subcategory
INSERT INTO products (product_id, product_name, unit_price, unit_quantity, in_stock, category_id, subcategory_id) VALUES
-- Frozen Foods
(1000, 'Fish Fingers', 2.55, '500 gram', 1500, 1, 103),
(1001, 'Fish Fingers', 5.00, '1000 gram', 750, 1, 103),
(1002, 'Hamburger Patties', 2.35, 'Pack 10', 1200, 1, 101),
(1003, 'Shelled Prawns', 6.90, '250 gram', 300, 1, 103),
(1004, 'Tub Ice Cream', 1.80, 'I Litre', 800, 1, 102),
(1005, 'Tub Ice Cream', 3.40, '2 Litre', 1200, 1, 102),

-- Health & Beauty
(2000, 'Panadol', 3.00, 'Pack 24', 2000, 2, 201),
(2001, 'Panadol', 5.50, 'Bottle 50', 1000, 2, 201),
(2002, 'Bath Soap', 2.60, 'Pack 6', 500, 2, 202),
(2005, 'Washing Powder', 4.00, '1000 gram', 800, 2, 202),
(2006, 'Laundry Bleach', 3.55, '2 Litre Bottle', 500, 2, 202),

-- Dairy & Meat
(3000, 'Cheddar Cheese', 8.00, '500 gram', 1000, 3, 301),
(3001, 'Cheddar Cheese', 15.00, '1000 gram', 1000, 3, 301),
(3002, 'T Bone Steak', 7.00, '1000 gram', 200, 3, 302),

-- Fruits & Vegetables
(3003, 'Navel Oranges', 3.99, 'Bag 20', 200, 4, 401),
(3004, 'Bananas', 1.49, 'Kilo', 400, 4, 401),
(3005, 'Peaches', 2.99, 'Kilo', 500, 4, 401),
(3006, 'Grapes', 3.50, 'Kilo', 200, 4, 401),
(3007, 'Apples', 1.99, 'Kilo', 500, 4, 401),

-- Beverages & Snacks
(4000, 'Earl Grey Tea Bags', 2.49, 'Pack 25', 1200, 5, 503),
(4001, 'Earl Grey Tea Bags', 7.25, 'Pack 100', 1200, 5, 503),
(4002, 'Earl Grey Tea Bags', 13.00, 'Pack 200', 800, 5, 503),
(4003, 'Instant Coffee', 2.89, '200 gram', 500, 5, 503),
(4004, 'Instant Coffee', 5.10, '500 gram', 500, 5, 503),
(4005, 'Chocolate Bar', 2.50, '500 gram', 300, 5, 502),

-- Pet Supplies
(5000, 'Dry Dog Food', 5.95, '5 kg Pack', 400, 6, 601),
(5001, 'Dry Dog Food', 1.95, '1 kg Pack', 400, 6, 601),
(5002, 'Bird Food', 3.99, '500g packet', 200, 6, 603),
(5003, 'Cat Food', 2.00, '500g tin', 200, 6, 602),
(5004, 'Fish Food', 3.00, '500g packet', 200, 6, 603); 