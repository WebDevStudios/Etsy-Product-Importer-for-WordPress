<?php
/**
 * Include and setup custom metaboxes and fields.
 *
 * @category Etsy Importer
 * @package  Metaboxes
 * @license  http://www.opensource.org/licenses/gpl-license.php GPL v2.0 (or later)
 * @link     https://github.com/WebDevStudios/Custom-Metaboxes-and-Fields-for-WordPress
 */

/**
 * Define the metabox and field configurations.
 */
function etsy_metaboxes() {

	// Start with an underscore to hide fields from custom fields list.
	$prefix = '_etsy_product_';

	$etsy_metaboxes = new_cmb2_box( array(
		'id'           => 'etsy_product_info',
		'title' => __( 'Product Information', 'etsy_importer' ),
		'object_types' => array( apply_filters( 'etsy_importer_custom_post_type_key', 'etsy_products' ) ), // Post type.
		'context' => 'normal',
		'priority' => 'high',
		'show_names' => true, // Show field names on the left.
	) );

	$etsy_metaboxes->add_field( array(
		'name' => __( 'Price', 'etsy_importer' ),
		'id'   => $prefix . 'price',
		'type' => 'text_small',
	) );

	$etsy_metaboxes->add_field( array(
		'name' => __( 'Etsy Link', 'etsy_importer' ),
		'id'   => $prefix . 'url',
		'type' => 'text',
	) );

	$etsy_metaboxes->add_field( array(
		'name' => __( 'Production Year', 'etsy_importer' ),
		'id'   => $prefix . 'made',
		'type' => 'text_medium',
	) );

	$etsy_metaboxes->add_field( array(
		'name' => __( 'Made For', 'etsy_importer' ),
		'id'   => $prefix . 'made_for',
		'type' => 'text_medium',
	) );

	$etsy_metaboxes->add_field( array(
		'name'       => __( 'Etsy Product ID', 'etsy_importer' ),
		'id'         => $prefix . 'id',
		'type'       => 'text_small',
		'attributes' => array(
			'disabled' => 'disabled',
		),
	) );

}
add_action( 'cmb2_init', 'etsy_metaboxes' );
