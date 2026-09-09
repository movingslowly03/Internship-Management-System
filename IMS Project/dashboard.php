<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_type'])) {
    header("Location: login.php");
    exit();
}

$userType = $_SESSION['user_type'];
$userName = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['email'] ?? '';
$profilePic = $_SESSION['user_image'] ?? 'images/pretty.png';

function getLatestAnnouncementForRole(mysqli $conn, string $role): ?array
{
    $stmt = $conn->prepare("
        SELECT title, message, posted_at
        FROM announcement
        WHERE is_active = 1
          AND (target_role = 'all' OR target_role = ?)
        ORDER BY posted_at DESC, announcementID DESC
        LIMIT 1
    ");

    $stmt->bind_param("s", $role);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc() ?: null;
    $stmt->close();

    return $row;
}

function getLatestAnnouncementAny(mysqli $conn): ?array
{
    $stmt = $conn->prepare("
        SELECT title, message, target_role, posted_at
        FROM announcement
        WHERE is_active = 1
        ORDER BY posted_at DESC, announcementID DESC
        LIMIT 1
    ");

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc() ?: null;
    $stmt->close();

    return $row;
}

/* Refresh current user details from database */
if ($userType === 'student') {
    $matricNo = $_SESSION['matricNo'] ?? '';

    $stmt = $conn->prepare("
        SELECT stuName, stuEmail
        FROM student
        WHERE matricNo = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $matricNo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $userName = $row['stuName'] ?? $userName;
        $userEmail = $row['stuEmail'] ?? $userEmail;
    }
    $stmt->close();

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

} elseif ($userType === 'supervisor') {
    $spMatric = $_SESSION['SPmatric'] ?? '';

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

} elseif ($userType === 'admin') {
    $adminId = $_SESSION['admin_id'] ?? '';

    $stmt = $conn->prepare("
        SELECT SAname, SAgmail
        FROM superadmin
        WHERE SAmatrix = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $adminId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $userName = $row['SAname'] ?? $userName;
        $userEmail = $row['SAgmail'] ?? $userEmail;
    }
    $stmt->close();
}

$_SESSION['user_name'] = $userName;
$_SESSION['email'] = $userEmail;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>

<body class="app-bg">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">

<?php if ($userType === 'student'): ?>

    <?php
    $matricNo = $_SESSION['matricNo'] ?? '';

    $logbookSubmitted = false;
    $reportSubmitted = false;
    $internshipStatus = 'Pending';
    $latestLogbookID = null;
    $latestReportID = null;

    $stmt = $conn->prepare("
        SELECT logID
        FROM logbook
        WHERE matricNo = ?
        ORDER BY logID DESC
        LIMIT 1
    ");
    $stmt->bind_param("s", $matricNo);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $latestLogbookID = (int)$row['logID'];
        $logbookSubmitted = true;
    }
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT reportID
        FROM report
        WHERE matricNo = ?
        ORDER BY reportID DESC
        LIMIT 1
    ");
    $stmt->bind_param("s", $matricNo);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $latestReportID = (int)$row['reportID'];
        $reportSubmitted = true;
    }
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT internStatus
        FROM internship
        WHERE matricNo = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $matricNo);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $internshipStatus = $row['internStatus'] ?? 'Pending';
    }
    $stmt->close();

    $studentAnnouncement = getLatestAnnouncementForRole($conn, 'student');

   $supervisorName = 'Not Assigned';

$supervisorName = 'Not Assigned';
$supervisorEmail = '';

