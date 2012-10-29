<?php
error_reporting( E_ALL );

// Wenn Sie diesen Boolean auf true setzten wird die SSL-Verbindung überprüft. Dann muss aber ein SSL-Zertifikat vorliegen.
$verifysslpeer = false;

if( !file_exists( 'lib/mysql.class.php' ) ) die();

if( isset( $_GET["pid"] ) && !empty( $_GET["pid"] ) ) {
	$pid = $_GET["pid"];

	require_once 'lib/functions.php';
	require_once 'lib/mysql.class.php';
	require_once 'lib/auth.class.php';
	require_once 'lib/library.class.php';
	require_once 'lib/config.class.php';

	$config = array();
	if( file_exists( 'lib/config.ini' ) ) {
		$config = parse_ini_file( 'lib/config.ini' );
		if( isset( $config["pagetitle"] ) ) {
			$pagetitle = $config["pagetitle"];
		}
	}

	$mysql	= new mysql();
	$config	= new config( $mysql );
	$auth	= new instagramAuth( $mysql, $config );
	$library= new library( $mysql );

	$media = $library->getPicture( $pid );

	$showcomments = $config->get("showcomments");

	if( $showcomments ) {
		$ch = curl_init( "https://api.instagram.com/v1/media/" . $pid . "?client_id=" . $auth->getClientId() );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, $verifysslpeer );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt_array( $ch, $config->get( "additional_curlopt" ) );
		$result = curl_exec( $ch );
		curl_close( $ch );
		$re = json_decode( $result, true );

		$comments = '';
		if( isset( $re["data"] ) ) {
			$library->renewTags( $pid, $re["data"]["tags"] );


			$ch = curl_init( "https://api.instagram.com/v1/media/" . $pid . "/likes?client_id=" . $auth->getClientId() );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, $verifysslpeer );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt_array( $ch, $config->get( "additional_curlopt" ) );
			$result = curl_exec( $ch );
			curl_close( $ch );
			$rel = json_decode( $result, true );

			if( count( $rel["data"] ) > 0 ) {
				$comments .= '<div class="likes">';
				$likes = $rel["data"];
				$comments .= '<img class="herz" src="herz.png" alt="">';
				foreach( $likes as $like ) {
					$comments .= '<img class="like" src="' . $like["profile_picture"] . '" alt="' . $like["username"] . '" title="' . $like["username"] . '">';
				}

				$comments .= '<hr class="clear" /></div>';
			}

			$comments .= '<div style="padding: 5px;">Bild erstellt am ' . date("d.m.Y \u\m H:i", $re["data"]["created_time"] ) . '</div>';


			if( $re["data"]["caption"] != null ) {
				$comment = $re["data"]["caption"];
				$comments .= '
			<div class="comment">
				<div class="profile_picture"><img src="' . $comment["from"]["profile_picture"] . '" alt=""></div>
				<div class="commenttext">
					<b>' . $comment["from"]["username"] . '</b><br>
					' . $comment["text"] . '
				</div>
				<div class="commentdate">' . ( !empty( $comment["from"]["full_name"] ) ? $comment["from"]["full_name"] . '<br>' : '' ) . date( "d.m.Y H:i", $comment["created_time"]) . '</div>
			</div>';
			}

			$ch = curl_init( "https://api.instagram.com/v1/media/" . $pid . "/comments?client_id=" . $auth->getClientId() );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, $verifysslpeer );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt_array( $ch, $config->get( "additional_curlopt" ) );
			$result = curl_exec( $ch );
			curl_close( $ch );
			$rec = json_decode( $result, true );

			foreach( $rec["data"] as $comment ) {
				$comments .= '
			<div class="comment">
				<div class="profile_picture"><img src="' . $comment["from"]["profile_picture"] . '" alt=""></div>
				<div class="commenttext">
					<b>' . $comment["from"]["username"] . '</b><br>
					' . $comment["text"] . '
				</div>
				<div class="commentdate">' . ( !empty( $comment["from"]["full_name"] ) ? $comment["from"]["full_name"] . '<br>' : '' ) . date( "d.m.Y H:i", $comment["created_time"]) . '</div>
			</div>';
			}

			$comments .= '<a href="' . $re["data"]["link"] . '" style="margin-left:5px;" target="_blank" title="Öffnet neues Fenster">Zur Instagram-Seite »</a>';
		}


		echo '
<div class="mediawrap">
	<div class="mediaimage"><img src="' . $media["href"] . '" alt=""></div>
	<div class="mediacomments">' . $comments . '</div>
</div>';
	} else {
		echo '<div class="mediaimage"><img src="' . $media["href"] . '" alt=""></div>';
	}
}