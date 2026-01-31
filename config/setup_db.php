<?php
// config/setup_db.php

$host = 'localhost';
$user = 'root';
$pass = ''; // Default XAMPP

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents(__DIR__ . '/database.sql');
    
    // Split SQL by semicolon, but be careful with triggers/procedures if any (none here)
    $queries = explode(';', $sql);

    foreach ($queries as $query) {
        $query = trim($query);
        if ($query) {
            $pdo->exec($query);
        }
    }

    echo "Banco de dados 'pricesmart' inicializado com sucesso e registros de exemplo inseridos!";
} catch (PDOException $e) {
    die("Erro ao inicializar o banco de dados: " . $e->getMessage());
}
?>
