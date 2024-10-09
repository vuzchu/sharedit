<?php
// Kết nối đến cơ sở dữ liệu
include 'db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hàm upload ảnh lên Imgur và trả về link ảnh
function uploadImageToImgur($imageFilePath)
{
    $client_id = "your_client_id"; // Thay thế bằng Client ID của bạn từ Imgur API

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.imgur.com/3/image",
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: Client-ID $client_id"],
        CURLOPT_POSTFIELDS => ['image' => base64_encode(file_get_contents($imageFilePath))],
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    $responseData = json_decode($response, true);
    if (isset($responseData['data']['link'])) {
        return $responseData['data']['link'];
    } else {
        return false;
    }
}

// Kiểm tra nếu form đã được gửi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $category_id = $_POST['category'] ?? '';
    $source = $_POST['source'] ?? '';
    $author = $_POST['author'] ?? '';

    // Kiểm tra dữ liệu đầu vào
    if (empty($title) || empty($category_id) || empty($source) || empty($author)) {
        // Hiển thị thông báo lỗi nếu dữ liệu đầu vào không hợp lệ
        $error_message = 'Please fill in all required fields.';
    } elseif (!preg_match('/(mega\.nz|drive\.google\.com|dropbox\.com)/i', $source)) {
        // Kiểm tra xem nguồn có phải từ Mega, Drive hoặc Dropbox không
        $error_message = 'Source must be from Mega, Drive, or Dropbox.';
    } else {
        // Đặt trạng thái mặc định là 'pending'
        $status = 'pending';

        // Lấy thời gian hiện tại cho `create_date` và `update_date`
        $current_time = date('Y-m-d H:i:s');

        // Kiểm tra và xử lý upload ảnh nếu có file được upload
        if ($_FILES['image']['error'] == 0) {
            $imageFilePath = $_FILES['image']['tmp_name'];
            $imageUrl = uploadImageToImgur($imageFilePath); // Gọi hàm upload ảnh
        } else {
            $imageUrl = null; // Nếu không có ảnh được upload
        }

        // Chuẩn bị câu truy vấn SQL để thêm dự án vào bảng `project`
        $stmt = $conn->prepare("INSERT INTO project (title, description, category_id, source, status, author, image, create_date, update_date) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // Kiểm tra lỗi trong truy vấn chuẩn bị
        if ($stmt === false) {
            echo "<p style='color:red;'>SQL Error: " . htmlspecialchars($conn->error) . "</p>";
        } else {
            // Liên kết dữ liệu với câu truy vấn
            $stmt->bind_param("ssisssss", $title, $description, $category_id, $source, $status, $author, $imageUrl, $current_time, $current_time);

            // Thực thi câu truy vấn
            if ($stmt->execute()) {
                // Nếu thêm thành công, thông báo thành công
                $success_message = 'Project successfully submitted. Awaiting approval.';
            } else {
                // Nếu có lỗi, hiển thị lỗi
                $error_message = 'An error occurred while adding the project. Please try again later. <br> SQL Error: ' . htmlspecialchars($stmt->error);
            }

            // Đóng statement
            $stmt->close();
        }
    }
}

// Lấy danh mục từ bảng category để hiển thị trong form
$categories = [];
$query = "SELECT category_id, category_name FROM category";
$result = $conn->query($query);
$categories = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dedicate Project</title>
    <link rel="stylesheet" href="css/style.css">

    <style>
        /* Container cho form */
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Form container */
        .form-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }

        .form-container h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 24px;
            color: #333;
        }

        /* Style cho form group */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-size: 16px;
            font-weight: bold;
            color: #555;
        }

        .form-group input[type="text"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s ease-in-out;
        }

        .form-group input[type="text"]:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #1E90FF;
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        /* Style cho nút gửi */
        .submit-btn {
            background-color: #1E90FF;
            color: white;
            padding: 14px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            text-align: center;
            width: 100%;
            transition: background-color 0.3s ease-in-out;
        }

        .submit-btn:hover {
            background-color: #1C86EE;
        }

        /* Lỗi form */
        .form-error {
            color: red;
            font-size: 12px;
            margin-top: 5px;
            display: none;
            /* Hiển thị khi có lỗi */
        }

        /* Form note */
        .form-note {
            font-size: 12px;
            color: #777;
            margin-top: 5px;
        }

        /* Thông báo thành công hoặc lỗi */
        .message {
            margin-bottom: 20px;
            font-size: 16px;
            color: #d9534f;
            /* Màu lỗi */
        }

        .message.success {
            color: #5cb85c;
            /* Màu thành công */
        }
    </style>
</head>

<body>
    <!-- Include sidebar và nav -->
    <?php include 'sidebar.php'; ?>
    <?php include 'nav.php'; ?>
    <div class="container">
        <div class="form-container">
            <h2>Project Information</h2>

            <!-- Hiển thị thông báo lỗi hoặc thành công -->
            <?php if (isset($error_message)): ?>
                <div class="message"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <?php if (isset($success_message)): ?>
                <div class="message success"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>

            <form action="" method="POST" id="dedicateForm" enctype="multipart/form-data">
                <!-- Tiêu đề -->
                <div class="form-group">
                    <label for="title">Title - Required</label>
                    <input type="text" id="title" name="title" placeholder="Enter title" required maxlength="255">
                    <span class="form-error">Please enter the title.</span>
                </div>

                <!-- Mô tả -->
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Enter description" maxlength="3000"></textarea>
                </div>

                <!-- Loại dự án (category) -->
                <div class="form-group">
                    <label for="category">Project Type - Required</label>
                    <select id="category" name="category" required>
                        <option value="">Choose a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= htmlspecialchars($category['category_id']) ?>">
                                <?= htmlspecialchars($category['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Link nguồn -->
                <div class="form-group">
                    <label for="source">Source (Mega, Drive, Dropbox)</label>
                    <input type="text" id="source" name="source" placeholder="Enter source link (e.g., https://mega.nz/...)" required>
                    <div class="form-note">Note: Source link must be from Mega, Drive, or Dropbox</div>
                </div>

                <!-- Author -->
                <div class="form-group">
                    <label for="author">Author - Required</label>
                    <input type="text" id="author" name="author" placeholder="Enter author's name" required>
                    <span class="form-error">Please enter the author's name.</span>
                </div>

                <!-- Upload ảnh -->
                <div class="form-group">
                    <label for="image">Upload Image</label>
                    <input type="file" name="image" class="form-control">
                </div>

                <!-- Nút gửi -->
                <button type="submit" class="submit-btn">Submit</button>
            </form>
        </div>
    </div>

    <script src="js/script.js"></script>

    <script>
        // Xử lý hiển thị lỗi nếu có trường bị bỏ trống
        const form = document.getElementById('dedicateForm');
        const titleInput = document.getElementById('title');
        const titleError = titleInput.nextElementSibling;

        form.addEventListener('submit', function(event) {
            if (titleInput.value.trim() === '') {
                titleError.style.display = 'block';
                event.preventDefault(); // Ngăn việc gửi form nếu có lỗi
            } else {
                titleError.style.display = 'none';
            }
        });
    </script>
</body>

</html>

<?php
// Đóng kết nối cơ sở dữ liệu
$conn->close();
?>