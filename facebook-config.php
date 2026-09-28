<?php
require_once __DIR__ . '/vendor/autoload.php';

$fb = new Facebook\Facebook([
    'app_id' => '815665994644018',           // Replace with your App ID
    'app_secret' => 'ec7be93253805402a894c91ee35f9302',   // Replace with your App Secret
    'default_graph_version' => 'v16.0',
]);

$fb_redirect = "http://localhost/Bookbang/facebook-callback.php";

?>
