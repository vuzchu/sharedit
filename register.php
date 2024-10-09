<?php
session_start();
include 'db_connect.php';
require 'send_otp.php'; // Include your PHPMailer OTP sending logic

// Biến lưu thông báo lỗi
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $role_id = 1; // Mặc định role_id là 1 (khách hàng)

    // Kiểm tra nếu tất cả các trường không rỗng
    if (!empty($username) && !empty($email) && !empty($password) && !empty($full_name)) {
        // Kiểm tra email đã tồn tại chưa
        $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error_message = 'Email đã tồn tại.';
        } else {
            // Lưu mật khẩu mà không mã hóa
            $plain_password = $password; // No hashing, storing the plain password

            // Generate an OTP
            $otp = rand(100000, 999999); // Random 6-digit OTP

            // Store OTP and user details in session
            $_SESSION['otp'] = $otp;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['password'] = $plain_password;  // Store the plain password
            $_SESSION['full_name'] = $full_name;

            // Send OTP to user's email
            if (sendOtp($email, $otp)) {
                // Redirect to OTP verification page
                header('Location: verify_otp.php');
                exit;
            } else {
                $error_message = 'Đã xảy ra lỗi khi gửi OTP. Vui lòng thử lại.';
            }
        }
    } else {
        $error_message = 'Vui lòng điền đầy đủ tất cả các trường.';
    }
}
?>





<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
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
            <h2>Register</h2>

            <!-- Hiển thị thông báo lỗi hoặc thành công -->
            <?php if (!empty($error_message)): ?>
                <div class="error"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="success"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="submit-btn">Register</button>
            </form>
        </div>
    </div>
</body>
<script src="js/script.js"></script>

</html>