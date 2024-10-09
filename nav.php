<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bao gồm file kết nối đến cơ sở dữ liệu
include 'db_connect.php';

// Get the categories from the database to display in the dropdown
$categories = [];
$query = "SELECT category_id, category_name FROM category";
$result = $conn->query($query);
$categories = $result->fetch_all(MYSQLI_ASSOC);
?>

<nav class="flex-div">
    <div class="nav-left flex-div">
        <img src="img/menu.png" class="menu" alt="">
    </div>
    <div class="nav-middle flex-div">
        <div class="search flex-div">
            <form action="index.php" method="GET" class="search-form" id="searchForm">
                <!-- Dropdown for category -->
                <select name="category" class="category-select" onchange="document.getElementById('searchForm').submit();">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= htmlspecialchars($category['category_id']) ?>"
                            <?= (isset($_GET['category']) && $_GET['category'] == $category['category_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Search input -->
                <input type="text" name="search" placeholder="Search by title or author" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" class="search-input">

                <!-- Search button -->
                <button type="submit" class="search-btn"><img src="img/search.png" alt=""></button>
            </form>
        </div>
    </div>
    <div class="nav-right flex-div">
        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- Hiển thị tên đầy đủ nếu đã đăng nhập -->
            <div class="user-dropdown">
                <span class="dropdown-trigger"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
                <div class="dropdown-content">
                    <!-- Kiểm tra nếu người dùng là staff (role_id = 2) thì hiển thị link quản lý -->
                    <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 2): ?>
                        <a href="manage-project.php">Projects</a>
                    <?php endif; ?>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Hiển thị liên kết "Login" nếu chưa đăng nhập -->
            <a href="login.php" class="login-link">Login</a>
        <?php endif; ?>
    </div>
</nav>

<!-- CSS styling (include in your CSS file) -->
<style>
    /* Style for the form */
    .search-form {
        display: flex;
        align-items: center;
    }

    /* Style for the search input */
    .search-input {
        border: none;
        outline: none;
        padding: 10px;
        border-radius: 0 30px 30px 0;
        font-size: 14px;
        flex-grow: 1;
        width: 100%;
    }

    /* Style for the dropdown (category select) */
    .category-select {
        border: none;
        background-color: transparent;
        font-size: 14px;
        outline: none;
        padding: 10px;
        color: #333;
        border-radius: 30px 0 0 30px;
    }

    /* Style for the search button */
    .search-btn {
        background-color: #fff;
        border: none;
        cursor: pointer;
        padding: 10px;
        display: flex;
        align-items: center;
        border-radius: 30px;
    }

    .search-btn img {
        width: 18px;
        height: 18px;
    }

    /* Add hover effect */
    .search-btn:hover {
        background-color: #f0f0f0;
    }

    /* User dropdown styles */
    .user-dropdown {
        position: relative;
        display: inline-block;
        font-weight: bold;
        cursor: pointer;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        background-color: white;
        box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
        right: 0;
        margin-top: 10px;
        border-radius: 6px;
        min-width: 160px;
        z-index: 1;
    }

    .dropdown-content a {
        padding: 10px 20px;
        display: block;
        color: #333;
        text-decoration: none;
        font-size: 14px;
    }

    .dropdown-content a:hover {
        background-color: #f1f1f1;
    }

    /* Style for login link */
    .login-link {
        font-weight: bold;
        text-decoration: none;
        font-size: 16px;
        color: #1E90FF;
    }

    .login-link:hover {
        text-decoration: underline;
    }
</style>

<script>
    // Lắng nghe sự kiện click vào tên người dùng
    document.addEventListener('DOMContentLoaded', function() {
        const dropdownTrigger = document.querySelector('.dropdown-trigger');
        const dropdownContent = document.querySelector('.dropdown-content');

        dropdownTrigger.addEventListener('click', function(event) {
            dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
            event.stopPropagation(); // Ngăn sự kiện click lan ra ngoài
        });

        // Ẩn dropdown khi click ra ngoài
        document.addEventListener('click', function() {
            dropdownContent.style.display = 'none';
        });

        // Đảm bảo khi click vào dropdown không bị đóng
        dropdownContent.addEventListener('click', function(event) {
            event.stopPropagation();
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Biến lưu thời gian chờ
        let timeout = null;

        // Lấy phần tử ô tìm kiếm và dropdown danh mục
        const searchInput = document.querySelector('.search-input');
        const categorySelect = document.querySelector('.category-select');
        const searchForm = document.getElementById('searchForm');

        // Hàm tự động submit form sau 1,5 giây
        function autoSearch() {
            // Xóa thời gian chờ trước đó
            clearTimeout(timeout);

            // Thiết lập thời gian chờ mới
            timeout = setTimeout(function() {
                searchForm.submit(); // Tự động submit form
            }, 1000);
        }

        // Lắng nghe sự kiện nhập vào ô tìm kiếm
        searchInput.addEventListener('input', autoSearch);

        // Lắng nghe sự kiện thay đổi danh mục
        categorySelect.addEventListener('change', autoSearch);
    });
</script>