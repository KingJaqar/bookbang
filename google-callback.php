<?php
session_start();
require_once "db_connect.php";
require_once "google-config.php";

if (!isset($_GET['code'])) {
    die("Google login failed.");
}

$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
$client->setAccessToken($token);

$google_service = new Google_Service_Oauth2($client);
$google_user = $google_service->userinfo->get();

// SAFE DATA
$google_id = $google_user->id;
$email = $google_user->email;
$name = $google_user->name;
$avatar = $google_user->picture;

// CHECK IF EXISTING USER
$stmt = $conn->prepare("SELECT * FROM users WHERE google_id = ? OR email = ?");
$stmt->bind_param("ss", $google_id, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {

    // REGISTER NEW GOOGLE USER
    $stmt = $conn->prepare("
        INSERT INTO users (username, email, google_id, avatar, password)
        VALUES (?, ?, ?, ?, '')
    ");
    $stmt->bind_param("ssss", $name, $email, $google_id, $avatar);
    $stmt->execute();
    $user_id = $stmt->insert_id;

} else {
    $user_data = $result->fetch_assoc();
    $user_id = $user_data['user_id'];
}

// SET SESSION
$_SESSION['user'] = $name;
$_SESSION['user_id'] = $user_id;

// MERGE TEMPORARY CART ITEMS
$session_id = session_id();
$conn->query("UPDATE carts SET user_id = $user_id, session_id = NULL WHERE session_id = '$session_id'");

header("Location: ProductPage.php");
exit;
?>
