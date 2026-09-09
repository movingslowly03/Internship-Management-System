<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$search = trim($_GET['q'] ?? '');
$forms = [];
$totalForms = 0;

$sqlCount = "
    SELECT COUNT(*) AS total
    FROM monitoring_form mf
    INNER JOIN student s ON s.matricNo = mf.matricNo
    WHERE 1=1
";
$params = [];
$types = "";

if ($search !== '') {
    $sqlCount .= " AND (mf.matricNo LIKE ? OR s.stuName LIKE ? OR mf.monitorType LIKE ?) ";
    $like = "%{$search}%";
    $params = [$like, $like, $like];
    $types = "sss";
}

$stmt = $conn->prepare($sqlCount);
if ($search !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $totalForms = (int)$row['total'];
}
$stmt->close();

$sql = "
    SELECT mf.monitorID, mf.matricNo, mf.monitorType, mf.monitorDate, mf.submittedAt,
           mf.attachmentName, s.stuName, s.stuEmail
    FROM monitoring_form mf
    INNER JOIN student s ON s.matricNo = mf.matricNo
    WHERE 1=1
";

if ($search !== '') {
    $sql .= " AND (mf.matricNo LIKE ? OR s.stuName LIKE ? OR mf.monitorType LIKE ?) ";
}
$sql .= " ORDER BY mf.submittedAt DESC, mf.monitorID DESC";

$stmt = $conn->prepare($sql);
if ($search !== '') {
    $stmt->bind_param("sss", $like, $like, $like);
}
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $forms[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Monitoring Forms</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="app-bg">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">

    <div class="card">
        <h2>Submitted Monitoring Forms</h2>
        <p class="subtitle">BLI-06 monitoring submissions are listed here.</p>

        <div class="summary-grid" style="margin-top:20px;">
            <div class="summary-box">
                <span class="summary-label">Total Submissions</span>
                <span class="summary-value"><?php echo $totalForms; ?></span>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:20px;">
        <form method="GET" action="" style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <input type="text"
                   name="q"
                   value="<?php echo htmlspecialchars($search); ?>"
                   placeholder="Search by student name, matric number, or monitoring type"
                   style="padding:12px 14px; border:1px solid var(--border); border-radius:8px; background:var(--bg-input); color:var(--text-main); min-width:280px;">
            <button type="submit" class="btn view small" style="width:auto;">Search</button>
            <?php if ($search !== ''): ?>
                <a href="admin_monitoring_forms.php" class="btn small" style="width:auto;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card" style="margin-top:20px;">
        <h3>Monitoring Records</h3>

        <div class="report-list">
            <?php if (empty($forms)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📝</div>
                    <h3>No Monitoring Forms Found</h3>
                    <p>There are no submitted monitoring forms yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($forms as $form): ?>
                    <div class="report-item">
                        <div class="report-info">
                            <h4><?php echo htmlspecialchars($form['stuName']); ?></h4>
                            <p>
                                <?php echo htmlspecialchars($form['matricNo']); ?> ·
                                <?php echo htmlspecialchars($form['monitorType']); ?>
                            </p>
                            <p>
                                Monitoring date:
                                <?php echo !empty($form['monitorDate']) ? date("d M Y", strtotime($form['monitorDate'])) : "N/A"; ?>
                            </p>
                            <p>
                                Submitted on
                                <?php echo !empty($form['submittedAt']) ? date("d M Y, h:i A", strtotime($form['submittedAt'])) : "N/A"; ?>
                            </p>
                        </div>

                        <div class="report-actions">
                            <a href="view_monitoring_form.php?id=<?php echo (int)$form['monitorID']; ?>"
                               class="btn view small">
                                View Full Form
                            </a>
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