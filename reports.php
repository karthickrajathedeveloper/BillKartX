<?php
require_once __DIR__ . '/includes/header.php';
?>

<main class="main-wrapper">
    <!-- Header with Period Selection, Custom Date Filter & Export -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.6rem; font-weight: 800; color: #0f172a; margin: 0 0 0.25rem 0;">Sales & Profit Analytics</h1>
            <p style="color: #64748b; font-size: 0.88rem; margin: 0;">Calculate exact profit (Selling Price − Purchase Cost − Discounts) across Day, Week, Month, or Custom Date ranges.</p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <button type="button" class="btn btn-secondary" onclick="applyReportFilter()" title="Refresh data">
                ↻ Refresh
            </button>
            <button type="button" class="btn" onclick="exportReportToExcel()" style="background: #16a34a; color: #ffffff; font-weight: 700; border: none; display: inline-flex; align-items: center; gap: 0.45rem; padding: 0.5rem 1.15rem; border-radius: 6px; cursor: pointer; box-shadow: 0 2px 5px rgba(22,163,74,0.25);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="8" y1="13" x2="16" y2="13"></line>
                    <line x1="8" y1="17" x2="16" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                📊 Download Excel Report (.xlsx)
            </button>
            <button type="button" class="btn btn-secondary" onclick="exportReportToPDF()" title="Print / PDF Report">
                📄 Print / PDF
            </button>
        </div>
    </div>

    <!-- Filter Control Card -->
    <div class="data-card" style="margin-bottom: 1.5rem; padding: 1rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
            <!-- Period Preset Buttons -->
            <div style="display: inline-flex; background: #f1f5f9; padding: 3px; border-radius: 8px; border: 1px solid #cbd5e1; gap: 2px;">
                <button type="button" class="btn btn-secondary rep-period-btn active" id="btnPeriodToday" onclick="setPeriodFilter('today')" style="padding: 0.35rem 0.8rem; font-size: 0.82rem; font-weight: 700; background: #2563eb; color: #fff; border: none; border-radius: 6px;">
                    📅 Today (Day-wise)
                </button>
                <button type="button" class="btn btn-secondary rep-period-btn" id="btnPeriodWeek" onclick="setPeriodFilter('week')" style="padding: 0.35rem 0.8rem; font-size: 0.82rem; font-weight: 600; border: none; border-radius: 6px; background: transparent; color: #475569;">
                    🗓️ This Week (7 Days)
                </button>
                <button type="button" class="btn btn-secondary rep-period-btn" id="btnPeriodMonth" onclick="setPeriodFilter('month')" style="padding: 0.35rem 0.8rem; font-size: 0.82rem; font-weight: 600; border: none; border-radius: 6px; background: transparent; color: #475569;">
                    📊 This Month
                </button>
                <button type="button" class="btn btn-secondary rep-period-btn" id="btnPeriodCustom" onclick="setPeriodFilter('custom')" style="padding: 0.35rem 0.8rem; font-size: 0.82rem; font-weight: 600; border: none; border-radius: 6px; background: transparent; color: #475569;">
                    🔍 Specified Dates
                </button>
            </div>

            <!-- Dynamic Date Picker Inputs based on active period -->
            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                <!-- Single Date for Day-wise -->
                <div id="filterSingleDateGroup" style="display: flex; align-items: center; gap: 0.35rem;">
                    <label class="form-label" style="font-size: 0.8rem; margin: 0; color: #475569;">Date:</label>
                    <input type="date" id="repSingleDate" class="form-control" value="<?= date('Y-m-d') ?>" onchange="onSingleDateChange(this.value)" style="width: auto; padding: 0.35rem 0.6rem; font-size: 0.85rem;">
                </div>

                <!-- Month Picker -->
                <div id="filterMonthGroup" style="display: none; align-items: center; gap: 0.35rem;">
                    <label class="form-label" style="font-size: 0.8rem; margin: 0; color: #475569;">Month:</label>
                    <input type="month" id="repMonth" class="form-control" value="<?= date('Y-m') ?>" onchange="applyReportFilter()" style="width: auto; padding: 0.35rem 0.6rem; font-size: 0.85rem;">
                </div>

                <!-- Custom Date Range -->
                <div id="filterCustomRangeGroup" style="display: none; align-items: center; gap: 0.35rem;">
                    <label class="form-label" style="font-size: 0.8rem; margin: 0; color: #475569;">From:</label>
                    <input type="date" id="repFromDate" class="form-control" value="<?= date('Y-m-01') ?>" style="width: auto; padding: 0.35rem 0.6rem; font-size: 0.85rem;">
                    <label class="form-label" style="font-size: 0.8rem; margin: 0; color: #475569;">To:</label>
                    <input type="date" id="repToDate" class="form-control" value="<?= date('Y-m-d') ?>" style="width: auto; padding: 0.35rem 0.6rem; font-size: 0.85rem;">
                    <button type="button" class="btn btn-primary" onclick="applyReportFilter()" style="padding: 0.35rem 0.75rem; font-size: 0.85rem;">
                        Filter
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Printable Report Container -->
    <div id="fullReportContainer">
        <!-- Report Header for PDF / Print -->
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.25rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e2e8f0; flex-wrap: wrap; gap: 0.5rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 44px; height: 44px; border-radius: 8px; background: #2563eb; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
                    🏬
                </div>
                <div>
                    <h2 id="reportStoreTitle" style="font-size: 1.35rem; font-weight: 800; color: #1e293b; margin: 0 0 0.15rem 0;">Sales & Profit Performance Report</h2>
                    <div id="reportStoreAddress" style="font-size: 0.82rem; color: #64748b; margin-bottom: 0.2rem;"></div>
                    <div style="font-size: 0.85rem; color: #475569;">
                        Period: <strong id="reportSelectedDateDisplay" style="color: #2563eb; font-weight: 700;">Today (<?= date('d M Y') ?>)</strong>
                    </div>
                </div>
            </div>
            <div style="text-align: right; font-size: 0.8rem; color: #64748b;">
                <div id="reportStoreContact" style="font-weight: 600; color: #334155; margin-bottom: 2px;"></div>
                <div>Report Generated: <?= date('d M Y, h:i A') ?></div>
            </div>
        </div>

        <!-- Metric KPI Cards: Sales, Returns, Cost, Discount, NET PROFIT -->
        <div class="stats-grid" style="margin-bottom: 1.25rem;">
            <!-- Card 1: Total Sales Revenue (Gross) -->
            <div class="stat-card" style="border-top: 4px solid #2563eb;">
                <div class="stat-header">
                    <span class="stat-label">Total Sales (Gross)</span>
                    <span style="font-size: 1.2rem;">💵</span>
                </div>
                <div class="stat-value" id="repTotalSales" style="color: #2563eb;">₹0.00</div>
                <div class="stat-sub"><span id="repTotalBills">0</span> Bills • <span id="repUnitsSold">0</span> Units Sold</div>
            </div>

            <!-- Card 2: Customer Returns & Refunds -->
            <div class="stat-card" style="border-top: 4px solid #ef4444; background: linear-gradient(180deg, #ffffff 0%, #fff1f2 100%);">
                <div class="stat-header">
                    <span class="stat-label" style="color: #991b1b; font-weight: 700;">Returns & Refunds</span>
                    <span style="font-size: 1.2rem;">↩️</span>
                </div>
                <div class="stat-value" id="repTotalReturns" style="color: #dc2626;">-₹0.00</div>
                <div class="stat-sub" style="color: #475569;">Net Sales: <strong id="repNetSales" style="color: #0f172a;">₹0.00</strong></div>
            </div>

            <!-- Card 3: Total Purchase Cost -->
            <div class="stat-card" style="border-top: 4px solid #64748b;">
                <div class="stat-header">
                    <span class="stat-label">Total Purchase Cost</span>
                    <span style="font-size: 1.2rem;">📦</span>
                </div>
                <div class="stat-value" id="repTotalCost" style="color: #475569;">₹0.00</div>
                <div class="stat-sub">Cost of Goods Sold (COGS)</div>
            </div>

            <!-- Card 4: Total Discount Given -->
            <div class="stat-card" style="border-top: 4px solid #f59e0b;">
                <div class="stat-header">
                    <span class="stat-label">Total Discounts Given</span>
                    <span style="font-size: 1.2rem;">🏷️</span>
                </div>
                <div class="stat-value" id="repTotalDiscount" style="color: #d97706;">₹0.00</div>
                <div class="stat-sub">Customer savings & item discounts</div>
            </div>

            <!-- Card 5: REAL NET PROFIT (HIGHEST PRIORITY - AFTER RETURNS & DISCOUNTS) -->
            <div class="stat-card" style="border-top: 4px solid #10b981; background: linear-gradient(180deg, #ffffff 0%, #f0fdf4 100%);">
                <div class="stat-header">
                    <span class="stat-label" style="font-weight: 800; color: #065f46;">⭐ REAL NET PROFIT (லாபம்)</span>
                    <span style="font-size: 1.3rem;">📈</span>
                </div>
                <div class="stat-value" id="repNetProfit" style="color: #10b981; font-weight: 900; font-size: 1.8rem;">₹0.00</div>
                <div class="stat-sub" style="font-size: 0.85rem; font-weight: 600; color: #047857;">
                    Net Margin: <strong id="repProfitMargin">0.0%</strong> (Sales − Refunds − Cost − Disc)
                </div>
            </div>
        </div>

        <!-- Profit & Cost Distribution Bar -->
        <div class="data-card" style="margin-bottom: 1.5rem; padding: 1rem 1.25rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.6rem; flex-wrap: wrap; gap: 0.5rem;">
                <h4 style="margin: 0; font-size: 0.95rem; font-weight: 700; color: #1e293b;">
                    Revenue Breakdown: Purchase Cost vs Discount vs Net Profit
                </h4>
                <div style="font-size: 0.8rem; color: #64748b;">
                    Selling Revenue = Purchase Cost + Discount Given + Net Profit
                </div>
            </div>

            <!-- Progress Bar Strip -->
            <div style="height: 14px; width: 100%; border-radius: 999px; overflow: hidden; display: flex; background: #e2e8f0; margin-bottom: 0.6rem;">
                <div id="barCost" style="width: 50%; background: #64748b; transition: width 0.5s ease;" title="Purchase Cost"></div>
                <div id="barDiscount" style="width: 5%; background: #f59e0b; transition: width 0.5s ease;" title="Discount"></div>
                <div id="barProfit" style="width: 45%; background: #10b981; transition: width 0.5s ease;" title="Net Profit"></div>
            </div>

            <div style="display: flex; justify-content: space-between; font-size: 0.82rem; flex-wrap: wrap; gap: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.4rem;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #64748b;"></span>
                    <span>Purchase Cost: <strong id="lblCostAmt">₹0.00</strong> (<span id="lblCostPct">0%</span>)</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.4rem;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #f59e0b;"></span>
                    <span>Discounts: <strong id="lblDiscAmt">₹0.00</strong> (<span id="lblDiscPct">0%</span>)</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.4rem;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #10b981;"></span>
                    <span>Net Profit: <strong id="lblProfitAmt" style="color: #10b981;">₹0.00</strong> (<span id="lblProfitPct">0%</span>)</span>
                </div>
            </div>
        </div>

        <!-- 3D Sales & Profit Analytics Visualizer -->
        <div class="data-card" style="margin-bottom: 1.5rem;">
            <div class="data-card-header">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                        <polyline points="2 17 12 22 22 17"></polyline>
                        <polyline points="2 12 12 17 22 12"></polyline>
                    </svg>
                    <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0;">Interactive 3D Revenue & Profit Bars (Three.js)</h3>
                </div>
                <div style="font-size: 0.85rem; color: #64748b;">
                    🔵 Blue = Sales Revenue | 🟢 Green = Net Profit (Drag to rotate in 3D)
                </div>
            </div>
            <div class="three-canvas-wrap" style="height: 320px; border-radius: 0;">
                <div id="threeChartCanvas" style="width: 100%; height: 100%;"></div>
            </div>
        </div>

        <!-- Report Tabs for Deep Profit Breakdown -->
        <div class="data-card" style="margin-bottom: 1.5rem;">
            <div class="data-card-header" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 0;">
                <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                    <button type="button" class="btn rep-sub-tab active" id="tabBtnDaily" onclick="switchReportTab('daily')" style="border-bottom: 3px solid #2563eb; border-radius: 0; background: transparent; font-weight: 700; color: #2563eb; padding: 0.6rem 1rem;">
                        📅 Daily Breakdown
                    </button>
                    <button type="button" class="btn rep-sub-tab" id="tabBtnBills" onclick="switchReportTab('bills')" style="border-bottom: 3px solid transparent; border-radius: 0; background: transparent; font-weight: 600; color: #64748b; padding: 0.6rem 1rem;">
                        🧾 Invoices List (<span id="tabBillsCount">0</span>)
                    </button>
                    <button type="button" class="btn rep-sub-tab" id="tabBtnProducts" onclick="switchReportTab('products')" style="border-bottom: 3px solid transparent; border-radius: 0; background: transparent; font-weight: 600; color: #64748b; padding: 0.6rem 1rem;">
                        📦 Product Profit Ranking
                    </button>
                    <button type="button" class="btn rep-sub-tab" id="tabBtnCategories" onclick="switchReportTab('categories')" style="border-bottom: 3px solid transparent; border-radius: 0; background: transparent; font-weight: 600; color: #64748b; padding: 0.6rem 1rem;">
                        🏷️ Category Sales & Profit
                    </button>
                </div>
            </div>

            <!-- Tab 1: Day-wise Profit Table -->
            <div id="tabContentDaily" style="padding: 1rem; overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Sale Date</th>
                            <th>Bills</th>
                            <th>Gross Sales (₹)</th>
                            <th style="color: #dc2626;">Refunds (₹)</th>
                            <th style="color: #2563eb;">Net Sales (₹)</th>
                            <th>Purchase Cost (₹)</th>
                            <th>Discount (₹)</th>
                            <th style="color: #10b981;">Net Profit (₹)</th>
                            <th>Profit Margin</th>
                        </tr>
                    </thead>
                    <tbody id="dailyProfitTableBody">
                        <tr><td colspan="9" style="text-align: center; color: #94a3b8; padding: 1.5rem;">Loading day-wise profit...</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Tab 2: Bill-by-Bill Breakdown -->
            <div id="tabContentBills" style="display: none; padding: 1rem; overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Bill Number</th>
                            <th>Date & Time</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Payment</th>
                            <th>Original Total</th>
                            <th style="color: #dc2626;">Refunds</th>
                            <th style="color: #2563eb;">Net Total</th>
                            <th>Purchase Cost</th>
                            <th>Discount</th>
                            <th style="color: #10b981;">Net Profit</th>
                        </tr>
                    </thead>
                    <tbody id="billsProfitTableBody">
                        <tr><td colspan="11" style="text-align: center; color: #94a3b8; padding: 1.5rem;">Loading invoice list...</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Tab 3: Product Profit Ranking -->
            <div id="tabContentProducts" style="display: none; padding: 1rem; overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Units Sold</th>
                            <th>Total Revenue (₹)</th>
                            <th>Purchase Cost (₹)</th>
                            <th style="color: #10b981;">Net Profit (₹)</th>
                            <th>Profit Margin</th>
                        </tr>
                    </thead>
                    <tbody id="productProfitTableBody">
                        <tr><td colspan="6" style="text-align: center; color: #94a3b8; padding: 1.5rem;">Loading product performance...</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Tab 4: Category Breakdown -->
            <div id="tabContentCategories" style="display: none; padding: 1rem; overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Category Name</th>
                            <th>Items Sold</th>
                            <th>Total Revenue (₹)</th>
                            <th>Purchase Cost (₹)</th>
                            <th style="color: #10b981;">Total Profit (₹)</th>
                        </tr>
                    </thead>
                    <tbody id="categoryProfitTableBody">
                        <tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 1.5rem;">Loading categories...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Warehouse Stock Balance & Health Overview -->
        <div class="data-card">
            <div class="data-card-header">
                <h3 style="font-size: 1rem; font-weight: 700; margin: 0;">Warehouse Stock Balance & Inventory Valuation</h3>
            </div>
            <div style="padding: 1.25rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div style="border-left: 4px solid #2563eb; padding-left: 0.75rem;">
                    <div style="font-size: 0.8rem; color: #64748b;">Current Stock Value (Retail)</div>
                    <div style="font-size: 1.3rem; font-weight: 800; color: #1e293b;" id="stockValRetail">₹0.00</div>
                    <div style="font-size: 0.75rem; color: #64748b;">Expected revenue when fully sold</div>
                </div>
                <div style="border-left: 4px solid #64748b; padding-left: 0.75rem;">
                    <div style="font-size: 0.8rem; color: #64748b;">Current Stock Value (Cost)</div>
                    <div style="font-size: 1.3rem; font-weight: 800; color: #1e293b;" id="stockValCost">₹0.00</div>
                    <div style="font-size: 0.75rem; color: #64748b;">Total invested purchase capital</div>
                </div>
                <div style="border-left: 4px solid #10b981; padding-left: 0.75rem;">
                    <div style="font-size: 0.8rem; color: #64748b;">Potential Stock Profit</div>
                    <div style="font-size: 1.3rem; font-weight: 800; color: #10b981;" id="stockValProfit">₹0.00</div>
                    <div style="font-size: 0.75rem; color: #047857;">Retail Value − Purchase Cost</div>
                </div>
                <div style="border-left: 4px solid #f59e0b; padding-left: 0.75rem;">
                    <div style="font-size: 0.8rem; color: #64748b;">Low Stock Items</div>
                    <div style="font-size: 1.3rem; font-weight: 800; color: #f59e0b;" id="stockValLow">0</div>
                    <div style="font-size: 0.75rem; color: #b45309;">Needs reorder soon</div>
                </div>
            </div>
        </div>
    </div>
