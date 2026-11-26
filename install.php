<?php


define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'nsbm_artcraft');

$message = '';
$status = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' || php_sapi_name() === 'cli') {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

      
        $sqlFile = __DIR__ . '/database.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("database.sql file not found at " . $sqlFile);
        }

        $sql = file_get_contents($sqlFile);
        
        
        $pdo->exec($sql);
        
        $status = 'success';
        $message = 'Database and tables created successfully! Demo data populated.';

        if (php_sapi_name() === 'cli') {
            echo "[SUCCESS] $message\n";
            exit(0);
        }
    } catch (Exception $e) {
        $status = 'error';
        $message = 'Installation Error: ' . $e->getMessage();
        if (php_sapi_name() === 'cli') {
            echo "[ERROR] $message\n";
            exit(1);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Database Setup - NSBM ArtCraft</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body { background: #f0f7f4; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; color: #1e293b; }
    .card { border-radius: 16px; border: none; box-shadow: 0 10px 25px -5px rgba(0, 102, 51, 0.1), 0 8px 10px -6px rgba(0, 102, 51, 0.1); }
    .brand-badge { background: #e6f4ea; color: #006633; font-weight: 700; padding: 6px 14px; border-radius: 50px; font-size: 0.85rem; }
    .btn-nsbm { background: #006633; color: white; font-weight: 600; padding: 12px 24px; border-radius: 8px; border: none; }
    .btn-nsbm:hover { background: #004d26; color: white; }
    .code-box { background: #1e293b; color: #38bdf8; padding: 16px; border-radius: 8px; font-family: monospace; font-size: 0.9rem; }
  </style>
</head>
<body class="py-5">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">
      <div class="text-center mb-4">
        <img src="assets/images/nsbm_logo.png" alt="NSBM Logo" width="52" height="56" class="mb-2" style="object-fit:contain;"><br>
        <span class="brand-badge"><i class="bi bi-mortarboard-fill me-1"></i> NSBM Green University</span>
        <h2 class="mt-2 fw-bold text-success">NSBM ArtCraft Platform</h2>
        <p class="text-muted">Database Initialization & Environment Setup</p>
      </div>

      <div class="card p-4 p-md-5">
        <?php if ($status === 'success'): ?>
          <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-3 me-3"></i>
            <div>
              <h5 class="alert-heading mb-1 fw-bold">Setup Completed Successfully!</h5>
              <div>Database <code>nsbm_artcraft</code> is created and ready with tables & realistic seed records.</div>
            </div>
          </div>

          <h5 class="fw-bold mb-3"><i class="bi bi-key-fill text-warning me-2"></i>Default Login Credentials</h5>
          <div class="table-responsive mb-4">
            <table class="table table-bordered table-striped align-middle">
              <thead class="table-light">
                <tr>
                  <th>Role</th>
                  <th>Email</th>
                  <th>Password</th>
                  <th>Student ID</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><span class="badge bg-danger">Admin</span></td>
                  <td><strong>admin@nsbm.ac.lk</strong></td>
                  <td><code>admin123</code></td>
                  <td>ADMIN-001</td>
                </tr>
                <tr>
                  <td><span class="badge bg-success">Student</span></td>
                  <td><strong>student1@nsbm.ac.lk</strong></td>
                  <td><code>student123</code></td>
                  <td>NSBM/SOC/26.1/1042</td>
                </tr>
                <tr>
                  <td><span class="badge bg-success">Student</span></td>
                  <td><strong>student2@nsbm.ac.lk</strong></td>
                  <td><code>student123</code></td>
                  <td>NSBM/FOB/26.1/2150</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="d-grid gap-2 d-md-flex justify-content-md-center">
            <a href="index.php" class="btn btn-nsbm"><i class="bi bi-house-door-fill me-2"></i>Go to Homepage</a>
            <a href="login.php" class="btn btn-outline-success"><i class="bi bi-box-arrow-in-right me-2"></i>Login Now</a>
          </div>

        <?php else: ?>

          <?php if ($status === 'error'): ?>
            <div class="alert alert-danger mb-4">
              <i class="bi bi-exclamation-triangle-fill me-2"></i>
              <strong>Error:</strong> <?php echo htmlspecialchars($message); ?>
            </div>
          <?php endif; ?>

          <div class="mb-4">
            <h5 class="fw-bold">Welcome to NSBM ArtCraft Installer</h5>
            <p class="text-secondary small">
              This automated installer will configure your MySQL database, create all required tables 
              (users, artworks, categories, exhibitions, participants, likes, ratings, comments), 
              and seed test student and admin accounts.
            </p>
          </div>

          <div class="mb-4">
            <label class="form-label text-muted small fw-bold">Current MySQL Configuration</label>
            <div class="code-box">
              Host: <?php echo DB_HOST; ?><br>
              Port: <?php echo DB_PORT; ?><br>
              User: <?php echo DB_USER; ?><br>
              Database: <?php echo DB_NAME; ?>
            </div>
          </div>

          <form method="POST">
            <div class="d-grid">
              <button type="submit" class="btn btn-nsbm py-3">
                <i class="bi bi-database-fill-gear me-2"></i>Install Database & Seed Data
              </button>
            </div>
          </form>

        <?php endif; ?>
      </div>

      <div class="text-center mt-4 text-muted small">
        Module: Web and Mobile Application Development | Batch 26.1 IFSL<br>
        Faculty of Computing, NSBM Green University
      </div>
    </div>
  </div>
</div>
</body>
</html>
