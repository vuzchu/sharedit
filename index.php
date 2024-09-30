<?php
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

// Xây dựng câu truy vấn SQL động dựa trên điều kiện tìm kiếm
$query = "SELECT SQL_CALC_FOUND_ROWS project_id, title, description, image, source 
          FROM project 
          WHERE title LIKE ? AND status = 'active'";

// Nếu có chọn danh mục, thêm điều kiện cho category_id
if (!empty($category_query)) {
    $query .= " AND category_id = ?";
}

$query .= " ORDER BY create_date DESC LIMIT ? OFFSET ?";

// Chuẩn bị câu truy vấn
$stmt = $conn->prepare($query);

// Gán giá trị cho các tham số, phụ thuộc vào việc người dùng có chọn category hay không
$search_query_wildcard = "%" . $search_query . "%";

if (!empty($category_query)) {
    $stmt->bind_param("ssii", $search_query_wildcard, $category_query, $limit, $offset);
} else {
    $stmt->bind_param("sii", $search_query_wildcard, $limit, $offset);
}

// Thực thi câu truy vấn và lấy kết quả
$stmt->execute();
$result = $stmt->get_result();
$projects = $result->fetch_all(MYSQLI_ASSOC);

// Lấy tổng số bản ghi để phân trang
$total_query = "SELECT FOUND_ROWS() as total";
$total_result = $conn->query($total_query);
$total_row = $total_result->fetch_assoc();
$total_projects = $total_row['total'];

// Đóng câu truy vấn
$stmt->close();

// Tính tổng số trang
$total_pages = ceil($total_projects / $limit);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project List</title>
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
                            <img src="<?= htmlspecialchars($project['image']) ?>" class="thumbnail" alt="Project Thumbnail">
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