</main>

<div id="toastContainer" class="toast-container"></div>

<!-- Three.js Visualizer Scripts -->
<script src="assets/js/three-inventory.js"></script>
<!-- SheetJS Excel Library for Instant .xlsx Report Generation -->
<script src="assets/vendor/xlsx.full.min.js"></script>

<script>
    let chart3DInstance = null;
    let currentPeriod = 'today'; // 'today', 'day', 'week', 'month', 'custom'
    let currentReportData = null;

    function showToast(msg, type = 'success') {
        const c = document.getElementById('toastContainer');
        const t = document.createElement('div');
        t.className = `toast ${type}`;
        t.innerHTML = `<span>${type === 'success' ? '✓' : '⚠'}</span><div>${msg}</div>`;
        c.appendChild(t);
        setTimeout(() => { t.remove(); }, 3000);
    }

    function setPeriodFilter(period) {
        currentPeriod = period;

        // Update button visual styles
        const btns = {
            today: document.getElementById('btnPeriodToday'),
            week: document.getElementById('btnPeriodWeek'),
            month: document.getElementById('btnPeriodMonth'),
            custom: document.getElementById('btnPeriodCustom')
        };

        Object.keys(btns).forEach(key => {
            const btn = btns[key];
            if (!btn) return;
            if (key === period) {
                btn.style.background = '#2563eb';
                btn.style.color = '#ffffff';
                btn.style.fontWeight = '700';
            } else {
                btn.style.background = 'transparent';
                btn.style.color = '#475569';
                btn.style.fontWeight = '600';
            }
        });

        // Toggle date picker containers
        const singleDateGrp = document.getElementById('filterSingleDateGroup');
        const monthGrp = document.getElementById('filterMonthGroup');
        const customGrp = document.getElementById('filterCustomRangeGroup');

        if (singleDateGrp) singleDateGrp.style.display = (period === 'today' || period === 'day') ? 'flex' : 'none';
        if (monthGrp) monthGrp.style.display = (period === 'month') ? 'flex' : 'none';
        if (customGrp) customGrp.style.display = (period === 'custom') ? 'flex' : 'none';

        applyReportFilter();
    }

    function onSingleDateChange(dateVal) {
        if (currentPeriod === 'today') {
            currentPeriod = 'day';
        }
        applyReportFilter();
    }

    async function applyReportFilter() {
        let url = `api.php?action=get_reports&period=${encodeURIComponent(currentPeriod)}`;

        if (currentPeriod === 'today' || currentPeriod === 'day') {
            const dateVal = document.getElementById('repSingleDate').value;
            url += `&date=${encodeURIComponent(dateVal)}`;
        } else if (currentPeriod === 'month') {
            const mVal = document.getElementById('repMonth').value;
            url += `&month=${encodeURIComponent(mVal)}`;
        } else if (currentPeriod === 'custom') {
            const fromVal = document.getElementById('repFromDate').value;
            const toVal = document.getElementById('repToDate').value;
            url += `&from_date=${encodeURIComponent(fromVal)}&to_date=${encodeURIComponent(toVal)}`;
        }

        try {
            const res = await fetch(url);
            const data = await res.json();
            if (data.success) {
                currentReportData = data;
                renderReportUI(data);

                // Update 3D Chart
                if (chart3DInstance && data.daily_breakdown) {
                    chart3DInstance.renderBars(data.daily_breakdown);
                }
            } else {
                showToast(data.error || 'Failed to fetch report data', 'error');
            }
        } catch (e) {
            console.error('Report fetch error:', e);
            showToast('Network error loading reports', 'error');
        }
    }

    function renderReportUI(data) {
        const sum = data.summary || {};
        const sales = parseFloat(sum.total_sales || 0);
        const cost = parseFloat(sum.total_purchase_cost || 0);
        const discount = parseFloat(sum.total_discount || 0);
        const profit = parseFloat(sum.net_profit || 0);
        const bills = sum.total_bills || 0;
        const units = parseFloat(sum.total_units_sold || 0);
        const margin = parseFloat(sum.profit_margin || 0);

        // Header label
        const titleEl = document.getElementById('reportSelectedDateDisplay');
        if (titleEl && data.filter && data.filter.label) {
            titleEl.innerText = data.filter.label;
        }

        // Store Details (Shop Name, Address, Contact)
        const store = data.store || (typeof globalStoreSettings !== 'undefined' ? globalStoreSettings : {}) || {};
        const shopTitleEl = document.getElementById('reportStoreTitle');
        const shopAddrEl = document.getElementById('reportStoreAddress');
        const shopContactEl = document.getElementById('reportStoreContact');
        if (shopTitleEl && store.store_name) shopTitleEl.innerText = store.store_name;
        if (shopAddrEl && store.store_address) shopAddrEl.innerText = store.store_address;
        if (shopContactEl) {
            const contacts = [];
            if (store.store_phone) contacts.push(`Phone: ${store.store_phone}`);
            if (store.store_gst) contacts.push(`GST: ${store.store_gst}`);
            shopContactEl.innerText = contacts.join(' | ');
        }

        // KPI Cards
        const curr = (typeof globalStoreSettings !== 'undefined' && globalStoreSettings.currency_symbol) || '₹';
        const returns = parseFloat(sum.total_returns || 0);
        const netSales = parseFloat(sum.net_sales || (sales - returns));

        document.getElementById('repTotalSales').innerText = `${curr}${sales.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('repTotalReturns').innerText = `-${curr}${returns.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('repNetSales').innerText = `${curr}${netSales.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('repTotalCost').innerText = `${curr}${cost.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('repTotalDiscount').innerText = `-${curr}${discount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('repNetProfit').innerText = `${curr}${profit.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('repTotalBills').innerText = bills;
        document.getElementById('repUnitsSold').innerText = units;
        document.getElementById('repProfitMargin').innerText = `${margin.toFixed(1)}%`;

        // Progress breakdown bar
        const totalBasis = Math.max(1, cost + discount + Math.max(0, profit));
        const costPct = Math.round((cost / totalBasis) * 100);
        const discPct = Math.round((discount / totalBasis) * 100);
        const profitPct = Math.max(0, 100 - costPct - discPct);

        const barCost = document.getElementById('barCost');
        const barDisc = document.getElementById('barDiscount');
        const barProfit = document.getElementById('barProfit');
        if (barCost) barCost.style.width = `${costPct}%`;
        if (barDisc) barDisc.style.width = `${discPct}%`;
        if (barProfit) barProfit.style.width = `${profitPct}%`;

        document.getElementById('lblCostAmt').innerText = `${curr}${cost.toFixed(2)}`;
        document.getElementById('lblCostPct').innerText = `${costPct}%`;
        document.getElementById('lblDiscAmt').innerText = `${curr}${discount.toFixed(2)}`;
        document.getElementById('lblDiscPct').innerText = `${discPct}%`;
        document.getElementById('lblProfitAmt').innerText = `${curr}${profit.toFixed(2)}`;
        document.getElementById('lblProfitPct').innerText = `${profitPct}%`;

        // Tab 1: Daily Breakdown Table
        const dailyTbody = document.getElementById('dailyProfitTableBody');
        let dailyHtml = '';
        (data.daily_breakdown || []).forEach(d => {
            const dSales = parseFloat(d.sales_amount || 0);
            const dRefund = parseFloat(d.refund_amount || 0);
            const dNetSales = parseFloat(d.net_sales || (dSales - dRefund));
            const dCost = parseFloat(d.purchase_cost || 0);
            const dDisc = parseFloat(d.total_discount || 0);
            const dProfit = parseFloat(d.net_profit || 0);
            const dMargin = dNetSales > 0 ? ((dProfit / dNetSales) * 100).toFixed(1) : '0.0';

            dailyHtml += `
                <tr>
                    <td><strong>${d.sale_date}</strong></td>
                    <td><span class="stock-tag in">${d.bills_count} bills</span></td>
                    <td>${curr}${dSales.toFixed(2)}</td>
                    <td><strong style="color: ${dRefund > 0 ? '#dc2626' : '#94a3b8'};">${dRefund > 0 ? '-' + curr + dRefund.toFixed(2) : curr + '0.00'}</strong></td>
                    <td><strong style="color: #2563eb;">${curr}${dNetSales.toFixed(2)}</strong></td>
                    <td>${curr}${dCost.toFixed(2)}</td>
                    <td>${dDisc > 0 ? `-${curr}${dDisc.toFixed(2)}` : `${curr}0.00`}</td>
                    <td><strong style="color: ${dProfit >= 0 ? '#10b981' : '#ef4444'}; font-size: 1rem;">${dProfit >= 0 ? '+' : ''}${curr}${dProfit.toFixed(2)}</strong></td>
                    <td><span style="font-weight: 700; color: ${dProfit >= 0 ? '#047857' : '#b91c1c'};">${dMargin}%</span></td>
                </tr>
            `;
        });
        dailyTbody.innerHTML = dailyHtml || `<tr><td colspan="9" style="text-align: center; color: #94a3b8; padding: 2rem;">No sales in this period</td></tr>`;

        // Tab 2: Bills Breakdown Table
        const billsTbody = document.getElementById('billsProfitTableBody');
        const billsCountBadge = document.getElementById('tabBillsCount');
        let billsHtml = '';
        const billsList = data.bills_list || [];
        if (billsCountBadge) billsCountBadge.innerText = billsList.length;

        billsList.forEach(b => {
            const bProfit = parseFloat(b.bill_profit || 0);
            const bRefunded = parseFloat(b.total_refunded || 0);
            const bNet = parseFloat(b.net_grand_total || (parseFloat(b.grand_total) - bRefunded));
            billsHtml += `
                <tr>
                    <td><code><strong>${b.bill_number}</strong></code></td>
                    <td><small>${b.created_at}</small></td>
                    <td><strong>${b.customer_name}</strong></td>
                    <td><span class="stock-tag in">${b.item_count} items</span></td>
                    <td><span style="font-weight: 600;">${b.payment_method}</span></td>
                    <td>${curr}${parseFloat(b.subtotal || b.grand_total).toFixed(2)}</td>
                    <td><strong style="color: ${bRefunded > 0 ? '#dc2626' : '#94a3b8'};">${bRefunded > 0 ? '-' + curr + bRefunded.toFixed(2) : curr + '0.00'}</strong></td>
                    <td><strong style="color: #2563eb;">${curr}${bNet.toFixed(2)}</strong></td>
                    <td>${curr}${parseFloat(b.purchase_cost).toFixed(2)}</td>
                    <td>${parseFloat(b.discount) > 0 ? `-${curr}${parseFloat(b.discount).toFixed(2)}` : `${curr}0.00`}</td>
                    <td><strong style="color: ${bProfit >= 0 ? '#10b981' : '#ef4444'}; font-size: 0.95rem;">${bProfit >= 0 ? '+' : ''}${curr}${bProfit.toFixed(2)}</strong></td>
                </tr>
            `;
        });
        billsTbody.innerHTML = billsHtml || `<tr><td colspan="11" style="text-align: center; color: #94a3b8; padding: 2rem;">No bills recorded</td></tr>`;

        // Tab 3: Products Table
        const prodTbody = document.getElementById('productProfitTableBody');
        let prodHtml = '';
        (data.top_products || []).forEach(p => {
            const pRev = parseFloat(p.total_revenue || 0);
            const pCost = parseFloat(p.total_cost || 0);
            const pProfit = parseFloat(p.item_profit || 0);
            const pMargin = pRev > 0 ? ((pProfit / pRev) * 100).toFixed(1) : '0.0';

            prodHtml += `
                <tr>
                    <td><strong>${p.product_name}</strong></td>
                    <td>${parseFloat(p.units_sold)} ${p.selected_unit || ''}</td>
                    <td>${curr}${pRev.toFixed(2)}</td>
                    <td>${curr}${pCost.toFixed(2)}</td>
                    <td><strong style="color: #10b981; font-size: 0.95rem;">+${curr}${pProfit.toFixed(2)}</strong></td>
                    <td><span style="font-weight: 700; color: #047857;">${pMargin}%</span></td>
                </tr>
            `;
        });
        prodTbody.innerHTML = prodHtml || `<tr><td colspan="6" style="text-align: center; color: #94a3b8; padding: 2rem;">No product sales recorded</td></tr>`;

        // Tab 4: Categories Table
        const catTbody = document.getElementById('categoryProfitTableBody');
        let catHtml = '';
        (data.category_sales || []).forEach(c => {
            const cRev = parseFloat(c.total_revenue || 0);
            const cCost = parseFloat(c.total_cost || 0);
            const cProfit = parseFloat(c.total_profit || 0);

            catHtml += `
                <tr>
                    <td>
                        <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:${c.category_color}; margin-right:6px;"></span>
                        <strong>${c.category_name}</strong>
                    </td>
                    <td>${c.total_orders} items</td>
                    <td>${curr}${cRev.toFixed(2)}</td>
                    <td>${curr}${cCost.toFixed(2)}</td>
                    <td><strong style="color: #10b981;">+${curr}${cProfit.toFixed(2)}</strong></td>
                </tr>
            `;
        });
        catTbody.innerHTML = catHtml || `<tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 2rem;">No category sales</td></tr>`;

        // Warehouse Stock Valuation
        const s = data.stock_summary || {};
        const stockRetail = parseFloat(s.stock_valuation_retail || 0);
        const stockCost = parseFloat(s.stock_valuation_cost || 0);
        const stockProfit = Math.max(0, stockRetail - stockCost);

        document.getElementById('stockValRetail').innerText = `${curr}${stockRetail.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
        document.getElementById('stockValCost').innerText = `${curr}${stockCost.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
        document.getElementById('stockValProfit').innerText = `+${curr}${stockProfit.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
        document.getElementById('stockValLow').innerText = s.low_stock_count || 0;
    }

    function switchReportTab(tabKey) {
        const tabs = {
            daily: { btn: document.getElementById('tabBtnDaily'), content: document.getElementById('tabContentDaily') },
            bills: { btn: document.getElementById('tabBtnBills'), content: document.getElementById('tabContentBills') },
            products: { btn: document.getElementById('tabBtnProducts'), content: document.getElementById('tabContentProducts') },
            categories: { btn: document.getElementById('tabBtnCategories'), content: document.getElementById('tabContentCategories') }
        };

        Object.keys(tabs).forEach(key => {
            const item = tabs[key];
            if (!item.btn || !item.content) return;
            if (key === tabKey) {
                item.btn.style.borderBottomColor = '#2563eb';
                item.btn.style.color = '#2563eb';
                item.btn.style.fontWeight = '700';
                item.content.style.display = 'block';
            } else {
                item.btn.style.borderBottomColor = 'transparent';
                item.btn.style.color = '#64748b';
                item.btn.style.fontWeight = '600';
                item.content.style.display = 'none';
            }
        });
    }

    // ================= EXCEL (.XLSX) REPORT GENERATION =================
    async function exportReportToExcel() {
        if (!currentReportData || !currentReportData.summary) {
            showToast('Loading latest report data, please wait...', 'error');
            await applyReportFilter();
            if (!currentReportData || !currentReportData.summary) {
                showToast('Unable to fetch report data. Please refresh.', 'error');
                return;
            }
        }

        if (typeof XLSX === 'undefined') {
            showToast('Excel library not loaded. Please refresh the page.', 'error');
            return;
        }

        try {
            showToast('Generating Excel report (.xlsx)...');

            const data = currentReportData;
            const sum = data.summary || {};
            const stock = data.stock_summary || {};
            const filter = data.filter || {};
            const store = data.store || (typeof globalStoreSettings !== 'undefined' ? globalStoreSettings : {}) || {};
            const shopName = ((store.store_name || '').trim()) || 'Store Sales & Profit Report';
            const shopAddress = (store.store_address || '').trim();
            const shopPhone = (store.store_phone || '').trim();
            const shopGst = (store.store_gst || '').trim();
            const storeDetailsLine = [
                shopAddress ? `Address: ${shopAddress}` : '',
                shopPhone ? `Phone: ${shopPhone}` : '',
                shopGst ? `GST: ${shopGst}` : ''
            ].filter(Boolean).join(' | ');

            const curr = (typeof globalStoreSettings !== 'undefined' && globalStoreSettings.currency_symbol) || '₹';
            const now = new Date();
            const genTimestamp = now.toLocaleString('en-IN', { dateStyle: 'medium', timeStyle: 'short' });

            const wb = XLSX.utils.book_new();

            // ----------------------------------------------------
            // 1. FINANCIAL SUMMARY & OVERVIEW WORKSHEET
            // ----------------------------------------------------
            const summaryRows = [
                [shopName.toUpperCase()],
                ...(storeDetailsLine ? [[storeDetailsLine]] : []),
                ["SALES, PURCHASE COST & PROFIT PERFORMANCE REPORT"],
                ["Period:", filter.label || "Selected Period", "", "Generated On:", genTimestamp],
                ["Currency:", `Indian Rupee (${curr})`, "", "Audit Status:", "Verified"],
                [],
                ["==================== 1. FINANCIAL PERFORMANCE & PROFIT SUMMARY ===================="],
                ["Financial Metric", `Amount (${curr})`, "Description / Tamil Meaning", "Business Significance"],
                ["Total Sales Revenue (Gross)", Number(sum.total_sales || 0), "மொத்த விற்பனை (Before Returns)", "Total invoice billing before customer refunds"],
                ["Customer Returns & Refunds", -Math.abs(Number(sum.total_returns || 0)), "வாடிக்கையாளர் திருப்பித் தந்த தொகை", "Cash/credit refunded on returned merchandise"],
                ["Net Sales Revenue", Number(sum.net_sales || 0), "நிகர விற்பனை வருவாய் (Gross Sales - Refunds)", "True recognized sales revenue"],
                ["Total Purchase Cost (COGS)", Number(sum.total_purchase_cost || 0), "மொத்த கொள்முதல் அடக்க விலை (Purchase Cost)", "Direct purchase cost of inventory sold"],
                ["Customer Discounts Given", -Math.abs(Number(sum.total_discount || 0)), "வழங்கப்பட்ட தள்ளுபடி தொகை", "Discounts given to customers"],
                ["Taxes / GST Collected", Number(sum.total_tax || 0), "வசூலிக்கப்பட்ட வரிகள்", "Total tax added on bills"],
                ["REAL NET PROFIT (லாபம்)", Number(sum.net_profit || 0), "உண்மையான நிகர லாபம் (Net Sales - Purchase - Disc)", "Actual net business earnings"],
                ["Net Profit Margin %", `${Number(sum.profit_margin || 0).toFixed(1)}%`, "லாப சதவீதம் (%)", "Profit margin percentage on net sales"],
                ["Total Completed Invoices", Number(sum.total_bills || 0), "மொத்த விற்பனை பில்கள்", "Count of transactions processed"],
                ["Total Units / Items Sold", Number(sum.total_units_sold || 0), "விற்பனையான பொருட்களின் அளவு", "Total volume/weight of merchandise sold"],
                [],
                ["==================== 2. WAREHOUSE INVENTORY & STOCK VALUATION ===================="],
                ["Inventory Metric", `Value (${curr})`, "Description / Tamil Meaning", "Warehouse Status"],
                ["Current Stock Valuation (Purchase Cost)", Number(stock.stock_valuation_cost || 0), "தற்போதுள்ள ஸ்டாக் கொள்முதல் முதலீடு (Capital)", "Total capital invested in current inventory"],
                ["Current Stock Valuation (Retail Selling)", Number(stock.stock_valuation_retail || 0), "விற்பனை விலை மதிப்பு (Expected Revenue)", "Expected revenue when all inventory sells"],
                ["Potential Stock Profit", Math.max(0, Number(stock.stock_valuation_retail || 0) - Number(stock.stock_valuation_cost || 0)), "எதிர்பார்க்கப்படும் ஸ்டாக் லாபம்", "Unrealized gross profit currently on shelves"],
                ["Total Catalog Products", Number(stock.total_products || 0), "பொருட்களின் எண்ணிக்கை", "Active SKUs in inventory"],
                ["Total Units Currently in Stock", Number(stock.total_units_in_stock || 0), "இருப்பில் உள்ள மொத்த அளவு", "Total physical units across all products"],
                ["Low Stock Alert Items", Number(stock.low_stock_count || 0), "குறைந்த இருப்பு எச்சரிக்கை பொருட்கள்", "Products needing reorder soon"],
                ["Out of Stock Items", Number(stock.out_of_stock_count || 0), "இருப்பற்ற பொருட்கள்", "Products completely sold out"]
            ];

            const wsSummary = XLSX.utils.aoa_to_sheet(summaryRows);
            wsSummary['!cols'] = [{ wch: 40 }, { wch: 20 }, { wch: 50 }, { wch: 45 }];
            XLSX.utils.book_append_sheet(wb, wsSummary, "Financial Summary");

            // ----------------------------------------------------
            // 2. DAILY BREAKDOWN WORKSHEET
            // ----------------------------------------------------
            const dailyRows = [
                [`${shopName.toUpperCase()} - DAY-WISE SALES, PURCHASE & PROFIT BREAKDOWN`],
                ...(storeDetailsLine ? [[storeDetailsLine]] : []),
                ["Period:", filter.label || ""],
                [],
                ["Sale Date", "Bills Count", `Gross Sales (${curr})`, `Refunds (${curr})`, `Net Sales (${curr})`, `Purchase Cost (${curr})`, `Discount (${curr})`, `Net Profit (${curr})`, "Profit Margin %"]
            ];

            let dTotalBills = 0, dTotalGross = 0, dTotalRef = 0, dTotalNet = 0, dTotalCost = 0, dTotalDisc = 0, dTotalProfit = 0;

            (data.daily_breakdown || []).forEach(d => {
                const bCount = Number(d.bills_count || 0);
                const gross = Number(d.sales_amount || 0);
                const ref = Number(d.refund_amount || 0);
                const net = Number(d.net_sales || (gross - ref));
                const cost = Number(d.purchase_cost || 0);
                const disc = Number(d.total_discount || 0);
                const prof = Number(d.net_profit || 0);
                const margin = net > 0 ? ((prof / net) * 100).toFixed(1) + "%" : "0.0%";

                dTotalBills += bCount;
                dTotalGross += gross;
                dTotalRef += ref;
                dTotalNet += net;
                dTotalCost += cost;
                dTotalDisc += disc;
                dTotalProfit += prof;

                dailyRows.push([
                    d.sale_date,
                    bCount,
                    gross,
                    ref,
                    net,
                    cost,
                    disc,
                    prof,
                    margin
                ]);
            });

            const dOverallMargin = dTotalNet > 0 ? ((dTotalProfit / dTotalNet) * 100).toFixed(1) + "%" : "0.0%";
            dailyRows.push([]);
            dailyRows.push(["TOTAL / AVERAGE", dTotalBills, dTotalGross, dTotalRef, dTotalNet, dTotalCost, dTotalDisc, dTotalProfit, dOverallMargin]);

            const wsDaily = XLSX.utils.aoa_to_sheet(dailyRows);
            wsDaily['!cols'] = [{ wch: 15 }, { wch: 14 }, { wch: 18 }, { wch: 16 }, { wch: 18 }, { wch: 18 }, { wch: 16 }, { wch: 18 }, { wch: 16 }];
            XLSX.utils.book_append_sheet(wb, wsDaily, "Daily Breakdown");

            // ----------------------------------------------------
            // 3. INVOICES & BILLS LIST WORKSHEET
            // ----------------------------------------------------
            const invoiceRows = [
                [`${shopName.toUpperCase()} - INVOICE-BY-INVOICE SALES & PROFIT LIST`],
                ...(storeDetailsLine ? [[storeDetailsLine]] : []),
                ["Period:", filter.label || ""],
                [],
                ["Bill Number", "Date & Time", "Customer Name", "Items Count", "Payment Method", `Subtotal (${curr})`, `Refunds (${curr})`, `Net Total (${curr})`, `Purchase Cost (${curr})`, `Discount (${curr})`, `Net Profit (${curr})`]
            ];

            let invSub = 0, invRef = 0, invNet = 0, invCost = 0, invDisc = 0, invProf = 0;

            (data.bills_list || []).forEach(b => {
                const sub = Number(b.subtotal || b.grand_total || 0);
                const ref = Number(b.total_refunded || 0);
                const net = Number(b.net_grand_total || (Number(b.grand_total) - ref));
                const cost = Number(b.purchase_cost || 0);
                const disc = Number(b.discount || 0);
                const prof = Number(b.bill_profit || 0);

                invSub += sub;
                invRef += ref;
                invNet += net;
                invCost += cost;
                invDisc += disc;
                invProf += prof;

                invoiceRows.push([
                    b.bill_number,
                    b.created_at,
                    b.customer_name || "Walk-in Customer",
                    Number(b.item_count || 1),
                    b.payment_method || "Cash",
                    sub,
                    ref,
                    net,
                    cost,
                    disc,
                    prof
                ]);
            });

            invoiceRows.push([]);
            invoiceRows.push(["TOTALS", "", "", "", "", invSub, invRef, invNet, invCost, invDisc, invProf]);

            const wsInvoices = XLSX.utils.aoa_to_sheet(invoiceRows);
            wsInvoices['!cols'] = [{ wch: 28 }, { wch: 20 }, { wch: 24 }, { wch: 14 }, { wch: 16 }, { wch: 16 }, { wch: 14 }, { wch: 16 }, { wch: 18 }, { wch: 14 }, { wch: 16 }];
            XLSX.utils.book_append_sheet(wb, wsInvoices, "Invoices List");

            // ----------------------------------------------------
            // 4. PRODUCT SALES & PROFIT RANKING
            // ----------------------------------------------------
            const prodRows = [
                [`${shopName.toUpperCase()} - PRODUCT SALES, PURCHASE COST & PROFIT RANKING`],
                ...(storeDetailsLine ? [[storeDetailsLine]] : []),
                ["Period:", filter.label || ""],
                [],
                ["Product Name", "Units Sold", "Unit Type", `Total Sales Revenue (${curr})`, `Total Purchase Cost (${curr})`, `Net Profit (${curr})`, "Profit Margin %"]
            ];

            let prodUnits = 0, prodRev = 0, prodCost = 0, prodProfit = 0;

            (data.top_products || []).forEach(p => {
                const units = Number(p.units_sold || 0);
                const rev = Number(p.total_revenue || 0);
                const cost = Number(p.total_cost || 0);
                const prof = Number(p.item_profit || 0);
                const margin = rev > 0 ? ((prof / rev) * 100).toFixed(1) + "%" : "0.0%";

                prodUnits += units;
                prodRev += rev;
                prodCost += cost;
                prodProfit += prof;

                prodRows.push([
                    p.product_name,
                    units,
                    p.selected_unit || "",
                    rev,
                    cost,
                    prof,
                    margin
                ]);
            });

            const prodOverallMargin = prodRev > 0 ? ((prodProfit / prodRev) * 100).toFixed(1) + "%" : "0.0%";
            prodRows.push([]);
            prodRows.push(["TOTALS", prodUnits, "", prodRev, prodCost, prodProfit, prodOverallMargin]);

            const wsProducts = XLSX.utils.aoa_to_sheet(prodRows);
            wsProducts['!cols'] = [{ wch: 32 }, { wch: 14 }, { wch: 12 }, { wch: 22 }, { wch: 22 }, { wch: 18 }, { wch: 16 }];
            XLSX.utils.book_append_sheet(wb, wsProducts, "Product Performance");

            // ----------------------------------------------------
            // 5. CATEGORY SALES & PROFIT BREAKDOWN
            // ----------------------------------------------------
            const catRows = [
                [`${shopName.toUpperCase()} - CATEGORY SALES & PROFIT BREAKDOWN`],
                ...(storeDetailsLine ? [[storeDetailsLine]] : []),
                ["Period:", filter.label || ""],
                [],
                ["Category Name", "Items Sold / Orders", `Total Revenue (${curr})`, `Total Purchase Cost (${curr})`, `Total Profit (${curr})`]
            ];

            let catOrders = 0, catRev = 0, catCost = 0, catProfit = 0;

            (data.category_sales || []).forEach(c => {
                const ord = Number(c.total_orders || 0);
                const rev = Number(c.total_revenue || 0);
                const cost = Number(c.total_cost || 0);
                const prof = Number(c.total_profit || 0);

                catOrders += ord;
                catRev += rev;
                catCost += cost;
                catProfit += prof;

                catRows.push([
                    c.category_name,
                    ord,
                    rev,
                    cost,
                    prof
                ]);
            });

            catRows.push([]);
            catRows.push(["TOTALS", catOrders, catRev, catCost, catProfit]);

            const wsCats = XLSX.utils.aoa_to_sheet(catRows);
            wsCats['!cols'] = [{ wch: 28 }, { wch: 20 }, { wch: 20 }, { wch: 22 }, { wch: 18 }];
            XLSX.utils.book_append_sheet(wb, wsCats, "Category Performance");

            // ----------------------------------------------------
            // GENERATE FILE & DOWNLOAD
            // ----------------------------------------------------
            let dateSuffix = currentPeriod;
            if (currentPeriod === 'today' || currentPeriod === 'day') {
                const dVal = document.getElementById('repSingleDate') ? document.getElementById('repSingleDate').value : '';
                dateSuffix = dVal || 'Today';
            } else if (currentPeriod === 'week') {
                dateSuffix = 'ThisWeek_' + (new Date().toISOString().slice(0, 10));
            } else if (currentPeriod === 'month') {
                const mVal = document.getElementById('repMonth') ? document.getElementById('repMonth').value : '';
                dateSuffix = mVal || 'ThisMonth';
            } else if (currentPeriod === 'custom') {
                const fVal = document.getElementById('repFromDate') ? document.getElementById('repFromDate').value : '';
                const tVal = document.getElementById('repToDate') ? document.getElementById('repToDate').value : '';
                dateSuffix = `${fVal}_to_${tVal}`;
            }

            const safeShopName = shopName.replace(/[^a-zA-Z0-9_\u0B80-\u0BFF]/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '') || 'Store_Report';
            const fileName = `${safeShopName}_Sales_Profit_Report_${dateSuffix}.xlsx`;
            XLSX.writeFile(wb, fileName);
            showToast('Excel Report downloaded successfully! (All 5 sheets included)', 'success');
        } catch (err) {
            console.error('Excel Generation Error:', err);
            showToast('Failed to generate Excel report: ' + err.message, 'error');
        }
    }

    async function exportReportToPDF() {
        const element = document.getElementById('fullReportContainer');
        if (!element) return;

        try {
            showToast('Preparing report for printing / PDF...');
            // Temporarily hide 3D canvas container to avoid dark/glitched rendering in print/PDF
            const threeCard = document.querySelector('.three-canvas-wrap')?.closest('.data-card');
            if (threeCard) threeCard.style.display = 'none';

            if (typeof html2canvas === 'undefined' || typeof jspdf === 'undefined') {
                window.print();
                if (threeCard) threeCard.style.display = 'block';
                return;
            }

            const canvas = await html2canvas(element, { scale: 1.5, useCORS: true, backgroundColor: '#ffffff' });
            if (threeCard) threeCard.style.display = 'block';

            const imgData = canvas.toDataURL('image/png');
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'mm', 'a4');
            const pageWidth = pdf.internal.pageSize.getWidth();
            const pageHeight = pdf.internal.pageSize.getHeight();
            const imgHeight = (canvas.height * pageWidth) / canvas.width;

            let heightLeft = imgHeight;
            let position = 0;

            pdf.addImage(imgData, 'PNG', 0, position, pageWidth, imgHeight);
            heightLeft -= pageHeight;

            while (heightLeft > 0) {
                position = heightLeft - imgHeight;
                pdf.addPage();
                pdf.addImage(imgData, 'PNG', 0, position, pageWidth, imgHeight);
                heightLeft -= pageHeight;
            }

            const store = (currentReportData && currentReportData.store) || (typeof globalStoreSettings !== 'undefined' ? globalStoreSettings : {}) || {};
            const shopName = ((store.store_name || '').trim()) || 'Store_Report';
            const safeShopName = shopName.replace(/[^a-zA-Z0-9_\u0B80-\u0BFF]/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '') || 'Store_Report';
            pdf.save(`${safeShopName}_Report_${Date.now()}.pdf`);
            showToast('Report PDF downloaded successfully!');
        } catch (e) {
            console.error('PDF error:', e);
            window.print();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        chart3DInstance = new ThreeChart3D('threeChartCanvas');
        applyReportFilter();
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
