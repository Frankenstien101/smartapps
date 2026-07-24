-- =====================================================================
-- Digital Call Sheet System — Schema + Sample Seed Data
-- For BlueSun Philippines (FMCG distributor), P&G product line sample
-- Target: SQL Server (matches config/database.php's sqlsrv connection)
-- =====================================================================
-- Run this against the 'DigitalCallSheet' database referenced in
-- config/database.php (create that database first if it doesn't exist).

-- ---------------------------------------------------------------------
-- SCHEMA
-- ---------------------------------------------------------------------

CREATE TABLE users (
    user_id INT IDENTITY(1,1) PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    role VARCHAR(20) NOT NULL,          -- 'Admin', 'SalesRep', 'Buyer'
    is_active BIT NOT NULL DEFAULT 1
);

CREATE TABLE products (
    product_id INT IDENTITY(1,1) PRIMARY KEY,
    product_code VARCHAR(30) NOT NULL UNIQUE,
    product_name VARCHAR(150) NOT NULL,
    brand VARCHAR(100),
    category VARCHAR(100),
    unit_of_measure VARCHAR(20),
    cost_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    selling_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    reorder_level INT NOT NULL DEFAULT 0,
    lead_time_days INT NOT NULL DEFAULT 3,
    safety_stock INT NOT NULL DEFAULT 0,
    is_active BIT NOT NULL DEFAULT 1
);

CREATE TABLE customers (
    customer_id INT IDENTITY(1,1) PRIMARY KEY,
    customer_name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(150),
    contact_number VARCHAR(30),
    address VARCHAR(255),
    is_active BIT NOT NULL DEFAULT 1
);

CREATE TABLE inventory (
    product_id INT PRIMARY KEY REFERENCES products(product_id),
    current_stock INT NOT NULL DEFAULT 0,
    incoming_stock INT NOT NULL DEFAULT 0,
    last_updated DATETIME DEFAULT GETDATE()
);

CREATE TABLE stock_history (
    history_id INT IDENTITY(1,1) PRIMARY KEY,
    product_id INT NOT NULL REFERENCES products(product_id),
    transaction_type VARCHAR(20) NOT NULL,  -- 'IN', 'OUT', 'ADJUST'
    quantity INT NOT NULL,
    reference VARCHAR(150),
    created_by INT REFERENCES users(user_id),
    created_at DATETIME DEFAULT GETDATE()
);

CREATE TABLE sales_history (
    sales_id INT IDENTITY(1,1) PRIMARY KEY,
    product_id INT NOT NULL REFERENCES products(product_id),
    customer_id INT REFERENCES customers(customer_id),
    sales_date DATE NOT NULL,
    quantity_sold INT NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    recorded_at DATETIME DEFAULT GETDATE()
);

CREATE TABLE call_sheets (
    call_sheet_id INT IDENTITY(1,1) PRIMARY KEY,
    sheet_number VARCHAR(50) NOT NULL UNIQUE,
    created_by INT NOT NULL REFERENCES users(user_id),
    created_date DATETIME DEFAULT GETDATE(),
    status VARCHAR(20) NOT NULL DEFAULT 'Draft', -- Draft, Submitted, Approved, Rejected, Converted
    buyer_remarks VARCHAR(500),
    approved_by INT REFERENCES users(user_id),
    approved_date DATETIME
);

CREATE TABLE call_sheet_items (
    item_id INT IDENTITY(1,1) PRIMARY KEY,
    call_sheet_id INT NOT NULL REFERENCES call_sheets(call_sheet_id),
    product_id INT NOT NULL REFERENCES products(product_id),
    suggested_qty INT NOT NULL DEFAULT 0,
    final_qty INT NOT NULL DEFAULT 0,
    reason VARCHAR(255),
    unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0
);

CREATE TABLE purchase_orders (
    po_id INT IDENTITY(1,1) PRIMARY KEY,
    po_number VARCHAR(50) NOT NULL UNIQUE,
    call_sheet_id INT REFERENCES call_sheets(call_sheet_id),
    order_date DATETIME DEFAULT GETDATE(),
    status VARCHAR(20) NOT NULL DEFAULT 'Pending', -- Pending, Completed
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_by INT NOT NULL REFERENCES users(user_id)
);

CREATE TABLE po_items (
    po_item_id INT IDENTITY(1,1) PRIMARY KEY,
    po_id INT NOT NULL REFERENCES purchase_orders(po_id),
    product_id INT NOT NULL REFERENCES products(product_id),
    quantity INT NOT NULL,
    unit_cost DECIMAL(12,2) NOT NULL,
    line_total DECIMAL(12,2) NOT NULL
);
GO

-- ---------------------------------------------------------------------
-- SEED: Users
-- IMPORTANT: 'REPLACE_WITH_GENERATED_HASH' is a placeholder.
-- Run database/generate_hash.php once on your PHP server to get a real
-- bcrypt hash for 'admin123', then UPDATE users SET password_hash = '...'
-- WHERE username = 'admin'; (see instructions at the bottom of this file)
-- ---------------------------------------------------------------------

INSERT INTO users (username, password_hash, full_name, role, is_active) VALUES
('admin',   'REPLACE_WITH_GENERATED_HASH', 'System Administrator', 'Admin',    1),
('jsantos', 'REPLACE_WITH_GENERATED_HASH', 'Juan Santos',          'SalesRep', 1),
('mreyes',  'REPLACE_WITH_GENERATED_HASH', 'Maria Reyes',          'Buyer',    1);
GO

-- ---------------------------------------------------------------------
-- SEED: Products — P&G product line (Philippines market), for BlueSun
-- ---------------------------------------------------------------------

INSERT INTO products (product_code, product_name, brand, category, unit_of_measure, cost_price, selling_price, reorder_level, lead_time_days, safety_stock, is_active) VALUES
('PG-TID-1KG',  'Tide Powder Detergent 1kg',            'Tide',            'Fabric Care',   'pack',  145.00, 168.00, 40, 5, 20, 1),
('PG-ARL-1KG',  'Ariel Powder Detergent 1kg',           'Ariel',           'Fabric Care',   'pack',  148.00, 172.00, 40, 5, 20, 1),
('PG-DWY-800',  'Downy Fabric Conditioner 800ml',       'Downy',           'Fabric Care',   'bottle',  95.00, 112.00, 35, 5, 15, 1),
('PG-SFG-135',  'Safeguard Bar Soap 135g',              'Safeguard',       'Personal Care', 'piece',   22.00,  28.00, 100, 4, 40, 1),
('PG-HNS-340',  'Head & Shoulders Shampoo 340ml',       'Head & Shoulders','Personal Care', 'bottle', 175.00, 205.00, 30, 6, 15, 1),
('PG-PAN-340',  'Pantene Shampoo 340ml',                'Pantene',         'Personal Care', 'bottle', 168.00, 198.00, 30, 6, 15, 1),
('PG-JOY-1L',   'Joy Dishwashing Liquid 1L',            'Joy',             'Home Care',     'bottle',  85.00, 102.00, 45, 4, 20, 1),
('PG-PMP-M36',  'Pampers Baby Dry Diapers Medium x36',  'Pampers',         'Baby Care',     'pack',  420.00, 485.00, 25, 7, 10, 1),
('PG-WSP-10',   'Whisper Ultra Thin Sanitary Napkin x10','Whisper',        'Feminine Care', 'pack',   58.00,  72.00, 50, 5, 20, 1),
('PG-GIL-3UP',  'Gillette 3-Up Disposable Razor',       'Gillette',        'Personal Care', 'piece',   35.00,  45.00, 60, 6, 25, 1),
('PG-ORB-CB',   'Oral-B Complete Clean Toothbrush',     'Oral-B',          'Personal Care', 'piece',   45.00,  58.00, 60, 6, 25, 1),
('PG-OLY-50',   'Olay White Radiance Cream 50g',        'Olay',            'Personal Care', 'jar',    155.00, 189.00, 20, 7, 10, 1);
GO

-- ---------------------------------------------------------------------
-- SEED: Inventory (current stock deliberately mixed: some low, some ok)
-- ---------------------------------------------------------------------

INSERT INTO inventory (product_id, current_stock, incoming_stock) VALUES
(1, 25, 0),   -- Tide: below reorder level (40) -> should trigger suggestion
(2, 60, 0),   -- Ariel: healthy stock
(3, 18, 0),   -- Downy: below reorder level (35)
(4, 30, 50),  -- Safeguard: below reorder (100) but incoming covers it
(5, 40, 0),   -- Head & Shoulders: healthy
(6, 10, 0),   -- Pantene: below reorder (30)
(7, 55, 0),   -- Joy: healthy
(8, 8, 0),    -- Pampers: below reorder (25)
(9, 70, 0),   -- Whisper: healthy
(10, 20, 0),  -- Gillette: below reorder (60)
(11, 65, 0),  -- Oral-B: healthy
(12, 12, 0);  -- Olay: below reorder (20)
GO

-- ---------------------------------------------------------------------
-- SEED: Customers (BlueSun Philippines — Soccsksargen-area accounts)
-- ---------------------------------------------------------------------

INSERT INTO customers (customer_name, contact_person, contact_number, address, is_active) VALUES
('Puregold Koronadal',            'Liza Fernandez',  '09171234501', 'National Highway, Koronadal City, South Cotabato', 1),
('Alturas Supermarket - Koronadal','Ramon Cruz',      '09171234502', 'Gensan Drive, Koronadal City, South Cotabato', 1),
('KCC Mall General Santos',       'Ellen Villanueva', '09171234503', 'Santiago Blvd, General Santos City', 1),
('Gaisano Mall Tacurong',         'Paolo Ramos',      '09171234504', 'Mabini St, Tacurong City, Sultan Kudarat', 1),
('Aling Rosa Sari-Sari Store',    'Rosa Delos Santos','09171234505', 'Purok 3, Barangay Zone II, Koronadal City', 1);
GO

-- ---------------------------------------------------------------------
-- SEED: Sales history (last 30 days) — enough volume for
-- getSuggestedOrder() in includes/functions.php to compute real averages
-- ---------------------------------------------------------------------

DECLARE @i INT = 0;
WHILE @i < 30
BEGIN
    -- Tide: steady ~8/day -> will look low vs current stock of 25
    INSERT INTO sales_history (product_id, customer_id, sales_date, quantity_sold, unit_price, total_amount)
    VALUES (1, 1, DATEADD(day, -@i, CAST(GETDATE() AS DATE)), 6 + (@i % 5), 168.00, (6 + (@i % 5)) * 168.00);

    -- Pantene: high movement -> will look low vs current stock of 10
    INSERT INTO sales_history (product_id, customer_id, sales_date, quantity_sold, unit_price, total_amount)
    VALUES (6, 2, DATEADD(day, -@i, CAST(GETDATE() AS DATE)), 4 + (@i % 3), 198.00, (4 + (@i % 3)) * 198.00);

    -- Pampers: moderate movement -> will look low vs current stock of 8
    IF @i % 2 = 0
    INSERT INTO sales_history (product_id, customer_id, sales_date, quantity_sold, unit_price, total_amount)
    VALUES (8, 3, DATEADD(day, -@i, CAST(GETDATE() AS DATE)), 3, 485.00, 3 * 485.00);

    -- Joy: healthy/slow movement, stock is fine
    IF @i % 3 = 0
    INSERT INTO sales_history (product_id, customer_id, sales_date, quantity_sold, unit_price, total_amount)
    VALUES (7, 4, DATEADD(day, -@i, CAST(GETDATE() AS DATE)), 5, 102.00, 5 * 102.00);

    SET @i = @i + 1;
END
GO

-- =====================================================================
-- NEXT STEP — set real password hashes:
--   1. Run: php database/generate_hash.php   (see that file)
--   2. Copy the printed hash, then run for each seeded user, e.g.:
--      UPDATE users SET password_hash = '<hash>' WHERE username = 'admin';
--   All three seeded users share the password 'admin123' by default —
--   change this after first login, per the note already on the login page.
-- =====================================================================
