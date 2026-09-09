<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'supervisor') {
    header("Location: login.php");
    exit();
}

$spMatric = $_SESSION['SPmatric'] ?? '';
if ($spMatric === '') {
    die("Supervisor not logged in.");
}

$userName = $_SESSION['user_name'] ?? 'Supervisor';
$userEmail = $_SESSION['email'] ?? '';
$profilePic = $_SESSION['user_image'] ?? 'images/pretty.png';

$error = '';
$success = '';

$selectedMatric = trim($_GET['matricNo'] ?? ($_POST['matricNo'] ?? ''));
$student = null;

/* LOAD SUPERVISOR DETAILS */
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

/* LOAD SELECTED ASSIGNED STUDENT */
if ($selectedMatric !== '') {
    $stmt = $conn->prepare("
        SELECT s.matricNo, s.stuName, s.stuEmail
        FROM assigned a
        INNER JOIN student s ON s.matricNo = a.matricNo
        WHERE a.SPmatric = ?
          AND s.matricNo = ?
        LIMIT 1
    ");
    $stmt->bind_param("ss", $spMatric, $selectedMatric);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc() ?: null;
    $stmt->close();
}

if (!$student) {
    die("Invalid or unassigned student selected.");
}

/* SAVE FORM */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $monitorType = trim($_POST['monitorType'] ?? '');
    $monitorDate = trim($_POST['monitorDate'] ?? '');

    $q1 = (int)($_POST['q1'] ?? 0);
    $q2 = (int)($_POST['q2'] ?? 0);
    $q3 = (int)($_POST['q3'] ?? 0);
    $q4 = (int)($_POST['q4'] ?? 0);
    $q5 = (int)($_POST['q5'] ?? 0);
    $q6 = (int)($_POST['q6'] ?? 0);
    $q7 = (int)($_POST['q7'] ?? 0);
    $q8 = (int)($_POST['q8'] ?? 0);

    $improvementComments = trim($_POST['improvementComments'] ?? '');

    if ($monitorType === '' || $monitorDate === '') {
        $error = "Please complete all required fields.";
    } elseif (
        $q1 < 1 || $q1 > 5 ||
        $q2 < 1 || $q2 > 5 ||
        $q3 < 1 || $q3 > 5 ||
        $q4 < 1 || $q4 > 5 ||
        $q5 < 1 || $q5 > 5 ||
        $q6 < 1 || $q6 > 5 ||
        $q7 < 1 || $q7 > 5 ||
        $q8 < 1 || $q8 > 5
    ) {
        $error = "Please answer all rating questions.";
    } elseif ($improvementComments === '') {
        $error = "Please fill in the academic supervisor comments.";
    } else {
        $attachmentName = null;
        $attachmentPath = null;

        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
            $originalName = $_FILES['attachment']['name'];
            $tmpName = $_FILES['attachment']['tmp_name'];
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExtensions, true)) {
                $error = "Only PDF, JPG, PNG, DOC and DOCX files are allowed.";
            } else {
                $uploadDir = __DIR__ . '/uploads/monitoring/';

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $safeName = 'monitor_' . time() . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $originalName);
                $targetPath = $uploadDir . $safeName;

                if (move_uploaded_file($tmpName, $targetPath)) {
                    $attachmentName = $originalName;
                    $attachmentPath = 'uploads/monitoring/' . $safeName;
                } else {
                    $error = "Failed to upload the additional document.";
                }
            }
        }

        if ($error === '') {
            $stmt = $conn->prepare("
                INSERT INTO monitoring_form
                (SPmatric, matricNo, monitorType, monitorDate,
                 q1, q2, q3, q4, q5, q6, q7, q8,
                 improvementComments, attachmentName, attachmentPath)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "ssssiiiiiiiisss",
                $spMatric,
                $selectedMatric,
                $monitorType,
                $monitorDate,
                $q1,
                $q2,
                $q3,
                $q4,
                $q5,
                $q6,
                $q7,
                $q8,
                $improvementComments,
                $attachmentName,
                $attachmentPath
            );

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: monitoring_form.php?matricNo=" . urlencode($selectedMatric) . "&saved=1");
                exit();
            } else {
                $error = "Failed to save monitoring form.";
                $stmt->close();
            }
        }
    }
}

if (isset($_GET['saved'])) {
    $success = "Monitoring form saved successfully.";
}

function checkedValue($current, $value): string
{
    return ((string)$current === (string)$value) ? 'checked' : '';
}

