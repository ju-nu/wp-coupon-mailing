<?php

use App\Auth;
use App\Config;
use App\Database;

require __DIR__ . '/../../vendor/autoload.php';

session_start();
if (!Auth::isLoggedIn()) {
    header("Location: login.php");
    exit;
}

Config::init();
$pdo = Database::connect();

// Handle add form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_email'], $_POST['new_brand'])) {
    $email = filter_var($_POST['new_email'], FILTER_VALIDATE_EMAIL);
    $brand = filter_var($_POST['new_brand'], FILTER_SANITIZE_STRING);
    if ($email && $brand) {
        $token = bin2hex(random_bytes(8));
        $utoken = bin2hex(random_bytes(8));
        $stmt = $pdo->prepare("
            INSERT INTO newsletter_subscriptions
            (email, brand_slug, status, confirm_token, unsubscribe_token, created_at, confirmed_at)
            VALUES (:email, :brand, 'active', :ct, :ut, NOW(), NOW())
        ");
        $stmt->execute([
            ':email' => $email,
            ':brand' => $brand,
            ':ct'    => $token,
            ':ut'    => $utoken
        ]);
    }
    header("Location: manage.php");
    exit;
}

// Handle remove action
if (isset($_GET['remove_id'])) {
    $id = (int)$_GET['remove_id'];
    $pdo->prepare("DELETE FROM newsletter_subscriptions WHERE id=:id")->execute([':id' => $id]);
    header("Location: manage.php");
    exit;
}

$subs = $pdo->query("SELECT * FROM newsletter_subscriptions ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Abonnenten verwalten</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">
<h1>Abonnenten verwalten</h1>
<a href="index.php">← Zurück zum Dashboard</a>

<hr>
<h2>Neuen Abonnenten hinzufügen</h2>
<form method="post" class="row g-3">
  <div class="col-md-4">
    <label for="new_email" class="form-label">E-Mail</label>
    <input type="email" class="form-control" name="new_email" id="new_email" required>
  </div>
  <div class="col-md-4">
    <label for="new_brand" class="form-label">Brand</label>
    <input type="text" class="form-control" name="new_brand" id="new_brand" required>
  </div>
  <div class="col-md-4" style="margin-top: 2rem;">
    <button type="submit" class="btn btn-primary">Hinzufügen</button>
  </div>
</form>
<hr>

<h2>Alle Abonnenten</h2>
<table class="table table-bordered">
  <thead>
    <tr>
      <th>ID</th>
      <th>E-Mail</th>
      <th>Brand</th>
      <th>Status</th>
      <th>Erstellt am</th>
      <th>Bestätigt am</th>
      <th>Aktion</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($subs as $sub): ?>
    <tr>
      <td><?= $sub['id'] ?></td>
      <td><?= htmlspecialchars($sub['email']) ?></td>
      <td><?= htmlspecialchars($sub['brand_slug']) ?></td>
      <td><?= $sub['status'] ?></td>
      <td><?= $sub['created_at'] ?></td>
      <td><?= $sub['confirmed_at'] ?></td>
      <td>
        <a class="btn btn-danger btn-sm"
           href="?remove_id=<?= $sub['id'] ?>"
           onclick="return confirm('Wirklich löschen?')">
          Löschen
        </a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</body>
</html>
