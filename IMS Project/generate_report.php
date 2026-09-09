<?php
session_start();
include 'db_connect.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$adminId = $_SESSION['admin_id'] ?? '';
$adminName = $_SESSION['user_name'] ?? 'Admin';
$adminEmail = $_SESSION['email'] ?? '';

/* Refresh admin details */
if ($adminId !== '') {
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
        $adminName = $row['SAname'] ?? $adminName;
        $adminEmail = $row['SAgmail'] ?? $adminEmail;
    }
    $stmt->close();
}

$_SESSION['user_name'] = $adminName;
$_SESSION['email'] = $adminEmail;

function getCount(mysqli $conn, string $sql): int
{
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        return (int)($row['total'] ?? 0);
    }
    return 0;
}

/* Overall totals */
$totalStudents = getCount($conn, "SELECT COUNT(*) AS total FROM student");
$totalSupervisors = getCount($conn, "SELECT COUNT(*) AS total FROM supervisor");
$totalAssignedRows = getCount($conn, "SELECT COUNT(*) AS total FROM assigned");
$totalAssignedStudents = getCount($conn, "SELECT COUNT(DISTINCT matricNo) AS total FROM assigned");
$totalInternships = getCount($conn, "SELECT COUNT(*) AS total FROM internship");
$totalLogbooks = getCount($conn, "SELECT COUNT(*) AS total FROM logbook");
$totalReports = getCount($conn, "SELECT COUNT(*) AS total FROM report");
$totalAnnouncements = getCount($conn, "SELECT COUNT(*) AS total FROM announcement WHERE is_active = 1");

