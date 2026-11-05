<?php
/**
 * Global Product Return & Refund Modal Component
 * Supports both:
 *  1. Return specific product(s) from an existing Invoice / Bill
 *  2. Direct Product Return (without bill / lost receipt)
 * Restocks inventory, records audit logs, and prints 58mm thermal return vouchers.
 */
?>
<!-- Modal 1: Process Product Return -->
<div id="productReturnModal" class="modal-overlay" style="z-index: 120;">
    <div class="modal-card modal-lg" style="max-width: 920px; max-height: 92vh; display: flex; flex-direction: column;">
        <div class="modal-header" style="background: linear-gradient(135deg, #1e293b, #0f172a); color: #fff; border-bottom: 2px solid #ef4444;">
            <div style="display: flex; align-items: center; gap: 0.6rem;">
                <span style="font-size: 1.3rem; background: #fee2e2; color: #b91c1c; padding: 0.2rem 0.5rem; border-radius: 8px;">↩️</span>
                <div>
                    <h3 class="modal-title" style="color: #fff; margin: 0; font-size: 1.15rem;">Sales Return & Customer Refund</h3>
                    <div style="font-size: 0.78rem; color: #94a3b8;">Return particular products, restock inventory, and generate refund slip</div>
                </div>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeReturnModal()" style="color: #fff;">✕</button>
        </div>

        <div class="modal-body" style="padding: 1.25rem; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 1rem;">
            
            <!-- Mode Switcher Tabs -->
            <div style="display: flex; gap: 0.5rem; background: #f1f5f9; padding: 4px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <button type="button" id="tabReturnByBillBtn" onclick="switchReturnMode('bill')" style="flex: 1; padding: 0.55rem 0.75rem; font-weight: 700; font-size: 0.88rem; border-radius: 6px; background: #2563eb; color: #fff; border: none; cursor: pointer; transition: all 0.2s;">
                    🧾 1. Return from Bill (Invoice)
                </button>
                <button type="button" id="tabReturnDirectBtn" onclick="switchReturnMode('direct')" style="flex: 1; padding: 0.55rem 0.75rem; font-weight: 700; font-size: 0.88rem; border-radius: 6px; background: transparent; color: #475569; border: none; cursor: pointer; transition: all 0.2s;">
                    📦 2. Direct Product Return (No Bill Needed)
                </button>
            </div>

            <!-- ================= TAB 1: RETURN FROM BILL ================= -->
            <div id="sectionReturnByBill" style="display: flex; flex-direction: column; gap: 1rem;">
                
                <!-- Step 1: Bill Lookup Bar -->
                <div id="retBillSearchSection" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1rem;">
                    <label style="font-weight: 700; font-size: 0.9rem; color: #1e293b; display: block; margin-bottom: 0.4rem;">
                        Step 1: Enter Bill Number or Customer Phone
                    </label>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 250px; position: relative;">
                            <input type="text" id="retBillQueryInput" class="form-control" placeholder="Enter Bill # (e.g. BILL-20261104...) or Phone" onkeydown="if(event.key === 'Enter'){ searchBillForReturn(); event.preventDefault(); }">
                        </div>
                        <button type="button" class="btn btn-primary" onclick="searchBillForReturn()" style="background: #2563eb; display: inline-flex; align-items: center; gap: 0.4rem;">
                            🔍 Find Bill
                        </button>
                    </div>
                    <div id="retRecentBillsList" style="margin-top: 0.6rem; display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; font-size: 0.8rem;">
                        <span style="color: #64748b; font-weight: 600;">Recent Invoices:</span>
                        <span id="retRecentBadges" style="color: #94a3b8;">Loading recent invoices...</span>
                    </div>
                </div>

                <!-- Step 2: Loaded Bill Info & Reset -->
                <div id="retLoadedBillInfo" style="display: none; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 0.85rem 1.15rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <span style="font-size: 0.85rem; font-weight: 700; color: #1e40af;">Original Bill:</span>
                                <code id="retInfoBillNumber" style="background: #dbeafe; color: #1e3a8a; padding: 2px 6px; border-radius: 4px; font-weight: 700; font-size: 0.95rem;">BILL-...</code>
                                <span id="retInfoBillDate" style="font-size: 0.8rem; color: #64748b;">-</span>
                            </div>
                            <div style="font-size: 0.82rem; color: #334155; margin-top: 0.2rem;">
                                <span>Customer: <strong id="retInfoCustomer">-</strong></span> | 
                                <span>Original Total: <strong id="retInfoTotal" style="color: #059669;">₹0.00</strong></span> |
                                <span>Refunded So Far: <strong id="retInfoAlreadyRefunded" style="color: #dc2626;">₹0.00</strong></span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary" onclick="resetReturnBillSearch()" style="padding: 0.3rem 0.65rem; font-size: 0.8rem; background: #fff; border-color: #93c5fd;">
                            🔄 Choose Another Bill
                        </button>
                    </div>
                </div>

                <!-- Step 2.5: Discount & Pricing Policy Alert (shown if bill has discount) -->
                <div id="retDiscountBanner" style="display: none; background: #fffbeb; border: 2px solid #f59e0b; border-radius: 10px; padding: 0.85rem 1.1rem; margin-bottom: 0.85rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.4rem;">
                                <span style="font-size: 1.25rem;">🏷️</span>
                                <strong style="color: #92400e; font-size: 0.95rem;">
                                    இந்த பில்லில் ₹<span id="retDiscAmtDisplay">0.00</span> தள்ளுபடி (Discount) கொடுக்கப்பட்டுள்ளது!
                                </strong>
                            </div>
                            <div style="font-size: 0.82rem; color: #b45309; margin-top: 3px;">
                                வாடிக்கையாளருக்கு எந்த விலையில் ரீபண்ட் செய்ய வேண்டும்? (Select Refund Price Policy):
                            </div>
                        </div>
                        <div style="display: inline-flex; background: #fff; padding: 3px; border-radius: 8px; border: 1px solid #fcd34d; gap: 4px;">
                            <label id="lblPolicyDiscount" style="display: flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 700; background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; transition: all 0.2s;">
                                <input type="radio" name="retPricePolicy" id="radioPolicyDiscount" value="discount" checked onchange="onGlobalPricePolicyChange('discount')" style="cursor: pointer;">
                                <span>தள்ளுபடி விலை (Discount / Paid Price) ⭐</span>
                            </label>
                            <label id="lblPolicyActual" style="display: flex; align-items: center; gap: 5px; padding: 5px 12px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600; color: #475569; border: 1px solid transparent; transition: all 0.2s;">
                                <input type="radio" name="retPricePolicy" id="radioPolicyActual" value="actual" onchange="onGlobalPricePolicyChange('actual')" style="cursor: pointer;">
                                <span>அசல் விலை (Actual MRP Price)</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Item Selection & Return Quantities Table -->
                <div id="retItemsContainer" style="display: none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 0.4rem;">
                        <div>
                            <label style="font-weight: 700; font-size: 0.92rem; color: #0f172a; margin: 0;">
                                Step 2: Select the Particular Product to Return
                            </label>
                            <div style="font-size: 0.78rem; color: #64748b;">
                                Click <strong>"↩️ Return This"</strong> on the item or check its box. Unchecked items will stay in the bill untouched.
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.2rem 0.55rem;" onclick="toggleSelectAllBillItems()">
                            Toggle All Items
                        </button>
                    </div>

                    <div style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 8px;">
                        <table class="data-table" style="margin: 0; font-size: 0.88rem;">
                            <thead style="background: #f1f5f9;">
                                <tr>
                                    <th style="width: 35px; text-align: center;">Pick</th>
                                    <th>Product Name</th>
                                    <th style="text-align: center; width: 80px;">Sold Qty</th>
                                    <th style="text-align: center; width: 75px;">Returned</th>
                                    <th style="text-align: center; width: 140px;">Return Qty</th>
                                    <th style="text-align: center; width: 165px;">Price Option</th>
                                    <th style="text-align: right; width: 105px;">Refund Rate</th>
                                    <th style="text-align: right; width: 95px;">Refund</th>
                                    <th style="text-align: center; width: 75px;" title="Restock product back to store inventory">Restock?</th>
                                    <th style="text-align: center; width: 100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="retItemsTableBody">
                                <!-- Populated dynamically via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Step 4: Return Reason & Refund Mode -->
                <div id="retDetailsRow" style="display: none; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;" class="return-options-grid">
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700; font-size: 0.85rem;">Return Reason</label>
                        <select id="retReasonSelect" class="form-control" style="font-size: 0.85rem;">
                            <option value="Customer Changed Mind">Customer Changed Mind</option>
                            <option value="Defective / Damaged Item">Defective / Damaged Item</option>
                            <option value="Wrong Item / Size Purchased">Wrong Item / Size Purchased</option>
                            <option value="Expired / Quality Concern">Expired / Quality Concern</option>
                            <option value="Other">Other / Miscellaneous</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 700; font-size: 0.85rem;">Refund Payment Mode</label>
                        <select id="retRefundMethod" class="form-control" style="font-size: 0.85rem;">
                            <option value="Cash">Cash (Cash Drawer)</option>
                            <option value="UPI / Online">UPI / Online Transfer</option>
                            <option value="Store Credit">Store Credit / Exchange</option>
                        </select>
                    </div>
                </div>

                <!-- Step 5: Highlighted Refund Banner -->
                <div id="retSummaryBanner" style="display: none; background: linear-gradient(135deg, #fef2f2, #ffe4e6); border: 2px dashed #f43f5e; border-radius: 12px; padding: 1rem 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                        <div>
                            <div style="font-size: 0.85rem; font-weight: 700; color: #9f1239; text-transform: uppercase; letter-spacing: 0.05em;">
                                Total Refund to Customer
                            </div>
                            <div style="font-size: 0.78rem; color: #e11d48;" id="retItemsSummaryCount">
                                0 item(s) selected for return
                            </div>
                        </div>
                        <div style="font-size: 1.8rem; font-weight: 900; color: #be123c;" id="retTotalRefundDisplay">
                            ₹0.00
                        </div>
                    </div>
                </div>

            </div>

            <!-- ================= TAB 2: DIRECT PRODUCT RETURN (NO BILL) ================= -->
            <div id="sectionReturnDirect" style="display: none; flex-direction: column; gap: 1rem;">
                <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 0.75rem 1rem; font-size: 0.84rem; color: #92400e; display: flex; align-items: center; gap: 0.5rem;">
                    <span>💡</span>
                    <span><strong>Direct Product Return:</strong> Return any individual product directly without requiring the customer's original bill. Stock will be restocked automatically.</span>
                </div>

                <!-- Product Selection -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1rem;">
                    <label style="font-weight: 700; font-size: 0.9rem; color: #1e293b; display: block; margin-bottom: 0.4rem;">
                        Select Product to Return
                    </label>
                    <div style="position: relative;">
                        <input type="text" id="dirProdSearchInput" class="form-control" placeholder="Search product by name or barcode..." oninput="filterDirectProducts(this.value)" autocomplete="off">
                        <div id="dirProdDropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; max-height: 220px; overflow-y: auto; background: #fff; border: 1px solid #cbd5e1; border-radius: 0 0 8px 8px; z-index: 50; box-shadow: 0 8px 20px rgba(0,0,0,0.12);">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>
                </div>

                <!-- Selected Product Details Card -->
                <div id="dirSelectedProductCard" style="display: none; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                        <div>
                            <span style="font-size: 0.78rem; text-transform: uppercase; font-weight: 800; color: #166534; background: #dcfce7; padding: 2px 8px; border-radius: 4px;">Selected Item</span>
                            <h4 id="dirProdName" style="margin: 0.3rem 0 0 0; color: #0f172a; font-size: 1.1rem;">-</h4>
                            <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">
                                Current Stock: <strong id="dirProdStock" style="color: #059669;">-</strong> | Standard Price: <strong id="dirProdStdPrice">₹0.00</strong>
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.25rem 0.5rem;" onclick="clearDirectProductSelection()">
                            ✕ Change Product
                        </button>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.75rem; background: #fff; border: 1px solid #dcfce7; border-radius: 8px; padding: 0.85rem;">
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Return Quantity</label>
                            <div style="display: flex; align-items: center; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden;">
                                <button type="button" style="border: none; background: #f1f5f9; padding: 6px 12px; font-weight: bold; cursor: pointer;" onclick="stepDirectQty(-1)">−</button>
                                <input type="number" id="dirReturnQty" class="form-control" value="1" min="0.001" step="any" style="border: none; text-align: center; font-weight: 700; padding: 6px 4px;" oninput="recalcDirectReturnTotal()">
                                <button type="button" style="border: none; background: #f1f5f9; padding: 6px 12px; font-weight: bold; cursor: pointer;" onclick="stepDirectQty(1)">+</button>
                            </div>
                        </div>

                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Unit</label>
                            <select id="dirReturnUnit" class="form-control" style="font-weight: 700; background: #fff; cursor: pointer;" onchange="onDirectUnitChange()">
                                <option value="kg">kg</option>
                                <option value="g">g (grams)</option>
                            </select>
                        </div>

                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" id="dirRefundRateLabel" style="font-size: 0.8rem; font-weight: 700;">Refund Rate (₹ per unit)</label>
                            <input type="number" id="dirRefundRate" class="form-control" step="any" min="0" style="font-weight: 700;" oninput="recalcDirectReturnTotal()">
                        </div>

                        <div class="form-group" style="margin: 0; display: flex; flex-direction: column; justify-content: center;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; font-weight: 600; cursor: pointer; color: #166534; margin: 0;">
                                <input type="checkbox" id="dirRestockCheck" checked style="width: 17px; height: 17px;">
                                <span>Restock back to inventory</span>
                            </label>
                            <span style="font-size: 0.72rem; color: #64748b; margin-top: 2px;">Adds qty back to stock count</span>
                        </div>
                    </div>
                </div>

                <!-- Customer Details (Optional) & Refund Mode for Direct Return -->
                <div id="dirExtraDetails" style="display: none; display: flex; flex-direction: column; gap: 0.75rem;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem;">
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Customer Name (Optional)</label>
                            <input type="text" id="dirCustName" class="form-control" placeholder="Walk-in Customer">
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Customer Phone (Optional)</label>
                            <input type="text" id="dirCustPhone" class="form-control" placeholder="Mobile Number">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.75rem;">
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Return Reason</label>
                            <select id="dirReasonSelect" class="form-control" style="font-size: 0.85rem;">
                                <option value="Defective / Damaged Item">Defective / Damaged Item</option>
                                <option value="Customer Changed Mind">Customer Changed Mind</option>
                                <option value="Wrong Item / Size Purchased">Wrong Item / Size Purchased</option>
                                <option value="Expired / Quality Concern">Expired / Quality Concern</option>
                                <option value="Direct Exchange / Return">Direct Exchange / Return</option>
                                <option value="Other">Other / Miscellaneous</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin: 0;">
                            <label class="form-label" style="font-size: 0.8rem; font-weight: 700;">Refund Payment Mode</label>
                            <select id="dirRefundMethod" class="form-control" style="font-size: 0.85rem;">
                                <option value="Cash">Cash (Cash Drawer)</option>
                                <option value="UPI / Online">UPI / Online Transfer</option>
                                <option value="Store Credit">Store Credit / Exchange</option>
                            </select>
                        </div>
                    </div>

                    <!-- Direct Return Refund Summary Banner -->
                    <div style="background: linear-gradient(135deg, #fef2f2, #ffe4e6); border: 2px dashed #f43f5e; border-radius: 12px; padding: 1rem 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                            <div>
                                <div style="font-size: 0.85rem; font-weight: 700; color: #9f1239; text-transform: uppercase; letter-spacing: 0.05em;">
                                    Total Direct Refund
                                </div>
                                <div style="font-size: 0.78rem; color: #e11d48;" id="dirItemsSummaryCount">
                                    Direct product return
                                </div>
                            </div>
                            <div style="font-size: 1.8rem; font-weight: 900; color: #be123c;" id="dirTotalRefundDisplay">
                                ₹0.00
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <div class="modal-footer" style="background: #f8fafc; border-top: 1px solid #e2e8f0; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
            <button type="button" class="btn btn-secondary" onclick="closeReturnModal()">
                Cancel
            </button>
            <button type="button" class="btn btn-danger" id="retConfirmBtn" onclick="submitActiveReturn()" style="background: #dc2626; border-color: #b91c1c; font-weight: 700; padding: 0.55rem 1.35rem;" disabled>
                ↩️ Confirm Return & Print Slip
            </button>
        </div>
    </div>
