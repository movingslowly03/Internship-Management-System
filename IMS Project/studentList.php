<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

$students = [];

$stmt = $conn->prepare("
    SELECT matricNo,
           stuName,
           stuEmail
    FROM student
    ORDER BY stuName ASC
");

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
    <title>Student List</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="app-bg">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">

    <!-- HEADER CARD -->
    <div class="card student-header-card">
        <h2>Manage Students</h2>
        <p class="subtitle">
            Click a student to view their profile, reports, and logbooks.
        </p>
    </div>

    <!-- STUDENT CARDS -->
    <div class="student-list-wrap">

        <?php if (empty($students)): ?>

            <div class="card empty-state-card">
                <div class="empty-icon">👥</div>
                <h3>No Students Found</h3>
                <p>There are no registered students in the database yet.</p>
            </div>

        <?php else: ?>

            <?php foreach ($students as $student): ?>
                <div class="card student-item-card">

                    <div class="student-item-top">
                        <div>
                            <h4><?php echo htmlspecialchars($student['stuName']); ?></h4>
                            <p>
                                <?php echo htmlspecialchars($student['matricNo']); ?>
                                •
                                <?php echo htmlspecialchars($student['stuEmail']); ?>
                            </p>
                        </div>

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

<?php include 'footer.php'; ?>

</body>
</html>