<?php
require 'vendor/autoload.php';

$client = new Google\Client();
$client->setApplicationName('Bookbang Test');
$client->setScopes(['https://www.googleapis.com/auth/drive.metadata.readonly']);

echo "Google Client library is working!\n";
