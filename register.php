<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | JobHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="register-page">

    <div class="register-wrapper">
        <div class="register-side-image">
            <div class="overlay-text">
                <h2>Join JobHub</h2>
                <p>Create an account to find your dream job or the perfect candidate.</p>
            </div>
        </div>
        
        <div class="register-card">
            <div class="register-header">
                <h2>Create Account</h2>
                <p>It's free and always will be.</p>
            </div>

            <form action="register_process.php" method="POST" onsubmit="return validateRegisterForm()">
                
                <div class="input-group">
                    <label for="fullname">Full Name</label>
                    <div class="input-field">
                        <i class="fas fa-user"></i>
                        <input type="text" id="fullname" name="fullname" 
                               placeholder="John Doe" required 
                               pattern="^[A-Za-z\s]{3,50}$" 
                               title="Name should be 3-50 characters and contain only letters.">
                    </div>
                </div>

                <div class="input-group">
                    <label for="email">Email Address</label>
                    <div class="input-field">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" 
                               placeholder="example@mail.com" required>
                    </div>
                </div>

                <div class="input-group">
                    <label>I am a:</label>
                    <div class="user-type-selector">
                        <input type="radio" name="user_type" value="candidate" id="candidate" checked>
                        <label for="candidate">Candidate</label>
                        
                        <input type="radio" name="user_type" value="employer" id="employer">
                        <label for="employer">Employer</label>
                    </div>
                </div>

                <div class="input-group">
                    <label for="password">Password</label>
                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" 
                               placeholder="Min. 8 characters" 
                               minlength="8" required>
                    </div>
                </div>

                <button type="submit" name="register" class="register-btn">
                    Create Account
                </button>
            </form>

            <div class="register-footer">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>

    <script>
    function validateRegisterForm() {
        const name = document.getElementById('fullname').value.trim();
        const password = document.getElementById('password').value;
        const email = document.getElementById('email').value.trim();

        if (name.length < 3) {
            alert("Please enter a valid full name (minimum 3 characters).");
            return false;
        }

        if (password.length < 8) {
            alert("Password must be at least 8 characters long.");
            return false;
        }

        // Simple Email Regex check
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            alert("Please enter a valid email address.");
            return false;
        }

        return true;
    }
    </script>

</body>
</html>