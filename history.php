<?php
require_once __DIR__ . '/includes/header.php';
?>

<main class="main-wrapper">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.6rem; font-weight: 800; color: #0f172a;">Bill History & Invoices</h1>
            <p style="color: #64748b; font-size: 0.9rem;">Browse past transactions, process sales returns, reprint receipts and export invoices to PDF.</p>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <button type="button" class="btn btn-secondary" onclick="openReturnModal()" style="background: #fff1f2; border-color: #fecdd3; color: #be123c; font-weight: 700; display: inline-flex; align-items: center; gap: 0.35rem;">
                ↩️ Process Return
            </button>
            <a href="index.php" class="btn btn-primary">
                + Create New Bill
            </a>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="data-card" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem;">
        <div class="history-filter-row">
            <div class="form-group">
                <label class="form-label">Search Invoices</label>
                <input type="text" id="historySearch" class="form-control" placeholder="Search by Bill #, Customer Name, Phone..." oninput="loadBills()">
            </div>
            <div class="form-group">
                <label class="form-label">From Date</label>
                <input type="date" id="historyFromDate" class="form-control" onchange="loadBills()">
            </div>
            <div class="form-group">
                <label class="form-label">To Date</label>
                <input type="date" id="historyToDate" class="form-control" onchange="loadBills()">
            </div>
            <div>
                <button type="button" class="btn btn-secondary" onclick="resetHistoryFilters()">Reset</button>
            </div>
        </div>
    </div>

    <!-- Tabs: Invoices vs Sales Returns -->
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1.25rem; flex-wrap: wrap;">
        <button type="button" id="tabInvoicesBtn" class="btn" onclick="switchHistoryTab('invoices')" style="background: #2563eb; color: #fff; font-weight: 700; padding: 0.5rem 1rem;">
            📄 Sales Invoices (<span id="historyCount">0</span>)
        </button>
        <button type="button" id="tabReturnsBtn" class="btn btn-secondary" onclick="switchHistoryTab('returns')" style="background: #f1f5f9; color: #475569; font-weight: 700; padding: 0.5rem 1rem;">
            ↩️ Return Vouchers (<span id="returnsCountBadge">0</span>)
        </button>
    </div>

    <!-- Bills Table Card -->
    <div class="data-card" id="invoicesCard">
        <div class="data-card-header">
            <h3 style="font-size: 1.1rem; font-weight: 700;">Transaction Invoices</h3>
            <span id="historyCountHeader" style="font-size: 0.85rem; font-weight: 600; color: #64748b;">0 Invoices</span>
        </div>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Bill Number</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Pay Mode</th>
                        <th>Subtotal</th>
                        <th>Discount</th>
                        <th>Tax</th>
                        <th>Grand Total</th>
                        <th style="color: #10b981;">Net Profit</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody">
                    <tr><td colspan="11" style="text-align: center; padding: 2rem; color: #94a3b8;">Loading bill history...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Return Vouchers Table Card (Shown when returns tab is active) -->
    <div class="data-card" id="returnsCard" style="display: none;">
        <div class="data-card-header" style="border-bottom: 2px solid #fee2e2;">
            <div>
                <h3 style="font-size: 1.1rem; font-weight: 700; color: #b91c1c;">Customer Return & Refund Vouchers</h3>
                <span style="font-size: 0.78rem; color: #64748b;">List of all processed product returns with restock and refund tracking</span>
            </div>
            <span id="returnsCountHeader" style="font-size: 0.85rem; font-weight: 700; color: #dc2626;">0 Returns</span>
        </div>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Return Number</th>
                        <th>Original Bill #</th>
                        <th>Customer</th>
                        <th style="text-align: center;">Items Count</th>
                        <th>Refund Mode</th>
                        <th>Reason</th>
                        <th style="text-align: right; color: #dc2626;">Total Refund</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="returnsTableBody">
                    <tr><td colspan="9" style="text-align: center; padding: 2rem; color: #94a3b8;">Loading returns history...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Modal: Bill Receipt Preview & Actions -->
