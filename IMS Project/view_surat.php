<?php
session_start();
include 'db_connect.php';

if (!isset($_GET['id'])) {
    die("Invalid file.");
}

$suratID = (int)$_GET['id'];

$stmt = $conn->prepare("
    SELECT fileName, fileType, suratFile
    FROM surat
    WHERE suratID = ?
    LIMIT 1
");
$stmt->bind_param("i", $suratID);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    header("Content-Type: " . ($row['fileType'] ?: 'application/octet-stream'));
    header('Content-Disposition: inline; filename="' . $row['fileName'] . '"');
    echo $row['suratFile'];
    exit();
}

$stmt->close();
die("File not found.");