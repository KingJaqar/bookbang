<?php
require_once 'vendor/autoload.php';

$client = new Google_Client();
$client->setClientId("804354259193-l46d2ubh31lq785abpsb5gncs8sj4gv8.apps.googleusercontent.com");
$client->setClientSecret("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");
$client->setRedirectUri('http://localhost/Bookbang/google-callback.php');
$client->addScope("email");
$client->addScope("profile");
?>

