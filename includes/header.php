<?php
/**
 * Navigation & Header Layout
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BillKartX - Smart Billing & Inventory System</title>
    <!-- Favicon & App Icons -->
    <link rel="icon" type="image/png" href="assets/RuPay-Inspired K Finance Logo-Favicon.png">
    <link rel="shortcut icon" type="image/png" href="assets/RuPay-Inspired K Finance Logo-Favicon.png">
    <link rel="apple-touch-icon" href="assets/RuPay-Inspired K Finance Logo-Favicon.png">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2563eb">

    <!-- Offline-ready Styles -->
    <link rel="stylesheet" href="assets/css/app.css">

    <!-- Vendor Libraries (Bundled locally for 100% offline usage) -->
    <script src="assets/vendor/three.min.js"></script>
    <script src="assets/vendor/OrbitControls.js"></script>
    <script src="assets/vendor/jspdf.umd.min.js"></script>
    <script src="assets/vendor/html2canvas.min.js"></script>
    <script src="assets/vendor/xlsx.full.min.js"></script>
    <!-- Bluetooth Thermal Printer Driver -->
    <script src="assets/js/bluetooth-printer.js"></script>
</head>
<body>
    <!-- Top Navigation Bar -->
    <header class="navbar">
        <a href="index.php" class="nav-brand" title="BillKartX - Smart Billing & Inventory">
            <div class="brand-icon-badge">
                <img src="assets/RuPay-Inspired K Finance Logo-Favicon.png" alt="BillKartX Logo" class="brand-icon-img">
            </div>
            <div class="brand-text-block">
                <div class="brand-title-stylish">
                    <span class="brand-word-bill">Bill</span><span class="brand-word-kart">Kart</span><span class="brand-word-x">X</span>
                </div>
                <div class="brand-tagline-stylish">
                    <span>Smart Billing</span><span class="brand-dot">&bull;</span><span>Smart POS</span>
                </div>
            </div>
        </a>

        <ul class="nav-links" id="navLinksMenu">
            <li class="nav-item <?= $currentPage === 'index.php' ? 'active' : '' ?>">
                <a href="index.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    POS Billing
                    <span id="navCartBadge" class="nav-cart-badge" style="display: none;">0</span>
                </a>
            </li>
            <li class="nav-item <?= $currentPage === 'products.php' ? 'active' : '' ?>">
                <a href="products.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line>
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    </svg>
                    Products & Categories
                </a>
            </li>
            <li class="nav-item <?= $currentPage === 'inventory.php' ? 'active' : '' ?>">
                <a href="inventory.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                        <polyline points="2 17 12 22 22 17"></polyline>
                        <polyline points="2 12 12 17 22 12"></polyline>
                    </svg>
                    3D Warehouse & Stock
                </a>
            </li>
            <li class="nav-item <?= $currentPage === 'history.php' ? 'active' : '' ?>">
                <a href="history.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 14 14"></polyline>
                    </svg>
                    Bill History
                </a>
            </li>
            <li class="nav-item <?= $currentPage === 'returns.php' ? 'active' : '' ?>">
                <a href="returns.php" title="Returned Products & Customer Refunds Management">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="1 4 1 10 7 10"></polyline>
                        <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                    </svg>
                    Returns & Refunds
                </a>
            </li>
            <li class="nav-item <?= $currentPage === 'reports.php' ? 'active' : '' ?>">
                <a href="reports.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    Reports & Analytics
                </a>
            </li>
            <li class="nav-item">
                <a href="javascript:void(0)" onclick="openSettingsModal()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                    Settings & Printer
                </a>
            </li>
        </ul>

        <div class="nav-right">
            <div class="nav-status" style="display: flex; gap: 0.5rem; align-items: center;">
                <span id="navBtPrinterBadge" class="status-badge" style="display: none; cursor: pointer;" onclick="openSettingsModal()" title="Bluetooth Thermal Printer">
                    <span class="dot"></span>
                    <span id="navBtPrinterText">🖨️ Printer</span>
                </span>
                <span id="networkStatusBadge" class="status-badge online">
                    <span class="dot"></span>
                    <span id="networkStatusText">Online</span>
                </span>
            </div>
            <button class="nav-toggle" id="navToggleBtn" type="button" aria-label="Toggle navigation" onclick="toggleMobileNav()">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
        </div>
    </header>

    <!-- Global Settings & Bluetooth Printer Modal -->
    <div id="settingsModal" class="modal-overlay">
        <div class="modal-card modal-lg">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 0.6rem;">
                    <img src="assets/RuPay-Inspired K Finance Logo-Favicon.png" alt="BillKartX" style="width: 26px; height: 26px; border-radius: 6px; object-fit: cover;">
                    <h3 class="modal-title">BillKartX Settings & Bluetooth Thermal Printer</h3>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeSettingsModal()">✕</button>
            </div>
            <form id="settingsForm" onsubmit="saveGlobalSettings(event)">
                <div class="modal-body" style="gap: 1.5rem;">
                    
                    <!-- Section 1: Bluetooth Thermal Printer (58mm) -->
                    <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span style="font-size: 1.3rem;">📶</span>
                                <div>
                                    <h4 style="font-weight: 700; color: #0f172a; margin: 0; font-size: 1rem;">Bluetooth Thermal Printer (58mm)</h4>
                                    <span style="font-size: 0.8rem; color: #64748b;">Connect wireless thermal printer for instant receipt printing</span>
                                </div>
                            </div>
                            <span id="btPrinterStatusBadge" class="status-badge offline">
                                <span class="dot"></span> Not Connected
                            </span>
                        </div>

                        <div style="font-size: 0.85rem; color: #475569; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                            <span>Active Printer:</span>
                            <strong id="btPrinterName" style="color: #2563eb;">None</strong>
                        </div>

                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                            <button type="button" class="btn btn-primary" id="btConnectBtn" onclick="handleBtConnect()" style="background: #2563eb;">
                                📶 Connect Bluetooth Printer
                            </button>
                            <button type="button" class="btn btn-secondary" id="btDisconnectBtn" onclick="handleBtDisconnect()" style="display: none; border-color: #fca5a5; color: #b91c1c;">
                                🔌 Disconnect
                            </button>
                            <button type="button" class="btn btn-secondary" id="btForgetBtn" onclick="handleBtForget()" style="display: none; border-color: #cbd5e1; color: #64748b;" title="Clear remembered printer so you can pair a different device">
                                ✕ Change Printer
                            </button>
                            <button type="button" class="btn btn-secondary" id="btTestPrintBtn" onclick="handleBtTestPrint()" style="border-color: #94a3b8;">
                                🖨️ Test Print (English)
                            </button>
                            <button type="button" class="btn btn-secondary" id="btTamilTestBtn" onclick="handleBtTamilTestPrint()" style="border-color: #3b82f6; color: #1d4ed8; font-weight: 600;" title="Test Tamil raster graphics printing on HOP-HL58">
                                🖨️ Tamil Test (HOP-HL58)
                            </button>
                        </div>
                        <div style="margin-top: 0.85rem; padding-top: 0.75rem; border-top: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                            <div style="font-size: 0.82rem; color: #334155;">
                                <strong>Bluetooth Print Mode:</strong>
                                <span style="font-size: 0.75rem; color: #64748b; display: block;">Auto detects Tamil & uses graphics mode for HOP-HL58</span>
                            </div>
                            <select id="settingBtPrintMode" class="form-control" style="width: auto; padding: 0.25rem 0.6rem; font-size: 0.8rem;" onchange="handleBtPrintModeChange(this.value)">
                                <option value="auto">Auto (Tamil: Graphics / English: Fast Text)</option>
                                <option value="image">Always Graphics / Image Mode (With Logo & Tamil)</option>
                                <option value="text">Text Only (Fastest, English only)</option>
                            </select>
                        </div>
                        <div style="margin-top: 0.65rem; font-size: 0.75rem; color: #64748b;">
                            ℹ Supports HOP-HL58 and any 58mm / 80mm ESC/POS Bluetooth thermal printer. Tamil text is automatically printed as high-contrast graphics.
                        </div>
                    </div>

                    <!-- Section 2: Shop Profile & Receipt Customization -->
                    <div>
                        <h4 style="font-size: 0.95rem; font-weight: 700; color: #1e293b; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">
                            🏪 Shop Information & Receipt Header/Footer
                        </h4>
                        
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label class="form-label">Shop / Store Name *</label>
                            <input type="text" id="settingStoreName" class="form-control" placeholder="e.g. BillKartX Smart Store" required>
                        </div>

                        <div class="form-row" style="margin-bottom: 1rem;">
                            <div class="form-group">
                                <label class="form-label">Shop Address</label>
                                <input type="text" id="settingStoreAddress" class="form-control" placeholder="e.g. 123 Main Bazaar Road, Anna Nagar, Chennai">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Contact Phone Number</label>
                                <input type="text" id="settingStorePhone" class="form-control" placeholder="e.g. +91 98765 43210">
                            </div>
                        </div>

                        <div class="form-row" style="margin-bottom: 1rem;">
                            <div class="form-group">
                                <label class="form-label">GST / Trade License Number</label>
                                <input type="text" id="settingStoreGst" class="form-control" placeholder="e.g. 33AAAAA0000A1Z5">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Receipt Header Tagline</label>
                                <input type="text" id="settingReceiptHeader" class="form-control" placeholder="e.g. Tax Invoice / Retail Bill">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label class="form-label">Receipt Footer Message (Appears at bottom of print)</label>
                            <input type="text" id="settingReceiptFooter" class="form-control" placeholder="e.g. *** Thank you for shopping with us! Visit again ***">
                        </div>

                        <div class="form-row" style="margin-bottom: 1rem;">
                            <div class="form-group">
                                <label class="form-label" style="display: flex; justify-content: space-between; align-items: center;">
                                    <span>GST Billing Option</span>
                                    <span style="font-size: 0.72rem; color: #64748b; font-weight: normal;">(ON / OFF)</span>
                                </label>
                                <select id="settingEnableGst" class="form-control" onchange="toggleSettingGstRate(this.value)">
                                    <option value="1">✅ Enabled (Calculate GST by default)</option>
                                    <option value="0">❌ Disabled (Turn OFF GST / 0% Tax)</option>
                                </select>
                                <small style="font-size: 0.72rem; color: #64748b; display: block; margin-top: 3px;">
                                    Tip: POS Cart-layum ovvoru bill-kum ON/OFF toggle pannikalam.
                                </small>
                            </div>
                            <div class="form-group" id="settingTaxRateGroup">
                                <label class="form-label">Default GST Tax Rate (%)</label>
                                <input type="number" id="settingTaxRate" class="form-control" step="any" min="0" max="100" placeholder="5">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Currency Symbol</label>
                                <div style="display: flex; gap: 0.5rem;">
                                    <input type="text" id="settingCurrency" class="form-control" placeholder="₹" value="₹" style="flex: 1;">
                                    <select class="form-control" style="width: 140px;" onchange="if(this.value){ document.getElementById('settingCurrency').value=this.value; }">
                                        <option value="">Quick Select</option>
                                        <option value="₹">₹ (INR)</option>
                                        <option value="Rs.">Rs. (Rupees)</option>
                                        <option value="$">$ (USD)</option>
                                        <option value="AED">AED</option>
                                        <option value="SAR">SAR</option>
                                        <option value="€">€ (EUR)</option>
                                        <option value="£">£ (GBP)</option>
                                    </select>
                                </div>
                                <small style="color: #64748b; font-size: 0.75rem; margin-top: 4px; display: block;">
                                    Tip: On 58mm Bluetooth thermal paper receipts, '₹' is automatically printed as 'Rs.' because thermal printers do not support Unicode glyphs.
                                </small>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeSettingsModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveSettingsBtn">💾 Save All Settings</button>
                </div>
            </form>
        </div>
    </div>

