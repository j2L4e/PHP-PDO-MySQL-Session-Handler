<?php

/**
 * A PHP session handler using PDO to keep session data within a MySQL database
 *
 * @author  Jan Lohage <info@j2l4e.de>
 * @link    https://github.com/j2L4e/PHP-PDO-MySQL-Session-Handler
 *
 *
 * Based on PHP-MySQL-Session-Handler (uses mysqli)
 *
 * @author  Manuel Reinhard <manu@sprain.ch>
 * @link    https://github.com/sprain/PHP-MySQL-Session-Handler
 */
class SessionHandler
{
  /**
   * a PDO connection resource
   * @var resource
   */
  protected $dbh;


  /**
   * the name of the DB table which handles the sessions
   * @var string
   */
  protected $dbTable;


  /**
   * Set db data if no connection is being injected
   * @param  string $dbHost
   * @param  string $dbUser
   * @param  string $dbPassword
   * @param  string $dbDatabase
   * @param  string $dbCharset optional, default 'utf8'
   */
  public function setDbDetails($dbHost, $dbUser, $dbPassword, $dbDatabase, $dbCharset = 'utf8') {

    //create db connection
    $this->dbh = new PDO("mysql:" .
      "host={$dbHost};" .
      "dbname={$dbDatabase};" .
      "charset={$dbCharset}",
      $dbUser,
      $dbPassword,
      array(
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION //USE ERRMODE_SILENT FOR PRODUCTION!
      )
    );
  }//function


  /**
   * Inject PDO from outside
   * @param object $dbh expects PDO object
   */
  public function setPDO($dbh) {
    $this->dbh = $dbh;
  }


  /**
   * Set MySQL table to work with
   * @param string $dbTable
   */
  public function setDbTable($dbTable) {
    $this->dbTable = $dbTable;
  }


  /**
   * Open the session
   * @return bool
   */
  public function open() {
    //delete old session handlers
    $limit = time() - (3600 * 24);
    $stmt = $this->dbh->prepare("DELETE FROM {$this->dbTable} WHERE timestamp < :limit");
    $ret = $stmt->execute(array(':limit' => $limit));

    return $ret;
  }

  /**
   * Close the session
   * @return bool
   */
  public function close() {
    $this->dbh = null;
  }

  /**
   * Read the session
   * @param int session id
   * @return string string of the sessoin
   */
  public function read($id) {
    $stmt = $this->dbh->prepare("SELECT * FROM {$this->dbTable} WHERE id=:id");
    $stmt->execute(array(':id' => $id));

    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($session) {
      $ret = $session['data'];
    } else {
      $ret = false;
    }

    return $ret;
  }


  /**
   * Write the session
   * @param int session id
   * @param string data of the session
   */
  public function write($id, $data) {
    $stmt = $this->dbh->prepare("REPLACE INTO {$this->dbTable} (id,data,timestamp) VALUES (:id,:data,:timestamp)");
    $ret = $stmt->execute(
      array(':id' => $id,
        ':data' => $data,
        'timestamp' => time()
      ));

    return $ret;
  }

  /**
   * Destroy the session
   * @param int session id
   * @return bool
   */
  public function destroy($id) {
    $stmt = $this->dbh->prepare("DELETE FROM {$this->dbTable} WHERE id=:id");
    $ret = $stmt->execute(array(
      ':id' => $id
    ));

    return $ret;
  }


  /**
   * Garbage Collector
   * @param int life time (sec.)
   * @return bool
   * @see session.gc_divisor      100
   * @see session.gc_maxlifetime 1440
   * @see session.gc_probability    1
   * @usage execution rate 1/100
   *        (session.gc_probability/session.gc_divisor)
   */
  public function gc($max) {
    $stmt = $this->dbh->prepare("DELETE FROM {$this->dbTable} WHERE timestamp < :limit");
    $ret = $stmt->execute(array(':limit' => time() - intval($max)));

    return $ret;
  }

}//class
