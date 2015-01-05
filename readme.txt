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
* Add the Price, Etsy Product Link, Production Year and Made For (men/women) meta information to the post
* Check for new products in your Etsy shop daily and import them automatically.  Hands free!

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

== Changelog ==

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
