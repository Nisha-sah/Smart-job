<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | JobHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="login-page"> <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-briefcase"></i>
                <h2>Welcome Back!</h2>
                <p>Login to your JobHub account</p>
            </div>

            <form action="login_process.php" method="POST">
                <div class="input-group">
                    <label>Email Address</label>
                    <div class="input-field">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Enter your email" required>
                    </div>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>

                <div class="form-options">
                    <label><input type="checkbox"> Remember me</label>
                    <a href="#">Forgot Password?</a>
                </div>

                <button type="submit" name="login" class="login-btn">Login Now</button>
            </form>

            <div class="login-footer">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>

</body>
</html>