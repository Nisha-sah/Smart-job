<?php
session_start();
require_once '../config/config.php';

if (isset($_POST['update_profile']) && isset($_SESSION['user_id'])) {
    
    $user_id   = $_SESSION['user_id'];
    $fullname  = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email     = mysqli_real_escape_string($conn, $_POST['email']);
    $user_type = mysqli_real_escape_string($conn, $_POST['user_type']);
    $skills    = mysqli_real_escape_string($conn, $_POST['skills']);

    // Validation: Check if email is already taken by ANOTHER user
    $email_check = "SELECT id FROM users WHERE email = '$email' AND id != '$user_id'";
    $res = mysqli_query($conn, $email_check);

    if (mysqli_num_rows($res) > 0) {
        echo "<script>alert('This email is already used by another account.'); window.history.back();</script>";
        exit();
    }

    // Perform Update
    $update_query = "UPDATE users SET 
                     fullname = '$fullname', 
                     email = '$email', 
                     user_type = '$user_type', 
                     skills = '$skills' 
                     WHERE id = '$user_id'";

    if (mysqli_query($conn, $update_query)) {
        // Update session name in case it changed
        $_SESSION['fullname'] = $fullname;
        
        echo "<script>alert('Profile updated successfully!'); window.location.href='profile.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    header("Location: login.php");
    exit();
}
?>