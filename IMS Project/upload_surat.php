<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$adminId = $_SESSION['admin_id'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title = trim($_POST['title'] ?? '');
    $target_role = trim($_POST['target_role'] ?? 'all');

    if ($title === '') {
        $error = "Please enter a title.";
    } elseif (!isset($_FILES['surat_file']) || $_FILES['surat_file']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please select a file to upload.";
    } else {
        $file = $_FILES['surat_file'];

        $originalName = $file['name'];
        $fileType = $file['type'] ?? 'application/octet-stream';
        $fileData = file_get_contents($file['tmp_name']);

        if ($fileData === false) {
            $error = "Could not read the uploaded file.";
        } else {
            $stmt = $conn->prepare("
    INSERT INTO surat (title, fileName, fileType, suratFile, target_role, uploaded_by)
    VALUES (?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    $error = "Database prepare failed: " . $conn->error;
} else {
    $suratFile = null;

    // 4th parameter is the BLOB field
    $stmt->bind_param("sssbss", $title, $originalName, $fileType, $suratFile, $target_role, $adminId);

    $fileData = file_get_contents($file['tmp_name']);
    $stmt->send_long_data(3, $fileData);

    if ($stmt->execute()) {
        header("Location: surats.php?uploaded=1");
        exit();
    } else {
        $error = "Failed to save surat to database.";
    }

    $stmt->close();
}
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Surat</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="app-bg">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">

    <div class="card report-upload-card">

        <h2>Upload Surat</h2>
        <p class="subtitle">Upload a file for students, supervisors, or all users.</p>

        <?php if (!empty($error)): ?>
            <p style="color:#ef4444;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" placeholder="Enter surat title" required>
            </div>

            <div class="form-group">
                <label>Target Role</label>
                <select name="target_role" class="input">
                    <option value="all">All Users</option>
                    <option value="student">Students Only</option>
                    <option value="supervisor">Supervisors Only</option>
                    <option value="admin">Admins Only</option>
                </select>
            </div>

            <div class="form-group">
                <label>Select File</label>
                <input type="file" name="surat_file" required>
            </div>

            <div class="form-actions">
                <a href="surats.php" class="btn">Cancel</a>
                <button type="submit" class="btn submit">Upload</button>
            </div>

        </form>

    </div>

</div>

<?php include 'footer.php'; ?>

</body>
</html>