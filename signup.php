<?php
require __DIR__.'/app.php'; $error=''; $mode='signup';
if($_SERVER['REQUEST_METHOD']==='POST'){
 try {bb_check_csrf(); $username=trim($_POST['username'] ?? ''); $email=trim($_POST['email'] ?? ''); $password=$_POST['password'] ?? '';
 if(!preg_match('/^[a-zA-Z0-9_\-]{3,50}$/D',$username)) throw new InvalidArgumentException('Use 3–50 letters, numbers, underscores or hyphens for your username.');
 if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>255) throw new InvalidArgumentException('Enter a valid email address.');
 if(strlen($password)<12||strlen($password)>72) throw new InvalidArgumentException('Use a password with 12–72 characters.');
 if($password!==($_POST['confirm_password'] ?? '')) throw new InvalidArgumentException('Passwords must match.');
 if(bb_one('SELECT user_id FROM users WHERE username=? OR email=? LIMIT 1','ss',[$username,$email])) throw new InvalidArgumentException('That username or email is already registered.');
 bb_schema(); $conn->begin_transaction();
 try{bb_query('INSERT INTO users(username,email,password) VALUES (?,?,?)','sss',[$username,$email,password_hash($password,PASSWORD_DEFAULT)]); $id=$conn->insert_id; $recovery=bb_recovery($id); $conn->commit();}catch(Throwable $e){$conn->rollback();throw $e;}
 bb_sign_in(bb_one('SELECT * FROM users WHERE user_id=?','i',[$id])); $_SESSION['recovery_code']=$recovery; bb_redirect('recovery.php');
 }catch(InvalidArgumentException $e){$error=$e->getMessage();}catch(Throwable $e){error_log($e->getMessage());$error='Unable to create the account. Please try again.';}
}
require __DIR__.'/account-form.php';
