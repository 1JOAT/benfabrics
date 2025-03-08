
<?php
// Database configuration
define('DB_HOST', 'sdb-79.hosting.stackcp.net');
define('DB_USER', 'benfabric-35303833d6c5');
define('DB_PASS', 'Sirstevehq12@@";');
define('DB_NAME', 'benfabric-35303833d6c5');

define('SITE_NAME', 'BENFABRICS');
define('SITE_URL', 'http://www.benfabrics.landcraft.site');

// define('DB_HOST', 'localhost');
// define('DB_USER', 'root');
// define('DB_PASS', '');
// define('DB_NAME', 'benfabric');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 