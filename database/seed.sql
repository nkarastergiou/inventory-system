USE inventory_system;

INSERT INTO categories (name, description) VALUES
('Electronics', 'Electronic devices and accessories'),
('Office Supplies', 'Basic office products'),
('Computer Hardware', 'PC parts and hardware components');

INSERT INTO suppliers (name, email, phone, address) VALUES
('TechWorld Supplies', 'info@techworld.com', '+302410111111', 'Larisa, Greece'),
('OfficeMarket', 'sales@officemarket.com', '+302410222222', 'Athens, Greece'),
('HardwarePro', 'contact@hardwarepro.com', '+302410333333', 'Thessaloniki, Greece');

INSERT INTO products 
(category_id, supplier_id, name, sku, description, quantity, min_stock, price) 
VALUES
(1, 1, 'Wireless Mouse', 'ELEC-001', 'Basic wireless mouse', 25, 5, 14.99),
(1, 1, 'USB-C Cable', 'ELEC-002', '1 meter USB-C charging cable', 40, 10, 7.50),
(2, 2, 'A4 Paper Pack', 'OFF-001', '500 sheets of A4 paper', 12, 5, 5.99),
(2, 2, 'Blue Pens Pack', 'OFF-002', 'Pack of 10 blue pens', 30, 8, 3.49),
(3, 3, '8GB RAM Module', 'HW-001', 'DDR4 8GB RAM module', 4, 5, 24.99),
(3, 3, 'SSD 500GB', 'HW-002', '500GB SATA SSD', 7, 3, 39.99);

INSERT INTO stock_movements 
(product_id, movement_type, quantity, note) 
VALUES
(1, 'in', 25, 'Initial stock'),
(2, 'in', 40, 'Initial stock'),
(3, 'in', 12, 'Initial stock'),
(4, 'in', 30, 'Initial stock'),
(5, 'in', 4, 'Initial stock'),
(6, 'in', 7, 'Initial stock');