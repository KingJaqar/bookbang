<?php
// Bookbang/checkout/checkout.php
session_start();
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../app_url.php';

// Resolve user ID
function resolveUserId($conn) {
    if (!empty($_SESSION['user_id'])) return intval($_SESSION['user_id']);
    if (!empty($_SESSION['user'])) {
        $username = $_SESSION['user'];
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $_SESSION['user_id'] = intval($row['user_id']);
            $stmt->close();
            return $_SESSION['user_id'];
        }
        $stmt->close();
    }
    return null;
}

$user_id = resolveUserId($conn);
$session_key = session_id();

// --- Fetch cart items or buy now ---
$buyNowItem = $_SESSION['buy_now'] ?? null;
$cartItems = [];
$total = 0.0;

if ($buyNowItem) {
    $book_id = intval($buyNowItem['book_id']);
$stmt = $conn->prepare("SELECT book_id, title, price, image FROM books WHERE book_id=? LIMIT 1");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($book = $res->fetch_assoc()) {
        $book['qty'] = 1;
        $book['subtotal'] = floatval($book['price']);
        $cartItems[] = $book;
        $total = $book['subtotal'];
    }
    $stmt->close();
    unset($_SESSION['buy_now']);
} else {
    if ($user_id) {
        $stmt = $conn->prepare("SELECT c.cart_id, c.book_id, c.quantity as qty, b.title, b.price, b.image FROM carts c 
                        LEFT JOIN books b ON c.book_id = b.book_id 
                        WHERE c.user_id = ?");

        $stmt->bind_param("i", $user_id);
    } else {
       $stmt = $conn->prepare("SELECT c.cart_id, c.book_id, c.quantity as qty, b.title, b.price, b.image FROM carts c 
                        LEFT JOIN books b ON c.book_id = b.book_id 
                        WHERE c.session_id = ?");

        $stmt->bind_param("s", $session_key);
    }
    $stmt->execute();
    $cartRes = $stmt->get_result();
    while ($r = $cartRes->fetch_assoc()) {
        $r['subtotal'] = floatval($r['price']) * intval($r['qty']);
        $total += $r['subtotal'];
        $cartItems[] = $r;
    }
    $stmt->close();
}

// --- Fetch logged-in user info ---
$user_info = ['first_name'=>'', 'last_name'=>'', 'email'=>'', 'phone'=>''];
if ($user_id) {
    $stmt = $conn->prepare("SELECT first_name, last_name, email, phone FROM users WHERE user_id=? LIMIT 1");
    $stmt->bind_param("i",$user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) $user_info = $row;
    $stmt->close();
}

// --- Handle order submission ---
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['place_order'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $payment_method = $_POST['payment_method'] ?? '';
    $payment_details = [];

    if ($payment_method === 'card') {
        $card_number = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
        $expiry = $_POST['expiry'] ?? '';
        $card_fname = $_POST['card_fname'] ?? '';
        $card_lname = $_POST['card_lname'] ?? '';
        $cvv = preg_replace('/\D/', '', $_POST['cvv'] ?? '');
        if (strlen($card_number) !== 16) die('Card number must be 16 digits.');
        if (!preg_match('/^\d{2}\/\d{4}$/', $expiry)) die('Expiration must be MM/YYYY.');
        if (!preg_match('/^[a-zA-Z]+$/', $card_fname) || !preg_match('/^[a-zA-Z]+$/', $card_lname)) die('Names letters only.');
        if (strlen($cvv)<3 || strlen($cvv)>4) die('CVV invalid.');
        $payment_details = [
            'card_last4' => substr($card_number, -4),
            'expiry' => $expiry,
            'card_fname' => $card_fname,
            'card_lname' => $card_lname
        ];
    } elseif ($payment_method === 'ewallet') {
        $wallet_type = $_POST['wallet_type'] ?? '';
        $wallet_number = preg_replace('/\D/', '', $_POST['wallet_number'] ?? '');
        if (strlen($wallet_number) !== 11) die('E-Wallet number must be 11 digits.');
        $payment_details = ['wallet_type'=>$wallet_type,'wallet_last4'=>substr($wallet_number, -4)];
    } else {
        die('Select a valid payment method.');
    }

    if (!empty($cartItems) && $first_name && $last_name && $email && $payment_method) {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO transactions (user_id, session_id, first_name, last_name, email, phone, payment_method, payment_data, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $json_data = json_encode($payment_details);
            $stmt->bind_param("isssssssd", $user_id, $session_key, $first_name, $last_name, $email, $phone, $payment_method, $json_data, $total);
            $stmt->execute();
            $transaction_id = $stmt->insert_id;
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO transaction_items (transaction_id, book_id, qty, price) VALUES (?, ?, ?, ?)");
            foreach ($cartItems as $item) {
                $stmt->bind_param("iiid", $transaction_id, $item['book_id'], $item['qty'], $item['price']);
                $stmt->execute();
            }
            $stmt->close();

            if ($user_id) {
                $stmt = $conn->prepare("DELETE FROM carts WHERE user_id=?");
                $stmt->bind_param("i", $user_id);
            } else {
                $stmt = $conn->prepare("DELETE FROM carts WHERE session_id=?");
                $stmt->bind_param("s", $session_key);
            }
            $stmt->execute();
            $stmt->close();
            $conn->commit();
        } catch (mysqli_sql_exception $error) {
            $conn->rollback();
            error_log('Bookbang checkout failed: ' . $error->getMessage());
            http_response_code(500);
            die('Could not place the order. No cart items were removed; please try again.');
        }

        header("Location: ../orders/order.php");
        exit();
    }
}

include __DIR__ . '/../navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout | BookBang</title>
<link rel="stylesheet" href="<?= htmlspecialchars(bookbang_url('checkout/checkout.css'), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(bookbang_url('bookbang-theme.css'), ENT_QUOTES, 'UTF-8') ?>">


</head>
<body>

<div class="container">
<h2>Checkout</h2>

<h3>Items</h3>
<div class="checkout-items">
<?php if (!empty($cartItems)): ?>
    <?php foreach($cartItems as $item): ?>
        <div style="display:flex; align-items:center; margin:5px 0;">

        
            <img src="<?= htmlspecialchars(bookbang_url($item['image'] ?? 'default.png'), ENT_QUOTES, 'UTF-8') ?>" 
     alt="<?= htmlspecialchars($item['title']) ?>" 
     style="width:60px; height:80px; object-fit:cover; 
     margin-right:10px; border-radius:4px;">



            <span style="flex:1;"><?= htmlspecialchars($item['title']) ?> x <?= $item['qty'] ?></span>
            <span>₱<?= number_format($item['subtotal'],2) ?></span>
        </div>
    <?php endforeach; ?>
    <div style="font-weight:bold; margin-top:10px;">Total: ₱<?= number_format($total,2) ?></div>
<?php else: ?>
    <p>Your cart is empty.</p>
<?php endif; ?>
</div>


<form method="post" id="checkoutForm">
    <label>First Name</label>
    <input type="text" name="first_name" value="<?= htmlspecialchars($user_info['first_name']) ?>" required>
    <label>Last Name</label>
    <input type="text" name="last_name" value="<?= htmlspecialchars($user_info['last_name']) ?>" required>
    <label>Email</label>
    <input type="email" name="email" value="<?= htmlspecialchars($user_info['email']) ?>" required>
    <label>Phone</label>
    <input type="text" name="phone" value="<?= htmlspecialchars($user_info['phone']) ?>" required>

    <label>Payment Method</label>
    <select name="payment_method" id="payment_method" required>
        <option value="">Select</option>
        <option value="card">Credit/Debit Card</option>
        <option value="ewallet">E-Wallet</option>
    </select>

    <div class="payment-section" id="cardSection">
        <label>Card Number</label>
        <input type="text" name="card_number" placeholder="16 digits">
        <label>Expiration (MM/YYYY)</label>
        <input type="text" name="expiry" placeholder="MM/YYYY">
        <label>First Name</label>
        <input type="text" name="card_fname">
        <label>Last Name</label>
        <input type="text" name="card_lname">
        <label>CVV</label>
        <input type="text" name="cvv" placeholder="3-4 digits">
    </div>

    <div class="payment-section" id="ewalletSection">
        <label>Wallet Type</label>
        <select name="wallet_type">
            <option value="gcash">Gcash</option>
            <option value="paymaya">Paymaya</option>
            <option value="paypal">Paypal</option>
        </select>
        <label>Wallet Number</label>
        <input type="text" name="wallet_number" placeholder="11 digits">
    </div>

    <button type="submit" name="place_order">Place Order</button>
</form>
</div>

<script>
const paymentSelect = document.getElementById('payment_method');
const cardSection = document.getElementById('cardSection');
const ewalletSection = document.getElementById('ewalletSection');

paymentSelect.addEventListener('change', function(){
    cardSection.style.display = this.value==='card' ? 'block':'none';
    ewalletSection.style.display = this.value==='ewallet' ? 'block':'none';
});
</script>
</body>
</html>
