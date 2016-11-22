<?php
/**
 * @project		Framework 1.0
 *
 * @author		Olivier Gaillard <olivier.gaillard@larep.com>
 * @version		1.0 du 01/01/2009
 * @lastmod		22/10/2009 14:19
 * @desc	   	Gestion des requêtes MySQL
 */

class mySQLdb {
	
	private $host;
	private $user;
	private $pass;
	private $dbname;
	private $cnx;
	public $db;
	private $result;

	// Constructeur
	function mySQLdb ($dbname, $dbhost, $dbuser, $dbpass) { 
		$this->host   = $dbhost;
		$this->user   = $dbuser;
		$this->pass   = $dbpass;
		$this->dbname = $dbname;
		if(! $this->cnx = @mysql_connect($this->host,$this->user,$this->pass)) {
			die("Erreur fatale : Impossible de se connecter à la bdd '$this->dbname' !");
			}
		if(! $this->db = @mysql_select_db($this->dbname,$this->cnx)){
			die("Erreur fatale : La bdd '$this->dbname' est introuvable !");
			}
		else {
		 	return $this->db;
			}
		}

	// Fermeture de la connexion
	function disconnect () { 
		$ret = mysql_close($this->cnx);
		$this->cnx = $this->db = null;
        return $ret;
		}
// ------------------------------------------------------------------------------------------------//
	function getRow($sql) { 
		$this->result = mysql_query($sql, $this->cnx);
		if (!$this->result) {
				return FALSE;
			}
		return mysql_fetch_object($this->result);
		}
// ------------------------------------------------------------------------------------------------//
	function getPop($sql) { 
		$this->result = mysql_query($sql, $this->cnx);
		if (!$this->result) {
				return FALSE;
			}
		while ($row = mysql_fetch_row($this->result)) {
					$results[$row[0]] = $row[1];
                }
		//echo "<pre>";print_r($results);echo "</pre>";
		if (isset($results)) return $results;
		}
// ------------------------------------------------------------------------------------------------//
	function getOne($sql) { 
		$this->result = mysql_query($sql, $this->cnx);
		if (!$this->result) {
				return FALSE;
			}
		$row = mysql_fetch_row($this->result);
		return $row[0];
		}
// ------------------------------------------------------------------------------------------------//
	function getAllOne($sql) { 
		$results = array();
		$this->result = mysql_query($sql, $this->cnx);
		if (!$this->result) {
				return FALSE;
			}
        while ($row = mysql_fetch_row($this->result)) {
			$results[] = $row[0];
			}
		return $results;
		}
// ------------------------------------------------------------------------------------------------//
	function getArray($sql) { 
		$this->result = mysql_query($sql, $this->cnx);
		if (!$this->result) {
				return FALSE;
			}
		return mysql_fetch_array($this->result, MYSQL_ASSOC);
		}

// ------------------------------------------------------------------------------------------------//
	function getAll($sql) { 
		$results = array();
		$this->result = mysql_query($sql, $this->cnx);
		if (!$this->result) {
				return FALSE;
			}
        while ($row = mysql_fetch_array($this->result, MYSQL_ASSOC)) {
			$results[] = $row;
			}
		return $results;
		}
// ------------------------------------------------------------------------------------------------//
	function query ($sql) { 
		$this->result = mysql_query($sql, $this->cnx);
		if (!$this->result) {
				return FALSE;
			}
		return $this->result;
		}
// ------------------------------------------------------------------------------------------------//
	function fetch ($result) { 
		return mysql_fetch_object($result);
		}
// ------------------------------------------------------------------------------------------------//
	function fetch_assoc ($result) { 
		return mysql_fetch_assoc($result);
		}
// ------------------------------------------------------------------------------------------------//
	function fetch_array ($result) { 
		return mysql_fetch_array($result);
		}

// ------------------------------------------------------------------------------------------------//
	function insert_id () { 
		return mysql_insert_id();
		}
// ------------------------------------------------------------------------------------------------//
}
?>