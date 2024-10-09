<?php
session_start();
ob_start();
include 'db_connect.php'; // Kết nối cơ sở dữ liệu

// Kiểm tra xem người dùng có đăng nhập với quyền "staff" không
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header('Location: login.php'); // Chuyển hướng đến trang đăng nhập nếu không phải nhân viên
    exit;
}

// Hàm upload ảnh lên Imgur và trả về link ảnh
function uploadImageToImgur($imageFilePath)
{
    $client_id = "4e67b818bef40e4"; // Thay thế bằng Client ID của bạn

    // Cấu hình cURL để upload ảnh
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.imgur.com/3/image",
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: Client-ID $client_id"],
        CURLOPT_POSTFIELDS => ['image' => base64_encode(file_get_contents($imageFilePath))]
    ]);

    // Thực hiện yêu cầu cURL
    $response = curl_exec($curl);
    curl_close($curl);

    // Giải mã JSON phản hồi từ Imgur
    $responseData = json_decode($response, true);

    // Kiểm tra kết quả và trả về link ảnh
    if (isset($responseData['data']['link'])) {
        return $responseData['data']['link'];
    } else {
        return false;
    }
}

// Delete project if a delete request is sent
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_project_id'])) {
    $project_id = $_POST['delete_project_id'];
    $sql = "DELETE FROM project WHERE project_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $project_id);

    if ($stmt->execute()) {
        // Redirect to avoid resubmitting the form when refreshing the page
        header('Location: manage-project.php');
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
}

// Add new project
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_project'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $author = $_POST['author'];
    $status = $_POST['status']; // 'active' or 'pending' directly
    $source = $_POST['source'];
    $category_id = $_POST['category_id'];

    // Upload ảnh lên Imgur và nhận link ảnh
    if ($_FILES['image']['error'] == 0) {
        $imageFilePath = $_FILES['image']['tmp_name'];
        $imageUrl = uploadImageToImgur($imageFilePath);
    } else {
        $imageUrl = null;
    }

    // Insert new project into the database
    $sql = "INSERT INTO project (title, description, author, status, source, image, category_id, create_date, update_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $title, $description, $author, $status, $source, $imageUrl, $category_id);

    if ($stmt->execute()) {
        header('Location: manage-project.php');
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
}

// Update project
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_project_id'])) {
    $project_id = $_POST['edit_project_id'];
    $title = $_POST['edit_title'];
    $description = $_POST['edit_description'];
    $author = $_POST['edit_author'];
    $status = $_POST['edit_status']; // 'active' or 'pending' directly
    $source = $_POST['edit_source'];
    $category_id = $_POST['edit_category_id'];

    // Kiểm tra xem người dùng có upload ảnh mới hay không
    if ($_FILES['edit_image']['error'] == 0) {
        $imageFilePath = $_FILES['edit_image']['tmp_name'];
        $imageUrl = uploadImageToImgur($imageFilePath);
    } else {
        $imageUrl = $_POST['existing_image']; // Sử dụng ảnh hiện tại nếu không upload mới
    }

    // Update project in the database
    $sql = "UPDATE project SET title = ?, description = ?, author = ?, status = ?, source = ?, image = ?, category_id = ?, update_date = NOW() 
            WHERE project_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssii", $title, $description, $author, $status, $source, $imageUrl, $category_id, $project_id);

    if ($stmt->execute()) {
        header('Location: manage-project.php');
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
}

$limit = 15; // Số bản ghi trên mỗi trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$category_query = isset($_GET['category']) ? $_GET['category'] : '';
$status_query = isset($_GET['status']) ? $_GET['status'] : '';

// Truy vấn lấy các dự án
$sql = "SELECT SQL_CALC_FOUND_ROWS p.*, c.category_name 
        FROM project p 
        JOIN category c ON p.category_id = c.category_id 
        WHERE p.title LIKE ?";

// Thêm điều kiện nếu có chọn danh mục
if (!empty($category_query)) {
    $sql .= " AND p.category_id = ?";
}

// Thêm điều kiện nếu có chọn trạng thái
if (!empty($status_query)) {
    $sql .= " AND p.status = ?";
}