/* Review stats */
$pendingLogbookReviews = getCount($conn, "
    SELECT COUNT(*) AS total
    FROM logbook
    WHERE COALESCE(NULLIF(reviewStatus, ''), 'Pending') = 'Pending'
");

$completedLogbookReviews = getCount($conn, "
    SELECT COUNT(*) AS total
    FROM logbook
    WHERE reviewedAt IS NOT NULL
");

$pendingReportReviews = getCount($conn, "
    SELECT COUNT(*) AS total
    FROM report
    WHERE COALESCE(NULLIF(reviewStatus, ''), 'Pending') = 'Pending'
");

$completedReportReviews = getCount($conn, "
    SELECT COUNT(*) AS total
    FROM report
    WHERE reviewedAt IS NOT NULL
");

/* Performance rates */
$assignmentCoverage = $totalStudents > 0 ? round(($totalAssignedStudents / $totalStudents) * 100, 1) : 0;
$internshipCoverage = $totalStudents > 0 ? round(($totalInternships / $totalStudents) * 100, 1) : 0;
$logbookReviewRate = $totalLogbooks > 0 ? round(($completedLogbookReviews / $totalLogbooks) * 100, 1) : 0;
$reportReviewRate = $totalReports > 0 ? round(($completedReportReviews / $totalReports) * 100, 1) : 0;

/* Lists for report */
$pendingLogbooks = [];
$stmt = $conn->prepare("
    SELECT l.logID, l.fileName, l.submission_date, s.matricNo, s.stuName
    FROM logbook l
    INNER JOIN student s ON s.matricNo = l.matricNo
    WHERE COALESCE(NULLIF(l.reviewStatus, ''), 'Pending') = 'Pending'
    ORDER BY l.submission_date DESC, l.logID DESC
    LIMIT 5
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pendingLogbooks[] = $row;
}
$stmt->close();

$pendingReports = [];
$stmt = $conn->prepare("
    SELECT r.reportID, r.fileName, r.submission_date, s.matricNo, s.stuName
    FROM report r
    INNER JOIN student s ON s.matricNo = r.matricNo
    WHERE COALESCE(NULLIF(r.reviewStatus, ''), 'Pending') = 'Pending'
    ORDER BY r.submission_date DESC, r.reportID DESC
    LIMIT 5
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $pendingReports[] = $row;
}
$stmt->close();

$unassignedStudents = [];
$stmt = $conn->prepare("
    SELECT s.matricNo, s.stuName, s.stuEmail
    FROM student s
    LEFT JOIN assigned a ON a.matricNo = s.matricNo
    WHERE a.matricNo IS NULL
    ORDER BY s.stuName ASC
    LIMIT 5
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $unassignedStudents[] = $row;
}
$stmt->close();

$studentsWithoutInternship = [];
$stmt = $conn->prepare("
    SELECT s.matricNo, s.stuName, s.stuEmail
    FROM student s
    LEFT JOIN internship i ON i.matricNo = s.matricNo
    WHERE i.matricNo IS NULL
    ORDER BY s.stuName ASC
    LIMIT 5
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $studentsWithoutInternship[] = $row;
}
$stmt->close();

$generatedAt = date("d M Y, h:i A");
?>

<!DOCTYPE html>
<html>
<head>
    <title>System Performance Report</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .report-header-bar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            flex-wrap: wrap;
        }

        .report-meta {
            color: var(--text-muted);
            font-size: 14px;
            margin-top: 6px;
            line-height: 1.6;
        }

        .report-actions-top {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .report-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }

        .report-summary-box {
            background: #111827;
            border: 1px solid #1f2937;
            border-radius: 12px;
            padding: 16px;
        }

        .report-summary-label {
            display: block;
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .report-summary-value {
            display: block;
            font-size: 28px;
            font-weight: 700;
            color: #fff;
            line-height: 1;
        }

        .report-summary-note {
            display: block;
            margin-top: 8px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .report-section {
            margin-top: 20px;
        }

        .report-section h3 {
            margin-top: 0;
        }

        .report-note {
            color: var(--text-muted);
            font-size: 14px;
            margin-top: 0;
        }

        .report-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
            background: rgba(124, 58, 237, 0.15);
            color: #e9d5ff;
            border: 1px solid rgba(124, 58, 237, 0.25);
        }

        .report-kpi {
            font-size: 32px;
            font-weight: 700;
            color: #fff;
            margin: 0;
        }

        .report-kpi-label {
            color: var(--text-muted);
            font-size: 13px;
            margin: 6px 0 0;
        }

        @media print {
            .header, .sidebar, .footer, .report-actions-top, .no-print {
                display: none !important;
            }

            body.app-bg {
                background: #fff !important;
                padding-top: 0 !important;
                color: #000 !important;
            }

            .main {
                margin-left: 0 !important;
                padding: 0 !important;
            }

            .card, .report-summary-box, .report-item {
                box-shadow: none !important;
                background: #fff !important;
                color: #000 !important;
                border-color: #ccc !important;
            }

            a {
                color: #000 !important;
                text-decoration: none !important;
            }
        }
    </style>
</head>

<body class="app-bg">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">

    <div class="card">
        <div class="report-header-bar">
            <div>
                <h2 style="margin-bottom:6px;">System Performance Report</h2>
                <p class="report-meta">
                    Generated by <strong><?php echo htmlspecialchars($adminName); ?></strong>
                    (<?php echo htmlspecialchars($adminEmail); ?>)<br>
                    Generated on <?php echo $generatedAt; ?>
                </p>
            </div>

            <div class="report-actions-top no-print">
                <a href="javascript:window.print()" class="btn submit" style="width:auto; display:inline-block;">
                    Print Report
                </a>
            </div>
        </div>

        <p class="report-note" style="margin-top:18px;">
            This report summarizes the current system state, review workload, and key operational coverage metrics.
        </p>
    </div>

    <div class="report-summary-grid">
        <div class="report-summary-box">
            <span class="report-summary-label">Total Students</span>
            <span class="report-summary-value"><?php echo $totalStudents; ?></span>
            <span class="report-summary-note">Registered in the system</span>
        </div>

        <div class="report-summary-box">
            <span class="report-summary-label">Total Supervisors</span>
            <span class="report-summary-value"><?php echo $totalSupervisors; ?></span>
            <span class="report-summary-note">Active supervisor accounts</span>
        </div>

        <div class="report-summary-box">
            <span class="report-summary-label">Assigned Students</span>
            <span class="report-summary-value"><?php echo $totalAssignedStudents; ?></span>
            <span class="report-summary-note"><?php echo $assignmentCoverage; ?>% assignment coverage</span>
        </div>

        <div class="report-summary-box">
            <span class="report-summary-label">Internship Records</span>
            <span class="report-summary-value"><?php echo $totalInternships; ?></span>
            <span class="report-summary-note"><?php echo $internshipCoverage; ?>% student coverage</span>
        </div>

        <div class="report-summary-box">
            <span class="report-summary-label">Logbooks Submitted</span>
            <span class="report-summary-value"><?php echo $totalLogbooks; ?></span>
            <span class="report-summary-note"><?php echo $logbookReviewRate; ?>% reviewed</span>
        </div>

        <div class="report-summary-box">
            <span class="report-summary-label">Reports Submitted</span>
            <span class="report-summary-value"><?php echo $totalReports; ?></span>
            <span class="report-summary-note"><?php echo $reportReviewRate; ?>% reviewed</span>
        </div>
    </div>

    <div class="report-summary-grid">
        <div class="report-summary-box">
            <span class="report-summary-label">Pending Logbook Reviews</span>
            <span class="report-summary-value"><?php echo $pendingLogbookReviews; ?></span>
            <span class="report-summary-note">Waiting for supervisor action</span>
        </div>

        <div class="report-summary-box">
            <span class="report-summary-label">Pending Report Reviews</span>
            <span class="report-summary-value"><?php echo $pendingReportReviews; ?></span>
            <span class="report-summary-note">Waiting for supervisor action</span>
        </div>

        <div class="report-summary-box">
            <span class="report-summary-label">Active Announcements</span>
            <span class="report-summary-value"><?php echo $totalAnnouncements; ?></span>
            <span class="report-summary-note">Visible to users on dashboards</span>
        </div>

        <div class="report-summary-box">
            <span class="report-summary-label">Assigned Rows</span>
            <span class="report-summary-value"><?php echo $totalAssignedRows; ?></span>
            <span class="report-summary-note">Assignment records in the system</span>
        </div>
    </div>

    <div class="card report-section">
        <h3>Students Without Supervisor Assignment</h3>
        <p class="report-note">These students do not yet have a record in the assignment table.</p>

        <div class="report-list">
            <?php if (empty($unassignedStudents)): ?>
                <p>No unassigned students found.</p>
            <?php else: ?>
                <?php foreach ($unassignedStudents as $student): ?>
                    <div class="report-item">
                        <div class="report-info">
                            <h4><?php echo htmlspecialchars($student['stuName']); ?></h4>
                            <p>
                                <?php echo htmlspecialchars($student['matricNo']); ?> ·
                                <?php echo htmlspecialchars($student['stuEmail']); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="card report-section">
        <h3>Students Without Internship Records</h3>
        <p class="report-note">These students have no internship details submitted yet.</p>

        <div class="report-list">
            <?php if (empty($studentsWithoutInternship)): ?>
                <p>All students have internship records.</p>
            <?php else: ?>
                <?php foreach ($studentsWithoutInternship as $student): ?>
                    <div class="report-item">
                        <div class="report-info">
                            <h4><?php echo htmlspecialchars($student['stuName']); ?></h4>
                            <p>
                                <?php echo htmlspecialchars($student['matricNo']); ?> ·
                                <?php echo htmlspecialchars($student['stuEmail']); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="report-section">
        <div class="card">
            <h3>Pending Logbook Reviews</h3>
            <p class="report-note">Most recent submissions that still need supervisor review.</p>

            <div class="report-list">
                <?php if (empty($pendingLogbooks)): ?>
                    <p>No pending logbooks found.</p>
                <?php else: ?>
                    <?php foreach ($pendingLogbooks as $item): ?>
                        <div class="report-item">
                            <div class="report-info">
                                <h4><?php echo htmlspecialchars($item['stuName']); ?></h4>
                                <p>
                                    <?php echo htmlspecialchars($item['matricNo']); ?> ·
                                    <?php echo htmlspecialchars($item['fileName']); ?>
                                </p>
                                <p>
                                    Uploaded on
                                    <?php echo !empty($item['submission_date']) ? date("d M Y", strtotime($item['submission_date'])) : 'N/A'; ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="report-section">
        <div class="card">
            <h3>Pending Report Reviews</h3>
            <p class="report-note">Most recent report submissions that still need supervisor review.</p>

            <div class="report-list">
                <?php if (empty($pendingReports)): ?>
                    <p>No pending reports found.</p>
                <?php else: ?>
                    <?php foreach ($pendingReports as $item): ?>
                        <div class="report-item">
                            <div class="report-info">
                                <h4><?php echo htmlspecialchars($item['stuName']); ?></h4>
                                <p>
                                    <?php echo htmlspecialchars($item['matricNo']); ?> ·
                                    <?php echo htmlspecialchars($item['fileName']); ?>
                                </p>
                                <p>
                                    Uploaded on
                                    <?php echo !empty($item['submission_date']) ? date("d M Y", strtotime($item['submission_date'])) : 'N/A'; ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php include 'footer.php'; ?>

</body>
</html>