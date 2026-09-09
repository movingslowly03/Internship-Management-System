<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], ['admin', 'supervisor'])) {
    header("Location: login.php");
    exit();
}

$matricNo = trim($_GET['matricNo'] ?? '');
if ($matricNo === '') {
    die("Invalid student.");
}

$isAdmin = ($_SESSION['user_type'] === 'admin');
$isSupervisor = ($_SESSION['user_type'] === 'supervisor');

$error = '';
$success = '';

/* =========================
   DELETE STUDENT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student'])) {
    $deleteMatricNo = trim($_POST['matricNo'] ?? '');

    if ($deleteMatricNo === '') {
        $error = "Invalid student.";
    } else {
        $conn->begin_transaction();

try {

    $stmt = $conn->prepare("DELETE FROM assigned WHERE matricNo = ?");
    $stmt->bind_param("s", $deleteMatricNo);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM evaluation_form WHERE matricNo = ?");
    $stmt->bind_param("s", $deleteMatricNo);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM monitoring_form WHERE matricNo = ?");
    $stmt->bind_param("s", $deleteMatricNo);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM internship WHERE matricNo = ?");
    $stmt->bind_param("s", $deleteMatricNo);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM report WHERE matricNo = ?");
    $stmt->bind_param("s", $deleteMatricNo);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM logbook WHERE matricNo = ?");
    $stmt->bind_param("s", $deleteMatricNo);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM student WHERE matricNo = ?");
    $stmt->bind_param("s", $deleteMatricNo);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    header("Location: studentList.php?deleted=1");
    exit();

} catch (Throwable $e) {

    $conn->rollback();

    $error = $e->getMessage();
}
    }
}

/* =========================
   LOAD STUDENT
========================= */
$student = [
    'matricNo' => '',
    'stuName' => '',
    'stuEmail' => '',
    'stuPhNO' => '',
    'stuSubjectRepeat' => 0,
    'stuEligibility' => 0,
    'stuProgress' => 0
];

$stmt = $conn->prepare("
    SELECT matricNo, stuName, stuEmail, stuPhNO, stuSubjectRepeat, stuEligibility, stuProgress
    FROM student
    WHERE matricNo = ?
    LIMIT 1
");
$stmt->bind_param("s", $matricNo);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $student = $row;
} else {
    die("Student not found.");
}
$stmt->close();

/* =========================
   LOAD INTERNSHIP
   (industrial supervisor info lives here)
========================= */
$internship = [
    'companyName' => '',
    'companyAddress' => '',
    'companyEmail' => '',
    'companyPhone' => '',
    'supervisorName' => '',
    'supervisorPosition' => '',
    'supervisorEmail' => '',
    'startDate' => '',
    'endDate' => '',
    'internStatus' => ''
];

$stmt = $conn->prepare("
    SELECT companyName, companyAddress, companyEmail, companyPhone,
           supervisorName, supervisorPosition, supervisorEmail,
           startDate, endDate, internStatus
    FROM internship
    WHERE matricNo = ?
    LIMIT 1
");
$stmt->bind_param("s", $matricNo);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $internship = $row;
}
$stmt->close();

$internshipStatus = $internship['internStatus'] ?? 'Pending';

/* =========================
   LOAD ASSIGNED SUPERVISORS
   (educational supervisors)
========================= */
$assignedSupervisors = [];

$stmt = $conn->prepare("
    SELECT a.role, a.assignedDate, s.SPmatric, s.SPname, s.SPgmail, s.SPphNO, s.SPType
    FROM assigned a
    INNER JOIN supervisor s ON a.SPmatric = s.SPmatric
    WHERE a.matricNo = ?
    ORDER BY a.assignedDate DESC
");
$stmt->bind_param("s", $matricNo);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $assignedSupervisors[] = $row;
}
$stmt->close();

/* =========================
   LOAD REPORTS
========================= */
$reports = [];

