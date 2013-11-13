<?php
/*
Plugin Name: Yet another Open Graph Plugin
Plugin URI: http://scm.schneidr.de/yaogp
Description: This plugin adds Open Graph meta tags to your WordPress site
Version: 0.1
Author: Gerald Schneider
Author URI: http://schneidr.de/
*/

/**
 * TODO
 * <meta property="og:image" content="http://schneidr.de/wp-content/uploads/2013/09/squeezelite_folder.png"/>
 * og:updated_time
 * og:video
 * og:audio
 * fb:app_id
 * fb:admins
 * fb:profile
 * https://developers.facebook.com/docs/reference/opengraph/object-type/object/
 * Localization
 **/

class YaOGP {

	var $default_image;
	var $image_size;

	public function __construct() {
		$this->default_image = "http://test.schneidr.de/wp-content/themes/twentythirteen/images/headers/circle.png";
		$this->image_size = 500;
		// load_plugin_textdomain( 'demo-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
        add_action('wp_head', array($this, 'head'));
    }

	function head() {
		global $post;
		$this->yaogp_meta( "site_name", get_option("blogname") );
		$this->yaogp_meta( "locale", get_locale() );
		$this->yaogp_meta( "locale:alternate", get_locale() );
		if ( is_front_page() || is_home() ) {
			// front page
			$this->yaogp_meta( "title", get_option("blogname") );
			$this->yaogp_meta( "url",get_option("siteurl") );
			$this->yaogp_meta( "description", get_option("blogdescription") );
			$this->yaogp_meta( "type", "website" );
			$this->yaogp_meta( "image", $this->default_image );
		}
		elseif ( is_single() || is_page() ) {
			// single post or page
			setup_postdata( $post );
			$this->yaogp_meta( "title", get_the_title() );
			$this->yaogp_meta( "url", get_permalink() );
			$this->yaogp_meta( "description", get_the_excerpt() );
			$this->yaogp_meta( "type", "article" );
			$images = array();
			$thumb_ID = 0;
			if ( has_post_thumbnail() ) {
				//$images[] = get_the_post_thumbnail();
				$thumb_ID = get_post_thumbnail_id( $post->ID );
				$img = wp_get_attachment_image_src( $thumb_ID, 'yaogp_thumb' );
				$images[] = $img[0];
			}
			$post_images = get_posts( array(
				'post_parent' => $post->ID,
				'post_type' => 'attachment',
				'numberposts' => -1,
				'post_mime_type' => 'image',
				'exclude' => $thumb_ID ) );
			foreach ($post_images as $image) {
				$img = wp_get_attachment_image_src( $image->ID, 'yaogp_thumb' );
				$images[] = $img[0];
			}
			if ( sizeof( $images ) == 0 ) {
				$images[] = $this->default_image;
			}
			foreach ( $images as $image ) {
				$this->yaogp_meta( "image", $image );
			}
		}
	}

	function yaogp_meta($name, $content) {
		echo sprintf("\t<meta property=\"og:%s\" content=\"%s\" />\n", $name, $content);
	}

}

if ( function_exists( 'add_image_size' ) ) { 
	add_image_size( 'yaogp_thumb', 500, 500, true );
}

$yaogp = new YaOGP();

?>