<?php
class Database {

    // database credentials.
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn = null;
    
    // get database connection.
    public function __construct($host, $db_name, $username, $password) {
        $this->host = $host;
        $this->db_name = $db_name;
        $this->username = $username;
        $this->password = $password;    
        
        $this->getConnection();
    } 

    private function getConnection() {
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("SET NAMES utf8");
            // echo "Connection is made <br />";
        } catch(PDOException $e) {
            echo "Connection error: " . $e->getMessage();
        }
    }
}

