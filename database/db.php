<?php
/**
 * Database Connection & Initializer (SQLite PDO)
 * Offline-first, zero-config, portable
 */

$dbDir = __DIR__;
$dbPath = $dbDir . DIRECTORY_SEPARATOR . 'pos_inventory.db';

try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("PRAGMA foreign_keys = ON;");
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}

/**
 * Initialize Tables and Seed Initial Demo Data if empty
 */
function initDatabase($pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            description TEXT,
            color TEXT DEFAULT '#4f46e5',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            category_id INTEGER NOT NULL,
            barcode TEXT,
            name TEXT NOT NULL,
            base_unit TEXT NOT NULL DEFAULT 'kg', -- 'kg', 'g', 'l', 'ml', 'pcs', 'box', 'm'
            purchase_price REAL NOT NULL DEFAULT 0.0,
            selling_price REAL NOT NULL DEFAULT 0.0,
            stock_qty REAL NOT NULL DEFAULT 0.0, -- In base units
            min_stock_alert REAL DEFAULT 5.0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
        );

        CREATE TABLE IF NOT EXISTS bills (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            bill_number TEXT UNIQUE NOT NULL,
            customer_name TEXT DEFAULT 'Walk-in Customer',
            customer_phone TEXT DEFAULT '',
            subtotal REAL NOT NULL,
            discount REAL DEFAULT 0.0,
            tax REAL DEFAULT 0.0,
            grand_total REAL NOT NULL,
            payment_method TEXT DEFAULT 'Cash',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS bill_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            bill_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            product_name TEXT NOT NULL,
            selected_unit TEXT NOT NULL, -- 'kg', 'g', 'l', 'ml', 'pcs'
            entered_qty REAL NOT NULL,
            base_unit_qty REAL NOT NULL, -- Qty converted to product's base unit for stock decrement
            unit_price REAL NOT NULL,
            purchase_price REAL NOT NULL,
            item_total REAL NOT NULL,
            FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
        );

        CREATE TABLE IF NOT EXISTS stock_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            type TEXT NOT NULL, -- 'PURCHASE', 'SALE', 'ADJUSTMENT', 'INITIAL'
            qty REAL NOT NULL,
            balance_after REAL NOT NULL,
            note TEXT DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
        );

        CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT UNIQUE NOT NULL,
            setting_value TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS returns (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            return_number TEXT UNIQUE NOT NULL,
            bill_id INTEGER DEFAULT NULL,
            bill_number TEXT NOT NULL DEFAULT 'DIRECT-RETURN',
            customer_name TEXT DEFAULT 'Walk-in Customer',
            customer_phone TEXT DEFAULT '',
            total_refund REAL NOT NULL DEFAULT 0.0,
            refund_method TEXT DEFAULT 'Cash',
            return_reason TEXT DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS return_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            return_id INTEGER NOT NULL,
            bill_item_id INTEGER DEFAULT NULL,
            product_id INTEGER NOT NULL,
            product_name TEXT NOT NULL,
            selected_unit TEXT NOT NULL,
            return_qty REAL NOT NULL,
            base_unit_qty REAL NOT NULL,
            refund_rate REAL NOT NULL,
            refund_amount REAL NOT NULL,
            restock INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (return_id) REFERENCES returns(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
        );
    ");

    // Seed default settings if not exists
    $defaultSettings = [
        'store_name' => 'BillKartX Smart Billing',
        'store_address' => '123 Main Bazaar Road, Anna Nagar, Chennai',
        'store_phone' => '+91 98765 43210',
        'store_gst' => '33AAAAA0000A1Z5',
        'currency_symbol' => '₹',
        'tax_rate' => '5',
        'upi_id' => 'billkartx@upi',
        'receipt_header' => 'Tax Invoice / Retail Bill',
        'receipt_footer' => '*** Thank you for shopping with BillKartX! Visit again ***',
        'printer_paper_width' => '58mm',
        'enable_gst' => '1'
    ];
    $insert = $pdo->prepare("INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($defaultSettings as $k => $v) {
        $insert->execute([$k, $v]);
    }

    // Seed sample categories & products if categories is empty
    $catCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ($catCount == 0) {
        $categories = [
            ['Grocery & Grains', 'Daily essential groceries, rice, pulses', '#2563eb'],
            ['Spices & Masala', 'Cooking spices and masalas', '#d97706'],
            ['Vegetables & Fruits', 'Fresh farm produce', '#16a34a'],
            ['Dairy & Beverages', 'Milk, butter, juices and soft drinks', '#9333ea'],
            ['Snacks & Confectionery', 'Biscuits, chips, chocolates', '#dc2626']
        ];
        $catStmt = $pdo->prepare("INSERT INTO categories (name, description, color) VALUES (?, ?, ?)");
        foreach ($categories as $cat) {
            $catStmt->execute($cat);
        }

        // Sample products
        // Notice unit pricing demonstration:
        // Rice: 1 Kg = ₹60 selling, ₹45 purchase
        // Sugar: 1 Kg = ₹44 selling, ₹36 purchase
        // Milk: 1 Liter = ₹32 selling, ₹26 purchase
        // Turmeric: 1 Kg = ₹200 selling, ₹150 purchase (can be bought as 100g, 250g, 500g)
        $products = [
            [1, '8901001', 'Ponni Boiled Rice', 'kg', 45.0, 60.0, 150.0, 20.0],
            [1, '8901002', 'White Sugar (Crystal)', 'kg', 36.0, 44.0, 100.0, 15.0],
            [1, '8901003', 'Toor Dal Premium', 'kg', 110.0, 140.0, 80.0, 10.0],
            [2, '8902001', 'Turmeric Powder (Pure)', 'kg', 160.0, 220.0, 30.0, 5.0],
            [2, '8902002', 'Black Pepper Whole', 'kg', 550.0, 750.0, 25.0, 3.0],
            [3, '8903001', 'Fresh Red Tomatoes', 'kg', 25.0, 40.0, 50.0, 10.0],
            [3, '8903002', 'Nashik Onions', 'kg', 30.0, 50.0, 60.0, 10.0],
            [4, '8904001', 'Arokya Pure Milk', 'l', 28.0, 34.0, 40.0, 5.0],
            [4, '8904002', 'Gold Winner Oil', 'l', 120.0, 145.0, 45.0, 8.0],
            [5, '8905001', 'Parle-G Biscuits (Pack)', 'pcs', 8.0, 10.0, 120.0, 20.0],
            [5, '8905002', 'Lays Classic Salted', 'pcs', 16.0, 20.0, 80.0, 15.0]
        ];

        $prodStmt = $pdo->prepare("INSERT INTO products (category_id, barcode, name, base_unit, purchase_price, selling_price, stock_qty, min_stock_alert) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $logStmt = $pdo->prepare("INSERT INTO stock_logs (product_id, type, qty, balance_after, note) VALUES (?, 'INITIAL', ?, ?, 'Initial inventory setup')");

        foreach ($products as $p) {
            $prodStmt->execute($p);
            $newId = $pdo->lastInsertId();
            $logStmt->execute([$newId, $p[6], $p[6]]);
        }
    }
}

// Automatically initialize schema and seed on include
initDatabase($pdo);

