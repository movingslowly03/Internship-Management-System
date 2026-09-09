<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$monitorID = (int)($_GET['id'] ?? 0);
if ($monitorID <= 0) {
    die("Invalid monitoring form.");
}

$form = null;

$stmt = $conn->prepare("
    SELECT mf.*, s.stuName, s.stuEmail, sup.SPname, sup.SPgmail, sup.SPphNO
    FROM monitoring_form mf
    INNER JOIN student s ON s.matricNo = mf.matricNo
    LEFT JOIN supervisor sup ON sup.SPmatric = mf.SPmatric
    WHERE mf.monitorID = ?
    LIMIT 1
");
$stmt->bind_param("i", $monitorID);
$stmt->execute();
$result = $stmt->get_result();
$form = $result->fetch_assoc();
$stmt->close();

if (!$form) {
    die("Monitoring form not found.");
}

$questions = [
    'q1' => 'Kesesuaian tugasan yang diberikan',
    'q2' => 'Bimbingan penyelia organisasi',
    'q3' => 'Kerjasama staf organisasi',
    'q4' => 'Keselesaan ruang yang disediakan',
    'q5' => 'Kemudahan perkakasan komputer dan perisian yang disediakan',
    'q6' => 'Persekitaran lokasi organisasi',
    'q7' => 'Kesesuaian tempat latihan',
    'q8' => 'Tahap tanggungjawab yang diberikan oleh organisasi'
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Monitoring Form</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .detail-box {
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 16px;
        }

        .question-item {
            padding: 16px 0;
            border-bottom: 1px solid var(--border);
        }

        .question-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .rating-pill {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(124, 58, 237, 0.15);
            border: 1px solid rgba(124, 58, 237, 0.25);
            color: var(--text-main);
            font-weight: 600;
            font-size: 13px;
        }

        @media (max-width: 800px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="app-bg">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">

    <div class="card">
        <h2>Monitoring Form Details</h2>
        <p class="subtitle">Submitted for <?php echo htmlspecialchars($form['stuName']); ?> (<?php echo htmlspecialchars($form['matricNo']); ?>)</p>

        <div class="detail-grid" style="margin-top:20px;">
            <div class="detail-box">
                <span class="summary-label">Supervisor</span>
                <div class="summary-value"><?php echo htmlspecialchars($form['SPname'] ?? 'N/A'); ?></div>
            </div>

            <div class="detail-box">
                <span class="summary-label">Student</span>
                <div class="summary-value"><?php echo htmlspecialchars($form['stuName']); ?></div>
            </div>

            <div class="detail-box">
                <span class="summary-label">Monitoring Type</span>
                <div class="summary-value"><?php echo htmlspecialchars($form['monitorType']); ?></div>
            </div>

            <div class="detail-box">
                <span class="summary-label">Monitoring Date</span>
                <div class="summary-value">
                    <?php echo !empty($form['monitorDate']) ? date("d M Y", strtotime($form['monitorDate'])) : "N/A"; ?>
                </div>
            </div>

            <div class="detail-box">
                <span class="summary-label">Submitted At</span>
                <div class="summary-value">
                    <?php echo !empty($form['submittedAt']) ? date("d M Y, h:i A", strtotime($form['submittedAt'])) : "N/A"; ?>
                </div>
            </div>

            <div class="detail-box">
                <span class="summary-label">Attachment</span>
                <div class="summary-value">
                    <?php if (!empty($form['attachmentPath'])): ?>
                        <a href="<?php echo htmlspecialchars($form['attachmentPath']); ?>" target="_blank">Download Attachment</a>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:20px;">
        <h3>Bahagian C: Penilaian</h3>

        <?php foreach ($questions as $key => $label): ?>
            <div class="question-item">
                <p style="margin:0 0 8px;"><strong><?php echo htmlspecialchars($label); ?></strong></p>
                <span class="rating-pill"><?php echo (int)($form[$key] ?? 0); ?> / 5</span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card" style="margin-top:20px;">
        <h3>Bahagian D: Penambahbaikan / Komen</h3>

        <div class="question-item">
            <p><strong>Cadangan Penambahbaikan / Komen Penyelia Akademik</strong></p>
            <p><?php echo nl2br(htmlspecialchars($form['improvementComments'] ?? '')); ?></p>
        </div>
    </div>

    <div class="card" style="margin-top:20px;">
        <a href="admin_monitoring_forms.php" class="btn">Back</a>
    </div>

</div>

<?php include 'footer.php'; ?>
</body>
</html>