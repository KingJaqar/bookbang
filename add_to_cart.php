<?php
require 'app.php';
if($_SERVER['REQUEST_METHOD']!=='POST') bb_json(['success'=>false,'error'=>'Use POST.'],405);
try{bb_check_csrf();$id=filter_var($_POST['book_id'] ?? '',FILTER_VALIDATE_INT);$qty=filter_var($_POST['quantity'] ?? 1,FILTER_VALIDATE_INT);
 if(!$id||!$qty||$qty<1||$qty>99||!bb_one('SELECT book_id FROM books WHERE book_id=?','i',[$id])) throw new InvalidArgumentException('Choose a valid book and quantity (1–99).');
 [$w,$t,$a]=bb_owner();$item=bb_one('SELECT cart_id,quantity FROM carts WHERE '.$w.' AND book_id=? LIMIT 1',$t.'i',array_merge($a,[$id]));
 if($item){if($item['quantity']+$qty>99) throw new InvalidArgumentException('Maximum quantity is 99.');bb_query('UPDATE carts SET quantity=quantity+? WHERE cart_id=?','ii',[$qty,$item['cart_id']]);}
 elseif(bb_user_id()) bb_query('INSERT INTO carts(user_id,book_id,quantity) VALUES (?,?,?)','iii',[bb_user_id(),$id,$qty]);
 else bb_query('INSERT INTO carts(session_id,book_id,quantity) VALUES (?,?,?)','sii',[session_id(),$id,$qty]);
 bb_json(['success'=>true,'cart_count'=>bb_cart_count()]);
}catch(InvalidArgumentException $e){bb_json(['success'=>false,'error'=>$e->getMessage()],400);}catch(Throwable $e){error_log($e->getMessage());bb_json(['success'=>false,'error'=>'Could not add the book. Try again.'],500);}
