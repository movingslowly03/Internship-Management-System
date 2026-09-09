<div class="sidebar">
    <ul>

        <?php if ($_SESSION['user_type'] === 'student'): ?>

            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="internship.php">Internship Details</a></li>
            <li><a href="surats.php">Download Forms</a></li>
            <li><a href="logbook.php">Submit Logbook</a></li>
            <li><a href="document.php">Submit Report</a></li>
            <li><a href="evaluation_form.php">BLI-07 Evaluation Form</a></li>


        <?php elseif ($_SESSION['user_type'] === 'admin'): ?>

            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="admin_profile.php">Admin Profile</a></li>
            <li><a href="announcement.php">Announcements</a></li>
            <li><a href="studentList.php">Manage Students</a></li>
            <li><a href="supervisorList.php">Manage Supervisors</a></li>
            <li><a href="surats.php">Manage Forms</a></li>
            
<li><a href="admin_monitoring_forms.php">BLI-06 Monitoring Forms</a></li>
<li><a href="admin_evaluation_forms.php">BLI-07 Evaluation Forms</a></li>
<li>
    <a href="admin_bli08_evaluations.php">
        BLI-08 Evaluations
    </a>
</li>
            <li><a href="generate_report.php">Generate Report</a></li>
            

        <?php elseif ($_SESSION['user_type'] === 'supervisor'): ?>

            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="SV_profile.php">Profile</a></li>
            <li><a href="my_students.php">My Students</a></li>
            <li><a href="review_logbooks.php">Review Logbooks</a></li>
<li><a href="review_reports.php">Review Reports</a></li>
            

            <li><a href="surats.php">Download Forms</a></li>
            

        <?php endif; ?>

    </ul>
</div>