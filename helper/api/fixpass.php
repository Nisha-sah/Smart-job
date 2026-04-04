<?php
require_once __DIR__ . '/../../backend/config/database.php';

$newHash = password_hash('secret123', PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt->execute([$newHash, 'np03cs4a240185@heraldcollege.edu.np']);

echo "Done! Hash saved: " . $newHash;