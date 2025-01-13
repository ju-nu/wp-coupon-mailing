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

// -- Settings
$perPage = 20;
$newError = '';

// -- Handle add subscriber
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_email'], $_POST['new_brand'])) {
    $email = filter_var($_POST['new_email'], FILTER_VALIDATE_EMAIL);
    $brand = filter_var($_POST['new_brand'], FILTER_SANITIZE_STRING);

    if ($email && $brand) {
        // Check if exists
        $existsStmt = $pdo->prepare("
            SELECT id FROM newsletter_subscriptions 
            WHERE email = :email AND brand_slug = :brand LIMIT 1
        ");
        $existsStmt->execute([':email' => $email, ':brand' => $brand]);
        $exists = $existsStmt->fetch();

        if ($exists) {
            $newError = "Diese E-Mail ist bereits für den Brand {$brand} eingetragen.";
        } else {
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
    }
    header("Location: manage.php");
    exit;
}

// -- Handle remove
if (isset($_GET['remove_id'])) {
    $id = (int)$_GET['remove_id'];
    $pdo->prepare("DELETE FROM newsletter_subscriptions WHERE id=:id")->execute([':id' => $id]);
    header("Location: manage.php");
    exit;
}

// -- Search, sort
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) ?: '';
$searchParam = "%$search%";

$validSort = ['email','brand_slug','status','created_at','confirmed_at'];
$sort = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_STRING);
$sort = in_array($sort, $validSort, true) ? $sort : 'created_at';

$order = filter_input(INPUT_GET, 'order', FILTER_SANITIZE_STRING) === 'asc' ? 'asc' : 'desc';

// -- Pagination
$page = max((int)(filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1), 1);
$offset = ($page - 1) * $perPage;

// Count total
$countSql = "
    SELECT COUNT(*) as cnt 
    FROM newsletter_subscriptions
    WHERE (email LIKE :search OR brand_slug LIKE :search)
";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute([':search' => $searchParam]);
$totalRows = (int)$countStmt->fetchColumn();

$sql = "
    SELECT * 
    FROM newsletter_subscriptions
    WHERE (email LIKE :search OR brand_slug LIKE :search)
    ORDER BY $sort $order
    LIMIT :offset, :limit
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':search', $searchParam);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->execute();
$subs = $stmt->fetchAll();

$totalPages = max(1, ceil($totalRows / $perPage));
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
<?php if ($newError): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($newError) ?></div>
<?php endif; ?>
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

<form method="get" class="mb-3">
  <div class="row">
    <div class="col-md-4">
      <input type="text" name="search" class="form-control" placeholder="Suchen (E-Mail oder Brand)" 
             value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-md-3">
      <select name="sort" class="form-select">
        <option value="created_at" <?= $sort==='created_at'?'selected':'' ?>>Sort: Erstellungsdatum</option>
        <option value="email" <?= $sort==='email'?'selected':'' ?>>Sort: E-Mail</option>
        <option value="brand_slug" <?= $sort==='brand_slug'?'selected':'' ?>>Sort: Brand</option>
        <option value="status" <?= $sort==='status'?'selected':'' ?>>Sort: Status</option>
        <option value="confirmed_at" <?= $sort==='confirmed_at'?'selected':'' ?>>Sort: Bestätigt am</option>
      </select>
    </div>
    <div class="col-md-2">
      <select name="order" class="form-select">
        <option value="desc" <?= $order==='desc'?'selected':'' ?>>Absteigend</option>
        <option value="asc" <?= $order==='asc'?'selected':'' ?>>Aufsteigend</option>
      </select>
    </div>
    <div class="col-md-3">
      <button class="btn btn-secondary" type="submit">Filtern</button>
    </div>
  </div>
</form>

<h2>Alle Abonnenten (<?= $totalRows ?>)</h2>
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
           href="?remove_id=<?= $sub['id'] ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>&order=<?= urlencode($order) ?>&page=<?= $page ?>"
           onclick="return confirm('Wirklich löschen?')">
          Löschen
        </a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<!-- Pagination -->
<nav>
  <ul class="pagination">
    <?php for ($p=1; $p<=$totalPages; $p++): ?>
      <li class="page-item <?= ($p==$page)?'active':'' ?>">
        <a class="page-link"
           href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&sort=<?= urlencode($sort) ?>&order=<?= urlencode($order) ?>">
          <?= $p ?>
        </a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
</body>
</html>