$stmt = $conn->prepare("
    SELECT s.SPname, s.SPgmail
    FROM assigned a
    INNER JOIN supervisor s
        ON s.SPmatric = a.SPmatric
    WHERE a.matricNo = ?
    LIMIT 1
");
$stmt->bind_param("s", $matricNo);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $supervisorName = $row['SPname'];
    $supervisorEmail = $row['SPgmail'];
}
$stmt->close();
    ?>

    

    <div class="profile-header">
        <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile Picture" class="profile-pic">
        <div class="welcome-text">
            <h2>Welcome, <?php echo htmlspecialchars($userName); ?>!</h2>
            <p>Student</p>
        </div>
    </div>

    <div class="announcement-box">
        <h3>Announcement</h3>

        <?php if ($studentAnnouncement): ?>
            <h4 style="margin-bottom:8px;"><?php echo htmlspecialchars($studentAnnouncement['title']); ?></h4>
            <p style="margin-top:0;"><?php echo nl2br(htmlspecialchars($studentAnnouncement['message'])); ?></p>
            <p class="mini-sub">
                Posted on <?php echo date("d M Y", strtotime($studentAnnouncement['posted_at'])); ?>
            </p>
        <?php else: ?>
            <p>No announcements at the moment.</p>
        <?php endif; ?>
    </div>

    

<div class="announcement-box">
    
        <h3>Assigned Supervisor</h3>

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
    

    <div class="dashboard-grid">
        <div class="card compact">
            <div class="card-header">
                <h3>Profile</h3>
            </div>
            <div class="card-body">
                <p class="mini-text"><?php echo htmlspecialchars($matricNo); ?></p>
                <a href="profile.php" class="btn view small">View</a>
            </div>
        </div>

        <?php
$displayStatus = trim($internshipStatus);

if ($displayStatus === '') {
    $displayStatus = 'Pending';
}
?>

        <div class="card compact">
    <div class="card-header">
        <h3>Internship Details</h3>

        <?php if ($displayStatus === 'Pending'): ?>
            <span class="status pending">
                <?php echo htmlspecialchars($displayStatus); ?>
            </span>
        <?php else: ?>
            <span class="status submitted">
                <?php echo htmlspecialchars($displayStatus); ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="card-body">
        <?php if ($displayStatus === 'Pending'): ?>
            <a href="internship.php" class="btn update small">Manage</a>
        <?php else: ?>
            <a href="internship.php" class="btn view small">View</a>
        <?php endif; ?>
    </div>
</div>

    


        <div class="card compact">
            <div class="card-header">
                <h3>Logbook</h3>
                <?php if ($logbookSubmitted): ?>
                    <span class="status submitted">Submitted</span>
                <?php else: ?>
                    <span class="status pending">Pending</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($logbookSubmitted && $latestLogbookID): ?>
                    <a href="view_logbook.php?id=<?php echo $latestLogbookID; ?>" class="btn view small">View</a>
                <?php else: ?>
                    <a href="upload_logbook.php" class="btn submit small">Upload</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card compact">
            <div class="card-header">
                <h3>Report Submission</h3>
                <?php if ($reportSubmitted): ?>
                    <span class="status submitted">Submitted</span>
                <?php else: ?>
                    <span class="status pending">Pending</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($reportSubmitted && $latestReportID): ?>
                    <a href="view_report.php?id=<?php echo $latestReportID; ?>" class="btn view small">View</a>
                <?php else: ?>
                    <a href="upload_doc.php" class="btn submit small">Upload</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php elseif ($userType === 'supervisor'): ?>

    <?php
    $spMatric = $_SESSION['SPmatric'] ?? '';
    if ($spMatric === '') {
        die("Supervisor not logged in.");
    }

    $studentCount = 0;
    $pendingLogbookCount = 0;
$pendingReportCount = 0;
    $supervisorAnnouncement = null;

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

    $stmt = $conn->prepare("
    SELECT COUNT(DISTINCT l.logID) AS total
    FROM logbook l
    INNER JOIN assigned a ON a.matricNo = l.matricNo
    WHERE a.SPmatric = ?
      AND COALESCE(l.reviewStatus, 'Pending') = 'Pending'
");
$stmt->bind_param("s", $spMatric);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $pendingLogbookCount = (int)$row['total'];
}
$stmt->close();

$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT r.reportID) AS total
    FROM report r
    INNER JOIN assigned a ON a.matricNo = r.matricNo
    WHERE a.SPmatric = ?
      AND COALESCE(r.reviewStatus, 'Pending') = 'Pending'
");
$stmt->bind_param("s", $spMatric);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $pendingReportCount = (int)$row['total'];
}
$stmt->close();

