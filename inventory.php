<?php
require_once __DIR__ . '/includes/header.php';
?>

<main class="main-wrapper">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.6rem; font-weight: 800; color: #0f172a;">3D Warehouse & Stock Control</h1>
            <p style="color: #64748b; font-size: 0.9rem;">Interactive 3D inventory shelves, real-time stock balance & health monitoring.</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <button type="button" class="btn btn-secondary" onclick="reloadWarehouseData()">
                ↻ Refresh 3D Shelves
            </button>
            <a href="products.php" class="btn btn-primary">
                + Add / Manage Products
            </a>
        </div>
    </div>

    <!-- Stock KPI Metric Cards -->
    <div class="stats-grid" id="stockKpiGrid">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Total Stock Value (Retail)</span>
                <span style="font-size: 1.2rem;">💰</span>
            </div>
            <div class="stat-value" id="kpiRetailVal">₹0.00</div>
            <div class="stat-sub">Cost valuation: <span id="kpiCostVal">₹0.00</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Total Products Tracked</span>
                <span style="font-size: 1.2rem;">📦</span>
            </div>
            <div class="stat-value" id="kpiTotalProds">0</div>
            <div class="stat-sub">Across all categories</div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Low Stock Alerts</span>
                <span style="font-size: 1.2rem;">⚠️</span>
            </div>
            <div class="stat-value" style="color: #f59e0b;" id="kpiLowStock">0</div>
            <div class="stat-sub">Requires restocking soon</div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Out of Stock</span>
                <span style="font-size: 1.2rem;">🚫</span>
            </div>
            <div class="stat-value" style="color: #ef4444;" id="kpiOutStock">0</div>
            <div class="stat-sub">Zero units left</div>
        </div>
    </div>

    <!-- 3D Warehouse Canvas View -->
    <div class="data-card" style="margin-bottom: 1.5rem; overflow: hidden;">
        <div class="data-card-header">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                    <polyline points="2 17 12 22 22 17"></polyline>
                    <polyline points="2 12 12 17 22 12"></polyline>
                </svg>
                <h3 style="font-size: 1.1rem; font-weight: 700;">Interactive 3D Warehouse Rack Visualizer</h3>
            </div>
            <div style="font-size: 0.85rem; color: #64748b;">
                🖱 Left-click & drag to Rotate | Right-click to Pan | Scroll to Zoom | Click any product box
            </div>
        </div>

        <div class="three-canvas-wrap" style="height: 520px; border-radius: 0;">
            <div id="threeWarehouseCanvas" style="width: 100%; height: 100%;"></div>
            
            <div class="three-overlay-controls">
                <button class="three-overlay-btn" onclick="warehouseInstance.resetCamera('overview')">Overview</button>
                <button class="three-overlay-btn" onclick="warehouseInstance.resetCamera('top')">Top View</button>
                <button class="three-overlay-btn" onclick="warehouseInstance.resetCamera('front')">Front Racks</button>
            </div>

            <div class="three-legend">
                <div class="legend-item"><span class="legend-color" style="background:#10b981;"></span> In Stock (&gt; Limit)</div>
                <div class="legend-item"><span class="legend-color" style="background:#f59e0b;"></span> Low Stock Warning</div>
                <div class="legend-item"><span class="legend-color" style="background:#ef4444;"></span> Out of Stock (0)</div>
            </div>
        </div>
    </div>

    <!-- Stock Ledger & Balance Table -->
    <div class="data-card">
        <div class="data-card-header">
            <h3 style="font-size: 1.1rem; font-weight: 700;">Stock Inventory Balance & Ledger</h3>
            <div style="display: flex; gap: 0.5rem;">
                <button class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;" onclick="filterStockTable('all')">All</button>
                <button class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; color: #b45309;" onclick="filterStockTable('low')">Low Stock Only</button>
                <button class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem; color: #b91c1c;" onclick="filterStockTable('out')">Out of Stock Only</button>
            </div>
        </div>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Base Unit</th>
                        <th>Current Stock Balance</th>
                        <th>Total Units Sold</th>
                        <th>Purchase Cost</th>
                        <th>Selling Price</th>
                        <th>Stock Health</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="stockTableBody">
                    <tr><td colspan="9" style="text-align: center; padding: 2rem; color: #94a3b8;">Loading inventory ledger...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Modal: Product Quick Info & Restock Trigger from 3D View -->
