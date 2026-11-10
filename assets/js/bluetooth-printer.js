/**
 * Web Bluetooth Thermal Printer (58mm ESC/POS) Driver
 * Direct wireless printing from browser to 58mm / 80mm Bluetooth receipt printers
 */

const BluetoothPrinter = {
    device: null,
    server: null,
    characteristic: null,
    printerName: (typeof localStorage !== 'undefined' ? localStorage.getItem('pos_bt_printer_name') : null),
    deviceId: (typeof localStorage !== 'undefined' ? localStorage.getItem('pos_bt_device_id') : null),
    columns: 32, // Standard for 58mm thermal printers (Font A)
    isConnecting: false,
    _onDisconnectedHandler: null,

    // Common Bluetooth Printer GATT Services & Characteristics
    SERVICES: [
        '000018f0-0000-1000-8000-00805f9b34fb', // Standard 16-bit 0x18F0
        '0000ffe0-0000-1000-8000-00805f9b34fb', // 0xFFE0 - Very common 58mm portable printers (GOOJPRT, POS-5802, etc.)
        '0000fee7-0000-1000-8000-00805f9b34fb', // 0xFEE7 - Tencent / POS-58
        '0000fff0-0000-1000-8000-00805f9b34fb', // 0xFFF0 - Xprinter / Milestone
        '0000ff00-0000-1000-8000-00805f9b34fb', // 0xFF00
        'e7810a71-73ae-499d-8c15-faa9aef0c3f2', // 128-bit Xprinter / Rongta
        '49535343-fe7d-4ae5-8fa9-9fafd205e455', // Nordic / ISSC transparent UART
        '0000e781-0000-1000-8000-00805f9b34fb',
        '000018f1-0000-1000-8000-00805f9b34fb',
        '0000af30-0000-1000-8000-00805f9b34fb',
        '0000ae30-0000-1000-8000-00805f9b34fb'
    ],

    isSupported() {
        return !!(navigator && navigator.bluetooth && typeof navigator.bluetooth.requestDevice === 'function');
    },

    isConnected() {
        return !!(this.device && this.device.gatt && this.device.gatt.connected && this.characteristic);
    },

    hasPairedPrinter() {
        return !!(this.deviceId || this.printerName);
    },

    getPrinterCurrency(rawCurr) {
        let curr = String(rawCurr || 'Rs.').trim();
        // Thermal receipt printers (58mm ESC/POS) cannot render Unicode ₹ glyph (prints â‚¹ or ?), map to ASCII 'Rs.'
        if (!curr || curr.includes('₹') || curr.charCodeAt(0) > 127) {
            return 'Rs.';
        }
        return curr;
    },

    getStatus() {
        return {
            supported: this.isSupported(),
            connected: this.isConnected(),
            printerName: this.printerName,
            deviceId: this.deviceId
        };
    },

    // Connect and initialize a specific BluetoothDevice
    async setupDevice(device) {
        if (!device) throw new Error('No Bluetooth device specified.');

        this.device = device;
        this.printerName = device.name || localStorage.getItem('pos_bt_printer_name') || 'Bluetooth Printer (58mm)';
        this.deviceId = device.id || null;

        if (typeof localStorage !== 'undefined') {
            localStorage.setItem('pos_bt_printer_name', this.printerName);
            if (this.deviceId) {
                localStorage.setItem('pos_bt_device_id', this.deviceId);
            }
        }

        // Attach disconnect event listener
        if (this._onDisconnectedHandler) {
            try { device.removeEventListener('gattserverdisconnected', this._onDisconnectedHandler); } catch (e) {}
        }
        this._onDisconnectedHandler = () => {
            console.log('Bluetooth printer disconnected event:', this.printerName);
            this.characteristic = null;
            this.updateUIStatus();
        };
        device.addEventListener('gattserverdisconnected', this._onDisconnectedHandler);

        // Connect GATT Server
        const server = await device.gatt.connect();
        this.server = server;

        // Discover writable characteristic
        let foundChar = null;
        let services = [];
        try {
            services = await server.getPrimaryServices();
        } catch (e) {
            console.warn('Could not enumerate all services, probing known service UUIDs...', e);
        }

        // Search in discovered services
        for (const service of services) {
            try {
                const characteristics = await service.getCharacteristics();
                for (const char of characteristics) {
                    if (char.properties && (char.properties.write || char.properties.writeWithoutResponse)) {
                        foundChar = char;
                        break;
                    }
                }
            } catch (e) {}
            if (foundChar) break;
        }

        // Search known fallback SERVICES
        if (!foundChar) {
            for (const serviceUuid of this.SERVICES) {
                try {
                    const service = await server.getPrimaryService(serviceUuid);
                    const characteristics = await service.getCharacteristics();
                    for (const char of characteristics) {
                        if (char.properties && (char.properties.write || char.properties.writeWithoutResponse)) {
                            foundChar = char;
                            break;
                        }
                    }
                } catch (e) {}
                if (foundChar) break;
            }
        }

        if (!foundChar) {
            throw new Error('No writable printer characteristic found on this Bluetooth device.');
        }

        this.characteristic = foundChar;
        this.updateUIStatus();
        return this.printerName;
    },

    // Request new Bluetooth Device (User dialog prompt)
    async connect() {
        if (!this.isSupported()) {
            throw new Error('Web Bluetooth is not supported on this browser. Please use Chrome/Edge or an Android Bluetooth browser.');
        }

        try {
            this.isConnecting = true;
            this.updateUIStatus('connecting');

            const device = await navigator.bluetooth.requestDevice({
                acceptAllDevices: true,
                optionalServices: this.SERVICES
            });

            const name = await this.setupDevice(device);
            return name;
        } catch (err) {
            console.error('Bluetooth Connection Error:', err);
            this.updateUIStatus();
            throw err;
        } finally {
            this.isConnecting = false;
        }
    },

    // Auto-reconnect on page load using saved browser permissions (Silent, No prompt)
    async autoReconnect() {
        if (!this.isSupported() || !navigator.bluetooth.getDevices) {
            return false;
        }
        if (this.isConnected() || this.isConnecting) return true;

        try {
            this.isConnecting = true;
            const devices = await navigator.bluetooth.getDevices();
            if (!devices || devices.length === 0) {
                this.updateUIStatus();
                return false;
            }

            const savedId = localStorage.getItem('pos_bt_device_id');
            const targetDevice = (savedId ? devices.find(d => d.id === savedId) : null) || devices[0];

            if (targetDevice) {
                this.device = targetDevice;
                this.printerName = targetDevice.name || localStorage.getItem('pos_bt_printer_name') || 'Bluetooth Printer (58mm)';
                await this.setupDevice(targetDevice);
                console.log('Bluetooth auto-reconnected successfully to', this.printerName);
                return true;
            }
        } catch (e) {
            // Browser may defer GATT connect until user gesture
            console.warn('Bluetooth auto-reconnect deferred until user interaction:', e.message);
        } finally {
            this.isConnecting = false;
            this.updateUIStatus();
        }
        return false;
    },

    // Ensures printer is connected before printing; auto-reconnects without prompting if possible
    async ensureConnected() {
        if (this.isConnected()) return true;

        if (this.isConnecting) {
            for (let i = 0; i < 20; i++) {
                await new Promise(r => setTimeout(r, 100));
                if (this.isConnected()) return true;
                if (!this.isConnecting) break;
            }
        }

        try {
            this.isConnecting = true;
            this.updateUIStatus('connecting');

            // 1. Reconnect to active device reference
            if (this.device && this.device.gatt) {
                try {
                    await this.setupDevice(this.device);
                    if (this.isConnected()) return true;
                } catch (e) {
                    console.warn('Re-connecting active device failed:', e.message);
                }
            }

            // 2. Query previously granted devices (silent reconnect)
            if (this.isSupported() && navigator.bluetooth.getDevices) {
                const devices = await navigator.bluetooth.getDevices();
                if (devices && devices.length > 0) {
                    const savedId = localStorage.getItem('pos_bt_device_id');
                    const target = (savedId ? devices.find(d => d.id === savedId) : null) || devices[0];
                    if (target) {
                        await this.setupDevice(target);
                        if (this.isConnected()) return true;
                    }
                }
            }
        } catch (e) {
            console.warn('ensureConnected failed:', e.message);
        } finally {
            this.isConnecting = false;
            this.updateUIStatus();
        }

        return this.isConnected();
    },

    // Try silent connect first, otherwise prompt user picker dialog
    async connectOrPrompt() {
        const connected = await this.ensureConnected();
        if (connected) return true;

        try {
            await this.connect();
            return this.isConnected();
        } catch (e) {
            if (e.name !== 'NotFoundError') {
                console.error('connectOrPrompt failed:', e);
            }
            return false;
        }
    },

    disconnect(forget = false) {
        if (this.device && this.device.gatt) {
            try {
                this.device.gatt.disconnect();
            } catch (e) {}
        }
        this.characteristic = null;
        this.server = null;
        if (forget) {
            this.device = null;
            this.printerName = null;
            this.deviceId = null;
            if (typeof localStorage !== 'undefined') {
                localStorage.removeItem('pos_bt_device_id');
                localStorage.removeItem('pos_bt_printer_name');
            }
        }
        this.updateUIStatus();
    },

    updateUIStatus(overrideState) {
        const statusEl = document.getElementById('btPrinterStatusBadge');
        const nameEl = document.getElementById('btPrinterName');
        const connectBtn = document.getElementById('btConnectBtn');
        const disconnectBtn = document.getElementById('btDisconnectBtn');
        const testBtn = document.getElementById('btTestPrintBtn');
        const forgetBtn = document.getElementById('btForgetBtn');

        const navBadge = document.getElementById('navBtPrinterBadge');
        const navText = document.getElementById('navBtPrinterText');

        const connected = this.isConnected();
        const connecting = overrideState === 'connecting' || this.isConnecting;

        if (statusEl) {
            if (connecting) {
                statusEl.className = 'status-badge';
                statusEl.style.background = '#fef3c7';
                statusEl.style.color = '#92400e';
                statusEl.innerHTML = `<span class="dot" style="background:#f59e0b;"></span> Connecting...`;
            } else if (connected) {
                statusEl.className = 'status-badge online';
                statusEl.style.background = '';
                statusEl.style.color = '';
                statusEl.innerHTML = `<span class="dot"></span> Connected`;
            } else {
                statusEl.className = 'status-badge offline';
                statusEl.style.background = '';
                statusEl.style.color = '';
                statusEl.innerHTML = `<span class="dot"></span> Not Connected`;
            }
        }

        if (nameEl) {
            if (connected) {
                nameEl.innerText = this.printerName || 'Bluetooth 58mm Printer';
            } else if (this.printerName) {
                nameEl.innerText = `${this.printerName} (Ready / Reconnect)`;
            } else {
                nameEl.innerText = 'None';
            }
        }

        if (connectBtn) {
            connectBtn.style.display = connected ? 'none' : 'inline-flex';
            if (this.hasPairedPrinter() && !connected) {
                connectBtn.innerHTML = `📶 Reconnect (${this.printerName || 'Printer'})`;
            } else {
                connectBtn.innerHTML = `📶 Connect Bluetooth Printer`;
            }
        }
        if (disconnectBtn) disconnectBtn.style.display = connected ? 'inline-flex' : 'none';
        if (forgetBtn) forgetBtn.style.display = this.hasPairedPrinter() ? 'inline-flex' : 'none';
        if (testBtn) testBtn.disabled = !connected && !this.hasPairedPrinter();

        // Navbar indicator if present
        if (navBadge && navText) {
            if (connected) {
                navBadge.style.display = 'inline-flex';
                navBadge.className = 'status-badge online';
                navText.innerText = `🖨️ ${this.printerName || 'Printer'}`;
            } else if (this.hasPairedPrinter()) {
                navBadge.style.display = 'inline-flex';
                navBadge.className = 'status-badge offline';
                navText.innerText = `🖨️ Disconnected`;
            } else {
                navBadge.style.display = 'none';
            }
        }
    },

    // Send raw byte chunks safely with rate limiting
    async sendBytes(bytes) {
        if (!this.isConnected()) {
            throw new Error('Bluetooth printer is not connected. Please connect from Settings.');
        }

        const CHUNK_SIZE = 120; // Safe chunk size for BLE MTU
        for (let i = 0; i < bytes.length; i += CHUNK_SIZE) {
            const chunk = bytes.slice(i, i + CHUNK_SIZE);
            if (this.characteristic.properties && this.characteristic.properties.writeWithoutResponse && typeof this.characteristic.writeValueWithoutResponse === 'function') {
                await this.characteristic.writeValueWithoutResponse(chunk);
            } else {
                await this.characteristic.writeValue(chunk);
            }
            // 25ms delay to prevent buffer overflows on micro thermal printers
            await new Promise(r => setTimeout(r, 25));
        }
    },

    // ESC/POS Command Helpers
    encoder: new TextEncoder(),

    padRow(left, right, width = 32) {
        left = String(left || '');
        right = String(right || '');
        const spaces = Math.max(1, width - left.length - right.length);
        return left + ' '.repeat(spaces) + right + '\n';
    },

    pad3Col(c1, c2, c3, width = 32) {
        c1 = String(c1 || '');
        c2 = String(c2 || '');
        c3 = String(c3 || '');
        const w3 = 8;
        const w2 = 7;
        const w1 = width - w2 - w3; // 17
        const col1 = c1.padEnd(w1, ' ').substring(0, w1);
        const col2 = c2.padStart(w2, ' ').substring(0, w2);
        const col3 = c3.padStart(w3, ' ').substring(0, w3);
        return col1 + col2 + col3 + '\n';
    },

    // 3-Column Item with clean word-wrapping (product starts on Line 1 with Qty & Price aligned)
    format3ColItem(name, qty, price, width = 32) {
        const wPrice = 8;
        const wQty = 7;
        const wItem = width - wPrice - wQty; // 17 chars

        const colQty = String(qty || '').trim().padStart(wQty, ' ');
        const colPrice = String(price || '').trim().padStart(wPrice, ' ');

        // Word wrap product name into lines of max wItem length
        const words = String(name || '').trim().split(/\s+/);
        let itemLines = [];
        let curLine = '';

        for (const w of words) {
            if (!curLine) {
                if (w.length <= wItem) {
                    curLine = w;
                } else {
                    itemLines.push(w.substring(0, wItem));
                    curLine = w.substring(wItem);
                }
            } else if ((curLine + ' ' + w).length <= wItem) {
                curLine += ' ' + w;
            } else {
                itemLines.push(curLine);
                if (w.length <= wItem) {
                    curLine = w;
                } else {
                    itemLines.push(w.substring(0, wItem));
                    curLine = w.substring(wItem);
                }
            }
        }
        if (curLine) itemLines.push(curLine);
        if (itemLines.length === 0) itemLines.push('');

        // Line 1: First line of name (padded to wItem) + QTY + PRICE
        let result = itemLines[0].padEnd(wItem, ' ') + colQty + colPrice + '\n';

        // Subsequent lines: Remaining lines of name (indented 3 spaces under product name)
        for (let i = 1; i < itemLines.length; i++) {
            const indent = '   ';
            let sub = itemLines[i];
            if (!sub.startsWith(indent)) {
                sub = indent + sub;
            }
            result += sub.substring(0, width) + '\n';
        }

        return result;
    },

    centerText(text, width = 32) {
        const s = String(text || '').trim();
        if (!s) return '\n';
        if (s.length >= width) return s.substring(0, width) + '\n';
        const left = Math.floor((width - s.length) / 2);
        return ' '.repeat(left) + s + '\n';
    },

    centerWrapped(text, width = 32) {
        if (!text) return '';
        const rawLines = String(text).split(/\r?\n/);
        let result = '';
        for (const rawLine of rawLines) {
            const trimmed = rawLine.trim();
            if (!trimmed) continue;
            const words = trimmed.split(/\s+/);
            let cur = '';
            for (const w of words) {
                if (!cur) {
                    cur = w;
                } else if ((cur + ' ' + w).length <= width) {
                    cur += ' ' + w;
                } else {
                    result += this.centerText(cur, width);
                    cur = w;
                }
            }
            if (cur) {
                result += this.centerText(cur, width);
            }
        }
        return result;
    },

    // Build 58mm ESC/POS Test Receipt Bytes
    buildTestReceipt(store = {}) {
        const width = (store.printer_paper_width === '80mm') ? 48 : (this.columns || 32);
        const esc = [0x1B];
        const gs = [0x1D];

        let cmds = [];

        // 1. Initialize
        cmds.push(...esc, 0x40);

        // 2. Header (Exact software centering in standard Left Align mode to prevent printer firmware drift)
        const storeTitle = (store.store_name || 'BILLKARTX SMART POS').trim();
        cmds.push(...esc, 0x61, 0x00); // Left align
        if (storeTitle.length <= 16) {
            const dblPad = Math.max(0, Math.floor((16 - storeTitle.length) / 2));
            cmds.push(...gs, 0x21, 0x11);  // Double size
            cmds.push(...this.encoder.encode(' '.repeat(dblPad) + storeTitle + '\n'));
            cmds.push(...gs, 0x21, 0x00);  // Normal size
        } else {
            cmds.push(...gs, 0x21, 0x01);  // Double height, normal width
            cmds.push(...esc, 0x45, 0x01); // Bold on
            cmds.push(...this.encoder.encode(this.centerWrapped(storeTitle, width)));
            cmds.push(...esc, 0x45, 0x00); // Bold off
            cmds.push(...gs, 0x21, 0x00);  // Normal size
        }

        if (store.store_address) {
            cmds.push(...this.encoder.encode(this.centerWrapped(store.store_address, width)));
        }
        if (store.store_phone) {
            cmds.push(...this.encoder.encode(this.centerText('Tel: ' + store.store_phone, width)));
        }
        const isTestGstOn = store.enable_gst !== '0';
        if (isTestGstOn && store.store_gst) {
            cmds.push(...this.encoder.encode(this.centerText('GSTIN: ' + store.store_gst, width)));
        }

        cmds.push(...this.encoder.encode('-'.repeat(width) + '\n'));
        cmds.push(...this.encoder.encode(this.centerText('*** TEST PRINT OK (58mm) ***', width)));
        cmds.push(...this.encoder.encode(this.centerText(new Date().toLocaleString(), width)));
        cmds.push(...this.encoder.encode('-'.repeat(width) + '\n'));

        // Sample Items (Product, Qty, Price)
        cmds.push(...esc, 0x61, 0x00); // Left align
        cmds.push(...esc, 0x45, 0x01); // Bold on
        cmds.push(...this.encoder.encode(this.pad3Col('ITEM', 'QTY', 'PRICE', width)));
        cmds.push(...esc, 0x45, 0x00); // Bold off
        cmds.push(...this.encoder.encode('-'.repeat(width) + '\n'));
        cmds.push(...this.encoder.encode(this.format3ColItem('1. Sample Rice', '1 kg', '60.00', width)));
        cmds.push(...this.encoder.encode(this.format3ColItem('2. White Sugar', '500 g', '22.00', width)));
        cmds.push(...this.encoder.encode('-'.repeat(width) + '\n'));

        // Totals
        cmds.push(...this.encoder.encode(this.padRow('Subtotal:', 'Rs.100.00', width)));
        if (isTestGstOn) {
            cmds.push(...this.encoder.encode(this.padRow('GST Tax (5%):', 'Rs.5.00', width)));
            cmds.push(...esc, 0x45, 0x01); // Bold on
            cmds.push(...this.encoder.encode(this.padRow('TOTAL:', 'Rs.105.00', width)));
            cmds.push(...esc, 0x45, 0x00); // Bold off
        } else {
            cmds.push(...this.encoder.encode(this.padRow('GST (OFF):', 'Rs.0.00', width)));
            cmds.push(...esc, 0x45, 0x01); // Bold on
            cmds.push(...this.encoder.encode(this.padRow('TOTAL:', 'Rs.100.00', width)));
            cmds.push(...esc, 0x45, 0x00); // Bold off
        }

        // Footer
        cmds.push(...esc, 0x61, 0x00); // Left align
        cmds.push(...this.encoder.encode('-'.repeat(width) + '\n'));
        const footer = store.receipt_footer || 'Thank you! Visit again';
        cmds.push(...this.encoder.encode(this.centerWrapped(footer, width)));

        // Line feeds & Cut
        cmds.push(...this.encoder.encode('\n\n\n\n'));
        cmds.push(...gs, 0x56, 0x42, 0x00); // Partial cut

        return new Uint8Array(cmds);
    },

    // Build Bill ESC/POS Receipt Bytes
    buildBillReceipt(bill, store = {}) {
        const width = (store.printer_paper_width === '80mm' || (bill.store && bill.store.printer_paper_width === '80mm')) ? 48 : (this.columns || 32);
        const esc = [0x1B];
        const gs = [0x1D];
        const curr = this.getPrinterCurrency(store.currency_symbol || (bill.store && bill.store.currency_symbol));

        let cmds = [];

        // 1. Initialize
        cmds.push(...esc, 0x40);

        // 2. Shop Header (Exact software centering in standard Left Align mode to prevent printer firmware drift)
        const storeTitle = (store.store_name || bill.store_name || 'BILLKARTX').trim();
        cmds.push(...esc, 0x61, 0x00); // Left align
        if (storeTitle.length <= 16) {
            const dblPad = Math.max(0, Math.floor((16 - storeTitle.length) / 2));
            cmds.push(...gs, 0x21, 0x11);  // Double size
            cmds.push(...this.encoder.encode(' '.repeat(dblPad) + storeTitle + '\n'));
            cmds.push(...gs, 0x21, 0x00);  // Normal size
        } else {
            cmds.push(...gs, 0x21, 0x01);  // Double height, normal width (32 cols, no clip)
            cmds.push(...esc, 0x45, 0x01); // Bold on
            cmds.push(...this.encoder.encode(this.centerWrapped(storeTitle, width)));
            cmds.push(...esc, 0x45, 0x00); // Bold off
            cmds.push(...gs, 0x21, 0x00);  // Normal size
        }

        const addr = (store.store_address || (bill.store && bill.store.store_address) || '').trim();
        if (addr) {
            cmds.push(...this.encoder.encode(this.centerWrapped(addr, width)));
        }

        const phone = (store.store_phone || (bill.store && bill.store.store_phone) || '').trim();
        if (phone) cmds.push(...this.encoder.encode(this.centerText('Phone: ' + phone, width)));

        const isGstActive = (store.enable_gst !== '0') && (parseFloat(bill.tax) > 0 || store.enable_gst === '1');
        const gst = isGstActive ? (store.store_gst || (bill.store && bill.store.store_gst) || '').trim() : '';
        if (gst) cmds.push(...this.encoder.encode(this.centerText('GST: ' + gst, width)));

        let headerNote = store.receipt_header || (bill.store && bill.store.receipt_header);
        if (!isGstActive && headerNote === 'Tax Invoice / Retail Bill') {
            headerNote = 'Retail Bill';
        }
        if (headerNote) cmds.push(...this.encoder.encode(this.centerText(headerNote, width)));

        if (bill.is_reprint) {
            cmds.push(...esc, 0x45, 0x01); // Bold on
            cmds.push(...this.encoder.encode(this.centerText('*** DUPLICATE / REPRINT ***', width)));
            cmds.push(...esc, 0x45, 0x00); // Bold off
        }

        cmds.push(...this.encoder.encode('-'.repeat(width) + '\n'));

        // 3. Bill & Customer Metadata (Clean alignment, no cut-off)
        cmds.push(...esc, 0x61, 0x00); // Left align
        cmds.push(...this.encoder.encode(`Bill: ${bill.bill_number}\n`));
        if (bill.created_at) {
            cmds.push(...this.encoder.encode(`Date: ${bill.created_at.substring(0, 16)}\n`));
        }

        // Customer details: print ONLY IF given (omit Walk-in / empty)
        const custName = (bill.customer_name || '').trim();
        const custPhone = (bill.customer_phone || '').trim();
        const isWalkIn = !custName || custName.toLowerCase() === 'walk-in customer' || custName.toLowerCase() === 'walk-in';

        if (!isWalkIn) {
            cmds.push(...this.encoder.encode(`Customer: ${custName}\n`));
        }
        if (custPhone) {
            cmds.push(...this.encoder.encode(`Phone   : ${custPhone}\n`));
        }

        cmds.push(...this.encoder.encode('-'.repeat(width) + '\n'));

        // 4. Items Table (ITEM, QTY, PRICE)
        cmds.push(...esc, 0x45, 0x01); // Bold on
        cmds.push(...this.encoder.encode(this.pad3Col('ITEM', 'QTY', 'PRICE', width)));
        cmds.push(...esc, 0x45, 0x00); // Bold off
        cmds.push(...this.encoder.encode('-'.repeat(width) + '\n'));

        (bill.items || []).forEach((it, idx) => {
            const name = `${idx + 1}. ${it.product_name}`;
            const unit = it.selected_unit || '';
            const qty = `${it.entered_qty}${unit ? ' ' + unit : ''}`.trim();
            const price = parseFloat(it.item_total).toFixed(2);
            cmds.push(...this.encoder.encode(this.format3ColItem(name, qty, price, width)));

            if (parseFloat(it.item_discount || it.discount || 0) > 0) {
                const discAmt = parseFloat(it.item_discount || it.discount);
                cmds.push(...this.encoder.encode(this.padRow('   (Disc Saved)', `-${curr}${discAmt.toFixed(2)}`, width)));
            }
        });

        cmds.push(...this.encoder.encode('-'.repeat(width) + '\n'));

        // 5. Totals Breakdown
        cmds.push(...this.encoder.encode(this.padRow('Subtotal:', `${curr}${parseFloat(bill.subtotal).toFixed(2)}`, width)));

        if (parseFloat(bill.discount) > 0) {
            cmds.push(...this.encoder.encode(this.padRow('Discount:', `-${curr}${parseFloat(bill.discount).toFixed(2)}`, width)));
        }

        if (parseFloat(bill.tax) > 0) {
            cmds.push(...this.encoder.encode(this.padRow('GST Tax:', `+${curr}${parseFloat(bill.tax).toFixed(2)}`, width)));
        }

        cmds.push(...this.encoder.encode('-'.repeat(width) + '\n'));
        cmds.push(...esc, 0x45, 0x01); // Bold on
        cmds.push(...gs, 0x21, 0x01);  // Double height
        cmds.push(...this.encoder.encode(this.padRow('GRAND TOTAL:', `${curr}${parseFloat(bill.grand_total).toFixed(2)}`, width)));
        cmds.push(...gs, 0x21, 0x00);  // Normal
        cmds.push(...esc, 0x45, 0x00); // Bold off

        cmds.push(...this.encoder.encode(this.padRow('Payment Mode:', bill.payment_method || 'Cash', width)));

        // 6. Custom Footer
        cmds.push(...esc, 0x61, 0x00); // Left align
        cmds.push(...this.encoder.encode('-'.repeat(width) + '\n'));
        const footer = store.receipt_footer || (bill.store && bill.store.receipt_footer) || '*** Thank You! Visit Again ***';
        cmds.push(...this.encoder.encode(this.centerWrapped(footer, width)));

        // 7. Feed & Cut
        cmds.push(...this.encoder.encode('\n\n\n\n'));
        cmds.push(...gs, 0x56, 0x42, 0x00); // Partial cut

        return new Uint8Array(cmds);
    },

    // Build Return / Refund Voucher ESC/POS Bytes
    buildReturnReceipt(ret, store = {}) {
        const width = (store.printer_paper_width === '80mm' || (ret.store && ret.store.printer_paper_width === '80mm')) ? 48 : (this.columns || 32);
        const esc = [0x1B];
        const gs = [0x1D];
        const curr = this.getPrinterCurrency(store.currency_symbol || (ret.store && ret.store.currency_symbol));

        let cmds = [];

        // 1. Initialize
        cmds.push(...esc, 0x40);

        // 2. Shop Header (Exact software centering in standard Left Align mode to prevent printer firmware drift)
        const storeTitle = (store.store_name || ret.store_name || 'BILLKARTX').trim();
        cmds.push(...esc, 0x61, 0x00); // Left align
        if (storeTitle.length <= 16) {
            const dblPad = Math.max(0, Math.floor((16 - storeTitle.length) / 2));
            cmds.push(...gs, 0x21, 0x11);  // Double size
            cmds.push(...this.encoder.encode(' '.repeat(dblPad) + storeTitle + '\n'));
            cmds.push(...gs, 0x21, 0x00);  // Normal size
        } else {
            cmds.push(...gs, 0x21, 0x01);  // Double height, normal width
            cmds.push(...esc, 0x45, 0x01); // Bold on
            cmds.push(...this.encoder.encode(this.centerWrapped(storeTitle, width)));
            cmds.push(...esc, 0x45, 0x00); // Bold off
            cmds.push(...gs, 0x21, 0x00);  // Normal size
        }

        const addr = (store.store_address || (ret.store && ret.store.store_address) || '').trim();
        if (addr) {
            cmds.push(...this.encoder.encode(this.centerWrapped(addr, width)));
        }

        const phone = (store.store_phone || (ret.store && ret.store.store_phone) || '').trim();
        if (phone) cmds.push(...this.encoder.encode(this.centerText('Phone: ' + phone, width)));

        cmds.push(...esc, 0x45, 0x01); // Bold on
        cmds.push(...this.encoder.encode(this.centerText('*** SALES RETURN & REFUND ***', width)));
        cmds.push(...esc, 0x45, 0x00); // Bold off

        cmds.push(...this.encoder.encode('-'.repeat(width) + '\n'));

        // 3. Metadata (Left)
        cmds.push(...esc, 0x61, 0x00); // Left align
        cmds.push(...this.encoder.encode(`Return #: ${ret.return_number || ret.id}\n`));
        if (ret.created_at) {
            cmds.push(...this.encoder.encode(`Date    : ${ret.created_at.substring(0, 16)}\n`));
        }
        const refLabel = (!ret.bill_id || ret.bill_number === 'DIRECT-RETURN') ? 'Type: Direct Return' : ('Ref Bill: ' + ret.bill_number);
        cmds.push(...this.encoder.encode(`${refLabel}\n`));

        // Customer details: print ONLY IF given
        const custName = (ret.customer_name || '').trim();
        const custPhone = (ret.customer_phone || '').trim();
        const isWalkIn = !custName || custName.toLowerCase() === 'walk-in customer' || custName.toLowerCase() === 'walk-in';

        if (!isWalkIn) {
            cmds.push(...this.encoder.encode(`Customer: ${custName}\n`));
        }
        if (custPhone) {
            cmds.push(...this.encoder.encode(`Phone   : ${custPhone}\n`));
        }

        cmds.push(...this.encoder.encode('-'.repeat(width) + '\n'));

        // 4. Returned Items Table (ITEM, QTY, REFUND)
        cmds.push(...esc, 0x45, 0x01); // Bold on
        cmds.push(...this.encoder.encode(this.pad3Col('ITEM', 'QTY', 'REFUND', width)));
        cmds.push(...esc, 0x45, 0x00); // Bold off
        cmds.push(...this.encoder.encode('-'.repeat(width) + '\n'));

        (ret.items || []).forEach((it, idx) => {
            const name = `${idx + 1}. ${it.product_name}`;
            const unit = it.selected_unit || '';
            const qty = `${it.return_qty}${unit ? ' ' + unit : ''}`.trim();
            const refund = parseFloat(it.refund_amount).toFixed(2);
            cmds.push(...this.encoder.encode(this.format3ColItem(name, qty, refund, width)));
            if (it.restock) {
                cmds.push(...this.encoder.encode(this.padRow('   (Restocked to inventory)', '', width)));
            }
        });

        cmds.push(...this.encoder.encode('-'.repeat(width) + '\n'));

        // 5. Refund Total
        cmds.push(...esc, 0x45, 0x01); // Bold on
        cmds.push(...gs, 0x21, 0x01);  // Double height
        cmds.push(...this.encoder.encode(this.padRow('REFUND AMOUNT:', `${curr}${parseFloat(ret.total_refund).toFixed(2)}`, width)));
        cmds.push(...gs, 0x21, 0x00);  // Normal
        cmds.push(...esc, 0x45, 0x00); // Bold off

        cmds.push(...this.encoder.encode(this.padRow('Refund Mode:', ret.refund_method || 'Cash', width)));
        if (ret.return_reason) {
            cmds.push(...this.encoder.encode(this.padRow('Reason:', ret.return_reason, width)));
        }

        // 6. Footer
        cmds.push(...esc, 0x61, 0x00); // Left align
        cmds.push(...this.encoder.encode('-'.repeat(width) + '\n'));
        const footer = store.receipt_footer || (ret.store && ret.store.receipt_footer) || '*** Refund Completed ***';
        cmds.push(...this.encoder.encode(this.centerWrapped(footer, width)));

        // 7. Feed & Cut
        cmds.push(...this.encoder.encode('\n\n\n\n'));
        cmds.push(...gs, 0x56, 0x42, 0x00); // Partial cut

        return new Uint8Array(cmds);
    },

    // ================= TAMIL & RASTER GRAPHICS (HOP-HL58 / ESC-POS) =================
    hasTamil(data) {
        if (!data) return false;
        try {
            const str = (typeof data === 'string') ? data : JSON.stringify(data);
            return /[\u0B80-\u0BFF]/.test(str);
        } catch (e) {
            return false;
        }
    },

    needsImageMode(data) {
        const pref = (typeof localStorage !== 'undefined') ? (localStorage.getItem('pos_bt_print_mode') || 'auto') : 'auto';
        if (pref === 'image') return true;
        if (pref === 'text') return false;
        return this.hasTamil(data);
    },

    getPaperWidthDots(store = {}) {
        const paper = store.printer_paper_width || (this.columns === 48 ? '80mm' : '58mm');
        return paper === '80mm' ? 576 : 384;
    },

    // Convert an HTML Canvas into 1-bit monochrome ESC/POS Raster Bit Image (GS v 0)
    // Slices into 48-line strips with delays to protect HOP-HL58 / 58mm printer buffer
    async printCanvasAsRaster(sourceCanvas, targetWidth = 384, options = {}) {
        if (!this.isConnected()) {
            throw new Error('Bluetooth printer is not connected.');
        }

        const widthDots = targetWidth || 384;
        const bytesPerRow = Math.floor(widthDots / 8); // 48 bytes for 384 dots (HOP-HL58)
        const targetHeight = Math.round(sourceCanvas.height * (widthDots / sourceCanvas.width));

        // Create an offscreen canvas at exact widthDots x targetHeight
        const canvas = document.createElement('canvas');
        canvas.width = widthDots;
        canvas.height = targetHeight;
        const ctx = canvas.getContext('2d', { willReadFrequently: true });

        // Fill background with solid white
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, widthDots, targetHeight);

        // Draw source canvas scaled to targetWidth
        ctx.drawImage(sourceCanvas, 0, 0, widthDots, targetHeight);

        const imgData = ctx.getImageData(0, 0, widthDots, targetHeight);
        const data = imgData.data;

        // Binarize into 1-bit monochrome bitmap
        // Threshold: 195 luminance produces rich, solid black glyphs for Tamil strokes
        const totalLines = targetHeight;
        const bitmap = new Uint8Array(bytesPerRow * totalLines);

        for (let y = 0; y < totalLines; y++) {
            for (let byteX = 0; byteX < bytesPerRow; byteX++) {
                let byteVal = 0;
                for (let bit = 0; bit < 8; bit++) {
                    const x = byteX * 8 + bit;
                    const idx = (y * widthDots + x) * 4;
                    const r = data[idx];
                    const g = data[idx + 1];
                    const b = data[idx + 2];
                    const a = data[idx + 3];

                    let isBlack = false;
                    if (a > 60) {
                        const lum = 0.299 * r + 0.587 * g + 0.114 * b;
                        if (lum < 195) {
                            isBlack = true;
                        }
                    }
                    if (isBlack) {
                        // In ESC/POS GS v 0: MSB (0x80) is leftmost pixel, LSB (0x01) is rightmost pixel
                        byteVal |= (1 << (7 - bit));
                    }
                }
                bitmap[y * bytesPerRow + byteX] = byteVal;
            }
        }

        // Initialize printer: ESC @ (0x1B 0x40)
        await this.sendBytes(new Uint8Array([0x1B, 0x40]));

        // Slicing into safe horizontal strips for HOP-HL58 2-inch buffer protection
        // 48 scanlines * 48 bytes = 2304 bytes per strip, fits safely inside portable thermal RAM
        const STRIP_LINES = 48;
        for (let y = 0; y < totalLines; y += STRIP_LINES) {
            const stripH = Math.min(STRIP_LINES, totalLines - y);
            const stripSize = bytesPerRow * stripH;
            const stripData = bitmap.subarray(y * bytesPerRow, y * bytesPerRow + stripSize);

            // ESC/POS GS v 0 0 xL xH yL yH
            // 0x1D 0x76 0x30 0x00 xL xH yL yH
            const header = new Uint8Array([
                0x1D, 0x76, 0x30, 0x00,
                bytesPerRow & 0xFF, (bytesPerRow >> 8) & 0xFF,
                stripH & 0xFF, (stripH >> 8) & 0xFF
            ]);

            const stripCmd = new Uint8Array(header.length + stripData.length);
            stripCmd.set(header, 0);
            stripCmd.set(stripData, header.length);

            await this.sendBytes(stripCmd);

            // 40ms pause between strips so HOP-HL58 thermal printhead burns & motor advances paper smoothly
            await new Promise(r => setTimeout(r, 40));
        }

        // Line feeds & partial cut
        await this.sendBytes(new Uint8Array([
            0x1B, 0x64, 0x04, // ESC d 4 (feed 4 lines)
            0x1D, 0x56, 0x42, 0x00 // GS V 66 0 (partial cut)
        ]));
    },

    // Capture an HTML element via html2canvas and print as high-contrast 384-dot ESC/POS raster graphic
    async printElementAsRaster(elementOrId, options = {}) {
        if (!this.isConnected()) {
            throw new Error('Bluetooth printer is not connected.');
        }
        let el = (typeof elementOrId === 'string') ? document.getElementById(elementOrId) : elementOrId;
        if (!el) {
            throw new Error('Receipt element not found for printing.');
        }
        if (typeof html2canvas === 'undefined') {
            throw new Error('html2canvas library not loaded. Required for printing Tamil to HOP-HL58.');
        }

        const widthDots = options.widthDots || this.getPaperWidthDots(options.store || {});

        // Clone element off-screen with borderless, high-contrast, print-optimized styles
        const clone = el.cloneNode(true);
        clone.id = '__bt_raster_clone';
        clone.style.position = 'fixed';
        clone.style.left = '-9999px';
        clone.style.top = '0';
        clone.style.width = widthDots + 'px';
        clone.style.maxWidth = widthDots + 'px';
        clone.style.minWidth = widthDots + 'px';
        clone.style.boxSizing = 'border-box';
        clone.style.padding = '8px 6px';
        clone.style.margin = '0';
        clone.style.border = 'none';
        clone.style.boxShadow = 'none';
        clone.style.background = '#ffffff';
        clone.style.color = '#000000';
        clone.style.fontFamily = "'Nirmala UI', 'Noto Sans Tamil', 'Latha', 'Tamil Sangam MN', Arial, sans-serif";
        clone.style.fontSize = '14px';
        clone.style.lineHeight = '1.35';
        clone.style.fontWeight = '500';
        clone.style.visibility = 'visible';

        document.body.appendChild(clone);

        try {
            const canvas = await html2canvas(clone, {
                scale: 2, // 2x scale for ultra crisp Tamil rendering
                width: widthDots,
                windowWidth: widthDots,
                backgroundColor: '#ffffff',
                useCORS: true,
                allowTaint: true,
                logging: false
            });

            await this.printCanvasAsRaster(canvas, widthDots, options);
        } finally {
            if (clone.parentNode) {
                clone.parentNode.removeChild(clone);
            }
        }
    },

    // Build Receipt HTML string with crisp Tamil-supporting layout
    buildReceiptHTML(bill, store = {}) {
        const storeName = store.store_name || bill.store_name || 'BillKartX Smart Billing';
        const storeAddr = store.store_address || (bill.store && bill.store.store_address) || '';
        const storePhone = store.store_phone || (bill.store && bill.store.store_phone) || '';
        const isGstActive = (store.enable_gst !== '0') && (parseFloat(bill.tax) > 0 || store.enable_gst === '1');
        const storeGst = isGstActive ? (store.store_gst || (bill.store && bill.store.store_gst) || '') : '';
        let headerTag = store.receipt_header || (bill.store && bill.store.receipt_header) || 'Retail Bill';
        if (!isGstActive && headerTag === 'Tax Invoice / Retail Bill') {
            headerTag = 'Retail Bill';
        }
        const footerMsg = store.receipt_footer || (bill.store && bill.store.receipt_footer) || '*** Thank You! Visit Again ***';
        const curr = store.currency_symbol || (bill.store && bill.store.currency_symbol) || '₹';

        const ls = (k) => (typeof localStorage !== 'undefined' ? localStorage.getItem('pos_' + k) : null);
        const fShopName = parseInt(store.font_shop_name || (bill.store && bill.store.font_shop_name) || (typeof globalStoreSettings !== 'undefined' && globalStoreSettings.font_shop_name) || ls('font_shop_name') || 18, 10);
        const fShopAddress = parseInt(store.font_shop_address || (bill.store && bill.store.font_shop_address) || (typeof globalStoreSettings !== 'undefined' && globalStoreSettings.font_shop_address) || ls('font_shop_address') || 12, 10);
        const fShopContact = parseInt(store.font_shop_contact || (bill.store && bill.store.font_shop_contact) || (typeof globalStoreSettings !== 'undefined' && globalStoreSettings.font_shop_contact) || ls('font_shop_contact') || 11, 10);
        const fReceiptHeader = parseInt(store.font_receipt_header || (bill.store && bill.store.font_receipt_header) || (typeof globalStoreSettings !== 'undefined' && globalStoreSettings.font_receipt_header) || ls('font_receipt_header') || 13, 10);
        const fProductName = parseInt(store.font_product_name || store.receipt_font_size || (bill.store && bill.store.font_product_name) || (bill.store && bill.store.receipt_font_size) || (typeof globalStoreSettings !== 'undefined' && (globalStoreSettings.font_product_name || globalStoreSettings.receipt_font_size)) || ls('font_product_name') || ls('receipt_font_size') || 18, 10);
        const fQtyPrice = parseInt(store.font_qty_price || (bill.store && bill.store.font_qty_price) || (typeof globalStoreSettings !== 'undefined' && globalStoreSettings.font_qty_price) || ls('font_qty_price') || 13, 10);
        const fGrandTotal = parseInt(store.font_grand_total || (bill.store && bill.store.font_grand_total) || (typeof globalStoreSettings !== 'undefined' && globalStoreSettings.font_grand_total) || ls('font_grand_total') || 18, 10);
        const fFooter = parseInt(store.font_footer || (bill.store && bill.store.font_footer) || (typeof globalStoreSettings !== 'undefined' && globalStoreSettings.font_footer) || ls('font_footer') || 11, 10);

        let itemsHtml = '';
        (bill.items || []).forEach((it, idx) => {
            const unit = it.selected_unit || '';
            const qtyText = `${it.entered_qty}${unit ? ' ' + unit : ''}`;
            const itemDisc = parseFloat(it.item_discount || it.discount || 0);
            itemsHtml += `
                <tr>
                    <td class="receipt-item-name" style="padding: 4px 0; text-align: left; vertical-align: top; width: 54%; word-break: break-word; overflow-wrap: break-word; white-space: normal; font-size: ${fProductName}px; font-weight: 700; line-height: 1.35;">
                        ${idx + 1}. ${it.product_name}
                        ${itemDisc > 0 ? `<br><small style="color: #16a34a; font-size: 11px; font-weight: normal;">(Disc: -${curr}${itemDisc.toFixed(2)})</small>` : ''}
                    </td>
                    <td style="padding: 4px 0; text-align: center; vertical-align: top; width: 21%; white-space: nowrap; font-size: ${fQtyPrice}px;">${qtyText}</td>
                    <td style="padding: 4px 0; text-align: right; vertical-align: top; width: 25%; white-space: nowrap; font-weight: bold; font-size: ${fQtyPrice}px;">${curr}${parseFloat(it.item_total).toFixed(2)}</td>
                </tr>
            `;
        });

        const custName = (bill.customer_name || '').trim();
        const custPhone = (bill.customer_phone || '').trim();
        const isWalkIn = !custName || custName.toLowerCase() === 'walk-in customer' || custName.toLowerCase() === 'walk-in';

        return `
            <div style="font-family: 'Nirmala UI', 'Noto Sans Tamil', 'Latha', Arial, sans-serif; font-size: ${fShopContact}px; color: #000; background: #fff; line-height: 1.35; padding: 4px 2px;">
                <div style="text-align: center; margin-bottom: 4px;">
                    <div style="font-size: ${fShopName}px; font-weight: 800; text-transform: uppercase;">${storeName}</div>
                    ${storeAddr ? `<div style="font-size: ${fShopAddress}px; margin-top: 2px;">${storeAddr}</div>` : ''}
                    ${storePhone ? `<div style="font-size: ${fShopContact}px;">Phone: ${storePhone}</div>` : ''}
                    ${storeGst ? `<div style="font-size: ${fShopContact}px;">GST: ${storeGst}</div>` : ''}
                    <div style="border-bottom: 1px dashed #000; margin: 5px 0;"></div>
                    <div style="font-weight: bold; font-size: ${fReceiptHeader}px;">${headerTag}</div>
                    ${bill.is_reprint ? `<div style="font-weight: bold; font-size: 12px; border: 1px dashed #000; padding: 2px; margin: 3px 0;">*** DUPLICATE / REPRINT ***</div>` : ''}
                    <div style="display: flex; justify-content: space-between; font-size: ${fShopContact}px; margin-top: 3px;">
                        <span><strong>Bill:</strong> ${bill.bill_number}</span>
                        <span>${(bill.created_at || '').substring(0, 16)}</span>
                    </div>
                    ${(!isWalkIn || custPhone) ? `
                        <div style="display: flex; justify-content: space-between; font-size: ${fShopContact}px; margin-top: 2px;">
                            <span>${!isWalkIn ? `<strong>Customer:</strong> ${custName}` : ''}</span>
                            <span>${custPhone}</span>
                        </div>
                    ` : ''}
                </div>

                <div style="border-bottom: 1px dashed #000; margin: 5px 0;"></div>

                <table style="width: 100%; table-layout: fixed; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid #000;">
                            <th style="text-align: left; width: 54%; padding: 3px 0; font-size: ${fQtyPrice}px; font-weight: bold;">Product Name</th>
                            <th style="text-align: center; width: 21%; padding: 3px 0; font-size: ${fQtyPrice}px; font-weight: bold;">Qty</th>
                            <th style="text-align: right; width: 25%; padding: 3px 0; font-size: ${fQtyPrice}px; font-weight: bold;">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itemsHtml}
                    </tbody>
                </table>

                <div style="border-bottom: 1px dashed #000; margin: 5px 0;"></div>

                <div style="font-size: ${fQtyPrice}px; display: flex; flex-direction: column; gap: 3px;">
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
                            <span>GST Tax:</span>
                            <span>+${curr}${parseFloat(bill.tax).toFixed(2)}</span>
                        </div>
                    ` : ''}
                    <div style="border-bottom: 1px dashed #000; margin: 3px 0;"></div>
                    <div style="display: flex; justify-content: space-between; font-weight: 800; font-size: ${fGrandTotal}px;">
                        <span>GRAND TOTAL:</span>
                        <span>${curr}${parseFloat(bill.grand_total).toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: ${fShopContact}px; margin-top: 2px;">
                        <span>Payment Mode:</span>
                        <span><strong>${bill.payment_method || 'Cash'}</strong></span>
                    </div>
                </div>

                <div style="border-bottom: 1px dashed #000; margin: 5px 0;"></div>
                <div style="text-align: center; font-size: ${fFooter}px; margin-top: 6px;">
                    ${footerMsg}
                </div>
            </div>
        `;
    },

    // Build Return Voucher HTML string
    buildReturnHTML(ret, store = {}) {
        const storeName = store.store_name || ret.store_name || 'BillKartX Smart Billing';
        const storeAddr = store.store_address || (ret.store && ret.store.store_address) || '';
        const storePhone = store.store_phone || (ret.store && ret.store.store_phone) || '';
        const footerMsg = store.receipt_footer || (ret.store && ret.store.receipt_footer) || '*** Refund Completed ***';
        const curr = store.currency_symbol || (ret.store && ret.store.currency_symbol) || '₹';

        const ls = (k) => (typeof localStorage !== 'undefined' ? localStorage.getItem('pos_' + k) : null);
        const fShopName = parseInt(store.font_shop_name || (ret.store && ret.store.font_shop_name) || (typeof globalStoreSettings !== 'undefined' && globalStoreSettings.font_shop_name) || ls('font_shop_name') || 18, 10);
        const fShopAddress = parseInt(store.font_shop_address || (ret.store && ret.store.font_shop_address) || (typeof globalStoreSettings !== 'undefined' && globalStoreSettings.font_shop_address) || ls('font_shop_address') || 12, 10);
        const fShopContact = parseInt(store.font_shop_contact || (ret.store && ret.store.font_shop_contact) || (typeof globalStoreSettings !== 'undefined' && globalStoreSettings.font_shop_contact) || ls('font_shop_contact') || 11, 10);
        const fReceiptHeader = parseInt(store.font_receipt_header || (ret.store && ret.store.font_receipt_header) || (typeof globalStoreSettings !== 'undefined' && globalStoreSettings.font_receipt_header) || ls('font_receipt_header') || 13, 10);
        const fProductName = parseInt(store.font_product_name || store.receipt_font_size || (ret.store && ret.store.font_product_name) || (ret.store && ret.store.receipt_font_size) || (typeof globalStoreSettings !== 'undefined' && (globalStoreSettings.font_product_name || globalStoreSettings.receipt_font_size)) || ls('font_product_name') || ls('receipt_font_size') || 18, 10);
        const fQtyPrice = parseInt(store.font_qty_price || (ret.store && ret.store.font_qty_price) || (typeof globalStoreSettings !== 'undefined' && globalStoreSettings.font_qty_price) || ls('font_qty_price') || 13, 10);
        const fGrandTotal = parseInt(store.font_grand_total || (ret.store && ret.store.font_grand_total) || (typeof globalStoreSettings !== 'undefined' && globalStoreSettings.font_grand_total) || ls('font_grand_total') || 18, 10);
        const fFooter = parseInt(store.font_footer || (ret.store && ret.store.font_footer) || (typeof globalStoreSettings !== 'undefined' && globalStoreSettings.font_footer) || ls('font_footer') || 11, 10);

        let itemsHtml = '';
        (ret.items || []).forEach((it, idx) => {
            const unit = it.selected_unit || '';
            const qtyText = `${it.return_qty}${unit ? ' ' + unit : ''}`;
            itemsHtml += `
                <tr>
                    <td class="receipt-item-name" style="padding: 4px 0; text-align: left; vertical-align: top; width: 54%; word-break: break-word; overflow-wrap: break-word; white-space: normal; font-size: ${fProductName}px; font-weight: 700; line-height: 1.35;">
                        ${idx + 1}. ${it.product_name}
                        ${it.restock ? '<br><small style="color: #64748b; font-size: 11px;">(Restocked to inventory)</small>' : ''}
                    </td>
                    <td style="padding: 4px 0; text-align: center; vertical-align: top; width: 21%; white-space: nowrap; font-size: ${fQtyPrice}px;">${qtyText}</td>
                    <td style="padding: 4px 0; text-align: right; vertical-align: top; width: 25%; white-space: nowrap; font-weight: bold; font-size: ${fQtyPrice}px; color: #b91c1c;">${curr}${parseFloat(it.refund_amount).toFixed(2)}</td>
                </tr>
            `;
        });

        const refLabel = (!ret.bill_id || ret.bill_number === 'DIRECT-RETURN') ? 'Direct Return' : ret.bill_number;
        const custName = (ret.customer_name || '').trim();
        const custPhone = (ret.customer_phone || '').trim();
        const isWalkIn = !custName || custName.toLowerCase() === 'walk-in customer' || custName.toLowerCase() === 'walk-in';

        return `
            <div style="font-family: 'Nirmala UI', 'Noto Sans Tamil', 'Latha', Arial, sans-serif; font-size: ${fShopContact}px; color: #000; background: #fff; line-height: 1.35; padding: 4px 2px;">
                <div style="text-align: center; margin-bottom: 4px;">
                    <div style="font-size: ${fShopName}px; font-weight: 800; text-transform: uppercase;">${storeName}</div>
                    ${storeAddr ? `<div style="font-size: ${fShopAddress}px; margin-top: 2px;">${storeAddr}</div>` : ''}
                    ${storePhone ? `<div style="font-size: ${fShopContact}px;">Phone: ${storePhone}</div>` : ''}
                    <div style="border-bottom: 1px dashed #000; margin: 5px 0;"></div>
                    <div style="font-weight: bold; font-size: ${fReceiptHeader}px;">*** SALES RETURN & REFUND ***</div>
                    <div style="display: flex; justify-content: space-between; font-size: ${fShopContact}px; margin-top: 3px;">
                        <span><strong>Return #:</strong> ${ret.return_number || ret.id}</span>
                        <span>${(ret.created_at || '').substring(0, 16)}</span>
                    </div>
                    <div style="font-size: ${fShopContact}px; text-align: left; margin-top: 2px;">
                        <strong>Ref:</strong> ${refLabel}
                    </div>
                    ${(!isWalkIn || custPhone) ? `
                        <div style="display: flex; justify-content: space-between; font-size: ${fShopContact}px; margin-top: 2px;">
                            <span>${!isWalkIn ? `<strong>Customer:</strong> ${custName}` : ''}</span>
                            <span>${custPhone}</span>
                        </div>
                    ` : ''}
                </div>

                <div style="border-bottom: 1px dashed #000; margin: 5px 0;"></div>

                <table style="width: 100%; table-layout: fixed; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid #000;">
                            <th style="text-align: left; width: 54%; padding: 3px 0; font-size: ${fQtyPrice}px; font-weight: bold;">Item</th>
                            <th style="text-align: center; width: 21%; padding: 3px 0; font-size: ${fQtyPrice}px; font-weight: bold;">Qty</th>
                            <th style="text-align: right; width: 25%; padding: 3px 0; font-size: ${fQtyPrice}px; font-weight: bold;">Refund</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itemsHtml}
                    </tbody>
                </table>

                <div style="border-bottom: 1px dashed #000; margin: 5px 0;"></div>

                <div style="font-size: ${fQtyPrice}px; display: flex; flex-direction: column; gap: 3px;">
                    <div style="display: flex; justify-content: space-between; font-weight: 800; font-size: ${fGrandTotal}px;">
                        <span>REFUND AMOUNT:</span>
                        <span>${curr}${parseFloat(ret.total_refund).toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: ${fShopContact}px; margin-top: 2px;">
                        <span>Refund Mode:</span>
                        <span><strong>${ret.refund_method || 'Cash'}</strong></span>
                    </div>
                    ${ret.return_reason ? `
                        <div style="display: flex; justify-content: space-between; font-size: ${fShopContact}px;">
                            <span>Reason:</span>
                            <span>${ret.return_reason}</span>
                        </div>
                    ` : ''}
                </div>

                <div style="border-bottom: 1px dashed #000; margin: 5px 0;"></div>
                <div style="text-align: center; font-size: ${fFooter}px; margin-top: 6px;">
                    ${footerMsg}
                </div>
            </div>
        `;
    },

    async printTestTamil(store = {}) {
        if (!this.isConnected()) {
            throw new Error('Bluetooth printer is not connected.');
        }

        const widthDots = this.getPaperWidthDots(store);
        const canvas = document.createElement('canvas');
        canvas.width = widthDots;
        canvas.height = 570; // Increased height for larger fonts and multi-line item wrap
        const ctx = canvas.getContext('2d');

        // Solid white background
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, widthDots, 570);

        ctx.fillStyle = '#000000';
        ctx.textAlign = 'center';

        const storeTitle = (store.store_name || 'பில்கார்ட் எக்ஸ் பில்லிங்').trim();
        ctx.font = "bold 22px 'Nirmala UI', 'Noto Sans Tamil', 'Latha', Arial, sans-serif";
        ctx.fillText(storeTitle, widthDots / 2, 38);

        ctx.font = "15px 'Nirmala UI', 'Noto Sans Tamil', 'Latha', Arial, sans-serif";
        ctx.fillText("HOP-HL58 2-இன்ச் தெர்மல் பிரிண்டர்", widthDots / 2, 68);
        ctx.fillText("------------------------------------------------", widthDots / 2, 90);

        ctx.font = "bold 18px 'Nirmala UI', 'Noto Sans Tamil', 'Latha', Arial, sans-serif";
        ctx.fillText("*** தமிழ் அச்சு சோதனை வெற்றி ***", widthDots / 2, 122);
        ctx.font = "14px 'Nirmala UI', 'Noto Sans Tamil', 'Latha', Arial, sans-serif";
        ctx.fillText("Tamil Print Test OK (HOP-HL58)", widthDots / 2, 146);
        ctx.fillText("------------------------------------------------", widthDots / 2, 168);

        // Column Headers
        ctx.textAlign = 'left';
        ctx.font = "bold 15px 'Nirmala UI', 'Noto Sans Tamil', 'Latha', Arial, sans-serif";
        ctx.fillText("பொருள்", 12, 196);
        ctx.fillText("அளவு", widthDots * 0.54, 196);
        ctx.fillText("விலை", widthDots * 0.77, 196);

        ctx.font = "13px 'Nirmala UI', 'Noto Sans Tamil', 'Latha', Arial, sans-serif";
        ctx.fillText("------------------------------------------------", 12, 216);

        // Item 1 (Demonstrates multi-line wrapping!)
        const testItemFontSize = parseInt(store.receipt_font_size || (typeof globalStoreSettings !== 'undefined' && globalStoreSettings.receipt_font_size) || (typeof localStorage !== 'undefined' ? localStorage.getItem('pos_receipt_font_size') : null) || 18, 10);
        ctx.font = `bold ${testItemFontSize}px 'Nirmala UI', 'Noto Sans Tamil', 'Latha', Arial, sans-serif`;
        ctx.fillText("1. சர்க்கரை பாக்கெட்", 12, 240);
        ctx.font = "13px 'Nirmala UI', 'Noto Sans Tamil', 'Latha', Arial, sans-serif";
        ctx.fillText("   (முதல் தரம் - 1st Quality)", 12, 262); // Wrapped second line
        ctx.font = "bold 15px 'Nirmala UI', 'Noto Sans Tamil', 'Latha', Arial, sans-serif";
        ctx.fillText("1 கிலோ", widthDots * 0.54, 240);
        ctx.fillText("Rs.44.00", widthDots * 0.77, 240);

        // Item 2
        ctx.fillText("2. நல்லெண்ணெய்", 12, 296);
        ctx.fillText("500 மி.லி", widthDots * 0.54, 296);
        ctx.fillText("Rs.120.00", widthDots * 0.77, 296);

        // Item 3
        ctx.fillText("3. கோதுமை மாவு", 12, 332);
        ctx.fillText("2 கிலோ", widthDots * 0.54, 332);
        ctx.fillText("Rs.110.00", widthDots * 0.77, 332);

        ctx.font = "13px 'Nirmala UI', 'Noto Sans Tamil', 'Latha', Arial, sans-serif";
        ctx.fillText("------------------------------------------------", 12, 356);

        // Totals
        ctx.font = "bold 19px 'Nirmala UI', 'Noto Sans Tamil', 'Latha', Arial, sans-serif";
        ctx.fillText("மொத்தத் தொகை:", 12, 392);
        ctx.fillText("Rs.274.00", widthDots * 0.68, 392);

        // Footer
        ctx.textAlign = 'center';
        ctx.font = "13px 'Nirmala UI', 'Noto Sans Tamil', 'Latha', Arial, sans-serif";
        ctx.fillText("------------------------------------------------", widthDots / 2, 424);
        ctx.font = "bold 15px 'Nirmala UI', 'Noto Sans Tamil', 'Latha', Arial, sans-serif";
        ctx.fillText("*** நன்றி! மீண்டும் வருக! ***", widthDots / 2, 456);
        ctx.font = "12px 'Nirmala UI', 'Noto Sans Tamil', 'Latha', Arial, sans-serif";
        ctx.fillText("Powered by BillKartX Smart POS", widthDots / 2, 482);

        await this.printCanvasAsRaster(canvas, widthDots, { store });
    },

    // Print Return Voucher via Bluetooth (Auto-switches to Graphics for Tamil/HOP-HL58)
    async printReturn(returnObj, store = {}, elementOrId = null) {
        if (!this.isConnected()) {
            throw new Error('Bluetooth printer is not connected.');
        }

        const useImage = this.needsImageMode(returnObj);
        if (useImage) {
            let el = null;
            if (elementOrId) {
                el = (typeof elementOrId === 'string') ? document.getElementById(elementOrId) : elementOrId;
            }
            if (!el) {
                el = document.getElementById('returnPrintArea') || document.getElementById('returnReceiptPrintContainer');
            }

            if (el) {
                await this.printElementAsRaster(el, { store });
                return;
            } else {
                await this.printReturnAsOffscreenRaster(returnObj, store);
                return;
            }
        }

        const bytes = this.buildReturnReceipt(returnObj, store);
        await this.sendBytes(bytes);
    },

    // Print Bill via Bluetooth (Auto-switches to Graphics for Tamil/HOP-HL58)
    async printBill(bill, store = {}, elementOrId = null) {
        if (!this.isConnected()) {
            throw new Error('Bluetooth printer is not connected.');
        }

        const useImage = this.needsImageMode(bill);
        if (useImage) {
            let el = null;
            if (elementOrId) {
                el = (typeof elementOrId === 'string') ? document.getElementById(elementOrId) : elementOrId;
            }
            if (!el) {
                el = document.getElementById('receiptPrintArea') || document.getElementById('receiptContainer');
            }

            if (el) {
                await this.printElementAsRaster(el, { store });
                return;
            } else {
                await this.printBillAsOffscreenRaster(bill, store);
                return;
            }
        }

        const bytes = this.buildBillReceipt(bill, store);
        await this.sendBytes(bytes);
    },

    // Print Test Receipt via Bluetooth (Supports English ASCII and Tamil Graphics)
    async printTest(store = {}, lang = 'auto') {
        if (!this.isConnected()) {
            throw new Error('Bluetooth printer is not connected.');
        }
        if (lang === 'tamil' || this.hasTamil(store.store_name)) {
            await this.printTestTamil(store);
            return;
        }
        const bytes = this.buildTestReceipt(store);
        await this.sendBytes(bytes);
    }
};

window.BluetoothPrinter = BluetoothPrinter;
