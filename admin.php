<?php
error_reporting( E_ALL );
session_start();

if( isset( $_GET["logout"] ) ) {
	session_unset();
	header( "Location: admin.php" );
	die();
}

// Wenn Sie diesen Boolean auf true setzten wird die SSL-Verbindung überprüft. Dann muss aber ein SSL-Zertifikat vorliegen.
$verifysslpeer = false;


if( !file_exists( 'lib/mysql.class.php' ) ) copy( 'lib/mysql.class.blank.php', 'lib/mysql.class.php' );

require_once 'lib/functions.php';
require_once 'lib/mysql.class.php';
require_once 'lib/auth.class.php';
require_once 'lib/library.class.php';
require_once 'lib/config.class.php';

require_once 'lib/template.class.php';


$mysql	= new mysql( true );

$content = '';


$config = new config( $mysql );
$template = new template( $config );

if( $mysql->isConnected() ) {

	if( !isset( $_GET["install"]) &&  $mysql->checkTables( true ) ) {
		unset( $_SESSION["install"] );

		$auth = new instagramAuth( $mysql, $config );
		if( isset( $_GET["code"] ) ) {
			$access_token = $auth->getAccessToken( $_GET["code"] );
			if( $access_token !== false ) {
				$_SESSION["access_token"] = $access_token;
				header( "Location: admin.php" );
			}
		}

		if( !isset( $_SESSION["access_token"] ) || $_SESSION["access_token"] != $auth->getAccessToken() ) {
			$content .= '<h1>Admin-Panel</h1>
			<a href="' . $auth->getAuthorizeLink() . '">Über Instagram einloggen »</a>';
		} else {
			$content .= '
			<h1>Admin-Panel</h1>
			<b>Willkommen im Administrationspanel!</b><br>
			<br>';

			$library = new library( $mysql );

			if( !isset( $_GET["a"] ) ) {
				$_GET["a"] = 0;
			}

			switch( $_GET["a"] ) {
				case "1":
					$token = $auth->getAccessToken();
					$userid = $auth->getUserId();

					function getPictures( $max_id = 0 ) {
						global $config, $library, $verifysslpeer, $token, $userid;

						$ch = curl_init( "https://api.instagram.com/v1/users/" . $userid . "/media/recent?count=100&access_token=" . $token . ( $max_id == 0 ? '' : "&max_id=" . $max_id ) );
						curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, $verifysslpeer );
						curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
						curl_setopt_array( $ch, $config->get( "additional_curlopt" ) );
						$result = curl_exec( $ch );
						curl_close( $ch );

						$re = json_decode( $result, true );

						$continue = false;
						$lastid = "0";

						if( isset( $re["data"] ) ) {
							$continue = true;

							if( count( $re["data"] ) == 1 && $library->has( $re["data"][0]["id"] ) ) {
								$continue = false;
							}

							foreach( $re["data"] as $media ) {
								$pid = $media["id"];
								$lastid = $pid;
								$href = $media["images"]["standard_resolution"]["url"];
								$thumbnailhref = $media["images"]["thumbnail"]["url"];
								$title = ( isset( $media["caption"] ) ?  $media["caption"]["text"] : "" );
								$date = $media["created_time"];
								$tags = $media["tags"];
								$library->add( $pid, $href, $thumbnailhref, $title, $date, $tags );
							}
						}

						if( $continue && $lastid != "0" ) {
							getPictures( $lastid );
						}
					}

					if( $token !== false && $userid !== false ) {
						$mysql->query( "TRUNCATE `" . $mysql->prefix . "picture`" );
						getPictures();
					}
					break;
				case "2":
					$token = $auth->getAccessToken();
					$userid = $auth->getUserId();

					function getPictures( $min_id ) {
						global $config, $library, $verifysslpeer, $token, $userid;

						$ch = curl_init( "https://api.instagram.com/v1/users/" . $userid . "/media/recent?count=100&access_token=" . $token . "&min_id=" . $min_id );
						curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, $verifysslpeer );
						curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
						curl_setopt_array( $ch, $config->get( "additional_curlopt" ) );
						$result = curl_exec( $ch );
						curl_close( $ch );

						$re = json_decode( $result, true );

						if( isset( $re["data"] ) ) {
							foreach( $re["data"] as $media ) {
								$pid = $media["id"];
								$lastid = $pid;
								$href = $media["images"]["standard_resolution"]["url"];
								$thumbnailhref = $media["images"]["thumbnail"]["url"];
								$title = ( isset( $media["caption"] ) ?  $media["caption"]["text"] : "" );
								$date = $media["created_time"];
								$tags = $media["tags"];
								$library->add( $pid, $href, $thumbnailhref, $title, $date, $tags );
							}
						}
					}

					if( $token !== false && $userid !== false ) {
						getPictures( $library->getLatest() );
					}
					break;
				case 3:
					if( isset( $_POST["submit"] ) ) {
						if( isset( $_POST["pagetitle"] ) ) $config->set( "pagetitle", $_POST["pagetitle"] );
						if( isset( $_POST["description"] ) ) $config->set( "description", $_POST["description"] );
						if( isset( $_POST["keywords"] ) ) $config->set( "keywords", $_POST["keywords"] );
						if( isset( $_POST["headline"] ) ) $config->set( "headline", $_POST["headline"] );
						if( isset( $_POST["picturesperpage"] ) ) $config->set( "picturesperpage", $_POST["picturesperpage"] );

						if( isset( $_POST["autoloadnewimages"] ) )
							$config->set( "autoloadnewimages", true );
						else
							$config->set( "autoloadnewimages", false );

						if( isset( $_POST["showtagcloud"] ) )
							$config->set( "showtagcloud", true );
						else
							$config->set( "showtagcloud", false );

						if( isset( $_POST["showcomments"] ) )
							$config->set( "showcomments", true );
						else
							$config->set( "showcomments", false );

						if( isset( $_POST["mintags"] ) ) $config->set( "mintags", $_POST["mintags"] );
						if( isset( $_POST["maxtags"] ) ) $config->set( "maxtags", $_POST["maxtags"] );

						$tagblist = array();
						foreach( $_POST as $key => $post ) {
							if( substr( $key, 0, 4 ) == "blt_" ) {
								$tagblist[] = $post;
							}
						}
						$config->set( "tagblacklist", json_encode( $tagblist ) );
					}

					$tagblacklist = '<div id="config_blacklist">';
					$blstr = $config->get("tagblacklist");
					$bl = json_decode( $blstr, true );
					$c = 0;
					foreach( $bl as $blt ) {
						$tagblacklist .= '<span class="bltag">' . $blt . '<input type="hidden" name="blt_' . $c . '" value="' . $blt . '"><a href="#" class="del_bltag">x</a></span>';
						$c++;
					}
					$tagblacklist .= '<input type="text" id="newbltag"><input type="hidden" id="tagcount" value="' . $c . '"></div>';

					$content .= '
			<form action="admin.php?a=3" method="post">
				<table width="100%">
					<tr>
						<td width="200"valign="top"><b>Seitentitel:</b></td>
						<td valign="top">
							<input type="text" value="' . $config->get("pagetitle") . '" name="pagetitle" style="width:100%;"><br>
							<small>Titel der im Fenster oder Tab des Browsers angezeigt wird.</small>
						</td>
					</tr>
					<tr>
						<td width="200"valign="top"><b>Beschreibung:</b></td>
						<td valign="top">
							<input type="text" value="' . $config->get("description") . '" name="description" style="width:100%;"><br>
							<small>Seitenbeschreibung für HTML-Metadaten die für Suchmaschinen beim Katalogisieren helfen kann.</small>
						</td>
					</tr>
					<tr>
						<td width="200"valign="top"><b>Schlüsselwörter/Keywords:</b></td>
						<td valign="top">
							<input type="text" value="' . $config->get("keywords") . '" name="keywords" style="width:100%;"><br>
							<small>Schlüsselwörter/Keywords für HTML-Metadaten die für Suchmaschinen beim Katalogisieren helfen kann.</small>
						</td>
					</tr>
					<tr>
						<td width="200"valign="top"><b>Überschrift:</b></td>
						<td valign="top">
							<input type="text" value="' . $config->get("headline") . '" name="headline" style="width:100%;"><br>
							<small>Überschrift die über der Galerie angezeigt wird. Wenn leer wird nicht angezeigt.</small>
						</td>
					</tr>
					<tr>
						<td width="200"valign="top"><b>Bilder pro Seite:</b></td>
						<td valign="top">
							<input type="number" value="' . $config->get("picturesperpage") . '" name="picturesperpage" style="width:100%;"><br>
							<small>Anzahl der Bilder pro Seite. Je weniger Bilder desto schneller läd die Seite.</small>
						</td>
					</tr>
					<tr>
						<td width="200" valign="top"><b>Bilder automatisch laden:</b></td>
						<td valign="top">
							<input type="checkbox" ' . ( $config->get("autoloadnewimages") ? 'checked="checked"' : '' ) . '" name="autoloadnewimages"><br>
							<small>Sucht automatisch nach neuen Bildern wenn jemand die Seite besucht. Wenn dies abgeschaltet ist müssen neue Bilder über das Admin-Panel manuell geholt werden.</small>
						</td>
					</tr>
					<tr>
						<td width="200" valign="top"><b>TagCloud anzeigen:</b></td>
						<td valign="top">
							<input type="checkbox" ' . ( $config->get("showtagcloud") ? 'checked="checked"' : '' ) . '" name="showtagcloud"><br>
							<small>Zeigt die TagCloud unter der Galerie an.</small>
						</td>
					</tr>
					<tr>
						<td width="200" valign="top"><b>Kommentare und Likes anzeigen:</b></td>
						<td valign="top">
							<input type="checkbox" ' . ( $config->get("showcomments") ? 'checked="checked"' : '' ) . '" name="showcomments"><br>
							<small>Zeigt Kommentare und Likes in der vergrößerten Ansicht an.</small>
						</td>
					</tr>
					<tr>
						<td width="200" valign="top"><b>Min. Vorkommen eines Tags:</b></td>
						<td valign="top">
							<input type="number" value="' . $config->get("mintags") . '" name="mintags" style="width:100%;"><br>
							<small>Tag muss mindestens so oft vorkommen bevor es überhaupt in der Cloud angezeigt wird.</small>
						</td>
					</tr>
					<tr>
						<td width="200" valign="top"><b>Max. Vorkommen eines Tags:</b></td>
						<td valign="top">
							<input type="number" value="' . $config->get("maxtags") . '" name="maxtags" style="width:100%;"><br>
							<small>Tags die öfter vorkommen werden nicht noch größer angezeigt.</small>
						</td>
					</tr>
					<tr>
						<td width="200" valign="top"><b>Tag-Blacklist:</b></td>
						<td valign="top">
							' . $tagblacklist . '
							<small>Mit der Tag-Blacklist kannst du Tags ausblenden lassen. Gibt einfach ein Tag ein und drücke Enter um sie der Liste hinzuzufügen. Speichern nicht vergessen!</small>
						</td>
					</tr>
					<tr>
						<td><a href="admin.php">« Zurück</a></td>
						<td><input type="submit" name="submit" value="Speichern" style="width:50%;"></td>
					</tr>
				</table>
			</form>';
					break;
				case 4:
					if( isset( $_POST["pid"] ) ) {
						$blacklist = json_decode( $config->get( "imageblacklist" ), true );
						if( in_array( $_POST["pid"], $blacklist ) ) {
							foreach( $blacklist as $k => $b ) {
								if( $b == $_POST["pid"] ) {
									unset( $blacklist[$k] );
								}
							}
							echo "successremove";
						} else {
							$blacklist[] = $_POST["pid"];
							echo "successadd";
						}
						$config->set( "imageblacklist", json_encode( $blacklist ) );
						die(  );
					} else {
						$content .= "missing pid";
					}
					break;
			}

			if( !isset( $_GET["a"] ) || $_GET["a"] < 3 )
				$content .= '
			Anzahl Bilder in Datenbank: ' . count( $mysql->query( "SELECT * FROM `" . $mysql->prefix . "picture`", true ) ) . '<br>
			» <a href="admin.php?a=1">Alle Bilder neu holen</a> (Löscht alle Bilder in der Datenbank und holt alle aus Instagram neu)<br>
			» <a href="admin.php?a=2">Neuste Bilder holen</a> (Holt alle Bilder ab dem neusten in der Datenbank)<br>
			» <a href="admin.php?a=3">Konfiguration bearbeiten</a> (Oberfläche zur Bearbeitung der Konfiguration)<br>
			<br>
			» <a href="index.php?logout">Abmelden</a> (Aus dem Adminpanel ausloggen)
			';


		}

	} else {
		if( !isset( $_GET["install"] ) ) {
			$_GET["install"] = 1;
		}

		switch( $_GET["install"] ) {
			default:
				$pre = $mysql->getPrefix();
				$content = '
				<h1>InstagramGallery Installation</h1>
				<b>Deine MySQL-Daten:</b><br>
				<table>
					<tr><td><b>Host:</b></td><td>' . $mysql->getHost() . '</td></tr>
					<tr><td><b>Benutzer:</b></td><td>' . $mysql->getUser() . '</td></tr>
					<tr><td><b>Passwort:</b></td><td><i style="color:#999999">Geheim</i></td></tr>
					<tr><td><b>Datenbank:</b></td><td>' . $mysql->getDatabase() . '</td></tr>
					<tr><td><b>Tabellenprefix:</b></td><td>' . ( empty( $pre ) ? '<i style="color:#999999">Keines</i>' : $pre ) . '</td></tr>
				</table>
				<br>
				Dementsprechent werden folgende Datenbanken angelegt:<br>
				<ol>
					<li>' . $pre . 'pictures</li>
					<li>' . $pre . 'settings</li>
				</ol>
				<br>
				<a href="admin.php?install=2">OK, Datenbanken jetzt erstellen! »</a><br>
				<br>
				<b>Warum bin ich hier?</b><br>
				Du bist hier in der Installation da das Programm nicht alle erforderlichen Datenbanken finden konnte.';
				$_SESSION["install"] = true;
				break;
			case 2:
				if( isset( $_SESSION["install"] ) ) {

					if( !isset( $_GET["nosql"] ) ) {
						$mysql->query( "CREATE TABLE IF NOT EXISTS `" . $mysql->getPrefix() . "picture` (
											`pid` varchar(100) NOT NULL,
											`href` varchar(200) NOT NULL,
											`thumbnailhref` varchar(200) NOT NULL,
											`title` text NOT NULL,
											`date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
											`tags` TEXT NOT NULL,
											PRIMARY KEY (`pid`)
										) ENGINE=InnoDB DEFAULT CHARSET=latin1" );
						$mysql->query( "CREATE TABLE IF NOT EXISTS `" . $mysql->getPrefix() . "settings` (
											`key` varchar(100) NOT NULL,
											`value` text NOT NULL,
											PRIMARY KEY (`key`)
										) ENGINE=InnoDB DEFAULT CHARSET=latin1" );
					}

					$content .= '
					<h1>InstagramGallery Installation</h1>
					<b>Datenbanken erstellt!</b><br>
					<br>
					Um die Installation abzuschließen muss die Datenbank noch mit ein paar wenigen Daten befüllt werden.<br>
					Dafür ist es Notwenig dass du bei Instagram diese Applikation registrierst.<br>
					Dies kannst du auf der <a href="http://instagr.am/developer/register/" target="_blank">Instagram-Developer-Seite</a> machen.<br>
					<br>
					Verwende zur Registrierung folgende Redirect-URL: <b>http://' .  $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . '</b><br>
					<br>
					Wenn du soweit bist gib unten bitte die Client-ID und den dazugehörigen Client-Secret:<br>
					<form action="admin.php?install=3" method="post">
						<table>
							<tr><td><b>Client-ID</b></td><td><input type="text" name="client_id"></td></tr>
							<tr><td><b>Client-Secret</b></td><td><input type="text" name="client_secret"></td></tr>
							<tr><td colspan="2" align="center"><input type="submit" value="Fertig »"></td></tr>
						</table>
					</form>';
				}

				break;
			case 3:
				if( isset( $_SESSION["install"] ) ) {
					if( !isset( $_GET["nosql"] ) ) {

						if( !isset( $_POST["client_id"] ) || !isset( $_POST["client_secret"] ) || empty( $_POST["client_id"] ) || empty( $_POST["client_secret"] ) ) {
							header( "Location: admin.php?install=2&nosql=true" );
						}

						$mysql->query( "INSERT INTO `" . $mysql->prefix . "settings` ( `key`, `value` ) VALUES ( 'api_client_id', '" . escape( $_POST["client_id"] ) . "' )" );
						$mysql->query( "INSERT INTO `" . $mysql->prefix . "settings` ( `key`, `value` ) VALUES ( 'api_client_secret', '" . escape( $_POST["client_secret"] ) . "' )" );
						$mysql->query( "INSERT INTO `" . $mysql->prefix . "settings` ( `key`, `value` ) VALUES ( 'api_redirect_url', 'http://" .  escape( $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] ) . "' )" );
					}

					$auth = new instagramAuth( $mysql );

					$content .= '
					<h1>InstagramGallery Installation</h1>
					Nun fehlt nur noch eine Sache: Deine Instagram-User-Id. Damit du dich hier im Admin-Panel anmelden kannst<br>
					brauchen wir deine Instagram-User-Id. Diese ruft das Programm einfach ab indem du einen ersten Access-Token<br>
					abrufst. Dafür musst du nur folgendem Link folgen und dich auf der Instagram-Seite anmelden und die Applikation<br>
					Authorisieren.<br>
					<br>
					» <a href="' . $auth->getAuthorizeLink() . '">Über Instagram einloggen</a><br>
					<br>
					Der Access-Token wird überdies benötigt um deine Fotos von der Instagram-API abzurufen. Der Access-Token kann zu<br>
					unbestimmten Zeiten ablaufen. Logge dich daher regelmäßig im AdminPanel ein um einen neuen Access-Token anzufordern.
					';
				}
				break;
		}

	}

} else {
	$content = '
		<h1>InstagramGallery</h1>
		Es ist für das Programm unabdingbar dass eine MySQL-Datenbank vorhanden ist.<br>
		Damit das Programm hier weiterlaufen kann musst du in der Datei lib/mysql.class.php<br>
		bitte in die entsprechenden Felder die Verbindungsdaten angeben.<br>
		<br>
		<b>Dies ist auch für die Installation notwendig.</b><br>
		<br>
		<a href="admin.php">Fertig!</a>';
}


echo $template->get( "tpl/admin.tpl" );