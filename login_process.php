<?php
session_start(); 
require_once '../config/config.php';

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // 1. Check if the email exists
    $query = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        // 2. Verify the hashed password
        if (password_verify($password, $user['password'])) {
            
            // 3. Set Session Data
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['user_type'] = $user['user_type'];

            // 4. Correct Redirect: Exit login folder, enter profile folder
            header("Location: ../profile/profile.php"); 
            exit(); 

        } else {
            echo "<script>alert('Invalid password. Please try again.'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('No account found with that email.'); window.history.back();</script>";
    }
}
?>