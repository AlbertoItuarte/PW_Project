<?php
// Test básico para verificar que PHP funciona
echo "🔧 PHP Version: " . phpversion() . "<br>";
echo "📅 Current Time: " . date('Y-m-d H:i:s') . "<br>";
echo "🌐 Server: " . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "<br>";
echo "🔗 Port: " . (getenv('PORT') ?: 'No definido') . "<br>";

// Test de base de datos
echo "<br><h3>🗄️ Database Test:</h3>";
try {
    require_once 'Config/dbConection.php';
    echo "✅ Database connection: SUCCESS<br>";
    
    // Test simple query
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "✅ Database query: SUCCESS (result: " . $result['test'] . ")<br>";
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<br><h3>📁 File Structure Test:</h3>";
$checkFiles = [
    'Pages/Login.php',
    'CSS/auth.css',
    'azul.jpg',
    'Config/dbConection.php'
];

foreach($checkFiles as $file) {
    $exists = file_exists($file);
    echo ($exists ? "✅" : "❌") . " $file" . ($exists ? "" : " (NOT FOUND)") . "<br>";
}

echo "<br><a href='health.php'>🏥 Health Check</a> | ";
echo "<a href='Pages/Login.php'>🔐 Login Page</a>";
?>