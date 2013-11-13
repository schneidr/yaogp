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
 **/

class YaOGP {

	var $default_image;
	var $image_size;
	var $fb_app_id;
	var $fb_admin_id;

	public function __construct() {
		// load_plugin_textdomain( 'demo-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
		register_activation_hook( __FILE__, array( $this, 'init' ) );
		add_action( 'regenerate_thumbnails', array($this, 'regenerate_all_attachment_sizes' ) );
		add_action( 'wp_head', array( $this, 'head' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action('admin_print_scripts', array( $this, 'admin_scripts' ) );

		$this->default_image = get_option("yaogp_default_image");
		$this->image_size = get_option("yaogp_image_size", 500);
		$this->fb_app_id = get_option("yaogp_fb_app_id");
		$this->fb_admin_id = get_option("yaogp_fb_admin_id");
	}

	function head() {
		global $post;
		$this->yaogp_meta( "site_name", get_option("blogname") );
		$this->yaogp_meta( "locale", get_locale() );
		$this->yaogp_meta( "locale:alternate", get_locale() );
		if ( $this->fb_app_id != null )
			$this->yaogp_meta( "app_id", $this->fb_app_id, 'fb' );
		if ( $this->fb_admin_id != null )
			$this->yaogp_meta( "admins", $this->fb_admin_id, 'fb' );
		if ( is_front_page() || is_home() ) {
			// front page
			$this->yaogp_meta( "title", get_option("blogname") );
			$this->yaogp_meta( "url",get_option("siteurl") );
			$this->yaogp_meta( "description", get_option("blogdescription") );
			$this->yaogp_meta( "type", "website" );
			$img = wp_get_attachment_image_src( $this->default_image, 'yaogp_thumb' );
			$this->yaogp_meta( "image", $img[0] );
		}
		elseif ( is_single() || is_page() ) {
			// single post or page
			setup_postdata( $post );
			$this->yaogp_meta( "title", get_the_title() );
			$this->yaogp_meta( "url", get_permalink() );
			$this->yaogp_meta( "description", strip_tags( get_the_excerpt() ) );
			$this->yaogp_meta( "type", "article" );
			$images = array();
			if ( has_post_thumbnail() ) {
				$images[] = get_post_thumbnail_id( $post->ID );
			}

			// galleries
			if( has_shortcode( $post->post_content, 'gallery' ) ) {
				$galleries = get_post_galleries( $post, false );
				foreach ( $galleries as $gallery ) {
					$ids = explode( ',', $gallery['ids'] );
					$images = array_merge( $images, $ids );
				}
			}

			// single images in the post
			preg_match_all("/wp-image-(\d+)/", $post->post_content, $imgs );
			foreach ($imgs[1] as $img) {
				$images[] = $img;
			}

			/* gets all images attached to the post
			 * not sure if this is a good idea, there might be images attached
			 * which were not supposed to be published
			$post_images = get_posts( array(
				'post_parent' => $post->ID,
				'post_type' => 'attachment',
				'numberposts' => -1,
				'post_mime_type' => 'image',
				'exclude' => $images ) );
			foreach ( $post_images as $image ) {
				$images[] = $image->ID;
			}*/
			if ( sizeof( $images ) == 0 && $this->default_image != null) {
				$images[] = $this->default_image;
			}
			// remove duplicate images
			$images = array_unique( $images );
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
		wp_schedule_single_event( time(), array( $this, 'regenerate_thumbnails' ) );
		// $this->regenerate_all_attachment_sizes();
	}

	function yaogp_meta( $name, $content, $prefix = "og" ) {
		echo sprintf( "\t<meta property=\"%s:%s\" content=\"%s\" />\n", $prefix, $name, $content );
	}

	function regenerate_all_attachment_sizes() {
		include_once( ABSPATH . 'wp-admin/includes/image.php' );
		$args = array( 'post_type' => 'attachment', 'numberposts' => -1, 'post_status' => null, 'post_parent' => null, 'post_mime_type' => 'image' ); 
		$attachments = get_posts( $args );
		if ($attachments) {
			foreach ( $attachments as $post ) {
				$file = get_attached_file( $post->ID );
				wp_update_attachment_metadata( $post->ID, wp_generate_attachment_metadata( $post->ID, $file ) );
			}
		}
	}

	function admin_scripts() {
		wp_enqueue_media();
	}

	function admin_init() {
		register_setting( 'yaogp', 'yaogp_default_image' );
		register_setting( 'yaogp', 'yaogp_image_size' );
		register_setting( 'yaogp', 'yaogp_fb_app_id' );
		register_setting( 'yaogp', 'yaogp_fb_admin_id' );
	}

	function admin_menu() {
		add_submenu_page( 'options-general.php', 'YaOGP Settings', 'YaOGP', 'administrator', __FILE__, array( $this, 'settings_page' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	function settings_page() {
		$img = wp_get_attachment_image_src( get_option('yaogp_default_image'), 'thumbnail' );
		$default_image_url = ($img) ? $img[0] : plugin_dir_url( __FILE__ ) . 'no_image.png';
		?>
		<div class="wrap">
			<h2>Yet another Open Graph Plugin</h2>

			<form method="post" action="options.php">
				<?php settings_fields( 'yaogp' ); ?>
				<?php do_settings_sections( 'yaogp' ); ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">Default image</th>
						<td>
							<img src="<?php echo $default_image_url; ?>" id="default_image" width="150" />
							<input type="hidden" name="yaogp_default_image" id="yaogp_default_image" value="<?php echo get_option('yaogp_default_image', $this->default_image); ?>" />
							<p class="description">This is the default image that is provided when your post or page doesn't contain any images. It is used on the front page as well. Click on the image to change it.</p>
						</td>
					</tr>

					<!-- <tr valign="top">
						<th scope="row">Image size</th>
						<td><input type="text" name="yaogp_image_size" value="<?php echo get_option('yaogp_image_size', $this->image_size); ?>" />
							<p class="description">The size of the thumbnail that is provided int the og:image tag. Facebook requires a minimum of 200px, the default value is 500px.</p>
						</td>
					</tr> -->

					<tr valign="top">
						<th scope="row">Facebook App-ID</th>
						<td><input type="text" name="yaogp_fb_app_id" value="<?php echo get_option('yaogp_fb_app_id', $this->fb_app_id); ?>" />
							<p class="description">You can provide the ID of a Facebook App here. I have no idea what it is used for.</p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">Facebook Admin-IDs</th>
						<td><input type="text" name="yaogp_fb_admin_id" value="<?php echo get_option('yaogp_fb_admin_id', $this->fb_admin_id); ?>" />
							<p class="description">You can provide the Facebook Admin ID here. Multiple Admins can be separated with a comma. You can find your ID here: http://graph.facebook.com/&lt;your-profile&gt;. I have no idea what it is used for either.</p>
						</td>
					</tr>
				</table>
				<script type="text/javascript">
				jQuery(document).ready(function($) {
					var custom_uploader;

					$('#default_image').click(function(e) {
				        e.preventDefault();
				        if (custom_uploader) {
				            custom_uploader.open();
				            return;
				        }
				        custom_uploader = wp.media.frames.file_frame = wp.media({
				            title: 'Choose Image',
				            button: {
				                text: 'Choose Image'
				            },
				            multiple: false
				        });
				        custom_uploader.on('select', function() {
				            attachment = custom_uploader.state().get('selection').first().toJSON();
				            console.log(attachment);
				            $('#yaogp_default_image').val(attachment.id);
							$('#default_image').attr('src', attachment.url);
				        });
				        custom_uploader.open();
				    });
				});
				</script>

				<?php submit_button(); ?>

			</form>
			<p class="description">You can test the result of this plugin in the <a href="https://developers.facebook.com/tools/debug" target="_blank">Facebook Developer Debugger</a> | For feature requests and bug reports feel free to <a href="http://scm.schneidr.de/yaogp/issues" target="_blank">open an issue in the bugtracker</a>.</p>

		</div>
		<?php
	}

}

$yaogp = new YaOGP();


?>