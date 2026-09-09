<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_type'])) {
    header("Location: login.php");
    exit();
}

$userType = $_SESSION['user_type'];
$userName = $_SESSION['user_name'] ?? 'User';
$profilePic = $_SESSION['user_image'] ?? 'images/pretty.png';

$surats = [];

/* DELETE SURAT */
if ($userType === 'admin' && isset($_GET['delete'])) {
    $suratID = (int)$_GET['delete'];

    $stmt = $conn->prepare("
        DELETE FROM surat
        WHERE suratID = ?
    ");
    $stmt->bind_param("i", $suratID);
    $stmt->execute();
    $stmt->close();

    header("Location: surats.php?deleted=1");
    exit();
}

if ($userType === 'admin') {
    $stmt = $conn->prepare("
        SELECT suratID, title, fileName, target_role, uploaded_at
        FROM surat
        ORDER BY uploaded_at DESC, suratID DESC
    ");
    $stmt->execute();
} else {
    $stmt = $conn->prepare("
        SELECT suratID, title, fileName, target_role, uploaded_at
        FROM surat
        WHERE target_role = 'all' OR target_role = ?
        ORDER BY uploaded_at DESC, suratID DESC
    ");
    $stmt->bind_param("s", $userType);
    $stmt->execute();
}

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $surats[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Surats</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="app-bg">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">

    <div class="card">
        <div class="profile-header">
            
            <div class="welcome-text">
                <h2>Essential Letters</h2>
                <p>Download important forms and letters.</p>
            </div>
        </div>

        <?php if ($userType === 'admin'): ?>
            <div class="profile-actions" style="margin-top:20px;">
                <a href="upload_surat.php" class="btn submit">+ Upload Letter</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-top:20px;">
        <h3>Available Files</h3>
        <p class="subtitle">Files are listed according to access level.</p>

        <div class="report-list">
            <?php if (empty($surats)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📄</div>
                    <h3>No Files Found</h3>
                    <p>There are no files available for you yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($surats as $surat): ?>
                    <div class="report-item">

                        <div class="report-info">
                            <h4><?php echo htmlspecialchars($surat['title']); ?></h4>
                            <p><?php echo htmlspecialchars($surat['fileName']); ?></p>
                            <p>
                                For: <?php echo htmlspecialchars($surat['target_role']); ?>
                                · Uploaded on <?php echo date("d M Y", strtotime($surat['uploaded_at'])); ?>
                            </p>
                        </div>

                        <div class="report-actions">
                            <a href="view_surat.php?id=<?php echo (int)$surat['suratID']; ?>"
                               class="btn view small"
                               target="_blank">
                                Download
                            </a>

                            <?php if ($userType === 'admin'): ?>
        <a href="surats.php?delete=<?php echo (int)$surat['suratID']; ?>"
           class="btn delete small"
           onclick="return confirm('Delete this form?');">
            Delete
        </a>
    <?php endif; ?>
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