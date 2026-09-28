<?php
// Bookbang/google-login.php

require 'vendor/autoload.php';
session_start();

// Create Google Client
$client = new Google\Client();
$client->setClientId('804354259193-l46d2ubh31lq785abpsb5gncs8sj4gv8.apps.googleusercontent.com');          // Replace with your actual Client ID
$client->setClientSecret('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');  // Replace with your actual Client Secret
$client->setRedirectUri('http://localhost/Bookbang/google-callback.php');
$client->addScope("email");
$client->addScope("profile");

// If the user just came back from Google with a code
if (isset($_GET['code'])) {
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        if (isset($token['error'])) {
            throw new Exception(join(', ', $token));
        }

        $client->setAccessToken($token);

        // Get user info
        $oauth2 = new Google\Service\Oauth2($client);
        $userInfo = $oauth2->userinfo->get();

        // Save user info in session
        $_SESSION['user_email'] = $userInfo->email;
        $_SESSION['user_name'] = $userInfo->name;

        // Optionally, you can create a user in your database if not exists
        // Redirect to your product page
        header('Location: ProductPage.php');
        exit();

    } catch (Exception $e) {
        echo "Error fetching Google user info: " . $e->getMessage();
        exit();
    }
} else {
    // No code yet, redirect to Google for authentication
    $auth_url = $client->createAuthUrl();
    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
    exit();
}
