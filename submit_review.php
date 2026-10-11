<?php require 'app.php';
if(!bb_user_id()) bb_redirect('login.php');
try{if($_SERVER['REQUEST_METHOD']!=='POST') throw new InvalidArgumentException('Use the review form.');bb_check_csrf();$id=(int)($_POST['book_id'] ?? 0);$rating=filter_var($_POST['rating'] ?? '',FILTER_VALIDATE_INT);$text=trim($_POST['review_text'] ?? '');if(!$rating||$rating<1||$rating>5||$text===''||strlen($text)>2000||!bb_one('SELECT book_id FROM books WHERE book_id=?','i',[$id])) throw new InvalidArgumentException('Choose a valid book, rating (1–5) and review (up to 2000 characters).');
bb_query('INSERT INTO reviews(book_id,username,rating,review_text) VALUES (?,?,?,?)','isis',[$id,$_SESSION['user'],$rating,$text]);bb_redirect('ProductDescription.php?id='.$id);
}catch(InvalidArgumentException $e){http_response_code(400);echo bb_h($e->getMessage());}catch(Throwable $e){error_log($e->getMessage());http_response_code(500);echo 'Could not post the review. Please try again.';}
