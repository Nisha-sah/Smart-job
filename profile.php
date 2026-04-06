<?php
session_start();
require_once '../config/config.php'; 

// Safety Check: Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    // Path: Go up one level to root, then into login folder
    header("Location: ../login/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch latest user data from database
$query = "SELECT * FROM users WHERE id = '$user_id'";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "User not found.";
    exit();
}

$user = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | JobHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="register-page">

    <div class="register-wrapper">
        <div class="register-side-image" style="background: linear-gradient(rgba(32, 178, 170, 0.9), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1499750310107-5fef28a66643?auto=format&fit=crop&w=800&q=80');">
            <div class="overlay-text">
                <h2>Hello, <?php echo htmlspecialchars(explode(' ', trim($user['fullname']))[0]); ?>!</h2>
                <p>Keep your profile updated to get noticed by the best opportunities.</p>
            </div>
        </div>

        <div class="register-card">
            <div class="register-header">
                <h2>User Profile</h2>
                <p>Manage your account settings</p>
            </div>

            <form action="update_profile.php" method="POST">
                
                <div class="input-group">
                    <label>Full Name</label>
                    <div class="input-field">
                        <i class="fas fa-user"></i>
                        <input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                    </div>
                </div>

                <div class="input-group">
                    <label>Email Address</label>
                    <div class="input-field">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                </div>

                <div class="input-group">
                    <label>Your Role</label>
                    <div class="user-type-selector">
                        <input type="radio" name="user_type" value="candidate" id="cand" <?php echo ($user['user_type'] == 'candidate') ? 'checked' : ''; ?>>
                        <label for="cand">Candidate</label>
                        
                        <input type="radio" name="user_type" value="employer" id="emp" <?php echo ($user['user_type'] == 'employer') ? 'checked' : ''; ?>>
                        <label for="emp">Employer</label>
                    </div>
                </div>

                <div class="input-group">
                    <label>Skills (comma separated)</label>
                    <div class="input-field">
                        <i class="fas fa-code"></i>
                        <input type="text" name="skills" placeholder="e.g. PHP, JavaScript, SQL" value="<?php echo htmlspecialchars($user['skills'] ?? ''); ?>">
                    </div>
                </div>

                <button type="submit" name="update_profile" class="register-btn">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                
                <div class="register-footer">
                    <a href="../index.php" style="text-decoration: none; color: #666;">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </form>
        </div>
    </div>

</body>
</html>