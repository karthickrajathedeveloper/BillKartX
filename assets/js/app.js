/**
 * POS & Billing Core Application Engine
 * Dynamic unit calculation, cart operations, instant receipts & PDF download
 */

const App = {
    cart: [],
    products: [],
    categories: [],
    currentCategory: null,
    searchQuery: '',
    selectedProductForAdd: null,
    discountMode: 'overall', // 'overall' or 'item'
    isGstEnabled: true,
    savedTaxRate: 5,
    lastBillId: localStorage.getItem('pos_last_bill_id') || null,
    currentBillData: null,
    settings: {
        currency: '₹',
        taxRate: 5,
        enableGst: '1'
    },

    init() {
        this.bindEvents();
        this.loadSettings();
        this.loadCategories();
        this.loadCart();
        this.loadProducts();
        if (this.lastBillId) {
            const btn = document.getElementById('reprintLastBillBtn');
            if (btn) btn.style.display = 'inline-flex';
        }
    },

    // ================= AUDIO FEEDBACK =================
    beep(type = 'success') {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);

            if (type === 'success') {
                osc.frequency.setValueAtTime(800, ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(1200, ctx.currentTime + 0.08);
                gain.gain.setValueAtTime(0.2, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.1);
                osc.start(ctx.currentTime);
                osc.stop(ctx.currentTime + 0.1);
            } else if (type === 'error') {
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(300, ctx.currentTime);
                gain.gain.setValueAtTime(0.25, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.2);
                osc.start(ctx.currentTime);
                osc.stop(ctx.currentTime + 0.2);
            }
        } catch (e) {
            // Audio context not allowed or unsupported
        }
    },

    // ================= TOAST NOTIFICATIONS =================
    showToast(message, type = 'success') {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <span>${type === 'success' ? '✓' : '⚠'}</span>
            <div>${message}</div>
        `;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    },

    // ================= SETTINGS & CATEGORIES =================
    async loadSettings() {
        try {
            const res = await fetch('api.php?action=get_settings');
            const data = await res.json();
            if (data.success && data.data) {
                this.settings.currency = data.data.currency_symbol || '₹';
                this.settings.taxRate = data.data.tax_rate !== undefined ? parseFloat(data.data.tax_rate) : 5;
                this.settings.enableGst = data.data.enable_gst !== undefined ? data.data.enable_gst : '1';
                this.savedTaxRate = this.settings.taxRate > 0 ? this.settings.taxRate : 5;

                const savedMeta = localStorage.getItem('pos_billing_cart_meta');
                if (savedMeta) {
                    try {
                        const meta = JSON.parse(savedMeta);
                        if (meta.is_gst_enabled !== undefined) {
                            this.isGstEnabled = (meta.is_gst_enabled === true || meta.is_gst_enabled === 'true');
                        } else {
                            this.isGstEnabled = (this.settings.enableGst !== '0');
                        }
                        if (meta.saved_tax_rate !== undefined) {
                            this.savedTaxRate = parseFloat(meta.saved_tax_rate) || this.savedTaxRate;
                        }
                    } catch (e) {
                        this.isGstEnabled = (this.settings.enableGst !== '0');
                    }
                } else {
                    this.isGstEnabled = (this.settings.enableGst !== '0');
                }

                const taxInput = document.getElementById('cartTaxRate');
                if (taxInput && (!savedMeta || this.isGstEnabled)) {
                    taxInput.value = this.savedTaxRate;
                }

                this.setGstEnabled(this.isGstEnabled, false);

                if (this.cart.length > 0) {
                    this.renderCart();
                }
            }
        } catch (e) {
            console.error('Failed to load settings:', e);
        }
    },

    async loadCategories() {
        try {
            const res = await fetch('api.php?action=get_categories');
            const data = await res.json();
            if (data.success) {
                this.categories = data.data;
                this.renderCategories();
            }
        } catch (e) {
            console.error('Failed to load categories:', e);
        }
    },

    renderCategories() {
        const container = document.getElementById('categoryPills');
        if (!container) return;

        let html = `
            <button class="category-pill ${this.currentCategory === null ? 'active' : ''}" onclick="App.filterByCategory(null)">
                All Products
            </button>
        `;

        this.categories.forEach(cat => {
            const isActive = this.currentCategory === parseInt(cat.id);
            html += `
                <button class="category-pill ${isActive ? 'active' : ''}" onclick="App.filterByCategory(${cat.id})">
                    <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:${cat.color}; margin-right:4px;"></span>
                    ${cat.name} (${cat.product_count})
                </button>
            `;
        });

        container.innerHTML = html;
    },

    filterByCategory(catId) {
        this.currentCategory = catId;
        this.renderCategories();
        this.loadProducts();
    },

    // ================= PRODUCTS CATALOG =================
    async loadProducts() {
        try {
            let url = 'api.php?action=get_products';
            if (this.currentCategory) url += `&category_id=${this.currentCategory}`;
            if (this.searchQuery) url += `&search=${encodeURIComponent(this.searchQuery)}`;

            const res = await fetch(url);
            const data = await res.json();
            if (data.success) {
                this.products = data.data;
                this.renderProducts();
            }
        } catch (e) {
            console.error('Failed to load products:', e);
        }
    },

    renderProducts() {
        const grid = document.getElementById('productGrid');
        if (!grid) return;

        if (this.products.length === 0) {
            grid.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 3rem; color: #94a3b8;">
                    <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🔍</div>
                    <h3>No products found</h3>
                    <p style="font-size: 0.9rem;">Try searching something else or add new products.</p>
                </div>
            `;
            return;
        }

        let html = '';
        this.products.forEach(p => {
            const stock = parseFloat(p.stock_qty);
            const minStock = parseFloat(p.min_stock_alert);
            let stockClass = 'in';
            let stockText = `${stock} ${p.base_unit} in stock`;

            if (stock <= 0) {
                stockClass = 'out';
                stockText = 'Out of Stock';
            } else if (stock <= minStock) {
                stockClass = 'low';
                stockText = `Low: ${stock} ${p.base_unit}`;
            }

            html += `
                <div class="product-card" onclick="App.openAddModal(${p.id})">
                    <div>
                        <div class="product-cat-tag" style="background: ${p.category_color}18; color: ${p.category_color};">
                            ${p.category_name}
                        </div>
                        <h4 class="product-title">${p.name}</h4>
                        <div class="product-barcode">Barcode: ${p.barcode}</div>
                    </div>
                    <div class="product-footer">
                        <div class="product-price">
                            <span class="price-unit">per 1 ${p.base_unit.toUpperCase()}</span>
                            <span class="price-val">${this.settings.currency}${parseFloat(p.selling_price).toFixed(2)}</span>
                        </div>
                        <span class="stock-tag ${stockClass}">${stockText}</span>
                    </div>
                </div>
            `;
        });

        grid.innerHTML = html;
    },

    // ================= MODAL: ADD PRODUCT TO CART WITH UNIT PRESETS =================
    openAddModal(productId) {
        const product = this.products.find(p => p.id == productId);
        if (!product) return;

        this.selectedProductForAdd = product;
        const modal = document.getElementById('addProductModal');
        if (!modal) return;

        document.getElementById('modalProdName').innerText = product.name;
        document.getElementById('modalProdCategory').innerText = product.category_name;
        document.getElementById('modalProdPrice').innerText = `${this.settings.currency}${parseFloat(product.selling_price).toFixed(2)} / ${product.base_unit}`;
        document.getElementById('modalProdStock').innerText = `${product.stock_qty} ${product.base_unit}`;

        const baseUnit = product.base_unit.toLowerCase();
        const unitSelect = document.getElementById('modalUnitSelect');
        const pillsContainer = document.getElementById('modalQuickPills');

        // Populate unit dropdown
        let units = [baseUnit];
        let quickPresets = [];

        if (baseUnit === 'kg') {
            units = ['kg', 'g'];
            quickPresets = [
                { label: '100g', qty: 100, unit: 'g' },
                { label: '250g', qty: 250, unit: 'g' },
                { label: '500g', qty: 500, unit: 'g' },
                { label: '1 Kg', qty: 1, unit: 'kg' },
                { label: '2 Kg', qty: 2, unit: 'kg' },
                { label: '5 Kg', qty: 5, unit: 'kg' }
            ];
        } else if (baseUnit === 'l' || baseUnit === 'liter') {
            units = ['l', 'ml'];
            quickPresets = [
                { label: '100ml', qty: 100, unit: 'ml' },
                { label: '250ml', qty: 250, unit: 'ml' },
                { label: '500ml', qty: 500, unit: 'ml' },
                { label: '1 Liter', qty: 1, unit: 'l' },
                { label: '2 Liters', qty: 2, unit: 'l' }
            ];
        } else if (baseUnit === 'pcs' || baseUnit === 'piece') {
            units = ['pcs', 'dozen'];
            quickPresets = [
                { label: '1 Pc', qty: 1, unit: 'pcs' },
                { label: '2 Pcs', qty: 2, unit: 'pcs' },
                { label: '5 Pcs', qty: 5, unit: 'pcs' },
                { label: '1 Dozen', qty: 1, unit: 'dozen' }
            ];
        } else {
            quickPresets = [
                { label: `1 ${baseUnit}`, qty: 1, unit: baseUnit },
                { label: `2 ${baseUnit}`, qty: 2, unit: baseUnit },
                { label: `5 ${baseUnit}`, qty: 5, unit: baseUnit }
            ];
        }

        unitSelect.innerHTML = units.map(u => `<option value="${u}">${u.toUpperCase()}</option>`).join('');

        // Build quick pills
        let pillsHtml = '';
        quickPresets.forEach(preset => {
            pillsHtml += `
                <button type="button" class="quick-unit-btn" onclick="App.setModalPreset(${preset.qty}, '${preset.unit}')">
                    ${preset.label}
                </button>
            `;
        });
        pillsContainer.innerHTML = pillsHtml;

        // Default qty
        document.getElementById('modalQtyInput').value = '1';
        unitSelect.value = baseUnit;

        this.calcModalPrice();
        modal.classList.add('active');
        document.getElementById('modalQtyInput').focus();
        document.getElementById('modalQtyInput').select();
    },

    setModalPreset(qty, unit) {
        document.getElementById('modalQtyInput').value = qty;
        document.getElementById('modalUnitSelect').value = unit;
        this.calcModalPrice();
    },

    calcModalPrice() {
        if (!this.selectedProductForAdd) return;

        const qty = parseFloat(document.getElementById('modalQtyInput').value) || 0;
        const selectedUnit = document.getElementById('modalUnitSelect').value;
        const baseUnit = this.selectedProductForAdd.base_unit;
        const baseSellingPrice = parseFloat(this.selectedProductForAdd.selling_price);

        const multiplier = this.getMultiplier(baseUnit, selectedUnit);
        const baseQty = qty * multiplier;
        const totalPrice = (baseQty * baseSellingPrice).toFixed(2);

        document.getElementById('modalCalculatedPrice').innerText = `${this.settings.currency}${totalPrice}`;
        document.getElementById('modalBaseDeduction').innerText = `Stock deduction: ${baseQty} ${baseUnit}`;
    },

    confirmAddToCart() {
        if (!this.selectedProductForAdd) return;

        const qty = parseFloat(document.getElementById('modalQtyInput').value) || 0;
        const unit = document.getElementById('modalUnitSelect').value;

        if (qty <= 0) {
            this.showToast('Please enter a valid quantity', 'error');
            this.beep('error');
            return;
        }

        const multiplier = this.getMultiplier(this.selectedProductForAdd.base_unit, unit);
        const requiredBaseQty = qty * multiplier;

        if (parseFloat(this.selectedProductForAdd.stock_qty) < requiredBaseQty) {
            this.showToast(`Insufficient stock! Available: ${this.selectedProductForAdd.stock_qty} ${this.selectedProductForAdd.base_unit}`, 'error');
            this.beep('error');
            return;
        }

        this.addItemToCart(this.selectedProductForAdd, qty, unit);
        this.closeModal('addProductModal');
    },

    getMultiplier(baseUnit, selectedUnit) {
        baseUnit = (baseUnit || '').toLowerCase();
        selectedUnit = (selectedUnit || '').toLowerCase();

        if (baseUnit === selectedUnit) return 1.0;

        if (baseUnit === 'kg' && (selectedUnit === 'g' || selectedUnit === 'gram')) return 0.001;
        if (baseUnit === 'g' && selectedUnit === 'kg') return 1000.0;
        if (baseUnit === 'l' && (selectedUnit === 'ml' || selectedUnit === 'milliliter')) return 0.001;
        if (baseUnit === 'ml' && selectedUnit === 'l') return 1000.0;
        if ((baseUnit === 'pcs' || baseUnit === 'piece') && (selectedUnit === 'dozen' || selectedUnit === 'dz')) return 12.0;

        return 1.0;
    },

    // ================= CART OPERATIONS & PERSISTENCE =================
    loadCart() {
        try {
            const saved = localStorage.getItem('pos_billing_cart');
            if (saved) {
                const parsed = JSON.parse(saved);
                if (Array.isArray(parsed)) {
                    this.cart = parsed;
                }
            }
            const savedMeta = localStorage.getItem('pos_billing_cart_meta');
            if (savedMeta) {
                const meta = JSON.parse(savedMeta);
                const nameEl = document.getElementById('custName');
                const phoneEl = document.getElementById('custPhone');
                const discEl = document.getElementById('cartDiscount');
                const taxEl = document.getElementById('cartTaxRate');
                if (nameEl && meta.customer_name !== undefined) nameEl.value = meta.customer_name;
                if (phoneEl && meta.customer_phone !== undefined) phoneEl.value = meta.customer_phone;
                if (discEl && meta.discount !== undefined) discEl.value = meta.discount;
                if (taxEl && meta.tax_rate !== undefined) taxEl.value = meta.tax_rate;
                if (meta.saved_tax_rate !== undefined) this.savedTaxRate = parseFloat(meta.saved_tax_rate) || 5;
                if (meta.is_gst_enabled !== undefined) {
                    this.isGstEnabled = (meta.is_gst_enabled === true || meta.is_gst_enabled === 'true');
                    this.setGstEnabled(this.isGstEnabled, false);
                }
            }
        } catch (e) {
            console.error('Failed to load cart from storage:', e);
            this.cart = [];
        }
        this.renderCart();
        this.updateNavBadge();
    },

    saveCart() {
        try {
            localStorage.setItem('pos_billing_cart', JSON.stringify(this.cart));
            const nameEl = document.getElementById('custName');
            const phoneEl = document.getElementById('custPhone');
            const discEl = document.getElementById('cartDiscount');
            const taxEl = document.getElementById('cartTaxRate');
            const meta = {
                customer_name: nameEl ? nameEl.value : 'Walk-in Customer',
                customer_phone: phoneEl ? phoneEl.value : '',
                discount: discEl ? discEl.value : '0',
                tax_rate: taxEl ? taxEl.value : this.settings.taxRate,
                is_gst_enabled: this.isGstEnabled,
                saved_tax_rate: this.savedTaxRate
            };
            localStorage.setItem('pos_billing_cart_meta', JSON.stringify(meta));
        } catch (e) {
            console.error('Failed to save cart to storage:', e);
        }
        this.updateNavBadge();
    },

    updateNavBadge() {
        const badges = document.querySelectorAll('#navCartBadge, .nav-cart-badge, #mobileCartTabCount');
        const count = this.cart.length;
        badges.forEach(badge => {
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'inline-flex';
            } else {
                badge.textContent = '0';
                if (badge.id === 'mobileCartTabCount') {
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            }
        });

        // Update mobile floating checkout bar
        const floatingBar = document.getElementById('posFloatingBar');
        const floatingCount = document.getElementById('floatingCartCount');
        if (floatingCount) floatingCount.innerText = `${count} ${count === 1 ? 'Item' : 'Items'}`;
        if (floatingBar) {
            const isMobile = window.innerWidth <= 992;
            const isCatalogView = !document.getElementById('posCatalogPane') || !document.getElementById('posCatalogPane').classList.contains('mobile-hidden');
            if (isMobile && count > 0 && isCatalogView) {
                floatingBar.style.display = 'flex';
            } else {
                floatingBar.style.display = 'none';
            }
        }
    },

    setDiscountMode(mode) {
        this.discountMode = mode;
        const overallBtn = document.getElementById('discModeOverallBtn');
        const itemBtn = document.getElementById('discModeItemBtn');
        const overallGroup = document.getElementById('cartDiscountGroup');
        const itemGroup = document.getElementById('cartItemDiscountSummaryGroup');

        if (mode === 'item') {
            if (overallBtn) { overallBtn.style.background = 'var(--slate-100)'; overallBtn.style.color = 'var(--slate-700)'; }
            if (itemBtn) { itemBtn.style.background = 'var(--primary)'; itemBtn.style.color = '#fff'; }
            if (overallGroup) overallGroup.style.display = 'none';
            if (itemGroup) itemGroup.style.display = 'block';
        } else {
            if (overallBtn) { overallBtn.style.background = 'var(--primary)'; overallBtn.style.color = '#fff'; }
            if (itemBtn) { itemBtn.style.background = 'var(--slate-100)'; itemBtn.style.color = 'var(--slate-700)'; }
            if (overallGroup) overallGroup.style.display = 'block';
            if (itemGroup) itemGroup.style.display = 'none';
        }

        this.renderCart();
    },

    updateItemDiscount(index, val) {
        if (!this.cart[index]) return;
        this.cart[index].discount = Math.max(0, parseFloat(val) || 0);
        this.renderCart();
    },

    setGstEnabled(enabled, triggerRender = true) {
        this.isGstEnabled = !!enabled;
        const btnOn = document.getElementById('gstBtnOn');
        const btnOff = document.getElementById('gstBtnOff');
        const rateWrapper = document.getElementById('gstRateWrapper');
        const offBadge = document.getElementById('gstOffBadge');
        const taxInput = document.getElementById('cartTaxRate');

        if (this.isGstEnabled) {
            if (btnOn) { btnOn.style.background = '#2563eb'; btnOn.style.color = '#fff'; }
            if (btnOff) { btnOff.style.background = '#f1f5f9'; btnOff.style.color = '#64748b'; }
            if (rateWrapper) rateWrapper.style.display = 'block';
            if (offBadge) offBadge.style.display = 'none';
            if (taxInput && (parseFloat(taxInput.value) === 0 || !taxInput.value)) {
                taxInput.value = this.savedTaxRate || this.settings.taxRate || 5;
            }
        } else {
            if (btnOn) { btnOn.style.background = '#f1f5f9'; btnOn.style.color = '#64748b'; }
            if (btnOff) { btnOff.style.background = '#ef4444'; btnOff.style.color = '#fff'; }
            if (rateWrapper) rateWrapper.style.display = 'none';
            if (offBadge) offBadge.style.display = 'block';
            if (taxInput && parseFloat(taxInput.value) > 0) {
                this.savedTaxRate = parseFloat(taxInput.value);
            }
        }

        this.saveCart();
        if (triggerRender) {
            this.renderCart();
        }
    },

    onTaxRateInput() {
        const taxInput = document.getElementById('cartTaxRate');
        if (taxInput) {
            const val = parseFloat(taxInput.value) || 0;
            if (val > 0) {
                this.savedTaxRate = val;
            }
        }
        this.renderCart();
    },

    applyGlobalGstSetting(enableGst, taxRate) {
        this.settings.enableGst = enableGst;
        if (taxRate !== undefined && taxRate !== '') {
            const tr = parseFloat(taxRate) || 0;
            this.settings.taxRate = tr;
            if (tr > 0) this.savedTaxRate = tr;
        }
        const taxInput = document.getElementById('cartTaxRate');
        if (taxInput && this.savedTaxRate > 0) {
            taxInput.value = this.savedTaxRate;
        }
        this.setGstEnabled(enableGst !== '0', true);
    },

    addItemToCart(product, qty, unit) {
        // Check if item with same ID and unit already exists in cart
        const existingIndex = this.cart.findIndex(item => item.id == product.id && item.unit == unit);

        if (existingIndex > -1) {
            this.cart[existingIndex].qty += qty;
        } else {
            this.cart.push({
                id: product.id,
                name: product.name,
                barcode: product.barcode,
                category_name: product.category_name,
                base_unit: product.base_unit,
                unit: unit,
                qty: qty,
                discount: 0,
                selling_price: parseFloat(product.selling_price),
                purchase_price: parseFloat(product.purchase_price)
            });
        }

        this.beep('success');
        this.showToast(`Added '${product.name}' to cart`);
        this.renderCart();
    },

    updateCartQty(index, delta) {
        if (!this.cart[index]) return;

        // Smart increment step: for grams 100g, for kg 1 or 0.5
        let step = 1;
        if (this.cart[index].unit === 'g' || this.cart[index].unit === 'ml') {
            step = 100;
        } else if (this.cart[index].unit === 'kg' || this.cart[index].unit === 'l') {
            step = 0.5;
        }

        const newQty = Math.max(0, this.cart[index].qty + (delta * step));

        if (newQty <= 0) {
            this.removeFromCart(index);
        } else {
            this.cart[index].qty = Math.round(newQty * 100) / 100;
            this.renderCart();
        }
    },

    setCartItemQty(index, val) {
        if (!this.cart[index]) return;
        const qty = parseFloat(val);
        if (isNaN(qty) || qty <= 0) {
            this.removeFromCart(index);
            return;
        }
        this.cart[index].qty = Math.round(qty * 1000) / 1000;
        this.renderCart();
    },

    onCartItemQtyInput(index, val) {
        if (!this.cart[index]) return;
        const qty = parseFloat(val);
        if (!isNaN(qty) && qty > 0) {
            this.cart[index].qty = qty;
            this.calculateAndRefreshTotals();
        }
    },

    calculateAndRefreshTotals() {
        let subtotal = 0;
        this.cart.forEach((item, idx) => {
            const multiplier = this.getMultiplier(item.base_unit, item.unit);
            const baseQty = (parseFloat(item.qty) || 0) * multiplier;
            const lineTotal = baseQty * item.selling_price;
            subtotal += lineTotal;
            const lineTotalEls = document.querySelectorAll('.cart-item-total');
            if (lineTotalEls[idx]) {
                const itemDisc = parseFloat(item.discount) || 0;
                const netLineTotal = Math.max(0, lineTotal - itemDisc);
                if (this.discountMode === 'item' && itemDisc > 0) {
                    lineTotalEls[idx].innerHTML = `<span style="font-size: 0.78rem; text-decoration: line-through; color: #94a3b8; margin-right: 4px;">${this.settings.currency}${lineTotal.toFixed(2)}</span>${this.settings.currency}${netLineTotal.toFixed(2)}`;
                } else {
                    lineTotalEls[idx].innerText = `${this.settings.currency}${lineTotal.toFixed(2)}`;
                }
            }
        });
        this.updateCartTotals(subtotal);
        this.saveCart();
    },

    changeCartItemUnit(index, newUnit) {
        if (!this.cart[index]) return;
        const item = this.cart[index];
        const oldUnit = item.unit;

        if (oldUnit === newUnit) return;

        // Auto convert quantity proportionally
        // e.g. 1 kg -> 1000 g, or 500 g -> 0.5 kg
        if (oldUnit === 'kg' && newUnit === 'g') {
            item.qty = item.qty * 1000;
        } else if (oldUnit === 'g' && newUnit === 'kg') {
            item.qty = item.qty / 1000;
        } else if (oldUnit === 'l' && newUnit === 'ml') {
            item.qty = item.qty * 1000;
        } else if (oldUnit === 'ml' && newUnit === 'l') {
            item.qty = item.qty / 1000;
        }

        item.unit = newUnit;
        this.renderCart();
    },

    removeFromCart(index) {
        if (!this.cart[index]) return;
        const name = this.cart[index].name;
        this.cart.splice(index, 1);
        this.showToast(`Removed '${name}' from cart`);
        this.renderCart();
    },

    clearCart(skipConfirm = false) {
        if (this.cart.length === 0) return;
        if (skipConfirm || confirm('Are you sure you want to clear the entire cart?')) {
            this.cart = [];
            try {
                localStorage.removeItem('pos_billing_cart');
                localStorage.removeItem('pos_billing_cart_meta');
            } catch (e) {}
            const nameEl = document.getElementById('custName');
            const phoneEl = document.getElementById('custPhone');
            const discEl = document.getElementById('cartDiscount');
            if (nameEl) nameEl.value = 'Walk-in Customer';
            if (phoneEl) phoneEl.value = '';
            if (discEl) discEl.value = '0';
            this.renderCart();
            this.updateNavBadge();
            if (!skipConfirm) this.showToast('Cart cleared');
        }
    },

    renderCart() {
        const container = document.getElementById('cartItemsScroll');
        const countBadge = document.getElementById('cartCount');
        if (!container) return;

        // Persist cart state to local storage
        this.saveCart();

        if (countBadge) countBadge.innerText = this.cart.length;

        if (this.cart.length === 0) {
            container.innerHTML = `
                <div class="empty-cart-state">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="8" cy="21" r="1"></circle>
                        <circle cx="19" cy="21" r="1"></circle>
                        <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path>
                    </svg>
                    <p style="margin-top: 0.75rem; font-weight: 600;">Cart is empty</p>
                    <span style="font-size: 0.8rem;">Select products or click on 3D shelves to bill</span>
                </div>
            `;
            this.updateCartTotals(0);
            return;
        }

        let subtotal = 0;
        let html = '';

        this.cart.forEach((item, index) => {
            const multiplier = this.getMultiplier(item.base_unit, item.unit);
            const baseQty = item.qty * multiplier;
            const lineTotal = baseQty * item.selling_price;
            const itemDisc = parseFloat(item.discount) || 0;
            const netLineTotal = Math.max(0, lineTotal - itemDisc);

            subtotal += lineTotal;

            // Unit options for this item
            let unitOptions = `<option value="${item.unit}">${item.unit}</option>`;
            if (item.base_unit === 'kg') {
                unitOptions = `
                    <option value="kg" ${item.unit === 'kg' ? 'selected' : ''}>kg</option>
                    <option value="g" ${item.unit === 'g' ? 'selected' : ''}>g</option>
                `;
            } else if (item.base_unit === 'l') {
                unitOptions = `
                    <option value="l" ${item.unit === 'l' ? 'selected' : ''}>l</option>
                    <option value="ml" ${item.unit === 'ml' ? 'selected' : ''}>ml</option>
                `;
            }

            let itemDiscountRow = '';
            if (this.discountMode === 'item') {
                itemDiscountRow = `
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 0.35rem; padding-top: 0.35rem; border-top: 1px dashed #e2e8f0; font-size: 0.78rem;">
                        <span style="color: #64748b;">Item Discount:</span>
                        <div style="display: flex; align-items: center; gap: 0.3rem;">
                            <span>${this.settings.currency}</span>
                            <input type="number" min="0" step="1" value="${itemDisc > 0 ? itemDisc : ''}"
                                placeholder="0"
                                style="width: 70px; padding: 0.2rem 0.4rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem; text-align: right;"
                                oninput="App.updateItemDiscount(${index}, this.value)">
                        </div>
                    </div>
                `;
            }

            let priceDisplayHtml = `${this.settings.currency}${lineTotal.toFixed(2)}`;
            if (this.discountMode === 'item' && itemDisc > 0) {
                priceDisplayHtml = `<span style="font-size: 0.78rem; text-decoration: line-through; color: #94a3b8; margin-right: 4px;">${this.settings.currency}${lineTotal.toFixed(2)}</span>${this.settings.currency}${netLineTotal.toFixed(2)}`;
            }

            html += `
                <div class="cart-item-card">
                    <div class="cart-item-top">
                        <div>
                            <div class="cart-item-name">${item.name}</div>
                            <div class="cart-item-rate">${this.settings.currency}${item.selling_price.toFixed(2)} / ${item.base_unit}</div>
                        </div>
                        <button type="button" class="cart-remove-btn" onclick="App.removeFromCart(${index})" title="Remove item">
                            ✕
                        </button>
                    </div>
                    <div class="cart-item-bottom">
                        <div style="display: flex; align-items: center;">
                            <div class="qty-control-group">
                                <button type="button" class="qty-btn" onclick="App.updateCartQty(${index}, -1)" title="Decrease quantity">−</button>
                                <input type="number" class="qty-val-input" value="${item.qty}" min="0.01" step="any"
                                    onfocus="this.select()"
                                    oninput="App.onCartItemQtyInput(${index}, this.value)"
                                    onchange="App.setCartItemQty(${index}, this.value)"
                                    onkeydown="if(event.key==='Enter') this.blur()"
                                    title="Click to type quantity directly">
                                <button type="button" class="qty-btn" onclick="App.updateCartQty(${index}, 1)" title="Increase quantity">+</button>
                            </div>
                            <select class="cart-unit-select" onchange="App.changeCartItemUnit(${index}, this.value)">
                                ${unitOptions}
                            </select>
                        </div>
                        <div class="cart-item-total">${priceDisplayHtml}</div>
                    </div>
                    ${itemDiscountRow}
                </div>
            `;
        });

        container.innerHTML = html;
        this.updateCartTotals(subtotal);
    },

    updateCartTotals(subtotal) {
        const discountInput = document.getElementById('cartDiscount');
        const taxRateInput = document.getElementById('cartTaxRate');

        let discount = 0;
        if (this.discountMode === 'item') {
            discount = this.cart.reduce((sum, it) => sum + (parseFloat(it.discount) || 0), 0);
            if (discountInput) discountInput.value = discount;
            const itemSumEl = document.getElementById('itemDiscountSumDisplay');
            if (itemSumEl) itemSumEl.innerText = `${this.settings.currency}${discount.toFixed(2)}`;
        } else {
            discount = parseFloat(discountInput ? discountInput.value : 0) || 0;
        }

        let taxRate = 0;
        if (this.isGstEnabled) {
            taxRate = parseFloat(taxRateInput ? taxRateInput.value : 0) || 0;
        }

        const discountedSubtotal = Math.max(0, subtotal - discount);
        const taxAmount = (discountedSubtotal * (taxRate / 100));
        const grandTotal = discountedSubtotal + taxAmount;

        const subtotalEl = document.getElementById('cartSubtotalDisplay');
        const taxEl = document.getElementById('cartTaxDisplay');
        const taxLabelEl = document.getElementById('cartTaxLabel');
        const grandTotalEl = document.getElementById('cartGrandTotalDisplay');

        if (subtotalEl) subtotalEl.innerText = `${this.settings.currency}${subtotal.toFixed(2)}`;
        if (taxLabelEl) {
            taxLabelEl.innerText = this.isGstEnabled ? `GST Tax (${taxRate}%)` : 'GST (OFF)';
        }
        if (taxEl) {
            taxEl.innerText = this.isGstEnabled 
                ? `+${this.settings.currency}${taxAmount.toFixed(2)}` 
                : `${this.settings.currency}0.00`;
        }
        if (grandTotalEl) grandTotalEl.innerText = `${this.settings.currency}${grandTotal.toFixed(2)}`;

        const floatingTotal = document.getElementById('floatingCartTotal');
        if (floatingTotal) floatingTotal.innerText = `${this.settings.currency}${grandTotal.toFixed(2)}`;
    },

    // ================= CHECKOUT & BILL COMPLETION =================
    async checkout() {
        if (this.cart.length === 0) {
            this.showToast('Cart is empty. Add products to bill!', 'error');
            this.beep('error');
            return;
        }

        const customerName = document.getElementById('custName') ? document.getElementById('custName').value.trim() : 'Walk-in Customer';
        const customerPhone = document.getElementById('custPhone') ? document.getElementById('custPhone').value.trim() : '';
        const paymentMethod = document.getElementById('paymentMethod') ? document.getElementById('paymentMethod').value : 'Cash';
        const discount = parseFloat(document.getElementById('cartDiscount').value) || 0;
        const taxRate = this.isGstEnabled ? (parseFloat(document.getElementById('cartTaxRate').value) || 0) : 0;

        const payload = {
            customer_name: customerName || 'Walk-in Customer',
            customer_phone: customerPhone,
            payment_method: paymentMethod,
            discount: discount,
            tax_rate: taxRate,
            cart: this.cart
        };

        const btn = document.getElementById('checkoutBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerText = 'Processing Bill...';
        }

        try {
            const res = await fetch('api.php?action=create_bill', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (data.success) {
                this.beep('success');
                this.showToast(`Bill ${data.bill_number} generated successfully!`);
                this.cart = [];
                try {
                    localStorage.removeItem('pos_billing_cart');
                    localStorage.removeItem('pos_billing_cart_meta');
                } catch (e) {}
                const nameEl = document.getElementById('custName');
                const phoneEl = document.getElementById('custPhone');
                const discEl = document.getElementById('cartDiscount');
                if (nameEl) nameEl.value = 'Walk-in Customer';
                if (phoneEl) phoneEl.value = '';
                if (discEl) discEl.value = '0';
                this.renderCart();
                this.updateNavBadge();
                this.loadProducts(); // Refresh stock in catalog

                this.lastBillId = data.bill_id;
                try {
                    localStorage.setItem('pos_last_bill_id', data.bill_id);
                } catch (e) {}
                const reprintBtn = document.getElementById('reprintLastBillBtn');
                if (reprintBtn) reprintBtn.style.display = 'inline-flex';

                // Open Receipt Modal
                this.openBillReceiptModal(data.bill_id);
            } else {
                this.beep('error');
                this.showToast(data.error || 'Failed to process bill', 'error');
            }
        } catch (e) {
            this.beep('error');
            this.showToast('Network error or server unavailable', 'error');
            console.error('Checkout error:', e);
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerText = '⚡ Complete & Print Bill';
            }
        }
    },

    // ================= RECEIPT VIEW & PDF DOWNLOAD =================
    async openBillReceiptModal(billId) {
        try {
            const res = await fetch(`api.php?action=get_bill_details&id=${billId}`);
            const data = await res.json();
            if (!data.success) {
                this.showToast('Could not load bill details', 'error');
                return;
            }

            const bill = data.data;
            this.renderReceiptHtml(bill);

            const modal = document.getElementById('receiptModal');
            if (modal) modal.classList.add('active');
        } catch (e) {
            console.error('Error opening receipt:', e);
        }
    },

    renderReceiptHtml(bill) {
        this.currentBillData = bill;
        const container = document.getElementById('receiptContent');
        if (!container) return;

        const store = bill.store || (typeof globalStoreSettings !== 'undefined' ? globalStoreSettings : {}) || {};
        const storeName = store.store_name || 'BillKartX Smart Billing';
        const storeAddr = store.store_address || '';
        const storePhone = store.store_phone || '';
        const isGstActive = (store.enable_gst !== '0') && (parseFloat(bill.tax) > 0 || store.enable_gst === '1');
        const storeGst = isGstActive ? (store.store_gst || '') : '';
        let headerTag = store.receipt_header || 'Tax Invoice / Retail Bill';
        if (!isGstActive && headerTag === 'Tax Invoice / Retail Bill') {
            headerTag = 'Retail Bill';
        }
        const footerMsg = store.receipt_footer || '*** Thank You! Visit Again ***';
        const curr = (store && store.currency_symbol) || this.settings.currency || '₹';

        let itemsHtml = '';
        (bill.items || []).forEach((it, idx) => {
            const itemDisc = parseFloat(it.item_discount || it.discount || 0);
            const unit = it.selected_unit || '';
            const qtyText = `${it.entered_qty}${unit ? ' ' + unit : ''}`;
            itemsHtml += `
                <tr>
                    <td style="text-align: left; vertical-align: top;">
                        ${idx + 1}. ${it.product_name}
                        ${itemDisc > 0 ? `<br><small style="color: #16a34a; font-size: 0.72rem;">(Disc: -${curr}${itemDisc.toFixed(2)})</small>` : ''}
                    </td>
                    <td class="text-center" style="text-align: center; vertical-align: top; white-space: nowrap;">${qtyText}</td>
                    <td class="text-right" style="text-align: right; vertical-align: top; white-space: nowrap; font-weight: 600;">${curr}${parseFloat(it.item_total).toFixed(2)}</td>
                </tr>
            `;
        });

        container.innerHTML = `
            <div id="receiptPrintArea" class="receipt-printable">
                <div class="receipt-header">
                    <div style="text-align: center; margin-bottom: 6px;">
                        <img src="assets/BillKartX Smart Billing Logo.png" alt="BillKartX" style="max-height: 38px; max-width: 170px; object-fit: contain;">
                    </div>
                    <div class="receipt-title">${storeName}</div>
                    ${storeAddr ? `<div style="font-size: 0.8rem;">${storeAddr}</div>` : ''}
                    ${storePhone ? `<div style="font-size: 0.8rem;">Phone: ${storePhone}</div>` : ''}
                    ${storeGst ? `<div style="font-size: 0.8rem;">GST: ${storeGst}</div>` : ''}
                    <div class="receipt-divider"></div>
                    <div style="font-weight: 700; font-size: 0.85rem; margin-bottom: 4px;">${headerTag}</div>
                    ${bill.is_reprint ? `<div class="reprint-badge" style="text-align: center; font-weight: 800; font-size: 0.82rem; border: 1px dashed #000; padding: 2px 4px; margin: 4px 0; background: #fef2f2; color: #b91c1c;">*** DUPLICATE / REPRINT ***</div>` : ''}
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                        <span><strong>Bill:</strong> ${bill.bill_number}</span>
                        <span>${bill.created_at || ''}</span>
                    </div>
                    ${((bill.customer_name && bill.customer_name.trim().toLowerCase() !== 'walk-in customer' && bill.customer_name.trim().toLowerCase() !== 'walk-in') || (bill.customer_phone && bill.customer_phone.trim())) ? `
                        <div style="display: flex; justify-content: space-between; font-size: 0.8rem;">
                            <span><strong>Customer:</strong> ${bill.customer_name}</span>
                            <span>${bill.customer_phone || ''}</span>
                        </div>
                    ` : ''}
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
                        <span>${curr}${parseFloat(bill.subtotal).toFixed(2)}</span>
                    </div>
                    ${parseFloat(bill.discount) > 0 ? `
                        <div style="display: flex; justify-content: space-between; color: #16a34a;">
                            <span>Discount:</span>
                            <span>-${curr}${parseFloat(bill.discount).toFixed(2)}</span>
                        </div>
                    ` : ''}
                    ${parseFloat(bill.tax) > 0 ? `
                        <div style="display: flex; justify-content: space-between;">
                            <span>Tax (GST):</span>
                            <span>+${curr}${parseFloat(bill.tax).toFixed(2)}</span>
                        </div>
                    ` : ''}
                    <div class="receipt-divider"></div>
                    <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.15rem;">
                        <span>Grand Total:</span>
                        <span>${curr}${parseFloat(bill.grand_total).toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-top: 4px;">
                        <span>Payment Mode:</span>
                        <span><strong>${bill.payment_method}</strong></span>
                    </div>
                </div>

                <div class="receipt-divider"></div>
                <div style="text-align: center; font-size: 0.8rem; margin-top: 10px;">
                    ${footerMsg}
                </div>
            </div>
        `;
    },

    async printReceiptBluetooth() {
        if (!window.BluetoothPrinter) {
            this.showToast('Bluetooth driver not loaded', 'error');
            return;
        }

        if (!this.currentBillData) {
            this.showToast('No active bill to print', 'error');
            return;
        }

        // Try auto-reconnect first, otherwise prompt
        if (!BluetoothPrinter.isConnected()) {
            this.showToast('Connecting to Bluetooth printer...');
            const ok = await BluetoothPrinter.connectOrPrompt();
            if (!ok) {
                this.showToast('Could not connect to Bluetooth printer', 'warning');
                return;
            }
        }

        try {
            const store = this.currentBillData.store || (typeof globalStoreSettings !== 'undefined' ? globalStoreSettings : {});
            const isTamil = BluetoothPrinter.hasTamil(this.currentBillData);
            this.showToast(isTamil ? 'Rendering Tamil graphics for HOP-HL58...' : 'Sending to 58mm Bluetooth printer...');
            await BluetoothPrinter.printBill(this.currentBillData, store, 'receiptPrintArea');
            this.showToast('Printed successfully via Bluetooth!');
            this.beep('success');
        } catch (err) {
            this.showToast('Bluetooth Print failed: ' + err.message, 'error');
            this.beep('error');
        }
    },

    printReceipt() {
        this.printThermalReceipt(false);
    },

    async printThermalReceipt(isDuplicate = false) {
        if (!this.currentBillData) {
            this.showToast('No active bill to print', 'error');
            return;
        }

        if (isDuplicate) {
            this.currentBillData.is_reprint = true;
            this.renderReceiptHtml(this.currentBillData);
        }

        // If Bluetooth is supported, prioritize direct Bluetooth ESC/POS printing!
        if (window.BluetoothPrinter && BluetoothPrinter.isSupported()) {
            // First check if already connected or can auto-reconnect silently
            const connected = await BluetoothPrinter.ensureConnected();
            if (connected) {
                await this.printReceiptBluetooth();
                return;
            }

            // If a Bluetooth printer was paired previously, connect to it
            if (BluetoothPrinter.hasPairedPrinter()) {
                this.showToast('Reconnecting Bluetooth printer...');
                const ok = await BluetoothPrinter.connectOrPrompt();
                if (ok) {
                    await this.printReceiptBluetooth();
                    return;
                }
            }
        }

        // Otherwise print via clean 58mm isolated iframe (browser print dialog)
        this.printViaThermalIframe('receiptPrintArea');
    },

    async reprintReceipt(isDuplicate = true) {
        if (!this.currentBillData) {
            this.showToast('No bill to reprint', 'error');
            return;
        }
        await this.printThermalReceipt(isDuplicate);
        this.showToast(isDuplicate ? 'Printing duplicate copy...' : 'Reprinting bill...');
    },

    reprintLastBill() {
        const billId = this.lastBillId || localStorage.getItem('pos_last_bill_id');
        if (!billId) {
            this.showToast('No recent bill found to reprint', 'error');
            return;
        }
        this.openBillReceiptModal(billId);
    },

    printViaThermalIframe(elementId = 'receiptPrintArea') {
        const printArea = document.getElementById(elementId);
        if (!printArea) {
            window.print();
            return;
        }

        let iframe = document.getElementById('posThermalPrintIframe');
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = 'posThermalPrintIframe';
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
                        font-family: 'Nirmala UI', 'Noto Sans Tamil', 'Latha', 'Tamil Sangam MN', Arial, sans-serif;
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
    },

    async downloadReceiptPDF() {
        const element = document.getElementById('receiptPrintArea');
        if (!element) return;

        try {
            this.showToast('Generating PDF...');
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
            pdf.save(`Bill_${Date.now()}.pdf`);
            this.showToast('PDF Downloaded successfully!');
        } catch (e) {
            console.error('PDF error:', e);
            window.print();
        }
    },

    // ================= MODAL HELPERS =================
    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.classList.remove('active');
    },

    bindEvents() {
        // Search bar
        const searchInput = document.getElementById('searchProduct');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.searchQuery = e.target.value.trim();
                this.loadProducts();
            });
        }

        // Customer details inputs
        const nameInput = document.getElementById('custName');
        if (nameInput) {
            nameInput.addEventListener('input', () => this.saveCart());
        }

        const phoneInput = document.getElementById('custPhone');
        if (phoneInput) {
            phoneInput.addEventListener('input', () => this.saveCart());
        }

        // Discount / Tax inputs in cart
        const discInput = document.getElementById('cartDiscount');
        if (discInput) {
            discInput.addEventListener('input', () => this.renderCart());
        }

        const taxInput = document.getElementById('cartTaxRate');
        if (taxInput) {
            taxInput.addEventListener('input', () => this.renderCart());
        }

        // Modal Qty input
        const modalQty = document.getElementById('modalQtyInput');
        if (modalQty) {
            modalQty.addEventListener('input', () => this.calcModalPrice());
        }

        const modalUnit = document.getElementById('modalUnitSelect');
        if (modalUnit) {
            modalUnit.addEventListener('change', () => this.calcModalPrice());
        }
    }
};

window.App = App;
document.addEventListener('DOMContentLoaded', () => App.init());

