<?php
// user/user.php — Fully Fixed Version

// Enable strict MySQLi error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Database connection
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../app_url.php';

// Start session
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Logged-in user's ID
$user_id = $_SESSION['user_id'];

// Fetch user information
try {
    $stmt = $conn->prepare("
        SELECT 
            first_name, 
            last_name, 
            username, 
            email, 
            phone, 
            profile_picture, 
            created_at 
        FROM users 
        WHERE user_id = ? 
        LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    die("Error fetching user info: " . $e->getMessage());
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $profile_picture = $user['profile_picture']; // Default to existing picture

    // Handle profile picture upload
    if (!empty($_FILES['profile_picture']['name'])) {
        $target_dir = "../uploads/profile_pictures/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $target_file = $target_dir . basename($_FILES["profile_picture"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ["jpg", "jpeg", "png", "gif"];

        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                $profile_picture = basename($_FILES["profile_picture"]["name"]);
            } else {
                echo "<script>alert('Error uploading file.');</script>";
            }
        } else {
            echo "<script>alert('Invalid file type. Only JPG, JPEG, PNG & GIF allowed.');</script>";
        }
    }

    try {
        $stmt = $conn->prepare("
            UPDATE users 
            SET first_name=?, last_name=?, username=?, email=?, phone=?, profile_picture=? 
            WHERE user_id=?
        ");
        $stmt->bind_param(
            "ssssssi",
            $first_name,
            $last_name,
            $username,
            $email,
            $phone,
            $profile_picture,
            $user_id
        );
        $stmt->execute();
        $stmt->close();

        echo "<script>alert('Profile updated successfully!'); window.location.href='user.php';</script>";
        exit();
    } catch (Exception $e) {
        die("Error updating profile: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile | BookBang</title>
    <link rel="stylesheet" href="user.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(bookbang_url('bookbang-theme.css'), ENT_QUOTES, 'UTF-8') ?>">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f8;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
        }
        .profile-info {
            margin-bottom: 20px;
        }
        label {
            font-weight: bold;
            display: block;
            margin-top: 12px;
        }
        input[type="text"], input[type="email"], input[type="file"] {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 12px 20px;
            margin-top: 20px;
            border-radius: 8px;
            cursor: pointer;
        }
        button:hover {
            background-color: #2980b9;
        }
        .profile-picture {
            text-align: center;
            margin-bottom: 20px;
        }
        .profile-picture img {
            border-radius: 50%;
            width: 120px;
            height: 120px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div id="navbar"></div>



<div class="container">
    <h2>User Profile</h2>

    <div class="profile-picture">
        <?php if (!empty($user['profile_picture'])): ?>
            <img src="../uploads/profile_pictures/<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture">
        <?php else: ?>
            <img src="../assets/default-avatar.png" alt="Profile Picture">
        <?php endif; ?>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <label>First Name</label>
        <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>

        <label>Last Name</label>
        <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>

        <label>Username</label>
        <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

        <label>Phone</label>
        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">

        <label>Profile Picture</label>
        <input type="file" name="profile_picture" accept="image/*">

        <button type="submit" name="update_profile">Update Profile</button>
    </form>

    <p style="margin-top:20px; color:#777;">Member since: 
        <?php echo date("F j, Y", strtotime($user['created_at'])); ?>
    </p>
</div>


<script>

fetch('<?= htmlspecialchars(bookbang_url('navbar.php'), ENT_QUOTES, 'UTF-8') ?>')
    .then(r => r.text())
    .then(html => document.getElementById('navbar').innerHTML = html);
</script>

</body>
</html>
