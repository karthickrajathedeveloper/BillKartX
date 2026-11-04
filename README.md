# 🛒 BillKartX - Smart Billing & Inventory Management Software
### Built with PHP + Three.js (100% Offline & Online Ready)

A modern, high-speed POS (Point of Sale) and Inventory Management system built with **PHP**, **SQLite (Zero-Configuration, Portable)**, and **Three.js** interactive 3D visualizations. Works seamlessly **offline without internet** or online on any server.

---

## 🚀 Quick Start (1-Click Run)

### Option A: Double Click (Windows)
Double-click the **`start_pos.bat`** file inside this folder.
- It automatically detects PHP (e.g. `C:\xampp\php\php.exe`).
- Starts the local built-in server at `http://localhost:8080`.
- Automatically opens your default browser!

### Option B: Terminal / Command Prompt
```bash
"C:\xampp\php\php.exe" -S localhost:8080
```
Then open: **`http://localhost:8080`** in your browser.

---

## ✨ Features Included

### 1. 🏷️ Category & Product Management (`products.php`)
- **Categories**: Create categories with custom color tags and descriptions.
- **Products**: Add products under categories with barcode/SKU, purchase price, selling price, and stock quantities.
- **Unit System & Fractional Billing**:
  - Base units supported: `kg`, `g`, `l`, `ml`, `pcs`, `box`, `m`.
  - **Dynamic Unit Calculations**:
    - Example: Base Selling Price = **₹10 / 1 Kg**.
    - If customer buys **500g** &rarr; System automatically charges **₹5.00**!
    - If customer buys **250g** &rarr; System automatically charges **₹2.50**!
    - If customer buys **1.5 Kg** &rarr; System automatically charges **₹15.00**!
    - For liquids: 1 Liter = ₹10 &rarr; 500ml = ₹5.00.
  - Automatic live margin % and profit calculation when setting prices.

### 2. ⚡ POS Fast Billing Screen (`index.php`)
- High-speed product search (by Name or Barcode, shortcut: `Alt+S`).
- Filter products instantly using Category Pills.
- **Quick Unit Preset Buttons**: Instant buttons for `100g`, `250g`, `500g`, `1kg`, `2kg`, `5kg`!
- **Interactive Cart**:
  - Add items to cart.
  - Dynamically increase (`+`) or decrease (`−`) quantity.
  - Switch units directly inside the cart (e.g., toggle between `g` & `kg`).
  - Delete individual items or clear the entire cart.
  - Apply custom Discounts (₹) and configurable GST Tax (%).
  - Customer name, phone number, and payment mode toggle (Cash, UPI / GPay, Card).
- **Instant Receipts & PDF Download**:
  - Generates thermal receipt format (58mm / 80mm) and standard A4 invoice.
  - Download invoice as PDF offline via jsPDF with 1 click.

### 3. 🏢 Three.js 3D Interactive Warehouse & Shelves (`inventory.php` & `index.php`)
- **3D Aisle Shelving Visualizer**:
  - Visualizes store racks and product boxes in real 3D using Three.js.
  - OrbitControls: Left-click drag to rotate, right-click to pan, scroll to zoom.
  - **Color-Coded Stock Status**:
    - 🟢 **Green**: Healthy Stock (> min stock alert)
    - 🟡 **Amber**: Low Stock Warning (<= min stock alert)
    - 🔴 **Red**: Out of Stock (0 balance)
  - Hover over any 3D box to view product details, stock count, and price.
  - Click any 3D box to inspect, restock, or add straight to the bill!

### 4. 📊 Reports & Sales Analytics (`reports.php`)
- **Daily Report**: Today's total sales, cost of goods, net profit, and invoices count.
- **Weekly Report**: Day-by-day sales trend and profit graph.
- **Monthly Report**: Current month's cumulative performance.
- **Three.js 3D Bar Chart**: Interactive 3D bars visualizing daily revenue (Blue) and net profit (Green).
- **Category Sales Performance**: Breakdown of revenue by category.
- **Top 10 Selling Products**: Best performers with total quantity sold and profit.
- **Stock Valuation**: Total inventory valuation at selling price vs cost price.
- **📄 Download Report PDF**: One-click export of complete executive report to PDF.

### 5. 📜 Bill History (`history.php`)
- Filter and search past bills by Invoice Number, Customer Name, or Date Range.
- View detailed breakdown of any past transaction.
- Reprint thermal receipts or re-download PDF copies at any time.

### 6. 🌐 100% Offline & Online Ready
- Uses **SQLite PDO** (`database/pos_inventory.db`): Zero server setup, self-contained, no external database server needed.
- All vendor libraries (**Three.js**, **OrbitControls**, **jsPDF**, **html2canvas**) are bundled locally in `assets/vendor/`.
- Includes PWA **Service Worker (`sw.js`)** and **Web Manifest (`manifest.json`)** for desktop/tablet installation.

---

## 📁 File Structure
```
p_try/
├── assets/
│   ├── css/
│   │   └── app.css                 # Responsive POS stylesheet
│   ├── js/
│   │   ├── app.js                  # POS billing, unit calculation & cart engine
│   │   └── three-inventory.js      # Three.js 3D warehouse & 3D charts engine
│   └── vendor/                     # 100% Offline JS libraries
│       ├── three.min.js
│       ├── OrbitControls.js
│       ├── jspdf.umd.min.js
│       └── html2canvas.min.js
├── database/
│   ├── db.php                      # SQLite connection & schema initialization
│   └── pos_inventory.db            # Auto-generated SQLite database
├── includes/
│   ├── header.php                  # Common navigation bar & offline detector
│   └── footer.php                  # Common footer & service worker registration
├── api.php                         # REST API for billing, stock, products, reports
├── index.php                       # POS Terminal billing screen with 3D peek
├── products.php                    # Category & Product management
├── inventory.php                   # Full 3D Warehouse & Stock Control
├── history.php                     # Bill history, search & reprint
├── reports.php                     # Daily, Weekly, Monthly Reports & PDF export
├── manifest.json                   # PWA Manifest
├── sw.js                           # Offline Service Worker
├── start_pos.bat                   # 1-Click Windows Launcher
└── README.md                       # Documentation
```

