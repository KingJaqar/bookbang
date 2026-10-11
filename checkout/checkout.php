<?php
require __DIR__.'/../app.php'; bb_schema(); $error=''; $buyRequested=isset($_GET['buy']); $buy=filter_var($_GET['buy'] ?? '',FILTER_VALIDATE_INT);
$items=[];
if($buyRequested){if($buy&&$buy>0){$b=bb_one('SELECT * FROM books WHERE book_id=?','i',[$buy]);if($b){$b['qty']=1;$b['unit_cents']=bb_cents($b);$b['subtotal_cents']=$b['unit_cents'];$items[]=$b;}}if(!$items) http_response_code(404);}
else $items=bb_cart_items();
$total=array_sum(array_column($items,'subtotal_cents'));$mode=$buyRequested ? 'buy:'.(int)$buy : 'cart';
if(empty($_SESSION['checkout_keys'][$mode])) $_SESSION['checkout_keys'][$mode]=bin2hex(random_bytes(32));
$key=$_SESSION['checkout_keys'][$mode];
$info=bb_user_id()?bb_one('SELECT first_name,last_name,email,phone FROM users WHERE user_id=?','i',[bb_user_id()]):[];
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{bb_check_csrf(); $posted=$_POST['request_key'] ?? '';
 if(!is_string($posted)||!hash_equals($key,$posted)) throw new InvalidArgumentException('Checkout expired. Refresh and try again.');
 $existing=bb_one('SELECT transaction_id FROM order_requests WHERE request_key=?','s',[$key]);
 if($existing) bb_redirect('orders/order.php?id='.$existing['transaction_id']);
 $first=trim($_POST['first_name'] ?? '');$last=trim($_POST['last_name'] ?? '');$email=trim($_POST['email'] ?? '');$phone=trim($_POST['phone'] ?? '');$address=trim($_POST['address'] ?? '');$city=trim($_POST['city'] ?? '');$postal=trim($_POST['postal_code'] ?? '');
 if(!$items) throw new InvalidArgumentException('Choose a book before placing an order.');
 if($first===''||$last===''||strlen($first)>100||strlen($last)>100) throw new InvalidArgumentException('Enter your first and last name (up to 100 characters each).');
 if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>255) throw new InvalidArgumentException('Enter a valid email.');
 if(!preg_match('/^[+0-9 ()-]{7,30}$/D',$phone)) throw new InvalidArgumentException('Enter a valid phone number.');
 if(strlen($address)<5||strlen($address)>500||$city===''||strlen($city)>100||!preg_match('/^[a-zA-Z0-9 -]{3,12}$/D',$postal)) throw new InvalidArgumentException('Enter your delivery address, city/province and postal code.');
 if(($_POST['payment_method'] ?? '')!=='cod') throw new InvalidArgumentException('Only Cash on Delivery is available.');
 $conn->begin_transaction();
 try{
 $data=json_encode(['address'=>$address,'city'=>$city,'postal_code'=>$postal,'status'=>'pending','payment_status'=>'unpaid']);
 $uid=bb_user_id();$sid=$uid?null:session_id();$method='cod';$amount=$total/100;
 bb_query('INSERT INTO transactions(user_id,session_id,first_name,last_name,email,phone,payment_method,payment_data,total) VALUES (?,?,?,?,?,?,?,?,?)','isssssssd',[$uid,$sid,$first,$last,$email,$phone,$method,$data,$amount]);$orderId=$conn->insert_id;
 bb_query('INSERT INTO order_requests(request_key,transaction_id) VALUES (?,?)','si',[$key,$orderId]);
 foreach($items as $item) bb_query('INSERT INTO transaction_items(transaction_id,book_id,qty,price) VALUES (?,?,?,?)','iiid',[$orderId,$item['book_id'],$item['qty'],$item['unit_cents']/100]);
 if(!$buy){foreach($items as $item){[$w,$t,$a]=bb_owner();bb_query('DELETE FROM carts WHERE cart_id=? AND '.$w,'i'.$t,array_merge([$item['cart_id']],$a));}}
 $conn->commit(); $_SESSION['completed_keys'][$mode]=$key;
 }catch(Throwable $e){$conn->rollback();throw $e;}
 bb_redirect('orders/order.php?id='.$orderId);
 }catch(InvalidArgumentException $e){$error=$e->getMessage();}catch(Throwable $e){error_log($e->getMessage());$error='Could not place the order. Your cart is unchanged; please try again.';}
}
// A fresh GET after a completed order starts a new checkout. POST retries retain the old key.
if($_SERVER['REQUEST_METHOD']==='GET'&&($_SESSION['completed_keys'][$mode] ?? '')===$key){$key=bin2hex(random_bytes(32));$_SESSION['checkout_keys'][$mode]=$key;unset($_SESSION['completed_keys'][$mode]);}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Checkout | BookBang</title><link rel="stylesheet" href="../backend.css"></head><body><?php include __DIR__.'/../navbar.php'; ?><main class="bb-panel"><h1>Checkout</h1><?php if($error): ?><p class="bb-error" role="alert"><?= bb_h($error) ?></p><?php endif; ?>
<?php foreach($items as $item): ?><div class="bb-item"><img src="<?= bb_h(bookbang_url($item['image'])) ?>" alt="<?= bb_h($item['title']) ?>"><span class="bb-title"><?= bb_h($item['title']) ?> x <?= (int)$item['qty'] ?></span><strong>₱<?= number_format($item['subtotal_cents']/100,2) ?></strong></div><?php endforeach; ?>
<?php if(!$items): ?><p>Your cart is empty or the selected book is unavailable.</p><a href="../ProductPage.php">Browse books</a><?php else: ?><h2>Total: ₱<?= number_format($total/100,2) ?></h2><p>Cash on Delivery. Pay the total when your books arrive.</p><form method="post"><input type="hidden" name="csrf" value="<?= bb_h(bb_csrf()) ?>"><input type="hidden" name="request_key" value="<?= bb_h($key) ?>">
<?php foreach(['first_name'=>'First name','last_name'=>'Last name','email'=>'Email','phone'=>'Phone','address'=>'Street address','city'=>'City / province','postal_code'=>'Postal code'] as $field=>$label): ?><label><?= $label ?><input type="<?= $field==='email'?'email':'text' ?>" name="<?= $field ?>" value="<?= bb_h($_POST[$field] ?? $info[$field] ?? '') ?>" maxlength="<?= $field==='address'?500:($field==='email'?255:100) ?>" required></label><?php endforeach; ?>
<label>Payment method<select name="payment_method"><option value="cod">Cash on Delivery (COD)</option></select></label><button name="place_order" value="1">Place COD order</button></form><?php endif; ?></main></body></html>
