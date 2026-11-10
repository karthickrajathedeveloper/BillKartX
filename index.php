<?php
require_once __DIR__ . '/includes/header.php';
?>

<main class="main-wrapper">
    <!-- Mobile POS View Switcher (Tabs) -->
    <div class="pos-mobile-tabs" id="posMobileTabs">
        <button type="button" class="pos-mobile-tab-btn active" id="tabBtnCatalog" onclick="switchMobilePosTab('catalog')">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                <line x1="8" y1="21" x2="16" y2="21"></line>
                <line x1="12" y1="17" x2="12" y2="21"></line>
            </svg>
            Products
        </button>
        <button type="button" class="pos-mobile-tab-btn" id="tabBtnCart" onclick="switchMobilePosTab('cart')">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
            Cart & Bill (<span class="mobile-cart-badge" id="mobileCartTabCount">0</span>)
        </button>
    </div>

    <div class="pos-container">
        
        <!-- Left Pane: Product Catalog & 3D Interactive Shelves -->
        <section class="pos-catalog-pane" id="posCatalogPane">
            <div class="catalog-toolbar">
                <div class="toolbar-top">
                    <div class="search-box">
                        <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text" id="searchProduct" class="search-input" placeholder="Search products by name or barcode... (Press Alt+S)">
                    </div>

                    <button type="button" class="btn btn-secondary" id="toggle3DViewBtn" onclick="toggle3DPeek()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                            <polyline points="2 17 12 22 22 17"></polyline>
                            <polyline points="2 12 12 17 22 12"></polyline>
                        </svg>
                        <span id="toggle3DText">3D Shelf View</span>
                    </button>
                </div>

                <!-- 3D Shelf Peek View (Collapsible) -->
                <div id="threePeekContainer" style="display: none; margin-top: 0.5rem; position: relative;">
                    <div class="three-canvas-wrap" style="height: 340px;">
                        <div id="threeWarehouseCanvas"></div>
                        <div class="three-overlay-controls">
                            <button class="three-overlay-btn" onclick="threePeekInstance && threePeekInstance.resetCamera('overview')">Reset View</button>
                            <button class="three-overlay-btn" onclick="threePeekInstance && threePeekInstance.resetCamera('front')">Front</button>
                        </div>
                        <div class="three-legend">
                            <div class="legend-item"><span class="legend-color" style="background:#10b981;"></span> In Stock</div>
                            <div class="legend-item"><span class="legend-color" style="background:#f59e0b;"></span> Low Stock</div>
                            <div class="legend-item"><span class="legend-color" style="background:#ef4444;"></span> Out of Stock</div>
                        </div>
                    </div>
                </div>

                <!-- Category Filter Pills -->
                <div class="category-pills" id="categoryPills">
                    <!-- Populated dynamically via App.js -->
                </div>
            </div>

            <!-- Products Grid Container -->
            <div class="products-scroll">
                <div class="product-grid" id="productGrid">
                    <!-- Populated dynamically via App.js -->
                </div>
            </div>
        </section>

        <!-- Right Pane: Active Cart & Fast Checkout -->
        <aside class="pos-cart-pane" id="posCartPane">
            <div class="cart-header">
                <div class="cart-title">
                    <button type="button" class="mobile-back-to-catalog-btn" onclick="switchMobilePosTab('catalog')" title="Back to Products">
                        ←
                    </button>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    Current Bill
                    <span class="cart-count" id="cartCount">0</span>
                </div>
                <div style="display: flex; gap: 0.35rem; align-items: center; flex-wrap: wrap;">
                    <button type="button" class="btn btn-secondary" id="salesReturnBtn" style="padding: 0.3rem 0.55rem; font-size: 0.78rem; background: #fff1f2; border-color: #fecdd3; color: #be123c; font-weight: 700;" onclick="openReturnModal()" title="Process Sales Return & Refund">
                        ↩️ Return
                    </button>
                    <button type="button" class="btn btn-secondary" id="reprintLastBillBtn" style="padding: 0.3rem 0.55rem; font-size: 0.78rem; display: none; background: #f8fafc; border-color: #cbd5e1;" onclick="App.reprintLastBill()" title="Reprint the most recent bill">
                        🔄 Reprint Last
                    </button>
                    <button type="button" class="btn btn-secondary" style="padding: 0.3rem 0.55rem; font-size: 0.78rem;" onclick="App.clearCart()">
                        Clear
                    </button>
                </div>
            </div>

            <!-- Cart Items Scroll List -->
            <div class="cart-items-scroll" id="cartItemsScroll">
                <!-- Populated dynamically via App.js -->
            </div>

            <!-- Cart Checkout & Calculations -->
            <div class="cart-checkout-footer">
                <!-- Customer Details -->
                <div class="form-row" style="gap: 0.5rem;">
                    <div class="form-group">
                        <input type="text" id="custName" class="form-control" placeholder="Customer Name" value="Walk-in Customer" style="font-size: 0.85rem; padding: 0.4rem 0.6rem;">
                    </div>
                    <div class="form-group">
                        <input type="tel" id="custPhone" class="form-control" placeholder="Phone Number" style="font-size: 0.85rem; padding: 0.4rem 0.6rem;">
                    </div>
                </div>

                <!-- Discount Mode Selector (Overall vs Per-Item) -->
                <div style="background: #f1f5f9; padding: 0.4rem 0.6rem; border-radius: 6px; display: flex; align-items: center; justify-content: space-between; font-size: 0.8rem;">
                    <span style="font-weight: 600; color: #475569;">Discount Option:</span>
                    <div style="display: flex; gap: 0.25rem;">
                        <button type="button" class="btn btn-secondary" id="discModeOverallBtn" onclick="App.setDiscountMode('overall')" style="padding: 0.2rem 0.5rem; font-size: 0.75rem; background: var(--primary); color: #fff; font-weight: 700;">Overall</button>
                        <button type="button" class="btn btn-secondary" id="discModeItemBtn" onclick="App.setDiscountMode('item')" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;">Per Item</button>
                    </div>
                </div>

                <!-- Discount, Tax & Payment Mode -->
                <div class="pos-pay-options-grid">
                    <div class="form-group" id="cartDiscountGroup">
                        <label class="form-label" style="font-size: 0.75rem;">Overall Disc (₹)</label>
                        <input type="number" id="cartDiscount" class="form-control" value="0" min="0" step="1" style="font-size: 0.85rem; padding: 0.4rem 0.6rem;">
                    </div>
                    <div class="form-group" id="cartItemDiscountSummaryGroup" style="display: none;">
                        <label class="form-label" style="font-size: 0.75rem;">Item Disc Total</label>
                        <div id="itemDiscountSumDisplay" style="font-size: 0.85rem; font-weight: 700; color: #16a34a; padding: 0.4rem 0.6rem; background: #f0fdf4; border-radius: 6px; border: 1px solid #bbf7d0;">₹0.00</div>
                    </div>
                    <div class="form-group" id="cartGstGroup">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2px;">
                            <label class="form-label" style="font-size: 0.75rem; margin: 0; font-weight: 600;">GST Tax</label>
                            <div style="display: inline-flex; border: 1px solid #cbd5e1; border-radius: 4px; overflow: hidden; font-size: 0.68rem; line-height: 1;">
                                <button type="button" id="gstBtnOn" onclick="App.setGstEnabled(true)" style="padding: 2px 6px; border: none; font-weight: 700; cursor: pointer; background: #2563eb; color: #fff;">ON</button>
                                <button type="button" id="gstBtnOff" onclick="App.setGstEnabled(false)" style="padding: 2px 6px; border: none; font-weight: 700; cursor: pointer; background: #f1f5f9; color: #64748b;">OFF</button>
                            </div>
                        </div>
                        <div id="gstRateWrapper" style="position: relative;">
                            <input type="number" id="cartTaxRate" class="form-control" value="5" min="0" step="any" style="font-size: 0.85rem; padding: 0.4rem 0.6rem; padding-right: 1.25rem;" oninput="App.onTaxRateInput()">
                            <span style="position: absolute; right: 6px; top: 50%; transform: translateY(-50%); font-size: 0.75rem; color: #64748b; font-weight: bold; pointer-events: none;">%</span>
                        </div>
                        <div id="gstOffBadge" style="display: none; padding: 0.35rem 0.4rem; background: #f1f5f9; border: 1px dashed #cbd5e1; border-radius: 6px; font-size: 0.78rem; color: #64748b; text-align: center; font-weight: 600; cursor: pointer;" onclick="App.setGstEnabled(true)" title="Click to turn GST ON">
                            OFF (0%)
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 0.75rem;">Pay Mode</label>
                        <select id="paymentMethod" class="form-control" style="font-size: 0.85rem; padding: 0.4rem 0.6rem;">
                            <option value="Cash">Cash</option>
                            <option value="UPI">UPI / GPay</option>
                            <option value="Card">Card</option>
                        </select>
                    </div>
                </div>

                <!-- Totals Breakdown -->
                <div class="cart-summary-line" style="margin-top: 0.25rem;">
                    <span>Subtotal</span>
                    <span id="cartSubtotalDisplay">₹0.00</span>
                </div>
                <div class="cart-summary-line">
                    <span id="cartTaxLabel">GST Tax</span>
                    <span id="cartTaxDisplay">₹0.00</span>
                </div>
                <div class="cart-grand-total">
                    <span class="grand-total-label">Grand Total</span>
                    <span class="grand-total-amount" id="cartGrandTotalDisplay">₹0.00</span>
                </div>

                <!-- Checkout Button -->
                <button type="button" class="btn btn-primary btn-lg" id="checkoutBtn" onclick="App.checkout()" style="width: 100%; margin-top: 0.25rem;">
                    ⚡ Complete & Print Bill
                </button>
            </div>
        </aside>

    </div>

    <!-- Floating Mobile Checkout Quick Bar -->
    <div class="pos-floating-bar" id="posFloatingBar" style="display: none;" onclick="switchMobilePosTab('cart')">
        <div class="pos-floating-info">
            <span class="pos-floating-count" id="floatingCartCount">0 Items</span>
            <span class="pos-floating-total" id="floatingCartTotal">₹0.00</span>
        </div>
        <button type="button" class="pos-floating-action">
            View Cart & Pay →
        </button>
    </div>