$pendingReviewCount = $pendingLogbookCount + $pendingReportCount;

$studentsWithInternshipDetails = 0;
$reviewedSubmissionCount = 0;

/* Students with internship details */
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT a.matricNo) AS total
    FROM assigned a
    INNER JOIN internship i ON i.matricNo = a.matricNo
    WHERE a.SPmatric = ?
");
$stmt->bind_param("s", $spMatric);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $studentsWithInternshipDetails = (int)$row['total'];
}
$stmt->close();

/* Already reviewed submissions (logbooks + reports) */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM (
        SELECT l.logID
        FROM logbook l
        INNER JOIN assigned a ON a.matricNo = l.matricNo
        WHERE a.SPmatric = ?
          AND COALESCE(l.reviewStatus, 'Pending') IN ('Approved', 'Rejected')

        UNION ALL

        SELECT r.reportID
        FROM report r
        INNER JOIN assigned a ON a.matricNo = r.matricNo
        WHERE a.SPmatric = ?
          AND COALESCE(r.reviewStatus, 'Pending') IN ('Approved', 'Rejected')
    ) reviewed_items
");
$stmt->bind_param("ss", $spMatric, $spMatric);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $reviewedSubmissionCount = (int)$row['total'];
}
$stmt->close();

    $stmt = $conn->prepare("
    SELECT DISTINCT
        l.logID,
        l.fileName,
        l.submission_date,
        l.reviewStatus,
        l.reviewFeedback,
        l.reviewedAt,
        s.matricNo,
        s.stuName
    FROM logbook l
    INNER JOIN assigned a ON a.matricNo = l.matricNo
    INNER JOIN student s ON s.matricNo = l.matricNo
    WHERE a.SPmatric = ?
      AND COALESCE(l.reviewStatus, 'Pending') = 'Pending'
    ORDER BY l.submission_date DESC, l.logID DESC
    LIMIT 5
");
    $stmt->bind_param("s", $spMatric);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $pendingLogbooks[] = $row;
    }
    $stmt->close();

    $supervisorAnnouncement = getLatestAnnouncementForRole($conn, 'supervisor');
    ?>

    <div class="profile-header">
        <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile Picture" class="profile-pic">
        <div class="welcome-text">
            <h2>Welcome, <?php echo htmlspecialchars($userName); ?>!</h2>
            <p>Supervisor</p>
        </div>
    </div>

    <div class="announcement-box">
        <h3>Announcement</h3>

        <?php if ($supervisorAnnouncement): ?>
            <h4 style="margin-bottom:8px;"><?php echo htmlspecialchars($supervisorAnnouncement['title']); ?></h4>
            <p style="margin-top:0;"><?php echo nl2br(htmlspecialchars($supervisorAnnouncement['message'])); ?></p>
            <p class="mini-sub">
                Posted on <?php echo date("d M Y", strtotime($supervisorAnnouncement['posted_at'])); ?>
            </p>
        <?php else: ?>
            <p>No announcements at the moment.</p>
        <?php endif; ?>
    </div>

    <div class="dashboard-grid">
    <div class="card compact">
        <div class="card-header">
            <h3>My Students</h3>
        </div>
        <div class="card-body">
            <p class="mini-text"><?php echo $studentCount; ?></p>
        </div>
    </div>

    <div class="card compact">
        <div class="card-header">
            <h3>Pending Reviews</h3>
        </div>
        <div class="card-body">
            <p class="mini-text"><?php echo $pendingReviewCount; ?></p>
        </div>
    </div>

    <div class="card compact">
        <div class="card-header">
            <h3>Registered Internship</h3>
        </div>
        <div class="card-body">
            <p class="mini-text"><?php echo $studentsWithInternshipDetails; ?></p>
        </div>
    </div>

    <div class="card compact">
        <div class="card-header">
            <h3>Reviewed Submissions</h3>
        </div>
        <div class="card-body">
            <p class="mini-text"><?php echo $reviewedSubmissionCount; ?></p>
        </div>
    </div>
</div>

    

