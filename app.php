<?php
require_once __DIR__ . '/app_url.php';
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off','samesite'=>'Lax','path'=>bookbang_app_base() ?: '/']);
    session_start();
}
require_once __DIR__ . '/db_connect.php';
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function bb_h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function bb_query(string $sql,string $types='',array $values=[]): mysqli_stmt {
    global $conn; $s=$conn->prepare($sql); if($types!=='') $s->bind_param($types,...$values); $s->execute(); return $s;
}
function bb_rows(string $sql,string $types='',array $values=[]): array { return bb_query($sql,$types,$values)->get_result()->fetch_all(MYSQLI_ASSOC); }
function bb_one(string $sql,string $types='',array $values=[]): ?array { return bb_query($sql,$types,$values)->get_result()->fetch_assoc(); }
function bb_redirect(string $path): void { header('Location: '.bookbang_url($path),true,303); exit; }
function bb_csrf(): string { return $_SESSION['csrf'] ?? ($_SESSION['csrf']=bin2hex(random_bytes(32))); }
function bb_check_csrf(): void { if(!is_string($_POST['csrf'] ?? null)||!hash_equals(bb_csrf(),$_POST['csrf'])) throw new InvalidArgumentException('The form expired. Refresh and try again.'); }
function bb_json(array $data,int $status=200): void { http_response_code($status); header('Content-Type: application/json'); echo json_encode($data); exit; }
function bb_user_id(): ?int { return empty($_SESSION['user_id']) ? null : (int)$_SESSION['user_id']; }
function bb_owner(): array { return bb_user_id() ? ['user_id = ?','i',[bb_user_id()]] : ['user_id IS NULL AND session_id = ?','s',[session_id()]]; }
function bb_cents(array $book): int { return (int)round((float)$book['price']*(1-(float)($book['discount'] ?? 0)/100)*100); }
function bb_cart_items(): array {
    [$w,$t,$a]=bb_owner();
    $items=bb_rows('SELECT c.cart_id,c.book_id,c.quantity AS qty,b.title,b.image,b.price,b.discount FROM carts c JOIN books b ON b.book_id=c.book_id WHERE c.'.$w.' ORDER BY c.cart_id',$t,$a);
    foreach($items as &$item){$item['unit_cents']=bb_cents($item); $item['subtotal_cents']=$item['unit_cents']*(int)$item['qty'];} return $items;
}
function bb_cart_count(): int { return array_sum(array_column(bb_cart_items(),'qty')); }
function bb_schema(): void {
    global $conn;
    $conn->query('CREATE TABLE IF NOT EXISTS account_recovery (user_id INT UNSIGNED PRIMARY KEY,code_hash VARCHAR(255) NOT NULL,attempts INT UNSIGNED NOT NULL DEFAULT 0,locked_until DATETIME NULL,FOREIGN KEY(user_id) REFERENCES users(user_id) ON DELETE CASCADE) ENGINE=InnoDB');
    $conn->query('CREATE TABLE IF NOT EXISTS order_requests (request_key CHAR(64) PRIMARY KEY,transaction_id INT UNSIGNED NOT NULL,FOREIGN KEY(transaction_id) REFERENCES transactions(transaction_id) ON DELETE CASCADE) ENGINE=InnoDB');
    $conn->query('CREATE TABLE IF NOT EXISTS newsletter_subscribers (email VARCHAR(255) PRIMARY KEY,unsubscribe_token CHAR(64) NOT NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}
function bb_recovery(int $id): string {
    $code=strtoupper(bin2hex(random_bytes(16)));
    bb_query('INSERT INTO account_recovery(user_id,code_hash) VALUES (?,?) ON DUPLICATE KEY UPDATE code_hash=VALUES(code_hash),attempts=0,locked_until=NULL','is',[$id,password_hash($code,PASSWORD_DEFAULT)]);
    return implode('-',str_split($code,8));
}
function bb_sign_in(array $user): void {
    global $conn; $old=session_id(); $id=(int)$user['user_id']; $conn->begin_transaction();
    try {
        foreach(bb_rows('SELECT * FROM carts WHERE user_id IS NULL AND session_id=? FOR UPDATE','s',[$old]) as $item){
            $existing=bb_one('SELECT cart_id,quantity FROM carts WHERE user_id=? AND book_id=? LIMIT 1 FOR UPDATE','ii',[$id,$item['book_id']]);
            if($existing){bb_query('UPDATE carts SET quantity=? WHERE cart_id=?','ii',[min(99,$existing['quantity']+$item['quantity']),$existing['cart_id']]); bb_query('DELETE FROM carts WHERE cart_id=?','i',[$item['cart_id']]);}
            else bb_query('UPDATE carts SET user_id=?,session_id=NULL WHERE cart_id=?','ii',[$id,$item['cart_id']]);
        }
        bb_query('INSERT IGNORE INTO library(user_id,book_id) SELECT ?,book_id FROM library WHERE user_id IS NULL AND session_id=?','is',[$id,$old]);
        bb_query('DELETE FROM library WHERE user_id IS NULL AND session_id=?','s',[$old]);
        bb_query('UPDATE transactions SET user_id=?,session_id=NULL WHERE user_id IS NULL AND session_id=?','is',[$id,$old]);
        $conn->commit();
    } catch(Throwable $e){$conn->rollback(); throw $e;}
    session_regenerate_id(true); $_SESSION['user_id']=$id; $_SESSION['user']=$user['username']; $_SESSION['role']=$user['role']; $_SESSION['auth_hash']=hash('sha256',$user['password']); unset($_SESSION['login_attempts']);
}
if(bb_user_id()){
    $account=bb_one('SELECT user_id,username,role,password FROM users WHERE user_id=?','i',[bb_user_id()]);
    if(!$account || (isset($_SESSION['auth_hash'])&&!hash_equals($_SESSION['auth_hash'],hash('sha256',$account['password'])))) unset($_SESSION['user_id'],$_SESSION['user'],$_SESSION['role'],$_SESSION['auth_hash']);
    else {$_SESSION['user']=$account['username']; $_SESSION['role']=$account['role']; $_SESSION['auth_hash']=hash('sha256',$account['password']);}
}
