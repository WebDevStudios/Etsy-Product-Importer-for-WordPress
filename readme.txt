=== Etsy Importer ===
Contributors: coreymcollins, webdevstudios
Plugin Name: Etsy Importer
Plugin URI: http://www.webdevstudios.com
Tags: etsy, store, shop, import, importer
Author: WebDevStudios
Author URI: http://www.webdevstudios.com
Requires at least: 3.5
Tested up to: 4.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Import your Etsy store's products as posts in a custom post type.

== Description ==

This plugin will allow you to import your entire Etsy shop's inventory into WordPress as posts in a Products custom post type.  All you have to do is supply an API Key for an Etsy application and the ID of your shop and the plugin will do the rest.

The Etsy Importer will not only import your products but will also:

* Attach all of the product's images to the post
* Set the main product image as the post's featured thumbnail
* Add and attach any category added to your product
* Add and attach any tag added to your product
* Add the Etsy Product Listing ID, Price, Etsy Product Link, Production Year, and Made For (men/women) meta information to the post
* Check for new products in your Etsy shop daily and import them automatically once daily.  Hands free!
* Check for existing posts which no longer exist in your shop as active products and set them to draft post status

Not only do we import your products, but we add some shortcodes to help you integrate your products into blog posts.

= Shortcodes =

Display a link to your product - either as a link to the post within your site using the post title as the link text:
`[product_link id=569]
[product_link id=569 title="This is a great new product"]`

Or as an external link to your Etsy product page:
`[product_link id=569 title="This is a great new product" external=true]`

Display your product's post content trimmed to whatever length you wish.  If no value is set for the length, the full content of the post will be displayed:
`[product_content id=569 length=50]`

Display your product's images in a Thickbox gallery using WordPress' built-in Thickbox jQuery and CSS:
`[product_images id=569]`

== Installation ==

1. Upload the 'etsy-importer' directory to the '/wp-content/plugins/' directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Visit the plugin settings page ('/wp-admin/options-general.php?page=etsy-importer/etsy-importer.php') to begin your import.
4. Enter your Etsy application API Key and your Etsy shop ID and hit the "Save Changes" button to save your information.
5. Hit the "Import Products" button to begin importing your products.

== Screenshots ==

1. In order to import your products, you first need to register an application with Etsy here: https://www.etsy.com/developers/register
2. Once you have created your app, click "Apps You've Made" in the sidebar and select your new app. On the app detail page, copy the value in the Keystring input field. This is your API Key.
3. To retrieve your shop's ID, begin by visiting the front page of your shop and viewing the source.
4. We want one specific line, whose meta name is "apple-itunes-app". The number you see below following "etsy://shop/" is your store ID.

== Other Notes ==

The Etsy Importer allows for the filtering of content throughout the course of posts being imported.  Below are the filters available, with details on what they do and how they can be modified.

= etsy_importer_default_post_status =
The default post status your post will be set to when a product being imported is currently ACTIVE.

Usage:
`function your_project_modify_etsy_importer_default_post_status() {

	return 'publish';
}
add_filter( 'etsy_importer_default_post_status', 'your_project_modify_etsy_importer_default_post_status' );`

= etsy_importer_updated_post_status =
The post status your existing post will be set to when its corresponding product is currently NOT ACTIVE.

Usage:
`function your_project_modify_etsy_importer_updated_post_status() {

	return 'draft';
}
add_filter( 'etsy_importer_updated_post_status', 'your_project_modify_etsy_importer_updated_post_status' );`

= etsy_importer_updated_post_args =
The post arguments passed to update posts when importing products.

Usage:
`function your_project_modify_etsy_importer_updated_post_args( $post_args ) {

	// Always set the post with the ID 45 to publish
	if( 45 == $post_args[ID] ) {
		$post_args[post_status] = 'publish';
	}

	return $post_args;
}
add_filter( 'etsy_importer_updated_post_args', 'your_project_modify_etsy_importer_updated_post_args' );`

= etsy_importer_custom_post_type_key =
The custom post type key.

Usage:
`function your_project_modify_etsy_importer_custom_post_type_key() {

	return 'my_products';
}
add_filter( 'etsy_importer_custom_post_type_key', 'your_project_modify_etsy_importer_custom_post_type_key' );`

= etsy_importer_category_key =
The custom post type's Category key.

