<?php
error_reporting( E_ALL );
session_start();

// Wenn Sie diesen Boolean auf true setzten wird die SSL-Verbindung überprüft. Dann muss aber ein SSL-Zertifikat vorliegen.
$verifysslpeer = false;

if( !file_exists( 'lib/mysql.class.php' ) ) die();
require_once 'lib/functions.php';
require_once 'lib/mysql.class.php';
require_once 'lib/auth.class.php';
require_once 'lib/library.class.php';
require_once 'lib/config.class.php';
require_once 'lib/tagcloud.class.php';

require_once 'lib/template.class.php';

$mysql		= new mysql();
$config		= new config( $mysql );
$auth		= new instagramAuth( $mysql, $config );
$library	= new library( $mysql );
$tagcloud	= new tagcloud( $library );

$template	= new template( $config );

if( isset( $_GET["code"] ) ) {
	$code = $_GET["code"];

	$auth->getAccessToken( $code );

	header( "Location: " . preg_replace( "/code=([^&]+)(&|$)/", "", $_SERVER["REQUEST_URI"] ) );
}

$userid = $auth->getUserId();
if( $config->get("autoloadnewimages") ) {
	$token = $auth->getAccessToken();
	if( $token !== false && $userid !== false ) {
		$ch = curl_init( "https://api.instagram.com/v1/users/" . $userid . "/media/recent?count=100&access_token=" . $token ."&min_id=" . $library->getLatest() );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, $verifysslpeer );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt_array( $ch, $config->get( "additional_curlopt" ) );
		$result = curl_exec( $ch );
		curl_close( $ch );

		$re = json_decode( $result, true );

		if( isset( $re["data"] ) ) {
			foreach( $re["data"] as $media ) {
				$pid = $media["id"];
				$href = $media["images"]["standard_resolution"]["url"];
				$thumbnailhref = $media["images"]["thumbnail"]["url"];
				$title = ( isset( $media["caption"] ) ?  $media["caption"]["text"] : "" );
				$date = $media["created_time"];
				$tags = $media["tags"];
				$library->add( $pid, $href, $thumbnailhref, $title, $date, $tags );
			}
		}
	}
}


if( $userid !== false ) {
	$ch = curl_init( "https://api.instagram.com/v1/users/" . $userid . "?client_id=" . $auth->getClientId() );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, $verifysslpeer );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt_array( $ch, $config->get( "additional_curlopt" ) );
	$result = curl_exec( $ch );
	curl_close( $ch );

	$re = json_decode( $result, true );

	if( isset( $re["data"] ) ) {
		$username = $re["data"]["username"];
		$full_name = $re["data"]["full_name"];
		$profile_picture = $re["data"]["profile_picture"];
		$bio = $re["data"]["bio"];
		$website = $re["data"]["website"];
		$pictures = $re["data"]["counts"]["media"];
	}
}



$showcomments = $config->get("showcomments");
$headline = $config->get("headline");

if( isset( $username ) ) {
	if( !empty( $full_name ) ) {
		$username = $full_name . ' (' .$username .')';
	}
}


$showtagcloud = $config->get("showtagcloud");
if( $showtagcloud )
	$tagcloud->countTags();

if( !isset( $_GET["page"] ) ) {
	$_GET["page"] = 1;
}

$page = $library->getPage( $_GET["page"] );


if( isset( $_SESSION["access_token"] ) && $_SESSION["access_token"] == $auth->getAccessToken() ) {
	$adminloggedin = true;
}

$gallery = '';
$imageblacklist = json_decode( $config->get( "imageblacklist" ), true );
foreach( $page as $pic ) {
	if( isset( $pid ) ) unset( $pid );
	$pid = $pic["pid"];
	if( isset( $title ) ) unset( $title );
	if( !$showcomments ) $title = $pic["title"];
	if( isset( $thumbnailhref ) ) unset( $thumbnailhref );
	$thumbnailhref = $pic["thumbnailhref"];

	if( isset( $blacklisted ) ) unset( $blacklisted );
	if( in_array( $pid, $imageblacklist ) ) {
		$blacklisted = true;
	}

	$gallery .= $template->get( "tpl/galleryimage.tpl" );
}

$prev_page = '<a ' . ( $_GET["page"] > 1 ? 'href="index.php?page=' . ($_GET["page"]-1) . ( isset( $_GET["tag"] ) ? '&tag=' . $_GET["tag"] : '' ) . '"' : 'style="color:#999999;text-decoration:none;"' ) . '>« Vorherige Seite</a>';
$next_page = '<a ' . ( ( count( $library->getPage( $_GET["page"]+1 ) ) > 0 ) ? 'href="index.php?page=' . ($_GET["page"]+1). ( isset( $_GET["tag"] ) ? '&tag=' . $_GET["tag"] : '' ) . '"' : 'style="color:#999999;text-decoration:none;"' ) . '>Nächste Seite »</a>';

$tc = '';
if( $showtagcloud )
	$tc = $tagcloud->generateTagCloud();

echo $template->get( "tpl/html.tpl" );

?>