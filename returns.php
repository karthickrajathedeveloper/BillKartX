<?php
/**
 * BillKartX - Returned Products & Customer Refunds Management Page
 * Dedicated page to view, audit, filter, and reprint all customer returned products,
 * track restocked inventory vs damaged scrap, and monitor refunded cash/UPI amounts.
 */
require_once __DIR__ . '/includes/header.php';
?>

<main class="main-wrapper">
    <!-- Header with Action Buttons -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                <span style="font-size: 1.4rem; background: #fee2e2; color: #b91c1c; padding: 0.2rem 0.5rem; border-radius: 8px;">↩️</span>
                <h1 style="font-size: 1.6rem; font-weight: 800; color: #0f172a; margin: 0;">Returned Products & Refunds</h1>
            </div>
            <p style="color: #64748b; font-size: 0.88rem; margin: 0;">
                Complete record of all returned products, restocked inventory balances, and refund amounts deducted from sales profit.
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <button type="button" class="btn btn-secondary" onclick="loadReturnedProducts()" title="Refresh list">
                ↻ Refresh
            </button>
            <button type="button" class="btn btn-primary" onclick="openReturnModal()" style="background: #dc2626; border-color: #b91c1c; font-weight: 700; display: inline-flex; align-items: center; gap: 0.4rem;">
                <span>↩️</span> Process New Return
            </button>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="stats-grid" style="margin-bottom: 1.25rem;">
        <!-- Card 1: Total Refund Paid -->
        <div class="stat-card" style="border-top: 4px solid #ef4444; background: linear-gradient(180deg, #ffffff 0%, #fef2f2 100%);">
            <div class="stat-header">
                <span class="stat-label" style="color: #991b1b; font-weight: 700;">Total Refunded Amount</span>
                <span style="font-size: 1.25rem;">💸</span>
            </div>
            <div class="stat-value" id="kpiTotalRefund" style="color: #dc2626; font-size: 1.8rem; font-weight: 900;">₹0.00</div>
            <div class="stat-sub" style="color: #b91c1c;">Deducted from gross sales profit</div>
        </div>

        <!-- Card 2: Total Items Returned -->
        <div class="stat-card" style="border-top: 4px solid #2563eb;">
            <div class="stat-header">
                <span class="stat-label">Total Returned Items</span>
                <span style="font-size: 1.25rem;">📦</span>
            </div>
            <div class="stat-value" id="kpiTotalItems" style="color: #2563eb; font-size: 1.8rem; font-weight: 900;">0</div>
            <div class="stat-sub"><span id="kpiTotalUnits">0</span> total units returned by customers</div>
        </div>

        <!-- Card 3: Restocked to Inventory -->
        <div class="stat-card" style="border-top: 4px solid #10b981; background: linear-gradient(180deg, #ffffff 0%, #f0fdf4 100%);">
            <div class="stat-header">
                <span class="stat-label" style="color: #065f46; font-weight: 700;">Restocked to Stock</span>
                <span style="font-size: 1.25rem;">✅</span>
            </div>
            <div class="stat-value" id="kpiRestockedVal" style="color: #059669; font-size: 1.8rem; font-weight: 900;">₹0.00</div>
            <div class="stat-sub" style="color: #047857;"><span id="kpiRestockedCount">0</span> items added back to inventory</div>
        </div>

        <!-- Card 4: Damaged / Discarded Loss -->
        <div class="stat-card" style="border-top: 4px solid #f59e0b;">
            <div class="stat-header">
                <span class="stat-label">Damaged / Scrap Loss</span>
                <span style="font-size: 1.25rem;">⚠️</span>
            </div>
            <div class="stat-value" id="kpiDamagedLoss" style="color: #d97706; font-size: 1.8rem; font-weight: 900;">₹0.00</div>
            <div class="stat-sub"><span id="kpiDamagedCount">0</span> defective items not restocked</div>
        </div>
    </div>

    <!-- Search & Date Filter Bar -->
    <div class="data-card" style="margin-bottom: 1.25rem; padding: 1rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
            
            <!-- Quick Period Filter Buttons -->
            <div style="display: inline-flex; background: #f1f5f9; padding: 3px; border-radius: 8px; border: 1px solid #cbd5e1; gap: 2px;">
                <button type="button" class="btn btn-secondary ret-period-btn active" id="btnPeriodAll" onclick="setReturnPeriod('all')" style="padding: 0.35rem 0.75rem; font-size: 0.82rem; font-weight: 700; background: #dc2626; color: #fff; border: none; border-radius: 6px;">
                    All Time
                </button>
                <button type="button" class="btn btn-secondary ret-period-btn" id="btnPeriodToday" onclick="setReturnPeriod('today')" style="padding: 0.35rem 0.75rem; font-size: 0.82rem; font-weight: 600; border: none; border-radius: 6px; background: transparent; color: #475569;">
                    Today
                </button>
                <button type="button" class="btn btn-secondary ret-period-btn" id="btnPeriodWeek" onclick="setReturnPeriod('week')" style="padding: 0.35rem 0.75rem; font-size: 0.82rem; font-weight: 600; border: none; border-radius: 6px; background: transparent; color: #475569;">
                    This Week
                </button>
                <button type="button" class="btn btn-secondary ret-period-btn" id="btnPeriodMonth" onclick="setReturnPeriod('month')" style="padding: 0.35rem 0.75rem; font-size: 0.82rem; font-weight: 600; border: none; border-radius: 6px; background: transparent; color: #475569;">
                    This Month
                </button>
            </div>

            <!-- Search, Restock Filter, and Custom Date Range -->
            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; flex: 1; justify-content: flex-end;">
                <!-- Product/Voucher Search -->
                <div style="min-width: 200px; flex: 1; max-width: 280px;">
                    <input type="text" id="retFilterSearch" class="form-control" placeholder="Search product, barcode, bill #..." oninput="debounceReturnSearch()" style="padding: 0.4rem 0.7rem; font-size: 0.85rem;">
                </div>

                <!-- Restock Filter Dropdown -->
                <select id="retFilterRestock" class="form-control" onchange="loadReturnedProducts()" style="width: auto; padding: 0.4rem 0.6rem; font-size: 0.85rem;">
                    <option value="">All Restock Status</option>
                    <option value="1">Restocked to Inventory</option>
                    <option value="0">Damaged / Not Restocked</option>
                </select>

                <!-- Date Pickers -->
                <div style="display: flex; align-items: center; gap: 0.35rem;">
                    <input type="date" id="retFilterFromDate" class="form-control" style="width: auto; padding: 0.35rem 0.55rem; font-size: 0.82rem;">
                    <span style="font-size: 0.8rem; color: #64748b;">to</span>
                    <input type="date" id="retFilterToDate" class="form-control" style="width: auto; padding: 0.35rem 0.55rem; font-size: 0.82rem;">
                    <button type="button" class="btn btn-primary" onclick="loadReturnedProducts()" style="padding: 0.35rem 0.7rem; font-size: 0.82rem; background: #dc2626; border-color: #b91c1c;">
                        Filter
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="resetReturnFilters()" style="padding: 0.35rem 0.55rem; font-size: 0.82rem;">
                        Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Returned Products Master Table Card -->
    <div class="data-card">
        <div class="data-card-header" style="border-bottom: 2px solid #fee2e2;">
            <div>
                <h3 style="font-size: 1.1rem; font-weight: 700; color: #b91c1c; margin: 0 0 2px 0;">
                    Returned Products Master Log
                </h3>
                <span style="font-size: 0.78rem; color: #64748b;">
                    Detailed breakdown of every returned item, quantity, unit rate, and customer refund mode
                </span>
            </div>
            <span id="retTableCountBadge" style="font-size: 0.85rem; font-weight: 700; color: #dc2626; background: #fef2f2; padding: 3px 10px; border-radius: 999px; border: 1px solid #fecdd3;">
                0 Returned Items
            </span>
        </div>

        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Return Slip #</th>
                        <th>Original Bill #</th>
                        <th>Returned Product</th>
                        <th style="text-align: center;">Returned Qty</th>
                        <th style="text-align: right;">Unit Rate</th>
                        <th style="text-align: right; color: #dc2626;">Total Refund</th>
                        <th style="text-align: center;">Restock Status</th>
                        <th>Return Reason</th>
                        <th>Customer</th>
                        <th>Payment Mode</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody id="returnedProductsTableBody">
                    <tr>
                        <td colspan="12" style="text-align: center; padding: 2.5rem; color: #94a3b8;">
                            Loading returned products list...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Modal: Original Bill Receipt Preview -->
