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
    FROM evaluation_form ef
    WHERE 1=1
";
$params = [];
$types = "";

if ($search !== '') {
    $sqlCount .= " AND (ef.matricNo LIKE ? OR ef.name LIKE ? OR ef.email LIKE ?) ";
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
    SELECT ef.evalID, ef.matricNo, ef.name, ef.email, ef.recommend_organization, ef.submitted_at
    FROM evaluation_form ef
    WHERE 1=1
";

if ($search !== '') {
    $sql .= " AND (ef.matricNo LIKE ? OR ef.name LIKE ? OR ef.email LIKE ?) ";
}
$sql .= " ORDER BY ef.submitted_at DESC, ef.evalID DESC";

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
    <title>Evaluation Forms</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="app-bg">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">

    <div class="card">
        <h2>Submitted Evaluation Forms</h2>
        <p class="subtitle">BLI-07 student evaluation submissions are listed here.</p>

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
                   placeholder="Search by name, matric number, or email"
                   style="padding:12px 14px; border:1px solid var(--border); border-radius:8px; background:var(--bg-input); color:var(--text-main); min-width:280px;">
            <button type="submit" class="btn view small" style="width:auto;">Search</button>
            <?php if ($search !== ''): ?>
                <a href="admin_evaluation_forms.php" class="btn small" style="width:auto;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card" style="margin-top:20px;">
        <h3>Evaluation Records</h3>

        <div class="report-list">
            <?php if (empty($forms)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📄</div>
                    <h3>No Evaluation Forms Found</h3>
                    <p>There are no submitted evaluation forms yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($forms as $form): ?>
                    <div class="report-item">
                        <div class="report-info">
                            <h4><?php echo htmlspecialchars($form['name']); ?></h4>
                            <p>
                                <?php echo htmlspecialchars($form['matricNo']); ?> ·
                                <?php echo htmlspecialchars($form['email']); ?>
                            </p>
                            <p>
                                Submitted on
                                <?php echo !empty($form['submitted_at']) ? date("d M Y, h:i A", strtotime($form['submitted_at'])) : "N/A"; ?>
                            </p>
                            <p>
                                Recommend Organization:
                                <strong><?php echo htmlspecialchars($form['recommend_organization']); ?></strong>
                            </p>
                        </div>

                        <div class="report-actions">
                            <a href="view_evaluation_form.php?id=<?php echo (int)$form['evalID']; ?>"
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