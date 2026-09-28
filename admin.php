<?php
session_start();
require_once __DIR__ . '/db_connect.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$access = $conn->prepare('SELECT role FROM users WHERE user_id = ? LIMIT 1');
$access->bind_param('i', $_SESSION['user_id']);
$access->execute();
$account = $access->get_result()->fetch_assoc();
$access->close();

if (!$account || $account['role'] !== 'admin') {
    http_response_code(403);
    exit('You do not have permission to access the admin area.');
}

function admin_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function admin_redirect(string $view): void
{
    header('Location: admin.php?view=' . rawurlencode($view));
    exit;
}

function admin_transaction_items(mysqli $conn, int $transactionId): array
{
    $stmt = $conn->prepare('SELECT ti.qty, ti.price, b.title FROM transaction_items ti JOIN books b ON b.book_id = ti.book_id WHERE ti.transaction_id = ? ORDER BY b.title');
    $stmt->bind_param('i', $transactionId);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $items;
}

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

$views = ['overview', 'books', 'transactions', 'customers'];
$view = $_GET['view'] ?? 'overview';
if (!in_array($view, $views, true)) {
    $view = 'overview';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirectView = in_array($_POST['view'] ?? '', $views, true) ? $_POST['view'] : 'overview';
    try {
        if (!hash_equals($_SESSION['admin_csrf'], $_POST['csrf'] ?? '')) {
            throw new InvalidArgumentException('The form expired. Please try again.');
        }

        $action = $_POST['action'] ?? '';
        if ($action === 'save_book') {
            $title = trim($_POST['title'] ?? '');
            $author = trim($_POST['author'] ?? '');
            $genre = trim($_POST['genre'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $image = trim($_POST['image'] ?? '');
            $priceInput = $_POST['price'] ?? '';
            $discountInput = $_POST['discount'] ?? '0';
            $pagesInput = $_POST['pages'] ?? '0';
            $collection = trim($_POST['collection'] ?? '');

            if ($title === '' || $author === '' || $genre === '' || $image === '') {
                throw new InvalidArgumentException('Title, author, genre, and cover path are required.');
            }
            if (strlen($image) > 500 || !is_numeric($priceInput) || !is_numeric($discountInput)) {
                throw new InvalidArgumentException('Enter a valid price, discount, and cover path.');
            }
            $price = (float) $priceInput;
            $discount = (float) $discountInput;
            $pages = filter_var($pagesInput, FILTER_VALIDATE_INT);
            if ($price < 0 || $discount < 0 || $discount > 100 || $pages === false || $pages < 0) {
                throw new InvalidArgumentException('Price and pages must be non-negative; discount must be between 0 and 100.');
            }

            $bookId = filter_var($_POST['book_id'] ?? '', FILTER_VALIDATE_INT);
            if ($bookId) {
                $stmt = $conn->prepare('UPDATE books SET title = ?, author = ?, genre = ?, description = ?, image = ?, price = ?, discount = ?, pages = ?, collection = ? WHERE book_id = ?');
                $stmt->bind_param('sssssddisi', $title, $author, $genre, $description, $image, $price, $discount, $pages, $collection, $bookId);
                $stmt->execute();
                $stmt->close();
                $_SESSION['admin_flash'] = 'Book changes saved.';
            } else {
                $stmt = $conn->prepare('INSERT INTO books (title, author, genre, description, image, price, discount, pages, collection) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('sssssddis', $title, $author, $genre, $description, $image, $price, $discount, $pages, $collection);
                $stmt->execute();
                $stmt->close();
                $_SESSION['admin_flash'] = 'Book added to the catalogue.';
            }
            $redirectView = 'books';
        } elseif ($action === 'delete_book') {
            $bookId = filter_var($_POST['book_id'] ?? '', FILTER_VALIDATE_INT);
            if (!$bookId) {
                throw new InvalidArgumentException('Choose a valid book.');
            }
            $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM transaction_items WHERE book_id = ?');
            $stmt->bind_param('i', $bookId);
            $stmt->execute();
            $hasHistory = (int) $stmt->get_result()->fetch_assoc()['total'] > 0;
            $stmt->close();
            if ($hasHistory) {
                throw new InvalidArgumentException('This book appears in purchase history and cannot be deleted.');
            }
            $stmt = $conn->prepare('DELETE FROM books WHERE book_id = ?');
            $stmt->bind_param('i', $bookId);
            $stmt->execute();
            $stmt->close();
            $_SESSION['admin_flash'] = 'Book deleted.';
            $redirectView = 'books';
        } elseif ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $stmt = $conn->prepare('SELECT password FROM users WHERE user_id = ?');
            $stmt->bind_param('i', $_SESSION['user_id']);
            $stmt->execute();
            $passwordHash = $stmt->get_result()->fetch_assoc()['password'] ?? '';
            $stmt->close();
            if (!password_verify($currentPassword, $passwordHash)) {
                throw new InvalidArgumentException('Current password is incorrect.');
            }
            if (strlen($newPassword) < 12 || $newPassword !== $confirmPassword) {
                throw new InvalidArgumentException('New passwords must match and contain at least 12 characters.');
            }
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE users SET password = ? WHERE user_id = ?');
            $stmt->bind_param('si', $newHash, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
            session_regenerate_id(true);
            $_SESSION['admin_flash'] = 'Admin password updated.';
        } else {
            throw new InvalidArgumentException('Unknown admin action.');
        }
    } catch (InvalidArgumentException $error) {
        $_SESSION['admin_flash'] = $error->getMessage();
    } catch (Throwable $error) {
        error_log('BookBang admin action failed: ' . $error->getMessage());
        $_SESSION['admin_flash'] = 'The action could not be completed. Check the server log.';
    }
    admin_redirect($redirectView);
}

$flash = $_SESSION['admin_flash'] ?? '';
unset($_SESSION['admin_flash']);
$stats = $conn->query("SELECT (SELECT COUNT(*) FROM books) AS books, (SELECT COUNT(*) FROM users WHERE role = 'customer') AS customers, (SELECT COUNT(*) FROM transactions) AS transactions, (SELECT COALESCE(SUM(total), 0) FROM transactions) AS revenue")->fetch_assoc();
$editBook = null;
$selectedCustomer = null;
$customerPurchases = [];
$selectedTransaction = null;
$transactionItems = [];

if ($view === 'books') {
    $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($editId) {
        $stmt = $conn->prepare('SELECT * FROM books WHERE book_id = ?');
        $stmt->bind_param('i', $editId);
        $stmt->execute();
        $editBook = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    $books = $conn->query('SELECT b.*, (SELECT COUNT(*) FROM transaction_items ti WHERE ti.book_id = b.book_id) AS historical_sales FROM books b ORDER BY b.title')->fetch_all(MYSQLI_ASSOC);
} elseif ($view === 'transactions') {
    $transactions = $conn->query("SELECT t.transaction_id, t.user_id, t.first_name, t.last_name, t.email, t.payment_method, t.total, t.created_at, COALESCE(NULLIF(CONCAT_WS(' ', u.first_name, u.last_name), ''), u.username, 'Guest checkout') AS customer_name FROM transactions t LEFT JOIN users u ON u.user_id = t.user_id ORDER BY t.created_at DESC")->fetch_all(MYSQLI_ASSOC);
    $transactionId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($transactionId) {
        $stmt = $conn->prepare("SELECT t.transaction_id, t.user_id, t.first_name, t.last_name, t.email, t.phone, t.payment_method, t.total, t.created_at, COALESCE(NULLIF(CONCAT_WS(' ', u.first_name, u.last_name), ''), u.username, 'Guest checkout') AS customer_name FROM transactions t LEFT JOIN users u ON u.user_id = t.user_id WHERE t.transaction_id = ?");
        $stmt->bind_param('i', $transactionId);
        $stmt->execute();
        $selectedTransaction = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($selectedTransaction) {
            $transactionItems = admin_transaction_items($conn, $transactionId);
        }
    }
} elseif ($view === 'customers') {
    $customers = $conn->query("SELECT u.user_id, u.username, u.first_name, u.last_name, u.email, u.phone, u.created_at, COUNT(DISTINCT t.transaction_id) AS order_count, COALESCE(SUM(t.total), 0) AS lifetime_total FROM users u LEFT JOIN transactions t ON t.user_id = u.user_id OR (t.user_id IS NULL AND LOWER(t.email) = LOWER(u.email)) WHERE u.role = 'customer' GROUP BY u.user_id ORDER BY u.created_at DESC")->fetch_all(MYSQLI_ASSOC);
    $customerId = filter_input(INPUT_GET, 'customer', FILTER_VALIDATE_INT);
    if ($customerId) {
        $stmt = $conn->prepare("SELECT user_id, username, first_name, last_name, email, phone, created_at FROM users WHERE user_id = ? AND role = 'customer'");
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $selectedCustomer = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($selectedCustomer) {
            $stmt = $conn->prepare('SELECT transaction_id, total, payment_method, created_at FROM transactions WHERE user_id = ? OR (user_id IS NULL AND LOWER(email) = LOWER(?)) ORDER BY created_at DESC');
            $stmt->bind_param('is', $customerId, $selectedCustomer['email']);
            $stmt->execute();
            $customerPurchases = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    }
}

$formBook = $editBook ?: ['book_id' => '', 'title' => '', 'author' => '', 'genre' => '', 'description' => '', 'image' => '', 'price' => '', 'discount' => '0', 'pages' => '0', 'collection' => ''];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin | BookBang</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="bookbang-theme.css">
</head>
<body>
<div class="admin-shell">
    <aside class="sidebar">
        <a class="brand" href="Homepage.php"><span class="brand-mark">B</span><span>BookBang<small>STORE ADMIN</small></span></a>
        <nav aria-label="Admin sections">
            <a class="<?= $view === 'overview' ? 'active' : '' ?>" href="admin.php">Overview</a>
            <a class="<?= $view === 'books' ? 'active' : '' ?>" href="admin.php?view=books">Books</a>
            <a class="<?= $view === 'transactions' ? 'active' : '' ?>" href="admin.php?view=transactions">Transactions</a>
            <a class="<?= $view === 'customers' ? 'active' : '' ?>" href="admin.php?view=customers">Customers</a>
        </nav>
        <div class="sidebar-bottom"><a href="Homepage.php">View storefront</a><a href="logout.php">Sign out</a></div>
    </aside>
    <main class="main-content">
        <header class="topbar"><div><p class="eyebrow">BOOKBANG / ADMINISTRATION</p><h1><?= ucfirst(admin_h($view)) ?></h1></div><div class="admin-identity"><span class="status-dot"></span><?= admin_h($_SESSION['user']) ?></div></header>
        <?php if ($flash !== ''): ?><div class="notice" role="status"><?= admin_h($flash) ?></div><?php endif; ?>

        <?php if ($view === 'overview'): ?>
            <section class="stats-grid" aria-label="Store summary">
                <article class="stat"><span>Books in catalogue</span><strong><?= number_format((int) $stats['books']) ?></strong><a href="admin.php?view=books">Manage catalogue</a></article>
                <article class="stat"><span>Customer accounts</span><strong><?= number_format((int) $stats['customers']) ?></strong><a href="admin.php?view=customers">View customers</a></article>
                <article class="stat"><span>Transactions</span><strong><?= number_format((int) $stats['transactions']) ?></strong><a href="admin.php?view=transactions">Review purchases</a></article>
                <article class="stat accent"><span>Recorded sales</span><strong>₱<?= number_format((float) $stats['revenue'], 2) ?></strong><small>Based on completed checkout records</small></article>
            </section>
            <section class="panel password-panel">
                <div class="panel-heading"><div><p class="eyebrow">ACCOUNT SECURITY</p><h2>Change admin password</h2></div></div>
                <form method="post" class="password-form">
                    <input type="hidden" name="csrf" value="<?= admin_h($_SESSION['admin_csrf']) ?>"><input type="hidden" name="action" value="change_password"><input type="hidden" name="view" value="overview">
                    <label>Current password<input type="password" name="current_password" autocomplete="current-password" required></label>
                    <label>New password<input type="password" name="new_password" minlength="12" autocomplete="new-password" required></label>
                    <label>Confirm new password<input type="password" name="confirm_password" minlength="12" autocomplete="new-password" required></label>
                    <button class="button primary" type="submit">Update password</button>
                </form>
            </section>
        <?php elseif ($view === 'books'): ?>
            <section class="panel">
                <div class="panel-heading"><div><p class="eyebrow">CATALOGUE</p><h2><?= $editBook ? 'Edit book' : 'Add a book' ?></h2></div><?php if ($editBook): ?><a class="button secondary" href="admin.php?view=books">Cancel edit</a><?php endif; ?></div>
                <form method="post" class="book-form">
                    <input type="hidden" name="csrf" value="<?= admin_h($_SESSION['admin_csrf']) ?>"><input type="hidden" name="action" value="save_book"><input type="hidden" name="view" value="books"><input type="hidden" name="book_id" value="<?= admin_h($formBook['book_id']) ?>">
                    <label>Title<input name="title" maxlength="255" value="<?= admin_h($formBook['title']) ?>" required></label>
                    <label>Author<input name="author" maxlength="255" value="<?= admin_h($formBook['author']) ?>" required></label>
                    <label>Genre<input name="genre" maxlength="100" value="<?= admin_h($formBook['genre']) ?>" required></label>
                    <label>Cover path<input name="image" maxlength="500" placeholder="ProductPageAssets/..." value="<?= admin_h($formBook['image']) ?>" required></label>
                    <label>Price (₱)<input name="price" type="number" min="0" step="0.01" value="<?= admin_h($formBook['price']) ?>" required></label>
                    <label>Discount (%)<input name="discount" type="number" min="0" max="100" step="0.01" value="<?= admin_h($formBook['discount']) ?>"></label>
                    <label>Pages<input name="pages" type="number" min="0" step="1" value="<?= admin_h($formBook['pages']) ?>"></label>
                    <label>Collection<input name="collection" maxlength="255" value="<?= admin_h($formBook['collection']) ?>"></label>
                    <label class="wide">Description<textarea name="description" rows="3"><?= admin_h($formBook['description']) ?></textarea></label>
                    <div class="wide"><button class="button primary" type="submit"><?= $editBook ? 'Save changes' : 'Add book' ?></button></div>
                </form>
            </section>
            <section class="panel table-panel"><div class="panel-heading"><div><p class="eyebrow">INVENTORY</p><h2>Books <span class="count"><?= count($books) ?></span></h2></div></div>
                <div class="table-wrap"><table><thead><tr><th>Book</th><th>Genre</th><th>Price</th><th>History</th><th>Actions</th></tr></thead><tbody>
                <?php foreach ($books as $book): ?><tr><td><strong><?= admin_h($book['title']) ?></strong><small><?= admin_h($book['author']) ?></small></td><td><?= admin_h($book['genre']) ?></td><td>₱<?= number_format((float) $book['price'], 2) ?></td><td><?= (int) $book['historical_sales'] ?> purchases</td><td class="actions"><a class="text-action" href="admin.php?view=books&amp;edit=<?= (int) $book['book_id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this book from the catalogue?')"><input type="hidden" name="csrf" value="<?= admin_h($_SESSION['admin_csrf']) ?>"><input type="hidden" name="action" value="delete_book"><input type="hidden" name="view" value="books"><input type="hidden" name="book_id" value="<?= (int) $book['book_id'] ?>"><button class="text-action danger" type="submit" <?= (int) $book['historical_sales'] > 0 ? 'disabled title="Preserved in purchase history"' : '' ?>>Delete</button></form></td></tr><?php endforeach; ?>
                </tbody></table></div>
            </section>
        <?php elseif ($view === 'transactions'): ?>
            <?php if ($selectedTransaction): ?><section class="panel detail-panel"><div class="panel-heading"><div><p class="eyebrow">TRANSACTION #<?= (int) $selectedTransaction['transaction_id'] ?></p><h2><?= admin_h($selectedTransaction['customer_name']) ?></h2></div><a class="button secondary" href="admin.php?view=transactions">Back to transactions</a></div><p><?= admin_h($selectedTransaction['email']) ?> · <?= admin_h($selectedTransaction['phone'] ?? '') ?> · <?= admin_h($selectedTransaction['payment_method']) ?> · <?= admin_h($selectedTransaction['created_at']) ?></p><div class="table-wrap"><table><thead><tr><th>Book</th><th>Qty</th><th>Unit price</th><th>Line total</th></tr></thead><tbody><?php foreach ($transactionItems as $item): ?><tr><td><?= admin_h($item['title']) ?></td><td><?= (int) $item['qty'] ?></td><td>₱<?= number_format((float) $item['price'], 2) ?></td><td>₱<?= number_format((float) $item['price'] * (int) $item['qty'], 2) ?></td></tr><?php endforeach; ?></tbody></table></div><p class="total-line">Transaction total <strong>₱<?= number_format((float) $selectedTransaction['total'], 2) ?></strong></p></section><?php endif; ?>
            <section class="panel table-panel"><div class="panel-heading"><div><p class="eyebrow">PURCHASES</p><h2>Transactions <span class="count"><?= count($transactions) ?></span></h2></div></div><div class="table-wrap"><table><thead><tr><th>Transaction</th><th>Customer</th><th>Payment</th><th>Total</th><th>Date</th><th></th></tr></thead><tbody>
            <?php foreach ($transactions as $transaction): ?><tr><td>#<?= (int) $transaction['transaction_id'] ?></td><td><strong><?= admin_h($transaction['customer_name']) ?></strong><small><?= admin_h($transaction['email']) ?></small></td><td><?= admin_h($transaction['payment_method']) ?></td><td>₱<?= number_format((float) $transaction['total'], 2) ?></td><td><?= admin_h($transaction['created_at']) ?></td><td><a class="text-action" href="admin.php?view=transactions&amp;id=<?= (int) $transaction['transaction_id'] ?>">Details</a></td></tr><?php endforeach; ?>
            </tbody></table></div></section>
        <?php else: ?>
            <?php if ($selectedCustomer): ?><section class="panel detail-panel"><div class="panel-heading"><div><p class="eyebrow">CUSTOMER ACCOUNT</p><h2><?= admin_h($selectedCustomer['username']) ?></h2></div><a class="button secondary" href="admin.php?view=customers">Back to customers</a></div><p><?= admin_h($selectedCustomer['email']) ?> · <?= admin_h($selectedCustomer['phone'] ?? '') ?> · Joined <?= admin_h($selectedCustomer['created_at']) ?></p><h3>Purchase history</h3><?php if ($customerPurchases): ?><div class="table-wrap"><table><thead><tr><th>Transaction</th><th>Payment</th><th>Total</th><th>Date</th><th></th></tr></thead><tbody><?php foreach ($customerPurchases as $purchase): ?><tr><td>#<?= (int) $purchase['transaction_id'] ?></td><td><?= admin_h($purchase['payment_method']) ?></td><td>₱<?= number_format((float) $purchase['total'], 2) ?></td><td><?= admin_h($purchase['created_at']) ?></td><td><a class="text-action" href="admin.php?view=transactions&amp;id=<?= (int) $purchase['transaction_id'] ?>">Items</a></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><p class="empty">No purchases recorded for this account.</p><?php endif; ?></section><?php endif; ?>
            <section class="panel table-panel"><div class="panel-heading"><div><p class="eyebrow">ACCOUNTS</p><h2>Customers <span class="count"><?= count($customers) ?></span></h2></div></div><div class="table-wrap"><table><thead><tr><th>Customer</th><th>Email</th><th>Phone</th><th>Orders</th><th>Lifetime spend</th><th>Joined</th><th></th></tr></thead><tbody>
            <?php foreach ($customers as $customer): ?><tr><td><strong><?= admin_h(trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) ?: $customer['username']) ?></strong><small>@<?= admin_h($customer['username']) ?></small></td><td><?= admin_h($customer['email']) ?></td><td><?= admin_h($customer['phone'] ?? '') ?></td><td><?= (int) $customer['order_count'] ?></td><td>₱<?= number_format((float) $customer['lifetime_total'], 2) ?></td><td><?= admin_h($customer['created_at']) ?></td><td><a class="text-action" href="admin.php?view=customers&amp;customer=<?= (int) $customer['user_id'] ?>">History</a></td></tr><?php endforeach; ?>
            </tbody></table></div></section>
        <?php endif; ?>
        <footer class="page-footer">BookBang administration · customer payment credentials are not displayed.</footer>
    </main>
</div>
</body>
</html>