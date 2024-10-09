<?php
session_start();  // Bắt đầu session
include 'db_connect.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = $_GET['token'] ?? '';

    // Check if the token is provided
    if (!empty($token)) {
        $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        // If the token is valid
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $user_id = $user['user_id'];

            // Store the user_id in session
            $_SESSION['reset_user_id'] = $user_id;
        } else {
            $error_message = 'Invalid token.';
        }
    } else {
        $error_message = 'No token provided.';
    }
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Kiểm tra xem $_SESSION['reset_user_id'] đã được thiết lập chưa
    if (isset($_SESSION['reset_user_id'])) {
        if (!empty($new_password) && $new_password === $confirm_password) {
            $user_id = $_SESSION['reset_user_id'];

            // Lưu mật khẩu thường không mã hóa
            $plain_password = $new_password;
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $plain_password, $user_id);
            $stmt->execute();

            // Xóa token đã sử dụng
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            // Xóa thông tin user_id khỏi session sau khi đặt lại mật khẩu
            unset($_SESSION['reset_user_id']);

            // Chuyển hướng về trang đăng nhập mà không cần truyền message
            header('Location: login.php');
            exit; // Dừng thực thi sau khi chuyển hướng
        } else {
            $error_message = 'Passwords do not match.';
        }
    } else {
        $error_message = 'Session expired or invalid. Please request a new reset link.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
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
    <!-- Include sidebar và nav nếu có -->
    <?php include 'sidebar.php'; ?>
    <?php include 'nav.php'; ?>

    <div class="container">
        <div class="form-container">
            <h2>Reset Password</h2>

            <!-- Display error message if any -->
            <?php if (!empty($error_message)): ?>
                <div class="error"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <form action="reset_password.php" method="POST">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="submit-btn">Reset Password</button>

                <!-- Thêm liên kết "Forgot Password" nếu cần -->
                <div style="text-align: center; margin-top: 10px;">
                    <p><a href="login.php">Back to Login</a></p>
                </div>
            </form>
        </div>
    </div>
</body>

<script src="js/script.js"></script>

</html>
