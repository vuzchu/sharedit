<?php
session_start();
include 'db_connect.php'; // Include DB connection
require 'send_email.php'; // Include your PHPMailer logic for sending email

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    // Check if the email is provided
    if (!empty($email)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if the email exists in the database
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $user_id = $user['user_id'];

            // Generate a unique token
            $token = bin2hex(random_bytes(50));

            // Insert the token into the password_resets table
            $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $token);
            $stmt->execute();

            // Generate the reset link
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $domain = $_SERVER['HTTP_HOST'];
            $resetLink = $protocol . '://' . $domain . '/reset_password.php?token=' . $token;

            // Send reset password email
            if (sendResetEmail($email, $resetLink)) {
                $success_message = 'A password reset link has been sent to your email.';
            } else {
                $error_message = 'Failed to send the reset link. Please try again.';
            }
        } else {
            $error_message = 'Email does not exist in our records.';
        }
    } else {
        $error_message = 'Please provide an email.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* You can use the same style from login.css or adjust this as needed */
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
            <h2>Forgot Password</h2>

            <!-- Display success message if any -->
            <?php if (!empty($success_message)): ?>
                <div class="success"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>

            <!-- Display error message if any -->
            <?php if (!empty($error_message)): ?>
                <div class="error"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <form action="forgot_password.php" method="POST">
                <div class="form-group">
                    <label for="email">Enter your email</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <button type="submit" class="submit-btn">Send Reset Password</button>

                <!-- Back to login link -->
                <div style="text-align: center; margin-top: 10px;">
                    <p><a href="login.php">Back to Login</a></p>
                </div>
            </form>
        </div>
    </div>
</body>
<script src="js/script.js"></script>

</html>

<?php
// Đóng kết nối cơ sở dữ liệu
$conn->close();
?>
