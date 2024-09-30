<?php
session_start();

// Xóa tất cả các session
session_unset();

// Hủy session
session_destroy();

// Chuyển hướng người dùng về trang login hoặc trang chủ
header('Location: login.php');
exit;
