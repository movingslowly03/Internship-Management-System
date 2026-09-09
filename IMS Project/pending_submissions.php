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

$userName = $_SESSION['user_name'] ?? 'Supervisor';
$profilePic = $_SESSION['user_image'] ?? 'images/pretty.png';
$search = trim($_GET['q'] ?? '');

$stmt = $conn->prepare("
    SELECT SPname
    FROM supervisor
    WHERE SPmatric = ?
    LIMIT 1
");
$stmt->bind_param("s", $spMatric);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $userName = $row['SPname'] ?? $userName;
}
$stmt->close();

$logbooks = [];
$reports = [];

/* Pending logbooks */
$sql = "
    SELECT
        l.logID AS itemID,
        l.fileName,
        l.submission_date,
        s.matricNo,
        s.stuName,
        'logbook' AS itemType
    FROM logbook l
    INNER JOIN assigned a ON a.matricNo = l.matricNo
    INNER JOIN student s ON s.matricNo = l.matricNo
    WHERE a.SPmatric = ?
      AND COALESCE(l.reviewStatus, 'Pending') = 'Pending'
";

if ($search !== '') {
    $sql .= " AND (s.stuName LIKE ? OR s.matricNo LIKE ? OR l.fileName LIKE ?) ";
}
$sql .= " ORDER BY l.submission_date DESC, l.logID DESC";

$stmt = $conn->prepare($sql);
if ($search !== '') {
    $like = "%{$search}%";
    $stmt->bind_param("ssss", $spMatric, $like, $like, $like);
} else {
    $stmt->bind_param("s", $spMatric);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $logbooks[] = $row;
}
$stmt->close();

/* Pending reports */
$sql = "
    SELECT
        r.reportID AS itemID,
        r.fileName,
        r.submission_date,
        s.matricNo,
        s.stuName,
        'report' AS itemType
    FROM report r
    INNER JOIN assigned a ON a.matricNo = r.matricNo
    INNER JOIN student s ON s.matricNo = r.matricNo
    WHERE a.SPmatric = ?
      AND COALESCE(r.reviewStatus, 'Pending') = 'Pending'
";

if ($search !== '') {
    $sql .= " AND (s.stuName LIKE ? OR s.matricNo LIKE ? OR r.fileName LIKE ?) ";
}
$sql .= " ORDER BY r.submission_date DESC, r.reportID DESC";

$stmt = $conn->prepare($sql);
if ($search !== '') {
    $like = "%{$search}%";
    $stmt->bind_param("ssss", $spMatric, $like, $like, $like);
} else {
    $stmt->bind_param("s", $spMatric);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pending Reviews</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="app-bg">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">

    <div class="card">
        <div class="profile-header">
            <div class="welcome-text">
                <h2>Pending Reviews</h2>
                <p>Review submitted logbooks and reports from your assigned students.</p>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap;">
            <div>
                <h3 style="margin:0;">Logbooks</h3>
            </div>

            
        </div>

        <div class="report-list" style="margin-top:20px;">
            <?php if (empty($logbooks)): ?>
                <p>No pending logbooks.</p>
            <?php else: ?>
                <?php foreach ($logbooks as $item): ?>
                    <div class="report-item">
                        <div class="report-info">
                            <h4><?php echo htmlspecialchars($item['stuName']); ?></h4>
                            <p><?php echo htmlspecialchars($item['matricNo']); ?> · <?php echo htmlspecialchars($item['fileName']); ?></p>
                            <p>Uploaded on <?php echo !empty($item['submission_date']) ? date("d M Y", strtotime($item['submission_date'])) : "N/A"; ?></p>
                        </div>
                        <div class="report-actions">
                            <a href="review_submission.php?type=logbook&id=<?php echo (int)$item['itemID']; ?>" class="btn view small">Review</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="card" style="margin-top:20px;">
        <h3>Reports</h3>

        <div class="report-list" style="margin-top:20px;">
            <?php if (empty($reports)): ?>
                <p>No pending reports.</p>
            <?php else: ?>
                <?php foreach ($reports as $item): ?>
                    <div class="report-item">
                        <div class="report-info">
                            <h4><?php echo htmlspecialchars($item['stuName']); ?></h4>
                            <p><?php echo htmlspecialchars($item['matricNo']); ?> · <?php echo htmlspecialchars($item['fileName']); ?></p>
                            <p>Uploaded on <?php echo !empty($item['submission_date']) ? date("d M Y", strtotime($item['submission_date'])) : "N/A"; ?></p>
                        </div>
                        <div class="report-actions">
                            <a href="review_submission.php?type=report&id=<?php echo (int)$item['itemID']; ?>" class="btn view small">Review</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>
</body>
</html>