<div id="billDetailModal" class="modal-overlay">
    <div class="modal-card" style="max-width: 500px;">
        <div class="modal-header">
            <h3 class="modal-title">Invoice Details</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('billDetailModal')">✕</button>
        </div>
        <div class="modal-body" style="padding: 1rem; max-height: 70vh; overflow-y: auto;">
            <div id="receiptContainer">
                <!-- Receipt is rendered here -->
            </div>
        </div>
        <div class="modal-footer" style="justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <button type="button" class="btn btn-primary" onclick="printThermalInvoice()" style="background: #2563eb; color: #fff;">
                    🖨️ Thermal Print (58mm)
                </button>
                <button type="button" class="btn btn-secondary" onclick="reprintCurrentBill(true)" title="Print second / duplicate copy">
                    🔄 Print Again (Duplicate)
                </button>
                <button type="button" class="btn btn-secondary" onclick="printCurrentBillBluetooth()">
                    📶 Bluetooth Print
                </button>
                <button type="button" class="btn btn-secondary" onclick="downloadCurrentBillPDF()">
                    📄 PDF
                </button>
                <button type="button" class="btn btn-secondary" onclick="openReturnModalForBill(currentBillObj.id)" style="background: #fff1f2; border-color: #fecdd3; color: #be123c; font-weight: 700;">
                    ↩️ Return Items
                </button>
            </div>
            <button type="button" class="btn btn-secondary" onclick="closeModal('billDetailModal')">
                Close
            </button>
        </div>
    </div>
</div>

<div id="toastContainer" class="toast-container"></div>

