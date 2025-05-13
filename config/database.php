<?php
//config/database.php
/**
 * Configuração de conexão com o banco de dados
 * Klube Cash - Sistema de Cashback
 */

// Parâmetros de conexão com o banco de dados
define('DB_HOST', 'srv406.hstgr.io'); // ou '45.89.204.5'
define('DB_NAME', 'u383946504_klubecash');
define('DB_USER', 'u383946504_klubecash');
define('DB_PASS', 'Aaku_2004@'); // Substitua pela senha real

/**
 * Classe Database - Gerencia a conexão com o banco de dados
 */
class Database {
    private static $connection = null;
    
    /**
     * Obtém uma conexão com o banco de dados
     * 
     * @return PDO Objeto de conexão PDO
     */
    public static function getConnection() {
        // Se já existe uma conexão, retorna ela
        if (self::$connection !== null) {
            return self::$connection;
        }
        
        try {
            // Cria uma nova conexão PDO
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            self::$connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            return self::$connection;
        } catch (PDOException $e) {
            // Registra o erro e retorna uma mensagem amigável
            error_log("Erro de conexão: " . $e->getMessage());
            die("Não foi possível conectar ao banco de dados. Por favor, tente novamente mais tarde.");
        }
    }
    
    /**
     * Fecha a conexão com o banco de dados
     */
    public static function closeConnection() {
        self::$connection = null;
    }
}