<div id="quickInfoModal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h3 class="modal-title" id="infoProdName">Product Details</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('quickInfoModal')">✕</button>
        </div>
        <div class="modal-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; background: #f8fafc; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div>
                    <span style="font-size: 0.8rem; color: #64748b;">Category:</span>
                    <div id="infoProdCat" style="font-weight: 600;">-</div>
                </div>
                <div>
                    <span style="font-size: 0.8rem; color: #64748b;">Barcode:</span>
                    <div id="infoProdBarcode" style="font-family: monospace;">-</div>
                </div>
                <div>
                    <span style="font-size: 0.8rem; color: #64748b;">Current Stock:</span>
                    <div id="infoProdStock" style="font-weight: 800; font-size: 1.1rem; color: #10b981;">0</div>
                </div>
                <div>
                    <span style="font-size: 0.8rem; color: #64748b;">Selling Price:</span>
                    <div id="infoProdPrice" style="font-weight: 800; font-size: 1.1rem; color: #2563eb;">₹0.00</div>
                </div>
                <div>
                    <span style="font-size: 0.8rem; color: #64748b;">Total Units Sold:</span>
                    <div id="infoProdSold" style="font-weight: 600;">0</div>
                </div>
                <div>
                    <span style="font-size: 0.8rem; color: #64748b;">Min Stock Alert:</span>
                    <div id="infoProdAlert" style="font-weight: 600;">5</div>
                </div>
            </div>

            <!-- Quick Restock in Modal -->
            <form onsubmit="handleQuickRestock(event)" style="margin-top: 0.5rem;">
                <input type="hidden" id="infoProdId" value="">
                <div class="form-group">
                    <label class="form-label">Add Stock Quantity (<span id="infoProdUnit">kg</span>)</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="number" id="infoRestockQty" class="form-control" placeholder="Qty" step="any" min="0.1" required>
                        <button type="submit" class="btn btn-success" style="white-space: nowrap;">+ Restock</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('quickInfoModal')">Close</button>
            <a href="index.php" class="btn btn-primary">Go to POS Billing</a>
        </div>
    </div>
</div>

<div id="toastContainer" class="toast-container"></div>

<!-- Scripts -->
<script src="assets/js/three-inventory.js"></script>

