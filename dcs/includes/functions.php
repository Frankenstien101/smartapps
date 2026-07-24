<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Calculate suggested order quantity for a product, optionally filtered by store.
 *
 * @param int $product_id
 * @param int|null $customer_id  If provided, sales history is filtered by this customer (store).
 * @param int $days_back
 * @return array|null
 */
function getSuggestedOrder($product_id, $customer_id = null, $days_back = 30) {
    global $pdo;

    // 1. Get product details
    $stmt = $pdo->prepare("SELECT lead_time_days, safety_stock, reorder_level FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    if (!$product) return null;

    $lead_time = $product['lead_time_days'] ?? 3;
    $safety_stock = $product['safety_stock'] ?? 0;

    // 2. Average daily sales (last $days_back days), optionally filtered by store
    $sql = "
        SELECT AVG(daily_qty) as avg_sales
        FROM (
            SELECT sales_date, SUM(quantity_sold) as daily_qty
            FROM sales_history
            WHERE product_id = ? AND sales_date >= DATEADD(day, -CAST(? AS INT), GETDATE())
    ";
    $params = [$product_id, $days_back];
    if ($customer_id) {
        $sql .= " AND customer_id = ?";
        $params[] = $customer_id;
    }
    $sql .= " GROUP BY sales_date
        ) AS daily
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    $avg_daily_sales = $result['avg_sales'] ?? 0;

    // 3. Current inventory (global, not store‑specific)
    $stmt = $pdo->prepare("SELECT current_stock, incoming_stock FROM inventory WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $inv = $stmt->fetch();
    $current_stock = $inv ? $inv['current_stock'] : 0;
    $incoming = $inv ? $inv['incoming_stock'] : 0;

    // 4. Reorder point
    $reorder_point = ($avg_daily_sales * $lead_time) + $safety_stock;

    // 5. Target level (lead_time + 7 days review period)
    $review_period = 7;
    $target_level = ($avg_daily_sales * ($lead_time + $review_period)) + $safety_stock;
    $suggested_qty = max(0, $target_level - ($current_stock + $incoming));

    // 6. Reason
    $reason = '';
    if ($suggested_qty > 0) {
        if ($current_stock + $incoming <= $reorder_point) {
            $reason = "Stock below reorder point (${reorder_point}).";
        } else {
            $reason = "Stock approaching reorder point; recommended replenishment.";
        }
    } else {
        $reason = "Sufficient stock.";
    }

    return [
        'suggested_qty' => (int)$suggested_qty,
        'reason' => $reason,
        'avg_daily_sales' => round($avg_daily_sales, 2),
        'reorder_point' => round($reorder_point, 2)
    ];
}

/**
 * Generate unique code (e.g., for sheet numbers)
 */
function generateCode($prefix) {
    return $prefix . '-' . date('Ymd') . '-' . rand(1000, 9999);
}

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}
?>