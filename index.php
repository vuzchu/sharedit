<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Kết nối đến cơ sở dữ liệu
include 'db_connect.php';

// Khởi tạo biến để lưu trữ kết quả tìm kiếm và các project
$search_query = '';
$category_query = '';
$projects = [];
$limit = 15; // Số bản ghi trên mỗi trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Kiểm tra nếu người dùng đã nhập từ khóa tìm kiếm
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = $_GET['search'];
}

// Kiểm tra nếu người dùng đã chọn danh mục
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $category_query = $_GET['category'];
}

// Truy vấn lấy dữ liệu cho trang hiện tại
$query = "SELECT project_id, title, description, image, source 
          FROM project 
          WHERE (title LIKE ? OR author LIKE ?) AND status = 'active'";

if (!empty($category_query)) {
    $query .= " AND category_id = ?";
}

$query .= " ORDER BY create_date DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);

// Gán giá trị cho các tham số
$search_query_wildcard = "%" . $search_query . "%";

if (!empty($category_query)) {
    $stmt->bind_param("sssii", $search_query_wildcard, $search_query_wildcard, $category_query, $limit, $offset);
} else {
    $stmt->bind_param("ssii", $search_query_wildcard, $search_query_wildcard, $limit, $offset);
}

// Thực thi câu truy vấn
$stmt->execute();
$result = $stmt->get_result();
$projects = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Truy vấn tổng số bản ghi
$total_query = "SELECT COUNT(*) as total 
                FROM project 
                WHERE (title LIKE ? OR author LIKE ?) AND status = 'active'";

if (!empty($category_query)) {
    $total_query .= " AND category_id = ?";
}

$stmt_total = $conn->prepare($total_query);

if (!empty($category_query)) {
    $stmt_total->bind_param("sss", $search_query_wildcard, $search_query_wildcard, $category_query);
} else {
    $stmt_total->bind_param("ss", $search_query_wildcard, $search_query_wildcard);
}

$stmt_total->execute();
$total_result = $stmt_total->get_result();
$total_row = $total_result->fetch_assoc();
$total_projects = $total_row['total'];
$stmt_total->close();

// Tính tổng số trang
$total_pages = ceil($total_projects / $limit);

$conn->close();
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project List</title>
    <meta property="og:image" content="https://i.imgur.com/akIxlUo.png">
    <meta name="google-site-verification" content="Ct2szNJeQoUZjluCsbFmVfhVYKcWc0DEESatMcg-mgg" />
    <link rel="stylesheet" href="css/style.css">
    <style>
        .list-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start;
        }

        .video-list {
            width: 260px;
            margin: 5px;
        }

        .video-list img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 5px 5px 0 0;
        }

        .video-info {
            padding: 10px;
            text-align: center;
        }

        .video-info a {
            font-weight: bold;
            font-size: 14px;
            color: #333;
        }

        .video-info p {
            font-size: 12px;
            color: #777;
        }

        .pagination {
            margin-top: 20px;
            text-align: center;
        }

        .pagination a {
            margin: 0 5px;
            padding: 10px 15px;
            background-color: #f1f1f1;
            border: 1px solid #ccc;
            text-decoration: none;
            color: #333;
        }

        .pagination a.active {
            background-color: #333;
            color: #fff;
        }
    </style>
</head>

<body>
    <!-- Include phần navigation -->
    <?php include 'nav.php'; ?>

    <!-- Include phần sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main content -->

    <div class="container">

        <!-- Hiển thị từ khóa tìm kiếm -->
        <?php if (!empty($search_query)): ?>
            <p>Results for: <?= htmlspecialchars($search_query) ?></p>
        <?php endif; ?>

        <div class="list-container">
            <?php if (!empty($projects)): ?>
                <?php foreach ($projects as $project): ?>
                    <div class="video-list">
                        <a href="<?= htmlspecialchars($project['source']) ?>" target="_blank">
                            <img src="<?= htmlspecialchars($project['image']) ?>" class="thumbnail" alt="Project Thumbnail" loading="lazy">
                        </a>
                        <div class="video-info">
                            <a href="<?= htmlspecialchars($project['source']) ?>" target="_blank">
                                <?= htmlspecialchars($project['title']) ?>
                            </a>
                            <p><?= htmlspecialchars($project['description']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No projects found</p>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <div class="pagination">
            <?php if ($total_pages > 1): ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?search=<?= htmlspecialchars($search_query) ?>&category=<?= htmlspecialchars($category_query) ?>&page=<?= $i ?>"
                        class="<?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            <?php endif; ?>
        </div>

    </div>

    <script src="js/script.js"></script>
</body>

</html>

<?php
$conn->close();
?>