<?php

//Bookbang/cart/cart.php
session_start();
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../app_url.php';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$session_id = session_id();

// --- ADD ITEM ---
if (isset($_POST['book_id'], $_POST['quantity'])) {
    $book_id = intval($_POST['book_id']);
    $quantity = intval($_POST['quantity']);

    if ($user_id) {
        $stmt = $conn->prepare("SELECT cart_id, quantity FROM carts WHERE user_id = ? AND book_id = ?");
        $stmt->bind_param("ii", $user_id, $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cart_item = $result->fetch_assoc();
        $stmt->close();

        if ($cart_item) {
            $new_qty = $cart_item['quantity'] + $quantity;
            $stmt = $conn->prepare("UPDATE carts SET quantity = ? WHERE cart_id = ?");
            $stmt->bind_param("ii", $new_qty, $cart_item['cart_id']);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO carts (user_id, book_id, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $user_id, $book_id, $quantity);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        $stmt = $conn->prepare("SELECT cart_id, quantity FROM carts WHERE session_id = ? AND book_id = ?");
        $stmt->bind_param("si", $session_id, $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cart_item = $result->fetch_assoc();
        $stmt->close();

        if ($cart_item) {
            $new_qty = $cart_item['quantity'] + $quantity;
            $stmt = $conn->prepare("UPDATE carts SET quantity = ? WHERE cart_id = ?");
            $stmt->bind_param("ii", $new_qty, $cart_item['cart_id']);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO carts (session_id, book_id, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("sii", $session_id, $book_id, $quantity);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// --- REMOVE ITEM ---
if(isset($_POST['remove_cart_id'])) {
    $cart_id = intval($_POST['remove_cart_id']);
    if ($user_id) {
        $stmt = $conn->prepare("DELETE FROM carts WHERE cart_id=? AND user_id=?");
        $stmt->bind_param("ii", $cart_id, $user_id);
    } else {
        $stmt = $conn->prepare("DELETE FROM carts WHERE cart_id=? AND session_id=?");
        $stmt->bind_param("is", $cart_id, $session_id);
    }
    if ($stmt) {
        $stmt->execute();
        $stmt->close();
        echo "success";
    }
    exit;
}

// --- REMOVE SELECTED ITEMS ---
if(isset($_POST['remove_selected']) && is_array($_POST['remove_selected'])) {
    $ids = array_values(array_filter(array_map('intval', $_POST['remove_selected']), function ($id) {
        return $id > 0;
    }));
    if (!$ids) {
        echo "success";
        exit;
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $params = $ids;
    if ($user_id) {
        $types .= 'i';
        $params[] = $user_id;
        $stmt = $conn->prepare("DELETE FROM carts WHERE cart_id IN ($placeholders) AND user_id=?");
    } else {
        $types .= 's';
        $params[] = $session_id;
        $stmt = $conn->prepare("DELETE FROM carts WHERE cart_id IN ($placeholders) AND session_id=?");
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();
    echo "success";
    exit;
}

// --- UPDATE QUANTITY ---
if(isset($_POST['cart_id'], $_POST['quantity'])) {
    $cart_id = intval($_POST['cart_id']);
    $qty = intval($_POST['quantity']);
    if ($qty < 1) $qty = 1;

    if ($user_id) {
        $stmt = $conn->prepare("UPDATE carts SET quantity=? WHERE cart_id=? AND user_id=?");
        $stmt->bind_param("iii", $qty, $cart_id, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE carts SET quantity=? WHERE cart_id=? AND session_id=?");
        $stmt->bind_param("iis", $qty, $cart_id, $session_id);
    }
    if ($stmt) {
        $stmt->execute();
        $stmt->close();
        echo "success";
    }
    exit;
}

// --- FETCH CART ITEMS ---
if ($user_id) {
    $stmt = $conn->prepare("
        SELECT c.cart_id, c.book_id, c.quantity, b.title, b.price, b.image
        FROM carts c
        LEFT JOIN books b ON c.book_id = b.book_id
        WHERE c.user_id=?
    ");
    if ($stmt) $stmt->bind_param("i", $user_id);
} else {
    $stmt = $conn->prepare("
        SELECT c.cart_id, c.book_id, c.quantity, b.title, b.price, b.image
        FROM carts c
        LEFT JOIN books b ON c.book_id = b.book_id
        WHERE c.session_id=?
    ");
    if ($stmt) $stmt->bind_param("s", $session_id);
}

if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    $cart_items = [];
    while ($row = $res->fetch_assoc()) $cart_items[] = $row;
    $stmt->close();
} else {
    $cart_items = [];
}

// --- CALCULATE TOTAL ---
$total = 0;
foreach ($cart_items as $item) $total += $item['price'] * $item['quantity'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="<?= htmlspecialchars(bookbang_url('cart/cart.css'), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(bookbang_url('bookbang-theme.css'), ENT_QUOTES, 'UTF-8') ?>">

<title>My Cart</title>


</head>
<body>

<div id="navbar"></div>

<h1>Shopping Cart</h1>

<?php if(count($cart_items) > 0): ?>
<div class="cart-controls">
    <label><input type="checkbox" id="selectAll"> Select All</label>
    <button type="button" class="remove-selected-btn" onclick="removeSelected()">Remove Selected</button>
</div>

<form id="cartForm" method="post">
<table class="cart-table">
    <thead>
        <tr>
            <th></th>
            <th>Book</th>
            <th>Title</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Subtotal</th>
            <th>Remove</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach($cart_items as $item): ?>
        <tr data-cart-id="<?= $item['cart_id'] ?>">
            <td><input type="checkbox" class="select-item" value="<?= $item['cart_id'] ?>"></td>
            <td class="clickable"><img src="<?= htmlspecialchars(bookbang_url($item['image']), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($item['title']) ?>"></td>
            <td class="clickable"><?= htmlspecialchars($item['title']) ?></td>
            <td class="clickable">₱<?= number_format($item['price'],2) ?></td>
            <td>
                <input type="number" class="quantity-input" value="<?= $item['quantity'] ?>" min="1" onchange="updateQuantity(this, <?= $item['cart_id'] ?>)">
            </td>
            <td class="subtotal">₱<?= number_format($item['price'] * $item['quantity'],2) ?></td>
            <td>
                <button type="button" class="remove-btn" onclick="removeItem(<?= $item['cart_id'] ?>)">Remove</button>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<div class="total">
    Total: ₱<span id="total"><?= number_format($total,2) ?></span>
</div>
<button type="button" class="checkout-btn" onclick="checkout()">Checkout</button>
</form>
<?php else: ?>
<p style="text-align:center;">Your cart is empty!</p>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {

    // --- Load Navbar ---
    fetch('<?= htmlspecialchars(bookbang_url('navbar.php'), ENT_QUOTES, 'UTF-8') ?>')
        .then(r => r.text())
        .then(html => document.getElementById('navbar').innerHTML = html)
        .catch(err => console.error('Navbar load failed:', err));

    // --- Update Quantity ---
    window.updateQuantity = function(input, cartId) {
        const quantity = parseInt(input.value);
        if(quantity < 1) { input.value = 1; return; }

        const xhr = new XMLHttpRequest();
        xhr.open("POST", "cart.php", true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.onload = function() {
            if(this.responseText === "success") location.reload();
        };
        xhr.send("cart_id="+cartId+"&quantity="+quantity);
    }

    // --- Remove Item ---
    window.removeItem = function(cartId) {
        if(confirm("Remove this item?")) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "cart.php", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                if(this.responseText === "success") location.reload();
            };
            xhr.send("remove_cart_id="+cartId);
        }
    }

    // --- Remove Selected Items ---
    window.removeSelected = function() {
        const selected = Array.from(document.querySelectorAll('.select-item:checked')).map(i => i.value);
        if(selected.length === 0) { alert("Select at least one item."); return; }

        if(confirm("Remove selected items?")) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "cart.php", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                if(this.responseText === "success") location.reload();
            };
            xhr.send("remove_selected[]=" + selected.join("&remove_selected[]="));
        }
    }

    // --- Checkout ---
    window.checkout = function() {
        window.location.href = "<?= htmlspecialchars(bookbang_url('checkout/checkout.php'), ENT_QUOTES, 'UTF-8') ?>";
    }

    // --- Select All ---
    const selectAllCheckbox = document.getElementById('selectAll');
    if(selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checked = this.checked;
            document.querySelectorAll('.select-item').forEach(cb => {
                cb.checked = checked;
                const row = cb.closest('tr');
                if(row) row.classList.toggle('selected', checked);
            });
        });
    }

    // --- Row Click Selection ---
    const table = document.querySelector('.cart-table');
    if(table) {
        table.querySelectorAll('tbody tr').forEach(row => {
            row.querySelectorAll('.clickable').forEach(cell => {
                cell.addEventListener('click', function() {
                    const checkbox = row.querySelector('.select-item');
                    if(checkbox) {
                        checkbox.checked = !checkbox.checked;
                        row.classList.toggle('selected', checkbox.checked);
                    }
                });
            });
        });
    }

});
</script>


</body>
</html>
