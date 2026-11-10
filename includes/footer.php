    <!-- Global Product Return & Refund Modal Component -->
    <?php require_once __DIR__ . '/return_modal.php'; ?>

    <!-- Online/Offline State Script & Service Worker & Global Cart Sync -->
    <script>
        function updateNetworkStatus() {
            const badge = document.getElementById('networkStatusBadge');
            const text = document.getElementById('networkStatusText');
            if (!badge || !text) return;

            if (navigator.onLine) {
                badge.className = 'status-badge online';
                text.innerText = 'Online';
            } else {
                badge.className = 'status-badge offline';
                text.innerText = 'Offline Mode';
            }
        }

        function updateGlobalNavCartBadge() {
            try {
                const badge = document.getElementById('navCartBadge');
                if (!badge) return;
                const saved = localStorage.getItem('pos_billing_cart');
                if (saved) {
                    const cart = JSON.parse(saved);
                    if (Array.isArray(cart) && cart.length > 0) {
                        badge.textContent = cart.length;
                        badge.style.display = 'inline-flex';
                        return;
                    }
                }
                badge.style.display = 'none';
            } catch (e) {
                // Ignore parse errors
            }
        }

        function toggleMobileNav() {
            const menu = document.getElementById('navLinksMenu');
            if (menu) {
                menu.classList.toggle('open');
            }
        }

        // Close mobile nav menu when clicking outside
        document.addEventListener('click', (e) => {
            const menu = document.getElementById('navLinksMenu');
            const toggle = document.getElementById('navToggleBtn');
            if (menu && menu.classList.contains('open')) {
                if (toggle && !toggle.contains(e.target) && !menu.contains(e.target)) {
                    menu.classList.remove('open');
                }
            }
        });

        window.addEventListener('online', updateNetworkStatus);
        window.addEventListener('offline', updateNetworkStatus);
        window.addEventListener('storage', updateGlobalNavCartBadge);
        updateNetworkStatus();
        updateGlobalNavCartBadge();

        // ================= GLOBAL SETTINGS & BLUETOOTH PRINTER HELPERS =================
        let globalStoreSettings = {};

        async function loadGlobalSettings() {
            try {
                const res = await fetch('api.php?action=get_settings');
                const data = await res.json();
                if (data.success && data.data) {
                    globalStoreSettings = data.data;
                    populateSettingsForm(data.data);
                }
            } catch (e) {
                console.error('Failed to load settings:', e);
            }
        }

        function toggleSettingGstRate(val) {
            const group = document.getElementById('settingTaxRateGroup');
            const input = document.getElementById('settingTaxRate');
            if (!group || !input) return;
            if (val === '0') {
                group.style.opacity = '0.5';
                input.disabled = true;
            } else {
                group.style.opacity = '1';
                input.disabled = false;
            }
        }

        function stepFontSize(id, delta) {
            const el = document.getElementById(id);
            if (!el) return;
            const min = parseInt(el.min || 8, 10);
            const max = parseInt(el.max || 32, 10);
            let val = (parseInt(el.value, 10) || min) + delta;
            val = Math.max(min, Math.min(max, val));
            el.value = val;
            updateReceiptSamplePreview();
        }

        function resetAllReceiptFontsToDefault() {
            const defaults = {
                fontShopName: 18,
                fontShopAddress: 12,
                fontShopContact: 11,
                fontReceiptHeader: 13,
                fontProductName: 18,
                fontQtyPrice: 13,
                fontGrandTotal: 18,
                fontFooter: 11
            };
            for (const [id, val] of Object.entries(defaults)) {
                const el = document.getElementById(id);
                if (el) el.value = val;
            }
            updateReceiptSamplePreview();
        }

        function updateReceiptSamplePreview() {
            // 1. Read Font Sizes
            const fShopName = parseInt(document.getElementById('fontShopName')?.value || 18, 10);
            const fShopAddress = parseInt(document.getElementById('fontShopAddress')?.value || 12, 10);
            const fShopContact = parseInt(document.getElementById('fontShopContact')?.value || 11, 10);
            const fReceiptHeader = parseInt(document.getElementById('fontReceiptHeader')?.value || 13, 10);
            const fProductName = parseInt(document.getElementById('fontProductName')?.value || 18, 10);
            const fQtyPrice = parseInt(document.getElementById('fontQtyPrice')?.value || 13, 10);
            const fGrandTotal = parseInt(document.getElementById('fontGrandTotal')?.value || 18, 10);
            const fFooter = parseInt(document.getElementById('fontFooter')?.value || 11, 10);

            // Sync hidden input
            const syncFontEl = document.getElementById('settingReceiptFontSize');
            if (syncFontEl) syncFontEl.value = fProductName;

            // 2. Read Text Fields
            const sName = document.getElementById('settingStoreName')?.value.trim() || 'Karthi Billing & Inventory';
            const sAddr = document.getElementById('settingStoreAddress')?.value.trim() || '123 Main Bazaar Road, Anna Nagar, Nilakottai';
            const sPhone = document.getElementById('settingStorePhone')?.value.trim() || '+91 98765 43210';
            const sGst = document.getElementById('settingStoreGst')?.value.trim() || '33AAAAA0000A1Z5';
            const sTag = document.getElementById('settingReceiptHeader')?.value.trim() || 'Tax Invoice / Retail Bill';
            const sFooter = document.getElementById('settingReceiptFooter')?.value.trim() || '*** Thank you for shopping with us! Visit again ***';
            const sCurr = document.getElementById('settingCurrency')?.value.trim() || '₹';

            // 3. Update DOM of Sample Receipt
            const elName = document.getElementById('sampleStoreName');
            if (elName) {
                elName.textContent = sName;
                elName.style.fontSize = fShopName + 'px';
            }
            const elAddr = document.getElementById('sampleStoreAddress');
            if (elAddr) {
                elAddr.textContent = sAddr;
                elAddr.style.fontSize = fShopAddress + 'px';
                elAddr.style.display = sAddr ? 'block' : 'none';
            }
            const elPhone = document.getElementById('sampleStorePhone');
            if (elPhone) {
                elPhone.textContent = sPhone ? `Phone: ${sPhone}` : '';
                elPhone.style.fontSize = fShopContact + 'px';
                elPhone.style.display = sPhone ? 'block' : 'none';
            }
            const elGst = document.getElementById('sampleStoreGst');
            if (elGst) {
                elGst.textContent = sGst ? `GST: ${sGst}` : '';
                elGst.style.fontSize = fShopContact + 'px';
                elGst.style.display = sGst ? 'block' : 'none';
            }
            const elTag = document.getElementById('sampleReceiptTag');
            if (elTag) {
                elTag.textContent = sTag;
                elTag.style.fontSize = fReceiptHeader + 'px';
            }

            // Contact font on bill/date/customer lines
            document.querySelectorAll('#sampleReceiptContainer .receipt-contact').forEach(el => {
                el.style.fontSize = fShopContact + 'px';
            });

            // Qty, Price & Table header font
            document.querySelectorAll('#sampleReceiptTable th, #sampleReceiptTable td.sample-qty, #sampleReceiptTable td.sample-price, #sampleReceiptContainer .sample-total-line').forEach(el => {
                el.style.fontSize = fQtyPrice + 'px';
            });

            // Product Name font
            document.querySelectorAll('#sampleReceiptItems .receipt-item-name').forEach(el => {
                el.style.fontSize = fProductName + 'px';
            });

            // Grand Total font
            const elGt = document.getElementById('sampleGrandTotal');
            if (elGt) {
                elGt.style.fontSize = fGrandTotal + 'px';
            }

            // Currency symbol update
            document.querySelectorAll('#sampleReceiptContainer .sample-curr').forEach(el => {
                el.textContent = sCurr;
            });

            // Footer font
            const elFt = document.getElementById('sampleFooter');
            if (elFt) {
                elFt.textContent = sFooter;
                elFt.style.fontSize = fFooter + 'px';
            }

            // 4. Update CSS custom properties on page
            document.documentElement.style.setProperty('--receipt-font-shop-name', fShopName + 'px');
            document.documentElement.style.setProperty('--receipt-font-shop-address', fShopAddress + 'px');
            document.documentElement.style.setProperty('--receipt-font-shop-contact', fShopContact + 'px');
            document.documentElement.style.setProperty('--receipt-font-receipt-header', fReceiptHeader + 'px');
            document.documentElement.style.setProperty('--receipt-font-product-name', fProductName + 'px');
            document.documentElement.style.setProperty('--receipt-item-font-size', fProductName + 'px');
            document.documentElement.style.setProperty('--receipt-font-qty-price', fQtyPrice + 'px');
            document.documentElement.style.setProperty('--receipt-font-grand-total', fGrandTotal + 'px');
            document.documentElement.style.setProperty('--receipt-font-footer', fFooter + 'px');
        }

        function printSampleThermalReceipt() {
            const printArea = document.getElementById('sampleReceiptContainer');
            if (!printArea) return;

            const fShopName = parseInt(document.getElementById('fontShopName')?.value || 18, 10);
            const fShopAddress = parseInt(document.getElementById('fontShopAddress')?.value || 12, 10);
            const fShopContact = parseInt(document.getElementById('fontShopContact')?.value || 11, 10);
            const fReceiptHeader = parseInt(document.getElementById('fontReceiptHeader')?.value || 13, 10);
            const fProductName = parseInt(document.getElementById('fontProductName')?.value || 18, 10);
            const fQtyPrice = parseInt(document.getElementById('fontQtyPrice')?.value || 13, 10);
            const fGrandTotal = parseInt(document.getElementById('fontGrandTotal')?.value || 18, 10);
            const fFooter = parseInt(document.getElementById('fontFooter')?.value || 11, 10);

            let iframe = document.getElementById('sampleThermalPrintIframe');
            if (!iframe) {
                iframe = document.createElement('iframe');
                iframe.id = 'sampleThermalPrintIframe';
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
                    <title>Sample 58mm Thermal Receipt</title>
                    <style>
                        @page { size: 58mm auto; margin: 0; }
                        * { box-sizing: border-box; margin: 0; padding: 0; }
                        body {
                            font-family: 'Nirmala UI', 'Noto Sans Tamil', 'Latha', 'Tamil Sangam MN', Arial, sans-serif;
                            font-size: ${fShopContact}px;
                            line-height: 1.25;
                            width: 58mm;
                            max-width: 58mm;
                            padding: 3mm 2mm;
                            color: #000;
                            background: #fff;
                        }
                        .receipt-title { font-size: ${fShopName}px !important; font-weight: 900; text-align: center; text-transform: uppercase; margin-bottom: 2px; }
                        .receipt-address { font-size: ${fShopAddress}px !important; text-align: center; }
                        .receipt-contact { font-size: ${fShopContact}px !important; }
                        .receipt-header-tag { font-size: ${fReceiptHeader}px !important; font-weight: 700; text-align: center; }
                        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
                        th { border-bottom: 1px solid #000; font-weight: bold; padding: 2px 0; font-size: ${fQtyPrice}px !important; }
                        td { padding: 2px 0; vertical-align: top; }
                        .receipt-item-name { font-size: ${fProductName}px !important; font-weight: 700; word-break: break-word; overflow-wrap: break-word; white-space: normal; }
                        .sample-qty, .sample-price, .sample-total-line { font-size: ${fQtyPrice}px !important; }
                        .receipt-grand-total { font-size: ${fGrandTotal}px !important; font-weight: 900; }
                        .receipt-footer { font-size: ${fFooter}px !important; text-align: center; }
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

        function populateSettingsForm(s) {
            const setVal = (id, val) => {
                const el = document.getElementById(id);
                if (el && val !== undefined) el.value = val;
            };
            setVal('settingStoreName', s.store_name || '');
            setVal('settingStoreAddress', s.store_address || '');
            setVal('settingStorePhone', s.store_phone || '');
            setVal('settingStoreGst', s.store_gst || '');
            setVal('settingReceiptHeader', s.receipt_header || 'Tax Invoice / Retail Bill');
            setVal('settingReceiptFooter', s.receipt_footer || '*** Thank you for shopping with us! Visit again ***');

            // Typography Fonts
            setVal('fontShopName', s.font_shop_name || '18');
            setVal('fontShopAddress', s.font_shop_address || '12');
            setVal('fontShopContact', s.font_shop_contact || '11');
            setVal('fontReceiptHeader', s.font_receipt_header || '13');
            const pFont = s.font_product_name || s.receipt_font_size || '18';
            setVal('fontProductName', pFont);
            setVal('settingReceiptFontSize', pFont);
            setVal('fontQtyPrice', s.font_qty_price || '13');
            setVal('fontGrandTotal', s.font_grand_total || '18');
            setVal('fontFooter', s.font_footer || '11');

            setVal('settingEnableGst', s.enable_gst !== undefined ? s.enable_gst : '1');
            setVal('settingTaxRate', s.tax_rate !== undefined ? s.tax_rate : '5');
            setVal('settingCurrency', s.currency_symbol || '₹');
            toggleSettingGstRate(s.enable_gst !== undefined ? s.enable_gst : '1');
            updateReceiptSamplePreview();
        }

        function openSettingsModal() {
            loadGlobalSettings();
            if (window.BluetoothPrinter) {
                BluetoothPrinter.updateUIStatus();
            }
            const modeEl = document.getElementById('settingBtPrintMode');
            if (modeEl && typeof localStorage !== 'undefined') {
                modeEl.value = localStorage.getItem('pos_bt_print_mode') || 'auto';
            }
            const modal = document.getElementById('settingsModal');
            if (modal) modal.classList.add('active');
            setTimeout(updateReceiptSamplePreview, 50);
        }

        function closeSettingsModal() {
            const modal = document.getElementById('settingsModal');
            if (modal) modal.classList.remove('active');
        }

        async function saveGlobalSettings(e) {
            if (e) e.preventDefault();
            const btn = document.getElementById('saveSettingsBtn');
            if (btn) {
                btn.disabled = true;
                btn.innerText = 'Saving...';
            }

            const settings = {
                store_name: document.getElementById('settingStoreName').value.trim(),
                store_address: document.getElementById('settingStoreAddress').value.trim(),
                store_phone: document.getElementById('settingStorePhone').value.trim(),
                store_gst: document.getElementById('settingStoreGst').value.trim(),
                receipt_header: document.getElementById('settingReceiptHeader').value.trim(),
                receipt_footer: document.getElementById('settingReceiptFooter').value.trim(),
                font_shop_name: document.getElementById('fontShopName') ? document.getElementById('fontShopName').value : '18',
                font_shop_address: document.getElementById('fontShopAddress') ? document.getElementById('fontShopAddress').value : '12',
                font_shop_contact: document.getElementById('fontShopContact') ? document.getElementById('fontShopContact').value : '11',
                font_receipt_header: document.getElementById('fontReceiptHeader') ? document.getElementById('fontReceiptHeader').value : '13',
                font_product_name: document.getElementById('fontProductName') ? document.getElementById('fontProductName').value : '18',
                receipt_font_size: document.getElementById('fontProductName') ? document.getElementById('fontProductName').value : '18',
                font_qty_price: document.getElementById('fontQtyPrice') ? document.getElementById('fontQtyPrice').value : '13',
                font_grand_total: document.getElementById('fontGrandTotal') ? document.getElementById('fontGrandTotal').value : '18',
                font_footer: document.getElementById('fontFooter') ? document.getElementById('fontFooter').value : '11',
                enable_gst: document.getElementById('settingEnableGst') ? document.getElementById('settingEnableGst').value : '1',
                tax_rate: document.getElementById('settingTaxRate').value.trim(),
                currency_symbol: document.getElementById('settingCurrency').value.trim()
            };

            try {
                const res = await fetch('api.php?action=save_settings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ settings })
                });
                const data = await res.json();
                if (data.success) {
                    globalStoreSettings = Object.assign({}, globalStoreSettings, settings);
                    if (typeof localStorage !== 'undefined') {
                        for (const [k, v] of Object.entries(settings)) {
                            localStorage.setItem('pos_' + k, v);
                        }
                    }
                    updateReceiptSamplePreview();
                    if (window.App) {
                        App.settings.currency = settings.currency_symbol || '₹';
                        App.settings.taxRate = parseFloat(settings.tax_rate) || 5;
                        App.settings.enableGst = settings.enable_gst;
                        App.settings.receiptFontSize = parseInt(settings.font_product_name, 10) || 18;
                        if (typeof App.applyGlobalGstSetting === 'function') {
                            App.applyGlobalGstSetting(settings.enable_gst, settings.tax_rate);
                        } else {
                            if (App.renderCart) App.renderCart();
                        }
                        if (typeof App.loadProducts === 'function') App.loadProducts();
                    }
                    alert('Settings and receipt typography saved successfully!');
                    closeSettingsModal();
                } else {
                    alert(data.error || 'Failed to save settings');
                }
            } catch (err) {
                alert('Network or server error while saving settings');
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerText = '💾 Save All Settings';
                }
            }
        }

        async function handleBtConnect() {
            if (!window.BluetoothPrinter) return;
            try {
                const name = await BluetoothPrinter.connect();
                alert(`Bluetooth Thermal Printer '${name}' connected successfully!`);
            } catch (e) {
                if (e.name !== 'NotFoundError') {
                    alert('Bluetooth connection failed: ' + e.message);
                }
            }
        }

        function handleBtDisconnect() {
            if (window.BluetoothPrinter) {
                BluetoothPrinter.disconnect(false);
                alert('Bluetooth printer disconnected.');
            }
        }

        function handleBtForget() {
            if (confirm('Clear remembered Bluetooth printer? You will be prompted to select a device next time.')) {
                if (window.BluetoothPrinter) {
                    BluetoothPrinter.disconnect(true);
                    alert('Remembered printer cleared.');
                }
            }
        }

        async function handleBtTestPrint() {
            if (!window.BluetoothPrinter) return;
            if (!BluetoothPrinter.isConnected()) {
                const ok = await BluetoothPrinter.connectOrPrompt();
                if (!ok) {
                    alert('Please connect your Bluetooth 58mm printer first.');
                    return;
                }
            }
            try {
                const store = {
                    store_name: document.getElementById('settingStoreName') ? document.getElementById('settingStoreName').value : (globalStoreSettings.store_name || 'BillKartX Smart Billing'),
                    store_address: document.getElementById('settingStoreAddress') ? document.getElementById('settingStoreAddress').value : globalStoreSettings.store_address,
                    store_phone: document.getElementById('settingStorePhone') ? document.getElementById('settingStorePhone').value : globalStoreSettings.store_phone,
                    store_gst: document.getElementById('settingStoreGst') ? document.getElementById('settingStoreGst').value : globalStoreSettings.store_gst,
                    receipt_footer: document.getElementById('settingReceiptFooter') ? document.getElementById('settingReceiptFooter').value : globalStoreSettings.receipt_footer
                };
                await BluetoothPrinter.printTest(store);
                alert('Test print sent to 58mm Bluetooth printer!');
            } catch (e) {
                alert('Error printing test receipt: ' + e.message);
            }
        }

        async function handleBtTamilTestPrint() {
            if (!window.BluetoothPrinter) return;
            if (!BluetoothPrinter.isConnected()) {
                const ok = await BluetoothPrinter.connectOrPrompt();
                if (!ok) {
                    alert('Please connect your HOP-HL58 Bluetooth printer first.');
                    return;
                }
            }
            try {
                const store = {
                    store_name: document.getElementById('settingStoreName') ? document.getElementById('settingStoreName').value : (globalStoreSettings.store_name || 'பில்கார்ட் எக்ஸ் பில்லிங்'),
                    store_address: document.getElementById('settingStoreAddress') ? document.getElementById('settingStoreAddress').value : globalStoreSettings.store_address,
                    store_phone: document.getElementById('settingStorePhone') ? document.getElementById('settingStorePhone').value : globalStoreSettings.store_phone,
                    store_gst: document.getElementById('settingStoreGst') ? document.getElementById('settingStoreGst').value : globalStoreSettings.store_gst,
                    receipt_footer: document.getElementById('settingReceiptFooter') ? document.getElementById('settingReceiptFooter').value : globalStoreSettings.receipt_footer
                };
                alert('Sending Tamil test print to HOP-HL58 Bluetooth printer...');
                await BluetoothPrinter.printTestTamil(store);
                alert('Tamil test printed successfully on HOP-HL58!');
            } catch (e) {
                alert('Error printing Tamil test receipt: ' + e.message);
            }
        }

        function handleBtPrintModeChange(val) {
            if (typeof localStorage !== 'undefined') {
                localStorage.setItem('pos_bt_print_mode', val);
            }
        }

        // Initialize Settings on page load & Auto-reconnect Bluetooth
        document.addEventListener('DOMContentLoaded', () => {
            loadGlobalSettings();
            if (window.BluetoothPrinter) {
                BluetoothPrinter.updateUIStatus();
                BluetoothPrinter.autoReconnect().catch(() => {});
            }
        });

        // Register Service Worker for Offline PWA support
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('PWA ServiceWorker registered:', reg.scope))
                    .catch(err => console.log('ServiceWorker registration skipped:', err));
            });
        }
    </script>
</body>
</html>

