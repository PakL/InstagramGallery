<?php

class template {

	private $config;
	private $var;

	public function __construct( $config ) {
		$this->config = $config;
		$this->var = array(
			"pagetitle" => $this->config->get( "pagetitle" ),
			"description" => $this->config->get( "description" ),
			"keywords" => $this->config->get( "keywords" )
		);
	}

	public function get( $filename ) {
		if( !file_exists( $filename ) )
			return;

		$tpl = $this->parse_full( $filename );

		return $tpl;
	}


	private function parse_full( $filename, $file = true ) {
		if( $file )
			$tpl = file_get_contents( $filename );
		else
			$tpl = $filename;

		preg_match_all( "/\{isset:([a-z0-9_]+)\}/i", $tpl, $matches );
		foreach( $matches[0] as $i => $match ) {
			$open = str_replace( "{", "\\{", str_replace( "}", "\\}", $match ) );
			$close = str_replace( "{", "{\\/", $open );

			if( preg_match( "/" . $open . "(.*)" . $close . "/imsU", $tpl, $ma ) ) {
				$v = strtolower( $matches[1][$i] );
				$r = "";
				if( isset( $this->var[$v] ) ) {
					if( !empty( $this->var[$v] ) )
						$r = $this->parse( $ma[1] );
				}
				if( isset( $GLOBALS[$v] ) ) {
					if( !empty( $GLOBALS[$v] ) )
						$r = $this->parse( $ma[1] );
				}

				$tpl = str_replace( $ma[0], $r, $tpl );
			}
		}

		$files = array();
		$handle = opendir( "tpl" );
		while( ($file = readdir( $handle )) !== false ) {
			$files[] = $file;
		}
		sort( $files, SORT_STRING );

		preg_match_all( "/\{inc:([a-z0-9\?\.]+)\}/i", $tpl, $matches );
		foreach( $matches[1] as $i => $match ) {
			$regex = str_replace( ".", "\\.", $match );
			$regex = str_replace( "?", ".", $regex );
			$regex = "/^" . $regex . "$/";

			$r = '';
			foreach( $files as $file ) {
				if( preg_match( $regex, $file ) ) {
					if( $filename != "tpl/" . $file )
						$r .= $this->parse_full( "tpl/" . $file );
				}
			}

			$tpl = str_replace( $matches[0][$i], $r, $tpl );
		}

		$tpl = $this->parse( $tpl );

		return $tpl;
	}

	private function parse( $tpl ) {
		preg_match_all( "/\{var:([a-z0-9_]+)\}/i", $tpl, $matches );

		foreach( $matches[0] as $i => $match ) {
			$v = strtolower( $matches[1][$i] );
			$r = "";
			if( isset( $this->var[$v] ) ) {
				if( !empty( $this->var[$v] ) )
					$r = $this->var[$v];
			}
			if( isset( $GLOBALS[$v] ) ) {
				if( !empty( $GLOBALS[$v] ) )
					$r = $GLOBALS[$v];
			}

			$tpl = str_replace( $match, $r, $tpl );
		}

		return $tpl;
	}

	public function set( $key, $value ) {
		$this->var[$key] = $value;
	}

	public function put( $key, $value ) {
		if( !isset( $this->var[$key] ) )
			$this->var[$key] = '';
		$this->var[$key] .= $value;
	}

}