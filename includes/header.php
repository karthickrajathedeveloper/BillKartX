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
        <div class="modal-card" style="max-width: 1100px; width: 95%;">
            <div class="modal-header">
                <div style="display: flex; align-items: center; gap: 0.6rem;">
                    <img src="assets/RuPay-Inspired K Finance Logo-Favicon.png" alt="BillKartX" style="width: 26px; height: 26px; border-radius: 6px; object-fit: cover;">
                    <h3 class="modal-title">BillKartX Settings & Receipt Customization</h3>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeSettingsModal()">✕</button>
            </div>
            <form id="settingsForm" onsubmit="saveGlobalSettings(event)">
                <div class="modal-body" style="gap: 1.5rem; max-height: 78vh; overflow-y: auto;">
                    
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
                    </div>

                    <!-- Section 2: Shop Profile & Info -->
                    <div>
                        <h4 style="font-size: 0.95rem; font-weight: 700; color: #1e293b; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">
                            🏪 Shop Information & GST Configuration
                        </h4>
                        
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label class="form-label">Shop / Store Name *</label>
                            <input type="text" id="settingStoreName" class="form-control" placeholder="e.g. BillKartX Smart Store" oninput="updateReceiptSamplePreview()" required>
                        </div>

                        <div class="form-row" style="margin-bottom: 1rem;">
                            <div class="form-group">
                                <label class="form-label">Shop Address</label>
                                <input type="text" id="settingStoreAddress" class="form-control" placeholder="e.g. 123 Main Bazaar Road, Anna Nagar, Chennai" oninput="updateReceiptSamplePreview()">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Contact Phone Number</label>
                                <input type="text" id="settingStorePhone" class="form-control" placeholder="e.g. +91 98765 43210" oninput="updateReceiptSamplePreview()">
                            </div>
                        </div>

                        <div class="form-row" style="margin-bottom: 1rem;">
                            <div class="form-group">
                                <label class="form-label">GST / Trade License Number</label>
                                <input type="text" id="settingStoreGst" class="form-control" placeholder="e.g. 33AAAAA0000A1Z5" oninput="updateReceiptSamplePreview()">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Receipt Header Tagline</label>
                                <input type="text" id="settingReceiptHeader" class="form-control" placeholder="e.g. Tax Invoice / Retail Bill" oninput="updateReceiptSamplePreview()">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label class="form-label">Receipt Footer Message (Appears at bottom of print)</label>
                            <input type="text" id="settingReceiptFooter" class="form-control" placeholder="e.g. *** Thank you for shopping with us! Visit again ***" oninput="updateReceiptSamplePreview()">
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
                            </div>
                            <div class="form-group" id="settingTaxRateGroup">
                                <label class="form-label">Default GST Tax Rate (%)</label>
                                <input type="number" id="settingTaxRate" class="form-control" step="any" min="0" max="100" placeholder="5">
                            </div>
                        </div>

                        <div class="form-row" style="margin-bottom: 1rem;">
                            <div class="form-group">
                                <label class="form-label">Currency Symbol</label>
                                <div style="display: flex; gap: 0.5rem;">
                                    <input type="text" id="settingCurrency" class="form-control" placeholder="₹" value="₹" style="flex: 1;" oninput="updateReceiptSamplePreview()">
                                    <select class="form-control" style="width: 140px;" onchange="if(this.value){ document.getElementById('settingCurrency').value=this.value; updateReceiptSamplePreview(); }">
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
                            </div>
                        </div>
                    </div>

                    <!-- Hidden sync input for backward compatibility -->
                    <input type="hidden" id="settingReceiptFontSize" value="18">

                    <!-- Section 3: 🖨️ Thermal Receipt Typography & Live 58mm Sample Preview -->
                    <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; padding: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.75rem;">
                            <div>
                                <h4 style="font-size: 1rem; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 0.4rem;">
                                    <span>🔤</span> Thermal Receipt Typography & Live Sample Preview
                                </h4>
                                <span style="font-size: 0.8rem; color: #64748b;">
                                    ஒவ்வொரு பகுதியின் எழுத்து அளவையும் (Font Size) நீங்கள் மாற்றலாம். வலதுபுறத்தில் மாதிரி பில் உடனே மாறும்!
                                </span>
                            </div>
                            <button type="button" class="btn btn-secondary" onclick="resetAllReceiptFontsToDefault()" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; border-color: #cbd5e1; font-weight: 600;" title="அனைத்து அளவுகளையும் பரிந்துரைக்கப்பட்ட இயல்பு நிலைக்கு மாற்ற">
                                ↺ Reset All Fonts to Defaults
                            </button>
                        </div>

                        <!-- 2-Column Responsive Layout: Controls on Left, Sticky Sample Bill on Right -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; align-items: start;">
                            
                            <!-- Left: 8 Individual Font Steppers -->
                            <div style="display: flex; flex-direction: column; gap: 0.6rem;">
                                
                                <!-- 1. Shop Name -->
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0.75rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                                    <div>
                                        <div style="font-weight: 700; font-size: 0.84rem; color: #1e293b;">🏪 Shop Name (கடையின் பெயர்)</div>
                                        <div style="font-size: 0.7rem; color: #64748b;">Recommended: 16px - 22px</div>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <button type="button" onclick="stepFontSize('fontShopName', -1)" style="width: 28px; height: 28px; border: 1px solid #cbd5e1; background: #fff; border-radius: 4px; font-weight: bold; cursor: pointer;">−</button>
                                        <input type="number" id="fontShopName" min="12" max="28" value="18" oninput="updateReceiptSamplePreview()" style="width: 46px; text-align: center; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 4px; padding: 3px 0; font-size: 0.85rem;">
                                        <span style="font-size: 0.72rem; color: #64748b;">px</span>
                                        <button type="button" onclick="stepFontSize('fontShopName', 1)" style="width: 28px; height: 28px; border: 1px solid #cbd5e1; background: #fff; border-radius: 4px; font-weight: bold; cursor: pointer;">+</button>
                                    </div>
                                </div>

                                <!-- 2. Shop Address -->
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0.75rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                                    <div>
                                        <div style="font-weight: 700; font-size: 0.84rem; color: #1e293b;">📍 Shop Address (முகவரி)</div>
                                        <div style="font-size: 0.7rem; color: #64748b;">Recommended: 11px - 14px</div>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <button type="button" onclick="stepFontSize('fontShopAddress', -1)" style="width: 28px; height: 28px; border: 1px solid #cbd5e1; background: #fff; border-radius: 4px; font-weight: bold; cursor: pointer;">−</button>
                                        <input type="number" id="fontShopAddress" min="9" max="18" value="12" oninput="updateReceiptSamplePreview()" style="width: 46px; text-align: center; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 4px; padding: 3px 0; font-size: 0.85rem;">
                                        <span style="font-size: 0.72rem; color: #64748b;">px</span>
                                        <button type="button" onclick="stepFontSize('fontShopAddress', 1)" style="width: 28px; height: 28px; border: 1px solid #cbd5e1; background: #fff; border-radius: 4px; font-weight: bold; cursor: pointer;">+</button>
                                    </div>
                                </div>

                                <!-- 3. Contact & GST & Bill Info -->
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0.75rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                                    <div>
                                        <div style="font-weight: 700; font-size: 0.84rem; color: #1e293b;">📞 Phone, GST & Bill Info (தொடர்பு & விவரங்கள்)</div>
                                        <div style="font-size: 0.7rem; color: #64748b;">Recommended: 10px - 13px</div>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <button type="button" onclick="stepFontSize('fontShopContact', -1)" style="width: 28px; height: 28px; border: 1px solid #cbd5e1; background: #fff; border-radius: 4px; font-weight: bold; cursor: pointer;">−</button>
                                        <input type="number" id="fontShopContact" min="9" max="16" value="11" oninput="updateReceiptSamplePreview()" style="width: 46px; text-align: center; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 4px; padding: 3px 0; font-size: 0.85rem;">
                                        <span style="font-size: 0.72rem; color: #64748b;">px</span>
                                        <button type="button" onclick="stepFontSize('fontShopContact', 1)" style="width: 28px; height: 28px; border: 1px solid #cbd5e1; background: #fff; border-radius: 4px; font-weight: bold; cursor: pointer;">+</button>
                                    </div>
                                </div>

                                <!-- 4. Receipt Header Tagline -->
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0.75rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                                    <div>
                                        <div style="font-weight: 700; font-size: 0.84rem; color: #1e293b;">🏷️ Header Tagline ("Retail Bill" தலைப்பு)</div>
                                        <div style="font-size: 0.7rem; color: #64748b;">Recommended: 12px - 15px</div>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <button type="button" onclick="stepFontSize('fontReceiptHeader', -1)" style="width: 28px; height: 28px; border: 1px solid #cbd5e1; background: #fff; border-radius: 4px; font-weight: bold; cursor: pointer;">−</button>
                                        <input type="number" id="fontReceiptHeader" min="10" max="18" value="13" oninput="updateReceiptSamplePreview()" style="width: 46px; text-align: center; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 4px; padding: 3px 0; font-size: 0.85rem;">
                                        <span style="font-size: 0.72rem; color: #64748b;">px</span>
                                        <button type="button" onclick="stepFontSize('fontReceiptHeader', 1)" style="width: 28px; height: 28px; border: 1px solid #cbd5e1; background: #fff; border-radius: 4px; font-weight: bold; cursor: pointer;">+</button>
                                    </div>
                                </div>

                                <!-- 5. Product Name (Tamil / English) -->
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0.75rem; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px;">
                                    <div>
                                        <div style="font-weight: 800; font-size: 0.86rem; color: #1d4ed8;">🌾 Product Name (பொருட்களின் பெயர் / தமிழ்)</div>
                                        <div style="font-size: 0.7rem; color: #2563eb;">Main item font (Recommended: 16px - 22px)</div>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <button type="button" onclick="stepFontSize('fontProductName', -1)" style="width: 28px; height: 28px; border: 1px solid #93c5fd; background: #fff; border-radius: 4px; font-weight: bold; cursor: pointer; color: #1d4ed8;">−</button>
                                        <input type="number" id="fontProductName" min="12" max="26" value="18" oninput="updateReceiptSamplePreview()" style="width: 46px; text-align: center; font-weight: 800; border: 1px solid #93c5fd; border-radius: 4px; padding: 3px 0; font-size: 0.85rem; color: #1d4ed8;">
                                        <span style="font-size: 0.72rem; color: #2563eb;">px</span>
                                        <button type="button" onclick="stepFontSize('fontProductName', 1)" style="width: 28px; height: 28px; border: 1px solid #93c5fd; background: #fff; border-radius: 4px; font-weight: bold; cursor: pointer; color: #1d4ed8;">+</button>
                                    </div>
                                </div>

                                <!-- 6. Qty, Price & Table Column Headers -->
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0.75rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                                    <div>
                                        <div style="font-weight: 700; font-size: 0.84rem; color: #1e293b;">🔢 Qty & Price (அளவு, விலை & Subtotal வரிகள்)</div>
                                        <div style="font-size: 0.7rem; color: #64748b;">Recommended: 12px - 14px</div>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <button type="button" onclick="stepFontSize('fontQtyPrice', -1)" style="width: 28px; height: 28px; border: 1px solid #cbd5e1; background: #fff; border-radius: 4px; font-weight: bold; cursor: pointer;">−</button>
                                        <input type="number" id="fontQtyPrice" min="10" max="18" value="13" oninput="updateReceiptSamplePreview()" style="width: 46px; text-align: center; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 4px; padding: 3px 0; font-size: 0.85rem;">
                                        <span style="font-size: 0.72rem; color: #64748b;">px</span>
                                        <button type="button" onclick="stepFontSize('fontQtyPrice', 1)" style="width: 28px; height: 28px; border: 1px solid #cbd5e1; background: #fff; border-radius: 4px; font-weight: bold; cursor: pointer;">+</button>
                                    </div>
                                </div>

                                <!-- 7. Grand Total -->
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0.75rem; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px;">
                                    <div>
                                        <div style="font-weight: 800; font-size: 0.85rem; color: #15803d;">💰 Grand Total (மொத்தத் தொகை)</div>
                                        <div style="font-size: 0.7rem; color: #16a34a;">Large & bold highlight (Recommended: 16px - 22px)</div>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <button type="button" onclick="stepFontSize('fontGrandTotal', -1)" style="width: 28px; height: 28px; border: 1px solid #86efac; background: #fff; border-radius: 4px; font-weight: bold; cursor: pointer; color: #15803d;">−</button>
                                        <input type="number" id="fontGrandTotal" min="14" max="26" value="18" oninput="updateReceiptSamplePreview()" style="width: 46px; text-align: center; font-weight: 800; border: 1px solid #86efac; border-radius: 4px; padding: 3px 0; font-size: 0.85rem; color: #15803d;">
                                        <span style="font-size: 0.72rem; color: #15803d;">px</span>
                                        <button type="button" onclick="stepFontSize('fontGrandTotal', 1)" style="width: 28px; height: 28px; border: 1px solid #86efac; background: #fff; border-radius: 4px; font-weight: bold; cursor: pointer; color: #15803d;">+</button>
                                    </div>
                                </div>

                                <!-- 8. Footer Message -->
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0.75rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                                    <div>
                                        <div style="font-weight: 700; font-size: 0.84rem; color: #1e293b;">🙏 Footer Message (நன்றி வாக்கியம்)</div>
                                        <div style="font-size: 0.7rem; color: #64748b;">Recommended: 10px - 13px</div>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 4px;">
                                        <button type="button" onclick="stepFontSize('fontFooter', -1)" style="width: 28px; height: 28px; border: 1px solid #cbd5e1; background: #fff; border-radius: 4px; font-weight: bold; cursor: pointer;">−</button>
                                        <input type="number" id="fontFooter" min="9" max="16" value="11" oninput="updateReceiptSamplePreview()" style="width: 46px; text-align: center; font-weight: 700; border: 1px solid #cbd5e1; border-radius: 4px; padding: 3px 0; font-size: 0.85rem;">
                                        <span style="font-size: 0.72rem; color: #64748b;">px</span>
                                        <button type="button" onclick="stepFontSize('fontFooter', 1)" style="width: 28px; height: 28px; border: 1px solid #cbd5e1; background: #fff; border-radius: 4px; font-weight: bold; cursor: pointer;">+</button>
                                    </div>
                                </div>

                            </div>

                            <!-- Right: Sticky Live 58mm Thermal Bill Simulator -->
                            <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 1rem; position: sticky; top: 10px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.65rem;">
                                    <div style="font-weight: 700; font-size: 0.85rem; color: #0f172a; display: flex; align-items: center; gap: 0.35rem;">
                                        <span>📄</span> Live 58mm Thermal Bill Preview
                                    </div>
                                    <button type="button" class="btn btn-primary" onclick="printSampleThermalReceipt()" style="padding: 0.25rem 0.65rem; font-size: 0.78rem; background: #2563eb; color: #fff;" title="Test print this sample bill on your 58mm printer">
                                        🖨️ Test Print Sample
                                    </button>
                                </div>

                                <!-- The 58mm Paper Simulator -->
                                <div id="sampleReceiptContainer" style="background: #ffffff; color: #000000; border: 1px solid #cbd5e1; border-radius: 6px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); padding: 12px 10px; width: 280px; max-width: 100%; margin: 0 auto; font-family: 'Nirmala UI', 'Noto Sans Tamil', 'Latha', Arial, sans-serif; line-height: 1.35; user-select: none;">
                                    <!-- Header -->
                                    <div style="text-align: center; margin-bottom: 4px;">
                                        <div style="text-align: center; margin-bottom: 4px;">
                                            <img src="assets/BillKartX Smart Billing Logo.png" alt="Logo" style="max-height: 32px; max-width: 140px; object-fit: contain; display: inline-block;">
                                        </div>
                                        <div id="sampleStoreName" class="receipt-title" style="font-weight: 900; text-transform: uppercase;">Karthi Billing & Inventory</div>
                                        <div id="sampleStoreAddress" class="receipt-address" style="margin-top: 2px;">123 Main Bazaar Road, Anna Nagar, Nilakottai</div>
                                        <div id="sampleStorePhone" class="receipt-contact">Phone: +91 98765 43210</div>
                                        <div id="sampleStoreGst" class="receipt-contact">GST: 33AAAAA0000A1Z5</div>
                                        <div style="border-bottom: 1px dashed #000; margin: 5px 0;"></div>
                                        <div id="sampleReceiptTag" class="receipt-header-tag" style="margin: 3px 0;">Tax Invoice / Retail Bill</div>
                                        <div class="receipt-contact" style="display: flex; justify-content: space-between;">
                                            <span><strong>Bill:</strong> BKX-SAMPLE</span>
                                            <span>10/11/2026</span>
                                        </div>
                                        <div class="receipt-contact" style="display: flex; justify-content: space-between;">
                                            <span>Customer: Walk-in</span>
                                            <span>+91 90000 00000</span>
                                        </div>
                                    </div>

                                    <div style="border-bottom: 1px dashed #000; margin: 5px 0;"></div>

                                    <!-- Items Table -->
                                    <table id="sampleReceiptTable" style="width: 100%; table-layout: fixed; border-collapse: collapse;">
                                        <thead>
                                            <tr style="border-bottom: 1px solid #000;">
                                                <th style="text-align: left; width: 54%; padding: 3px 0;">Item Name</th>
                                                <th style="text-align: center; width: 21%; padding: 3px 0;">Qty</th>
                                                <th style="text-align: right; width: 25%; padding: 3px 0;">Price</th>
                                            </tr>
                                        </thead>
                                        <tbody id="sampleReceiptItems">
                                            <tr>
                                                <td class="receipt-item-name" style="text-align: left; vertical-align: top; width: 54%; word-break: break-word; overflow-wrap: break-word; white-space: normal; padding: 4px 0;">
                                                    1. ஆரோக்கியா பசும்பால் 500 மி.லி
                                                    <br><small style="color: #16a34a; font-size: 0.72rem;">(Disc: -<span class="sample-curr">₹</span>2.00)</small>
                                                </td>
                                                <td class="text-center sample-qty" style="text-align: center; vertical-align: top; padding: 4px 0;">1 pkt</td>
                                                <td class="text-right sample-price" style="text-align: right; vertical-align: top; font-weight: 600; padding: 4px 0;"><span class="sample-curr">₹</span>34.00</td>
                                            </tr>
                                            <tr>
                                                <td class="receipt-item-name" style="text-align: left; vertical-align: top; width: 54%; word-break: break-word; overflow-wrap: break-word; white-space: normal; padding: 4px 0;">
                                                    2. ஆசீர்வாத் கோதுமை மாவு
                                                </td>
                                                <td class="text-center sample-qty" style="text-align: center; vertical-align: top; padding: 4px 0;">2 kg</td>
                                                <td class="text-right sample-price" style="text-align: right; vertical-align: top; font-weight: 600; padding: 4px 0;"><span class="sample-curr">₹</span>110.00</td>
                                            </tr>
                                            <tr>
                                                <td class="receipt-item-name" style="text-align: left; vertical-align: top; width: 54%; word-break: break-word; overflow-wrap: break-word; white-space: normal; padding: 4px 0;">
                                                    3. வெள்ளை சர்க்கரை
                                                </td>
                                                <td class="text-center sample-qty" style="text-align: center; vertical-align: top; padding: 4px 0;">1 kg</td>
                                                <td class="text-right sample-price" style="text-align: right; vertical-align: top; font-weight: 600; padding: 4px 0;"><span class="sample-curr">₹</span>44.00</td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <div style="border-bottom: 1px dashed #000; margin: 5px 0;"></div>

                                    <!-- Totals -->
                                    <div id="sampleTotals" style="display: flex; flex-direction: column; gap: 2px;">
                                        <div class="sample-total-line" style="display: flex; justify-content: space-between;">
                                            <span>Subtotal:</span>
                                            <span><span class="sample-curr">₹</span>188.00</span>
                                        </div>
                                        <div class="sample-total-line" style="display: flex; justify-content: space-between;">
                                            <span>Tax (GST 5%):</span>
                                            <span><span class="sample-curr">₹</span>0.00</span>
                                        </div>
                                        <div style="border-bottom: 1px dashed #000; margin: 4px 0;"></div>
                                        <div id="sampleGrandTotal" class="receipt-grand-total" style="display: flex; justify-content: space-between; font-weight: 900;">
                                            <span>GRAND TOTAL:</span>
                                            <span><span class="sample-curr">₹</span>188.00</span>
                                        </div>
                                        <div class="sample-total-line" style="display: flex; justify-content: space-between; margin-top: 2px;">
                                            <span>Payment Mode:</span>
                                            <span><strong>Cash</strong></span>
                                        </div>
                                    </div>

                                    <div style="border-bottom: 1px dashed #000; margin: 5px 0;"></div>
                                    <div id="sampleFooter" class="receipt-footer" style="text-align: center; margin-top: 8px;">
                                        *** Thank you for shopping with us! Visit again ***
                                    </div>
                                </div>

                                <div style="text-align: center; margin-top: 0.6rem; font-size: 0.75rem; color: #64748b;">
                                    💡 <em>நீங்கள் அளவுகளை மாற்றும்போது இந்த மாதிரி பில் உடனே மாறும்!</em>
                                </div>
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

