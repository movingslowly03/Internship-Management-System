<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_type'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['user_type'] !== 'student') {
    header("Location: login.php");
    exit();
}

$matricNo = $_SESSION['matricNo'] ?? '';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_FILES['report_file']) || $_FILES['report_file']['error'] !== 0) {
        $error = "Please select a PDF file.";
    } else {

        $file = $_FILES['report_file'];

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($extension !== 'pdf') {

            $error = "Only PDF files are allowed.";

        } else {

            $fileName = $file['name'];
            $fileData = file_get_contents($file['tmp_name']);

            $stmt = $conn->prepare("
                INSERT INTO report
                (matricNo, fileName, reportFile, submission_date, reportStatus)
                VALUES (?, ?, ?, CURDATE(), 'Submit')
            ");

            if (!$stmt) {
                die("Prepare failed: " . $conn->error);
            }

            $null = NULL;

            $stmt->bind_param(
                "ssb",
                $matricNo,
                $fileName,
                $null
            );

            $stmt->send_long_data(2, $fileData);

            if ($stmt->execute()) {

                header("Location: document.php");
                exit();

            } else {

                $error = "Upload failed: " . $stmt->error;

            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Report</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="app-bg">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">

    <div class="card report-upload-card">

        <h2>Upload Report</h2>
        <p class="subtitle">Upload your internship report (PDF only)</p>

        <?php if (!empty($error)): ?>
            <p style="color:red;">
                <?php echo htmlspecialchars($error); ?>
            </p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <div class="form-group">
                <label>Select File</label>
                <input type="file"
                       name="report_file"
                       accept=".pdf"
                       required>
            </div>

            <div class="form-actions">
                <a href="document.php" class="btn">Cancel</a>
                <button type="submit" class="btn submit">
                    Upload
                </button>
            </div>

        </form>

    </div>

</div>

<?php include 'footer.php'; ?>

</body>
</html>