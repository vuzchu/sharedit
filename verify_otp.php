<?php
session_start();
include 'db_connect.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = $_POST['otp'] ?? '';

    // Check if the OTP entered by the user matches the one in session
    if ($entered_otp == $_SESSION['otp']) {
        // Register the user in the database after OTP verification
        $username = $_SESSION['username'];
        $email = $_SESSION['email'];
        $password = $_SESSION['password'];
        $full_name = $_SESSION['full_name'];
        $role_id = 1; // default role for users

        // Insert the user into the database
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, role_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $username, $email, $password, $full_name, $role_id);

        if ($stmt->execute()) {
            // Clear the session
            unset($_SESSION['otp'], $_SESSION['username'], $_SESSION['email'], $_SESSION['password'], $_SESSION['full_name']);

            // Redirect to login
            header('Location: login.php');
            exit;
        } else {
            $error_message = 'Đã xảy ra lỗi khi đăng ký. Vui lòng thử lại.';
        }
    } else {
        $error_message = 'OTP không hợp lệ. Vui lòng thử lại.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Styling cho form-container */
        .form-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .submit-btn {
            background-color: #007bff;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }

        .submit-btn:hover {
            background-color: #0056b3;
        }

        .error {
            color: red;
            margin-bottom: 15px;
            text-align: center;
        }

        .success {
            color: green;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>

<body>
    <!-- Include sidebar và nav -->
    <?php include 'sidebar.php'; ?>
    <?php include 'nav.php'; ?>

    <div class="container">
        <div class="form-container">
            <h2>Verify OTP</h2>

            <!-- Hiển thị thông báo lỗi hoặc thành công -->
            <?php if (!empty($error_message)): ?>
                <div class="error"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="success"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>

            <form action="verify_otp.php" method="POST">
                <div class="form-group">
                    <label for="otp">Enter OTP</label>
                    <input type="text" id="otp" name="otp" required>
                </div>

                <button type="submit" class="submit-btn">Verify</button>
            </form>
        </div>
    </div>
</body>

<script src="js/script.js"></script>

</html>