<div id="billDetailModal" class="modal-overlay">
    <div class="modal-card" style="max-width: 500px;">
        <div class="modal-header">
            <h3 class="modal-title">Original Invoice Details</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('billDetailModal')">✕</button>
        </div>
        <div class="modal-body" style="padding: 1rem; max-height: 70vh; overflow-y: auto;">
            <div id="receiptContainer"></div>
        </div>
        <div class="modal-footer" style="justify-content: space-between;">
            <button type="button" class="btn btn-secondary" onclick="closeModal('billDetailModal')">
                Close
            </button>
        </div>
    </div>
</div>

<div id="toastContainer" class="toast-container"></div>

<script>
    let activeReturnPeriod = 'all';
    let searchTimeout = null;
    let returnedItemsList = [];

    function showToast(msg, type = 'success') {
        const c = document.getElementById('toastContainer');
        const t = document.createElement('div');
        t.className = `toast ${type}`;
        t.innerHTML = `<span>${type === 'success' ? '✓' : '⚠'}</span><div>${msg}</div>`;
        c.appendChild(t);
        setTimeout(() => { t.remove(); }, 3000);
    }

    function closeModal(id) {
        const el = document.getElementById(id);
        if (el) el.classList.remove('active');
    }

    function setReturnPeriod(period) {
        activeReturnPeriod = period;
        const btns = {
            all: document.getElementById('btnPeriodAll'),
            today: document.getElementById('btnPeriodToday'),
            week: document.getElementById('btnPeriodWeek'),
            month: document.getElementById('btnPeriodMonth')
        };

        Object.keys(btns).forEach(key => {
            const btn = btns[key];
            if (!btn) return;
            if (key === period) {
                btn.style.background = '#dc2626';
                btn.style.color = '#fff';
                btn.style.fontWeight = '700';
            } else {
                btn.style.background = 'transparent';
                btn.style.color = '#475569';
                btn.style.fontWeight = '600';
            }
        });

        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        const todayStr = `${yyyy}-${mm}-${dd}`;

        const fromInput = document.getElementById('retFilterFromDate');
        const toInput = document.getElementById('retFilterToDate');

        if (period === 'all') {
            fromInput.value = '';
            toInput.value = '';
        } else if (period === 'today') {
            fromInput.value = todayStr;
            toInput.value = todayStr;
        } else if (period === 'week') {
            const weekAgo = new Date(today);
            weekAgo.setDate(today.getDate() - 6);
            const wY = weekAgo.getFullYear();
            const wM = String(weekAgo.getMonth() + 1).padStart(2, '0');
            const wD = String(weekAgo.getDate()).padStart(2, '0');
            fromInput.value = `${wY}-${wM}-${wD}`;
            toInput.value = todayStr;
        } else if (period === 'month') {
            fromInput.value = `${yyyy}-${mm}-01`;
            toInput.value = todayStr;
        }

        loadReturnedProducts();
    }

    function debounceReturnSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadReturnedProducts();
        }, 300);
    }

    function resetReturnFilters() {
        document.getElementById('retFilterSearch').value = '';
        document.getElementById('retFilterRestock').value = '';
        setReturnPeriod('all');
    }

    async function loadReturnedProducts() {
        const search = document.getElementById('retFilterSearch').value.trim();
        const restock = document.getElementById('retFilterRestock').value;
        const fromDate = document.getElementById('retFilterFromDate').value;
        const toDate = document.getElementById('retFilterToDate').value;

        let url = 'api.php?action=get_returned_products';
        if (search) url += `&search=${encodeURIComponent(search)}`;
        if (restock !== '') url += `&restock=${encodeURIComponent(restock)}`;
        if (fromDate) url += `&from_date=${encodeURIComponent(fromDate)}`;
        if (toDate) url += `&to_date=${encodeURIComponent(toDate)}`;

        try {
            const res = await fetch(url);
            const data = await res.json();
            if (data.success) {
                returnedItemsList = data.data || [];
                renderReturnedProductsUI(data);
            } else {
                showToast(data.error || 'Failed to fetch returned products', 'error');
            }
        } catch (e) {
            console.error('Failed to load returned products:', e);
            showToast('Network error while loading returns', 'error');
        }
    }

    function renderReturnedProductsUI(data) {
        const items = data.data || [];
        const sum = data.summary || {};

        // Update KPI Cards
        document.getElementById('kpiTotalRefund').innerText = `₹${parseFloat(sum.total_refund_amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('kpiTotalItems').innerText = sum.total_items_count || 0;
        document.getElementById('kpiTotalUnits').innerText = parseFloat(sum.total_units_returned || 0).toFixed(1);
        document.getElementById('kpiRestockedVal').innerText = `₹${parseFloat(sum.restocked_value || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('kpiRestockedCount').innerText = sum.restocked_items_count || 0;
        document.getElementById('kpiDamagedLoss').innerText = `₹${parseFloat(sum.damaged_loss || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('kpiDamagedCount').innerText = sum.damaged_items_count || 0;

        document.getElementById('retTableCountBadge').innerText = `${items.length} Returned Items`;

        const tbody = document.getElementById('returnedProductsTableBody');
        if (items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="12" style="text-align: center; padding: 2.5rem; color: #94a3b8;">No returned products found for the selected filter.</td></tr>`;
            return;
        }

        let html = '';
        items.forEach(it => {
            const isRestocked = parseInt(it.restock, 10) === 1;
            const unit = it.selected_unit || '';
            const isDirect = !it.bill_id || it.bill_number === 'DIRECT-RETURN';

            html += `
                <tr>
                    <td><small style="color: #475569;">${it.created_at || it.voucher_date}</small></td>
                    <td><code style="color: #b91c1c; font-weight: 700;">${it.return_number}</code></td>
                    <td>
                        ${isDirect ? `
                            <span style="display: inline-block; font-size: 0.74rem; font-weight: 700; background: #fef3c7; color: #92400e; padding: 2px 6px; border-radius: 4px;">
                                📦 Direct Return (No Bill)
                            </span>
                        ` : `
                            <a href="javascript:void(0)" onclick="viewOriginalBill(${it.bill_id})" style="font-weight: 700; color: #2563eb; text-decoration: none;" title="Click to view original invoice">
                                ${it.bill_number}
                            </a>
                        `}
                    </td>
                    <td>
                        <strong style="color: #0f172a; font-size: 0.95rem;">${it.product_name}</strong>
                        ${it.category_name ? `<br><small style="color: #64748b;">Category: ${it.category_name}</small>` : ''}
                        ${it.barcode ? `<br><small style="color: #94a3b8;">Barcode: ${it.barcode}</small>` : ''}
                    </td>
                    <td style="text-align: center; white-space: nowrap;">
                        <span class="stock-tag out" style="font-size: 0.82rem; font-weight: 700;">
                            ${parseFloat(it.return_qty).toFixed(unit === 'pcs' ? 0 : 2)} ${unit}
                        </span>
                    </td>
                    <td style="text-align: right; font-weight: 600;">
                        ₹${parseFloat(it.refund_rate).toFixed(2)}
                    </td>
                    <td style="text-align: right;">
                        <strong style="color: #dc2626; font-size: 1.05rem;">
                            -₹${parseFloat(it.refund_amount).toFixed(2)}
                        </strong>
                    </td>
                    <td style="text-align: center;">
                        ${isRestocked ? `
                            <span style="display: inline-block; font-size: 0.75rem; font-weight: 700; background: #dcfce7; color: #15803d; padding: 2px 7px; border-radius: 999px;">
                                ✅ Restocked (+Stock)
                            </span>
                        ` : `
                            <span style="display: inline-block; font-size: 0.75rem; font-weight: 700; background: #fee2e2; color: #b91c1c; padding: 2px 7px; border-radius: 999px;">
                                ❌ Damaged / Scrap
                            </span>
                        `}
                    </td>
                    <td>
                        <span style="font-size: 0.85rem; color: #334155;">${it.return_reason || '-'}</span>
                    </td>
                    <td>
                        <strong>${it.customer_name || 'Walk-in'}</strong>
                        ${it.customer_phone ? `<br><small style="color: #64748b;">${it.customer_phone}</small>` : ''}
                    </td>
                    <td>
                        <span style="font-weight: 600; font-size: 0.85rem;">${it.refund_method || 'Cash'}</span>
                    </td>
                    <td style="text-align: center;">
                        <div style="display: flex; gap: 0.35rem; justify-content: center; flex-wrap: wrap;">
                            <button type="button" class="btn btn-primary" onclick="printReturnById(${it.return_id})" style="padding: 0.25rem 0.55rem; font-size: 0.78rem; background: #dc2626; border-color: #b91c1c;" title="Print 58mm Thermal Voucher">
                                🖨️ Slip
                            </button>
                            ${!isDirect ? `
                                <button type="button" class="btn btn-secondary" onclick="viewOriginalBill(${it.bill_id})" style="padding: 0.25rem 0.5rem; font-size: 0.78rem;" title="View Original Bill Details">
                                    📄 Bill
                                </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    async function viewOriginalBill(billId) {
        if (!billId) return;
        try {
            const res = await fetch(`api.php?action=get_bill_details&id=${billId}`);
            const data = await res.json();
            if (data.success) {
                renderOriginalBillModal(data.data);
                document.getElementById('billDetailModal').classList.add('active');
            } else {
                showToast(data.error || 'Failed to load bill', 'error');
            }
        } catch (e) {
            showToast('Network error loading bill', 'error');
        }
    }

    function renderOriginalBillModal(b) {
        const container = document.getElementById('receiptContainer');
        if (!container) return;

        let itemsHtml = '';
        (b.items || []).forEach(it => {
            itemsHtml += `
                <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 4px;">
                    <div>${it.product_name} x ${it.entered_qty} ${it.selected_unit}</div>
                    <div>₹${parseFloat(it.item_total).toFixed(2)}</div>
                </div>
            `;
        });

        container.innerHTML = `
            <div style="padding: 0.5rem; font-family: monospace;">
                <div style="text-align: center; border-bottom: 1px dashed #cbd5e1; padding-bottom: 0.5rem; margin-bottom: 0.5rem;">
                    <h4 style="margin: 0;">Original Invoice: ${b.bill_number}</h4>
                    <div style="font-size: 0.8rem; color: #64748b;">${b.created_at} | Customer: ${b.customer_name}</div>
                </div>
                <div style="margin-bottom: 0.5rem;">
                    ${itemsHtml}
                </div>
                <div style="border-top: 1px dashed #cbd5e1; padding-top: 0.5rem; font-size: 0.85rem;">
                    <div style="display: flex; justify-content: space-between;">
                        <span>Grand Total:</span>
                        <strong>₹${parseFloat(b.grand_total).toFixed(2)}</strong>
                    </div>
                    ${parseFloat(b.total_refunded || 0) > 0 ? `
                        <div style="display: flex; justify-content: space-between; color: #dc2626; font-weight: 700; margin-top: 2px;">
                            <span>Total Refunded:</span>
                            <span>-₹${parseFloat(b.total_refunded).toFixed(2)}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; color: #2563eb; font-weight: 800; margin-top: 2px;">
                            <span>Net Sales:</span>
                            <span>₹${parseFloat(b.net_grand_total).toFixed(2)}</span>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadReturnedProducts();
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

