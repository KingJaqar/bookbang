<?php
// orders/order.php — Fully Fixed Version with Items
session_start();
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../app_url.php';

// Make sure $user_id is set if user is logged in
$user_id = $_SESSION['user_id'] ?? null;
$session_id = session_id();

if ($user_id) {
    $stmt = $conn->prepare("
    SELECT 
        transaction_id, total, payment_method, payment_data, created_at 
    FROM transactions 
    WHERE user_id = ? 
    ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
} else {
    $stmt = $conn->prepare("
    SELECT transaction_id, total, payment_method, payment_data, created_at
    FROM transactions
    WHERE user_id IS NULL AND session_id = ?
    ORDER BY created_at DESC
    ");
    $stmt->bind_param("s", $session_id);
}
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// If viewing specific transaction details
$order_details = null;
$order_items = [];
if (isset($_GET['id'])) {
    $transaction_id = intval($_GET['id']);
    if ($user_id) {
        $stmt = $conn->prepare("
        SELECT * FROM transactions 
        WHERE transaction_id=? AND user_id=? LIMIT 1
        ");
        $stmt->bind_param("ii", $transaction_id, $user_id);
    } else {
        $stmt = $conn->prepare("
        SELECT * FROM transactions
        WHERE transaction_id=? AND user_id IS NULL AND session_id=? LIMIT 1
        ");
        $stmt->bind_param("is", $transaction_id, $session_id);
    }
    $stmt->execute();
    $order_details = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($order_details) {
        // Fetch items for this transaction
       $stmt = $conn->prepare("
    SELECT ti.qty, ti.price, b.title, b.image
    FROM transaction_items ti
    JOIN books b ON b.book_id=ti.book_id
    WHERE ti.transaction_id=?
");

        $stmt->bind_param("i", $transaction_id);
        $stmt->execute();
        $order_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// Include navbar once
include __DIR__ . '/../navbar.php';

function mask_card($number) {
    $number = preg_replace('/\D/', '', $number); // Remove non-digits
    if (strlen($number) < 4) return '**** **** **** ****';
    return '**** **** **** ' . substr($number, -4);
}

function mask_cvv($cvv) {
    return str_repeat('*', strlen($cvv));
}

function mask_wallet($number) {
    $number = preg_replace('/\D/', '', $number);
    if (strlen($number) < 4) return '***********';
    return str_repeat('*', strlen($number) - 4) . substr($number, -4);
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Orders | BookBang</title>
<link rel="stylesheet" href="<?= htmlspecialchars(bookbang_url('orders/order.css'), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(bookbang_url('bookbang-theme.css'), ENT_QUOTES, 'UTF-8') ?>">
<style>
/* ---------------------- Modern & Clean Orders Page ---------------------- */
:root {
    --primary: #76263f;
    --primary-hover: #922f4e;
    --bg-light: #ffffff;
    --card-bg: #fff;
    --text-dark: #292326;
    --text-muted: #766a70;
    --border: #e7dfe2;
    --radius: 4px;
    --gap: 12px;
    --font: 'Aptos', 'Segoe UI', sans-serif;
}

body {
    font-family: var(--font);
    background: var(--bg-light);
    margin: 0;
    padding: 0;
    color: var(--text-dark);
}

.container {
    max-width: 900px;
    margin: 30px auto;
    padding: 20px;
}

/* ----------- Orders Table ----------- */
h2 {
    font-weight: 700;
    margin-bottom: 20px;
    font-size: 1.7rem;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: var(--card-bg);
    border-radius: var(--radius);
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

thead {
    background: var(--primary);
    color: #fff;
}

thead th {
    padding: 12px 15px;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
}

tbody tr {
    border-bottom: 1px solid var(--border);
    transition: background 0.2s;
}

tbody tr:hover {
    background: #f1f1f1;
}

tbody td {
    padding: 12px 15px;
    font-size: 0.9rem;
    vertical-align: middle;
}

.details-btn {
    text-decoration: none;
    color: #fff;
    background: var(--primary);
    padding: 6px 14px;
    border-radius: var(--radius);
    font-size: 0.85rem;
    font-weight: 600;
    transition: 0.2s;
}

.details-btn:hover {
    background: var(--primary-hover);
}

/* ----------- Order Details Card ----------- */
.back-btn {
    display: inline-block;
    margin-bottom: 15px;
    text-decoration: none;
    color: var(--text-muted);
    font-weight: 500;
    font-size: 0.9rem;
}

.back-btn:hover {
    color: var(--primary);
}

.order-details {
    background: var(--card-bg);
    border-radius: var(--radius);
    padding: 20px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.05);
    display: flex;
    flex-direction: column;
    gap: var(--gap);
}

.order-details p {
    margin: 4px 0;
    font-size: 0.95rem;
}

.order-item {
    display: flex;
    align-items: center;
    gap: var(--gap);
    padding: 10px 0;
    border-bottom: 1px solid var(--border);
}

.order-item:last-child {
    border-bottom: none;
}

.item-image {
    width: 50px;
    height: 70px;
    object-fit: cover;
    border-radius: 4px;
    flex-shrink: 0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.item-title {
    flex: 1;
    font-weight: 600;
    font-size: 0.95rem;
}

.item-qty {
    font-weight: 500;
    color: var(--text-muted);
    margin-right: 15px;
    font-size: 0.9rem;
}

.item-price {
    font-weight: 700;
    color: var(--primary);
    min-width: 80px;
    text-align: right;
    font-size: 0.95rem;
}

/* Payment Data JSON block */
/* JSON Display - Clean, Modern, Structured */
pre.json {
    background: #f5f5f5;          /* Light grey background */
    border: 1px solid #ddd;        /* Subtle border */
    border-radius: 8px;            /* Rounded corners */
    padding: 15px;                 /* Spacing inside */
    font-size: 0.9rem;             /* Slightly smaller font */
    color: #333;                   /* Dark text */
    overflow-x: auto;              /* Scroll horizontally if needed */
    line-height: 1.5;              /* Better readability */
    white-space: pre-wrap;         /* Wrap long lines */
    word-wrap: break-word;         /* Break long words */
}

/* Optional: color syntax highlighting */
pre.json span.key {
    color: #d63384;                /* pink-ish for keys */
    font-weight: 600;
}

pre.json span.value {
    color: #1c7ed6;                /* blue-ish for values */
}


/* Empty message styling */
.empty-message {
    text-align: center;
    padding: 20px;
    font-style: italic;
    color: var(--text-muted);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .order-item {
        flex-direction: column;
        align-items: flex-start;
    }

    .item-qty {
        margin-right: 0;
    }

    .item-price {
        width: 100%;
        text-align: left;
        margin-top: 4px;
    }

    table th, table td {
        font-size: 0.85rem;
        padding: 10px;
    }
}

/* --- Modern Payment Card --- */
.payment-card {
    background: linear-gradient(145deg, #ffffff, #f5f6fa);
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    margin-top: 15px;
}

.payment-card h3 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 15px;
    color: var(--text-dark);
    border-bottom: 1px solid #e0e0e0;
    padding-bottom: 8px;
}

.payment-fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px 20px;
}

.payment-fields .field {
    background: #fff;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid #ddd;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    font-size: 0.9rem;
}

.payment-fields .field span {
    display: block;
    font-weight: 600;
    color: var(--primary);
    margin-bottom: 4px;
}

@media (max-width: 600px) {
    .payment-fields {
        grid-template-columns: 1fr;
    }
}

</style>
</head>
<body>
<div class="container">
<?php if (!$order_details): ?>
    <h2>Your Orders</h2>
    <?php if (count($transactions) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Total (₱)</th>
                    <th>Payment Method</th>
                    <th>Date</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $order): ?>
                <tr>
                    <td>#<?= htmlspecialchars($order['transaction_id']) ?></td>
                    <td><?= number_format($order['total'],2) ?></td>
                    <td><?= htmlspecialchars($order['payment_method']) ?></td>
                    <td><?= date("F j, Y, g:i a", strtotime($order['created_at'])) ?></td>
                    <td><a class="details-btn" href="order.php?id=<?= $order['transaction_id'] ?>">View</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="empty-message">You haven’t placed any orders yet.</p>
    <?php endif; ?>
<?php else: ?>
    <a href="order.php" class="back-btn">← Back to Orders</a>
    <h2>Order #<?= htmlspecialchars($order_details['transaction_id']) ?></h2>
    <div class="order-details">
        <p><strong>Total:</strong> ₱<?= number_format($order_details['total'],2) ?></p>
        <p><strong>Payment Method:</strong> <?= htmlspecialchars($order_details['payment_method']) ?></p>
        <p><strong>Date:</strong> <?= date("F j, Y, g:i a", strtotime($order_details['created_at'])) ?></p>
        <p><strong>Items:</strong></p>

        <?php if ($order_items): ?>
            <?php foreach($order_items as $item): ?>
            <div class="order-item">
                <img src="<?= htmlspecialchars(bookbang_url($item['image'] ?? 'default.png'), ENT_QUOTES, 'UTF-8') ?>"
                     alt="<?= htmlspecialchars($item['title']) ?>" 
                     class="item-image">
                <span class="item-title"><?= htmlspecialchars($item['title']) ?></span>
                <span class="item-qty">x <?= $item['qty'] ?></span>
                <span class="item-price">₱<?= number_format($item['price']*$item['qty'],2) ?></span>
            </div>
            <?php endforeach; ?>


            
        <?php else: ?>
            <p class="empty-message">No items found for this order.</p>
        <?php endif; ?>

       <?php 
$payment = json_decode($order_details['payment_data'], true); 
$method = strtolower($order_details['payment_method'] ?? '');
?>

<div class="payment-card">
    <h3>Payment Details (<?= htmlspecialchars($order_details['payment_method']) ?>)</h3>
    <div class="payment-fields">
    <?php if ($payment): ?>
        <?php if ($method === 'card'): ?>
            <div class="field"><span>Card Number:</span> <?= htmlspecialchars(mask_card($payment['card_last4'] ?? $payment['card_number'] ?? '')) ?></div>
            <div class="field"><span>Expiration Date:</span> <?= htmlspecialchars($payment['expiry'] ?? '') ?></div>
            <div class="field"><span>Cardholder Name:</span> <?= htmlspecialchars(($payment['card_fname'] ?? '') . ' ' . ($payment['card_lname'] ?? '')) ?></div>
            <div class="field"><span>CVV:</span> Not stored</div>
        <?php elseif ($method === 'ewallet'): ?>
            <div class="field"><span>Wallet Type:</span> <?= htmlspecialchars($payment['wallet_type'] ?? 'N/A') ?></div>
            <div class="field"><span>Wallet Number:</span> <?= htmlspecialchars(mask_wallet($payment['wallet_last4'] ?? $payment['wallet_number'] ?? '')) ?></div>
        <?php else: ?>
                <div class="field">Payment data not available</div>
            <?php endif; ?>
        <?php else: ?>
            <div class="field">Payment data not available</div>
        <?php endif; ?>
    </div>
</div>

   
    </div>
<?php endif; ?>
</div>
</body>
</html>

