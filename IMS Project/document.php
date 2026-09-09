<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['matricNo'])) {
    die("Student not logged in.");
}

$matricNo = $_SESSION['matricNo'];

/* DELETE REPORT */
if (isset($_GET['delete'])) {

    $report_id = (int)$_GET['delete'];

    $delete = $conn->prepare("
        DELETE FROM report
        WHERE reportID = ? AND matricNo = ?
    ");

    $delete->bind_param("is", $report_id, $matricNo);
    $delete->execute();

    header("Location: document.php");
    exit();
}

/* LOAD REPORTS */
$reports = [];

$sql = "
    SELECT reportID,
       fileName,
       submission_date,
       reportStatus,
       reviewStatus,
       reviewFeedback,
       reviewedAt
FROM report
    WHERE matricNo = ?
    ORDER BY submission_date DESC, reportID DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $matricNo);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reports</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="app-bg">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">

    <div class="report-top">
        <a href="upload_doc.php" class="btn submit">
            + Upload Report
        </a>
    </div>

    <div class="card report-list">

        <?php if (empty($reports)): ?>

<div class="empty-state">
    <div class="empty-icon">📄</div>

    <h3>No Reports Uploaded Yet</h3>

    <p>
        You haven't uploaded any reports yet.
    </p>

</div>

<?php else: ?>

            <?php foreach ($reports as $report): ?>

                <div class="report-item">

                    <div class="report-info">

                        <h4>
                            <?php echo htmlspecialchars($report['fileName']); ?>
                        </h4>

                        <p>
                            Uploaded on
                            <?php
                            echo !empty($report['submission_date'])
                                ? date(
                                    "d M Y",
                                    strtotime($report['submission_date'])
                                  )
                                : "N/A";
                            ?>
                        </p>

                        <div class="report-review-box" style="margin-top:10px;">

        <p class="mini-sub">
            Status:
            <strong>
                <?php echo htmlspecialchars($report['reviewStatus'] ?? 'Pending'); ?>
            </strong>
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

                    </div>

                    <div class="report-actions">

                        <a href="view_report.php?id=<?php echo $report['reportID']; ?>"
                           class="btn view small"
                           target="_blank">
                            View
                        </a>

                        <div class="menu-container">

                            <button type="button" class="menu-btn">
                                ⋮
                            </button>

                            <div class="menu-dropdown">

                                <a href="document.php?delete=<?php echo $report['reportID']; ?>"
                                   class="delete"
                                   onclick="return confirm('Delete this report?');">
                                    Delete
                                </a>

                            </div>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</div>

<?php include 'footer.php'; ?>

<script>
document.querySelectorAll(".menu-btn").forEach(btn => {
    btn.addEventListener("click", (e) => {
        e.stopPropagation();

        document.querySelectorAll(".menu-dropdown").forEach(menu => {
            if (menu !== btn.nextElementSibling) {
                menu.style.display = "none";
            }
        });

        const dropdown = btn.nextElementSibling;

        dropdown.style.display =
            dropdown.style.display === "block"
                ? "none"
                : "block";
    });
});

document.addEventListener("click", () => {
    document.querySelectorAll(".menu-dropdown").forEach(menu => {
        menu.style.display = "none";
    });
});
</script>

</body>
</html>