<script>
    let billsList = [];
    let returnsList = [];
    let activeHistoryTab = 'invoices';

    function showToast(msg, type = 'success') {
        const c = document.getElementById('toastContainer');
        const t = document.createElement('div');
        t.className = `toast ${type}`;
        t.innerHTML = `<span>${type === 'success' ? '✓' : '⚠'}</span><div>${msg}</div>`;
        c.appendChild(t);
        setTimeout(() => { t.remove(); }, 3000);
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    function switchHistoryTab(tabKey) {
        activeHistoryTab = tabKey;
        const invBtn = document.getElementById('tabInvoicesBtn');
        const retBtn = document.getElementById('tabReturnsBtn');
        const invCard = document.getElementById('invoicesCard');
        const retCard = document.getElementById('returnsCard');

        if (tabKey === 'invoices') {
            invBtn.style.background = '#2563eb';
            invBtn.style.color = '#fff';
            retBtn.style.background = '#f1f5f9';
            retBtn.style.color = '#475569';
            invCard.style.display = 'block';
            retCard.style.display = 'none';
            loadBills();
        } else {
            retBtn.style.background = '#dc2626';
            retBtn.style.color = '#fff';
            invBtn.style.background = '#f1f5f9';
            invBtn.style.color = '#475569';
            retCard.style.display = 'block';
            invCard.style.display = 'none';
            loadReturns();
        }
    }

    async function loadBills() {
        const search = document.getElementById('historySearch').value.trim();
        const fromDate = document.getElementById('historyFromDate').value;
        const toDate = document.getElementById('historyToDate').value;

        let url = 'api.php?action=get_bills';
        if (search) url += `&search=${encodeURIComponent(search)}`;
        if (fromDate) url += `&from_date=${fromDate}`;
        if (toDate) url += `&to_date=${toDate}`;

        try {
            const res = await fetch(url);
            const data = await res.json();
            if (data.success) {
                billsList = data.data;
                renderBillsTable();
            }
        } catch (e) {
            console.error('Failed to load bills:', e);
        }
    }

    function renderBillsTable() {
        const tbody = document.getElementById('historyTableBody');
        const count = document.getElementById('historyCount');
        const headerCount = document.getElementById('historyCountHeader');
        if (count) count.innerText = billsList.length;
        if (headerCount) headerCount.innerText = `${billsList.length} Invoices`;

        if (billsList.length === 0) {
            tbody.innerHTML = `<tr><td colspan="11" style="text-align: center; padding: 2rem; color: #94a3b8;">No invoices found</td></tr>`;
            return;
        }

        let html = '';
        billsList.forEach(b => {
            const profit = parseFloat(b.net_profit || 0);
            const totalRefunded = parseFloat(b.total_refunded || 0);
            let returnTag = '';
            if (totalRefunded > 0) {
                if (totalRefunded >= parseFloat(b.grand_total)) {
                    returnTag = `<br><span style="display:inline-block; font-size:0.7rem; font-weight:800; background:#fee2e2; color:#991b1b; padding:1px 5px; border-radius:4px; margin-top:2px;">↩️ FULL RETURN (-₹${totalRefunded.toFixed(2)})</span>`;
                } else {
                    returnTag = `<br><span style="display:inline-block; font-size:0.7rem; font-weight:800; background:#fff1f2; color:#be123c; padding:1px 5px; border-radius:4px; margin-top:2px;">↩️ RETURNED (-₹${totalRefunded.toFixed(2)})</span>`;
                }
            }

            html += `
                <tr>
                    <td><small>${b.created_at}</small></td>
                    <td>
                        <code><strong>${b.bill_number}</strong></code>
                        ${returnTag}
                    </td>
                    <td>
                        <strong>${b.customer_name}</strong>
                        ${b.customer_phone ? `<br><small style="color:#64748b;">${b.customer_phone}</small>` : ''}
                    </td>
                    <td><span class="stock-tag in">${b.item_count} items</span></td>
                    <td><span style="font-weight: 600;">${b.payment_method}</span></td>
                    <td>₹${parseFloat(b.subtotal).toFixed(2)}</td>
                    <td>${parseFloat(b.discount) > 0 ? `<span style="color:#10b981;">-₹${parseFloat(b.discount).toFixed(2)}</span>` : '₹0.00'}</td>
                    <td>₹${parseFloat(b.tax).toFixed(2)}</td>
                    <td><strong style="color: #2563eb; font-size: 1.05rem;">₹${parseFloat(b.grand_total).toFixed(2)}</strong></td>
                    <td><strong style="color: ${profit >= 0 ? '#10b981' : '#ef4444'}; font-size: 0.95rem;">${profit >= 0 ? '+' : ''}₹${profit.toFixed(2)}</strong></td>
                    <td>
                        <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                            <button type="button" class="btn btn-primary" style="padding: 0.25rem 0.55rem; font-size: 0.78rem; background: #2563eb;" onclick="reprintBillDirect(${b.id})" title="Instant 58mm Thermal Reprint">
                                🖨️ Reprint
                            </button>
                            <button type="button" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.78rem;" onclick="viewBillDetails(${b.id})" title="View Details & Options">
                                👁️ View
                            </button>
                            <button type="button" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.78rem; background: #fff1f2; border-color: #fecdd3; color: #be123c; font-weight: 700;" onclick="openReturnModalForBill(${b.id})" title="Return / Refund Items from this Bill">
                                ↩️ Return
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    async function loadReturns() {
        const search = document.getElementById('historySearch').value.trim();
        const fromDate = document.getElementById('historyFromDate').value;
        const toDate = document.getElementById('historyToDate').value;

        let url = 'api.php?action=get_returns';
        if (search) url += `&search=${encodeURIComponent(search)}`;
        if (fromDate) url += `&from_date=${fromDate}`;
        if (toDate) url += `&to_date=${toDate}`;

        try {
            const res = await fetch(url);
            const data = await res.json();
            if (data.success) {
                returnsList = data.data;
                renderReturnsTable();
            }
        } catch (e) {
            console.error('Failed to load returns:', e);
        }
    }

    function renderReturnsTable() {
        const tbody = document.getElementById('returnsTableBody');
        const badge = document.getElementById('returnsCountBadge');
        const header = document.getElementById('returnsCountHeader');
        if (badge) badge.innerText = returnsList.length;
        if (header) header.innerText = `${returnsList.length} Returns`;

        if (returnsList.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" style="text-align: center; padding: 2rem; color: #94a3b8;">No product returns found for the selected period.</td></tr>`;
            return;
        }

        let html = '';
        returnsList.forEach(r => {
            html += `
                <tr>
                    <td><small>${r.created_at}</small></td>
                    <td><code style="color: #b91c1c;"><strong>${r.return_number}</strong></code></td>
                    <td>
                        ${r.bill_id ? `
                            <a href="javascript:void(0)" onclick="viewBillDetails(${r.bill_id})" style="font-weight: 700; color: #2563eb; text-decoration: none;" title="Click to view original bill">
                                ${r.bill_number}
                            </a>
                        ` : `<span style="font-weight: 600; color: #64748b; font-size: 0.82rem;">Direct Return (No Bill)</span>`}
                    </td>
                    <td>
                        <strong>${r.customer_name}</strong>
                        ${r.customer_phone ? `<br><small style="color:#64748b;">${r.customer_phone}</small>` : ''}
                    </td>
                    <td style="text-align: center;"><span class="stock-tag out">${r.item_count} item(s)</span></td>
                    <td><span style="font-weight: 600;">${r.refund_method}</span></td>
                    <td><span style="font-size: 0.85rem; color: #475569;">${r.return_reason || '-'}</span></td>
                    <td style="text-align: right;"><strong style="color: #dc2626; font-size: 1.05rem;">-₹${parseFloat(r.total_refund).toFixed(2)}</strong></td>
                    <td>
                        <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                            <button type="button" class="btn btn-primary" style="padding: 0.25rem 0.55rem; font-size: 0.78rem; background: #dc2626; border-color: #b91c1c;" onclick="printReturnById(${r.id})" title="Print Sales Return 58mm Voucher">
                                🖨️ Print Slip
                            </button>
                            ${r.bill_id ? `
                                <button type="button" class="btn btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.78rem;" onclick="viewBillDetails(${r.bill_id})" title="View Original Bill">
                                    📄 Orig. Bill
                                </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    function resetHistoryFilters() {
        document.getElementById('historySearch').value = '';
        document.getElementById('historyFromDate').value = '';
        document.getElementById('historyToDate').value = '';
        if (activeHistoryTab === 'returns') {
            loadReturns();
        } else {
            loadBills();
        }
    }

    let currentBillObj = null;

    async function viewBillDetails(billId) {
        try {
            const res = await fetch(`api.php?action=get_bill_details&id=${billId}`);
            const data = await res.json();
            if (data.success) {
                currentBillObj = data.data;
                renderInvoice(data.data);
                document.getElementById('billDetailModal').classList.add('active');
            } else {
                showToast(data.error || 'Failed to fetch details', 'error');
            }
        } catch (e) {
            showToast('Error loading bill', 'error');
        }
    }

    async function printCurrentBillBluetooth() {
        if (!window.BluetoothPrinter) {
            showToast('Bluetooth printing not supported on this browser', 'error');
            return;
        }
        if (!currentBillObj) return;

        if (!BluetoothPrinter.isConnected()) {
            if (confirm('Bluetooth 58mm printer is not connected. Connect now?')) {
                try {
                    await BluetoothPrinter.connect();
                } catch (e) {
                    return;
                }
            } else {
                return;
            }
        }

        try {
            showToast('Printing via Bluetooth 58mm...');
            await BluetoothPrinter.printBill(currentBillObj, globalStoreSettings);
            showToast('Printed successfully!');
        } catch (e) {
            showToast('Print failed: ' + e.message, 'error');
        }
    }

    function printThermalInvoice(isDuplicate = false) {
        if (!currentBillObj) return;
        if (isDuplicate) {
            currentBillObj.is_reprint = true;
            renderInvoice(currentBillObj);
        }
        if (window.BluetoothPrinter && BluetoothPrinter.isConnected()) {
            printCurrentBillBluetooth();
            return;
        }
        printViaThermalIframe('receiptContainer');
    }

    function reprintCurrentBill(isDuplicate = true) {
        if (!currentBillObj) return;
        printThermalInvoice(isDuplicate);
        showToast(isDuplicate ? 'Printing duplicate copy...' : 'Reprinting bill...');
    }

    async function reprintBillDirect(billId) {
        await viewBillDetails(billId);
        printThermalInvoice(true);
    }

    function printViaThermalIframe(elementId = 'receiptContainer') {
        const printArea = document.getElementById(elementId);
        if (!printArea) {
            window.print();
            return;
        }

        let iframe = document.getElementById('historyThermalIframe');
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = 'historyThermalIframe';
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
                <title>58mm Thermal Receipt</title>
                <style>
                    @page {
                        size: 58mm auto;
                        margin: 0;
                    }
                    * {
                        box-sizing: border-box;
                        margin: 0;
                        padding: 0;
                    }
                    body {
                        font-family: 'Courier New', Courier, monospace;
                        font-size: 11px;
                        line-height: 1.25;
                        width: 58mm;
                        max-width: 58mm;
                        padding: 3mm 2mm;
                        color: #000;
                        background: #fff;
                    }
                    .receipt-title {
                        font-size: 14px;
                        font-weight: 900;
                        text-align: center;
                        text-transform: uppercase;
                        margin-bottom: 2px;
                    }
                    .receipt-header {
                        text-align: center;
                        margin-bottom: 4px;
                    }
                    .receipt-divider {
                        border-bottom: 1px dashed #000;
                        margin: 4px 0;
                    }
                    .receipt-table {
                        width: 100%;
                        border-collapse: collapse;
                        margin: 4px 0;
                    }
                    .receipt-table th {
                        border-bottom: 1px solid #000;
                        font-weight: bold;
                        padding: 2px 0;
                        text-align: left;
                        font-size: 10px;
                    }
                    .receipt-table td {
                        padding: 2px 0;
                        font-size: 10px;
                        vertical-align: top;
                    }
                    .text-right {
                        text-align: right;
                    }
                    .reprint-badge {
                        text-align: center;
                        font-weight: bold;
                        font-size: 11px;
                        border: 1px dashed #000;
                        padding: 2px;
                        margin: 4px 0;
                        background: #f1f5f9;
                    }
                    small {
                        font-size: 9px;
                    }
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
        }, 200);
    }

    function renderInvoice(bill) {
        const c = document.getElementById('receiptContainer');
        const store = bill.store || {};
        const storeName = store.store_name || 'BillKartX Smart Billing';
        const storeAddr = store.store_address || '123 Main Bazaar Road, Anna Nagar, Chennai';
        const storePhone = store.store_phone || '+91 98765 43210';
        const isGstActive = (store.enable_gst !== '0') && (parseFloat(bill.tax) > 0 || store.enable_gst === '1');
        const storeGst = isGstActive ? (store.store_gst || '') : '';
        let headerTagline = store.receipt_header || 'Tax Invoice / Retail Bill';
        if (!isGstActive && headerTagline === 'Tax Invoice / Retail Bill') {
            headerTagline = 'Retail Bill';
        }
        const footerMsg = store.receipt_footer || '*** Thank You! Visit Again ***';

        let itemsHtml = '';
        (bill.items || []).forEach((it, idx) => {
            const unit = it.selected_unit || '';
            const qtyText = `${it.entered_qty}${unit ? ' ' + unit : ''}`;
            itemsHtml += `
                <tr>
                    <td style="text-align: left; vertical-align: top;">
                        ${idx + 1}. ${it.product_name}
                    </td>
                    <td class="text-center" style="text-align: center; vertical-align: top; white-space: nowrap;">${qtyText}</td>
                    <td class="text-right" style="text-align: right; vertical-align: top; white-space: nowrap; font-weight: 600;">₹${parseFloat(it.item_total).toFixed(2)}</td>
                </tr>
            `;
        });

        c.innerHTML = `
            <div id="receiptPrintArea" class="receipt-printable">
                <div class="receipt-header">
                    <div style="text-align: center; margin-bottom: 6px;">
                        <img src="assets/BillKartX Smart Billing Logo.png" alt="BillKartX" style="max-height: 38px; max-width: 170px; object-fit: contain;">
                    </div>
                    <div class="receipt-title">${storeName}</div>
                    <div style="font-size: 0.8rem;">${storeAddr}</div>
                    <div style="font-size: 0.8rem;">Phone: ${storePhone}</div>
                    ${storeGst ? `<div style="font-size: 0.8rem;">GST: ${storeGst}</div>` : ''}
                    <div style="font-weight: 700; font-size: 0.85rem; margin-bottom: 4px;">${headerTagline}</div>
                    ${bill.is_reprint ? `<div class="reprint-badge" style="text-align: center; font-weight: 800; font-size: 0.82rem; border: 1px dashed #000; padding: 2px 4px; margin: 4px 0; background: #fef2f2; color: #b91c1c;">*** DUPLICATE / REPRINT ***</div>` : ''}
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                        <span><strong>Bill:</strong> ${bill.bill_number}</span>
                        <span>${bill.created_at}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                        <span><strong>Customer:</strong> ${bill.customer_name}</span>
                        <span>${bill.customer_phone || ''}</span>
                    </div>
                </div>

                <div class="receipt-divider"></div>

                <table class="receipt-table">
                    <thead>
                        <tr>
                            <th style="text-align: left;">Product Name</th>
                            <th class="text-center" style="width: 24%; text-align: center;">Qty</th>
                            <th class="text-right" style="width: 26%; text-align: right;">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itemsHtml}
                    </tbody>
                </table>

                <div class="receipt-divider"></div>

                <div style="font-size: 0.85rem; display: flex; flex-direction: column; gap: 3px;">
                    <div style="display: flex; justify-content: space-between;">
                        <span>Subtotal:</span>
                        <span>₹${parseFloat(bill.subtotal).toFixed(2)}</span>
                    </div>
                    ${parseFloat(bill.discount) > 0 ? `
                        <div style="display: flex; justify-content: space-between; color: #16a34a;">
                            <span>Discount:</span>
                            <span>-₹${parseFloat(bill.discount).toFixed(2)}</span>
                        </div>
                    ` : ''}
                    ${parseFloat(bill.tax) > 0 ? `
                        <div style="display: flex; justify-content: space-between;">
                            <span>Tax (GST):</span>
                            <span>+₹${parseFloat(bill.tax).toFixed(2)}</span>
                        </div>
                    ` : ''}
                    <div class="receipt-divider"></div>
                    <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.15rem;">
                        <span>Grand Total:</span>
                        <span>₹${parseFloat(bill.grand_total).toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-top: 4px;">
                        <span>Payment Mode:</span>
                        <span><strong>${bill.payment_method}</strong></span>
                    </div>
                </div>

                <div class="receipt-divider"></div>
                <div style="text-align: center; font-size: 0.8rem; margin-top: 10px;">
                    ${footerMsg}<br>
                    <small>Official Sales Receipt</small>
                </div>
            </div>
        `;
    }

    async function downloadCurrentBillPDF() {
        const element = document.getElementById('receiptPrintArea');
        if (!element) return;

        try {
            showToast('Generating invoice PDF...');
            if (typeof html2canvas === 'undefined' || typeof jspdf === 'undefined') {
                window.print();
                return;
            }

            const canvas = await html2canvas(element, { scale: 2 });
            const imgData = canvas.toDataURL('image/png');

            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'mm', [80, Math.max(150, (canvas.height * 80) / canvas.width)]);
            const imgWidth = 80;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;

            pdf.addImage(imgData, 'PNG', 0, 0, imgWidth, imgHeight);
            pdf.save(`Invoice_${Date.now()}.pdf`);
            showToast('PDF downloaded successfully!');
        } catch (e) {
            console.error('PDF error:', e);
            window.print();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadBills();
        loadReturns();
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

