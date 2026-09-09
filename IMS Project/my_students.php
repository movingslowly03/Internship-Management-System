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

$search = trim($_GET['q'] ?? '');

$userName = $_SESSION['user_name'] ?? 'Supervisor';
$userEmail = $_SESSION['email'] ?? '';
$profilePic = $_SESSION['user_image'] ?? 'images/pretty.png';

/* Refresh supervisor name/email */
$stmt = $conn->prepare("
    SELECT SPname, SPgmail
    FROM supervisor
    WHERE SPmatric = ?
    LIMIT 1
");
$stmt->bind_param("s", $spMatric);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $userName = $row['SPname'] ?? $userName;
    $userEmail = $row['SPgmail'] ?? $userEmail;
}
$stmt->close();

$_SESSION['user_name'] = $userName;
$_SESSION['email'] = $userEmail;

/* Count assigned students */
$studentCount = 0;

$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT matricNo) AS total
    FROM assigned
    WHERE SPmatric = ?
");
$stmt->bind_param("s", $spMatric);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $studentCount = (int)$row['total'];
}
$stmt->close();

/* Load assigned students */
$students = [];

$sql = "
    SELECT DISTINCT
        a.assignID,
        a.role,
        a.assignedDate,
        s.matricNo,
        s.stuName,
        s.stuEmail,
        s.stuPhNO,
        s.stuProgress
    FROM assigned a
    INNER JOIN student s ON s.matricNo = a.matricNo
    WHERE a.SPmatric = ?
";

if ($search !== '') {
    $sql .= " AND (s.stuName LIKE ? OR s.matricNo LIKE ? OR s.stuEmail LIKE ?) ";
}

$sql .= " ORDER BY a.assignedDate DESC, s.stuName ASC";

$stmt = $conn->prepare($sql);

if ($search !== '') {
    $like = "%" . $search . "%";
    $stmt->bind_param("ssss", $spMatric, $like, $like, $like);
} else {
    $stmt->bind_param("s", $spMatric);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Students</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="app-bg">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">

    <div class="card">
        <div class="profile-header">
            
            <div class="welcome-text">
                <h2>My Students</h2>
                <p>View and evaluate students assigned to you.</p>
            </div>
        </div>

        <div class="summary-grid" style="margin-top:20px;">
            <div class="summary-box">
                <span class="summary-label">Total Assigned Students</span>
                <span class="summary-value"><?php echo $studentCount; ?></span>
            </div>

            <div class="summary-box">
                <span class="summary-label">Matrics ID</span>
                <span class="summary-value"><?php echo htmlspecialchars($spMatric); ?></span>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:20px;">

    <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap;">
        <div>
            <h3 style="margin:0;">Assigned Students</h3>
            <p class="subtitle" style="margin:4px 0 0;">Students currently assigned to you.</p>
        </div>

        <form method="GET" action="" style="display:flex; gap:8px; align-items:center; margin:0;">
            <input type="text"
                   name="q"
                   value="<?php echo htmlspecialchars($search); ?>"
                   placeholder="Search students..."
                   style="padding:10px 12px; border:1px solid var(--border); border-radius:8px; background:var(--bg-input); color:var(--text-main); width:260px;">

            <button type="submit" class="btn view small" style="width:auto;">Search</button>

            <?php if ($search !== ''): ?>
                <a href="my_students.php" class="btn small" style="width:auto;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

        <div class="report-list">
            <?php if (empty($students)): ?>
                <div class="empty-state">
                    <div class="icon-huge">👥</div>
                    <h3>No Students Assigned</h3>
                    <p>You have not been assigned any students yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($students as $student): ?>
                    <div class="report-item">
                        <div class="report-info">
                            <h4><?php echo htmlspecialchars($student['stuName']); ?></h4>
                            <p>
                                <?php echo htmlspecialchars($student['matricNo']); ?>
                            </p>
                            <p>
                                
                                Assigned on:
                                <?php
                                echo !empty($student['assignedDate'])
                                    ? date("d M Y", strtotime($student['assignedDate']))
                                    : "N/A";
                                ?>
                            </p>
                        </div>

                        <div class="report-actions">
                            <a href="student_profile.php?matricNo=<?php echo urlencode($student['matricNo']); ?>"
       class="btn view small">
        View
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