$sql .= " ORDER BY p.create_date DESC LIMIT ? OFFSET ?";

// Chuẩn bị câu truy vấn
$stmt = $conn->prepare($sql);

// Gán tham số tìm kiếm
$search_param = '%' . $search_query . '%';

if (!empty($category_query) && !empty($status_query)) {
    $stmt->bind_param("ssiii", $search_param, $category_query, $status_query, $limit, $offset);
} elseif (!empty($category_query)) {
    $stmt->bind_param("ssii", $search_param, $category_query, $limit, $offset);
} elseif (!empty($status_query)) {
    $stmt->bind_param("ssii", $search_param, $status_query, $limit, $offset);
} else {
    $stmt->bind_param("sii", $search_param, $limit, $offset);
}

// Thực hiện truy vấn và lấy kết quả
$stmt->execute();
$result = $stmt->get_result();

// Lấy tổng số bản ghi cho việc phân trang
$total_result = $conn->query("SELECT FOUND_ROWS() as total");
$total_row = $total_result->fetch_assoc();
$total_projects = $total_row['total'];
$total_pages = ceil($total_projects / $limit);

// Fetch tất cả danh mục để đưa vào dropdown
$category_sql = "SELECT * FROM category";
$category_result = $conn->query($category_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Manage Projects</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto|Varela+Round">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="css/manage-style.css">
</head>

<body>
    <div class="container-xl">
        <div class="table-responsive">
            <div class="table-wrapper">
                <div class="table-title">
                    <div class="row">
                        <div class="col-sm-6">
                            <h2>Manage <b>Projects</b></h2>
                        </div>
                        <div class="col-sm-6">
                            <a href="#addProjectModal" class="btn btn-success" data-toggle="modal"><i class="material-icons">&#xE147;</i> <span>Add New Project</span></a>
                        </div>
                    </div>
                </div>

                <!-- Tìm kiếm và lọc -->
                <form method="GET" action="">
                    <div class="row">
                        <div class="col-sm-4">
                            <input type="text" name="search" class="form-control" placeholder="Search by Title" value="<?= htmlspecialchars($search_query); ?>">
                        </div>
                        <div class="col-sm-3">
                            <select name="category" class="form-control">
                                <option value="">All Categories</option>
                                <?php while ($category = $category_result->fetch_assoc()) : ?>
                                    <option value="<?= $category['category_id']; ?>" <?= ($category_query == $category['category_id']) ? 'selected' : ''; ?>><?= htmlspecialchars($category['category_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <select name="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="active" <?= ($status_query == 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="pending" <?= ($status_query == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="disable" <?= ($status_query == 'disable') ? 'selected' : ''; ?>>Disable</option>
                            </select>
                        </div>
                        <div class="col-sm-2">
                            <button type="submit" class="btn btn-primary">Search</button>
                        </div>
                    </div>
                </form>

                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Stt</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Author</th>
                            <th>Status</th>
                            <th>Image</th>
                            <th>Category</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $index = ($page - 1) * $limit + 1; // Tính toán số thứ tự trên từng trang
                        while ($project = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $index++; ?></td>
                                <td><?php echo htmlspecialchars($project['title']); ?></td>
                                <td><?php echo htmlspecialchars($project['description']); ?></td>
                                <td><?php echo htmlspecialchars($project['author']); ?></td>
                                <td><?php echo htmlspecialchars($project['status']); ?></td>
                                <td><img src="<?php echo htmlspecialchars($project['image']); ?>" alt="Project Image" style="width:50px;height:50px;"></td>
                                <td><?php echo htmlspecialchars($project['category_name']); ?></td>
                                <td>
                                    <a href="#editProjectModal" class="edit" data-toggle="modal" data-id="<?php echo $project['project_id']; ?>" data-title="<?php echo htmlspecialchars($project['title']); ?>" data-description="<?php echo htmlspecialchars($project['description']); ?>" data-author="<?php echo htmlspecialchars($project['author']); ?>" data-status="<?php echo htmlspecialchars($project['status']); ?>" data-source="<?php echo htmlspecialchars($project['source']); ?>" data-image="<?php echo htmlspecialchars($project['image']); ?>" data-category="<?php echo htmlspecialchars($project['category_id']); ?>"><i class="material-icons" data-toggle="tooltip" title="Edit">&#xE254;</i></a>
                                    <a href="#deleteProjectModal" class="delete" data-toggle="modal" data-id="<?php echo $project['project_id']; ?>"><i class="material-icons" data-toggle="tooltip" title="Delete">&#xE872;</i></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <!-- Phân trang -->
                <div class="clearfix">
                    <ul class="pagination">
                        <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : ''; ?>">
                                <a href="?search=<?= htmlspecialchars($search_query); ?>&category=<?= htmlspecialchars($category_query); ?>&status=<?= htmlspecialchars($status_query); ?>&page=<?= $i; ?>" class="page-link"><?= $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Modal HTML -->
    <div id="addProjectModal" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h4 class="modal-title">Add Project</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Author</label>
                            <input type="text" name="author" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control" required>
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="disable">Disable</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Source</label>
                            <input type="text" name="source" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Image</label>
                            <input type="file" name="image" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category_id" class="form-control" required>
                                <?php
                                // Fetch categories from the database
                                $sql = "SELECT category_id, category_name FROM category";
                                $result = $conn->query($sql);
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='" . $row['category_id'] . "'>" . $row['category_name'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="hidden" name="add_project" value="1">
                        <input type="button" class="btn btn-default" data-dismiss="modal" value="Cancel">
                        <input type="submit" class="btn btn-success" value="Add">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal HTML -->
    <div id="editProjectModal" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h4 class="modal-title">Edit Project</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="edit_title" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="edit_description" class="form-control" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Author</label>
                            <input type="text" name="edit_author" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="edit_status" class="form-control" required>
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="disable">Disable</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Source</label>
                            <input type="text" name="edit_source" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Image</label>
                            <img id="edit_image_preview" src="" alt="Project Image" style="width:100px;height:100px;">
                            <input type="file" name="edit_image" class="form-control">
                            <input type="hidden" name="existing_image" id="existing_image">
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="edit_category_id" class="form-control" required>
                                <?php
                                // Fetch categories from the database
                                $sql = "SELECT category_id, category_name FROM category";
                                $result = $conn->query($sql);
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='" . $row['category_id'] . "'>" . $row['category_name'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="hidden" name="edit_project_id" id="edit_project_id" value="">
                        <input type="button" class="btn btn-default" data-dismiss="modal" value="Cancel">
                        <input type="submit" class="btn btn-info" value="Save">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Modal HTML -->
    <div id="deleteProjectModal" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h4 class="modal-title">Delete Project</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this project?</p>
                        <p class="text-warning"><small>This action cannot be undone.</small></p>
                    </div>
                    <div class="modal-footer">
                        <input type="hidden" name="delete_project_id" id="delete_project_id" value="">
                        <input type="button" class="btn btn-default" data-dismiss="modal" value="Cancel">
                        <input type="submit" class="btn btn-danger" value="Delete">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Fill edit modal with project details
        $('#editProjectModal').on('show.bs.modal', function(e) {
            var projectId = $(e.relatedTarget).data('id');
            var title = $(e.relatedTarget).data('title');
            var description = $(e.relatedTarget).data('description');
            var author = $(e.relatedTarget).data('author');
            var status = $(e.relatedTarget).data('status');
            var source = $(e.relatedTarget).data('source');
            var image = $(e.relatedTarget).data('image');
            var category = $(e.relatedTarget).data('category');

            $('#edit_project_id').val(projectId);
            $('input[name="edit_title"]').val(title);
            $('textarea[name="edit_description"]').val(description);
            $('input[name="edit_author"]').val(author);
            $('select[name="edit_status"]').val(status);
            $('input[name="edit_source"]').val(source);
            $('#edit_image_preview').attr('src', image); // Show current image
            $('#existing_image').val(image); // Store current image URL
            $('select[name="edit_category_id"]').val(category);
        });

        // Fill delete modal with project id
        $('#deleteProjectModal').on('show.bs.modal', function(e) {
            var projectId = $(e.relatedTarget).data('id');
            $('#delete_project_id').val(projectId);
        });
    </script>
</body>

</html>