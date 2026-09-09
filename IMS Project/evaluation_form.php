<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    header("Location: login.php");
    exit();
}

$matricNo = $_SESSION['matricNo'] ?? '';
if ($matricNo === '') {
    die("Student not logged in.");
}

$studentName = $_SESSION['user_name'] ?? '';
$studentEmail = $_SESSION['email'] ?? '';

$error = '';
$success = '';

/* Load student details */
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
    $studentName = $row['stuName'] ?? $studentName;
    $studentEmail = $row['stuEmail'] ?? $studentEmail;
}
$stmt->close();

/* Check if already submitted */
$existing = null;
$stmt = $conn->prepare("
    SELECT *
    FROM evaluation_form
    WHERE matricNo = ?
    LIMIT 1
");
$stmt->bind_param("s", $matricNo);
$stmt->execute();
$result = $stmt->get_result();
$existing = $result->fetch_assoc() ?: null;
$stmt->close();

/* Save form */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $q1 = (int)($_POST['q1'] ?? 0);
    $q2 = (int)($_POST['q2'] ?? 0);
    $q3 = (int)($_POST['q3'] ?? 0);
    $q4 = (int)($_POST['q4'] ?? 0);
    $q5 = (int)($_POST['q5'] ?? 0);
    $q6 = (int)($_POST['q6'] ?? 0);
    $q7 = (int)($_POST['q7'] ?? 0);
    $q8 = (int)($_POST['q8'] ?? 0);
    $q9 = (int)($_POST['q9'] ?? 0);
$q10 = (int)($_POST['q10'] ?? 0);

    
    $recommend_organization = trim($_POST['recommend_organization'] ?? '');
    $problems_encountered = trim($_POST['problems_encountered'] ?? '');
    $recommendations = trim($_POST['recommendations'] ?? '');

    if (
        $q1 < 1 || $q1 > 5 ||
        $q2 < 1 || $q2 > 5 ||
        $q3 < 1 || $q3 > 5 ||
        $q4 < 1 || $q4 > 5 ||
        $q5 < 1 || $q5 > 5 ||
        $q6 < 1 || $q6 > 5 ||
        $q7 < 1 || $q7 > 5 ||
        $q8 < 1 || $q8 > 5 ||
        $q9 < 1 || $q9 > 5 ||
        $q10 < 1 || $q10 > 5
    ) {
        $error = "Please answer all rating questions.";
    } elseif (
        
        !in_array($recommend_organization, ['Yes', 'No'], true)
    ) {
        $error = "Please complete all required fields.";
    } else {
        if ($existing) {

    $stmt = $conn->prepare("
        UPDATE evaluation_form
        SET
            name = ?,
            email = ?,
            q1 = ?,
            q2 = ?,
            q3 = ?,
            q4 = ?,
            q5 = ?,
            q6 = ?,
            q7 = ?,
            q8 = ?,
            q9 = ?,
            q10 = ?,
            recommend_organization = ?,
            problems_encountered = ?,
            recommendations = ?,
            submitted_at = NOW()
        WHERE matricNo = ?
    ");

    $stmt->bind_param(
        "ssiiiiiiiiiissss",
        $studentName,
        $studentEmail,
        $q1,
        $q2,
        $q3,
        $q4,
        $q5,
        $q6,
        $q7,
        $q8,
        $q9,
        $q10,
        $recommend_organization,
        $problems_encountered,
        $recommendations,
        $matricNo
    );

} else {

    $stmt = $conn->prepare("
        INSERT INTO evaluation_form
        (
            matricNo,
            name,
            email,
            q1,
            q2,
            q3,
            q4,
            q5,
            q6,
            q7,
            q8,
            q9,
            q10,
            recommend_organization,
            problems_encountered,
            recommendations
        )
        VALUES
        (
            ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?
        )
    ");

    $stmt->bind_param(
        "sssiiiiiiiiiiiss",
        $matricNo,
        $studentName,
        $studentEmail,
        $q1,
        $q2,
        $q3,
        $q4,
        $q5,
        $q6,
        $q7,
        $q8,
        $q9,
        $q10,
        $recommend_organization,
        $problems_encountered,
        $recommendations
    );
}

        if ($stmt->execute()) {
            $success = "Evaluation form saved successfully.";
            $stmt->close();

            $stmt = $conn->prepare("
                SELECT *
                FROM evaluation_form
                WHERE matricNo = ?
                LIMIT 1
            ");
            $stmt->bind_param("s", $matricNo);
            $stmt->execute();
            $result = $stmt->get_result();
            $existing = $result->fetch_assoc() ?: null;
            $stmt->close();
        } else {
            $error = "Failed to save evaluation form.";
            $stmt->close();
        }
    }
}

function valueOr($existing, $key, $default = '')
{
    return $existing[$key] ?? $default;
}

function checked($existing, $key, $value)
{
    return (isset($existing[$key]) && (string)$existing[$key] === (string)$value) ? 'checked' : '';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Evaluation Form</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .eval-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .eval-box {
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 16px;
        }

        .scale-row {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .scale-option {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .section-title {
            margin: 0 0 12px;
        }

        textarea.evaluation-text {
            width: 100%;
            min-height: 100px;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--bg-input);
            color: var(--text-main);
            resize: vertical;
            font-family: inherit;
        }

        @media (max-width: 800px) {
            .eval-grid {
                grid-template-columns: 1fr;
            }
        }

        .eval-box {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
}

.eval-box .form-group {
    margin-bottom: 24px;
}

.eval-box .form-group:last-child {
    margin-bottom: 0;
}

.eval-box label {
    display: block;
    margin-bottom: 10px;
    font-weight: 500;
}

.question-block > label {
    font-size: 1.05rem;
    font-weight: 600;
    line-height: 1.5;
    color: var(--text-main);
}

.scale-row {
    display: flex;
    gap: 18px;
    flex-wrap: wrap;
    margin-top: 10px;
}

.section-title {
    margin-bottom: 24px;
}

.evaluation-text {
    width: 100%;
    min-height: 120px;
    margin-top: 8px;
}

.question-block {
    padding-bottom: 20px;
    margin-bottom: 20px;
    border-bottom: 1px solid var(--border);
}

.question-block:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.question-block > label,
.feedback-question {
    font-size: 1.1rem;
    font-weight: 600;
    line-height: 1.5;
}   
    </style>
</head>
<body class="app-bg">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">

    <div class="card">
        <h2>Evaluation Form</h2>
        <p class="subtitle">BLI-07 | Evaluation Form (Students)</p>
        <p>
            Please complete this form for your practical training evaluation.
        </p>

        <?php if (!empty($error)): ?>
            <p style="color:#ef4444;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <p style="color:#22c55e;"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
    </div>

<div class="card" style="margin-top:20px; padding:30px;">
            <form method="POST" action="">

            <div class="eval-box">
                <h3 class="section-title">Student Information</h3>

                <div class="eval-grid">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($studentName); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label>Matric Number</label>
                        <input type="text" value="<?php echo htmlspecialchars($matricNo); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="text" value="<?php echo htmlspecialchars($studentEmail); ?>" readonly>
                    </div>
                </div>
            </div>

            <div class="eval-box">
                <h3 class="section-title">Part A - Evaluation Towards Organization</h3>

                <?php
                $questions = [
                    'q1' => 'Task given during training is related to my course.',
                    'q2' => 'Resources (hardware and software) have been made available by the organisation to help me finish my tasks.',
                    'q3' => 'Time frame given is adequate for completing task(s) or project.',
                    'q4' => 'The organization / organization supervisor is willing to guide or assist me while completing my task(s) or project.',
                    'q5' => 'My involvement in activities beyond the scope of my job is encouraged by the organisation. (sports / social etc.)',
                    'q6' => 'I have gained skills in Computer Science / Information Technology during training.',
                    'q7' => 'I have also gained other skills (leadership, communication, interpersonal) during training.',
                    'q8' => 'Overall, my experience at this organization has been the best.',
                    'q9' => 'Overall, practical training is a very challenging part of my studies.',
                    'q10' => 'I will recommend other students to apply for practical training at this company.'
                ];

                foreach ($questions as $key => $label):
                ?>
<div class="form-group question-block">
                            <label><?php echo htmlspecialchars($label); ?></label>
                        <div class="scale-row">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label class="scale-option">
                                    <input type="radio"
                                           name="<?php echo $key; ?>"
                                           value="<?php echo $i; ?>"
                                           required
                                           <?php echo checked($existing, $key, $i); ?>>
                                    <?php echo $i; ?>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="eval-box">
                <h3 class="section-title">Part B - Feedback</h3>


                

                <div class="eval-box">
                    <label class="feedback-question">Recommend this organization?</label>
                    <div class="scale-row">
                        <label class="scale-option">
                            <input type="radio" name="recommend_organization" value="Yes" required <?php echo checked($existing, 'recommend_organization', 'Yes'); ?>>
                            Yes
                        </label>
                        <label class="scale-option">
                            <input type="radio" name="recommend_organization" value="No" required <?php echo checked($existing, 'recommend_organization', 'No'); ?>>
                            No
                        </label>
                    </div>
                </div>

                <div class="eval-box">
                    <label class="feedback-question">Problems encountered during training (if any)</label>
                    <textarea name="problems_encountered" class="evaluation-text"><?php echo htmlspecialchars(valueOr($existing, 'problems_encountered')); ?></textarea>
                </div>

                <div class="eval-box">
                    <label class="feedback-question">Recommendation / Comments / Notes (if any)</label>
                    <textarea name="recommendations" class="evaluation-text"><?php echo htmlspecialchars(valueOr($existing, 'recommendations')); ?></textarea>
                </div>
            </div>

            <div class="profile-actions">
                <a href="dashboard.php" class="btn">Back</a>
                <button type="submit" class="btn submit">
                    <?php echo $existing ? 'Update' : 'Submit'; ?>
                </button>
            </div>

        </form>
    </div>

</div>

<?php include 'footer.php'; ?>

</body>
</html>