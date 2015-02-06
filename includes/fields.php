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
 *
 * @param  array $meta_boxes
 * @return array
 */
function etsy_metaboxes( array $meta_boxes ) {

	// Start with an underscore to hide fields from custom fields list
	$prefix = '_etsy_product_';

	$meta_boxes['etsy_metaboxes'] = array(
		'id'         => 'etsy_product_info',
		'title'      => __( 'Product Information', 'etsy' ),
		'object_types'      => array( 'etsy_products', ), // Post type
		'context'    => 'normal',
		'priority'   => 'high',
		'show_names' => true, // Show field names on the left
		// 'cmb_styles' => true, // Enqueue the CMB stylesheet on the frontend
		'fields'     => array(
			array(
				'name' => __( 'Price', 'etsy' ),
				'id'   => $prefix . 'price',
				'type' => 'text_small',
			),
			array(
				'name' => __( 'Etsy Link', 'etsy' ),
				'id'   => $prefix . 'url',
				'type' => 'text',
			),
			array(
				'name' => __( 'Production Year', 'etsy' ),
				'id'   => $prefix . 'made',
				'type' => 'text_medium',
			),
			array(
				'name' => __( 'Made For', 'etsy' ),
				'id'   => $prefix . 'made_for',
				'type' => 'text_medium',
			),
		)
	);

	// Add other metaboxes as needed

	return $meta_boxes;
}
add_filter( 'cmb2_meta_boxes', 'etsy_metaboxes' );