$stmt = $conn->prepare("
    SELECT reportID, fileName, submission_date, reportStatus,
       reviewStatus,
       reviewFeedback,
       reviewedAt
    FROM report
    WHERE matricNo = ?
    ORDER BY submission_date DESC, reportID DESC
");
$stmt->bind_param("s", $matricNo);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}
$stmt->close();

/* =========================
   LOAD LOGBOOKS
========================= */
$logbooks = [];

$stmt = $conn->prepare("
    SELECT logID, fileName, submission_date, logbookStatus,
       reviewStatus,
       reviewFeedback,
       reviewedAt
    FROM logbook
    WHERE matricNo = ?
    ORDER BY submission_date DESC, logID DESC
");
$stmt->bind_param("s", $matricNo);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $logbooks[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Profile</title>
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
                <h2><?php echo htmlspecialchars($student['stuName']); ?></h2>
                <p><?php echo htmlspecialchars($student['matricNo']); ?></p>
                <p><?php echo htmlspecialchars($student['stuEmail']); ?></p>
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
                <span class="summary-value"><?php echo htmlspecialchars($student['stuPhNO']); ?></span>
            </div>

            <div class="summary-box">
    <span class="summary-label">Eligibility</span>
    <span class="summary-value">
        <?php echo ($internshipStatus === 'Submitted' || $internshipStatus === 'Approved')
            ? 'Eligible'
            : 'Not Eligible'; ?>
    </span>
</div>

            <div class="summary-box">
                <span class="summary-label">Progress</span>
                <span class="summary-value"><?php echo (int)$student['stuProgress']; ?>%</span>
            </div>

            <div class="summary-box">
                <span class="summary-label">Subject Repeat</span>
                <span class="summary-value"><?php echo !empty($student['stuSubjectRepeat']) ? 'Yes' : 'No'; ?></span>
            </div>
        </div>

        <div class="profile-actions" style="margin-top:20px;">
            <button type="button"
            class="btn"
            onclick="history.back();">Back
            </button>

            <?php if ($isSupervisor): ?>
            <a href="monitoring_form.php?matricNo=<?php echo urlencode($student['matricNo']); ?>"
   class="btn view small">
    BLI-06 Borang Lawatan
</a>
<?php endif; ?>

<?php if ($isSupervisor): ?>
            <a href="SVevaluation_form.php?matricNo=<?php echo urlencode($student['matricNo']); ?>"
   class="btn view small">
    BLI-08 Borang Penilaian
</a>
<?php endif; ?>

            <?php if ($isAdmin): ?>
    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this student and all related records?');">
        <input type="hidden" name="matricNo" value="<?php echo htmlspecialchars($student['matricNo']); ?>">
        <button type="submit" name="delete_student" class="btn delete">Delete Student</button>
    </form>
<?php endif; ?>
        </div>
    </div>

    <div class="card" style="margin-top:20px;">
        <h3>Internship Information</h3>

        <?php if (empty($internship['companyName'])): ?>
            <p>No internship information found.</p>
        <?php else: ?>
            <div class="internship-grid">

                <div class="info-card">
                    <span class="info-label">Company</span>
                    <span class="info-value"><?php echo htmlspecialchars($internship['companyName']); ?></span>
                </div>

                <div class="info-card">
                    <span class="info-label">Status</span>
                    <span class="info-value"><?php echo htmlspecialchars($internship['internStatus']); ?></span>
                </div>

                <div class="info-card">
                    <span class="info-label">Industrial Supervisor</span>
                    <span class="info-value"><?php echo htmlspecialchars($internship['supervisorName']); ?></span>
                </div>

                <div class="info-card">
                    <span class="info-label">Position</span>
                    <span class="info-value"><?php echo htmlspecialchars($internship['supervisorPosition']); ?></span>
                </div>

                <div class="info-card">
                    <span class="info-label">Company Email</span>
                    <span class="info-value"><?php echo htmlspecialchars($internship['companyEmail']); ?></span>
                </div>

                <div class="info-card">
                    <span class="info-label">Company Phone</span>
                    <span class="info-value"><?php echo htmlspecialchars($internship['companyPhone']); ?></span>
                </div>

                <div class="info-card">
                    <span class="info-label">Start Date</span>
                    <span class="info-value">
                        <?php echo !empty($internship['startDate']) ? date('d M Y', strtotime($internship['startDate'])) : 'N/A'; ?>
                    </span>
                </div>

                <div class="info-card">
                    <span class="info-label">End Date</span>
                    <span class="info-value">
                        <?php echo !empty($internship['endDate']) ? date('d M Y', strtotime($internship['endDate'])) : 'N/A'; ?>
                    </span>
                </div>

            </div>

            <div class="address-card">
                <span class="info-label">Company Address</span>
                <p><?php echo htmlspecialchars($internship['companyAddress']); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-top:20px;">
        <h3>Assigned Academic Supervisors</h3>

        <?php if (empty($assignedSupervisors)): ?>
            <p>No supervisors assigned yet.</p>
        <?php else: ?>
            <div class="report-list">
                <?php foreach ($assignedSupervisors as $sup): ?>
                    <div class="report-item">
                        <div class="report-info">
                            <h4><?php echo htmlspecialchars($sup['SPname']); ?></h4>
                            <p>
                                
                                <?php echo htmlspecialchars($sup['SPType']); ?>
                            </p>
                            <p><?php echo htmlspecialchars($sup['SPgmail']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-top:20px;">
        <h3>Uploaded Documents</h3>

        <div class="report-list">
            <?php if (empty($reports)): ?>
                <p>No reports uploaded.</p>
            <?php else: ?>
                <?php foreach ($reports as $report): ?>
                    <div class="report-item">
                        <div class="report-info">
                            <h4><?php echo htmlspecialchars($report['fileName']); ?></h4>
                            <p>
                                Uploaded on
                                <?php echo !empty($report['submission_date']) ? date("d M Y", strtotime($report['submission_date'])) : "N/A"; ?>
                            </p>

                            <?php if (!empty($report['reviewFeedback'])): ?>
            <p class="mini-sub">
                Feedback:
                <?php echo nl2br(htmlspecialchars($report['reviewFeedback'])); ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($report['reviewedAt'])): ?>
            <p class="mini-sub">
                Reviewed on
                <?php echo date("d M Y", strtotime($report['reviewedAt'])); ?>
            </p>
        <?php endif; ?>
                        </div>

                        <div class="report-actions">
                            <a href="view_report.php?id=<?php echo $report['reportID']; ?>"
                               class="btn view small"
                               target="_blank">
                                View
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="card" style="margin-top:20px;">
        <h3>Uploaded Logbooks</h3>

        <div class="report-list">
            <?php if (empty($logbooks)): ?>
                <p>No logbooks uploaded.</p>
            <?php else: ?>
                <?php foreach ($logbooks as $logbook): ?>
                    <div class="report-item">
                        <div class="report-info">
                            <h4><?php echo htmlspecialchars($logbook['fileName']); ?></h4>
                            <p>
                                Uploaded on
                                <?php echo !empty($logbook['submission_date']) ? date("d M Y", strtotime($logbook['submission_date'])) : "N/A"; ?>
                            </p>

                            <?php if (!empty($logbook['reviewFeedback'])): ?>
            <p class="mini-sub">
                Feedback:
                <?php echo nl2br(htmlspecialchars($logbook['reviewFeedback'])); ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($logbook['reviewedAt'])): ?>
            <p class="mini-sub">
                Reviewed on
                <?php echo date("d M Y", strtotime($logbook['reviewedAt'])); ?>
            </p>
        <?php endif; ?>

                        </div>

                        <div class="report-actions">
                            <a href="view_logbook.php?id=<?php echo $logbook['logID']; ?>"
                               class="btn view small"
                               target="_blank">
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