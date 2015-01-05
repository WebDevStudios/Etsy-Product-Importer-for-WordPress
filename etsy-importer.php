<?php
/*
Plugin Name: Etsy Importer
Plugin URI: http://www.webdevstudios.com
Description: Import your Etsy store's products as posts in a custom post type.
Author: WebDevStudios
Author URI: http://www.webdevstudios.com
Version: 1.2.0
License: GPLv2
*/


/**
 * All of the required global functions should be placed here.
 *
 * @package WordPress
 * @subpackage Etsy Importer
 */
Class Etsy_Importer {

	const VERSION = '1.2.0';

	// A single instance of this class.
	public static $instance = null;

	/**
	 * Creates or returns an instance of this class.
	 * @since  0.1.0
	 * @return Etsy_Importer A single instance of this class.
	 */
	public static function engage() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Build our class and run the functions
	 */
	private function __construct() {

		// Setup our cron job
		add_action( 'wp', array( $this, 'setup_cron_schedule' ) );

		// Run our cron job to import new products
		add_action( 'etsy_importer_daily_cron_job', array( $this, 'import_posts' ) );

		// Add our menu items
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_admin_settings' ) );
		add_action( 'admin_init', array( $this, 'process_settings_save') );

		// Load translations
		load_plugin_textdomain( 'etsy_importer', false, 'etsy-importer/languages' );

		// Define our constants
		add_action( 'after_setup_theme', array( $this, 'constants' ), 1 );

		// Register our post types
		add_action( 'init', array( $this, 'post_types' ) );

		// Register our taxonomies
		add_action( 'init', array( $this, 'taxonomies' ) );

		// Don't load in WP Dashboard
		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ), 21 );
			add_action( 'admin_enqueue_scripts', 'add_thickbox', 21 );
		} else {
			add_action( 'wp_head', array( $this, 'check_for_enqueue' ), 99 );
		}

		// Add shortcodes
		add_shortcode( 'product_link', array( $this, 'product_link_shortcode' ) );
		add_shortcode( 'product_content', array( $this, 'product_content_shortcode' ) );
		add_shortcode( 'product_images', array( $this, 'product_images_shortcode' ) );

		// Include CMB
		require_once( 'cmb/etsy-fields.php' );
		require_once( 'cmb/init.php' );

		// Get the shop ID and our API key
		$this->options  = get_option( 'etsy_store_settings' );
		$this->api_key  = ( isset( $this->options['settings_etsy_api_key'] ) ) ? esc_html( $this->options['settings_etsy_api_key'] ) : '';
		$this->store_id = ( isset( $this->options['settings_etsy_store_id'] ) ) ? esc_html( $this->options['settings_etsy_store_id'] ) : '';

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
	 * Define a constant if it hasn't been already (this allows them to be overridden)
	 * @since  1.0.0
	 * @param  string  $constant Constant name
	 * @param  string  $value    Constant value
	 */
	public function define_const( $constant, $value ) {
		// (can be overridden via wp-config, etc)
		if ( ! defined( $constant ) ) {
			define( $constant, $value );
		}
	}


	/**
	 * Load global styles
	 */
	public function admin_styles() {

		// Main stylesheet
		wp_enqueue_style( 'etsy-importer', ETSY_CSS . 'style.css', null, self::VERSION );

	}


	/**
	 * Load global styles
	 */
	public function check_for_enqueue() {
		global $post;

		// Enqueue thickbox if the product images shortcode is used
		if ( isset( $post->post_content ) && has_shortcode( $post->post_content, 'product_images' ) ) {
			add_thickbox();
		}

	}


	/**
	 * Register Custom Post Types
	 *
	 * Name (Singular), Name (Plural), Post Type Key (lowercase, use underscore for space),
	 * URL Slug (lowercase, use dash for space), Search, Link To Taxonomies, Hierachical, Menu Position, Supports
	 */
	public function post_types() {

		$this->post_type( array( __( 'Product', 'etsy_importer' ), __( 'Products', 'etsy_importer' ), 'etsy_products', 'products' ), array( 'menu_position' => '4' ) );
	}

	public function post_type( $type, $args = array() ) {

		$type_single = $type[0];
		$types       = $type[1];
		$key         = $type[2];
		$slug        = $type[3];

		// Setup our labels
		$labels = array(
			'name'               => $type_single,
			'singular_name'      => $type_single,
			'add_new'            => __( 'Add New' ),
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

		// Register our post types
		register_post_type( $key, $args );

	}


	/**
	 * Register Taxonomies
	 *
	 * Name (Singular), Name (Plural), Taxonomy Key (lowercase, use underscore for space), URL Slug (lowercase, use dash for space), Parent Post Type Key
	 */
	public function taxonomies() {

		$this->taxonomy( __( 'Category' ), __( 'Categories' ), 'etsy_category', 'category', array( 'etsy_products' ), true );
		$this->taxonomy( __( 'Tag' ), __( 'Tags' ), 'etsy_tag', 'tag', array( 'etsy_products' ), true );

	}

	public function taxonomy( $type, $types, $key, $url_slug, $post_type_keys, $public ) {

		// Setup our labels
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

		// Permalink
		$rewrite = array(
			'slug'                       => $url_slug,
			'with_front'                 => true,
			'hierarchical'               => true,
		);

		// Default arguments
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

		// Register our taxonomies
		register_taxonomy( $key, $post_type_keys, $args );

	}


	/**
	 * Add our menu items
	 */
	public function admin_menu() {

		add_options_page( __( 'Etsy Importer', 'etsy_importer' ), __( 'Etsy Importer', 'etsy_importer' ), 'manage_options', __FILE__, array( $this, 'admin_page' ) );
	}

	/**
	 * Register settings and fields
	 */
	public function register_admin_settings() {

		// Add our settings
		register_setting( 'etsy_store_settings', 'etsy_store_settings', array( $this, 'validate_settings' ) );
		add_settings_section( 'etsy_store_main_options', '', '', __FILE__ );
		add_settings_field( 'etsy_settings_api_key', __( 'API Key:', 'etsy_importer' ), array( $this, 'settings_etsy_api_key' ), __FILE__, 'etsy_store_main_options' );
		add_settings_field( 'etsy_settings_store_id', __( 'Store ID:', 'etsy_importer' ), array( $this, 'settings_etsy_store_id' ), __FILE__, 'etsy_store_main_options' );
	}

	/**
	 * Build the form fields
	 */
	public function settings_etsy_api_key() {

		echo "<div class='input-wrap'><div class='left'><input id='api-key' name='etsy_store_settings[settings_etsy_api_key]' type='text' value='{$this->options['settings_etsy_api_key']}' /></div>";
		?>

		<p><?php printf( __( 'Need help? <a href="%s" class="thickbox">Click here</a> for a walkthrough on how to setup your Etsy Application.', 'etsy_importer' ), '#TB_inline?width=1200&height=600&inlineId=etsy-api-instructions' ); ?></p>

		<div id="etsy-api-instructions" style="display: none;">
			<p><?php printf( __( 'In order to import your products, you first need to register an application with Etsy.  <a href="%s" target="_blank">Click here</a> to begin registering your application.  You should see a screen similar to the image below:', 'etsy_importer' ), 'https://www.etsy.com/developers/register' ); ?><br />
			<img src="<?php echo ETSY_DIR . 'screenshot-1.jpg'; ?>" /></p>

			<p><?php _e( 'Once you have created your app, click "Apps You\'ve Made" in the sidebar and select your new app.  On the app detail page, copy the value in the Keystring input field.  This is your API Key.', 'etsy_importer' ); ?><br />
			<img src="<?php echo ETSY_DIR . 'screenshot-2.jpg'; ?>" /></p>

		</div>

		<?php
	}

	/**
	 * Build the form fields
	 */
	public function settings_etsy_store_id() {

		// Get the total post count
		$count_posts = wp_count_posts( 'etsy_products' );
		$total_posts = $count_posts->publish + $count_posts->future + $count_posts->draft + $count_posts->pending + $count_posts->private;

		$response = $this->get_response();
		// Grab our shop name if we have a response
		$shop_name = isset( $response->results[0]->title )
			? sprintf( __( 'You are connected to <strong>%s</strong>.', 'etsy_importer' ), $response->results[0]->title )
			: '';

		echo "<div class='input-wrap'>
			<div class='left'>
				<input id='store-id' name='etsy_store_settings[settings_etsy_store_id]' type='text' value='{$this->options['settings_etsy_store_id']}' />
			</div>";

	?>

		<p><?php printf( __( 'Need help? <a href="%s" class="thickbox">Click here</a> for a walkthrough on how to find your Etsy store ID.', 'etsy_importer' ), '#TB_inline?width=1200&height=600&inlineId=etsy-store-id-instructions' ); ?></p>

		<div id="etsy-store-id-instructions" style="display: none;">
			<p><?php _e( 'Visit your Etsy store\'s front page.  View the page source:', 'etsy_importer' ); ?><br />
			<img src="<?php echo ETSY_DIR . 'screenshot-3.jpg'; ?>" /></p>

			<p><?php _e( 'We want one specific line, whose meta name is "apple-itunes-app".  The number you see below following "etsy://shop/" is your store ID:', 'etsy_importer' ); ?><br />
			<img src="<?php echo ETSY_DIR . 'screenshot-4.jpg'; ?>" /></p>

		</div>

		<p class="import-count">
			<span>
				<?php
				echo $total_posts >= 1 ? sprintf( __( 'You have imported <strong>%s products</strong>.', 'etsy_importer' ), $total_posts ) . '<br />' : null;
				echo $shop_name;
				?>
			</span>
		</p>

	<?php
	}

	/**
	 * Sanitize the value
	 */
	public function validate_settings( $etsy_store_settings ) {
		return $etsy_store_settings;
	}

	/**
	 * Import our products and add our new taxonomy terms on settings save
	 */
	public function process_settings_save() {

		$updated = 0;
		// Save our API Key
		if ( isset( $_POST['etsy_store_settings']['settings_etsy_api_key'] ) && ! empty( $_POST['etsy_store_settings']['settings_etsy_api_key'] ) ) {

			// Update our class variables
			$this->settings_etsy_api_key = $_POST['etsy_store_settings']['settings_etsy_api_key'];
			$updated++;
		}

		// Save our Store ID
		if ( isset( $_POST['etsy_store_settings']['settings_etsy_store_id'] ) && ! empty( $_POST['etsy_store_settings']['settings_etsy_store_id'] ) ) {

			// Update our class variables
			$this->settings_etsy_store_id = $_POST['etsy_store_settings']['settings_etsy_store_id'];
			$updated++;
		}


		// If both our API Key and Store ID are saved, import our products
		if ( isset( $_POST['etsy_import_nonce'] ) && isset( $_POST['submit-import'] ) && 2 === $updated ) {

			// Import our products
			$this->import_posts();
		}
	}

	/**
	 * On an early action hook, check if the hook is scheduled - if not, schedule it.
	 * This runs once daily
	 */
	public function setup_cron_schedule() {

		if ( ! wp_next_scheduled( 'etsy_importer_daily_cron_job' ) ) {
			$frequency = apply_filters( 'etsy_importer_daily_cron_job', 'daily' );
			wp_schedule_event( time(), $frequency, 'etsy_importer_daily_cron_job' );
		}
	}

	/**
	 * Build the admin page
	 */
	public function admin_page() {

		// Get the total post count
		$count_posts = wp_count_posts( 'etsy_products' );
		$total_posts = $count_posts->publish + $count_posts->future + $count_posts->draft + $count_posts->pending + $count_posts->private;

		$response = $this->get_response();

		// If there is no response, disable the import button
		$disabled = empty( $response ) ? 'disabled' : 'enabled';

		?>
		<div id="theme-options-wrap" class="metabox-holder">
			<h2><?php _e( 'Etsy Importer', 'etsy_importer' ); ?></h2>
			<form id="options-form" method="post" action="options.php" enctype="multipart/form-data">
				<div class="postbox ui-tabs etsy-wrapper">
					<h3 class="hndle"><?php _e( 'Import your Etsy store\'s products as posts in the Product custom post type.', 'etsy_importer' ); ?></h3>
					<?php settings_fields( 'etsy_store_settings' ); ?>
					<input type="hidden" name="etsy_import_nonce" value="<?php echo wp_create_nonce( basename( __FILE__ ) ); ?>" />
					<?php do_settings_sections( __FILE__ ); ?>
					<div class="submit">
						<input name="submit-save" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'etsy_importer' ); ?>" />
						<span class="save-notes"><em><?php _e( 'You must save changes before importing products. If you need to change your API Key or Store ID, hit the Save Changes button before hitting the Import Products button.', 'etsy_importer' ); ?></em></span>
					</div>
					<div class="submit">
						<input name="submit-import" type="submit" class="button-primary button-import" value="<?php esc_attr_e( 'Import Products', 'etsy_importer' ); ?>" <?php echo $disabled; ?> />
						<div class="save-notes"><em><?php _e( 'Your import could take a while if you have a large number of products or images attached to each product.', 'etsy_importer' ); ?></em>
						<p><em><?php _e( 'After your initial import, your products will import automatically once daily.  If you need to manually import your products ahead of schedule, clicking the Import Products button will begin a manual import of new products.', 'etsy_importer' ); ?></em></p></div>
					</div>
				</div>
			</form>
		</div>
	<?php }


	/**
	 * Add a shortcode to display the product title
	 *
	 * @since 1.0
	 */
	public function product_link_shortcode( $atts, $content = null ) {

		// Get our shortcode attributes
		$atts = shortcode_atts( array(
			'id'		=> '',
			'external'	=> '',
			'title'		=> '',
		), $atts, 'product_link' );

		extract( $atts );

		// Get our post content
		$product = get_post( $id );

		// If there is no product found, stop
		if ( ! $product ) {
			return;
		}

		// Get our post or external link
		if ( 'yes' == $external || 'true' == $external ) {

			$link 	= esc_url( get_post_meta( $id, '_etsy_product_url', true ) );
			$target	= '_blank';

		} else {

			$link 	= get_permalink( $id );
			$target	= '_self';
		}

		// Get our link title
		$title = ( $title ) ? $title : get_the_title( $id );

		// Assume zer is nussing
		$output = '';

		// If our title and link return something, display the link
		if ( $title && $link ) {
			$output .= '<p><a href="' . $link . '" title="' . $title . '" target="' . $target . '">' . $title . '</a></p>';
		}

		return apply_filters( 'etsy_importer_product_link_shortcode', $output, $atts );
	}


	/**
	 * Add a shortcode to display the product content
	 *
	 * @since 1.0
	 */
	public function product_content_shortcode( $atts, $content = null ) {

		// Get our shortcode attributes
		$atts = shortcode_atts( array(
			'id'		=> '',
			'length'	=> '',
		), $atts, 'product_content' );

		extract( $atts );

		// Get our post content
		$product = get_post( $id );

		// If there is no product found, stop
		if ( ! $product ) {
			return;
		}

		$content = wpautop( $product->post_content );

		// Assume zer is nussing
		$output = '';

		// If our content returns something, display it
		if ( $content ) {

			// If we have a length set, apply it
			if ( '' !== $length ) {

				$excerpt_length = $length;
				$excerpt_more   = '&hellip;';
				$output        .= '<p>' . wp_trim_words( $content, $excerpt_length, $excerpt_more ) . ' <a href="' . get_permalink( $id ) . '" class="more-link">' . __( 'Continue reading', 'etsy_importer' ) . ' <span class="screen-reader-text">' . $product->post_title . '</span></a></p>';

			} else {

				$output .= $content;

			}
		}

		return apply_filters( 'etsy_importer_product_content_shortcode', $output, $atts );
	}


	/**
	 * Add a shortcode to display the product content
	 *
	 * @since 1.0
	 */
	public function product_images_shortcode( $atts, $content = null ) {

		// Get our shortcode attributes
		$atts = shortcode_atts( array(
			'id'	=> '',
			'size'	=> '',
		), $atts, 'product_images' );

		extract( $atts );

		// Get our post content
		$product = get_post( $id );

		// If there is no product found, stop
		if ( ! $product ) {
			return;
		}

		$img_args = apply_filters( 'etsy_importer_product_images_shortcode_args', array(
			'post_type'			=> 'attachment',
			'posts_per_page'	=> 500, // sanity
			'post_parent'		=> $id,
		), $atts );

		// Get our post images
		$images = get_posts( $img_args );

		$thumb_size = apply_filters( 'etsy_importer_product_images_shortcode_thumb_size', 'thumbnail', $atts );

		// Assume zer is nussing
		$output = '';

		// If our content returns something, display it
		if ( $images ) {
			foreach ( $images as $image ) {

				// Set the image ID
				$image_id = $image->ID;

				// Grab the image based on the size passed in the shortcode
				$image_thumb 	= wp_get_attachment_image( $image_id, $thumb_size );
				$image_full 	= wp_get_attachment_image_src( $image_id, 'full' );

				// Display the image
				$output .= '<a href="' . $image_full[0]. '" class="thickbox" rel="gallery-' . $id . '">' . $image_thumb . '</a>';

			}
		}

		return apply_filters( 'etsy_importer_product_images_shortcode', $output, $atts );
	}

	/**
	 * Grab the image ID from its URL
	 */
	public function get_attachment_id_from_src( $image_src ){
		global $wpdb;

		$query = "SELECT ID FROM {$wpdb->posts} WHERE guid='$image_src'";
		$id = $wpdb->get_var( $query );
		return $id;

	}

	/**
	 * Register the function that imports our posts
	 */
	public function import_posts() {

		// Make sure you define API_KEY to be your unique, registered key
		$response = $this->get_results_count();

		if ( ! isset( $response->count ) ) {
			wp_die( 'No count paramater available?' );
		}

		// Get the total number of products so we can loop through each page
		$total_count = $response->count;

		// Divide our total by 25 to get 25 results per page
		$paged_total = ceil( $total_count / 25 );

		// Loop through pages enough times for each page of results
		$post_limit  = 25;
		$post_offset = 0;

		$this->get_page_results( $paged_total, $post_limit, $post_offset );
	}

	/**
	 * Get our paged results
	 */
	public function get_page_results( $paged_total, $post_limit, $post_offset ) {

		for ( $import_count = 1; $import_count <= $paged_total; $import_count++ ) {

			$paged_response = $this->get_paged_results( $post_limit, $post_offset );

			if ( empty( $paged_response ) ) {
				return;
			}

			// Get each listing
			$this->import_each_product( $paged_response );

			$post_limit  = $post_limit + 25;
			$post_offset = $post_offset + 25;
		}
	}

	/**
	 * Get each product's data
	 */
	public function import_each_product( $paged_response ) {

		if ( ! isset( $paged_response->results ) || empty( $paged_response->results ) ) {
			return;
		}

		// Increase our time limit
		set_time_limit( 120 );

		// Loop through each product
		foreach ( $paged_response->results as $product ) {

			// If the post exists, don't bother
			if ( get_page_by_title( esc_html( $product->title ), OBJECT, 'etsy_products' ) ) {
				continue;
			}

			// Set up our post args
			$post_args = $this->setup_post_args( $product );

			// Create our post
			$post_id = wp_insert_post( $post_args );

			// Update our post meta with the group ID
			$this->update_product_post_meta( $post_id, $product );

			// Set our categories
			if ( isset( $product->category_path ) ) {
				wp_set_object_terms( $post_id, $product->category_path, 'etsy_category', true );
			}

			// Set our tags
			if ( isset( $product->tags ) ) {
				wp_set_object_terms( $post_id, $product->tags, 'etsy_tag', true );
			}

			// Get each listing's images
			$response = $this->get_product_images( $product );

			// Get our attached images
			$this->add_images_to_product_post( $post_id, $response );

			do_action( 'etsy_importer_product_import', $post_id, $product );

		}
	}

	/**
	 * Setup our post args
	 */
	public function setup_post_args( $product ) {

		$product_description = ! empty( $product->description ) ? wp_kses_post( $product->description ) : '';

		return apply_filters( 'etsy_importer_product_import_insert_args', array(
			'post_type'    => 'etsy_products',
			'post_title'   => esc_html( $product->title ),
			'post_content' => $product_description,
			'post_status'  => 'publish',
		) );
	}

	/**
	 * Add/Update the product's post meta
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
	 * Attach the images to the product post
	 */
	public function add_images_to_product_post( $post_id, $response ) {

		if ( ! isset( $response->results ) || empty( $response->results ) ) {
			return;
		}

		// Loop through each listing's images and upload them
		foreach ( $response->results as $image ) {

			// Get our image URL and basename
			$image_url = $image->url_fullxfull;
			$filename  = basename( $image->url_fullxfull );

			// Upload our image and attach it to our post
			$uploaded_image = media_sideload_image( $image_url, $post_id, $filename );

			// Grab the src URL from our image tag
			$uploaded_image = preg_replace( "/.*(?<=src=[''])([^'']*)(?=['']).*/", '$1', $uploaded_image );

			// Set post thumbnail to the image with rank 1
			if ( isset( $image->rank ) && 1 == $image->rank ) {
				set_post_thumbnail( $post_id, $this->get_attachment_id_from_src( $uploaded_image ) );
			}
		}
	}

	/**
	 * Get response from etsy
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
	 * Get results for our initial query
	 */
	public function get_results_count() {

		$url = "https://openapi.etsy.com/v2/private/shops/{$this->store_id}/listings/active?sort_on=created&sort_order=down&api_key={$this->api_key}&limit=25";

		return $this->get_results( $url );
	}


	/**
	 * Get paged results
	 */
	public function get_paged_results( $post_limit, $post_offset ) {

		$paged_url = "https://openapi.etsy.com/v2/private/shops/{$this->store_id}/listings/active?sort_on=created&sort_order=down&api_key={$this->api_key}&limit={$post_limit}&offset={$post_offset}";

		return $this->get_results( $paged_url );
	}


	/**
	 * Get the product's images
	 */
	public function get_product_images( $product ) {

		$images_url = "https://openapi.etsy.com/v2/private/listings/{$product->listing_id}/images?api_key={$this->api_key}";

		return $this->get_results( $images_url );
	}

	/**
	 * Get results generically for a url
	 * todo possibly better handline for wp_die in some instances
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

}

// Instantiate the class
Etsy_Importer::engage();
