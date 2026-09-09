<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$SPmatric = trim($_GET['SPmatric'] ?? '');
if ($SPmatric === '') {
    die("Invalid supervisor.");
}

$error = '';
$success = '';

// -------------------------
// REMOVE ASSIGNMENT
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_assignment'])) {
    $assignID = (int)($_POST['assignID'] ?? 0);

    if ($assignID > 0) {
        $stmt = $conn->prepare("
            DELETE FROM assigned
            WHERE assignID = ? AND SPmatric = ?
        ");
        $stmt->bind_param("is", $assignID, $SPmatric);

        if ($stmt->execute()) {
            header("Location: supervisor_profile.php?SPmatric=" . urlencode($SPmatric) . "&removed=1");
            exit();
        } else {
            $error = "Failed to remove assignment.";
        }

        $stmt->close();
    } else {
        $error = "Invalid assignment.";
    }
}

if (isset($_GET['removed'])) {
    $success = "Assignment removed successfully.";
}

// -------------------------
// DELETE SUPERVISOR
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_supervisor'])) {

    $conn->begin_transaction();

    try {

        // Remove supervisor assignments first
        $stmt = $conn->prepare("
            DELETE FROM assigned
            WHERE SPmatric = ?
        ");
        $stmt->bind_param("s", $SPmatric);
        $stmt->execute();
        $stmt->close();

        // Delete supervisor
        $stmt = $conn->prepare("
            DELETE FROM supervisor
            WHERE SPmatric = ?
        ");
        $stmt->bind_param("s", $SPmatric);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        header("Location: supervisorList.php?deleted=1");
        exit();

    } catch (Throwable $e) {

        $conn->rollback();
        $error = "Failed to delete supervisor.";

    }
}

// -------------------------
// LOAD SUPERVISOR
// -------------------------
$supervisor = [
    'SPmatric' => '',
    'SPname' => '',
    'SPgmail' => '',
    'SPphNO' => '',
    'SPType' => '',
    'SPprogress' => 0
];

$stmt = $conn->prepare("
    SELECT SPmatric, SPname, SPgmail, SPphNO, SPType, SPprogress
    FROM supervisor
    WHERE SPmatric = ?
    LIMIT 1
");
$stmt->bind_param("s", $SPmatric);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $supervisor = $row;
} else {
    die("Supervisor not found.");
}
$stmt->close();

// -------------------------
// LOAD ASSIGNED STUDENTS
// -------------------------
$assignedStudents = [];

$stmt = $conn->prepare("
    SELECT a.assignID, a.role, a.assignedDate,
           s.matricNo, s.stuName, s.stuEmail, s.stuPhNO
    FROM assigned a
    INNER JOIN student s ON a.matricNo = s.matricNo
    WHERE a.SPmatric = ?
    ORDER BY a.assignedDate DESC, s.stuName ASC
");
$stmt->bind_param("s", $SPmatric);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $assignedStudents[] = $row;
}
$stmt->close();
?>

<?php if (isset($_GET['deleted'])): ?>
    <div class="card" style="margin-bottom:20px;">
        <p style="color:#22c55e; margin:0;">
            Supervisor deleted successfully.
        </p>
    </div>
<?php endif; ?>

<!DOCTYPE html>
<html>
<head>
    <title>Supervisor Profile</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="app-bg">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">

    <div class="card profile-card internship-card">

        <div class="profile-top">
            <img src="images/pretty.png" class="profile-pic-large" alt="Profile Picture">

            <div class="profile-info">
                <h2><?php echo htmlspecialchars($supervisor['SPname']); ?></h2>
                <p><?php echo htmlspecialchars($supervisor['SPmatric']); ?></p>
                <p><?php echo htmlspecialchars($supervisor['SPgmail']); ?></p>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <p style="color:#ef4444;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p style="color:#22c55e;"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <div class="summary-grid">
            <div class="summary-box">
                <span class="summary-label">Phone Number</span>
                <span class="summary-value"><?php echo htmlspecialchars($supervisor['SPphNO']); ?></span>
            </div>

            <div class="summary-box">
                <span class="summary-label">Type</span>
                <span class="summary-value"><?php echo htmlspecialchars($supervisor['SPType']); ?></span>
            </div>

            <div class="summary-box">
                <span class="summary-label">Progress</span>
                <span class="summary-value"><?php echo (int)$supervisor['SPprogress']; ?>%</span>
            </div>

            <div class="summary-box">
                <span class="summary-label">Assigned Students</span>
                <span class="summary-value"><?php echo count($assignedStudents); ?></span>
            </div>
        </div>

        <div class="profile-actions" style="margin-top:20px;">
    <a href="supervisorList.php" class="btn">Back</a>

    <a href="assign_students.php?SPmatric=<?php echo urlencode($supervisor['SPmatric']); ?>"
       class="btn submit">
        Assign Students
    </a>

    <form method="POST"
          style="display:inline;"
          onsubmit="return confirm('Delete this supervisor and remove all assignments?');">
        <button type="submit"
                name="delete_supervisor"
                class="btn delete">
            Delete Supervisor
        </button>
    </form>
</div>
    </div>

    <div class="card" style="margin-top:20px;">
        <h3>Assigned Students</h3>

        <?php if (empty($assignedStudents)): ?>
            <p>No students assigned yet.</p>
        <?php else: ?>
            <div class="report-list">
                <?php foreach ($assignedStudents as $student): ?>
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
                                View Student
                            </a>

                            <form method="POST"
                                  style="display:inline;"
                                  onsubmit="return confirm('Remove this assignment?');">
                                <input type="hidden" name="assignID" value="<?php echo (int)$student['assignID']; ?>">
                                <button type="submit" name="remove_assignment" class="btn delete small">
                                    Remove
                                </button>
                            </form>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php include 'footer.php'; ?>

</body>
</html>