<?php
// ============================================================
// Conexión a la base de datos "rutanexo" (PDO)
// Uso:  $db = new Database();  $pdo = $db->conectar();
// Si la conexión falla devuelve null (la página decide qué mostrar).
// ============================================================

// En producción: no mostrar errores en pantalla, solo registrarlos en el log
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

class Database
{
    private $hostname = "127.0.0.1";
    private $port     = 3306;          // MAMP usa 8889
    private $database = "rutanexo";
    private $username = "root";
    private $password = "";            // XAMPP: vacío por defecto
    private $charset  = "utf8mb4";

    public function conectar(): ?PDO
    {
        try {
            $dsn = "mysql:host={$this->hostname};port={$this->port};dbname={$this->database};charset={$this->charset}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

            return new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // El detalle va al log del servidor, nunca al usuario
            error_log('Error de conexión: ' . $e->getMessage());
            return null;
        }
    }
}
