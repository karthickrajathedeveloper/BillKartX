/**
 * Web Bluetooth Thermal Printer (58mm ESC/POS) Driver
 * Direct wireless printing from browser to 58mm / 80mm Bluetooth receipt printers
 */

const BluetoothPrinter = {
    device: null,
    server: null,
    characteristic: null,
    printerName: (typeof localStorage !== 'undefined' ? localStorage.getItem('pos_bt_printer_name') : null),
    columns: 32, // Standard for 58mm thermal printers (Font A)

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

    getStatus() {
        return {
            supported: this.isSupported(),
            connected: this.isConnected(),
            printerName: this.printerName
        };
    },

    async connect() {
        if (!this.isSupported()) {
            throw new Error('Web Bluetooth is not supported on this browser. Please use Chrome/Edge or an Android Bluetooth browser.');
        }

        try {
            // Request Bluetooth Device
            const device = await navigator.bluetooth.requestDevice({
                acceptAllDevices: true,
                optionalServices: this.SERVICES
            });

            this.device = device;
            this.printerName = device.name || 'Bluetooth Printer (58mm)';
            localStorage.setItem('pos_bt_printer_name', this.printerName);

            device.addEventListener('gattserverdisconnected', () => {
                this.characteristic = null;
                this.updateUIStatus();
            });

            // Connect to GATT Server
            const server = await device.gatt.connect();
            this.server = server;

            // Search through available services to find writable characteristic
            let foundChar = null;
            const services = await server.getPrimaryServices();

            for (const service of services) {
                try {
                    const characteristics = await service.getCharacteristics();
                    for (const char of characteristics) {
                        if (char.properties.write || char.properties.writeWithoutResponse) {
                            foundChar = char;
                            break;
                        }
                    }
                } catch (e) {
                    // Try next service
                }
                if (foundChar) break;
            }

            if (!foundChar) {
                throw new Error('No writable printer characteristic found on this Bluetooth device.');
            }

            this.characteristic = foundChar;
            this.updateUIStatus();
            return this.printerName;
        } catch (err) {
            console.error('Bluetooth Connection Error:', err);
            throw err;
        }
    },

    disconnect() {
        if (this.device && this.device.gatt) {
            try {
                this.device.gatt.disconnect();
            } catch (e) {}
        }
        this.device = null;
        this.server = null;
        this.characteristic = null;
        this.updateUIStatus();
    },

    updateUIStatus() {
        const statusEl = document.getElementById('btPrinterStatusBadge');
        const nameEl = document.getElementById('btPrinterName');
        const connectBtn = document.getElementById('btConnectBtn');
        const disconnectBtn = document.getElementById('btDisconnectBtn');
        const testBtn = document.getElementById('btTestPrintBtn');

        const connected = this.isConnected();

        if (statusEl) {
            if (connected) {
                statusEl.className = 'status-badge online';
                statusEl.innerHTML = `<span class="dot"></span> Connected`;
            } else {
                statusEl.className = 'status-badge offline';
                statusEl.innerHTML = `<span class="dot"></span> Not Connected`;
            }
        }

        if (nameEl) {
            nameEl.innerText = connected ? (this.printerName || 'Bluetooth 58mm Printer') : (this.printerName ? `${this.printerName} (Disconnected)` : 'None');
        }

        if (connectBtn) connectBtn.style.display = connected ? 'none' : 'inline-flex';
        if (disconnectBtn) disconnectBtn.style.display = connected ? 'inline-flex' : 'none';
        if (testBtn) testBtn.disabled = !connected;
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
        const w1 = width - w2 - w3;
        const col1 = c1.padEnd(w1, ' ').substring(0, w1);
        const col2 = c2.padStart(w2, ' ').substring(0, w2);
        const col3 = c3.padStart(w3, ' ').substring(0, w3);
        return col1 + col2 + col3 + '\n';
    },

    format3ColItem(name, qty, price, width = 32) {
        name = String(name || '').trim();
        qty = String(qty || '').trim();
        price = String(price || '').trim();
        const w3 = 8;
        const w2 = 7;
        const w1 = width - w2 - w3;

        const col2 = qty.padStart(w2, ' ');
        const col3 = price.padStart(w3, ' ');

        if (name.length <= w1) {
            return name.padEnd(w1, ' ') + col2 + col3 + '\n';
        } else {
            return name + '\n' + ' '.repeat(w1) + col2 + col3 + '\n';
        }
    },

    centerText(text, width = 32) {
        text = String(text || '').trim();
        if (text.length >= width) return text + '\n';
        const pad = Math.floor((width - text.length) / 2);
        return ' '.repeat(pad) + text + '\n';
    },

    // Build 58mm ESC/POS Test Receipt Bytes
    buildTestReceipt(store = {}) {
        const width = this.columns;
        const esc = [0x1B];
        const gs = [0x1D];

        let cmds = [];

        // 1. Initialize
        cmds.push(...esc, 0x40);

        // 2. Header (Center)
        cmds.push(...esc, 0x61, 0x01); // Center align
        cmds.push(...gs, 0x21, 0x11);  // Double size
        cmds.push(...this.encoder.encode((store.store_name || 'BILLKARTX SMART POS') + '\n'));
        cmds.push(...gs, 0x21, 0x00);  // Normal size

        if (store.store_address) {
            cmds.push(...this.encoder.encode(this.centerText(store.store_address, width)));
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
        cmds.push(...this.encoder.encode(this.pad3Col('PRODUCT', 'QTY', 'PRICE', width)));
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
        cmds.push(...esc, 0x61, 0x01); // Center align
        cmds.push(...this.encoder.encode('-'.repeat(width) + '\n'));
        const footer = store.receipt_footer || 'Thank you! Visit again';
        cmds.push(...this.encoder.encode(this.centerText(footer, width)));
        cmds.push(...this.encoder.encode(this.centerText('Wireless Bluetooth POS', width)));

        // Line feeds & Cut
        cmds.push(...this.encoder.encode('\n\n\n\n'));
        cmds.push(...gs, 0x56, 0x42, 0x00); // Partial cut

        return new Uint8Array(cmds);
    },

    // Build Bill ESC/POS Receipt Bytes
    buildBillReceipt(bill, store = {}) {
        const width = this.columns;
        const esc = [0x1B];
        const gs = [0x1D];
        const curr = store.currency_symbol || 'Rs.';

        let cmds = [];

        // 1. Initialize
        cmds.push(...esc, 0x40);

        // 2. Shop Header (Center)
        cmds.push(...esc, 0x61, 0x01); // Center align
        cmds.push(...gs, 0x21, 0x11);  // Double size
        cmds.push(...this.encoder.encode((store.store_name || bill.store_name || 'BILLKARTX') + '\n'));
        cmds.push(...gs, 0x21, 0x00);  // Normal size

        const addr = store.store_address || (bill.store && bill.store.store_address);
        if (addr) cmds.push(...this.encoder.encode(this.centerText(addr, width)));

        const phone = store.store_phone || (bill.store && bill.store.store_phone);
        if (phone) cmds.push(...this.encoder.encode(this.centerText('Phone: ' + phone, width)));

        const isGstActive = (store.enable_gst !== '0') && (parseFloat(bill.tax) > 0 || store.enable_gst === '1');
        const gst = isGstActive ? (store.store_gst || (bill.store && bill.store.store_gst)) : '';
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

        // 3. Bill & Customer Metadata (Left)
        cmds.push(...esc, 0x61, 0x00); // Left align
        cmds.push(...this.encoder.encode(this.padRow('Bill: ' + bill.bill_number, bill.created_at ? bill.created_at.substring(0, 16) : '', width)));
        cmds.push(...this.encoder.encode(this.padRow('Customer: ' + (bill.customer_name || 'Walk-in'), bill.customer_phone || '', width)));
        cmds.push(...this.encoder.encode('-'.repeat(width) + '\n'));

        // 4. Items Table (Product Name, Qty, Price)
        cmds.push(...esc, 0x45, 0x01); // Bold on
        cmds.push(...this.encoder.encode(this.pad3Col('PRODUCT', 'QTY', 'PRICE', width)));
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
        cmds.push(...esc, 0x61, 0x01); // Center align
        cmds.push(...this.encoder.encode('-'.repeat(width) + '\n'));
        const footer = store.receipt_footer || (bill.store && bill.store.receipt_footer) || '*** Thank You! Visit Again ***';
        cmds.push(...this.encoder.encode(this.centerText(footer, width)));
        cmds.push(...this.encoder.encode(this.centerText('Powered by BillKartX Smart POS', width)));

        // 7. Feed & Cut
        cmds.push(...this.encoder.encode('\n\n\n\n'));
        cmds.push(...gs, 0x56, 0x42, 0x00); // Partial cut

        return new Uint8Array(cmds);
    },

    // Build Return / Refund Voucher ESC/POS Bytes
    buildReturnReceipt(ret, store = {}) {
        const width = this.columns;
        const esc = [0x1B];
        const gs = [0x1D];
        const curr = store.currency_symbol || 'Rs.';

        let cmds = [];

        // 1. Initialize
        cmds.push(...esc, 0x40);

        // 2. Shop Header (Center)
        cmds.push(...esc, 0x61, 0x01); // Center align
        cmds.push(...gs, 0x21, 0x11);  // Double size
        cmds.push(...this.encoder.encode((store.store_name || ret.store_name || 'BILLKARTX') + '\n'));
        cmds.push(...gs, 0x21, 0x00);  // Normal size

        const addr = store.store_address || (ret.store && ret.store.store_address);
        if (addr) cmds.push(...this.encoder.encode(this.centerText(addr, width)));

        const phone = store.store_phone || (ret.store && ret.store.store_phone);
        if (phone) cmds.push(...this.encoder.encode(this.centerText('Phone: ' + phone, width)));

        cmds.push(...esc, 0x45, 0x01); // Bold on
        cmds.push(...this.encoder.encode(this.centerText('*** SALES RETURN & REFUND ***', width)));
        cmds.push(...esc, 0x45, 0x00); // Bold off

        cmds.push(...this.encoder.encode('-'.repeat(width) + '\n'));

        // 3. Metadata (Left)
        cmds.push(...esc, 0x61, 0x00); // Left align
        cmds.push(...this.encoder.encode(this.padRow('Return #: ' + (ret.return_number || ret.id), '', width)));
        const refLabel = (!ret.bill_id || ret.bill_number === 'DIRECT-RETURN') ? 'Type: Direct Return' : ('Ref Bill: ' + ret.bill_number);
        cmds.push(...this.encoder.encode(this.padRow(refLabel, ret.created_at ? ret.created_at.substring(0, 16) : '', width)));
        cmds.push(...this.encoder.encode(this.padRow('Customer: ' + (ret.customer_name || 'Walk-in'), ret.customer_phone || '', width)));
        cmds.push(...this.encoder.encode('-'.repeat(width) + '\n'));

        // 4. Returned Items Table
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
        cmds.push(...esc, 0x61, 0x01); // Center align
        cmds.push(...this.encoder.encode('-'.repeat(width) + '\n'));
        cmds.push(...this.encoder.encode(this.centerText('*** Refund Completed ***', width)));
        cmds.push(...this.encoder.encode(this.centerText('Powered by BillKartX Smart POS', width)));

        // 7. Feed & Cut
        cmds.push(...this.encoder.encode('\n\n\n\n'));
        cmds.push(...gs, 0x56, 0x42, 0x00); // Partial cut

        return new Uint8Array(cmds);
    },

    // Print Return Voucher via Bluetooth
    async printReturn(returnObj, store = {}) {
        if (!this.isConnected()) {
            throw new Error('Bluetooth printer is not connected.');
        }
        const bytes = this.buildReturnReceipt(returnObj, store);
        await this.sendBytes(bytes);
    },

    // Print Bill via Bluetooth
    async printBill(bill, store = {}) {
        if (!this.isConnected()) {
            throw new Error('Bluetooth printer is not connected.');
        }
        const bytes = this.buildBillReceipt(bill, store);
        await this.sendBytes(bytes);
    },

    // Print Test Receipt via Bluetooth
    async printTest(store = {}) {
        if (!this.isConnected()) {
            throw new Error('Bluetooth printer is not connected.');
        }
        const bytes = this.buildTestReceipt(store);
        await this.sendBytes(bytes);
    }
};

window.BluetoothPrinter = BluetoothPrinter;

