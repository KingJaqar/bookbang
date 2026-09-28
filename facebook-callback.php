<?php
session_start();
require_once 'facebook-config.php';
require_once 'db_connect.php';

$helper = $fb->getRedirectLoginHelper();

try {
    // Remove $fb_redirect
    $accessToken = $helper->getAccessToken();
} catch (Facebook\Exceptions\FacebookResponseException $e) {
    die('Graph error: ' . $e->getMessage());
} catch (Facebook\Exceptions\FacebookSDKException $e) {
    die('SDK error: ' . $e->getMessage());
}

if (!$accessToken) {
    die("No access token obtained. Make sure your Valid OAuth Redirect URI is correct in the Facebook app settings.");
}

try {
    // Get user information
    $response = $fb->get("/me?fields=id,name,email,picture", $accessToken);
    $fb_user = $response->getGraphUser();

    $fb_id = $fb_user->getId();
    $name = $fb_user->getName();
    $email = $fb_user->getEmail();
    $avatar = $fb_user->getPicture()->getUrl();

    // Check if user exists
    $stmt = $conn->prepare("
        SELECT * 
        FROM users 
        WHERE facebook_id = ? OR email = ?
    ");
    $stmt->bind_param("ss", $fb_id, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        // Insert new user
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, facebook_id, avatar, password)
            VALUES (?, ?, ?, ?, '')
        ");
        $stmt->bind_param("ssss", $name, $email, $fb_id, $avatar);
        $stmt->execute();
        $user_id = $stmt->insert_id;
    } else {
        $user_data = $result->fetch_assoc();
        $user_id = $user_data['user_id'];
    }

    // Store session
    $_SESSION['user'] = $name;
    $_SESSION['user_id'] = $user_id;

    header("Location: ProductPage.php");
    exit();

} catch (Facebook\Exceptions\FacebookResponseException $e) {
    die("Graph returned an error: " . $e->getMessage());
} catch (Facebook\Exceptions\FacebookSDKException $e) {
    die("SDK returned an error: " . $e->getMessage());
}