<script>
    let warehouseInstance = null;
    let cachedShelves = [];
    let allProducts = [];
    let currentFilter = 'all';

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

    async function initWarehouse() {
        warehouseInstance = new Warehouse3D('threeWarehouseCanvas', {
            onProductClick: (prod) => {
                openProductInfoModal(prod);
            }
        });

        await reloadWarehouseData();
    }

    async function reloadWarehouseData() {
        try {
            // Load 3D shelves
            const res3D = await fetch('api.php?action=get_3d_shelf_data');
            const data3D = await res3D.json();
            if (data3D.success) {
                cachedShelves = data3D.shelves;
                if (warehouseInstance) {
                    warehouseInstance.loadShelves(cachedShelves);
                }
            }

            // Load Reports/Stock Summary
            const repRes = await fetch('api.php?action=get_reports');
            const repData = await repRes.json();
            const curr = (typeof globalStoreSettings !== 'undefined' && globalStoreSettings.currency_symbol) || '₹';
            if (repData.success && repData.stock_summary) {
                const s = repData.stock_summary;
                document.getElementById('kpiRetailVal').innerText = `${curr}${parseFloat(s.stock_valuation_retail || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
                document.getElementById('kpiCostVal').innerText = `${curr}${parseFloat(s.stock_valuation_cost || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
                document.getElementById('kpiTotalProds').innerText = s.total_products || 0;
                document.getElementById('kpiLowStock').innerText = s.low_stock_count || 0;
                document.getElementById('kpiOutStock').innerText = s.out_of_stock_count || 0;
            }

            // Load all products for the ledger
            const prodRes = await fetch('api.php?action=get_products');
            const prodData = await prodRes.json();
            if (prodData.success) {
                allProducts = prodData.data;
                renderStockTable();
            }
        } catch (e) {
            console.error('Error reloading warehouse data:', e);
        }
    }

    function renderStockTable() {
        const tbody = document.getElementById('stockTableBody');
        let filtered = allProducts;

        if (currentFilter === 'low') {
            filtered = allProducts.filter(p => parseFloat(p.stock_qty) <= parseFloat(p.min_stock_alert) && parseFloat(p.stock_qty) > 0);
        } else if (currentFilter === 'out') {
            filtered = allProducts.filter(p => parseFloat(p.stock_qty) <= 0);
        }

        if (filtered.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding:2rem; color:#94a3b8;">No items matching filter</td></tr>`;
            return;
        }

        let html = '';
        const curr = (typeof globalStoreSettings !== 'undefined' && globalStoreSettings.currency_symbol) || '₹';
        filtered.forEach(p => {
            const stock = parseFloat(p.stock_qty);
            const minStock = parseFloat(p.min_stock_alert);
            let healthClass = 'in';
            let healthText = 'Healthy';

            if (stock <= 0) {
                healthClass = 'out';
                healthText = 'Out of Stock';
            } else if (stock <= minStock) {
                healthClass = 'low';
                healthText = 'Low Stock';
            }

            html += `
                <tr>
                    <td>
                        <strong>${p.name}</strong><br>
                        <small style="color:#94a3b8; font-family:monospace;">${p.barcode}</small>
                    </td>
                    <td>
                        <span class="product-cat-tag" style="background:${p.category_color}18; color:${p.category_color};">
                            ${p.category_name}
                        </span>
                    </td>
                    <td><strong>${p.base_unit.toUpperCase()}</strong></td>
                    <td>
                        <strong style="font-size:1.05rem;">${stock}</strong> <small>${p.base_unit}</small>
                    </td>
                    <td>${p.min_stock_alert} ${p.base_unit} (alert)</td>
                    <td>${curr}${parseFloat(p.purchase_price).toFixed(2)}</td>
                    <td><strong style="color:#2563eb;">${curr}${parseFloat(p.selling_price).toFixed(2)}</strong></td>
                    <td><span class="stock-tag ${healthClass}">${healthText}</span></td>
                    <td>
                        <button type="button" class="btn btn-secondary" style="padding:0.25rem 0.6rem; font-size:0.8rem;" onclick="openProductInfoModalById(${p.id})">Inspect / Restock</button>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    function filterStockTable(filterType) {
        currentFilter = filterType;
        renderStockTable();
    }

    function openProductInfoModalById(id) {
        const p = allProducts.find(item => item.id == id);
        if (p) openProductInfoModal(p);
    }

    function openProductInfoModal(prod) {
        document.getElementById('infoProdId').value = prod.id;
        document.getElementById('infoProdName').innerText = prod.name;
        document.getElementById('infoProdCat').innerText = prod.category_name || '-';
        document.getElementById('infoProdBarcode').innerText = prod.barcode;
        const curr = (typeof globalStoreSettings !== 'undefined' && globalStoreSettings.currency_symbol) || '₹';
        document.getElementById('infoProdStock').innerText = `${prod.stock_qty} ${prod.base_unit}`;
        document.getElementById('infoProdPrice').innerText = `${curr}${parseFloat(prod.selling_price).toFixed(2)} / ${prod.base_unit}`;
        document.getElementById('infoProdSold').innerText = `${prod.total_sold || 0} ${prod.base_unit}`;
        document.getElementById('infoProdAlert').innerText = `${prod.min_stock_alert} ${prod.base_unit}`;
        document.getElementById('infoProdUnit').innerText = prod.base_unit;
        document.getElementById('infoRestockQty').value = '';

        document.getElementById('quickInfoModal').classList.add('active');
    }

    async function handleQuickRestock(e) {
        e.preventDefault();
        const pId = document.getElementById('infoProdId').value;
        const qty = parseFloat(document.getElementById('infoRestockQty').value);

        try {
            const res = await fetch('api.php?action=update_stock', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: pId, qty, type: 'PURCHASE', note: 'Restocked from 3D Warehouse' })
            });
            const data = await res.json();
            if (data.success) {
                showToast('Stock restocked successfully!');
                closeModal('quickInfoModal');
                reloadWarehouseData();
            } else {
                showToast(data.error || 'Failed to update stock', 'error');
            }
        } catch (err) {
            showToast('Error updating stock', 'error');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        initWarehouse();
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