function selectedValue($current, $value): string
{
    return ((string)$current === (string)$value) ? 'selected' : '';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>BLI-06 Monitoring Form</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-section {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            margin-top: 20px;
        }

        .form-section h3 {
            margin-top: 0;
            margin-bottom: 18px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .question-block {
            padding-bottom: 18px;
            margin-bottom: 18px;
            border-bottom: 1px solid var(--border);
        }

        .question-block:last-child {
            padding-bottom: 0;
            margin-bottom: 0;
            border-bottom: none;
        }

        .question-block > label,
        .feedback-question {
            display: block;
            margin-bottom: 10px;
            font-size: 1.08rem;
            font-weight: 600;
            line-height: 1.5;
        }

        .scale-row {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .scale-option {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
        }

        textarea.monitor-textarea {
            width: 100%;
            min-height: 120px;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--bg-input);
            color: var(--text-main);
            resize: vertical;
            font-family: inherit;
        }

        .attachment-note {
            color: var(--text-muted);
            font-size: 13px;
            margin-top: 6px;
        }

        .select-wrapper {
            position: relative;
            width: 100%;
        }

        .styled-select {
            width: 100%;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            padding: 14px 45px 14px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--bg-input);
            color: var(--text-main);
            
            outline: none;
            transition: 0.2s ease;
        }

        .select-wrapper::after {
            content: "▾";
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
            font-size: 14px;
        }

        .styled-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(124, 58, 237, 0.15);
        }

        @media (max-width: 800px) {
            .form-grid {
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
        <div class="profile-header">
            <img src="<?php echo htmlspecialchars($profilePic); ?>" alt="Profile Picture" class="profile-pic">
            <div class="welcome-text">
                <h2>BLI-06 Monitoring Form</h2>
                <p>Lawatan / Pemantauan Pelajar</p>
            </div>
        </div>

        <?php if (!empty($success)): ?>
            <p style="color:#22c55e; margin-top:16px;"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <p style="color:#ef4444; margin-top:16px;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
    </div>

    <div class="form-section">
        <form method="POST" enctype="multipart/form-data">

            <h3>Bahagian A: Maklumat Pelajar / Maklumat Lawatan</h3>

            <div class="form-grid">
                <div class="form-group">
                    <label>Nama Penyelia</label>
                    <input type="text" value="<?php echo htmlspecialchars($userName); ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Nama Pelajar</label>
                    <input type="text" value="<?php echo htmlspecialchars($student['stuName']); ?>" readonly>
                    <input type="hidden" name="matricNo" value="<?php echo htmlspecialchars($student['matricNo']); ?>">
                </div>

                <div class="form-group">
                    <label>Jenis Pemantauan</label>
                    <div class="select-wrapper">
                        <select name="monitorType" required class="styled-select">
                            <option value="">-- Pilih Jenis --</option>
                            <option value="Panggilan Telefon" <?php echo selectedValue($_POST['monitorType'] ?? '', 'Panggilan Telefon'); ?>>Panggilan Telefon</option>
                            <option value="Lawatan Tapak" <?php echo selectedValue($_POST['monitorType'] ?? '', 'Lawatan Tapak'); ?>>Lawatan Tapak</option>
                            <option value="Mesyuarat Dalam Talian" <?php echo selectedValue($_POST['monitorType'] ?? '', 'Mesyuarat Dalam Talian'); ?>>Mesyuarat Dalam Talian</option>
                            <option value="E-mel" <?php echo selectedValue($_POST['monitorType'] ?? '', 'E-mel'); ?>>E-mel</option>
                            <option value="Lain-lain" <?php echo selectedValue($_POST['monitorType'] ?? '', 'Lain-lain'); ?>>Lain-lain</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Tarikh Pemantauan</label>
                    <input type="date" name="monitorDate" value="<?php echo htmlspecialchars($_POST['monitorDate'] ?? date('Y-m-d')); ?>" required>
                </div>
            </div>

            <div class="form-section" style="margin-top:24px;">
                <h3>Bahagian C: Penilaian</h3>

                <?php
                $questions = [
                    'q1' => 'Kesesuaian tugasan yang diberikan',
                    'q2' => 'Bimbingan penyelia organisasi',
                    'q3' => 'Kerjasama staf organisasi',
                    'q4' => 'Keselesaan ruang yang disediakan',
                    'q5' => 'Kemudahan perkakasan komputer dan perisian yang disediakan',
                    'q6' => 'Persekitaran lokasi organisasi (kemudahan awam, keselamatan dan lain-lain)',
                    'q7' => 'Kesesuaian tempat latihan',
                    'q8' => 'Tahap tanggungjawab yang diberikan oleh organisasi'
                ];

                foreach ($questions as $key => $label):
                    $currentValue = $_POST[$key] ?? '';
                ?>
                    <div class="question-block">
                        <label><?php echo htmlspecialchars($label); ?> *</label>
                        <div class="scale-row">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label class="scale-option">
                                    <input type="radio" name="<?php echo $key; ?>" value="<?php echo $i; ?>" required <?php echo checkedValue($currentValue, $i); ?>>
                                    <?php echo $i; ?>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="form-section" style="margin-top:24px;">
                <h3>Bahagian D: Penambahbaikan / Komen</h3>

                <div class="question-block">
                    <label class="feedback-question">Cadangan Penambahbaikan / Komen Penyelia Akademik *</label>
                    <textarea name="improvementComments" class="monitor-textarea" required><?php echo htmlspecialchars($_POST['improvementComments'] ?? ''); ?></textarea>
                </div>

                <div class="question-block">
                    <label class="feedback-question">Dokumen Tambahan (sekiranya perlu)</label>
                    <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                    <div class="attachment-note">Muat naik sebarang bahan bukti jika diperlukan.</div>
                </div>
            </div>

            <div class="profile-actions" style="margin-top:24px;">
                <a href="dashboard.php" class="btn">Back</a>
                <button type="submit" class="btn submit">Submit</button>
            </div>

        </form>
    </div>

</div>

<?php include 'footer.php'; ?>

</body>
</html>