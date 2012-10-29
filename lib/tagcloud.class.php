<?php

class tagcloud {

	private $library;
	private $tags = array();
	private $mintags = 5;
	private $maxtags = 30;
	private $blacklist = array();

	public function __construct( $library ) {
		global $config;

		$this->mintags = $config->get( "mintags" );
		$this->maxtags = $config->get( "maxtags" );
		$this->blacklist = json_decode( $config->get( "tagblacklist" ) );

		$this->library = $library;
	}

	public function countTags() {
		$tags = $this->library->getTags();

		foreach( $tags as $tag ) {
			if( !isset( $this->tags[$tag] ) )
 				$this->tags[$tag] = 0;

			$this->tags[$tag]++;
		}

		foreach( $this->blacklist as $bltag ) {
			unset( $this->tags[$bltag] );
		}

		if( isset( $_GET["tag"] ) && !empty( $_GET["tag"]) ) {
			$this->library->filterByTag( $_GET["tag"] );
		}
	}


	public function generateTagCloud() {
		$ret = '';

		if( count( $this->tags ) > 0 )  {
			$min = min( $this->tags );
			if( $min < $this->mintags )
				$min = $this->mintags;

			$max = max( $this->tags );
			if( $max > $this->maxtags )
				$max = $this->maxtags;

			$range = $max - $min;
			if( $range == 0 ) {
				$range = 1;
				$min = 0;
			}

			$more = '';

			foreach( $this->tags as $tag => $count ) {
				if( $count > $this->maxtags )
					$count = $this->maxtags;

				if( $count >= $min ) $fsize = round( 15 / $range * ($count-$min) );
				else $fsize = -2;

				$selected = ( isset( $_GET["tag"] ) && $tag == $_GET["tag"] );
				$r = ' <a href="index.php?' . ( isset( $_GET["page"] ) ? 'page=' . $_GET["page"] .'&' : '' ) . ( !$selected ? 'tag=' . urlencode( $tag ) : '' ) . '" style="font-size:' . (10+$fsize) . 'pt"' . ( $selected ? ' class="selected"' : '') . '>' . $tag . '</a>';

				if( $count >= $min ) {
					$ret .= $r;
				} else {
					$more .= $r;
				}
			}
		}

		if( !empty( $more ) ) {
			if( !empty( $ret ) )
				$ret .= '<br>';
			$ret .= '<a href="#" id="morelink" style="display:none;">[+] mehr</a><div id="moretags">' . $more . '</div>';
		}

		return $ret;
	}

}