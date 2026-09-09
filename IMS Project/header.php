<div class="header">
    <div class="logo-section">
        <img src="images/LogoUiTM.png" alt="UiTM Logo">
        <a href="home.php" class="system-name">Internship Management System</a>
    </div>

    <div class="nav">
        <?php
        if (isset($_SESSION['user_type'])) {
            echo '
                <div class="profile-menu">
                    <a href="logout.php" class="signup-btn">Log out</a>
                </div>
            ';
        } else {
            $currentPage = basename($_SERVER['PHP_SELF']);

            if ($currentPage == "login.php") {
                echo '<a href="register.php" class="signup-btn">Sign Up</a>';
            } elseif ($currentPage == "register.php") {
                echo '<a href="login.php" class="signup-btn">Login</a>';
            } elseif ($currentPage == "index.php" || $currentPage == "home.php") {
                
            } else {
                echo '
                    <a href="login.php" class="signup-btn">Login</a>
                    <a href="register.php" class="signup-btn">Sign Up</a>
                ';
            }
        }
        ?>
    </div>
</div>