<?php
$docRoot = getenv("DOCUMENT_ROOT");

require_once($docRoot . "/MIT-Mobile-Web/mobi-config/mobi_web_constants.php");

class db {
  public static $connection = NULL;

    private static $host = localhost;
    private static $username = root;
    private static $passwd = skyglobe;
    private static $db = NULL;

  public static function init() {
    if(!self::$connection) {
      self::$connection = new mysqli(self::$host, self::$username, self::$passwd, self::$db);
    }
  }

  public static function escape($string) {
    return self::$connection->real_escape_string($string);
  }

  public static function ping() {
    if(!self::$connection->ping()) {
      self::$connection->close();
      self::$connection = NULL;
      self::init();
    }
  }
}

db::init();

?>
