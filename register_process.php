<?php
session_start(); // Session start garnu parchha login status rakhna
require_once '../config/config.php'; 

if (isset($_POST['register'])) {
    $fullname  = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email     = mysqli_real_escape_string($conn, $_POST['email']);
    $user_type = mysqli_real_escape_string($conn, $_POST['user_type']); 
    $password  = $_POST['password'];

    // 1. Password Hashing
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 2. Email Check (Duplicate rokhna)
    $check_email = "SELECT email FROM users WHERE email = '$email'";
    $run_check = mysqli_query($conn, $check_email);

    if (mysqli_num_rows($run_check) > 0) {
        echo "<script>alert('Email already registered!'); window.history.back();</script>";
        exit();
    } else {
        // 3. Insert User
        $insert_sql = "INSERT INTO users (fullname, email, password, user_type) 
                       VALUES ('$fullname', '$email', '$hashed_password', '$user_type')";
        
        if (mysqli_query($conn, $insert_sql)) {
            // 4. AUTO-LOGIN: Naya baneko User ko ID nikalne
            $new_user_id = mysqli_insert_id($conn); 

            // 5. SESSION set garne (Jasle गर्दा profile.php ले user चिन्छ)
            $_SESSION['user_id'] = $new_user_id;
            $_SESSION['fullname'] = $fullname;
            $_SESSION['user_type'] = $user_type;

            // 6. Direct Profile Page ma pathaune
            echo "<script>alert('Registration Successful! Welcome to your profile.'); window.location.href='profile.php';</script>";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
}
?>