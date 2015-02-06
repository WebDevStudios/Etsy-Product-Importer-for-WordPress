<?php

class Etsy_Importer_Shortcodes {

	public function __construct() {
		// Add shortcodes
		add_shortcode( 'product_link', array( $this, 'product_link_shortcode' ) );
		add_shortcode( 'product_content', array( $this, 'product_content_shortcode' ) );
		add_shortcode( 'product_images', array( $this, 'product_images_shortcode' ) );
	}

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
	 * Add a shortcode to display the product images
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

}
