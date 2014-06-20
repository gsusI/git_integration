<?php
$connection;

$dbname;
$host;
$port;
$username;
$password;

$stating_dbname;
$stating_host;
$stating_port;
$stating_username;
$stating_password;

$main_dbname;
$main_host;
$main_port;
$main_username;
$main_password;

$configuration = parse_ini_file ( "ReportingConfig.ini", TRUE );

loadArgs ( $argv );

$query = "SELECT DISTINCT recipient FROM `sg_events_email_event` WHERE CHAR_LENGTH(recipient)=30";
$emailsToFix = stating_query ( $query );

foreach ( $emailsToFix as $emailToFix ) {
	$originalEmailToFix = $emailToFix ['recipient'];
	$emailToQuery = '%' . $originalEmailToFix . '%';
	$query = "SELECT email FROM `sg_users` WHERE email LIKE '$emailToQuery'";
	$correctEmail = main_query ( $query );
	if (count ( $correctEmail ) == 1) {
		$correctEmail = $correctEmail [0] ['email'];
		if (strcasecmp ( $correctEmail, $originalEmailToFix ) != 0) {
			$query = "UPDATE `sg_events_email_event` SET recipient='$correctEmail' WHERE recipient='$originalEmailToFix'";
			stating_insert ( $query );
		}
	} else {
		var_dump ( $emailToQuery );
		var_dump ( $correctEmail );
	}
}
function filter($input) {
	global $connection;
	setStatingDB ();
	connect ();
	$result = mysqli_real_escape_string ( $connection, $input );
	disconnect ();
	return $result;
}
function loadArgs($argv) {
	global $configuration, $stating_host, $stating_port, $stating_dbname, $stating_username, $stating_password, $main_host, $main_port, $main_dbname, $main_username, $main_password;
	$stating_host = $configuration ['stating_host'];
	$stating_port = $configuration ['stating_port'];
	$stating_dbname = $configuration ['stating_dbname'];
	$stating_username = $configuration ['stating_username'];
	$stating_password = $configuration ['stating_password'];
	$main_host = $configuration ['main_host'];
	$main_port = $configuration ['main_port'];
	$main_dbname = $configuration ['main_dbname'];
	$main_username = $configuration ['main_username'];
	$main_password = $configuration ['main_password'];
}
function stating_insert($query, $getId = FALSE) {
	setStatingDB ();
	insert ( $query, $getId );
}
function stating_query($query, $index = -1) {
	setStatingDB ();
	return query ( $query, $index );
}
function main_insert($query, $getId = FALSE) {
	setMainDB ();
	insert ( $query, $getId );
}
function main_query($query, $index = -1) {
	setMainDB ();
	return query ( $query, $index );
}
function setStatingDB() {
	global $dbname, $host, $port, $username, $password, $stating_dbname, $stating_host, $stating_port, $stating_username, $stating_password;
	$host = $stating_host;
	$dbname = $stating_dbname;
	$port = $stating_port;
	$username = $stating_username;
	$password = $stating_password;
}
function setMainDB() {
	global $dbname, $host, $port, $username, $password, $main_dbname, $main_host, $main_port, $main_username, $main_password;
	$host = $main_host;
	$dbname = $main_dbname;
	$port = $main_port;
	$username = $main_username;
	$password = $main_password;
}
function insert($query, $getId = FALSE) {
	global $connection, $dbname, $host, $port;
	$result = array ();
	$id = null;
	if (connect ()) {
		$queryResult = mysqli_query ( $connection, $query );
		if (($error = mysqli_errno ( $connection )) != 0) {
			disconnect ();
			throw new \Exception ( "Error $error while doing query: $host:$port - $dbname - Query: $query" );
		}
		if ($getId) {
			$id = mysqli_insert_id ( $connection );
			if (($error = mysqli_errno ( $connection )) != 0) {
				disconnect ();
				throw new \Exception ( "Error $error while gettin inserted id: $host:$port - $dbname - Query: $query" );
			}
		}
		disconnect ();
	}
	return $id;
}
function query($query, $index = -1) {
	global $connection, $dbname, $host, $port;
	$result = array ();
	if (connect ()) {
		$queryResult = mysqli_query ( $connection, $query );
		if (($error = mysqli_errno ( $connection )) != 0) {
			disconnect ();
			throw new \Exception ( "Error $error while doing query: $host:$port - $dbname - Query: $query" );
		}
		if ($queryResult) {
			while ( $row = mysqli_fetch_assoc ( $queryResult ) ) {
				if ($index > - 1 && $index < count ( $row )) {
					$auxiliarIndex = array_keys ( $row );
					$result [$row [$auxiliarIndex [$index]]] = $row;
				} else {
					$result [] = $row;
				}
			}
			mysqli_free_result ( $queryResult );
			if (($error = mysqli_errno ( $connection )) != 0) {
				disconnect ();
				throw new \Exception ( "Error $error while reading results: $host:$port - $dbname - Query: $query" );
			}
		}
		disconnect ();
	}
	return $result;
}
function connect() {
	$result = FALSE;
	global $connection, $dbname, $host, $port, $username, $password;
	if (! isset ( $connection )) {
		$connection = mysqli_connect ( $host, $username, $password );
		if (($error = mysqli_errno ( $connection )) != 0) {
			$connection = null;
			throw new \Exception ( "Error $error connecting Database: $host:$port" );
		}
		$connection = ($connection == FALSE) ? null : $connection;
		if ($connection) {
			if (mysqli_select_db ( $connection, $dbname )) {
				$result = TRUE;
			} else {
				if (($error = mysqli_errno ( $connection )) != 0) {
					disconnect ();
					throw new \Exception ( "Error $error selecting Database: $host:$port - $dbname" );
				}
				$connection = null;
			}
		}
	}
	// var_dump($result);
	return $result;
}
function disconnect() {
	global $connection, $host, $port, $dbname;
	if (isset ( $connection )) {
		$result = mysqli_close ( $connection );
		if (! $result) {
			$error = mysqli_errno ( $connection );
			$connection = null;
			throw new \Exception ( "Error $error closing dataBase: $host:$port - $dbname" );
		}
		$connection = null;
	}
}