</main>

<!-- Modal: Add Product to Cart with Unit Conversion -->
<div id="addProductModal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <div>
                <h3 class="modal-title" id="modalProdName">Product Name</h3>
                <span id="modalProdCategory" style="font-size: 0.8rem; color: #64748b;">Category</span>
            </div>
            <button type="button" class="modal-close-btn" onclick="App.closeModal('addProductModal')">✕</button>
        </div>
        <div class="modal-body">
            <div style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div>
                    <span style="font-size: 0.8rem; color: #64748b;">Standard Price:</span>
                    <div id="modalProdPrice" style="font-size: 1.1rem; font-weight: bold; color: #2563eb;">₹0.00 / kg</div>
                </div>
                <div style="text-align: right;">
                    <span style="font-size: 0.8rem; color: #64748b;">Available Stock:</span>
                    <div id="modalProdStock" style="font-weight: 700; color: #10b981;">0 kg</div>
                </div>
            </div>

            <!-- Unit & Qty Input -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Quantity</label>
                    <input type="number" id="modalQtyInput" class="form-control" value="1" min="0.001" step="any">
                </div>
                <div class="form-group">
                    <label class="form-label">Unit</label>
                    <select id="modalUnitSelect" class="form-control">
                        <!-- Populated dynamically -->
                    </select>
                </div>
            </div>

            <!-- Quick Unit Presets (e.g., 100g, 250g, 500g, 1kg) -->
            <div class="form-group">
                <label class="form-label">Quick Select Unit & Qty:</label>
                <div class="quick-unit-pills" id="modalQuickPills">
                    <!-- Populated dynamically -->
                </div>
            </div>

            <!-- Live Calculated Price Preview -->
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 1rem; text-align: center;">
                <div style="font-size: 0.85rem; color: #1e40af; font-weight: 600;">Calculated Price for Selected Quantity:</div>
                <div id="modalCalculatedPrice" style="font-size: 2rem; font-weight: 900; color: #1d4ed8; margin: 0.25rem 0;">₹0.00</div>
                <div id="modalBaseDeduction" style="font-size: 0.8rem; color: #64748b;">Stock deduction: 1 kg</div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="App.closeModal('addProductModal')">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="App.confirmAddToCart()">+ Add to Bill</button>
        </div>
    </div>
