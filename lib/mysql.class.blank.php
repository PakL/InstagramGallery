<?php

class mysql {

	private $host		= "";
	private $user		= "";
	private $password	= "";
	private $database	= "";
	public  $prefix		= "";

	private $connection;

	public function __construct( $dontdie = false ) {

		if(
			!isset( $this->host ) 		|| empty( $this->host )		||
			!isset( $this->user )		|| empty( $this->user )		||
			!isset( $this->password )	|| empty( $this->password )	||
			!isset( $this->database )	|| empty( $this->database )	||
			!isset( $this->prefix )
		) {
			if( !$dontdie )
				die( 'Bitte Installation durchführen.' );
			else
				return;
		}

		$this->connection = @mysql_connect( $this->host, $this->user, $this->password ) or die( 'Verbindung zu Datenbank fehlgeschlagen.' );

		@mysql_select_db( $this->database, $this->connection ) or die( 'Datenbank konnte nicht ausgewählt werden. (' . mysql_error( $this->connection ) . ')' );

		$this->checkTables( $dontdie );
	}

	public function checkTables( $returnbool = false ) {
		$tables = $this->query( "SHOW TABLES", true );

		$reqtables = array(
			$this->prefix . "settings" => false,
			$this->prefix . "picture" => false
		);

		if( count( $tables ) >= count( $reqtables ) ) {
			foreach( $tables as $table ) {
				$reqtables[$table[0]] = true;
			}

			foreach( $reqtables as $table => $bool ) {
				if( $bool === false ) {
					if( !$returnbool )
						die( 'Tabelle vermisst: ' . $table );
					else
						return;
				}
			}
		} else {
			if( !$returnbool )
				die( 'Datenbank nicht komplett.' );
			else
				return false;
		}

		return true;
	}

	public function isConnected() {
		if( $this->connection )
			return true;

		return false;
	}

	public function query( $query, $response = false ) {
		$result = mysql_query( $query, $this->connection ) or die( 'Fehler in SQL-Anfrage. (' . mysql_error( $this->connection ) . ')' );

		$re = array();
		if( $response ) {
			if( substr( $query, 0, strlen( "INSERT" ) ) == "INSERT" ) {
				return mysql_insert_id( $this->connection );
			} else {
				while( $row = mysql_fetch_array( $result ) ) {
					$re[] = $row;
				}

				return $re;
			}
		}
	}

	public function getHost() {
		return $this->host;
	}
	public function getUser() {
		return $this->user;
	}
	/*public function getPassword() {
		return $this->password;
	}*/
	public function getDatabase() {
		return $this->database;
	}
	public function getPrefix() {
		return $this->prefix;
	}

	public function __destruct() {
		@mysql_close( $this->connection );
	}

}