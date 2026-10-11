<?php require __DIR__ . '/../app.php'; bb_json(['logged_in'=>(bool)bb_user_id(),'username'=>$_SESSION['user'] ?? null]);
