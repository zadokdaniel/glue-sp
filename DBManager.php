<?php

require_once BaseConf::CONFIG_DIR . "/Database.php";

class DBManager {

    protected static $dbs_array;
    protected static $db_connections = [];

    public static function initialize($dbs_array) {
        self::$dbs_array = $dbs_array;
    }

    public static function connection($db_name) {
        
        // If connection has not made yet connect;
        if(!isset(self::$db_connections[$db_name]) && isset(self::$dbs_array[$db_name])) {
            self::$db_connections[$db_name] = new Database(...self::$dbs_array[$db_name]);
        }
        return self::$db_connections[$db_name]->conn;
    }
}