</div>

<!-- Modal 2: Return Voucher Slip Preview & Thermal Print -->
<div id="returnReceiptModal" class="modal-overlay" style="z-index: 130;">
    <div class="modal-card" style="max-width: 460px;">
        <div class="modal-header" style="background: #1e293b; color: #fff;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="font-size: 1.2rem;">🧾</span>
                <h3 class="modal-title" style="color: #fff; font-size: 1.05rem;">Sales Return & Refund Voucher</h3>
            </div>
            <button type="button" class="modal-close-btn" onclick="closeReturnReceiptModal()" style="color: #fff;">✕</button>
        </div>
        <div class="modal-body" style="padding: 1rem; max-height: 70vh; overflow-y: auto;">
            <div id="returnReceiptPrintContainer" class="receipt-printable">
                <!-- Rendered Return Receipt Slip -->
            </div>
        </div>
        <div class="modal-footer" style="justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <button type="button" class="btn btn-primary" onclick="printReturnVoucherThermal()" style="background: #2563eb;" title="Direct 58mm Thermal Print">
                    🖨️ Thermal Print (58mm)
                </button>
                <button type="button" class="btn btn-secondary" onclick="printReturnVoucherBluetooth()" title="Direct Wireless Bluetooth Thermal Print">
                    📶 Bluetooth Print
                </button>
                <button type="button" class="btn btn-secondary" onclick="printReturnVoucherIframe()" title="Open Browser Print Dialog">
                    🖨️ Browser Print
                </button>
            </div>
            <button type="button" class="btn btn-secondary" onclick="closeReturnReceiptModal()">
                Done
            </button>
        </div>
    </div>
