<?php
// Test simple de connexion à la base de données pour diagnostiquer l'erreur 500
// Ce script utilise les paramètres que vous m'avez fournis pour la préproduction.

$host = 'localhost';
$db   = 'isfppmad_Bien_sileny';
$user = 'isfppmad_Bien_sileny';
$pass = 'YzQh4nHFphp9STQ8FxCF';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

echo "<h1>Test de connexion BDD</h1>";

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "<p style='color:green;'>✅ Connexion réussie à la base de données !</p>";
} catch (\PDOException $e) {
    echo "<p style='color:red;'>❌ Erreur de connexion : " . $e->getMessage() . "</p>";
}

echo "<h2>Version PHP : " . phpversion() . "</h2>";
echo "<h2>Extensions chargées :</h2>";
echo "<pre>";
print_r(get_loaded_extensions());
echo "</pre>";
