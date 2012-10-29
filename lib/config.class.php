<?php

class config {

	private $mysql;
	private $config = array(
		"additional_curlopt" => array(
		array(
			CURLOPT_HTTPPROXYTUNNEL => false,
			CURLOPT_PROXYAUTH		=> CURLAUTH_BASIC,
			CURLOPT_PROXYPORT		=> 8080,
			CURLOPT_PROXYTYPE		=> CURLPROXY_HTTP,
			CURLOPT_PROXY			=> "",
			CURLOPT_PROXYUSERPWD	=> ""
		), "array" ),


		"pagetitle" => array( "Instagram gallery powered by paklDev", "string" ),
		"headline" => array( "", "string" ),
		"autoloadnewimages" => array( true, "bool" ),
		"picturesperpage" => array( 25, "int" ),
		"showtagcloud" => array( true, "bool" ),
		"showcomments" => array( true, "bool" ),
		"mintags" => array( 5, "int" ),
		"maxtags" => array( 30, "int" ),
		"description" => array( "", "string" ),
		"keywords" => array( "", "string" ),
		"tagblacklist" => array( "[]", "string" ),
		"imageblacklist" => array( "[]", "string" )
	);

	public function __construct( $mysql ) {
		$this->mysql = $mysql;
		if( $this->mysql->isConnected() && $this->mysql->checkTables( true ) ) {
			$result = $mysql->query( "SELECT * FROM `" . $this->mysql->prefix . "settings` WHERE `key` LIKE 'conf_%'", true );

			foreach( $result as $row ) {
				$key = substr( $row["key"], strlen( "conf_" ) );
				$val = $row["value"];
				$format = "string";
				if( isset( $this->config[$key] ) && isset( $this->config[$key][1] ) ) {
					$format = $this->config[$key][1];
				}

				$val = $this->formatContent( $val, $format );

				$this->config[$key] = array( $val, $format );
			}
		}
	}

	private function formatContent( $input, $format ) {
		if( strtolower( $format ) == "int" ) {
			return intval( preg_replace( "[^0-9\-]", "", $input ) );
		} else if( strtolower( $format ) == "bool" ) {
			if( $input === false || $input === "false" ) {
				return false;
			} 

			return true;
		} else if( strtolower( $format ) == "array" ) {
			if( !is_array( $input ) ) {
				return array( $input );
			}
		}

		return $input;
	}

	public function get( $config ) {
		if( isset( $this->config[$config] ) ) {
			return $this->config[$config][0];
		}
	}

	public function set( $config, $value, $format = "" ) {
		$format = strtolower( $format );
		if( isset( $this->config[$config] ) ) {
			if( !empty( $format ) &&  $format != $this->config[$config][1] )
				return;

			$format = $this->config[$config][1];

			$value = $this->formatContent( $value, $format );
			$this->config[$config] = array( $value, $format );

			if( $format == "bool" ) {
				if( $value ) 
					$value = "true";
				else
					$value = "false";
			}

			if( $this->mysql->isConnected() ) {
				$this->mysql->query( "DELETE FROM `" . $this->mysql->prefix . "settings` WHERE `key` = 'conf_" . escape( $config ) . "'" );
				$this->mysql->query( "INSERT INTO `" . $this->mysql->prefix . "settings` ( `key`, `value` ) VALUES ( 'conf_" . escape( $config ) . "', '" . escape( $value ) . "' )" );
			}
		} else {
			if( empty( $format ) )
				$format = "string";

			$value = $this->formatContent( $value, $format );
			$this->config[$config] = array( $value, $format );

			if( $format == "bool" ) {
				if( $value ) 
					$value = "true";
				else
					$value = "false";
			}

			if( $this->mysql->isConnected() ) {
				$this->mysql->query( "DELETE FROM `" . $this->mysql->prefix . "settings` WHERE `key` = 'conf_" . escape( $config ) . "'" );
				$this->mysql->query( "INSERT INTO `" . $this->mysql->prefix . "settings` ( `key`, `value` ) VALUES ( 'conf_" . escape( $config ) . "', '" . escape( $value ) . "' )" );
			}
		}
	}

}