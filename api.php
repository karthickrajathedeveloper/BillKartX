<?php
/**
 * REST API for POS & Inventory System
 * Handles Categories, Products, Billing, Stock Logs, Reports & 3D Visualization Data
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/database/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = $GLOBALS['test_input'] ?? json_decode(file_get_contents('php://input'), true) ?? $_POST ?? [];

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function jsonError($message, $status = 400) {
    jsonResponse(['success' => false, 'error' => $message], $status);
}

// Unit conversion helper
// Base unit -> Supported sub-units with multiplier to convert sub-unit to base unit
function getUnitMultiplier($baseUnit, $selectedUnit) {
    $baseUnit = strtolower(trim($baseUnit));
    $selectedUnit = strtolower(trim($selectedUnit));

    if ($baseUnit === $selectedUnit) {
        return 1.0;
    }

    // Weight conversions: Base = kg
    if ($baseUnit === 'kg') {
        if ($selectedUnit === 'g' || $selectedUnit === 'gram' || $selectedUnit === 'grams') return 0.001;
        if ($selectedUnit === 'mg') return 0.000001;
        if ($selectedUnit === 'quintal') return 100.0;
        if ($selectedUnit === 'ton') return 1000.0;
    }
    // Base = g
    if ($baseUnit === 'g') {
        if ($selectedUnit === 'kg') return 1000.0;
        if ($selectedUnit === 'mg') return 0.001;
    }

    // Volume conversions: Base = l (Liter)
    if ($baseUnit === 'l' || $baseUnit === 'liter' || $baseUnit === 'ltr') {
        if ($selectedUnit === 'ml' || $selectedUnit === 'milliliter') return 0.001;
    }
    if ($baseUnit === 'ml') {
        if ($selectedUnit === 'l' || $selectedUnit === 'liter') return 1000.0;
    }

    // Count / Quantity: Base = pcs
    if ($baseUnit === 'pcs' || $baseUnit === 'piece' || $baseUnit === 'nos') {
        if ($selectedUnit === 'dozen' || $selectedUnit === 'dz') return 12.0;
        if ($selectedUnit === 'box') return 10.0; // default multiplier or 1
    }

    // Length: Base = m
    if ($baseUnit === 'm' || $baseUnit === 'meter') {
        if ($selectedUnit === 'cm') return 0.01;
        if ($selectedUnit === 'mm') return 0.001;
    }

    return 1.0;
}

try {
    switch ($action) {

        // ================= CATEGORIES =================
        case 'get_categories':
            $stmt = $pdo->query("
                SELECT c.*, COUNT(p.id) as product_count 
                FROM categories c 
                LEFT JOIN products p ON p.category_id = c.id 
                GROUP BY c.id 
                ORDER BY c.name ASC
            ");
            jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'save_category':
            $id = isset($input['id']) ? (int)$input['id'] : null;
            $name = trim($input['name'] ?? '');
            $desc = trim($input['description'] ?? '');
            $color = trim($input['color'] ?? '#4f46e5');

            if (empty($name)) {
                jsonError("Category name is required.");
            }

            if ($id) {
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, color = ? WHERE id = ?");
                $stmt->execute([$name, $desc, $color, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO categories (name, description, color) VALUES (?, ?, ?)");
                $stmt->execute([$name, $desc, $color]);
                $id = $pdo->lastInsertId();
            }
            jsonResponse(['success' => true, 'message' => 'Category saved successfully.', 'id' => $id]);
            break;

        case 'delete_category':
            $id = (int)($input['id'] ?? 0);
            $check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
            $check->execute([$id]);
            if ($check->fetchColumn() > 0) {
                jsonError("Cannot delete category because it contains active products. Please move or delete products first.");
            }
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            jsonResponse(['success' => true, 'message' => 'Category deleted.']);
            break;

        // ================= PRODUCTS =================
        case 'get_products':
            $categoryId = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null;
            $search = trim($_GET['search'] ?? '');

            $sql = "
                SELECT p.*, c.name as category_name, c.color as category_color 
                FROM products p 
                JOIN categories c ON c.id = p.category_id 
                WHERE 1=1
            ";
            $params = [];

            if ($categoryId) {
                $sql .= " AND p.category_id = ?";
                $params[] = $categoryId;
            }
            if ($search !== '') {
                $sql .= " AND (p.name LIKE ? OR p.barcode LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            $sql .= " ORDER BY p.name ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'save_product':
            $id = isset($input['id']) && $input['id'] !== '' ? (int)$input['id'] : null;
            $catId = (int)($input['category_id'] ?? 0);
            $name = trim($input['name'] ?? '');
            $barcode = trim($input['barcode'] ?? '');
            $baseUnit = strtolower(trim($input['base_unit'] ?? 'kg'));
            $purchasePrice = (float)($input['purchase_price'] ?? 0);
            $sellingPrice = (float)($input['selling_price'] ?? 0);
            $stockQty = (float)($input['stock_qty'] ?? 0);
            $minAlert = (float)($input['min_stock_alert'] ?? 5);

            if (empty($name)) jsonError("Product name is required.");
            if ($catId <= 0) jsonError("Please select a valid category.");
            if ($sellingPrice < 0) jsonError("Selling price cannot be negative.");
            if ($purchasePrice < 0) jsonError("Purchase price cannot be negative.");

            // Generate barcode if empty
            if (empty($barcode)) {
                $barcode = 'PROD' . str_pad((string)time(), 8, '0', STR_PAD_LEFT);
            }

            if ($id) {
                // Fetch current stock
                $current = $pdo->prepare("SELECT stock_qty FROM products WHERE id = ?");
                $current->execute([$id]);
                $oldStock = (float)$current->fetchColumn();

                $stmt = $pdo->prepare("
                    UPDATE products 
                    SET category_id = ?, barcode = ?, name = ?, base_unit = ?, 
                        purchase_price = ?, selling_price = ?, stock_qty = ?, min_stock_alert = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$catId, $barcode, $name, $baseUnit, $purchasePrice, $sellingPrice, $stockQty, $minAlert, $id]);

                // Log adjustment if changed
                if ($oldStock != $stockQty) {
                    $diff = $stockQty - $oldStock;
                    $logStmt = $pdo->prepare("INSERT INTO stock_logs (product_id, type, qty, balance_after, note) VALUES (?, 'ADJUSTMENT', ?, ?, 'Manual Stock Adjustment')");
                    $logStmt->execute([$id, $diff, $stockQty]);
                }
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO products (category_id, barcode, name, base_unit, purchase_price, selling_price, stock_qty, min_stock_alert) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$catId, $barcode, $name, $baseUnit, $purchasePrice, $sellingPrice, $stockQty, $minAlert]);
                $id = $pdo->lastInsertId();

                // Initial stock log
                $logStmt = $pdo->prepare("INSERT INTO stock_logs (product_id, type, qty, balance_after, note) VALUES (?, 'INITIAL', ?, ?, 'Initial inventory stock')");
                $logStmt->execute([$id, $stockQty, $stockQty]);
            }

            jsonResponse(['success' => true, 'message' => 'Product saved successfully.', 'id' => $id]);
            break;

        case 'delete_product':
            $id = (int)($input['id'] ?? 0);
            // Check if product exists in bills
            $check = $pdo->prepare("SELECT COUNT(*) FROM bill_items WHERE product_id = ?");
            $check->execute([$id]);
            if ($check->fetchColumn() > 0) {
                jsonError("Cannot delete product because it has past bill transactions. You can set stock to 0 instead.");
            }
            $pdo->prepare("DELETE FROM stock_logs WHERE product_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
            jsonResponse(['success' => true, 'message' => 'Product deleted successfully.']);
            break;

        case 'update_stock':
            $productId = (int)($input['product_id'] ?? 0);
            $qty = (float)($input['qty'] ?? 0);
            $type = strtoupper(trim($input['type'] ?? 'PURCHASE')); // 'PURCHASE' or 'ADJUSTMENT'
            $note = trim($input['note'] ?? 'Stock in / purchase');

            if ($productId <= 0 || $qty == 0) {
                jsonError("Invalid product or quantity.");
            }

            $stmt = $pdo->prepare("SELECT stock_qty, name FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            if (!$product) jsonError("Product not found.");

            $newBalance = $product['stock_qty'] + $qty;
            if ($newBalance < 0) {
                jsonError("Resulting stock balance cannot be negative.");
            }

            $pdo->beginTransaction();
            $pdo->prepare("UPDATE products SET stock_qty = ? WHERE id = ?")->execute([$newBalance, $productId]);
            $pdo->prepare("INSERT INTO stock_logs (product_id, type, qty, balance_after, note) VALUES (?, ?, ?, ?, ?)")
                ->execute([$productId, $type, $qty, $newBalance, $note]);
            $pdo->commit();

            jsonResponse(['success' => true, 'message' => 'Stock updated successfully.', 'balance' => $newBalance]);
            break;

        // ================= BILLING & CART CHECKOUT =================
        case 'create_bill':
            $cart = $input['cart'] ?? [];
            if (empty($cart) || !is_array($cart)) {
                jsonError("Cart is empty.");
            }

            $customerName = trim($input['customer_name'] ?? 'Walk-in Customer');
            if (empty($customerName)) $customerName = 'Walk-in Customer';
            $customerPhone = trim($input['customer_phone'] ?? '');
            $paymentMethod = trim($input['payment_method'] ?? 'Cash');
            $discount = max(0, (float)($input['discount'] ?? 0));
            $taxRate = max(0, (float)($input['tax_rate'] ?? 0)); // percentage, e.g. 5%

            $pdo->beginTransaction();

            $subtotal = 0.0;
            $processedItems = [];

            // Generate unique bill number
            $billNumber = 'BILL-' . date('Ymd-His') . '-' . rand(100, 999);

            foreach ($cart as $item) {
                $pId = (int)($item['id'] ?? 0);
                $enteredQty = (float)($item['qty'] ?? 0);
                $selectedUnit = strtolower(trim($item['unit'] ?? ''));

                if ($pId <= 0 || $enteredQty <= 0) {
                    $pdo->rollBack();
                    jsonError("Invalid item or quantity in cart.");
                }

                // Lock & read product
                $pStmt = $pdo->prepare("SELECT id, name, base_unit, purchase_price, selling_price, stock_qty FROM products WHERE id = ?");
                $pStmt->execute([$pId]);
                $p = $pStmt->fetch();

                if (!$p) {
                    $pdo->rollBack();
                    jsonError("Product ID #{$pId} does not exist.");
                }

                if (empty($selectedUnit)) {
                    $selectedUnit = $p['base_unit'];
                }

                // Multiplier to convert selected unit qty into product's base unit qty
                $multiplier = getUnitMultiplier($p['base_unit'], $selectedUnit);
                $baseUnitQty = $enteredQty * $multiplier;

                // Price calculation:
                // Selling price is stored per Base Unit (e.g. ₹10 per 1 Kg)
                // If base is Kg and user buys 500g: baseUnitQty = 0.5.
                // Item total = 0.5 * 10 = ₹5.00!
                $itemTotal = round($baseUnitQty * $p['selling_price'], 2);
                $effectiveRatePerSelectedUnit = round($p['selling_price'] * $multiplier, 2);
                $itemDiscount = max(0, (float)($item['discount'] ?? 0));
                $finalItemTotal = max(0, round($itemTotal - $itemDiscount, 2));

                // Check stock
                if ($p['stock_qty'] < $baseUnitQty) {
                    $pdo->rollBack();
                    jsonError("Insufficient stock for '{$p['name']}'. Available: {$p['stock_qty']} {$p['base_unit']}, Requested: {$baseUnitQty} {$p['base_unit']}");
                }

                // Deduct stock
                $newStock = $p['stock_qty'] - $baseUnitQty;
                $pdo->prepare("UPDATE products SET stock_qty = ? WHERE id = ?")->execute([$newStock, $pId]);

                // Stock log
                $pdo->prepare("INSERT INTO stock_logs (product_id, type, qty, balance_after, note) VALUES (?, 'SALE', ?, ?, ?)")
                    ->execute([$pId, -$baseUnitQty, $newStock, "Bill #{$billNumber}"]);

                $subtotal += $itemTotal;

                $processedItems[] = [
                    'product_id' => $pId,
                    'product_name' => $p['name'],
                    'selected_unit' => $selectedUnit,
                    'entered_qty' => $enteredQty,
                    'base_unit_qty' => $baseUnitQty,
                    'unit_price' => $effectiveRatePerSelectedUnit,
                    'purchase_price' => $p['purchase_price'],
                    'item_total' => $itemTotal
                ];
            }

            // Calculate tax & grand total
            $taxAmount = round(($subtotal - $discount) * ($taxRate / 100), 2);
            if ($taxAmount < 0) $taxAmount = 0;
            $grandTotal = round(max(0, ($subtotal - $discount) + $taxAmount), 2);

            // Insert into bills
            $bStmt = $pdo->prepare("
                INSERT INTO bills (bill_number, customer_name, customer_phone, subtotal, discount, tax, grand_total, payment_method) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $bStmt->execute([$billNumber, $customerName, $customerPhone, $subtotal, $discount, $taxAmount, $grandTotal, $paymentMethod]);
            $billId = $pdo->lastInsertId();

            // Insert bill items
            $itemStmt = $pdo->prepare("
                INSERT INTO bill_items (bill_id, product_id, product_name, selected_unit, entered_qty, base_unit_qty, unit_price, purchase_price, item_total) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($processedItems as $it) {
                $itemStmt->execute([
                    $billId,
                    $it['product_id'],
                    $it['product_name'],
                    $it['selected_unit'],
                    $it['entered_qty'],
                    $it['base_unit_qty'],
                    $it['unit_price'],
                    $it['purchase_price'],
                    $it['item_total']
                ]);
            }

            $pdo->commit();

            jsonResponse([
                'success' => true,
                'message' => 'Bill created successfully.',
                'bill_id' => $billId,
                'bill_number' => $billNumber,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $taxAmount,
                'grand_total' => $grandTotal,
                'payment_method' => $paymentMethod,
                'items_count' => count($processedItems)
            ]);
            break;

        // ================= BILL HISTORY & DETAILS =================
        case 'get_bills':
            $search = trim($_GET['search'] ?? '');
            $fromDate = trim($_GET['from_date'] ?? '');
            $toDate = trim($_GET['to_date'] ?? '');
            $limit = min(200, max(10, (int)($_GET['limit'] ?? 50)));
            $offset = max(0, (int)($_GET['offset'] ?? 0));

            $sql = "
                SELECT 
                    b.*, 
                    COALESCE(i.item_count, 0) as item_count,
                    COALESCE(i.purchase_cost, 0) as purchase_cost,
                    COALESCE(r.restocked_cost, 0) as restocked_cost,
                    (b.grand_total - COALESCE(r.total_refunded, 0)) - (COALESCE(i.purchase_cost, 0) - COALESCE(r.restocked_cost, 0)) as net_profit,
                    b.grand_total - COALESCE(r.total_refunded, 0) as net_grand_total,
                    COALESCE(r.total_refunded, 0) as total_refunded,
                    COALESCE(r.return_count, 0) as return_count
                FROM bills b
                LEFT JOIN (
                    SELECT 
                        bill_id,
                        COUNT(id) as item_count,
                        COALESCE(SUM(base_unit_qty * purchase_price), 0) as purchase_cost
                    FROM bill_items
                    GROUP BY bill_id
                ) i ON i.bill_id = b.id
                LEFT JOIN (
                    SELECT 
                        r.bill_id,
                        COUNT(DISTINCT r.id) as return_count,
                        COALESCE(SUM(ri.refund_amount), 0) as total_refunded,
                        COALESCE(SUM(CASE WHEN ri.restock = 1 THEN ri.base_unit_qty * p.purchase_price ELSE 0 END), 0) as restocked_cost
                    FROM returns r
                    JOIN return_items ri ON ri.return_id = r.id
                    LEFT JOIN products p ON p.id = ri.product_id
                    GROUP BY r.bill_id
                ) r ON r.bill_id = b.id
                WHERE 1=1
            ";
            $params = [];

            if ($search !== '') {
                $sql .= " AND (b.bill_number LIKE ? OR b.customer_name LIKE ? OR b.customer_phone LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            if ($fromDate !== '') {
                $sql .= " AND date(b.created_at) >= date(?)";
                $params[] = $fromDate;
            }
            if ($toDate !== '') {
                $sql .= " AND date(b.created_at) <= date(?)";
                $params[] = $toDate;
            }

            $sql .= " ORDER BY b.id DESC LIMIT $limit OFFSET $offset";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $bills = $stmt->fetchAll();

            jsonResponse(['success' => true, 'data' => $bills]);
            break;

        case 'get_bill_details':
            $billId = (int)($_GET['id'] ?? 0);
            if ($billId <= 0) jsonError("Invalid bill ID.");

            $stmt = $pdo->prepare("SELECT * FROM bills WHERE id = ?");
            $stmt->execute([$billId]);
            $bill = $stmt->fetch();
            if (!$bill) jsonError("Bill not found.");

            $itemsStmt = $pdo->prepare("
                SELECT bi.*, p.base_unit as product_base_unit,
                       COALESCE(ret.returned_qty, 0) as already_returned_qty
                FROM bill_items bi 
                LEFT JOIN products p ON p.id = bi.product_id 
                LEFT JOIN (
                    SELECT bill_item_id, COALESCE(SUM(return_qty), 0) as returned_qty 
                    FROM return_items 
                    GROUP BY bill_item_id
                ) ret ON ret.bill_item_id = bi.id
                WHERE bi.bill_id = ?
            ");
            $itemsStmt->execute([$billId]);
            $bill['items'] = $itemsStmt->fetchAll();

            // Calculate profit breakdown for this bill
            $itemProfitSum = 0;
            $totalPurchaseCost = 0;
            foreach ($bill['items'] as &$it) {
                $cost = (float)$it['base_unit_qty'] * (float)$it['purchase_price'];
                $profit = (float)$it['item_total'] - $cost;
                $it['item_cost'] = round($cost, 2);
                $it['item_profit'] = round($profit, 2);
                $it['available_return_qty'] = max(0, (float)$it['entered_qty'] - (float)$it['already_returned_qty']);
                $totalPurchaseCost += $cost;
                $itemProfitSum += $profit;
            }
            // Fetch any returns on this bill with restocked cost
            $retStmt = $pdo->prepare("
                SELECT 
                    r.*,
                    COALESCE(SUM(ri.refund_amount), 0) as refund_sum,
                    COALESCE(SUM(CASE WHEN ri.restock = 1 THEN ri.base_unit_qty * p.purchase_price ELSE 0 END), 0) as bill_restocked_cost
                FROM returns r
                JOIN return_items ri ON ri.return_id = r.id
                LEFT JOIN products p ON p.id = ri.product_id
                WHERE r.bill_id = ?
                GROUP BY r.id
                ORDER BY r.id DESC
            ");
            $retStmt->execute([$billId]);
            $bill['returns'] = $retStmt->fetchAll();

            $totalRefunded = 0;
            $totalRestockedCost = 0;
            foreach ($bill['returns'] as $r) {
                $totalRefunded += (float)($r['total_refund'] ?? 0);
                $totalRestockedCost += (float)($r['bill_restocked_cost'] ?? 0);
            }
            $netPurchaseCost = max(0, $totalPurchaseCost - $totalRestockedCost);
            $bill['total_refunded'] = round($totalRefunded, 2);
            $bill['net_grand_total'] = round((float)$bill['grand_total'] - $totalRefunded, 2);
            $bill['total_purchase_cost'] = round($totalPurchaseCost, 2);
            $bill['net_purchase_cost'] = round($netPurchaseCost, 2);
            $bill['gross_profit'] = round((float)$bill['subtotal'] - $totalPurchaseCost, 2);
            $bill['net_profit'] = round($bill['net_grand_total'] - $netPurchaseCost, 2);

            // Settings for invoice printing (Store Name, Address, GST, UPI, etc.)
            $setStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings = [];
            while ($row = $setStmt->fetch()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            $bill['store'] = $settings;

            jsonResponse(['success' => true, 'data' => $bill]);
            break;

        // ================= PRODUCT RETURNS & REFUNDS =================
        case 'get_bill_for_return':
            $billQuery = trim($_GET['bill_query'] ?? $_GET['id'] ?? '');
            if (empty($billQuery)) {
                jsonError("Please enter a Bill Number or select an invoice.");
            }

            // Find bill by exact ID or exact Bill Number first
            $bStmt = $pdo->prepare("SELECT * FROM bills WHERE id = ? OR bill_number = ? LIMIT 1");
            $bStmt->execute([$billQuery, $billQuery]);
            $bill = $bStmt->fetch();

            if (!$bill) {
                // Try partial bill number search if not exact match
                $bStmt = $pdo->prepare("SELECT * FROM bills WHERE bill_number LIKE ? ORDER BY id DESC LIMIT 1");
                $bStmt->execute(["%$billQuery%"]);
                $bill = $bStmt->fetch();
            }

            if (!$bill) {
                jsonError("Bill '{$billQuery}' not found. Please verify the Bill Number.");
            }

            $itemsStmt = $pdo->prepare("
                SELECT 
                    bi.*, 
                    p.base_unit as product_base_unit,
                    p.stock_qty as current_stock,
                    COALESCE(ret.returned_base_qty, 0) as already_returned_base_qty,
                    COALESCE(ret.returned_qty, 0) as already_returned_qty,
                    COALESCE(ret.refunded_sum, 0) as already_refunded_amount
                FROM bill_items bi
                LEFT JOIN products p ON p.id = bi.product_id
                LEFT JOIN (
                    SELECT 
                        bill_item_id,
                        COALESCE(SUM(base_unit_qty), 0) as returned_base_qty,
                        COALESCE(SUM(return_qty), 0) as returned_qty,
                        COALESCE(SUM(refund_amount), 0) as refunded_sum
                    FROM return_items
                    GROUP BY bill_item_id
                ) ret ON ret.bill_item_id = bi.id
                WHERE bi.bill_id = ?
            ");
            $itemsStmt->execute([$bill['id']]);
            $items = $itemsStmt->fetchAll();

            $billDiscount = (float)($bill['discount'] ?? 0);
            $billSubtotal = (float)($bill['subtotal'] ?? 0);
            $discountRatio = ($billSubtotal > 0 && $billDiscount > 0) ? ($billDiscount / $billSubtotal) : 0;
            $bill['has_discount'] = ($billDiscount > 0);

            foreach ($items as &$it) {
                $baseUnit = !empty($it['product_base_unit']) ? $it['product_base_unit'] : $it['selected_unit'];
                $it['base_unit'] = $baseUnit;
                $alreadyReturnedBase = (float)$it['already_returned_base_qty'];
                $availableBaseQty = max(0, (float)$it['base_unit_qty'] - $alreadyReturnedBase);
                $it['available_base_qty'] = round($availableBaseQty, 4);

                $multiplier = getUnitMultiplier($baseUnit, $it['selected_unit']);
                $availInSelected = $multiplier > 0 ? ($availableBaseQty / $multiplier) : $availableBaseQty;
                $it['available_qty'] = round($availInSelected, 3);

                // Original / Actual rates (selling price before discount)
                $actualUnitRate = (float)$it['unit_price'];
                $actualBaseRate = (float)$it['base_unit_qty'] > 0 ? round((float)$it['item_total'] / (float)$it['base_unit_qty'], 4) : $actualUnitRate;

                // Discount calculation for this item
                $itemDiscount = round((float)$it['item_total'] * $discountRatio, 2);
                $discountedItemTotal = max(0, (float)$it['item_total'] - $itemDiscount);
                $discountedUnitRate = (float)$it['entered_qty'] > 0 ? round($discountedItemTotal / (float)$it['entered_qty'], 2) : $actualUnitRate;
                $discountedBaseRate = (float)$it['base_unit_qty'] > 0 ? round($discountedItemTotal / (float)$it['base_unit_qty'], 4) : $actualBaseRate;

                $it['actual_unit_rate'] = $actualUnitRate;
                $it['actual_base_rate'] = $actualBaseRate;
                $it['discounted_unit_rate'] = $discountedUnitRate;
                $it['discounted_base_rate'] = $discountedBaseRate;
                $it['item_discount'] = $itemDiscount;
                $it['has_discount'] = ($itemDiscount > 0 || $billDiscount > 0);
                $it['effective_unit_rate'] = $it['has_discount'] ? $discountedUnitRate : $actualUnitRate;
            }

            // Previous returns on this bill
            $prevReturnsStmt = $pdo->prepare("SELECT * FROM returns WHERE bill_id = ? ORDER BY id DESC");
            $prevReturnsStmt->execute([$bill['id']]);
            $previousReturns = $prevReturnsStmt->fetchAll();

            // Store settings
            $setStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings = [];
            while ($row = $setStmt->fetch()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }

            jsonResponse([
                'success' => true,
                'data' => [
                    'bill' => $bill,
                    'items' => $items,
                    'previous_returns' => $previousReturns,
                    'store' => $settings
                ]
            ]);
            break;

        case 'process_return':
            $billId = (int)($input['bill_id'] ?? 0);
            $itemsToReturn = $input['items'] ?? [];
            $refundMethod = trim($input['refund_method'] ?? 'Cash');
            $returnReason = trim($input['return_reason'] ?? 'Customer Return');
            $customerName = trim($input['customer_name'] ?? '');
            $customerPhone = trim($input['customer_phone'] ?? '');

            if (empty($itemsToReturn) || !is_array($itemsToReturn)) {
                jsonError("No items selected for return.");
            }

            $pdo->beginTransaction();

            $bill = null;
            $billNumber = 'DIRECT-RETURN';
            if ($billId > 0) {
                // Verify bill exists
                $bStmt = $pdo->prepare("SELECT * FROM bills WHERE id = ?");
                $bStmt->execute([$billId]);
                $bill = $bStmt->fetch();
                if (!$bill) {
                    $pdo->rollBack();
                    jsonError("Original bill not found.");
                }
                $billNumber = $bill['bill_number'];
                if (empty($customerName)) {
                    $customerName = $bill['customer_name'] ?: 'Walk-in Customer';
                }
                if (empty($customerPhone)) {
                    $customerPhone = $bill['customer_phone'] ?: '';
                }
            } else {
                if (empty($customerName)) {
                    $customerName = 'Walk-in Customer';
                }
            }

            $returnNumber = 'RET-' . date('Ymd-His') . '-' . rand(100, 999);
            $totalRefund = 0.0;
            $processedItems = [];

            foreach ($itemsToReturn as $itemReq) {
                $billItemId = (int)($itemReq['bill_item_id'] ?? 0);
                $productId = (int)($itemReq['product_id'] ?? 0);
                $returnQty = max(0, (float)($itemReq['return_qty'] ?? 0));
                $restock = !empty($itemReq['restock']) ? 1 : 0;

                if ($returnQty <= 0) {
                    continue; // Skip unselected or 0 qty items
                }

                if ($billId > 0) {
                    if ($billItemId <= 0) {
                        continue;
                    }
                    // Check original bill item
                    $biStmt = $pdo->prepare("SELECT * FROM bill_items WHERE id = ? AND bill_id = ?");
                    $biStmt->execute([$billItemId, $billId]);
                    $bi = $biStmt->fetch();
                    if (!$bi) {
                        $pdo->rollBack();
                        jsonError("Bill item #{$billItemId} not found on this bill.");
                    }

                    // Check already returned base qty
                    $retCheck = $pdo->prepare("SELECT COALESCE(SUM(base_unit_qty), 0) FROM return_items WHERE bill_item_id = ?");
                    $retCheck->execute([$billItemId]);
                    $alreadyReturnedBase = (float)$retCheck->fetchColumn();

                    $pStmt = $pdo->prepare("SELECT id, name, base_unit, stock_qty FROM products WHERE id = ?");
                    $pStmt->execute([$bi['product_id']]);
                    $prod = $pStmt->fetch();

                    $prodBaseUnit = $prod ? $prod['base_unit'] : $bi['selected_unit'];
                    $returnSelectedUnit = trim($itemReq['selected_unit'] ?? $bi['selected_unit']);
                    $multiplier = getUnitMultiplier($prodBaseUnit, $returnSelectedUnit);
                    $returnBaseQty = round($returnQty * $multiplier, 4);

                    $maxAvailBase = round((float)$bi['base_unit_qty'] - $alreadyReturnedBase, 4);
                    if ($returnBaseQty > $maxAvailBase + 0.0001) {
                        $pdo->rollBack();
                        $availInSelected = $multiplier > 0 ? round($maxAvailBase / $multiplier, 3) : $maxAvailBase;
                        jsonError("Cannot return {$returnQty} {$returnSelectedUnit} of '{$bi['product_name']}'. Only {$availInSelected} {$returnSelectedUnit} available.");
                    }

                    // Determine refund rate per selected unit (actual MRP, discounted paid price, or custom)
                    $priceType = trim($itemReq['price_type'] ?? 'discount');
                    $unitRefundRate = 0.0;
                    if (isset($itemReq['refund_rate']) && is_numeric($itemReq['refund_rate']) && (float)$itemReq['refund_rate'] >= 0) {
                        $unitRefundRate = round((float)$itemReq['refund_rate'], 4);
                    } else {
                        $actualBaseRate = (float)$bi['base_unit_qty'] > 0 ? ((float)$bi['item_total'] / (float)$bi['base_unit_qty']) : (float)$bi['unit_price'];
                        $discountRatio = ($bill && (float)$bill['subtotal'] > 0) ? ((float)$bill['discount'] / (float)$bill['subtotal']) : 0;
                        $discountedBaseRate = $actualBaseRate * (1 - $discountRatio);

                        $baseRate = ($priceType === 'actual') ? $actualBaseRate : $discountedBaseRate;
                        $unitRefundRate = round($baseRate * $multiplier, 4);
                    }

                    $itemRefund = round($returnQty * $unitRefundRate, 2);
                    $totalRefund += $itemRefund;

                    // Restock back to inventory if checked
                    if ($restock && $prod) {
                        $newStock = $prod['stock_qty'] + $returnBaseQty;
                        $pdo->prepare("UPDATE products SET stock_qty = ? WHERE id = ?")->execute([$newStock, $prod['id']]);

                        // Log in stock_logs
                        $pdo->prepare("INSERT INTO stock_logs (product_id, type, qty, balance_after, note) VALUES (?, 'RETURN', ?, ?, ?)")
                            ->execute([$prod['id'], $returnBaseQty, $newStock, "Return #{$returnNumber} ({$returnQty} {$returnSelectedUnit} @ {$priceType})"]);
                    }

                    $processedItems[] = [
                        'bill_item_id' => $billItemId,
                        'product_id' => $bi['product_id'],
                        'product_name' => $bi['product_name'],
                        'selected_unit' => $returnSelectedUnit,
                        'return_qty' => $returnQty,
                        'base_unit_qty' => $returnBaseQty,
                        'refund_rate' => $unitRefundRate,
                        'refund_amount' => $itemRefund,
                        'restock' => $restock
                    ];
                } else {
                    // Direct product return without bill
                    if ($productId <= 0) {
                        continue;
                    }
                    $pStmt = $pdo->prepare("SELECT id, name, base_unit, selling_price, stock_qty FROM products WHERE id = ?");
                    $pStmt->execute([$productId]);
                    $prod = $pStmt->fetch();
                    if (!$prod) {
                        $pdo->rollBack();
                        jsonError("Product #{$productId} not found.");
                    }

                    $selectedUnit = trim($itemReq['selected_unit'] ?? $prod['base_unit']);
                    $refundRate = isset($itemReq['refund_rate']) && (float)$itemReq['refund_rate'] >= 0 ? (float)$itemReq['refund_rate'] : (float)$prod['selling_price'];
                    $itemRefund = round($returnQty * $refundRate, 2);
                    $totalRefund += $itemRefund;

                    $multiplier = getUnitMultiplier($prod['base_unit'], $selectedUnit);
                    $baseUnitQty = round($returnQty * $multiplier, 4);

                    if ($restock) {
                        $newStock = $prod['stock_qty'] + $baseUnitQty;
                        $pdo->prepare("UPDATE products SET stock_qty = ? WHERE id = ?")->execute([$newStock, $prod['id']]);

                        $pdo->prepare("INSERT INTO stock_logs (product_id, type, qty, balance_after, note) VALUES (?, 'RETURN', ?, ?, ?)")
                            ->execute([$prod['id'], $baseUnitQty, $newStock, "Direct Return #{$returnNumber} (No Bill)"]);
                    }

                    $processedItems[] = [
                        'bill_item_id' => null,
                        'product_id' => $prod['id'],
                        'product_name' => $prod['name'],
                        'selected_unit' => $selectedUnit,
                        'return_qty' => $returnQty,
                        'base_unit_qty' => $baseUnitQty,
                        'refund_rate' => round($refundRate, 2),
                        'refund_amount' => $itemRefund,
                        'restock' => $restock
                    ];
                }
            }

            if (empty($processedItems)) {
                $pdo->rollBack();
                jsonError("No items selected for return. Please enter return quantity.");
            }

            // Insert into returns
            $rStmt = $pdo->prepare("
                INSERT INTO returns (return_number, bill_id, bill_number, customer_name, customer_phone, total_refund, refund_method, return_reason)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $rStmt->execute([
                $returnNumber,
                $bill ? $bill['id'] : null,
                $billNumber,
                $customerName,
                $customerPhone,
                $totalRefund,
                $refundMethod,
                $returnReason
            ]);
            $returnId = $pdo->lastInsertId();

            // Insert return_items
            $riStmt = $pdo->prepare("
                INSERT INTO return_items (return_id, bill_item_id, product_id, product_name, selected_unit, return_qty, base_unit_qty, refund_rate, refund_amount, restock)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($processedItems as $ri) {
                $riStmt->execute([
                    $returnId,
                    $ri['bill_item_id'],
                    $ri['product_id'],
                    $ri['product_name'],
                    $ri['selected_unit'],
                    $ri['return_qty'],
                    $ri['base_unit_qty'],
                    $ri['refund_rate'],
                    $ri['refund_amount'],
                    $ri['restock']
                ]);
            }

            $pdo->commit();

            // Store settings
            $setStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings = [];
            while ($row = $setStmt->fetch()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }

            jsonResponse([
                'success' => true,
                'message' => "Return processed successfully! Total refund: ₹" . number_format($totalRefund, 2),
                'data' => [
                    'id' => $returnId,
                    'return_id' => $returnId,
                    'return_number' => $returnNumber,
                    'bill_id' => $bill ? $bill['id'] : null,
                    'bill_number' => $billNumber,
                    'customer_name' => $customerName,
                    'customer_phone' => $customerPhone,
                    'total_refund' => $totalRefund,
                    'refund_method' => $refundMethod,
                    'return_reason' => $returnReason,
                    'created_at' => date('Y-m-d H:i:s'),
                    'items' => $processedItems,
                    'store' => $settings
                ]
            ]);
            break;

        case 'get_returns':
            $search = trim($_GET['search'] ?? '');
            $fromDate = trim($_GET['from_date'] ?? '');
            $toDate = trim($_GET['to_date'] ?? '');
            $limit = min(200, max(10, (int)($_GET['limit'] ?? 50)));
            $offset = max(0, (int)($_GET['offset'] ?? 0));

            $sql = "
                SELECT 
                    r.*,
                    COALESCE(ri.item_count, 0) as item_count
                FROM returns r
                LEFT JOIN (
                    SELECT return_id, COUNT(id) as item_count
                    FROM return_items
                    GROUP BY return_id
                ) ri ON ri.return_id = r.id
                WHERE 1=1
            ";
            $params = [];

            if ($search !== '') {
                $sql .= " AND (r.return_number LIKE ? OR r.bill_number LIKE ? OR r.customer_name LIKE ? OR r.customer_phone LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            if ($fromDate !== '') {
                $sql .= " AND date(r.created_at) >= date(?)";
                $params[] = $fromDate;
            }
            if ($toDate !== '') {
                $sql .= " AND date(r.created_at) <= date(?)";
                $params[] = $toDate;
            }

            $sql .= " ORDER BY r.id DESC LIMIT $limit OFFSET $offset";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $returns = $stmt->fetchAll();

            jsonResponse(['success' => true, 'data' => $returns]);
            break;

        case 'get_return_details':
            $returnId = (int)($_GET['id'] ?? 0);
            if ($returnId <= 0) jsonError("Invalid return ID.");

            $stmt = $pdo->prepare("SELECT * FROM returns WHERE id = ?");
            $stmt->execute([$returnId]);
            $ret = $stmt->fetch();
            if (!$ret) jsonError("Return voucher not found.");

            $itemsStmt = $pdo->prepare("SELECT * FROM return_items WHERE return_id = ?");
            $itemsStmt->execute([$returnId]);
            $ret['items'] = $itemsStmt->fetchAll();

            // Settings for printing
            $setStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings = [];
            while ($row = $setStmt->fetch()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            $ret['store'] = $settings;

            jsonResponse(['success' => true, 'data' => $ret]);
            break;

        case 'get_returned_products':
            $search = trim($_GET['search'] ?? '');
            $fromDate = trim($_GET['from_date'] ?? '');
            $toDate = trim($_GET['to_date'] ?? '');
            $restock = isset($_GET['restock']) && $_GET['restock'] !== '' ? (int)$_GET['restock'] : null;
            $limit = min(500, max(10, (int)($_GET['limit'] ?? 100)));
            $offset = max(0, (int)($_GET['offset'] ?? 0));

            $baseFilterSql = " WHERE 1=1 ";
            $params = [];

            if ($search !== '') {
                $baseFilterSql .= " AND (ri.product_name LIKE ? OR r.return_number LIKE ? OR r.bill_number LIKE ? OR r.customer_name LIKE ? OR r.customer_phone LIKE ? OR p.barcode LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            if ($fromDate !== '') {
                $baseFilterSql .= " AND date(ri.created_at) >= date(?)";
                $params[] = $fromDate;
            }
            if ($toDate !== '') {
                $baseFilterSql .= " AND date(ri.created_at) <= date(?)";
                $params[] = $toDate;
            }
            if ($restock !== null) {
                $baseFilterSql .= " AND ri.restock = ?";
                $params[] = $restock;
            }

            // Summary metrics query
            $sumSql = "
                SELECT 
                    COUNT(ri.id) as total_items_count,
                    COALESCE(SUM(ri.refund_amount), 0) as total_refund_amount,
                    COALESCE(SUM(ri.return_qty), 0) as total_units_returned,
                    COALESCE(SUM(CASE WHEN ri.restock = 1 THEN 1 ELSE 0 END), 0) as restocked_items_count,
                    COALESCE(SUM(CASE WHEN ri.restock = 1 THEN ri.refund_amount ELSE 0 END), 0) as restocked_value,
                    COALESCE(SUM(CASE WHEN ri.restock = 0 THEN 1 ELSE 0 END), 0) as damaged_items_count,
                    COALESCE(SUM(CASE WHEN ri.restock = 0 THEN ri.refund_amount ELSE 0 END), 0) as damaged_loss
                FROM return_items ri
                JOIN returns r ON r.id = ri.return_id
                LEFT JOIN products p ON p.id = ri.product_id
                $baseFilterSql
            ";
            $sumStmt = $pdo->prepare($sumSql);
            $sumStmt->execute($params);
            $summary = $sumStmt->fetch(PDO::FETCH_ASSOC);

            // Detailed rows
            $dataSql = "
                SELECT 
                    ri.*,
                    r.return_number,
                    r.bill_id,
                    r.bill_number,
                    r.customer_name,
                    r.customer_phone,
                    r.refund_method,
                    r.return_reason,
                    r.created_at as voucher_date,
                    p.barcode,
                    p.stock_qty as current_stock,
                    c.name as category_name,
                    c.color as category_color
                FROM return_items ri
                JOIN returns r ON r.id = ri.return_id
                LEFT JOIN products p ON p.id = ri.product_id
                LEFT JOIN categories c ON c.id = p.category_id
                $baseFilterSql
                ORDER BY ri.id DESC LIMIT $limit OFFSET $offset
            ";
            $dataStmt = $pdo->prepare($dataSql);
            $dataStmt->execute($params);
            $items = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

            jsonResponse([
                'success' => true,
                'data' => $items,
                'summary' => [
                    'total_items_count' => (int)($summary['total_items_count'] ?? 0),
                    'total_refund_amount' => (float)($summary['total_refund_amount'] ?? 0),
                    'total_units_returned' => (float)($summary['total_units_returned'] ?? 0),
                    'restocked_items_count' => (int)($summary['restocked_items_count'] ?? 0),
                    'restocked_value' => (float)($summary['restocked_value'] ?? 0),
                    'damaged_items_count' => (int)($summary['damaged_items_count'] ?? 0),
                    'damaged_loss' => (float)($summary['damaged_loss'] ?? 0)
                ]
            ]);
            break;

        // ================= REPORTS & ANALYTICS =================
        case 'get_reports':
            $period = trim($_GET['period'] ?? 'today'); // 'today', 'day', 'daily', 'week', 'month', 'custom'
            $today = date('Y-m-d');

            if ($period === 'today') {
                $startDate = $today;
                $endDate = $today;
                $periodLabel = "Today (" . date('d M Y') . ")";
            } elseif ($period === 'day' || $period === 'daily') {
                $date = trim($_GET['date'] ?? $today);
                if (empty($date)) $date = $today;
                $startDate = $date;
                $endDate = $date;
                $periodLabel = "Day: " . date('d M Y', strtotime($date));
            } elseif ($period === 'week') {
                $startDate = date('Y-m-d', strtotime('-6 days'));
                $endDate = $today;
                $periodLabel = "This Week (" . date('d M', strtotime($startDate)) . " - " . date('d M Y', strtotime($endDate)) . ")";
            } elseif ($period === 'month') {
                $m = trim($_GET['month'] ?? date('Y-m'));
                if (empty($m)) $m = date('Y-m');
                $startDate = date('Y-m-01', strtotime($m . '-01'));
                $endDate = date('Y-m-t', strtotime($m . '-01'));
                $periodLabel = "Month: " . date('F Y', strtotime($m . '-01'));
            } elseif ($period === 'custom') {
                $startDate = trim($_GET['from_date'] ?? $today);
                $endDate = trim($_GET['to_date'] ?? $today);
                if (empty($startDate)) $startDate = $today;
                if (empty($endDate)) $endDate = $today;
                if ($startDate > $endDate) {
                    $tmp = $startDate; $startDate = $endDate; $endDate = $tmp;
                }
                $periodLabel = "Specified: " . date('d M Y', strtotime($startDate)) . " to " . date('d M Y', strtotime($endDate));
            } else {
                $startDate = $today;
                $endDate = $today;
                $periodLabel = "Today (" . date('d M Y') . ")";
            }

            // Summary of bills in selected period
            $bStmt = $pdo->prepare("
                SELECT 
                    COUNT(id) as total_bills,
                    COALESCE(SUM(subtotal), 0) as total_subtotal,
                    COALESCE(SUM(discount), 0) as total_discount,
                    COALESCE(SUM(tax), 0) as total_tax,
                    COALESCE(SUM(grand_total), 0) as total_sales
                FROM bills
                WHERE date(created_at) >= ? AND date(created_at) <= ?
            ");
            $bStmt->execute([$startDate, $endDate]);
            $bSummary = $bStmt->fetch(PDO::FETCH_ASSOC);

            // Summary of items sold in selected period
            $iStmt = $pdo->prepare("
                SELECT 
                    COALESCE(SUM(bi.entered_qty), 0) as total_units_sold,
                    COALESCE(SUM(bi.item_total), 0) as total_item_revenue,
                    COALESCE(SUM(bi.base_unit_qty * bi.purchase_price), 0) as total_purchase_cost,
                    COALESCE(SUM(bi.item_total - (bi.base_unit_qty * bi.purchase_price)), 0) as gross_profit
                FROM bill_items bi
                JOIN bills b ON b.id = bi.bill_id
                WHERE date(b.created_at) >= ? AND date(b.created_at) <= ?
            ");
            $iStmt->execute([$startDate, $endDate]);
            $iSummary = $iStmt->fetch(PDO::FETCH_ASSOC);

            // Summary of returns in selected period (including restocked cost)
            $retStmt = $pdo->prepare("
                SELECT 
                    COUNT(DISTINCT r.id) as total_returns_count,
                    COALESCE(SUM(ri.refund_amount), 0) as total_refunds,
                    COALESCE(SUM(CASE WHEN ri.restock = 1 THEN ri.base_unit_qty * p.purchase_price ELSE 0 END), 0) as total_restocked_cost
                FROM returns r
                JOIN return_items ri ON ri.return_id = r.id
                LEFT JOIN products p ON p.id = ri.product_id
                WHERE date(r.created_at) >= ? AND date(r.created_at) <= ?
            ");
            $retStmt->execute([$startDate, $endDate]);
            $retSummary = $retStmt->fetch(PDO::FETCH_ASSOC);

            $totalRefunds = (float)($retSummary['total_refunds'] ?? 0);
            $totalRestockedCost = (float)($retSummary['total_restocked_cost'] ?? 0);
            $totalSales = (float)$bSummary['total_sales'];
            $netSales = round($totalSales - $totalRefunds, 2);
            $totalSubtotal = (float)$bSummary['total_subtotal'];
            $totalCost = (float)$iSummary['total_purchase_cost'];
            $netCost = round(max(0, $totalCost - $totalRestockedCost), 2);
            $totalDiscount = (float)$bSummary['total_discount'];
            $totalTax = (float)$bSummary['total_tax'];
            $grossProfit = round(($totalSubtotal - $totalRefunds) - $netCost, 2);
            $netProfit = round($netSales - $netCost, 2);
            $margin = $netSales > 0 ? round(($netProfit / $netSales) * 100, 1) : 0.0;

            // Day-wise breakdown for the period (deducting returns and accounting for restocked goods)
            $dailyStmt = $pdo->prepare("
                SELECT 
                    d.sale_date,
                    d.bills_count,
                    d.total_sales as sales_amount,
                    COALESCE(ret.daily_refunds, 0) as refund_amount,
                    d.total_sales - COALESCE(ret.daily_refunds, 0) as net_sales,
                    d.total_discount,
                    COALESCE(i.total_cost, 0) as purchase_cost,
                    COALESCE(ret.daily_restocked_cost, 0) as restocked_cost,
                    (d.total_sales - COALESCE(ret.daily_refunds, 0)) - (COALESCE(i.total_cost, 0) - COALESCE(ret.daily_restocked_cost, 0)) as estimated_profit,
                    (d.total_sales - COALESCE(ret.daily_refunds, 0)) - (COALESCE(i.total_cost, 0) - COALESCE(ret.daily_restocked_cost, 0)) as net_profit
                FROM (
                    SELECT 
                        date(created_at) as sale_date,
                        COUNT(id) as bills_count,
                        COALESCE(SUM(subtotal), 0) as total_subtotal,
                        COALESCE(SUM(grand_total), 0) as total_sales,
                        COALESCE(SUM(discount), 0) as total_discount
                    FROM bills
                    WHERE date(created_at) >= ? AND date(created_at) <= ?
                    GROUP BY date(created_at)
                ) d
                LEFT JOIN (
                    SELECT 
                        date(b.created_at) as item_date,
                        COALESCE(SUM(bi.base_unit_qty * bi.purchase_price), 0) as total_cost
                    FROM bill_items bi
                    JOIN bills b ON b.id = bi.bill_id
                    WHERE date(b.created_at) >= ? AND date(b.created_at) <= ?
                    GROUP BY date(b.created_at)
                ) i ON i.item_date = d.sale_date
                LEFT JOIN (
                    SELECT 
                        date(r.created_at) as ret_date,
                        COALESCE(SUM(ri.refund_amount), 0) as daily_refunds,
                        COALESCE(SUM(CASE WHEN ri.restock = 1 THEN ri.base_unit_qty * p.purchase_price ELSE 0 END), 0) as daily_restocked_cost
                    FROM returns r
                    JOIN return_items ri ON ri.return_id = r.id
                    LEFT JOIN products p ON p.id = ri.product_id
                    WHERE date(r.created_at) >= ? AND date(r.created_at) <= ?
                    GROUP BY date(r.created_at)
                ) ret ON ret.ret_date = d.sale_date
                ORDER BY d.sale_date ASC
            ");
            $dailyStmt->execute([$startDate, $endDate, $startDate, $endDate, $startDate, $endDate]);
            $dailyBreakdown = $dailyStmt->fetchAll(PDO::FETCH_ASSOC);

            // Top Products Performance in this period
            $topStmt = $pdo->prepare("
                SELECT 
                    bi.product_name,
                    COUNT(DISTINCT bi.bill_id) as order_count,
                    COALESCE(SUM(bi.entered_qty), 0) as units_sold,
                    bi.selected_unit,
                    COALESCE(SUM(bi.item_total), 0) as total_revenue,
                    COALESCE(SUM(bi.base_unit_qty * bi.purchase_price), 0) as total_cost,
                    COALESCE(SUM(bi.item_total - (bi.base_unit_qty * bi.purchase_price)), 0) as item_profit
                FROM bill_items bi
                JOIN bills b ON b.id = bi.bill_id
                WHERE date(b.created_at) >= ? AND date(b.created_at) <= ?
                GROUP BY bi.product_name, bi.selected_unit
                ORDER BY item_profit DESC
                LIMIT 15
            ");
            $topStmt->execute([$startDate, $endDate]);
            $topProducts = $topStmt->fetchAll(PDO::FETCH_ASSOC);

            // Category Wise Sales Performance
            $catStmt = $pdo->prepare("
                SELECT 
                    c.name as category_name,
                    c.color as category_color,
                    COUNT(DISTINCT bi.id) as total_orders,
                    COALESCE(SUM(bi.item_total), 0) as total_revenue,
                    COALESCE(SUM(bi.base_unit_qty * bi.purchase_price), 0) as total_cost,
                    COALESCE(SUM(bi.item_total - (bi.base_unit_qty * bi.purchase_price)), 0) as total_profit
                FROM categories c
                LEFT JOIN products p ON p.category_id = c.id
                LEFT JOIN bill_items bi ON bi.product_id = p.id
                LEFT JOIN bills b ON b.id = bi.bill_id AND date(b.created_at) >= ? AND date(b.created_at) <= ?
                GROUP BY c.id
                ORDER BY total_revenue DESC
            ");
            $catStmt->execute([$startDate, $endDate]);
            $categorySales = $catStmt->fetchAll(PDO::FETCH_ASSOC);

            // Bill-by-bill list in this period with profit adjusted for returns
            $billsStmt = $pdo->prepare("
                SELECT 
                    b.id,
                    b.bill_number,
                    b.created_at,
                    b.customer_name,
                    b.payment_method,
                    b.subtotal,
                    b.discount,
                    b.tax,
                    b.grand_total,
                    COALESCE(r.total_refunded, 0) as total_refunded,
                    b.grand_total - COALESCE(r.total_refunded, 0) as net_grand_total,
                    COALESCE(i.purchase_cost, 0) as purchase_cost,
                    COALESCE(r.restocked_cost, 0) as restocked_cost,
                    (b.grand_total - COALESCE(r.total_refunded, 0)) - (COALESCE(i.purchase_cost, 0) - COALESCE(r.restocked_cost, 0)) as bill_profit,
                    COALESCE(i.item_count, 0) as item_count
                FROM bills b
                LEFT JOIN (
                    SELECT 
                        bill_id,
                        COUNT(id) as item_count,
                        COALESCE(SUM(base_unit_qty * purchase_price), 0) as purchase_cost
                    FROM bill_items
                    GROUP BY bill_id
                ) i ON i.bill_id = b.id
                LEFT JOIN (
                    SELECT 
                        r.bill_id,
                        COALESCE(SUM(ri.refund_amount), 0) as total_refunded,
                        COALESCE(SUM(CASE WHEN ri.restock = 1 THEN ri.base_unit_qty * p.purchase_price ELSE 0 END), 0) as restocked_cost
                    FROM returns r
                    JOIN return_items ri ON ri.return_id = r.id
                    LEFT JOIN products p ON p.id = ri.product_id
                    GROUP BY r.bill_id
                ) r ON r.bill_id = b.id
                WHERE date(b.created_at) >= ? AND date(b.created_at) <= ?
                ORDER BY b.id DESC
                LIMIT 100
            ");
            $billsStmt->execute([$startDate, $endDate]);
            $periodBills = $billsStmt->fetchAll(PDO::FETCH_ASSOC);

            // Overall Stock Valuation & Balance
            $stockSummary = $pdo->query("
                SELECT 
                    COUNT(id) as total_products,
                    COALESCE(SUM(stock_qty * purchase_price), 0) as stock_valuation_cost,
                    COALESCE(SUM(stock_qty * selling_price), 0) as stock_valuation_retail,
                    COALESCE(SUM(stock_qty), 0) as total_units_in_stock,
                    SUM(CASE WHEN stock_qty <= min_stock_alert THEN 1 ELSE 0 END) as low_stock_count,
                    SUM(CASE WHEN stock_qty <= 0 THEN 1 ELSE 0 END) as out_of_stock_count
                FROM products
            ")->fetch(PDO::FETCH_ASSOC);

            // Monthly stats (current month)
            $currentMonth = date('Y-m');
            $monthlyStmt = $pdo->query("
                SELECT 
                    COUNT(DISTINCT b.id) as total_bills,
                    COALESCE(SUM(b.grand_total), 0) as total_sales,
                    COALESCE(SUM(bi.base_unit_qty * bi.purchase_price), 0) as total_cost,
                    COALESCE(SUM(bi.item_total - (bi.base_unit_qty * bi.purchase_price)), 0) - COALESCE(SUM(b.discount), 0) as net_profit
                FROM bills b
                LEFT JOIN bill_items bi ON bi.bill_id = b.id
                WHERE strftime('%Y-%m', b.created_at) = '$currentMonth'
            ");
            $monthlyStats = $monthlyStmt->fetch(PDO::FETCH_ASSOC);

            jsonResponse([
                'success' => true,
                'filter' => [
                    'period' => $period,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'label' => $periodLabel
                ],
                'summary' => [
                    'total_bills' => (int)$bSummary['total_bills'],
                    'total_sales' => $totalSales,
                    'total_returns' => $totalRefunds,
                    'net_sales' => $netSales,
                    'total_subtotal' => $totalSubtotal,
                    'total_discount' => $totalDiscount,
                    'total_tax' => $totalTax,
                    'total_purchase_cost' => $totalCost,
                    'total_units_sold' => (float)$iSummary['total_units_sold'],
                    'gross_profit' => $grossProfit,
                    'net_profit' => $netProfit,
                    'profit_margin' => $margin
                ],
                'daily' => [
                    'total_bills' => (int)$bSummary['total_bills'],
                    'total_sales' => $totalSales,
                    'total_cost' => $totalCost,
                    'net_profit' => $netProfit,
                    'profit_margin' => $margin
                ],
                'weekly' => $dailyBreakdown,
                'daily_breakdown' => $dailyBreakdown,
                'monthly' => $monthlyStats,
                'category_sales' => $categorySales,
                'top_products' => $topProducts,
                'bills_list' => $periodBills,
                'stock_summary' => $stockSummary
            ]);
            break;

        // ================= 3D SHELF / INVENTORY DATA FOR THREE.JS =================
        case 'get_3d_shelf_data':
            // Renders 3D shelves organized by categories
            $categories = $pdo->query("SELECT * FROM categories ORDER BY id ASC")->fetchAll();
            $shelves = [];

            foreach ($categories as $index => $cat) {
                $pStmt = $pdo->prepare("
                    SELECT p.*, 
                           COALESCE((SELECT SUM(base_unit_qty) FROM bill_items WHERE product_id = p.id), 0) as total_sold
                    FROM products p 
                    WHERE p.category_id = ?
                    ORDER BY p.id ASC
                ");
                $pStmt->execute([$cat['id']]);
                $products = $pStmt->fetchAll();

                $shelves[] = [
                    'category_id' => $cat['id'],
                    'category_name' => $cat['name'],
                    'color' => $cat['color'] ?: '#3b82f6',
                    'shelf_x' => ($index - (count($categories) / 2)) * 6.0, // 3D coordinates spacing
                    'products' => array_map(function($p) {
                        $stockStatus = 'normal';
                        if ($p['stock_qty'] <= 0) $stockStatus = 'out';
                        elseif ($p['stock_qty'] <= $p['min_stock_alert']) $stockStatus = 'low';

                        return [
                            'id' => (int)$p['id'],
                            'name' => $p['name'],
                            'barcode' => $p['barcode'],
                            'base_unit' => $p['base_unit'],
                            'selling_price' => (float)$p['selling_price'],
                            'purchase_price' => (float)$p['purchase_price'],
                            'stock_qty' => (float)$p['stock_qty'],
                            'min_stock_alert' => (float)$p['min_stock_alert'],
                            'total_sold' => (float)$p['total_sold'],
                            'stock_status' => $stockStatus
                        ];
                    }, $products)
                ];
            }

            jsonResponse(['success' => true, 'shelves' => $shelves]);
            break;

        // ================= SETTINGS =================
        case 'get_settings':
            $rows = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
            $settings = [];
            foreach ($rows as $r) {
                $settings[$r['setting_key']] = $r['setting_value'];
            }
            jsonResponse(['success' => true, 'data' => $settings]);
            break;

        case 'save_settings':
            $settings = $input['settings'] ?? [];
            $stmt = $pdo->prepare("INSERT OR REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($settings as $k => $v) {
                $stmt->execute([$k, (string)$v]);
            }
            jsonResponse(['success' => true, 'message' => 'Settings updated successfully.']);
            break;

        default:
            jsonError("Action '{$action}' not recognized.");
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonError($e->getMessage(), 500);
}
