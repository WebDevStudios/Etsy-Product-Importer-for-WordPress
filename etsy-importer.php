<?php
/*
Plugin Name: Etsy Importer
Plugin URI: http://www.webdevstudios.com
Description: Import your Etsy store's products as posts in a custom post type.
Author: WebDevStudios
Author URI: http://www.webdevstudios.com
Version: 1.4.0
License: GPLv2
*/

define( 'PLUGIN_BASE_DIR', plugins_url( '/', __FILE__ ) );

/**
 * All of the required global functions should be placed here.
 *
 * @package WordPress
 * @subpackage Etsy Importer
 */
class Etsy_Importer {

	const VERSION = '1.4.0';

	// A single instance of this class.
	public static $instance  = null;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @since 0.1.0
	 *
	 * @return Etsy_Importer A single instance of this class.
	 */
	public static function engage() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Build our class and run the functions.
	 */
	public function __construct() {

		// Include CMB2.
		if ( file_exists( dirname( __FILE__ ) . '/cmb2/init.php' ) ) {
			require_once 'cmb2/init.php';
		} elseif ( file_exists( dirname( __FILE__ ) . '/CMB2/init.php' ) ) {
			require_once 'CMB2/init.php';
		}

		// Include CMB2 Fields.
		require_once 'includes/fields.php';

		// Include Admin Settings Page.
		require_once 'includes/settings-page.php';

		// Get it started.
		$this->admin = new Etsy_Options_Admin( $this->post_type_key() );
		$this->admin->hooks();

		// Setup our cron job.
		add_action( 'wp', array( $this, 'setup_cron_schedule' ) );

		// Run our cron job to import new products.
		add_action( 'etsy_importer_daily_cron_job', array( $this, 'import_posts' ) );

		// Load translations.
		load_plugin_textdomain( 'etsy_importer', false, 'etsy-importer/languages' );

		// Define our constants.
		add_action( 'after_setup_theme', array( $this, 'constants' ), 1 );

		// Register our post types.
		add_action( 'init', array( $this, 'post_types' ) );

		// Register our taxonomies.
		add_action( 'init', array( $this, 'taxonomies' ) );

		// Run when we save our settings.
		add_action( 'cmb2_save_options-page_fields', array( $this, 'settings_save' ) );

		// Don't load in WP Dashboard.
		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ), 21 );
			add_action( 'admin_enqueue_scripts', 'add_thickbox', 21 );
		} else {
			add_action( 'wp_head', array( $this, 'check_for_enqueue' ), 99 );
		}

		// Add shortcodes.
		require_once( 'includes/shortcodes.php' );
		$this->shortcodes = new Etsy_Importer_Shortcodes();

		// Grab our new values set via CMB2.
		$etsy_options = get_option( 'etsy_options' );
		$api_key      = isset( $etsy_options['etsy_importer_api_key'] ) ? esc_html( $etsy_options['etsy_importer_api_key'] ) : '';
		$store_id     = isset( $etsy_options['etsy_importer_store_id'] ) ? esc_html( $etsy_options['etsy_importer_store_id'] ) : '';
		$checkbox     = isset( $etsy_options['etsy_importer_status_checkbox'] ) ? $etsy_options['etsy_importer_status_checkbox'] : '';

		// Set our API Key value to be used throughout the class.
		$this->api_key = $api_key;

		// Set our Store ID value to be used throughout the class.
		$this->store_id = $store_id;

		// Set our checkbox value to be used throughout the class.
		$this->post_status_on_import = $checkbox;
	}

	/**
	 * Set our filterable post type.
	 */
	public function post_type_key() {

		return apply_filters( 'etsy_importer_custom_post_type_key', 'etsy_products' );
	}

	/**
	 * Set our filterable category key.
	 */
	public function category_key() {

		return apply_filters( 'etsy_importer_category_key', 'etsy_category' );
	}

	/**
	 * Set our filterable tag key.
	 */
	public function tag_key() {

		return apply_filters( 'etsy_importer_tag_key', 'etsy_tag' );
	}

	/**
	 * Defines the constant paths for use within the theme.
	 */
	public function constants() {

		// Sets the path to the child theme directory.
		$this->define_const( 'ETSY_DIR', plugins_url( '/', __FILE__ ) );

		// Sets the path to the css directory.
		$this->define_const( 'ETSY_CSS', trailingslashit( ETSY_DIR . 'css' ) );

		// Sets the path to the javascript directory.
		$this->define_const( 'ETSY_JS', trailingslashit( ETSY_DIR . 'js' ) );

		// Sets the path to the images directory.
		$this->define_const( 'ETSY_IMG', trailingslashit( ETSY_DIR . 'images' ) );

		// Sets the path to the languages directory.
		$this->define_const( 'ETSY_LANG', trailingslashit( ETSY_DIR . 'languages' ) );

	}

	/**
	 * Define a constant if it hasn't been already (this allows them to be overridden).
	 *
	 * @since 1.0.0
	 *
	 * @param string $constant Constant name.
	 * @param string $value    Constant value.
	 */
	public function define_const( $constant, $value ) {
		// (can be overridden via wp-config, etc).
		if ( ! defined( $constant ) ) {
			define( $constant, $value );
		}
	}

	/**
	 * Load global styles.
	 */
	public function admin_styles() {

		// Main stylesheet.
		wp_enqueue_style( 'etsy-importer', ETSY_CSS . 'style.css', null, self::VERSION );

	}

	/**
	 * Load global styles.
	 */
	public function check_for_enqueue() {
		global $post;

		// Enqueue thickbox if the product images shortcode is used.
		if ( isset( $post->post_content ) && has_shortcode( $post->post_content, 'product_images' ) ) {
			add_thickbox();
		}
	}

	/**
	 * Set our Custom Post Type labels.
	 *
	 * Name (Singular), Name (Plural), Post Type Key (lowercase, use underscore for space),
	 * URL Slug (lowercase, use dash for space), Search, Link To Taxonomies, Hierachical, Menu Position, Supports.
	 */
	public function post_types() {

		// Check to see if our post type is registered already.  If so, stop the presses.
		if ( post_type_exists( $this->post_type_key() ) ) {
			return;
		}

		$this->post_type( array( __( 'Product', 'etsy_importer' ), __( 'Products', 'etsy_importer' ), $this->post_type_key(), 'products' ), array( 'menu_position' => '4' ) );
	}

	/**
	 * Register our custom post type.
	 *
	 * @param array $type Label values for CPT label.
	 * @param array $args CPT settings.
	 */
	public function post_type( $type, $args = array() ) {

		$type_single = $type[0];
		$types       = $type[1];
		$key         = $type[2];
		$slug        = $type[3];

		// Setup our labels.
		$labels = array(
			'name'               => $type_single,
			'singular_name'      => $type_single,
			'add_new'            => __( 'Add New', 'etsy_importer' ),
			'add_new_item'       => sprintf( __( 'Add New %s', 'etsy_importer' ), $type_single ),
			'edit_item'          => sprintf( __( 'Edit %s', 'etsy_importer' ), $type_single ),
			'new_item'           => sprintf( __( 'New %s', 'etsy_importer' ), $type_single ),
			'view_item'          => sprintf( __( 'View %s', 'etsy_importer' ), $type_single ),
			'search_items'       => sprintf( __( 'Search %s', 'etsy_importer' ), $types ),
			'not_found'          => sprintf( __( 'No %s found', 'etsy_importer' ), $types ),
			'not_found_in_trash' => sprintf( __( 'No %s found in Trash', 'etsy_importer' ), $types ),
			'parent_item_colon'  => '',
			'menu_name'          => $types,
		);

		$args = wp_parse_args( $args, array(
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'query_var'           => true,
			'rewrite'             => array( 'slug' => $slug ),
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'menu_position'       => '8',
			'has_archive'         => true,
			'exclude_from_search' => true,
			'supports'            => array( 'title', 'editor', 'revisions', 'thumbnail' ),
			'taxonomies'          => array(),
			'menu_icon'           => 'dashicons-cart',
		) );

		// Register our post types.
		register_post_type( $key, $args );
	}

	/**
	 * Set custom taxonomy labels.
	 *
	 * Name (Singular), Name (Plural), Taxonomy Key (lowercase, use underscore for space),
	 * URL Slug (lowercase, use dash for space), Parent Post Type Key
	 */
	public function taxonomies() {

		$this->taxonomy( __( 'Category', 'etsy_importer' ), __( 'Categories', 'etsy_importer' ), $this->category_key(), 'category', array( $this->post_type_key() ), true );
		$this->taxonomy( __( 'Tag', 'etsy_importer' ), __( 'Tags', 'etsy_importer' ), $this->tag_key(), 'tag', array( $this->post_type_key() ), true );
	}

	/**
	 * Register taxonomies.
	 *
	 * @param string  $type           Singular name.
	 * @param string  $types          Plural name.
	 * @param string  $key            Taxonomy key.
	 * @param string  $url_slug       Taxonomy slug.
	 * @param array   $post_type_keys Post type keys.
	 * @param boolean $public         Boolean value for public/non-public taxonomy.
	 */
	public function taxonomy( $type, $types, $key, $url_slug, $post_type_keys, $public ) {

		// Setup our labels.
		$labels = array(
			'name'                       => $types,
			'singular_name'              => $type,
			'search_items'               => sprintf( __( 'Search %s', 'etsy_importer' ), $types ),
			'popular_items'              => sprintf( __( 'Common %s', 'etsy_importer' ), $types ),
			'all_items'                  => sprintf( __( 'All %s', 'etsy_importer' ), $types ),
			'parent_item'                => null,
			'parent_item_colon'          => null,
			'edit_item'                  => sprintf( __( 'Edit %s', 'etsy_importer' ), $type ),
			'update_item'                => sprintf( __( 'Update %s', 'etsy_importer' ), $type ),
			'add_new_item'               => sprintf( __( 'Add New %s', 'etsy_importer' ), $type ),
			'new_item_name'              => sprintf( __( 'New %s Name', 'etsy_importer' ), $type ),
			'separate_items_with_commas' => sprintf( __( 'Separate %s with commas', 'etsy_importer' ), $types ),
			'add_or_remove_items'        => sprintf( __( 'Add or remove %s', 'etsy_importer' ), $types ),
			'choose_from_most_used'      => sprintf( __( 'Choose from the most used %s', 'etsy_importer' ), $types ),
		);

		// Permalink.
		$rewrite = array(
			'slug'                       => $url_slug,
			'with_front'                 => true,
			'hierarchical'               => true,
		);

		// Default arguments.
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => true,
			'public'                     => $public,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => true,
			'query_var'                  => true,
			'rewrite'                    => $rewrite,
		);

		// Register our taxonomies.
		register_taxonomy( $key, $post_type_keys, $args );

	}

	/**
	 * On an early action hook, check if the hook is scheduled - if not, schedule it.
	 *
	 * This runs once daily.
	 */
	public function setup_cron_schedule() {
		if ( ! wp_next_scheduled( 'etsy_importer_daily_cron_job' ) ) {
			wp_schedule_event( time(), 'daily', 'etsy_importer_daily_cron_job' );
		}
	}

	/**
	 * Grab the image ID from its URL.
	 *
	 * @param string $image_src Image URL.
	 * @return int $id
	 */
	public function get_attachment_id_from_src( $image_src ) {
		global $wpdb;

		$query = "SELECT ID FROM {$wpdb->posts} WHERE guid='$image_src'";
		$id = $wpdb->get_var( $query );

		return $id;
	}

	/**
	 * Run some functionality when saving settings
	 */
	public function settings_save() {

		// If both the API Key and Store ID values are set and both are not empty, do something.
		if ( isset( $this->api_key ) && '' !== $this->api_key && isset( $this->store_id ) && '' !== $this->store_id ) {
			$this->import_posts();
		}

		if ( ! isset( $_POST['etsy_importer_status_checkbox'] ) ) {
			$this->import_posts();
		}
	}

	/**
	 * Register the function that imports our posts.
	 */
	public function import_posts() {

		// Make sure you define API_KEY to be your unique, registered key.
		$response = $this->get_results_count();

		if ( ! isset( $response->count ) ) {
			wp_die( esc_html__( 'No product count paramater available from Etsy response. Are there any products available?', 'etsy_importer' ) );
		}

		// Get the total number of products so we can loop through each page.
		$total_count = $response->count;

		// Divide our total by 25 to get 25 results per page.
		$paged_total = ceil( $total_count / 25 );

		// Loop through pages enough times for each page of results.
		$post_limit  = 25;
		$post_offset = 0;

		$this->get_page_results( $paged_total, $post_limit, $post_offset );
	}

	/**
	 * Get our paged results.
	 *
	 * @param int $paged_total How many more to available to fetch for current page.
	 * @param int $post_limit  Maximum amount to fetch for this page.
	 * @param int $post_offset Where to start with the fetching of more results.
	 */
	public function get_page_results( $paged_total, $post_limit, $post_offset ) {

		for ( $import_count = 1; $import_count <= $paged_total; $import_count++ ) {

			$paged_response = $this->get_paged_results( $post_limit, $post_offset );

			if ( empty( $paged_response ) ) {
				return;
			}

			// Get each listing.
			$this->import_each_product( $paged_response );

			$post_limit  = $post_limit + 25;
			$post_offset = $post_offset + 25;
		}
	}

	/**
	 * Get each product's data.
	 *
	 * @param object $paged_response API result data.
	 */
	public function import_each_product( $paged_response ) {

		if ( ! isset( $paged_response->results ) || empty( $paged_response->results ) ) {
			return;
		}

		// Check to see if any posts are no longer in our Etsy shop. If not, set them to draft mode
		// ONLY when our checkbox is unchecked in settings.
		$this->set_inactive_posts_to_draft( $paged_response );

		// Increase our time limit.
		set_time_limit( 120 );

		// Loop through each product.
		foreach ( $paged_response->results as $product ) {

			// If the post exists, don't bother
			// @TO DO: In our next update, switch this out to look for the matching product listing ID.
			if ( get_page_by_title( esc_html( $product->title ), OBJECT, $this->post_type_key() ) ) {

				$existing_post = get_page_by_title( esc_html( $product->title ), OBJECT, $this->post_type_key() );

				// Import the product listing ID as post meta.
				$this->import_product_listing_id( $existing_post->ID, $product );

				// Then stop.
				continue;
			}

			// Set up our post args.
			$post_args = $this->setup_post_args( $product );

			// Create our post.
			$post_id = wp_insert_post( $post_args );

			// Update our post meta with the group ID.
			$this->update_product_post_meta( $post_id, $product );

			// Import the product listing ID as post meta.
			$this->import_product_listing_id( $post_id, $product );

			// Set our categories.
			if ( isset( $product->category_path ) ) {
				wp_set_object_terms( $post_id, $product->category_path, $this->category_key(), true );
			}

			// Set our tags.
			if ( isset( $product->tags ) ) {
				wp_set_object_terms( $post_id, $product->tags, $this->tag_key(), true );
			}

			// Get each listing's images.
			$response = $this->get_product_images( $product );

			// Get our attached images.
			$this->add_images_to_product_post( $post_id, $response );

			do_action( 'etsy_importer_product_import', $post_id, $product );

		}
	}

	/**
	 * Setup our post args.
	 *
	 * @param object $product Product object.
	 *
	 * @return array
	 */
	public function setup_post_args( $product ) {

		// Get the product description.
		$product_description = ! empty( $product->description ) ? wp_kses_post( $product->description ) : '';

		return apply_filters( 'etsy_importer_product_import_insert_args', array(
			'post_type'    => $this->post_type_key(),
			'post_title'   => esc_html( $product->title ),
			'post_content' => $product_description,
			'post_status'  => 'publish',
		), $product );
	}

	/**
	 * Import the product listing ID as post meta
	 *
	 * @param int    $post_id ID of the post to update.
	 * @param object $product Product object.
	 */
	public function import_product_listing_id( $post_id, $product ) {

		if ( isset( $product->listing_id ) ) {
			update_post_meta( $post_id, '_etsy_product_id', intval( $product->listing_id ) );
		}
	}

	/**
	 * Add/Update the product's post meta.
	 *
	 * @param int    $post_id ID of the post to update.
	 * @param object $product Product object.
	 */
	public function update_product_post_meta( $post_id, $product ) {

		if ( isset( $product->price ) ) {
			update_post_meta( $post_id, '_etsy_product_price', esc_html( $product->price ) );
		}

		if ( isset( $product->currency_code ) ) {
			update_post_meta( $post_id, '_etsy_product_currency', esc_html( $product->currency_code ) );
		}

		if ( isset( $product->url ) ) {
			update_post_meta( $post_id, '_etsy_product_url', esc_url( $product->url ) );
		}

		if ( isset( $product->when_made ) ) {
			update_post_meta( $post_id, '_etsy_product_made', str_replace( '_', '-', $product->when_made ) );
		}

		if ( isset( $product->recipient ) ) {
			update_post_meta( $post_id, '_etsy_product_made_for', esc_html( $product->recipient ) );
		}

	}

	/**
	 * Attach the images to the product post.
	 *
	 * @param int    $post_id ID of the post to add images to.
	 * @param object $response Response object.
	 */
	public function add_images_to_product_post( $post_id, $response ) {

		if ( ! isset( $response->results ) || empty( $response->results ) ) {
			return;
		}

		// Loop through each listing's images and upload them.
		foreach ( $response->results as $image ) {

			// Get our image URL and basename.
			$image_url = $image->url_fullxfull;
			$filename  = basename( $image->url_fullxfull );

			// Upload our image and attach it to our post.
			$uploaded_image = media_sideload_image( $image_url, $post_id, $filename );

			// Grab the src URL from our image tag.
			$uploaded_image = preg_replace( "/.*(?<=src=[''])([^'']*)(?=['']).*/", '$1', $uploaded_image );

			// Set post thumbnail to the image with rank 1.
			if ( isset( $image->rank ) && 1 == $image->rank ) {
				set_post_thumbnail( $post_id, $this->get_attachment_id_from_src( $uploaded_image ) );
			}
		}
	}

	/**
	 * Get response from etsy.
	 */
	public function get_response() {

		if ( isset( $this->response ) ) {
			return $this->response;
		}

		$url = "https://openapi.etsy.com/v2/private/shops/{$this->store_id}?api_key={$this->api_key}";

		$this->response = $this->get_results( $url );

		return $this->response;
	}

	/**
	 * Get results for our initial query.
	 */
	public function get_results_count() {

		$url = "https://openapi.etsy.com/v2/private/shops/{$this->store_id}/listings/active?sort_on=created&sort_order=down&api_key={$this->api_key}&limit=25";

		return $this->get_results( $url );
	}


	/**
	 * Get paged results.
	 *
	 * @param int $post_limit  Limit of products to fetch.
	 * @param int $post_offset Offset for where to start at.
	 * @return object
	 */
	public function get_paged_results( $post_limit, $post_offset ) {

		$paged_url = "https://openapi.etsy.com/v2/private/shops/{$this->store_id}/listings/active?sort_on=created&sort_order=down&api_key={$this->api_key}&limit={$post_limit}&offset={$post_offset}";

		return $this->get_results( $paged_url );
	}


	/**
	 * Get the product's images.
	 *
	 * @param object $product Product object.
	 * @return object
	 */
	public function get_product_images( $product ) {

		$images_url = "https://openapi.etsy.com/v2/private/listings/{$product->listing_id}/images?api_key={$this->api_key}";

		return $this->get_results( $images_url );
	}

	/**
	 * Get results generically for a url.
	 *
	 * @todo possibly better handline for wp_die in some instances
	 *
	 * @param string $url URL to fetch data from.
	 * @return object
	 */
	public function get_results( $url ) {

		$response  = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			wp_die( $response );
		}

		$body = wp_remote_retrieve_body( $response );

		if ( ! $body ) {
			wp_die( $body );
		}

		return json_decode( $body );
	}

	/**
	 * Update our post status to draft mode if it is
	 * no longer in the Active state on Etsy.
	 *
	 * @param object $paged_response Paged response data.
	 */
	public function set_inactive_posts_to_draft( $paged_response ) {

		// If the box is checked, don't try to change post statuses.
		if ( isset( $_POST['etsy_importer_status_checkbox'] ) ) {
			return;
		}

		// Retrieve ALL product posts.
		$all_products = get_posts( array( 'post_type' => $this->post_type_key(), 'posts_per_page' => -1, 'post_status' => 'any' ) );

		// Begin an array of product titles
		// as served by the Etsy API.
		$all_product_titles = array();

		// Loop through each product from Etsy.
		foreach ( $paged_response->results as $product ) {

			// Add the product title to our array.
			$all_product_titles[] = $product->title;

		}

		// Loop through each product in our CPT.
		foreach ( $all_products as $this_product ) {

			// Set our in_array outcome to a variable.
			$in_product_array = in_array( $this_product->post_title, $all_product_titles );

			// Set the default post status to publish.
			$post_status = apply_filters( 'etsy_importer_default_post_status', 'publish' );

			// Check to see our post is in our product array.
			// If not, set it to draft mode.
			if ( ! $in_product_array ) {

				$post_status = apply_filters( 'etsy_importer_updated_post_status', 'draft' );

			}

			// If it is not in the response, set it to draft.
			$update_post_args = array(
				'ID'          => $this_product->ID,
				'post_status' => $post_status,
			);

			// Update our post settings.
			wp_update_post( apply_filters( 'etsy_importer_updated_post_args', $update_post_args ) );

		}
	}
}

// Instantiate the class.
Etsy_Importer::engage();
