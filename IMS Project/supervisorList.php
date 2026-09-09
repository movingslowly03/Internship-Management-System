<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$supervisors = [];

$stmt = $conn->prepare("
    SELECT SPmatric, SPname, SPgmail, SPphNO, SPType, SPprogress
    FROM supervisor
    ORDER BY SPname ASC
");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $supervisors[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Supervisor List</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="app-bg">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">

    <div class="card supervisor-header-card">
        <h2>Manage Supervisors</h2>
        <p class="subtitle">
            Click a supervisor to view their profile and assigned students.
        </p>
    </div>

    <div class="supervisor-list-wrap">

        <?php if (empty($supervisors)): ?>

            <div class="card empty-state-card">
                <div class="empty-icon">👤</div>
                <h3>No Supervisors Found</h3>
                <p>There are no registered supervisors in the database yet.</p>
            </div>

        <?php else: ?>

            <?php foreach ($supervisors as $supervisor): ?>
                <div class="card supervisor-item-card">

                    <div class="supervisor-item-top">
                        <div>
                            <h4><?php echo htmlspecialchars($supervisor['SPname']); ?></h4>
                            <p>
                                <?php echo htmlspecialchars($supervisor['SPmatric']); ?> ·
                                <?php echo htmlspecialchars($supervisor['SPgmail']); ?>
                            </p>
                        </div>

                        <a href="supervisor_profile.php?SPmatric=<?php echo urlencode($supervisor['SPmatric']); ?>"
                           class="btn view small">
                            View
                        </a>
                    </div>

                

                </div>
            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>

<?php include 'footer.php'; ?>

</body>
</html>