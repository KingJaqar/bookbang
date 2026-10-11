<?php
require __DIR__.'/../app.php'; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{bb_check_csrf();[$w,$t,$a]=bb_owner();$action=$_POST['action'] ?? ''; $ids=($_POST['cart_ids'] ?? []); if(!is_array($ids)) $ids=[];
 if($action==='quantity'){$qty=filter_var($_POST['quantity'] ?? '',FILTER_VALIDATE_INT);$id=filter_var($_POST['cart_id'] ?? '',FILTER_VALIDATE_INT);if(!$qty||$qty<1||$qty>99||!$id) throw new InvalidArgumentException('Use a quantity from 1 to 99.');bb_query('UPDATE carts SET quantity=? WHERE cart_id=? AND '.$w,'ii'.$t,array_merge([$qty,$id],$a));}
 elseif($action==='remove'){$id=(int)($_POST['cart_id'] ?? 0);bb_query('DELETE FROM carts WHERE cart_id=? AND '.$w,'i'.$t,array_merge([$id],$a));}
 elseif($action==='remove_selected'){foreach($ids as $id) bb_query('DELETE FROM carts WHERE cart_id=? AND '.$w,'i'.$t,array_merge([(int)$id],$a));}
 else throw new InvalidArgumentException('Unknown cart action.');
 bb_redirect('cart/cart.php');
 }catch(InvalidArgumentException $e){$error=$e->getMessage();}catch(Throwable $e){error_log($e->getMessage());$error='Could not update the cart.';}
}
$items=bb_cart_items();$total=array_sum(array_column($items,'subtotal_cents'));
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Cart | BookBang</title><link rel="stylesheet" href="../backend.css"></head><body><?php include __DIR__.'/../navbar.php'; ?><main class="bb-panel"><h1>Shopping Cart</h1><?php if($error): ?><p role="alert" class="bb-error"><?= bb_h($error) ?></p><?php endif; ?>
<?php if(!$items): ?><p>Your cart is empty.</p><a href="../ProductPage.php">Browse books</a><?php else: ?><form method="post" id="selection"><input type="hidden" name="csrf" value="<?= bb_h(bb_csrf()) ?>"><input type="hidden" name="action" value="remove_selected"><label><input type="checkbox" id="selectAll"> Select all</label><button>Remove selected</button></form>
<?php foreach($items as $item): ?><div class="bb-item"><input type="checkbox" name="cart_ids[]" form="selection" value="<?= (int)$item['cart_id'] ?>" aria-label="Select <?= bb_h($item['title']) ?>"><img src="<?= bb_h(bookbang_url($item['image'])) ?>" alt="<?= bb_h($item['title']) ?>"><div class="bb-title"><a href="../ProductDescription.php?id=<?= (int)$item['book_id'] ?>"><?= bb_h($item['title']) ?></a><p>₱<?= number_format($item['unit_cents']/100,2) ?> each · Subtotal ₱<?= number_format($item['subtotal_cents']/100,2) ?></p></div>
<form method="post"><input type="hidden" name="csrf" value="<?= bb_h(bb_csrf()) ?>"><input type="hidden" name="action" value="quantity"><input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>"><label>Quantity<input type="number" name="quantity" min="1" max="99" value="<?= (int)$item['qty'] ?>" required aria-label="Quantity for <?= bb_h($item['title']) ?>"></label><button>Update</button></form>
<form method="post"><input type="hidden" name="csrf" value="<?= bb_h(bb_csrf()) ?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="cart_id" value="<?= (int)$item['cart_id'] ?>"><button>Remove</button></form></div><?php endforeach; ?><h2>Total: ₱<?= number_format($total/100,2) ?></h2><a class="bb-button" href="../checkout/checkout.php">Checkout</a><?php endif; ?></main><script>document.getElementById('selectAll')?.addEventListener('change',e=>document.querySelectorAll('[name="cart_ids[]"]').forEach(x=>x.checked=e.target.checked));</script></body></html>
