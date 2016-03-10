<?php
/**
 * Created by michael on 3/1/16.
 */

namespace Connection;

// These are the PHPMyAdmin info (can probably remove).
define( 'PHPMYADMIN_URL', 'https://tqtag.ezadmin3.com/tqtag_pma_449/' );
define( 'DB_USER_HOST', 'ip-10-35-132-143.eu-west-1.compute.internal' );
define( 'DB_PORT', '36538' );

// These are the 4 I use for connection
define( 'DB_HOST', 'tqtagdb.cj4pvyybtpd7.eu-west-1.rds.amazonaws.com' );
define( 'DB_USERNAME', 'U7fdeea503731519' );
define( 'DB_PASSWORD', 'a234fc65df7bd8fa');
define( 'DB_DATABASE', 'copro_co_il');


class DB {

    static $conn;
    static $host = DB_HOST;
    static $db_name = DB_DATABASE;

    function __construct() {

    }

    static function get_conn() {

        if ( !is_null( self::$conn ) && is_a(self::$conn, '\mysqli') ) {
            return self::$conn;
        }
        $host = self::$host;
        $db_name = self::$db_name;
        try {
            $conn = new \PDO("mysql:host={$host};dbname={$db_name}", DB_USERNAME, DB_PASSWORD, array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
            $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            self::$conn = $conn;
        } catch(\PDOException $e) {
            echo 'ERROR: ' . $e->getMessage();
        }

        return self::$conn;
    }
}
/**
 * Get a $mysqli connection. Sort of a Singleton (at the moment) so it will return the
 * same mysqli object as whichever one is first connected to here.
 *
 * @return \mysqli|null
 */
function get_connection() {
    return DB::get_conn();
}

