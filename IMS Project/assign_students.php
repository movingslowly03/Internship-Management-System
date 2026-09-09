<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$SPmatric = trim($_GET['SPmatric'] ?? ($_POST['SPmatric'] ?? ''));
if ($SPmatric === '') {
    die("Invalid supervisor.");
}

$search = trim($_GET['q'] ?? '');
$error = '';
$success = '';

/* =========================
   LOAD SUPERVISOR
========================= */
$supervisor = [
    'SPmatric' => '',
    'SPname' => '',
    'SPgmail' => '',
    'SPType' => ''
];

$stmt = $conn->prepare("
    SELECT SPmatric, SPname, SPgmail, SPType
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

/* =========================
   ASSIGN STUDENTS
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_students'])) {
    $selectedStudents = $_POST['students'] ?? [];

    if (empty($selectedStudents)) {
        $error = "Please select at least one student.";
    } else {
        $assignDate = date('Y-m-d');
        $role = $supervisor['SPType']; // use the supervisor's type as the role

        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("
                INSERT INTO assigned (matricNo, SPmatric, role, assignedDate)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($selectedStudents as $matricNo) {
                $matricNo = trim($matricNo);

                if ($matricNo === '') {
                    continue;
                }

                // Prevent duplicate assignment for the same student and supervisor
                $check = $conn->prepare("
                    SELECT assignID
                    FROM assigned
                    WHERE matricNo = ? AND SPmatric = ?
                    LIMIT 1
                ");
                $check->bind_param("ss", $matricNo, $SPmatric);
                $check->execute();
                $checkResult = $check->get_result();

                if ($checkResult->num_rows === 0) {
                    $stmt->bind_param("ssss", $matricNo, $SPmatric, $role, $assignDate);
                    $stmt->execute();
                }

                $check->close();
            }

            $stmt->close();
            $conn->commit();

            header("Location: supervisor_profile.php?SPmatric=" . urlencode($SPmatric) . "&assigned=1");
            exit();
        } catch (Throwable $e) {
            $conn->rollback();
            $error = "Failed to assign students.";
        }
    }
}

if (isset($_GET['assigned'])) {
    $success = "Student(s) assigned successfully.";
}


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
    }
}

if (isset($_GET['removed'])) {
    $success = "Assignment removed successfully.";
}


/* =========================
   LOAD UNASSIGNED STUDENTS
========================= */
$students = [];

$sql = "
    SELECT s.matricNo, s.stuName, s.stuEmail, s.stuPhNO, s.stuProgress
    FROM student s
    LEFT JOIN assigned a ON s.matricNo = a.matricNo
    WHERE a.matricNo IS NULL
";

$params = [];
$types = "";

if ($search !== '') {
    $sql .= " AND (s.stuName LIKE ? OR s.matricNo LIKE ? OR s.stuEmail LIKE ?) ";
    $like = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types = "sss";
}

$sql .= " ORDER BY s.stuName ASC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
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
    <title>Assign Students</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="app-bg">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">

    <div class="card supervisor-header-card">
        <h2>Assign Students</h2>
        <p class="subtitle">
            Assign unassigned students to
            <?php echo htmlspecialchars($supervisor['SPname']); ?>
            (<?php echo htmlspecialchars($supervisor['SPmatric']); ?>)
        </p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="card" style="margin-bottom:20px;">
            <p style="color:#ef4444; margin:0;"><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="card" style="margin-bottom:20px;">
            <p style="color:#22c55e; margin:0;"><?php echo htmlspecialchars($success); ?></p>
        </div>
    <?php endif; ?>

    <div class="card" style="margin-bottom:20px;">
        <form method="GET" action="assign_students.php">
            <input type="hidden" name="SPmatric" value="<?php echo htmlspecialchars($SPmatric); ?>">

            <div class="form-group">
                <label>Search Student</label>
                <input type="text"
                       name="q"
                       value="<?php echo htmlspecialchars($search); ?>"
                       placeholder="Search by name, matric number, or email">
            </div>

            <div class="form-actions" style="justify-content:flex-start;">
                <button type="submit" class="btn view">Search</button>
                <a href="assign_students.php?SPmatric=<?php echo urlencode($SPmatric); ?>"
                   class="btn">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <div class="card">
        <?php if (empty($students)): ?>
            <div class="empty-state">
                <div class="empty-icon">👥</div>
                <h3>No Unassigned Students Found</h3>
                <p>All students are already assigned, or none match your search.</p>
            </div>
        <?php else: ?>
            <form method="POST" action="assign_students.php?SPmatric=<?php echo urlencode($SPmatric); ?>">

                <input type="hidden" name="SPmatric" value="<?php echo htmlspecialchars($SPmatric); ?>">

                <div class="report-list">
                    <?php foreach ($students as $student): ?>
                        <div class="report-item">

                            <div class="report-info">
                                <label style="display:flex; align-items:center; gap:12px; cursor:pointer;">
                                    <input type="checkbox"
                                           name="students[]"
                                           value="<?php echo htmlspecialchars($student['matricNo']); ?>">

                                    <div>
                                        <h4 style="margin:0;">
                                            <?php echo htmlspecialchars($student['stuName']); ?>
                                        </h4>
                                        <p>
                                            <?php echo htmlspecialchars($student['matricNo']); ?> ·
                                            <?php echo htmlspecialchars($student['stuEmail']); ?>
                                        </p>
                                    </div>
                                </label>
                            </div>


                            

                        </div>
                    <?php endforeach; ?>

                    
                </div>

                 <div class="form-actions" style="justify-content:flex-end; margin-top:20px;">
                    <a href="supervisor_profile.php?SPmatric=<?php echo urlencode($SPmatric); ?>" class="btn">
                        Back
                    </a>
                    <button type="submit" name="assign_students" class="btn submit">
                        Assign Selected
                    </button>
                </div>

                

            </form>
        <?php endif; ?>
    </div>

</div>

<?php include 'footer.php'; ?>

</body>
</html>