</div>

<!-- Modal: Receipt Preview & Instant Print / PDF -->
<div id="receiptModal" class="modal-overlay">
    <div class="modal-card" style="max-width: 480px;">
        <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <h3 class="modal-title" style="margin: 0;">Receipt Generated</h3>
                <!-- Quick Font Size Adjuster for Thermal Receipts -->
                <div style="display: inline-flex; align-items: center; gap: 4px; background: #f1f5f9; padding: 2px 7px; border-radius: 6px; border: 1px solid #cbd5e1;" title="தமிழ் / பொருள் எழுத்து அளவை மாற்றவும் (Adjust Receipt Font Size)">
                    <span style="font-size: 0.74rem; font-weight: 600; color: #475569;">Font:</span>
                    <button type="button" onclick="App.adjustReceiptFontSize(-2)" title="Decrease font size" style="padding: 1px 7px; border: 1px solid #cbd5e1; background: #fff; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 0.78rem; line-height: 1.2;">A−</button>
                    <span id="receiptFontSizeBadge" style="font-size: 0.78rem; font-weight: 700; color: #2563eb; min-width: 34px; text-align: center;">18px</span>
                    <button type="button" onclick="App.adjustReceiptFontSize(2)" title="Increase font size" style="padding: 1px 7px; border: 1px solid #cbd5e1; background: #fff; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 0.78rem; line-height: 1.2;">A+</button>
                </div>
            </div>
            <button type="button" class="modal-close-btn" onclick="App.closeModal('receiptModal')">✕</button>
        </div>
        <div class="modal-body" style="padding: 1rem; max-height: 70vh; overflow-y: auto;">
            <div id="receiptContent">
                <!-- Receipt is injected here -->
            </div>
        </div>
        <div class="modal-footer" style="justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <button type="button" class="btn btn-primary" onclick="App.printThermalReceipt()" style="background: #2563eb; color: #fff;" title="Direct 58mm Thermal Print">
                    🖨️ Thermal Print (58mm)
                </button>
                <button type="button" class="btn btn-secondary" onclick="App.reprintReceipt(true)" title="Print second / duplicate copy">
                    🔄 Print Again (Duplicate)
                </button>
                <button type="button" class="btn btn-secondary" onclick="App.printReceiptBluetooth()" title="Direct Wireless Bluetooth Thermal Print">
                    📶 Bluetooth Print
                </button>
                <button type="button" class="btn btn-secondary" onclick="App.printViaThermalIframe('receiptPrintArea')" title="Open Browser Print Dialog">
                    🖨️ Browser Print
                </button>
                <button type="button" class="btn btn-secondary" onclick="App.downloadReceiptPDF()">
                    📄 PDF
                </button>
            </div>
            <button type="button" class="btn btn-success" onclick="App.closeModal('receiptModal')">
                ✓ Done
            </button>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="assets/js/three-inventory.js"></script>
