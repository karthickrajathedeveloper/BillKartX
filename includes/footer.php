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
            setVal('settingEnableGst', s.enable_gst !== undefined ? s.enable_gst : '1');
            setVal('settingTaxRate', s.tax_rate !== undefined ? s.tax_rate : '5');
            setVal('settingCurrency', s.currency_symbol || '₹');
            toggleSettingGstRate(s.enable_gst !== undefined ? s.enable_gst : '1');
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
                    if (window.App) {
                        App.settings.currency = settings.currency_symbol || '₹';
                        App.settings.taxRate = parseFloat(settings.tax_rate) || 5;
                        App.settings.enableGst = settings.enable_gst;
                        if (typeof App.applyGlobalGstSetting === 'function') {
                            App.applyGlobalGstSetting(settings.enable_gst, settings.tax_rate);
                        } else {
                            if (App.renderCart) App.renderCart();
                        }
                        if (typeof App.loadProducts === 'function') App.loadProducts();
                    }
                    alert('Settings updated successfully!');
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

