<?php

class library {

	// Wird von Einstellung in der config.ini überschrieben
	public $picturesperpage = 25;

	private $mysql;

	private $pictures = array();

	public function __construct( $mysql ) {
		global $config, $auth;

		$this->picturesperpage = $config->get("picturesperpage");

		$this->mysql = $mysql;

		$this->pictures = $this->mysql->query( "SELECT * FROM `" . $this->mysql->prefix . "picture`", true );

		if( !isset( $_SESSION["access_token"] ) || $_SESSION["access_token"] != $auth->getAccessToken() ) {
			$blacklist = json_decode( $config->get( "imageblacklist" ), true );
			foreach( $this->pictures as $key => $pic ) {
				if( in_array( $pic["pid"], $blacklist ) ) {
					unset( $this->pictures[$key] );
				}
			}
		}

		foreach( $this->pictures as $key => $pic ) {
			$this->pictures[$key]["tags"] = explode( ",", $pic["tags"] );
		}

		usort( $this->pictures, "orderPictures" );
	}

	private $activeFilter = '';

	public function filterByTag( $tag ) {
		$this->activeFilter = $tag;
	}

	private function hasTag( $picture, $tag ) {
		return in_array( $tag, $picture["tags"] );
	}

	public function getPage( $page = 1 ) {
		$page = $page-1;

		if( $page < 0 ) {
			$page = 0;
		}

		$pageret = array();

		$filteredPictures = array();

		if( !empty( $this->activeFilter ) ) {
			foreach( $this->pictures as $pic ) {
				if( $this->hasTag( $pic, $this->activeFilter ) ) {
					$filteredPictures[] = $pic;
				}
			}
		} else {
			$filteredPictures = $this->pictures;
		}

		for(
			$i = ($page*$this->picturesperpage);
			$i < ($page*$this->picturesperpage)+$this->picturesperpage;
			$i++
		) {
			if( isset( $filteredPictures[$i] ) ) {
				$pageret[] = $filteredPictures[$i];
			} else break;
		}

		return $pageret;
	}

	public function has( $pid ) {
		$pics = $this->mysql->query( "SELECT * FROM `" . $this->mysql->prefix . "picture`", true );
		foreach( $pics as $pic ) {
			if( in_array( $pid, $pic ) )
				return true;
		}

		return false;
	}

	public function getPicture( $pid ) {
		foreach( $this->pictures as $pic ) {
			if( $pic["pid"] == $pid ) {
				return $pic;
			}
		}
	}

	public function add( $pid, $href, $thumbnailhref, $title, $date, $tags ) {
		if( !$this->has( $pid ) ) {
			$tagstring = "";
			foreach( $tags as $tag ) {
				$tagstring .= $tag . ',';
			}
			if( !empty( $tagstring ) )
				$tagstring = substr( $tagstring, 0, strlen( $tagstring )-1 );

			$this->mysql->query( "INSERT INTO `" . $this->mysql->prefix . "picture` ( `pid`, `href`, `thumbnailhref`, `title`, `date`, `tags` ) VALUES ( '" . escape( $pid ) . "', '" . escape( $href ) . "', '" . escape ( $thumbnailhref ) . "', '" . escape( $title ) . "', '" . date( "Y-m-d H:i:s", $date ) . "', '" . escape( $tagstring ) . "' )" );
			$this->pictures[] = array(
				"pid" => $pid,
				"href" => $href,
				"thumbnailhref" => $thumbnailhref,
				"title" => $title,
				"date" =>  date( "Y-m-d H:i:s", $date ),
				"tags" => $tags
			);

			usort( $this->pictures, "orderPictures" );
		}
	}

	public function renewTags( $pid, $tags ) {
		if( $this->has( $pid ) ) {
			$tagstring = "";
			foreach( $tags as $tag ) {
				$tagstring .= $tag . ',';
			}
			if( !empty( $tagstring ) )
				$tagstring = substr( $tagstring, 0, strlen( $tagstring )-1 );

			$this->mysql->query( "UPDATE `" . $this->mysql->prefix . "picture` SET `tags` = '" . escape( $tagstring ) . "' WHERE `pid` = '" . escape( $pid ) . "'" );
		}
	}

	public function getTags( ) {
		$tags = array();
		foreach( $this->pictures as $pic ) {
			foreach( $pic["tags"] as $tag )
				if( !empty( $tag ) ) $tags[] = $tag;
		}

		return $tags;
	}

	public function getLatest() {
		$ret = $this->mysql->query( "SELECT * FROM `" . $this->mysql->prefix . "picture`", true );
		if( count( $ret ) == 0 ) {
			return "";
		} else {
			return $ret[0]["pid"];
		}
	}

}