<?php

use App\Config;
use App\Auth;

require __DIR__ . '/../../vendor/autoload.php';
Config::init();

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    if (Auth::login($user, $pass)) {
        header("Location: index.php");
        exit;
    } else {
        $error = "Login fehlgeschlagen.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Admin Login</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">
<h1>Admin Login</h1>
<?php if (!empty($error)): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<form method="post">
  <div class="mb-3">
    <label for="username" class="form-label">Benutzername</label>
    <input type="text" class="form-control" id="username" name="username" required>
  </div>
  <div class="mb-3">
    <label for="password" class="form-label">Passwort</label>
    <input type="password" class="form-control" id="password" name="password" required>
  </div>
  <button type="submit" class="btn btn-primary">Login</button>
</form>
</body>
</html>
