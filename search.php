<?php
header('Content-Type: application/json');
require 'db_connect.php';

$keyword = $_GET['keyword'] ?? '';
$loc = $_GET['location'] ?? '';
$cat = $_GET['category'] ?? '';
$type = $_GET['type'] ?? '';
$sal = $_GET['salary'] ?? 0;

$query = "SELECT * FROM jobs WHERE 1=1";
$params = [];

if($keyword) { $query .= " AND title LIKE ?"; $params[] = "%$keyword%"; }
if($loc) { $query .= " AND location = ?"; $params[] = $loc; }
if($cat) { $query .= " AND category = ?"; $params[] = $cat; }
if($type) { $query .= " AND job_type = ?"; $params[] = $type; }
if($sal) { $query .= " AND salary_max >= ?"; $params[] = $sal; }

$stmt = $pdo->prepare($query);
$stmt->execute($params);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>