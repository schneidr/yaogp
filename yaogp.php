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
 * og:updated_time
 * og:video
 * og:audio
 * fb:profile
 * https://developers.facebook.com/docs/reference/opengraph/object-type/object/
 * Localization
 * admin ui
 *  - default image URL
 *  - Facebook App ID
 *  - Facebook Admin ID
 **/

class YaOGP {

	var $default_image = null;
	var $image_size = 500;
	var $fb_app_id = null;
	var $fb_admin_id = null;

	public function __construct() {
		// load_plugin_textdomain( 'demo-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
		register_activation_hook( __FILE__, array( $this, 'init' ) );
        add_action('wp_head', array($this, 'head'));
    }

	function head() {
		global $post;
		$this->yaogp_meta( "site_name", get_option("blogname") );
		$this->yaogp_meta( "locale", get_locale() );
		$this->yaogp_meta( "locale:alternate", get_locale() );
		if ($this->fb_app_id != null)
			$this->yaogp_meta( "fb:app_id", $this->fb_app_id );
		if ($this->fb_admin_id != null)
			$this->yaogp_meta( "fb:admins", $this->fb_admin_id );
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
			if ( has_post_thumbnail() ) {
				$images[] = get_post_thumbnail_id( $post->ID );
			}

			// galleries
			$pattern = get_shortcode_regex();
			if ( preg_match_all( '/'. $pattern .'/s', $post->post_content, $matches )
		        && array_key_exists( 2, $matches ) ) {
			        foreach ($matches[2] as $i => $tag) {
			        	if ($tag == 'gallery') {
			        		$ids = explode('"', $matches[3][$i]);
			        		$ids = explode(',', $ids[1]);
			        		$images = array_merge($images, $ids);
			        	}
			        }
			    }

			/*$post_images = get_posts( array(
				'post_parent' => $post->ID,
				'post_type' => 'attachment',
				'numberposts' => -1,
				'post_mime_type' => 'image',
				'exclude' => $thumb_ID ) );
			foreach ($post_images as $image) {
				$img = wp_get_attachment_image_src( $image->ID, 'yaogp_thumb' );
				$images[] = $img[0];
			}*/
			if ( sizeof( $images ) == 0 && $this->default_image != null) {
				$images[] = $this->default_image;
			}
			foreach ( $images as $image ) {
				$img = wp_get_attachment_image_src( $image, 'yaogp_thumb' );
				$this->yaogp_meta( "image", $img[0] );
				//$this->yaogp_meta( "image:width", $img[1] );
				//$this->yaogp_meta( "image:height", $img[2] );
			}
		}

	}

	function init() {
		if ( function_exists( 'add_image_size' ) ) { 
			add_image_size( 'yaogp_thumb', $this->image_size, $this->image_size, true );
		}
	}

	function yaogp_meta($name, $content) {
		echo sprintf("\t<meta property=\"og:%s\" content=\"%s\" />\n", $name, $content);
	}

}

$yaogp = new YaOGP();

?>