<?php
require __DIR__.'/app.php'; $error=''; $mode='login';
if($_SERVER['REQUEST_METHOD']==='POST'){
 try {bb_check_csrf();
 $attempts=$_SESSION['login_attempts'] ?? ['count'=>0,'start'=>time()]; if(time()-$attempts['start']>900) $attempts=['count'=>0,'start'=>time()];
 if($attempts['count']>=10) throw new InvalidArgumentException('Too many attempts. Try again in 15 minutes.'); $attempts['count']++; $_SESSION['login_attempts']=$attempts;
 $id=trim($_POST['email'] ?? ''); $matches=bb_rows('SELECT * FROM users WHERE email=? OR username=? LIMIT 2','ss',[$id,$id]);
 if(count($matches)!==1 || !password_verify($_POST['password'] ?? '',$matches[0]['password'])) throw new InvalidArgumentException('Email, username or password is incorrect.');
 bb_sign_in($matches[0]); bb_redirect($matches[0]['role']==='admin' ? 'admin.php' : 'ProductPage.php');
 }catch(InvalidArgumentException $e){$error=$e->getMessage();}catch(Throwable $e){error_log($e->getMessage()); $error='Unable to sign in. Please try again.';}
}
require __DIR__.'/account-form.php';
