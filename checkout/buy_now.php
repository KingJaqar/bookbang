<?php require __DIR__.'/../app.php';
if($_SERVER['REQUEST_METHOD']!=='POST') bb_json(['success'=>false],405);
try{bb_check_csrf();$id=filter_var($_POST['book_id'] ?? '',FILTER_VALIDATE_INT);if(!$id||!bb_one('SELECT book_id FROM books WHERE book_id=?','i',[$id])) throw new InvalidArgumentException('Book not found.');bb_json(['success'=>true,'url'=>bookbang_url('checkout/checkout.php?buy='.$id)]);}catch(InvalidArgumentException $e){bb_json(['success'=>false,'error'=>$e->getMessage()],400);}
