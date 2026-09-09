<!DOCTYPE html>
<html>
<head>
    <title>Add Logbook</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="app-bg">

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main">
    
    <div class="card logbook-form-card">

        <div class="form-header">
            <h2>Add Logbook</h2>
            <p><?php echo date("l, d M Y"); ?></p>
        </div>

        <form method="POST">

            <div class="form-group">
                <label>Activities</label>
                <textarea name="activities" rows="5" placeholder="What did you do today?" required></textarea>
            </div>

            <div class="form-group">
                <label>Remarks</label>
                <textarea name="remarks" rows="3" placeholder="Optional notes"></textarea>
            </div>

            <div class="form-actions">
                <a href="logbook.php" class="btn">Cancel</a>
                <button type="submit" class="btn submit">Save Logbook</button>
            </div>

        </form>

    </div>

</div>

<?php include 'footer.php'; ?>

</body>
</html>