<script src="assets/js/app.js"></script>

<script>
    let threePeekInstance = null;

    function toggle3DPeek() {
        const container = document.getElementById('threePeekContainer');
        const btnText = document.getElementById('toggle3DText');

        if (container.style.display === 'none') {
            container.style.display = 'block';
            btnText.innerText = 'Hide 3D View';

            if (!threePeekInstance) {
                threePeekInstance = new Warehouse3D('threeWarehouseCanvas', {
                    onProductClick: (prod) => {
                        App.openAddModal(prod.id);
                    }
                });
                loadThreeShelves();
            } else {
                setTimeout(() => threePeekInstance.onResize(), 100);
            }
        } else {
            container.style.display = 'none';
            btnText.innerText = '3D Shelf View';
        }
    }

    async function loadThreeShelves() {
        try {
            const res = await fetch('api.php?action=get_3d_shelf_data');
            const data = await res.json();
            if (data.success && threePeekInstance) {
                threePeekInstance.loadShelves(data.shelves);
            }
        } catch (e) {
            console.error('Failed to load 3D shelves:', e);
        }
    }

    // Mobile POS tab switcher
    function switchMobilePosTab(tab) {
        const catalogPane = document.getElementById('posCatalogPane');
        const cartPane = document.getElementById('posCartPane');
        const catalogBtn = document.getElementById('tabBtnCatalog');
        const cartBtn = document.getElementById('tabBtnCart');
        const floatingBar = document.getElementById('posFloatingBar');

        if (tab === 'cart') {
            if (catalogPane) catalogPane.classList.add('mobile-hidden');
            if (cartPane) cartPane.classList.remove('mobile-hidden');
            if (catalogBtn) catalogBtn.classList.remove('active');
            if (cartBtn) cartBtn.classList.add('active');
            if (floatingBar) floatingBar.style.display = 'none';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            if (catalogPane) catalogPane.classList.remove('mobile-hidden');
            if (cartPane) cartPane.classList.add('mobile-hidden');
            if (catalogBtn) catalogBtn.classList.add('active');
            if (cartBtn) cartBtn.classList.remove('active');
            if (floatingBar && window.App && App.cart && App.cart.length > 0 && window.innerWidth <= 992) {
                floatingBar.style.display = 'flex';
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    // Keyboard shortcuts: Alt+S to search
    window.addEventListener('keydown', (e) => {
        if (e.altKey && e.key.toLowerCase() === 's') {
            e.preventDefault();
            const s = document.getElementById('searchProduct');
            if (s) { s.focus(); s.select(); }
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

