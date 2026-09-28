<!-- Bookbang/login.php -->

<?php
session_start();
include("db_connect.php");

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $loginId = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($loginId === "" || $password === "") {
        $error_message = "Username or email and password are required.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ? LIMIT 2");
        $stmt->bind_param("ss", $loginId, $loginId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 1) {
            $error_message = "More than one account matches. Sign in with your email address.";
        } elseif ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {

                session_regenerate_id(true);
                $_SESSION['user'] = $user['username'];
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'] ?? 'customer';

                $sessionId = session_id();
                $userId = $user['user_id'];
                $mergeCart = $conn->prepare("UPDATE carts SET user_id = ?, session_id = NULL WHERE session_id = ?");
                $mergeCart->bind_param("is", $userId, $sessionId);
                $mergeCart->execute();

                header("Location: " . ($_SESSION['role'] === 'admin' ? 'admin.php' : 'ProductPage.php'));
                exit();
            } else {
                $error_message = "Invalid password.";
            }
        } else {
            $error_message = "No account found with that email.";
        }
    }
}
?>

<?php include("login.html"); ?>

