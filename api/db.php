<?php
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $host = 'localhost';
        $dbname = 'u82195';
        $user = 'u82195';
        $pass = '6640640';
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}