</div>

<script>
// ==========================================
// GLOBAL PRODUCT RETURN CONTROLLER (JS)
// ==========================================
(function() {
    let currentReturnMode = 'bill'; // 'bill' or 'direct'
    let currentReturnBill = null;
    let currentReturnResult = null;
    let allProductsCatalog = [];
    let selectedDirectProduct = null;

    window.openReturnModal = function(prefillBillIdOrNum = null) {
        const modal = document.getElementById('productReturnModal');
        if (!modal) return;
        modal.classList.add('active');

        loadAllProductsForDirectReturn();

        if (prefillBillIdOrNum) {
            switchReturnMode('bill');
            document.getElementById('retBillQueryInput').value = prefillBillIdOrNum;
            searchBillForReturn();
        } else {
            loadRecentBillsForReturn();
        }
    };

    window.openReturnModalForBill = function(billId) {
        window.openReturnModal(billId);
    };

    window.closeReturnModal = function() {
        const modal = document.getElementById('productReturnModal');
        if (modal) modal.classList.remove('active');
        resetReturnBillSearch();
        clearDirectProductSelection();
    };

    window.closeReturnReceiptModal = function() {
        const modal = document.getElementById('returnReceiptModal');
        if (modal) modal.classList.remove('active');
    };

    window.switchReturnMode = function(mode) {
        currentReturnMode = mode;
        const btnBill = document.getElementById('tabReturnByBillBtn');
        const btnDirect = document.getElementById('tabReturnDirectBtn');
        const secBill = document.getElementById('sectionReturnByBill');
        const secDirect = document.getElementById('sectionReturnDirect');

        if (mode === 'bill') {
            btnBill.style.background = '#2563eb';
            btnBill.style.color = '#fff';
            btnDirect.style.background = 'transparent';
            btnDirect.style.color = '#475569';
            secBill.style.display = 'flex';
            secDirect.style.display = 'none';
            recalculateBillReturnTotals();
        } else {
            btnDirect.style.background = '#dc2626';
            btnDirect.style.color = '#fff';
            btnBill.style.background = 'transparent';
            btnBill.style.color = '#475569';
            secDirect.style.display = 'flex';
            secBill.style.display = 'none';
            recalcDirectReturnTotal();
        }
    };

    // ----------------------------------------------------
    // TAB 1: RETURN FROM BILL LOGIC
    // ----------------------------------------------------
    window.resetReturnBillSearch = function() {
        currentReturnBill = null;
        document.getElementById('retBillQueryInput').value = '';
        document.getElementById('retBillSearchSection').style.display = 'block';
        document.getElementById('retLoadedBillInfo').style.display = 'none';
        document.getElementById('retItemsContainer').style.display = 'none';
        const discBanner = document.getElementById('retDiscountBanner');
        if (discBanner) discBanner.style.display = 'none';
        document.getElementById('retDetailsRow').style.display = 'none';
        document.getElementById('retSummaryBanner').style.display = 'none';
        document.getElementById('retConfirmBtn').disabled = true;
        loadRecentBillsForReturn();
    };

    async function loadRecentBillsForReturn() {
        const container = document.getElementById('retRecentBadges');
        if (!container) return;
        try {
            const res = await fetch('api.php?action=get_bills&limit=6');
            const data = await res.json();
            if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                container.innerHTML = data.data.map(b => `
                    <button type="button" class="btn btn-secondary" style="padding: 0.15rem 0.45rem; font-size: 0.75rem; border-radius: 4px;" onclick="selectRecentBillForReturn('${b.bill_number}')">
                        ${b.bill_number.replace('BILL-', '')} (₹${parseFloat(b.grand_total).toFixed(0)})
                    </button>
                `).join('');
            } else {
                container.innerHTML = '<span style="color: #94a3b8;">No recent invoices found</span>';
            }
        } catch (e) {
            container.innerHTML = '';
        }
    }

    window.selectRecentBillForReturn = function(billNum) {
        document.getElementById('retBillQueryInput').value = billNum;
        searchBillForReturn();
    };

    window.searchBillForReturn = async function() {
        const query = document.getElementById('retBillQueryInput').value.trim();
        if (!query) {
            alert('Please enter a Bill Number or select a recent invoice.');
            return;
        }

        try {
            const res = await fetch(`api.php?action=get_bill_for_return&bill_query=${encodeURIComponent(query)}`);
            const data = await res.json();
            if (!data.success) {
                alert(data.error || 'Bill not found. Please check bill number.');
                return;
            }

            renderLoadedBillForReturn(data.data);
        } catch (e) {
            alert('Network error while searching for bill: ' + e.message);
        }
    };

    function updatePolicyLabelStyles(policy) {
        const lblDisc = document.getElementById('lblPolicyDiscount');
        const lblAct = document.getElementById('lblPolicyActual');
        if (!lblDisc || !lblAct) return;
        if (policy === 'discount') {
            lblDisc.style.background = '#ecfdf5';
            lblDisc.style.color = '#065f46';
            lblDisc.style.borderColor = '#a7f3d0';
            lblDisc.style.fontWeight = '700';
            lblAct.style.background = 'transparent';
            lblAct.style.color = '#475569';
            lblAct.style.borderColor = 'transparent';
            lblAct.style.fontWeight = '600';
        } else {
            lblAct.style.background = '#f1f5f9';
            lblAct.style.color = '#0f172a';
            lblAct.style.borderColor = '#cbd5e1';
            lblAct.style.fontWeight = '700';
            lblDisc.style.background = 'transparent';
            lblDisc.style.color = '#475569';
            lblDisc.style.borderColor = 'transparent';
            lblDisc.style.fontWeight = '600';
        }
    }

    window.onGlobalPricePolicyChange = function(policy) {
        updatePolicyLabelStyles(policy);
        if (!currentReturnBill || !currentReturnBill.items) return;
        currentReturnBill.items.forEach((it, idx) => {
            const typeSelect = document.getElementById(`retPriceType_${idx}`);
            if (typeSelect) {
                typeSelect.value = policy;
                onItemPriceTypeChange(idx);
            }
        });
    };

    window.onItemPriceTypeChange = function(idx) {
        if (!currentReturnBill || !currentReturnBill.items || !currentReturnBill.items[idx]) return;
        const it = currentReturnBill.items[idx];
        const typeSelect = document.getElementById(`retPriceType_${idx}`);
        const rateInput = document.getElementById(`retRateInput_${idx}`);
        const unitSelect = document.getElementById(`retUnit_${idx}`);
        const unit = unitSelect ? unitSelect.value : (it.selected_unit || 'kg');
        if (!typeSelect || !rateInput) return;

        const mode = typeSelect.value;
        if (mode === 'custom') {
            rateInput.focus();
            rateInput.select();
            return;
        }

        const actBaseRate = parseFloat(it.actual_base_rate || it.actual_unit_rate || it.unit_price || 0);
        const discBaseRate = parseFloat(it.discounted_base_rate || it.discounted_unit_rate || actBaseRate);

        let rate = 0;
        if (mode === 'actual') {
            rate = (unit === 'g' || unit === 'ml') ? (actBaseRate / 1000) : actBaseRate;
        } else {
            rate = (unit === 'g' || unit === 'ml') ? (discBaseRate / 1000) : discBaseRate;
        }

        const decimals = (unit === 'g' || unit === 'ml') ? 4 : 2;
        rateInput.value = parseFloat(rate.toFixed(decimals));
        recalculateBillReturnTotals();
    };

    window.onCustomRateInput = function(idx) {
        const typeSelect = document.getElementById(`retPriceType_${idx}`);
        if (typeSelect && typeSelect.value !== 'custom') {
            typeSelect.value = 'custom';
        }
        recalculateBillReturnTotals();
    };

    function renderLoadedBillForReturn(data) {
        currentReturnBill = data;
        const b = data.bill;
        const items = data.items || [];

        document.getElementById('retBillSearchSection').style.display = 'none';
        document.getElementById('retLoadedBillInfo').style.display = 'block';
        document.getElementById('retItemsContainer').style.display = 'block';
        document.getElementById('retDetailsRow').style.display = 'grid';
        document.getElementById('retSummaryBanner').style.display = 'block';

        const hasBillDiscount = parseFloat(b.discount || 0) > 0.0001;
        const discountBanner = document.getElementById('retDiscountBanner');
        if (discountBanner) {
            if (hasBillDiscount) {
                discountBanner.style.display = 'block';
                document.getElementById('retDiscAmtDisplay').innerText = parseFloat(b.discount).toFixed(2);
                const rDisc = document.getElementById('radioPolicyDiscount');
                if (rDisc) rDisc.checked = true;
                updatePolicyLabelStyles('discount');
            } else {
                discountBanner.style.display = 'none';
            }
        }

        document.getElementById('retInfoBillNumber').innerText = b.bill_number;
        document.getElementById('retInfoBillDate').innerText = b.created_at || '';
        document.getElementById('retInfoCustomer').innerText = `${b.customer_name}${b.customer_phone ? ' (' + b.customer_phone + ')' : ''}`;
        document.getElementById('retInfoTotal').innerText = `₹${parseFloat(b.grand_total).toFixed(2)}`;

        let alreadyRefunded = 0;
        (data.previous_returns || []).forEach(r => {
            alreadyRefunded += parseFloat(r.total_refund || 0);
        });
        document.getElementById('retInfoAlreadyRefunded').innerText = `₹${alreadyRefunded.toFixed(2)}`;

        const tbody = document.getElementById('retItemsTableBody');
        if (items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="10" style="text-align:center; padding: 1.5rem; color: #94a3b8;">No items found on this bill.</td></tr>`;
            return;
        }

        let html = '';
        items.forEach((it, idx) => {
            const avail = parseFloat(it.available_qty || 0);
            const isFullyReturned = avail <= 0.0001;
            const unit = it.selected_unit || '';
            const baseUnit = it.product_base_unit || it.base_unit || unit;
            const hasItemDiscount = Boolean(it.has_discount || hasBillDiscount || (parseFloat(it.item_discount || 0) > 0.0001));

            const actRate = parseFloat(it.actual_unit_rate || it.unit_price || 0);
            const discRate = parseFloat(it.discounted_unit_rate || it.effective_unit_rate || actRate);
            const initialRate = hasItemDiscount ? discRate : actRate;
            const initialReturnQty = isFullyReturned ? 0 : avail;

            // Note: checkboxes start unchecked if there's more than 1 item so cashier easily chooses particular product!
            const autoChecked = items.length === 1 && !isFullyReturned ? 'checked' : '';

            let unitSelectHtml = `<span style="font-weight: 700; font-size: 0.85rem; color: #475569; margin-left: 3px;">${unit}</span>`;
            if (baseUnit === 'kg') {
                unitSelectHtml = `
                    <select id="retUnit_${idx}" class="form-control" style="width: auto; display: inline-block; padding: 2px 5px; font-size: 0.8rem; font-weight: 700; height: 28px; cursor: pointer; margin-left: 3px;" onchange="onBillItemUnitChange(${idx})">
                        <option value="kg" ${unit === 'kg' ? 'selected' : ''}>kg</option>
                        <option value="g" ${unit === 'g' ? 'selected' : ''}>g</option>
                    </select>
                `;
            } else if (baseUnit === 'l') {
                unitSelectHtml = `
                    <select id="retUnit_${idx}" class="form-control" style="width: auto; display: inline-block; padding: 2px 5px; font-size: 0.8rem; font-weight: 700; height: 28px; cursor: pointer; margin-left: 3px;" onchange="onBillItemUnitChange(${idx})">
                        <option value="l" ${unit === 'l' ? 'selected' : ''}>l</option>
                        <option value="ml" ${unit === 'ml' ? 'selected' : ''}>ml</option>
                    </select>
                `;
            }

            let priceOptionHtml = '';
            if (hasItemDiscount) {
                priceOptionHtml = `
                    <select id="retPriceType_${idx}" class="form-control ret-price-type" data-idx="${idx}" style="width: 100%; padding: 2px 4px; font-size: 0.78rem; font-weight: 700; height: 30px; border-color: #f59e0b; cursor: pointer; background: #fffbeb;" onchange="onItemPriceTypeChange(${idx})">
                        <option value="discount" selected>🏷️ தள்ளுபடி (₹${discRate.toFixed(2)})</option>
                        <option value="actual">📦 அசல் MRP (₹${actRate.toFixed(2)})</option>
                        <option value="custom">✏️ Custom...</option>
                    </select>
                `;
            } else {
                priceOptionHtml = `<span style="font-size: 0.8rem; color: #64748b; font-weight: 600;">Standard MRP</span>`;
            }

            html += `
                <tr id="retRow_${idx}" style="${isFullyReturned ? 'opacity: 0.55; background: #f8fafc;' : ''}">
                    <td style="text-align: center;">
                        <input type="checkbox" id="retCheck_${idx}" class="ret-item-checkbox" data-idx="${idx}" ${isFullyReturned ? 'disabled' : autoChecked} onchange="recalculateBillReturnTotals()">
                    </td>
                    <td>
                        <strong style="color: #0f172a; font-size: 0.92rem;">${it.product_name}</strong>
                        ${isFullyReturned ? '<br><span style="color: #dc2626; font-size: 0.72rem; font-weight: 700;">(Already 100% Returned)</span>' : ''}
                    </td>
                    <td style="text-align: center; white-space: nowrap;">
                        ${it.entered_qty} ${unit}
                    </td>
                    <td style="text-align: center; color: #64748b; white-space: nowrap;">
                        ${parseFloat(it.already_returned_qty || 0) > 0 ? `<span style="color: #b91c1c; font-weight: 600;">${it.already_returned_qty} ${unit}</span>` : '0'}
                    </td>
                    <td style="text-align: center; white-space: nowrap;">
                        ${isFullyReturned ? '<span style="color: #94a3b8;">-</span>' : `
                            <div style="display: inline-flex; align-items: center; justify-content: center;">
                                <div style="display: inline-flex; align-items: center; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; background: #fff;">
                                    <button type="button" style="border: none; background: #f1f5f9; padding: 2px 7px; font-weight: bold; cursor: pointer;" onclick="stepReturnQty(${idx}, -1)">−</button>
                                    <input type="number" id="retQty_${idx}" class="ret-qty-input" data-idx="${idx}" data-max="${avail}" value="${initialReturnQty}" min="0.001" max="${avail}" step="any" style="width: 58px; border: none; text-align: center; font-weight: 700; padding: 3px 0;" oninput="onReturnQtyInput(${idx})">
                                    <button type="button" style="border: none; background: #f1f5f9; padding: 2px 7px; font-weight: bold; cursor: pointer;" onclick="stepReturnQty(${idx}, 1)">+</button>
                                </div>
                                ${unitSelectHtml}
                            </div>
                        `}
                    </td>
                    <td style="text-align: center;">
                        ${priceOptionHtml}
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        ${isFullyReturned ? '<span style="color: #94a3b8;">-</span>' : `
                            <div style="display: inline-flex; align-items: center; justify-content: flex-end; gap: 2px;">
                                <span style="font-size: 0.85rem; font-weight: 700; color: #475569;">₹</span>
                                <input type="number" id="retRateInput_${idx}" class="form-control ret-rate-input" data-idx="${idx}" value="${initialRate.toFixed(2)}" step="any" min="0" oninput="onCustomRateInput(${idx})" style="width: 68px; padding: 2px 4px; text-align: right; font-weight: 700; font-size: 0.85rem; height: 28px; border: 1px solid #cbd5e1; border-radius: 4px;">
                            </div>
                            <div style="font-size: 0.7rem; color: #64748b;" id="retRateUnitLabel_${idx}">per ${unit}</div>
                        `}
                    </td>
                    <td style="text-align: right; font-weight: 700; color: #b91c1c; white-space: nowrap;" id="retSubtotalDisplay_${idx}">
                        ₹0.00
                    </td>
                    <td style="text-align: center;">
                        <input type="checkbox" id="retRestock_${idx}" class="ret-restock-check" ${isFullyReturned ? 'disabled' : 'checked'} title="Check to restock product quantity into warehouse inventory">
                    </td>
                    <td style="text-align: center;">
                        ${isFullyReturned ? '<span style="font-size: 0.75rem; color: #94a3b8;">Completed</span>' : `
                            <button type="button" class="btn btn-secondary" onclick="returnOnlyThisItem(${idx})" style="padding: 0.2rem 0.5rem; font-size: 0.76rem; background: #fff1f2; border-color: #fecdd3; color: #be123c; font-weight: 700;" title="Return only this single product">
                                ↩️ Return This
                            </button>
                        `}
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        recalculateBillReturnTotals();
    }

    // Single product return shortcut for a bill item
    window.returnOnlyThisItem = function(selectedIdx) {
        const checkboxes = document.querySelectorAll('.ret-item-checkbox');
        checkboxes.forEach((cb, idx) => {
            cb.checked = (idx === selectedIdx);
        });

        // Set qty to max available for this item
        const input = document.getElementById(`retQty_${selectedIdx}`);
        if (input) {
            input.value = input.dataset.max || 1;
        }

        recalculateBillReturnTotals();

        // Highlight selected row briefly
        const row = document.getElementById(`retRow_${selectedIdx}`);
        if (row) {
            row.style.outline = '2px solid #ef4444';
            row.style.backgroundColor = '#fef2f2';
            setTimeout(() => {
                row.style.outline = '';
                row.style.backgroundColor = '';
            }, 1200);
        }
    };

    window.toggleSelectAllBillItems = function() {
        const checkboxes = document.querySelectorAll('.ret-item-checkbox:not(:disabled)');
        const anyUnchecked = Array.from(checkboxes).some(cb => !cb.checked);
        checkboxes.forEach(cb => cb.checked = anyUnchecked);
        recalculateBillReturnTotals();
    };

    window.onReturnQtyInput = function(idx) {
        const check = document.getElementById(`retCheck_${idx}`);
        const qtyInput = document.getElementById(`retQty_${idx}`);
        if (check && qtyInput && parseFloat(qtyInput.value || 0) > 0) {
            check.checked = true;
        }
        recalculateBillReturnTotals();
    };

    window.stepReturnQty = function(idx, delta) {
        const input = document.getElementById(`retQty_${idx}`);
        const check = document.getElementById(`retCheck_${idx}`);
        const unitSelect = document.getElementById(`retUnit_${idx}`);
        const unit = unitSelect ? unitSelect.value : 'kg';
        if (!input) return;
        const max = parseFloat(input.dataset.max || 1);
        const step = (unit === 'g' || unit === 'ml') ? 50 : 1;
        let val = parseFloat(input.value || 0) + (delta * step);
        const minVal = (unit === 'g' || unit === 'ml') ? 1 : 0.001;
        if (val < minVal) val = minVal;
        if (val > max) val = max;
        input.value = val;
        if (check) check.checked = true;
        recalculateBillReturnTotals();
    };

    window.onBillItemUnitChange = function(idx) {
        if (!currentReturnBill || !currentReturnBill.items || !currentReturnBill.items[idx]) return;
        const it = currentReturnBill.items[idx];
        const unitSelect = document.getElementById(`retUnit_${idx}`);
        const qtyInput = document.getElementById(`retQty_${idx}`);
        const rateInput = document.getElementById(`retRateInput_${idx}`);
        const typeSelect = document.getElementById(`retPriceType_${idx}`);
        const rateUnitLabel = document.getElementById(`retRateUnitLabel_${idx}`);
        if (!unitSelect || !qtyInput || !rateInput) return;

        const newUnit = unitSelect.value;
        const baseUnit = it.product_base_unit || it.base_unit || it.selected_unit || 'kg';
        const origUnit = it.selected_unit || baseUnit;
        const origAvail = parseFloat(it.available_qty || 0);

        let availBase = (it.available_base_qty !== undefined && it.available_base_qty !== null) ? parseFloat(it.available_base_qty) : origAvail;
        if (it.available_base_qty === undefined && (origUnit === 'g' || origUnit === 'ml')) {
            availBase = origAvail / 1000;
        }

        let newMax = availBase;
        if (newUnit === 'g' || newUnit === 'ml') {
            newMax = Math.round(availBase * 1000 * 100) / 100;
            if (parseFloat(qtyInput.value) <= 1 && newMax >= 100) {
                qtyInput.value = Math.min(newMax, 500);
            } else if (parseFloat(qtyInput.value) > newMax) {
                qtyInput.value = newMax;
            }
        } else {
            newMax = availBase;
            if (parseFloat(qtyInput.value) > newMax) {
                qtyInput.value = newMax;
            }
        }

        qtyInput.dataset.max = newMax;
        qtyInput.max = newMax;

        if (rateUnitLabel) {
            rateUnitLabel.innerText = `per ${newUnit}`;
        }

        const actBaseRate = parseFloat(it.actual_base_rate || it.actual_unit_rate || it.unit_price || 0);
        const discBaseRate = parseFloat(it.discounted_base_rate || it.discounted_unit_rate || actBaseRate);
        const actRateUnit = (newUnit === 'g' || newUnit === 'ml') ? (actBaseRate / 1000) : actBaseRate;
        const discRateUnit = (newUnit === 'g' || newUnit === 'ml') ? (discBaseRate / 1000) : discBaseRate;
        const decimals = (newUnit === 'g' || newUnit === 'ml') ? 4 : 2;

        if (typeSelect) {
            const currentMode = typeSelect.value;
            typeSelect.innerHTML = `
                <option value="discount" ${currentMode === 'discount' ? 'selected' : ''}>🏷️ தள்ளுபடி (₹${discRateUnit.toFixed(decimals)})</option>
                <option value="actual" ${currentMode === 'actual' ? 'selected' : ''}>📦 அசல் MRP (₹${actRateUnit.toFixed(decimals)})</option>
                <option value="custom" ${currentMode === 'custom' ? 'selected' : ''}>✏️ Custom...</option>
            `;
            if (currentMode === 'actual') {
                rateInput.value = parseFloat(actRateUnit.toFixed(decimals));
            } else if (currentMode === 'discount') {
                rateInput.value = parseFloat(discRateUnit.toFixed(decimals));
            }
        } else {
            rateInput.value = parseFloat(actRateUnit.toFixed(decimals));
        }

        recalculateBillReturnTotals();
    };

    window.recalculateBillReturnTotals = function() {
        if (!currentReturnBill || !currentReturnBill.items) return;

        let totalRefund = 0;
        let selectedCount = 0;
        const items = currentReturnBill.items;

        items.forEach((it, idx) => {
            const check = document.getElementById(`retCheck_${idx}`);
            const qtyInput = document.getElementById(`retQty_${idx}`);
            const rateInput = document.getElementById(`retRateInput_${idx}`);
            const subDisplay = document.getElementById(`retSubtotalDisplay_${idx}`);

            if (!check || !qtyInput || !subDisplay) return;

            if (check.checked) {
                const max = parseFloat(qtyInput.dataset.max || 0);
                let qty = parseFloat(qtyInput.value || 0);
                if (qty > max) {
                    qty = max;
                    qtyInput.value = max;
                }
                if (qty < 0) {
                    qty = 0;
                    qtyInput.value = 0;
                }
                const rate = rateInput ? parseFloat(rateInput.value || 0) : 0;
                const sub = Math.round(qty * rate * 100) / 100;
                subDisplay.innerText = `₹${sub.toFixed(2)}`;

                if (qty > 0) {
                    totalRefund += sub;
                    selectedCount++;
                }
            } else {
                subDisplay.innerText = '₹0.00';
            }
        });

        totalRefund = Math.round(totalRefund * 100) / 100;
        document.getElementById('retTotalRefundDisplay').innerText = `₹${totalRefund.toFixed(2)}`;
        document.getElementById('retItemsSummaryCount').innerText = `${selectedCount} item(s) selected for return`;

        const confirmBtn = document.getElementById('retConfirmBtn');
        if (confirmBtn && currentReturnMode === 'bill') {
            confirmBtn.disabled = totalRefund <= 0;
            confirmBtn.innerText = `↩️ Confirm Return (₹${totalRefund.toFixed(2)}) & Print Slip`;
        }
    };

    // ----------------------------------------------------
    // TAB 2: DIRECT PRODUCT RETURN (NO BILL NEEDED)
    // ----------------------------------------------------
    async function loadAllProductsForDirectReturn() {
        if (allProductsCatalog.length > 0) return;
        try {
            const res = await fetch('api.php?action=get_products');
            const data = await res.json();
            if (data.success && Array.isArray(data.data)) {
                allProductsCatalog = data.data;
            }
        } catch (e) {
            console.error('Failed to load products for return:', e);
        }
    }

    window.filterDirectProducts = function(val) {
        const query = (val || '').toLowerCase().trim();
        const dropdown = document.getElementById('dirProdDropdown');
        if (!dropdown) return;

        if (!query) {
            dropdown.style.display = 'none';
            dropdown.innerHTML = '';
            return;
        }

        const filtered = allProductsCatalog.filter(p => 
            p.name.toLowerCase().includes(query) || 
            (p.barcode && p.barcode.toLowerCase().includes(query))
        ).slice(0, 15);

        if (filtered.length === 0) {
            dropdown.innerHTML = `<div style="padding: 0.75rem; color: #94a3b8; font-size: 0.85rem; text-align: center;">No matching products found</div>`;
            dropdown.style.display = 'block';
            return;
        }

        dropdown.innerHTML = filtered.map(p => `
            <div style="padding: 0.65rem 0.85rem; cursor: pointer; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;" 
                 onmouseover="this.style.background='#f8fafc'" 
                 onmouseout="this.style.background='transparent'" 
                 onclick="selectDirectProduct(${p.id})">
                <div>
                    <strong style="color: #0f172a; font-size: 0.9rem;">${p.name}</strong>
                    <div style="font-size: 0.75rem; color: #64748b;">
                        Stock: ${p.stock_qty} ${p.base_unit} ${p.barcode ? ' | Barcode: ' + p.barcode : ''}
                    </div>
                </div>
                <div style="font-weight: 700; color: #059669; font-size: 0.9rem;">
                    ₹${parseFloat(p.selling_price).toFixed(2)} / ${p.base_unit}
                </div>
            </div>
        `).join('');
        dropdown.style.display = 'block';
    };

    window.selectDirectProduct = function(prodId) {
        const prod = allProductsCatalog.find(p => p.id == prodId);
        if (!prod) return;

        selectedDirectProduct = prod;

        // Hide dropdown
        const dropdown = document.getElementById('dirProdDropdown');
        if (dropdown) dropdown.style.display = 'none';
        document.getElementById('dirProdSearchInput').value = prod.name;

        // Fill card details
        document.getElementById('dirProdName').innerText = prod.name;
        document.getElementById('dirProdStock').innerText = `${prod.stock_qty} ${prod.base_unit}`;
        document.getElementById('dirProdStdPrice').innerText = `₹${parseFloat(prod.selling_price).toFixed(2)} / ${prod.base_unit}`;

        // Populate unit dropdown
        const unitSelect = document.getElementById('dirReturnUnit');
        const base = prod.base_unit || 'kg';
        if (unitSelect) {
            if (base === 'kg') {
                unitSelect.innerHTML = `<option value="kg" selected>kg</option><option value="g">g (grams)</option>`;
            } else if (base === 'l') {
                unitSelect.innerHTML = `<option value="l" selected>l</option><option value="ml">ml (milliliters)</option>`;
            } else {
                unitSelect.innerHTML = `<option value="${base}" selected>${base}</option>`;
            }
            unitSelect.value = base;
        }

        const rateLabel = document.getElementById('dirRefundRateLabel');
        if (rateLabel) rateLabel.innerText = `Refund Rate (₹ per ${base})`;

        document.getElementById('dirReturnQty').value = 1;
        document.getElementById('dirRefundRate').value = parseFloat(prod.selling_price).toFixed(2);
        document.getElementById('dirRestockCheck').checked = true;

        document.getElementById('dirSelectedProductCard').style.display = 'block';
        document.getElementById('dirExtraDetails').style.display = 'flex';

        recalcDirectReturnTotal();
    };

    window.onDirectUnitChange = function() {
        if (!selectedDirectProduct) return;
        const unitSelect = document.getElementById('dirReturnUnit');
        const qtyInput = document.getElementById('dirReturnQty');
        const rateInput = document.getElementById('dirRefundRate');
        const rateLabel = document.getElementById('dirRefundRateLabel');
        if (!unitSelect || !qtyInput || !rateInput) return;

        const newUnit = unitSelect.value;
        const base = selectedDirectProduct.base_unit || 'kg';
        const stdPrice = parseFloat(selectedDirectProduct.selling_price || 0);

        if (base === 'kg') {
            if (newUnit === 'g') {
                rateInput.value = (stdPrice / 1000).toFixed(4).replace(/\.?0+$/, '') || '0';
                if (rateLabel) rateLabel.innerText = 'Refund Rate (₹ per g)';
                if (parseFloat(qtyInput.value) <= 1) {
                    qtyInput.value = 500;
                }
            } else {
                rateInput.value = stdPrice.toFixed(2);
                if (rateLabel) rateLabel.innerText = 'Refund Rate (₹ per kg)';
                if (parseFloat(qtyInput.value) >= 50) {
                    qtyInput.value = (parseFloat(qtyInput.value) / 1000).toFixed(3).replace(/\.?0+$/, '');
                }
            }
        } else if (base === 'l') {
            if (newUnit === 'ml') {
                rateInput.value = (stdPrice / 1000).toFixed(4).replace(/\.?0+$/, '') || '0';
                if (rateLabel) rateLabel.innerText = 'Refund Rate (₹ per ml)';
                if (parseFloat(qtyInput.value) <= 1) {
                    qtyInput.value = 500;
                }
            } else {
                rateInput.value = stdPrice.toFixed(2);
                if (rateLabel) rateLabel.innerText = 'Refund Rate (₹ per l)';
                if (parseFloat(qtyInput.value) >= 50) {
                    qtyInput.value = (parseFloat(qtyInput.value) / 1000).toFixed(3).replace(/\.?0+$/, '');
                }
            }
        } else {
            rateInput.value = stdPrice.toFixed(2);
            if (rateLabel) rateLabel.innerText = `Refund Rate (₹ per ${base})`;
        }

        recalcDirectReturnTotal();
    };

    window.clearDirectProductSelection = function() {
        selectedDirectProduct = null;
        document.getElementById('dirProdSearchInput').value = '';
        const dropdown = document.getElementById('dirProdDropdown');
        if (dropdown) dropdown.style.display = 'none';
        document.getElementById('dirSelectedProductCard').style.display = 'none';
        document.getElementById('dirExtraDetails').style.display = 'none';
        recalcDirectReturnTotal();
    };

    window.stepDirectQty = function(delta) {
        const input = document.getElementById('dirReturnQty');
        const unitSelect = document.getElementById('dirReturnUnit');
        const unit = unitSelect ? unitSelect.value : 'kg';
        if (!input) return;
        const step = (unit === 'g' || unit === 'ml') ? 50 : 1;
        let val = parseFloat(input.value || 0) + (delta * step);
        const minVal = (unit === 'g' || unit === 'ml') ? 1 : 0.001;
        if (val < minVal) val = minVal;
        input.value = val;
        recalcDirectReturnTotal();
    };

    window.recalcDirectReturnTotal = function() {
        if (!selectedDirectProduct) {
            document.getElementById('dirTotalRefundDisplay').innerText = '₹0.00';
            const confirmBtn = document.getElementById('retConfirmBtn');
            if (confirmBtn && currentReturnMode === 'direct') {
                confirmBtn.disabled = true;
                confirmBtn.innerText = '↩️ Confirm Direct Return & Print Slip';
            }
            return;
        }

        const qty = parseFloat(document.getElementById('dirReturnQty').value || 0);
        const unit = document.getElementById('dirReturnUnit') ? document.getElementById('dirReturnUnit').value : (selectedDirectProduct.base_unit || 'kg');
        const rate = parseFloat(document.getElementById('dirRefundRate').value || 0);
        const total = Math.max(0, Math.round(qty * rate * 100) / 100);

        document.getElementById('dirTotalRefundDisplay').innerText = `₹${total.toFixed(2)}`;
        document.getElementById('dirItemsSummaryCount').innerText = `1 item: ${qty} ${unit} of ${selectedDirectProduct.name}`;

        const confirmBtn = document.getElementById('retConfirmBtn');
        if (confirmBtn && currentReturnMode === 'direct') {
            confirmBtn.disabled = total <= 0;
            confirmBtn.innerText = `↩️ Confirm Direct Return (₹${total.toFixed(2)}) & Print Slip`;
        }
    };

    // ----------------------------------------------------
    // SUBMIT HANDLER (DISPATCHES TO BILL OR DIRECT)
    // ----------------------------------------------------
    window.submitActiveReturn = function() {
        if (currentReturnMode === 'direct') {
            submitDirectReturn();
        } else {
            submitBillReturn();
        }
    };

    async function submitBillReturn() {
        if (!currentReturnBill || !currentReturnBill.bill) return;

        const bill = currentReturnBill.bill;
        const items = currentReturnBill.items || [];
        const payloadItems = [];

        items.forEach((it, idx) => {
            const check = document.getElementById(`retCheck_${idx}`);
            const qtyInput = document.getElementById(`retQty_${idx}`);
            const unitSelect = document.getElementById(`retUnit_${idx}`);
            const restockCheck = document.getElementById(`retRestock_${idx}`);
            const rateInput = document.getElementById(`retRateInput_${idx}`);
            const typeSelect = document.getElementById(`retPriceType_${idx}`);

            if (check && check.checked && qtyInput) {
                const returnQty = parseFloat(qtyInput.value || 0);
                const selectedUnit = unitSelect ? unitSelect.value : (it.selected_unit || 'kg');
                const refundRate = rateInput ? parseFloat(rateInput.value || 0) : 0;
                const priceType = typeSelect ? typeSelect.value : (it.has_discount ? 'discount' : 'actual');
                const subtotal = Math.round(returnQty * refundRate * 100) / 100;

                if (returnQty > 0) {
                    payloadItems.push({
                        bill_item_id: it.id,
                        product_id: it.product_id,
                        return_qty: returnQty,
                        selected_unit: selectedUnit,
                        price_type: priceType,
                        refund_rate: refundRate,
                        refund_amount: subtotal,
                        restock: restockCheck && restockCheck.checked ? 1 : 0
                    });
                }
            }
        });

        if (payloadItems.length === 0) {
            alert('Please select at least one item with quantity greater than 0 to return.');
            return;
        }

        const refundMethod = document.getElementById('retRefundMethod').value;
        const returnReason = document.getElementById('retReasonSelect').value;

        const confirmMsg = `Process return for ${payloadItems.length} item(s)?\nTotal Refund: ${document.getElementById('retTotalRefundDisplay').innerText}\nRefund Mode: ${refundMethod}`;
        if (!confirm(confirmMsg)) return;

        const confirmBtn = document.getElementById('retConfirmBtn');
        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.innerText = 'Processing Return...';
        }

        try {
            const res = await fetch('api.php?action=process_return', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    bill_id: bill.id,
                    refund_method: refundMethod,
                    return_reason: returnReason,
                    items: payloadItems
                })
            });

            const data = await res.json();
            if (!data.success) {
                alert(data.error || 'Failed to process return.');
                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    recalculateBillReturnTotals();
                }
                return;
            }

            handleReturnSuccess(data.data);
        } catch (e) {
            alert('Error processing return: ' + e.message);
            if (confirmBtn) {
                confirmBtn.disabled = false;
                recalculateBillReturnTotals();
            }
        }
    }

    async function submitDirectReturn() {
        if (!selectedDirectProduct) {
            alert('Please search and select a product to return.');
            return;
        }

        const qty = parseFloat(document.getElementById('dirReturnQty').value || 0);
        if (qty <= 0) {
            alert('Please enter a valid return quantity greater than 0.');
            return;
        }

        const rate = parseFloat(document.getElementById('dirRefundRate').value || 0);
        const unitSelect = document.getElementById('dirReturnUnit');
        const unit = unitSelect ? unitSelect.value : (selectedDirectProduct.base_unit || 'kg');
        const restock = document.getElementById('dirRestockCheck').checked ? 1 : 0;
        const custName = document.getElementById('dirCustName').value.trim() || 'Walk-in Customer';
        const custPhone = document.getElementById('dirCustPhone').value.trim();
        const refundMethod = document.getElementById('dirRefundMethod').value;
        const returnReason = document.getElementById('dirReasonSelect').value;
        const total = Math.round(qty * rate * 100) / 100;

        const confirmMsg = `Confirm DIRECT Return of:\n${selectedDirectProduct.name}\nQuantity: ${qty} ${unit}\nTotal Refund: ₹${total.toFixed(2)}\nMode: ${refundMethod}`;
        if (!confirm(confirmMsg)) return;

        const confirmBtn = document.getElementById('retConfirmBtn');
        if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.innerText = 'Processing Direct Return...';
        }

        try {
            const res = await fetch('api.php?action=process_return', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    bill_id: 0,
                    customer_name: custName,
                    customer_phone: custPhone,
                    refund_method: refundMethod,
                    return_reason: returnReason,
                    items: [{
                        product_id: selectedDirectProduct.id,
                        selected_unit: unit,
                        return_qty: qty,
                        refund_rate: rate,
                        restock: restock
                    }]
                })
            });

            const data = await res.json();
            if (!data.success) {
                alert(data.error || 'Failed to process direct return.');
                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    recalcDirectReturnTotal();
                }
                return;
            }

            handleReturnSuccess(data.data);
        } catch (e) {
            alert('Error processing direct return: ' + e.message);
            if (confirmBtn) {
                confirmBtn.disabled = false;
                recalcDirectReturnTotal();
            }
        }
    }

    function handleReturnSuccess(returnData) {
        currentReturnResult = returnData;
        closeReturnModal();

        // Refresh lists if on history page or POS billing page
        if (typeof window.loadBills === 'function') {
            window.loadBills();
        }
        if (typeof window.loadReturns === 'function') {
            window.loadReturns();
        }
        if (typeof window.App !== 'undefined' && typeof window.App.loadProducts === 'function') {
            window.App.loadProducts();
        }

        // Render Return Voucher Slip and open print modal
        renderReturnVoucherSlip(returnData);
        document.getElementById('returnReceiptModal').classList.add('active');

        // Trigger Bluetooth print if connected
        if (window.BluetoothPrinter && BluetoothPrinter.isConnected()) {
            printReturnVoucherBluetooth();
        }
    }

    // ----------------------------------------------------
    // RECEIPT & PRINTING
    // ----------------------------------------------------
    function renderReturnVoucherSlip(ret) {
        const container = document.getElementById('returnReceiptPrintContainer');
        if (!container) return;

        const store = ret.store || (typeof globalStoreSettings !== 'undefined' ? globalStoreSettings : {}) || {};
        const curr = (store && store.currency_symbol) || (typeof globalStoreSettings !== 'undefined' && globalStoreSettings.currency_symbol) || '₹';
        const storeName = store.store_name || 'BillKartX Smart Billing';
        const storeAddr = store.store_address || '';
        const storePhone = store.store_phone || '';
        const isDirect = !ret.bill_id || ret.bill_number === 'DIRECT-RETURN';

        let itemsHtml = '';
        (ret.items || []).forEach((it, idx) => {
            const unit = it.selected_unit || '';
            const qtyText = `${it.return_qty}${unit ? ' ' + unit : ''}`;
            itemsHtml += `
                <tr>
                    <td style="text-align: left; vertical-align: top;">
                        ${idx + 1}. ${it.product_name}
                        ${it.restock ? '<br><small style="color: #16a34a; font-size: 0.72rem;">(Restocked to Inventory)</small>' : '<br><small style="color: #dc2626; font-size: 0.72rem;">(Damaged/Not Restocked)</small>'}
                    </td>
                    <td style="text-align: center; vertical-align: top; white-space: nowrap;">${qtyText}</td>
                    <td style="text-align: right; vertical-align: top; white-space: nowrap; font-weight: 700; color: #b91c1c;">${curr}${parseFloat(it.refund_amount).toFixed(2)}</td>
                </tr>
            `;
        });

        container.innerHTML = `
            <div id="returnPrintArea" class="receipt-printable">
                <div class="receipt-header">
                    <div style="text-align: center; margin-bottom: 6px;">
                        <img src="assets/BillKartX Smart Billing Logo.png" alt="BillKartX" style="max-height: 38px; max-width: 170px; object-fit: contain;">
                    </div>
                    <div class="receipt-title">${storeName}</div>
                    ${storeAddr ? `<div style="font-size: 0.8rem;">${storeAddr}</div>` : ''}
                    ${storePhone ? `<div style="font-size: 0.8rem;">Phone: ${storePhone}</div>` : ''}
                    <div class="receipt-divider"></div>
                    <div style="font-weight: 800; font-size: 0.95rem; margin-bottom: 2px; color: #b91c1c;">
                        *** SALES RETURN & REFUND VOUCHER ***
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                        <span><strong>Return #:</strong> ${ret.return_number}</span>
                        <span>${ret.created_at || ''}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                        <span><strong>${isDirect ? 'Type:' : 'Ref Bill:'}</strong> ${isDirect ? 'Direct Return (No Bill)' : ret.bill_number}</span>
                        ${((ret.customer_name && ret.customer_name.trim().toLowerCase() !== 'walk-in customer' && ret.customer_name.trim().toLowerCase() !== 'walk-in')) ? `<span><strong>Customer:</strong> ${ret.customer_name}</span>` : ''}
                    </div>
                </div>

                <div class="receipt-divider"></div>

                <table class="receipt-table">
                    <thead>
                        <tr>
                            <th style="text-align: left;">Returned Product</th>
                            <th class="text-center" style="width: 24%; text-align: center;">Qty</th>
                            <th class="text-right" style="width: 26%; text-align: right;">Refund</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itemsHtml}
                    </tbody>
                </table>

                <div class="receipt-divider"></div>

                <div class="receipt-totals" style="font-size: 0.9rem;">
                    <div class="totals-row" style="font-weight: 800; font-size: 1.05rem; color: #b91c1c; display: flex; justify-content: space-between; border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 4px 0;">
                        <span>TOTAL REFUND:</span>
                        <span>${curr}${parseFloat(ret.total_refund).toFixed(2)}</span>
                    </div>
                    <div class="totals-row" style="display: flex; justify-content: space-between; margin-top: 4px; font-size: 0.8rem;">
                        <span>Refund Payment Mode:</span>
                        <span>${ret.refund_method || 'Cash'}</span>
                    </div>
                    ${ret.return_reason ? `
                    <div class="totals-row" style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                        <span>Return Reason:</span>
                        <span>${ret.return_reason}</span>
                    </div>` : ''}
                </div>

                <div class="receipt-divider"></div>

                <div class="receipt-footer" style="text-align: center; font-size: 0.78rem; margin-top: 6px;">
                    <div>*** Customer Refund Completed ***</div>
                </div>
            </div>
        `;
    }

    window.printReturnVoucherIframe = function() {
        const printArea = document.getElementById('returnReceiptPrintContainer');
        if (!printArea) return;

        let iframe = document.getElementById('returnThermalIframe');
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = 'returnThermalIframe';
            iframe.style.position = 'fixed';
            iframe.style.right = '0';
            iframe.style.bottom = '0';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.style.border = '0';
            document.body.appendChild(iframe);
        }

        const doc = iframe.contentWindow.document;
        doc.open();
        doc.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>Sales Return Voucher</title>
                <style>
                    @page { size: 58mm auto; margin: 0; }
                    * { box-sizing: border-box; margin: 0; padding: 0; }
                    body {
                        font-family: 'Nirmala UI', 'Noto Sans Tamil', 'Latha', 'Tamil Sangam MN', Arial, sans-serif;
                        font-size: 11px;
                        line-height: 1.25;
                        width: 58mm;
                        max-width: 58mm;
                        padding: 3mm 2mm;
                        color: #000;
                        background: #fff;
                    }
                    .receipt-title { font-size: 13px; font-weight: 900; text-align: center; text-transform: uppercase; margin-bottom: 2px; }
                    .receipt-header { text-align: center; margin-bottom: 4px; }
                    .receipt-divider { border-bottom: 1px dashed #000; margin: 4px 0; }
                    .receipt-table { width: 100%; border-collapse: collapse; margin: 4px 0; }
                    .receipt-table th { border-bottom: 1px solid #000; font-weight: bold; padding: 2px 0; font-size: 10px; }
                    .receipt-table td { padding: 2px 0; font-size: 10px; vertical-align: top; }
                    .text-right { text-align: right; }
                    .text-center { text-align: center; }
                    img { max-height: 38px; max-width: 150px; display: block; margin: 0 auto 4px auto; }
                </style>
            </head>
            <body>
                ${printArea.innerHTML}
            </body>
            </html>
        `);
        doc.close();

        setTimeout(() => {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        }, 250);
    };

    window.printReturnVoucherBluetooth = async function() {
        if (!window.BluetoothPrinter) {
            alert('Bluetooth printing not supported on this browser.');
            return;
        }
        if (!currentReturnResult) {
            alert('No active return data to print.');
            return;
        }

        // Auto-reconnect or prompt
        if (!BluetoothPrinter.isConnected()) {
            const ok = await BluetoothPrinter.connectOrPrompt();
            if (!ok) {
                alert('Could not connect to Bluetooth printer.');
                return;
            }
        }

        try {
            await BluetoothPrinter.printReturn(currentReturnResult, typeof globalStoreSettings !== 'undefined' ? globalStoreSettings : {}, 'returnPrintArea');
            alert('Return voucher sent to Bluetooth thermal printer!');
        } catch (e) {
            alert('Bluetooth print failed: ' + e.message);
        }
    };

    window.printReturnVoucherThermal = async function() {
        if (!currentReturnResult) return;

        // Prioritize Bluetooth Thermal print
        if (window.BluetoothPrinter && BluetoothPrinter.isSupported()) {
            const connected = await BluetoothPrinter.ensureConnected();
            if (connected) {
                await printReturnVoucherBluetooth();
                return;
            }

            if (BluetoothPrinter.hasPairedPrinter()) {
                const ok = await BluetoothPrinter.connectOrPrompt();
                if (ok) {
                    await printReturnVoucherBluetooth();
                    return;
                }
            }
        }

        // Fallback to browser print page
        printReturnVoucherIframe();
    };

    // Print past return by Return ID
    window.printReturnById = async function(returnId) {
        try {
            const res = await fetch(`api.php?action=get_return_details&id=${returnId}`);
            const data = await res.json();
            if (!data.success) {
                alert(data.error || 'Failed to fetch return details.');
                return;
            }
            currentReturnResult = data.data;
            renderReturnVoucherSlip(data.data);
            document.getElementById('returnReceiptModal').classList.add('active');
        } catch (e) {
            alert('Error loading return voucher: ' + e.message);
        }
    };

})();
</script>
