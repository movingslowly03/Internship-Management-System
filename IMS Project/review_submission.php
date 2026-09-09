<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'supervisor') {
    header("Location: login.php");
    exit();
}

$spMatric = $_SESSION['SPmatric'] ?? '';
if ($spMatric === '') {
    die("Supervisor not logged in.");
}

$type = $_GET['type'] ?? ($_POST['type'] ?? '');
$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));

$map = [
    'logbook' => [
        'table' => 'logbook',
        'idField' => 'logID',
        'title' => 'Logbook Review',
        'viewPage' => 'view_logbook.php'
    ],
    'report' => [
        'table' => 'report',
        'idField' => 'reportID',
        'title' => 'Report Review',
        'viewPage' => 'view_report.php'
    ]
];

if (!isset($map[$type]) || $id <= 0) {
    die("Invalid submission.");
}

$table = $map[$type]['table'];
$idField = $map[$type]['idField'];
$viewPage = $map[$type]['viewPage'];

$error = '';
$success = '';
$allowedStatuses = ['Pending', 'Approved', 'Rejected'];

/* Save review */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reviewStatus = $_POST['reviewStatus'] ?? 'Pending';
    $feedback = trim($_POST['feedback'] ?? '');

    if (!in_array($reviewStatus, $allowedStatuses, true)) {
        $error = "Invalid status.";
    } else {
        $sql = "
            UPDATE {$table}
            SET reviewStatus = ?,
                reviewFeedback = ?,
                reviewedBy = ?,
                reviewedAt = NOW()
            WHERE {$idField} = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $reviewStatus, $feedback, $spMatric, $id);

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: pending_submissions.php?updated=1");
            exit();
        } else {
            $error = "Failed to save review.";
        }

        $stmt->close();
    }
}

/* Load submission */
$sql = "
    SELECT t.{$idField} AS itemID,
           t.fileName,
           t.submission_date,
           t.reviewStatus,
           t.reviewFeedback,
           t.reviewedAt,
           s.matricNo,
           s.stuName,
           s.stuEmail
    FROM {$table} t
    INNER JOIN student s ON s.matricNo = t.matricNo
    INNER JOIN assigned a ON a.matricNo = s.matricNo
    WHERE a.SPmatric = ?
      AND t.{$idField} = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $spMatric, $id);
$stmt->execute();
$result = $stmt->get_result();

if (!$submission = $result->fetch_assoc()) {
    die("Submission not found.");
}
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($map[$type]['title']); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="app-bg">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">

    <div class="card">
        <div class="profile-header">
            <div class="welcome-text">
                <h2><?php echo htmlspecialchars($map[$type]['title']); ?></h2>
                <p><?php echo htmlspecialchars($submission['stuName']); ?> · <?php echo htmlspecialchars($submission['matricNo']); ?></p>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <p style="color:#ef4444;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <div class="summary-grid" style="margin-top:20px;">
            <div class="summary-box">
                <span class="summary-label">File</span>
                <span class="summary-value"><?php echo htmlspecialchars($submission['fileName']); ?></span>
            </div>

            <div class="summary-box">
                <span class="summary-label">Uploaded On</span>
                <span class="summary-value">
                    <?php echo !empty($submission['submission_date']) ? date("d M Y", strtotime($submission['submission_date'])) : "N/A"; ?>
                </span>
            </div>

            <div class="summary-box">
                <span class="summary-label">Current Status</span>
                <span class="summary-value"><?php echo htmlspecialchars($submission['reviewStatus'] ?? 'Pending'); ?></span>
            </div>

            <div class="summary-box">
                <span class="summary-label">Student Email</span>
                <span class="summary-value"><?php echo htmlspecialchars($submission['stuEmail']); ?></span>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:20px;">
        <h3>Submission Preview</h3>
        <iframe
            src="<?php echo htmlspecialchars($viewPage . '?id=' . (int)$submission['itemID']); ?>"
            style="width:100%; height:700px; border:1px solid var(--border); border-radius:10px; background:#fff;">
        </iframe>
    </div>

    <div class="card" style="margin-top:20px;">
        <h3>Review Form</h3>

        <form method="POST">
            <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
            <input type="hidden" name="id" value="<?php echo (int)$id; ?>">

            <div class="form-group">
                <label>Status</label>
                <select name="reviewStatus" class="input">
                    <option value="Pending" <?php echo (($submission['reviewStatus'] ?? 'Pending') === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="Approved" <?php echo (($submission['reviewStatus'] ?? '') === 'Approved') ? 'selected' : ''; ?>>Approved</option>
                    <option value="Rejected" <?php echo (($submission['reviewStatus'] ?? '') === 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>

            <div class="form-group">
                <label>Feedback</label>
                <textarea name="feedback" rows="6" class="feedback-box"><?php echo htmlspecialchars($submission['reviewFeedback'] ?? ''); ?></textarea>
            </div>

            <div class="form-actions">
                <a href="pending_submissions.php" class="btn">Back</a>
                <button type="submit" class="btn submit">Save Review</button>
            </div>
        </form>
    </div>

</div>

<?php include 'footer.php'; ?>
</body>
</html>