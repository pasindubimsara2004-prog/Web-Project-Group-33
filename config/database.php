<?php



define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'nsbm_artcraft');

define('SITE_NAME', 'NSBM ArtCraft');
define('SITE_TAGLINE', 'Student Arts & Handcraft Showcasing System');
define('UNIVERSITY_NAME', 'NSBM Green University');


$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = isset($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) : '';

$pathParts = explode('/', trim($scriptDir, '/\\'));
$baseFolder = !empty($pathParts[0]) ? '/' . $pathParts[0] : '';
define('SITE_URL', $protocol . $host . $baseFolder);

function getDBConnection() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
        
            if (($e->getCode() == 1049 || strpos($e->getMessage(), 'Unknown database') !== false) && basename($_SERVER['PHP_SELF']) !== 'install.php') {
                header("Location: " . SITE_URL . "/install.php");
                exit;
            }

           
            die("<div style='font-family:sans-serif;max-width:550px;margin:50px auto;padding:24px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;'>
                <h3 style='color:#b91c1c;margin-top:0;'>Database Connection Error</h3>
                <p>Could not connect to the MySQL database. Please make sure MySQL is started in your XAMPP Control Panel.</p>
                <p style='color:#64748b;font-size:14px;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                <a href='" . SITE_URL . "/install.php' style='display:inline-block;padding:10px 16px;background:#006633;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;'>Run Database Setup</a>
            </div>");
        }
    }

    return $pdo;
}
