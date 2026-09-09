<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$evalID = (int)($_GET['id'] ?? 0);
if ($evalID <= 0) {
    die("Invalid evaluation form.");
}

$form = null;

$stmt = $conn->prepare("
    SELECT *
    FROM evaluation_form
    WHERE evalID = ?
    LIMIT 1
");
$stmt->bind_param("i", $evalID);
$stmt->execute();
$result = $stmt->get_result();
$form = $result->fetch_assoc();
$stmt->close();

if (!$form) {
    die("Evaluation form not found.");
}

$questions = [
    'q1' => 'Task given during training is related to my course.',
    'q2' => 'Resources (hardware and software) have been made available by the organisation to help me finish my tasks.',
    'q3' => 'Time frame given is adequate for completing task(s) or project.',
    'q4' => 'The organization / organization supervisor is willing to guide or assist me while completing my task(s) or project.',
    'q5' => 'My involvement in activities beyond the scope of my job is encouraged by the organisation.',
    'q6' => 'I have gained skills in Computer Science / Information Technology during training.',
    'q7' => 'I have also gained other skills during training.',
    'q8' => 'Overall, my experience at this organization has been the best.',
    'q9' => 'Overall, practical training is a very challenging part of my studies.',
    'q10' => 'I will recommend other students to apply for practical training at this company.'
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Evaluation Form</title>
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
        <h2>Evaluation Form Details</h2>
        <p class="subtitle">Submitted by <?php echo htmlspecialchars($form['name']); ?> (<?php echo htmlspecialchars($form['matricNo']); ?>)</p>

        <div class="detail-grid" style="margin-top:20px;">
            <div class="detail-box">
                <span class="summary-label">Name</span>
                <div class="summary-value"><?php echo htmlspecialchars($form['name']); ?></div>
            </div>

            <div class="detail-box">
                <span class="summary-label">Matric No</span>
                <div class="summary-value"><?php echo htmlspecialchars($form['matricNo']); ?></div>
            </div>

            <div class="detail-box">
                <span class="summary-label">Email</span>
                <div class="summary-value"><?php echo htmlspecialchars($form['email']); ?></div>
            </div>

            <div class="detail-box">
                <span class="summary-label">Submitted At</span>
                <div class="summary-value">
                    <?php echo !empty($form['submitted_at']) ? date("d M Y, h:i A", strtotime($form['submitted_at'])) : "N/A"; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:20px;">
        <h3>Part A - Evaluation Towards Organization</h3>

        <?php foreach ($questions as $key => $label): ?>
            <div class="question-item">
                <p style="margin:0 0 8px;"><strong><?php echo htmlspecialchars($label); ?></strong></p>
                <?php
$score = (int)($form[$key] ?? 0);

$label = match($score) {
    1 => 'Strongly Disagree',
    2 => 'Disagree',
    3 => 'Neutral',
    4 => 'Agree',
    5 => 'Strongly Agree',
    default => 'N/A'
};
?>

<span class="rating-pill">
    <?php echo $score; ?>/5 - <?php echo $label; ?>
</span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card" style="margin-top:20px;">
        <h3>Part B - Feedback</h3>

        <div class="question-item">
            <p><strong>Recommend this Organization?</strong></p>
            <p><?php echo htmlspecialchars($form['recommend_organization'] ?? ''); ?></p>
        </div>

        <div class="question-item">
            <p><strong>Problems Encountered</strong></p>
            <p><?php echo nl2br(htmlspecialchars($form['problems_encountered'] ?? '')); ?></p>
        </div>

        <div class="question-item">
            <p><strong>Recommendation / Comments / Notes</strong></p>
            <p><?php echo nl2br(htmlspecialchars($form['recommendations'] ?? '')); ?></p>
        </div>
    </div>

    <div class="card" style="margin-top:20px;">
        <a href="admin_evaluation_forms.php" class="btn">Back</a>
    </div>

</div>

<?php include 'footer.php'; ?>
</body>
</html>