Usage:
`function your_project_modify_etsy_importer_category_key() {

	return 'my_category';
}
add_filter( 'etsy_importer_category_key', 'your_project_modify_etsy_importer_category_key' );`

= etsy_importer_tag_key =
The custom post type's Tag key.

Usage:
`function your_project_modify_etsy_importer_tag_key() {

	return 'my_tag';
}
add_filter( 'etsy_importer_tag_key', 'your_project_modify_etsy_importer_tag_key' );`

= etsy_importer_product_link_shortcode =
Filter the output of the product link shortcode.

Usage:
`function your_project_modify_etsy_importer_product_link_shortcode( $output, $atts ) {

	// Output a custom value based on the post ID
	if ( 4439 == $atts['id'] ) {

		$output = 'This is my custom output for post ID 4439';

	}

	// Return the output
	return $output;

}
add_filter( 'etsy_importer_product_link_shortcode', 'your_project_modify_etsy_importer_product_link_shortcode', 10, 2 );`

= etsy_importer_product_content_shortcode =
Filter the output of the product content shortcode.

Usage:
`function your_project_modify_etsy_importer_product_content_shortcode( $output, $atts ) {

	// Output a custom value based on the post ID
	if ( 4439 == $atts['id'] ) {

		$output = 'This is my custom output for post ID 4439';

	}

	// Return the output
	return $output;

}
add_filter( 'etsy_importer_product_content_shortcode', 'your_project_modify_etsy_importer_product_content_shortcode', 10, 2 );`

= etsy_importer_product_images_shortcode_args =
Filter the args passed when displaying a product's images.

Usage:
`function your_project_modify_etsy_importer_product_images_shortcode_args( $args ) {

	// Limit the number of images displayed to 1
	$args['posts_per_page'] = 1;

	// Return the args
	return $args;

}
add_filter( 'etsy_importer_product_images_shortcode_args', 'your_project_modify_etsy_importer_product_images_shortcode_args' );`

= etsy_importer_product_images_shortcode_thumb_size =
Filter the size of the thumbnail iamge shown when displaying a product's images.

Usage:
`function your_project_modify_etsy_importer_product_images_shortcode_thumb_size( $atts ) {

	// Use the 'large' image size to display images
	return 'large';

}
add_filter( 'etsy_importer_product_images_shortcode_thumb_size', 'your_project_modify_etsy_importer_product_images_shortcode_thumb_size' );`

= etsy_importer_product_images_shortcode =
Filter the output of the product images shortcode.

Usage:
`function your_project_modify_etsy_importer_product_images_shortcode( $output, $atts ) {

	// Display a different output based on post ID
	if ( 4439 == $atts['id'] ) {
		$output = 'Visit my Etsy shop to view my images.';
	}

	// Return the output
	return $output;

}
add_filter( 'etsy_importer_product_images_shortcode', 'your_project_modify_etsy_importer_product_images_shortcode', 10, 2 );`

= etsy_importer_product_import_insert_args =
Filter the arguments passed when importing your products.

Usage:
`function your_project_modify_etsy_importer_product_import_insert_args( $args, $product ) {

	// Filter the post arguments used when importing your posts
	// In this example, we are adding "My product: " to the beginning of the post title
	$args['post_title'] = 'My product: ' . esc_html( $product->title );

	// Return the output
	return $args;

}
add_filter( 'etsy_importer_product_import_insert_args', 'your_project_modify_etsy_importer_product_import_insert_args', 10, 2 );`

== Changelog ==

= 1.3.0 =
* If a post already exists in your WP site and its product state is no longer "Active" in your Etsy shop, set that post's status to draft
* Add more documentation for all of the filters present in the plugin
* Update settings page and post meta fields to use CMB2
* Settings menu item moved inside the CPT menu

= 1.2.0 =
* Add ability to grab more than 25 products by paging through your store in sets of 25
* Add filters to hook into shortcodes, cron schedule, and post args when importing products
* General cleanup of code with the help of @jtsternberg.  Thanks!

= 1.1.1 =
* Fix line grabbing the title of a product when using a shortcode

= 1.1.0 =
* Add cron job to automatically pull in products once daily
* Add a conditional to fix the product_content shortcode from breaking when using a non-existent post ID
* Fix product_content typo in readme.txt

= 1.0.0 =
* Launch
