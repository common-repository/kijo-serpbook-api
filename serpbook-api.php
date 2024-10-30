<?php
/**
 * Plugin Name: KIJO Serpbook API
 * Plugin URI: https://kijo.co.uk/serpbook-api-wordpress-plugin/
 * Description: Display your SERP rankings right within any page or post using the popular Serpbook SEO rank tracker. Add your API key and then use our handy shortcode to display the SERP data table.
 * Version: 1.0.3
 * Author: KIJO
 * Author URI: http://kijo.co
 * Requires at least: 4.0.0
 * Tested up to: 4.0.0
 *
 * Text Domain: serpbook-api
 * Domain Path: /languages/
 *
 * @package KIJO_Serpbook_API
 * @category Core
 * @author Daveyon - KIJO
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Returns the main instance of KIJO_Serpbook_API to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object KIJO_Serpbook_API
 */
function KIJO_Serpbook_API() {
	return KIJO_Serpbook_API::instance();
} // End KIJO_Serpbook_API()


add_action( 'plugins_loaded', 'KIJO_Serpbook_API' );

/**
 * Loads the Serpbook API JavaScript
 * Uses prefix to prevent issues: "sbapi_"
 */

function ssrt_enqueue_js() {
	$prefix = "ssrt_";
  wp_enqueue_script( "${prefix}serpbook.js", plugins_url( './assets/js/serpbook.js', __FILE__ ) );
	wp_enqueue_script( "${prefix}underscore-1-8-3.js", plugins_url( './assets/js/underscore-1-8-3.min.js', __FILE__ ) );
	wp_enqueue_style("${prefix}serpbook.css", plugins_url( './assets/css/serpbook.css', __FILE__ ));

}

// Scripts to run on both admin and front end
$prefix = "ssrt_";
add_action('admin_enqueue_scripts', "ssrt_enqueue_js");
add_action('wp_enqueue_scripts', "ssrt_enqueue_js");



/* Fire our meta box setup function on the post editor screen. */
add_action( 'load-post.php', 'ssrt_smashing_post_meta_boxes_setup' );
add_action( 'load-post-new.php', 'ssrt_smashing_post_meta_boxes_setup' );

/* Meta box setup function. */
function ssrt_smashing_post_meta_boxes_setup() {

  /* Add meta boxes on the 'add_meta_boxes' hook. */
  add_action( 'add_meta_boxes', 'ssrt_smashing_add_post_meta_boxes' );

	/* Save post meta on the 'save_post' hook. */
  add_action( 'save_post', 'ssrt_smashing_save_post_class_meta', 10, 2 );
}

/* Create one or more meta boxes to be displayed on the post editor screen. */
function ssrt_smashing_add_post_meta_boxes() {

	$postype_ini = new KIJO_Serpbook_API();
	$custom_post_type = $postype_ini->get_value( 'select_custom_post_type', '', 'standard-fields' );


	// Select Meta Box
  add_meta_box(
    'sbapi_select_view_key',      // Unique ID
    esc_html__( 'Select Viewkey', 'example' ),    // Title
    'ssrt_smashing_post_class_meta_box',   // Callback function
    $custom_post_type,         // Admin page (or post type)
    'normal',         // Context
    'default'         // Priority
  );

	// Result Metabox
	add_meta_box(
    'sbapi_results_box',      // Unique ID
    esc_html__( 'Results', 'example' ),    // Title
    'ssrt_results_meta_box_content',   // Callback function
    $custom_post_type,         // Admin page (or post type)
    'normal',         // Context
    'default'         // Priority
  );
}

/* Save the meta box's post metadata. */
function ssrt_smashing_save_post_class_meta( $post_id, $post ) {

	// prevent XSS
	$_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

  /* Verify the nonce before proceeding. */
  if ( !isset( $_POST['smashing_post_class_nonce'] ) || !wp_verify_nonce( $_POST['smashing_post_class_nonce'], basename( __FILE__ ) ) )
    return $post_id;

  /* Get the post type object. */
  $post_type = get_post_type_object( $post->post_type );

  /* Check if the current user has permission to edit the post. */
  if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
    return $post_id;

  /* Get the posted data and sanitize. */
  $new_meta_value = ( isset( $_POST['sbapi_select_view_key'] ) ? sanitize_text_field($_POST['sbapi_select_view_key']) : '' );

  /* Get the meta key. */
  $meta_key = 'sbapi_select_view_key';

  /* Get the meta value of the custom field key. */
  $meta_value = get_post_meta( $post_id, $meta_key, true );


  /* If a new meta value was added and there was no previous value, add it. */
  if ( $new_meta_value && '' == $meta_value )
    add_post_meta( $post_id, $meta_key, $new_meta_value, true );

  /* If the new meta value does not match the old value, update it. */
  elseif ( $new_meta_value && $new_meta_value != $meta_value )
    update_post_meta( $post_id, $meta_key, $new_meta_value );

  /* If there is no new meta value but an old value exists, delete it. */
  elseif ( '' == $new_meta_value && $meta_value )
    delete_post_meta( $post_id, $meta_key, $meta_value );

}

/* Display the post meta box. */
function ssrt_smashing_post_class_meta_box( $post ) {
	echo ssrt_company_select_meta_box_content($post);
}