<?php elseif ($userType === 'admin'): ?>

    <?php
    $studentCount = 0;
    $supervisorCount = 0;
    $assignedCount = 0;
    $internshipCount = 0;
    $logbookCount = 0;
    $reportCount = 0;

    $studentQuery = $conn->query("SELECT COUNT(*) AS total FROM student");
    if ($studentQuery) {
        $studentCount = (int)$studentQuery->fetch_assoc()['total'];
    }

    $supervisorQuery = $conn->query("SELECT COUNT(*) AS total FROM supervisor");
    if ($supervisorQuery) {
        $supervisorCount = (int)$supervisorQuery->fetch_assoc()['total'];
    }

    $assignedQuery = $conn->query("SELECT COUNT(*) AS total FROM assigned");
    if ($assignedQuery) {
        $assignedCount = (int)$assignedQuery->fetch_assoc()['total'];
    }

    $internshipQuery = $conn->query("SELECT COUNT(*) AS total FROM internship");
    if ($internshipQuery) {
        $internshipCount = (int)$internshipQuery->fetch_assoc()['total'];
    }

    $logbookQuery = $conn->query("SELECT COUNT(*) AS total FROM logbook");
    if ($logbookQuery) {
        $logbookCount = (int)$logbookQuery->fetch_assoc()['total'];
    }

    $reportQuery = $conn->query("SELECT COUNT(*) AS total FROM report");
    if ($reportQuery) {
        $reportCount = (int)$reportQuery->fetch_assoc()['total'];
    }

    $adminAnnouncement = getLatestAnnouncementAny($conn);
    ?>

    <div class="profile-header">
        <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile Picture" class="profile-pic">
        <div class="welcome-text">
            <h2>Welcome, <?php echo htmlspecialchars($userName); ?>!</h2>
            <p>Admin</p>
        </div>
    </div>

    <div class="announcement-box">
        <h3>Announcement</h3>

        <?php if ($adminAnnouncement): ?>
            <h4 style="margin-bottom:8px;"><?php echo htmlspecialchars($adminAnnouncement['title']); ?></h4>
            <p style="margin-top:0;"><?php echo nl2br(htmlspecialchars($adminAnnouncement['message'])); ?></p>
            <p class="mini-sub">
                Posted on <?php echo date("d M Y", strtotime($adminAnnouncement['posted_at'])); ?>
            </p>
            <p class="mini-sub">
                Target: <?php echo htmlspecialchars($adminAnnouncement['target_role']); ?>
            </p>
        <?php else: ?>
            <p>No announcements at the moment.</p>
        <?php endif; ?>

        <a href="announcement.php" class="btn submit small">Manage Announcements</a>
    </div>

    <div class="dashboard-grid">
        <div class="card compact">
            <div class="card-header">
                <h3>Total Students</h3>
            </div>
            <div class="card-body">
                <p class="mini-text"><?php echo $studentCount; ?></p>
            </div>
        </div>

        <div class="card compact">
            <div class="card-header">
                <h3>Total Supervisors</h3>
            </div>
            <div class="card-body">
                <p class="mini-text"><?php echo $supervisorCount; ?></p>
            </div>
        </div>

        <div class="card compact">
            <div class="card-header">
                <h3>Assigned Supervisors</h3>
            </div>
            <div class="card-body">
                <p class="mini-text"><?php echo $assignedCount; ?></p>
            </div>
        </div>

        <div class="card compact">
            <div class="card-header">
                <h3>Registered Internships</h3>
            </div>
            <div class="card-body">
                <p class="mini-text"><?php echo $internshipCount; ?></p>
            </div>
        </div>

        <div class="card compact">
            <div class="card-header">
                <h3>Logbooks Submitted</h3>
            </div>
            <div class="card-body">
                <p class="mini-text"><?php echo $logbookCount; ?></p>
            </div>

            
        </div>

        <div class="card compact">
            <div class="card-header">
                <h3>Reports Submitted</h3>
            </div>
            <div class="card-body">
                <p class="mini-text"><?php echo $reportCount; ?></p>
            </div>
        </div>
    </div>

<?php endif; ?>

</div>

<?php include 'footer.php'; ?>

</body>
</html>