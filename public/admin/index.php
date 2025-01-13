<?php

use App\Auth;

require __DIR__ . '/../../vendor/autoload.php';

session_start();

if (!Auth::isLoggedIn()) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">
<h1>Dashboard</h1>
<ul>
  <li><a href="manage.php">Abonnenten verwalten</a></li>
  <li><a href="logout.php" class="text-danger">Logout</a></li>
</ul>
</body>
</html>
