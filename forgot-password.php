<?php
require 'app.php'; $error=''; $done=false;
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{bb_check_csrf();bb_schema();$email=trim($_POST['email'] ?? '');$code=strtoupper(preg_replace('/[^a-zA-Z0-9]/','',$_POST['recovery_code'] ?? ''));$password=$_POST['password'] ?? '';
 if(strlen($password)<12||strlen($password)>72||$password!==($_POST['confirm_password'] ?? '')) throw new InvalidArgumentException('Passwords must match and contain 12–72 characters.');
 $conn->begin_transaction();$row=bb_one('SELECT u.user_id,r.code_hash,r.attempts,r.locked_until FROM users u JOIN account_recovery r ON r.user_id=u.user_id WHERE u.email=? LIMIT 1 FOR UPDATE','s',[$email]);
 if(!$row||($row['locked_until']&&strtotime($row['locked_until'])>time())||!password_verify($code,$row['code_hash'])){
 if($row&&(!$row['locked_until']||strtotime($row['locked_until'])<=time())) bb_query('UPDATE account_recovery SET attempts=attempts+1,locked_until=IF(attempts>=5,DATE_ADD(NOW(),INTERVAL 15 MINUTE),NULL) WHERE user_id=?','i',[$row['user_id']]);
 $conn->commit();throw new InvalidArgumentException('Email or recovery code is incorrect, or recovery is temporarily locked.');}
 bb_query('UPDATE users SET password=? WHERE user_id=?','si',[password_hash($password,PASSWORD_DEFAULT),$row['user_id']]);$newCode=bb_recovery((int)$row['user_id']);$conn->commit();$done=true;
 }catch(InvalidArgumentException $e){$error=$e->getMessage();}catch(Throwable $e){$conn->rollback();error_log($e->getMessage());$error='Unable to reset the password. Please try again.';}
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Reset password | BookBang</title><link rel="stylesheet" href="backend.css"></head><body><?php include 'navbar.php'; ?><main class="bb-panel"><h1>Reset your password</h1>
<?php if($done): ?><p class="bb-success">Password updated. Your previous recovery code no longer works. Save this new one:</p><code class="bb-code"><?= bb_h($newCode) ?></code><p><a href="login.php">Sign in</a></p><?php else: ?><p>Use the private recovery code saved after sign-up or generated from your profile.</p><?php if($error): ?><p class="bb-error" role="alert"><?= bb_h($error) ?></p><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= bb_h(bb_csrf()) ?>"><label>Email<input type="email" name="email" required autocomplete="email"></label><label>Recovery code<input name="recovery_code" required autocomplete="off"></label><label>New password<input type="password" name="password" minlength="12" maxlength="72" required autocomplete="new-password"></label><label>Confirm password<input type="password" name="confirm_password" minlength="12" maxlength="72" required autocomplete="new-password"></label><button>Reset password</button></form><?php endif; ?></main></body></html>
