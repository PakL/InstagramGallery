<?php

class instagramAuth {

	private $client_id		= "";
	private $client_secret	= "";
	private $redirect_url	= "";

	private $mysql, $config;
	public $error = "Keine Fehler.";

	public function __construct( $mysql, $config ) {
		$this->mysql = $mysql;
		$this->config = $config;

		$result = $this->mysql->query( "SELECT * FROM `" . $this->mysql->prefix . "settings` WHERE `key` LIKE 'api_%'", true );

		foreach( $result as $row ) {
			switch( $row["key"] ) {
				case "api_client_id":		$this->client_id = $row["value"]; break;
				case "api_client_secret":	$this->client_secret = $row["value"]; break;
				case "api_redirect_url":	$this->redirect_url = $row["value"]; break;
			}
		}

		if(
			!isset( $this->client_id )		|| empty( $this->client_id )		||
			!isset( $this->client_secret )	|| empty( $this->client_secret )	||
			!isset( $this->redirect_url )	|| empty( $this->redirect_url )
		) {
			die( 'Bitte Installation durchführen.' );
		}
	}

	private $access_token = "";

	public function getAccessToken( $code = "" ) {
		global $verifysslpeer;

		if( empty( $code ) ) {
			if( !empty( $this->access_token ) ) {
				return $this->access_token;
			}

			$result = $this->mysql->query( "SELECT * FROM `" . $this->mysql->prefix . "settings` WHERE `key` = 'api_access_token'", true );
			if( count( $result ) == 1 ) {
				if( !empty( $result[0]["value"] ) )
					return $result[0]["value"];
			}
		} else {
			$ch = curl_init( "https://api.instagram.com/oauth/access_token" );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, $verifysslpeer );
			curl_setopt( $ch, CURLOPT_POST, true );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt_array( $ch, $this->config->get( "additional_curlopt" ) );

			$postdata = array(
				"client_id" => $this->client_id,
				"client_secret" => $this->client_secret,
				"grant_type" => "authorization_code",
				"redirect_uri" =>  $this->redirect_url,
				"code" => $code
			);

			curl_setopt( $ch, CURLOPT_POSTFIELDS, $postdata );

			$result = curl_exec( $ch );

			if( $result === false ) {
				die( curl_error( $ch ) );
			}
			curl_close( $ch );

			$return = json_decode( $result, true );

			if( isset( $return["access_token"] ) ) {
				$this->access_token = $return["access_token"];

				$res = $this->mysql->query( "SELECT * FROM `" . $this->mysql->prefix . "settings` WHERE `key` = 'api_user_id'", true );
				if( count( $res ) == 0 ) {
					$this->userid = $return["user"]["id"];
					$this->mysql->query( "INSERT INTO `" . $this->mysql->prefix . "settings` ( `key`, `value` ) VALUES ( 'api_user_id', '" . $this->userid . "' )" );
				} else {
					if( $return["user"]["id"] != $res[0]["value"] ) {
						return false;
					}
				}

				$res = $this->mysql->query( "SELECT * FROM `" . $this->mysql->prefix . "settings` WHERE `key` = 'api_access_token'", true );
				if( count( $res ) == 0 ) {
					$this->mysql->query( "INSERT INTO `" . $this->mysql->prefix . "settings` ( `key`, `value` ) VALUES ( 'api_access_token', '" . $this->access_token . "' )" );
				} else {
					$this->mysql->query( "UPDATE `" . $this->mysql->prefix . "settings` SET `value` = '" . $this->access_token . "' WHERE `key` = 'api_access_token'" );
				}

				return $this->access_token;
			} else {
				$error = $return["error_message"];
			}
		}

		return false;
	}

	private $userid = "";

	public function getUserId() {
		if( empty( $userid ) ) {
			$res = $this->mysql->query( "SELECT * FROM `" . $this->mysql->prefix . "settings` WHERE `key` = 'api_user_id'", true );
			if( count( $res ) == 1 ) {
				$this->userid = $res[0]["value"];
				return $this->userid;
			} else {
				return false;
			}
		} else {
			return $this->userid;
		}
	}

	public function getAuthorizeLink() {
		return "https://api.instagram.com/oauth/authorize/?client_id=" . $this->client_id . "&redirect_uri=" . urlencode( $this->redirect_url ) . "&response_type=code";
	}

	public function getClientId() {
		return $this->client_id;
	}

}