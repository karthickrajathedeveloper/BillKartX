<?php
require_once __DIR__ . '/includes/header.php';
?>

<main class="main-wrapper">
    <!-- Header with Action Buttons -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.6rem; font-weight: 800; color: #0f172a;">Product & Category Management</h1>
            <p style="color: #64748b; font-size: 0.9rem;">Create categories, add products with unit-based pricing, set selling & purchase rates.</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <button type="button" class="btn btn-secondary" onclick="openCategoryModal()">
                + Manage Categories
            </button>
            <button type="button" class="btn btn-primary" onclick="openProductModal()">
                + Add New Product
            </button>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="data-card" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem;">
        <div class="products-filter-row">
            <div style="flex: 1; min-width: 220px;" class="filter-search-box">
                <input type="text" id="productFilterSearch" class="form-control" placeholder="Search product by name or barcode..." oninput="loadProductList()">
            </div>
            <div style="width: 220px;" class="filter-cat-box">
                <select id="productFilterCat" class="form-control" onchange="loadProductList()">
                    <option value="">All Categories</option>
                </select>
            </div>
            <button type="button" class="btn btn-secondary filter-refresh-btn" onclick="loadProductList()">
                Refresh
            </button>
        </div>
    </div>

    <!-- Products Data Table -->
    <div class="data-card">
        <div class="data-card-header">
            <h3 style="font-size: 1.1rem; font-weight: 700;">Inventory Products List</h3>
            <span id="productTotalCount" style="font-size: 0.85rem; font-weight: 600; color: #64748b;">0 Products</span>
        </div>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Barcode</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Base Unit</th>
                        <th>Purchase Price</th>
                        <th>Selling Price</th>
                        <th>Current Stock</th>
                        <th>Stock Value</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="productsTableBody">
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 2rem; color: #94a3b8;">Loading products...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Modal: Add / Edit Product -->
<div id="productFormModal" class="modal-overlay">
    <div class="modal-card modal-lg">
        <div class="modal-header">
            <h3 class="modal-title" id="productModalTitle">Add New Product</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('productFormModal')">✕</button>
        </div>
        <form id="productForm" onsubmit="saveProduct(event)">
            <input type="hidden" id="prodId" value="">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <select id="prodCategory" class="form-control" required>
                            <!-- Populated dynamically -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Barcode / SKU</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="text" id="prodBarcode" class="form-control" placeholder="Leave empty to auto-generate">
                            <button type="button" class="btn btn-secondary" onclick="generateBarcode()" style="padding: 0.4rem 0.75rem; font-size: 0.8rem;">Auto</button>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Product Name *</label>
                    <input type="text" id="prodName" class="form-control" placeholder="e.g. Ponni Boiled Rice / White Sugar / Milk" required>
                </div>

                <!-- Unit & Pricing -->
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Base Unit *</label>
                        <select id="prodBaseUnit" class="form-control" onchange="updateUnitHint()" required>
                            <option value="kg">kg (Kilogram)</option>
                            <option value="g">g (Gram)</option>
                            <option value="l">l (Liter)</option>
                            <option value="ml">ml (Milliliter)</option>
                            <option value="pcs">pcs (Piece/Units)</option>
                            <option value="box">box (Box/Carton)</option>
                            <option value="m">m (Meter)</option>
                        </select>
                        <small id="unitHint" style="color: #64748b; font-size: 0.75rem;">Supports g, kg billing calculation</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Purchase Price (₹) *</label>
                        <input type="number" id="prodPurchasePrice" class="form-control" placeholder="Cost per base unit" step="any" min="0" oninput="calcMargin()" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Selling Price (₹) *</label>
                        <input type="number" id="prodSellingPrice" class="form-control" placeholder="Price per base unit" step="any" min="0" oninput="calcMargin()" required>
                    </div>
                </div>

                <!-- Live Margin & Unit Demonstration Notice -->
                <div id="marginPreviewBox" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.75rem 1rem; font-size: 0.85rem; display: flex; justify-content: space-between;">
                    <div>Estimated Margin: <strong id="marginDisplay" style="color: #10b981;">0.00%</strong> (Profit: <span id="profitDisplay">₹0.00</span>)</div>
                    <div id="unitDemoDisplay" style="color: #2563eb; font-weight: 600;">1 kg = ₹0.00 | 500g = ₹0.00</div>
                </div>

                <!-- Stock Details -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Current Stock Quantity & Unit (pcs / kg / g / l) *</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="number" id="prodStock" class="form-control" placeholder="e.g. 50" step="any" min="0" required style="flex: 2;" oninput="updateStockUnitHelp()">
                            <select id="prodStockUnit" class="form-control" style="flex: 1.2;" onchange="updateStockUnitHelp()">
                                <option value="kg">kg (Kilogram)</option>
                                <option value="g">g (Gram)</option>
                                <option value="pcs">pcs (Piece)</option>
                                <option value="dozen">dozen (12 pcs)</option>
                                <option value="l">l (Liter)</option>
                                <option value="ml">ml (Milliliter)</option>
                                <option value="box">box (Box)</option>
                                <option value="m">m (Meter)</option>
                            </select>
                        </div>
                        <small id="stockUnitHint" style="color: #2563eb; font-size: 0.75rem; font-weight: 600;">Current stock unit: kg</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Min Stock Alert Limit</label>
                        <input type="number" id="prodMinAlert" class="form-control" value="5" step="any" min="0">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('productFormModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="saveProdBtn">Save Product</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Category Management -->
<div id="categoryModal" class="modal-overlay">
    <div class="modal-card modal-lg">
        <div class="modal-header">
            <h3 class="modal-title">Manage Categories</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('categoryModal')">✕</button>
        </div>
        <div class="modal-body">
            <!-- Add New Category Form -->
            <form id="categoryForm" onsubmit="saveCategory(event)" style="background: #f8fafc; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                <input type="hidden" id="catId" value="">
                <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.75rem;">Create / Edit Category</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Category Name *</label>
                        <input type="text" id="catName" class="form-control" placeholder="e.g. Grocery, Vegetables, Bakery" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Theme Color</label>
                        <input type="color" id="catColor" class="form-control" value="#2563eb" style="height: 42px; padding: 4px;">
                    </div>
                </div>
                <div class="form-group" style="margin-top: 0.5rem;">
                    <label class="form-label">Description (Optional)</label>
                    <input type="text" id="catDesc" class="form-control" placeholder="Short description of category items">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 0.75rem;">
                    <button type="button" class="btn btn-secondary" onclick="resetCategoryForm()">Reset</button>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>

            <!-- Categories List Table -->
            <div style="margin-top: 1rem;">
                <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.5rem;">Existing Categories</h4>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Color</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Products</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="categoryTableBody">
                        <!-- Populated dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('categoryModal')">Close</button>
        </div>
    </div>
</div>

<!-- Modal: Quick Restock / Stock In -->
<div id="restockModal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h3 class="modal-title">Restock / Add Inventory</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('restockModal')">✕</button>
        </div>
        <form onsubmit="confirmRestock(event)">
            <input type="hidden" id="restockProdId" value="">
            <div class="modal-body">
                <div style="background: #eff6ff; padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid #bfdbfe;">
                    <div id="restockProdName" style="font-weight: 700; font-size: 1.1rem; color: #1e3a8a;">Product</div>
                    <div style="font-size: 0.85rem; color: #475569; margin-top: 0.2rem;">
                        Current Stock: <strong id="restockCurrentStock" style="color: #10b981;">0</strong>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Quantity to Add & Unit (pcs / kg / g / l) *</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="number" id="restockQty" class="form-control" placeholder="e.g. 20" step="any" min="0.01" required style="flex: 2;">
                        <select id="restockUnitSelect" class="form-control" style="flex: 1.2;">
                            <option value="kg">kg</option>
                            <option value="g">g</option>
                            <option value="pcs">pcs</option>
                            <option value="dozen">dozen</option>
                            <option value="l">l</option>
                            <option value="ml">ml</option>
                            <option value="box">box</option>
                            <option value="m">m</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Note / Invoice Ref</label>
                    <input type="text" id="restockNote" class="form-control" placeholder="Supplier purchase, shipment #123">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('restockModal')">Cancel</button>
                <button type="submit" class="btn btn-success">+ Add to Stock</button>
            </div>
        </form>
    </div>
</div>

<!-- Toast Container -->
<div id="toastContainer" class="toast-container"></div>

<script>
    let globalCategories = [];
    let globalProducts = [];

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

    // ================= CATEGORIES =================
    async function loadCategories() {
        try {
            const res = await fetch('api.php?action=get_categories');
            const data = await res.json();
            if (data.success) {
                globalCategories = data.data;

                // Populate filter dropdown
                const filterSelect = document.getElementById('productFilterCat');
                const formSelect = document.getElementById('prodCategory');

                let filterOptions = '<option value="">All Categories</option>';
                let formOptions = '<option value="">Select Category</option>';

                globalCategories.forEach(cat => {
                    filterOptions += `<option value="${cat.id}">${cat.name}</option>`;
                    formOptions += `<option value="${cat.id}">${cat.name}</option>`;
                });

                filterSelect.innerHTML = filterOptions;
                formSelect.innerHTML = formOptions;

                renderCategoryTable();
            }
        } catch (e) {
            console.error(e);
        }
    }

    function renderCategoryTable() {
        const tbody = document.getElementById('categoryTableBody');
        if (!tbody) return;

        let html = '';
        globalCategories.forEach(c => {
            html += `
                <tr>
                    <td><span style="display:inline-block; width:18px; height:18px; border-radius:4px; background:${c.color};"></span></td>
                    <td><strong>${c.name}</strong></td>
                    <td><small style="color:#64748b;">${c.description || '-'}</small></td>
                    <td><span class="stock-tag in">${c.product_count} items</span></td>
                    <td>
                        <button type="button" class="btn btn-secondary" style="padding: 0.2rem 0.5rem; font-size: 0.8rem;" onclick="editCategory(${c.id})">Edit</button>
                        <button type="button" class="btn btn-danger" style="padding: 0.2rem 0.5rem; font-size: 0.8rem;" onclick="deleteCategory(${c.id})">Delete</button>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    }

    function openCategoryModal() {
        resetCategoryForm();
        renderCategoryTable();
        document.getElementById('categoryModal').classList.add('active');
    }

    function resetCategoryForm() {
        document.getElementById('catId').value = '';
        document.getElementById('catName').value = '';
        document.getElementById('catDesc').value = '';
        document.getElementById('catColor').value = '#2563eb';
    }

    function editCategory(id) {
        const cat = globalCategories.find(c => c.id == id);
        if (!cat) return;
        document.getElementById('catId').value = cat.id;
        document.getElementById('catName').value = cat.name;
        document.getElementById('catDesc').value = cat.description || '';
        document.getElementById('catColor').value = cat.color || '#2563eb';
    }

    async function saveCategory(e) {
        e.preventDefault();
        const id = document.getElementById('catId').value;
        const name = document.getElementById('catName').value.trim();
        const desc = document.getElementById('catDesc').value.trim();
        const color = document.getElementById('catColor').value;

        try {
            const res = await fetch('api.php?action=save_category', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, name, description: desc, color })
            });
            const data = await res.json();
            if (data.success) {
                showToast(data.message);
                resetCategoryForm();
                await loadCategories();
                loadProductList();
            } else {
                showToast(data.error || 'Failed to save', 'error');
            }
        } catch (err) {
            showToast('Error saving category', 'error');
        }
    }

    async function deleteCategory(id) {
        if (!confirm('Are you sure you want to delete this category?')) return;
        try {
            const res = await fetch('api.php?action=delete_category', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const data = await res.json();
            if (data.success) {
                showToast(data.message);
                await loadCategories();
                loadProductList();
            } else {
                showToast(data.error || 'Cannot delete category', 'error');
            }
        } catch (e) {
            showToast('Failed to delete category', 'error');
        }
    }

    // ================= PRODUCTS =================
    async function loadProductList() {
        const catId = document.getElementById('productFilterCat').value;
        const search = document.getElementById('productFilterSearch').value.trim();

        let url = 'api.php?action=get_products';
        if (catId) url += `&category_id=${catId}`;
        if (search) url += `&search=${encodeURIComponent(search)}`;

        try {
            const res = await fetch(url);
            const data = await res.json();
            if (data.success) {
                globalProducts = data.data;
                renderProductTable();
            }
        } catch (e) {
            console.error(e);
        }
    }

    function renderProductTable() {
        const tbody = document.getElementById('productsTableBody');
        const countEl = document.getElementById('productTotalCount');
        countEl.innerText = `${globalProducts.length} Products`;

        if (globalProducts.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding:2rem; color:#94a3b8;">No products found</td></tr>`;
            return;
        }

        let html = '';
        globalProducts.forEach(p => {
            const stock = parseFloat(p.stock_qty);
            const minStock = parseFloat(p.min_stock_alert);
            let badgeClass = 'in';
            if (stock <= 0) badgeClass = 'out';
            else if (stock <= minStock) badgeClass = 'low';

            const stockVal = (stock * parseFloat(p.selling_price)).toFixed(2);

            html += `
                <tr>
                    <td><code>${p.barcode}</code></td>
                    <td>
                        <strong>${p.name}</strong>
                    </td>
                    <td>
                        <span class="product-cat-tag" style="background:${p.category_color}18; color:${p.category_color};">
                            ${p.category_name}
                        </span>
                    </td>
                    <td><strong>${p.base_unit.toUpperCase()}</strong></td>
                    <td>₹${parseFloat(p.purchase_price).toFixed(2)}</td>
                    <td><strong style="color:#2563eb;">₹${parseFloat(p.selling_price).toFixed(2)}</strong></td>
                    <td>
                        <span class="stock-tag ${badgeClass}">${stock} ${p.base_unit}</span>
                    </td>
                    <td>₹${stockVal}</td>
                    <td>
                        <button type="button" class="btn btn-secondary" style="padding:0.25rem 0.5rem; font-size:0.8rem;" onclick="openRestockModal(${p.id})">+ Restock</button>
                        <button type="button" class="btn btn-secondary" style="padding:0.25rem 0.5rem; font-size:0.8rem;" onclick="editProduct(${p.id})">Edit</button>
                        <button type="button" class="btn btn-danger" style="padding:0.25rem 0.5rem; font-size:0.8rem;" onclick="deleteProduct(${p.id})">Delete</button>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    function getUnitMultiplier(baseUnit, selectedUnit) {
        baseUnit = (baseUnit || '').toLowerCase();
        selectedUnit = (selectedUnit || '').toLowerCase();
        if (baseUnit === selectedUnit) return 1.0;
        if (baseUnit === 'kg' && (selectedUnit === 'g' || selectedUnit === 'gram')) return 0.001;
        if (baseUnit === 'g' && selectedUnit === 'kg') return 1000.0;
        if (baseUnit === 'l' && (selectedUnit === 'ml' || selectedUnit === 'milliliter')) return 0.001;
        if (baseUnit === 'ml' && selectedUnit === 'l') return 1000.0;
        if ((baseUnit === 'pcs' || baseUnit === 'piece') && (selectedUnit === 'dozen' || selectedUnit === 'dz')) return 12.0;
        return 1.0;
    }

    function updateStockUnitHelp() {
        const baseEl = document.getElementById('prodBaseUnit');
        const stockUnitEl = document.getElementById('prodStockUnit');
        const stockInput = document.getElementById('prodStock');
        const hint = document.getElementById('stockUnitHint');
        if (!baseEl || !stockUnitEl || !hint) return;

        const base = baseEl.value;
        const stockUnit = stockUnitEl.value;
        const entered = parseFloat(stockInput.value) || 0;

        if (base === stockUnit) {
            hint.innerText = `Current stock: ${entered} ${base}`;
            hint.style.color = '#2563eb';
        } else {
            const mult = getUnitMultiplier(base, stockUnit);
            const inBase = (entered * mult).toFixed(3);
            hint.innerText = `${entered} ${stockUnit} = ${parseFloat(inBase)} ${base} in base inventory`;
            hint.style.color = '#16a34a';
        }
    }

    function openProductModal() {
        document.getElementById('productForm').reset();
        document.getElementById('prodId').value = '';
        document.getElementById('productModalTitle').innerText = 'Add New Product';
        document.getElementById('prodStock').value = '10';
        document.getElementById('prodStockUnit').value = document.getElementById('prodBaseUnit').value;
        generateBarcode();
        calcMargin();
        updateUnitHint();
        updateStockUnitHelp();
        document.getElementById('productFormModal').classList.add('active');
    }

    function editProduct(id) {
        const p = globalProducts.find(item => item.id == id);
        if (!p) return;

        document.getElementById('prodId').value = p.id;
        document.getElementById('prodCategory').value = p.category_id;
        document.getElementById('prodBarcode').value = p.barcode;
        document.getElementById('prodName').value = p.name;
        document.getElementById('prodBaseUnit').value = p.base_unit;
        document.getElementById('prodPurchasePrice').value = p.purchase_price;
        document.getElementById('prodSellingPrice').value = p.selling_price;
        document.getElementById('prodStock').value = p.stock_qty;
        document.getElementById('prodStockUnit').value = p.base_unit;
        document.getElementById('prodMinAlert').value = p.min_stock_alert;

        document.getElementById('productModalTitle').innerText = 'Edit Product';
        calcMargin();
        updateUnitHint();
        updateStockUnitHelp();
        document.getElementById('productFormModal').classList.add('active');
    }

    function generateBarcode() {
        const code = 'PROD' + Math.floor(10000000 + Math.random() * 90000000);
        document.getElementById('prodBarcode').value = code;
    }

    function updateUnitHint() {
        const u = document.getElementById('prodBaseUnit').value;
        const hint = document.getElementById('unitHint');
        if (u === 'kg') hint.innerText = 'Calculates fractional units (e.g. 500g = half price)';
        else if (u === 'l') hint.innerText = 'Calculates fractional volume (e.g. 500ml = half price)';
        else hint.innerText = `Standard count in ${u}`;

        const stockSelect = document.getElementById('prodStockUnit');
        if (stockSelect) stockSelect.value = u;
        updateStockUnitHelp();
        calcMargin();
    }

    function calcMargin() {
        const cost = parseFloat(document.getElementById('prodPurchasePrice').value) || 0;
        const price = parseFloat(document.getElementById('prodSellingPrice').value) || 0;
        const unit = document.getElementById('prodBaseUnit').value;

        const profit = price - cost;
        const marginPct = cost > 0 ? ((profit / cost) * 100).toFixed(1) : 0;

        document.getElementById('profitDisplay').innerText = `₹${profit.toFixed(2)}`;
        document.getElementById('marginDisplay').innerText = `${marginPct}%`;

        // Unit demo display (e.g. 1kg = 10, 500g = 5)
        if (unit === 'kg') {
            document.getElementById('unitDemoDisplay').innerText = `1 kg = ₹${price.toFixed(2)} | 500g = ₹${(price * 0.5).toFixed(2)}`;
        } else if (unit === 'l') {
            document.getElementById('unitDemoDisplay').innerText = `1 L = ₹${price.toFixed(2)} | 500ml = ₹${(price * 0.5).toFixed(2)}`;
        } else {
            document.getElementById('unitDemoDisplay').innerText = `1 ${unit} = ₹${price.toFixed(2)}`;
        }
    }

    async function saveProduct(e) {
        e.preventDefault();
        const baseUnit = document.getElementById('prodBaseUnit').value;
        const stockUnit = document.getElementById('prodStockUnit').value;
        const enteredStock = parseFloat(document.getElementById('prodStock').value) || 0;
        const mult = getUnitMultiplier(baseUnit, stockUnit);
        const finalBaseStock = enteredStock * mult;

        const payload = {
            id: document.getElementById('prodId').value,
            category_id: document.getElementById('prodCategory').value,
            barcode: document.getElementById('prodBarcode').value.trim(),
            name: document.getElementById('prodName').value.trim(),
            base_unit: baseUnit,
            purchase_price: parseFloat(document.getElementById('prodPurchasePrice').value),
            selling_price: parseFloat(document.getElementById('prodSellingPrice').value),
            stock_qty: finalBaseStock,
            min_stock_alert: parseFloat(document.getElementById('prodMinAlert').value)
        };

        try {
            const res = await fetch('api.php?action=save_product', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                showToast(data.message);
                closeModal('productFormModal');
                loadProductList();
            } else {
                showToast(data.error || 'Failed to save product', 'error');
            }
        } catch (err) {
            showToast('Error saving product', 'error');
        }
    }

    async function deleteProduct(id) {
        if (!confirm('Are you sure you want to delete this product?')) return;
        try {
            const res = await fetch('api.php?action=delete_product', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            const data = await res.json();
            if (data.success) {
                showToast(data.message);
                loadProductList();
            } else {
                showToast(data.error || 'Cannot delete product', 'error');
            }
        } catch (e) {
            showToast('Failed to delete product', 'error');
        }
    }

    // ================= RESTOCK MODAL =================
    function openRestockModal(id) {
        const p = globalProducts.find(item => item.id == id);
        if (!p) return;

        document.getElementById('restockProdId').value = p.id;
        document.getElementById('restockProdName').innerText = p.name;
        document.getElementById('restockCurrentStock').innerText = `${p.stock_qty} ${p.base_unit}`;
        document.getElementById('restockQty').value = '';
        document.getElementById('restockNote').value = '';

        const unitSelect = document.getElementById('restockUnitSelect');
        if (unitSelect) unitSelect.value = p.base_unit;

        document.getElementById('restockModal').classList.add('active');
    }

    async function confirmRestock(e) {
        e.preventDefault();
        const pId = document.getElementById('restockProdId').value;
        const p = globalProducts.find(item => item.id == pId);
        const qty = parseFloat(document.getElementById('restockQty').value) || 0;
        const restockUnit = document.getElementById('restockUnitSelect') ? document.getElementById('restockUnitSelect').value : (p ? p.base_unit : 'kg');
        const mult = p ? getUnitMultiplier(p.base_unit, restockUnit) : 1.0;
        const finalBaseQty = qty * mult;
        const userNote = document.getElementById('restockNote').value.trim();
        const note = userNote + (restockUnit !== (p ? p.base_unit : '') ? ` (Entered: ${qty} ${restockUnit})` : '');

        try {
            const res = await fetch('api.php?action=update_stock', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ product_id: pId, qty: finalBaseQty, type: 'PURCHASE', note })
            });
            const data = await res.json();
            if (data.success) {
                showToast('Stock added successfully!');
                closeModal('restockModal');
                loadProductList();
            } else {
                showToast(data.error || 'Failed to update stock', 'error');
            }
        } catch (err) {
            showToast('Error updating stock', 'error');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadCategories();
        loadProductList();
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

