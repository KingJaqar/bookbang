<?php
session_start();
require_once 'facebook-config.php';

$helper = $fb->getRedirectLoginHelper();
$permissions = ['email']; 

$loginUrl = $helper->getLoginUrl($fb_redirect, $permissions);

header("Location: " . $loginUrl);
exit();
