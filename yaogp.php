<?php
/*
Plugin Name: Yet another Open Graph Plugin
Plugin URI: http://scm.schneidr.de/yaogp
Description: This plugin adds Open Graph meta tags to your WordPress site
Version: 0.1
Author: Gerald Schneider
Author URI: http://schneidr.de/
*/

/*
<meta property="og:type" content="article"/>
<meta property="og:title" content="squeezelite unter Windows"/>
<meta property="og:url" content="http://schneidr.de/2013/09/squeezelite-unter-windows/"/>
<meta property="og:description" content="Unter Windows ist die Installation ähnlich. Die exe-Datei für Windows herunterladen, yusätzlich brauchen wir noch die PortAudio.dll sowie die libmpg123-0.dll. Beide Dateien können einfach in das Verzeichnis der squeezelite .exe-Datei gelegt werden.
<meta property="og:site_name" content="Gerald Schneider"/>
<meta property="og:locale" content="de_DE"/>
<meta property="og:locale:alternate" content="de_DE"/>

<meta property="og:image" content="http://schneidr.de/wp-content/uploads/2013/09/squeezelite_folder.png"/>

Die Parameter sind die gleichen wie unter Linux, ich musste hier noch mit -o das richtige Audio-Device angeben.

Um herauszufinden welche Bibl ..."/>
*/

add_action('wp_head', 'yaogp_head');

/**
 * Inserts the meta tags in the header of the page
 *
 * @return string
 * @author Gerald Schneider
 **/
function yaogp_head()
{
	global $post;
	//var_dump($post);
	yaogp_meta("site_name", get_option("blogname"));
	yaogp_meta("locale", get_locale());
	yaogp_meta("locale:alternate", get_locale());
	if ( is_front_page() || is_home() ) {
		// front page
		yaogp_meta("description", get_option("blogdescription"));
		yaogp_meta("type", "website");
	}
	elseif ( is_single() || is_page() ) {
		// single post or page
		setup_postdata( $post );
		yaogp_meta("title", get_the_title());
		yaogp_meta("url", get_permalink());
		yaogp_meta("description", get_the_excerpt());
		yaogp_meta("type", "article");
	}
}

function yaogp_meta($name, $content) {
	echo sprintf("\t<meta property=\"og:%s\" content=\"%s\" />\n", $name, $content);
}

?>