function ssrt_results_meta_box_content() {

	$html = (
		"
		<div class='sbapi_legend'>
			<h5>Lenend</h5>
			<ul>
				<li>Domain <span class='sbapi_label sbapi_legend__domain'></span></li>
				<li>Keywords <span class='sbapi_label sbapi_legend__kw'></span></li>
				<li>Google <span class='sbapi_label sbapi_legend__google'></span></li>
				<li>Bing <span class='sbapi_label sbapi_legend__bing'></span></li>
				<li>Yahoo <span class='sbapi_label sbapi_legend__yahoo'></span></li>
			</ul>
		</div>
		<div class='sbapi_results'>
			<div id='sbapi_hd' class='sbapi__header'>
				<div class='sbapi__header__domain'>Domain</div>
				<div class='sbapi__header__kw'>Keyword</div>
				<div class='sbapi__header__rank__group'>
					<div class='sbapi__header__google'>Google</div>
					<div class='sbapi__header__bing'>Bing</div>
					<div class='sbapi__header__yahoo'>Yahoo</div>
					<div class='sbapi__header__ms'>MS</div>
				</div>
			</div>
			<!-- Render JS results -->
			<div id='sbapi__results'></div>
			<div id='sbapi__lastupdated'></div>
		</div>"
	);

	echo $html;
}

/**
 * The contents of our meta box.
 * @access public
 * @since  1.0.0
 * @return void
 */
function ssrt_company_select_meta_box_content ($post) {

	wp_nonce_field( basename( __FILE__ ), 'smashing_post_class_nonce' );

	$id = "sbapi_select_view_key";
	$default_value = get_post_meta( $post->ID, 'sbapi_select_view_key', true );
	$html = '';
	$serpbook = new Serpbook_WP();
	$all_categories = $serpbook->ssrt_get_all_categories();

	$keyval = '';

	foreach ($all_categories as $key => $value) {
		$selected = $value == $default_value ? "selected=$default_value" : null;
		$keyval .= "<option $selected value='$value'>$key</option>";
	};

	$html = (
		"<div>
			<select id=sbapi_select_view_key' name='$id'>
				$keyval
			</select>
			<!-- Data for JavaScript on first load -->
			<input id='sbapi_hidden_viewkey_url' type='hidden' value='$default_value' />
		</div>"
	);

	echo $html;
} // ssrt_company_select_meta_box_content()

function ssrt_serp_table() {
	global $post;
	$default_value = get_post_meta( $post->ID, 'sbapi_select_view_key', true );

	$html = '';
	$html .= ssrt_results_meta_box_content();
	$html.= "<input id='sbapi_hidden_viewkey_url' type='hidden' value='$default_value' />";

	echo $html;
}
add_shortcode( 'sbapi_serp_table', 'ssrt_serp_table' );


/**
 * Main KIJO_Serpbook_API Class
 *
 * @class KIJO_Serpbook_API
 * @version	1.0.0
 * @since 1.0.0
 * @package	KIJO_Serpbook_API
 * @author Daveyon
 */
final class KIJO_Serpbook_API {
	/**
	 * The post type token.
	 * @access public
	 * @since  1.0.0
	 * @var    string
	 */
	public $post_type;

	/**
	 * KIJO_Serpbook_API The single instance of KIJO_Serpbook_API.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $token;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $version;

	/**
	 * The plugin directory URL.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $plugin_url;

	// Admin - Start
	/**
	 * The admin object.
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $admin;

	/**
	 * The settings object.
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings;
	// Admin - End

	/**
	 * The plugin directory path.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $plugin_path;

	// Post Types - Start
	/**
	 * The post types we're registering.
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $post_types = array();
	// Post Types - End
	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 */
	public function __construct () {
		$this->token 			= 'serpbook-api';
		$this->plugin_url 		= plugin_dir_url( __FILE__ );
		$this->plugin_path 		= plugin_dir_path( __FILE__ );
		$this->version 			= '1.0.0';

		// Admin - Start
		require_once( 'classes/class-serpbook-api-settings.php' );
			$this->settings = KIJO_Serpbook_API_Settings::instance();

		if ( is_admin() ) {
			require_once( 'classes/class-serpbook-api-admin.php' );
			$this->admin = KIJO_Serpbook_API_Admin::instance();
		}
		// Admin - End

		require_once( 'classes/class-serpbook-api-serp-data.php' );

		register_activation_hook( __FILE__, array( $this, 'install' ) );

		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
	} // End __construct()

	/**
	 * Main KIJO_Serpbook_API Instance
	 *
	 * Ensures only one instance of KIJO_Serpbook_API is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see KIJO_Serpbook_API()
	 * @return Main KIJO_Serpbook_API instance
	 */
	public static function instance () {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	} // End instance()

	/**
	 * Load the localisation file.
	 * @access  public
	 * @since   1.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'serpbook-api', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	} // End load_plugin_textdomain()

	/**
	 * Cloning is forbidden.
	 * @access public
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 * @access public
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), '1.0.0' );
	} // End __wakeup()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 */
	public function install () {
		$this->_log_version_number();
	} // End install()

	/**
	 * Log the plugin version number.
	 * @access  private
	 * @since   1.0.0
	 */
	private function _log_version_number () {
		// Log the version number.
		update_option( $this->token . '-version', $this->version );
	} // End _log_version_number()


	/**
	 * Return a value, using a desired retrieval method.
	 * @access  public
	 * @param  string $key option key.
	 * @param  string $default default value.
	 * @param  string $section field section.
	 * @since   1.0.0
	 * @return  mixed Returned value.
	 */
	public function get_value ( $key, $default, $section ) {
		$values = get_option( 'serpbook-api-' . $section, array() );

		if ( is_array( $values ) && isset( $values[$key] ) ) {
			$response = $values[$key];
		} else {
			$response = $default;
		}

		return $response;
	} // End get_value()
} // End Class
