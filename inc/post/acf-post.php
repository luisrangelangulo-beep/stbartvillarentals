<?php
/**
 * Magazine authoring fields and shared article presentation helpers.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'acf/init', static function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'      => 'group_lvc_magazine_editorial',
			'title'    => 'Magazine — Editorial Details',
			'fields'   => array(
				array(
					'key'          => 'field_lvc_blog_media_image_url',
					'label'        => 'Article image URL',
					'name'         => 'blog_media_image_url',
					'type'         => 'url',
					'instructions' => 'Optional unique landscape image. The featured image is used when this is empty.',
				),
				array(
					'key'          => 'field_lvc_read_time',
					'label'        => 'Read time',
					'name'         => 'read_time',
					'type'         => 'text',
					'placeholder'  => '6 min read',
					'instructions' => 'Optional. A reading time is calculated automatically when empty.',
				),
				array(
					'key'           => 'field_lvc_author_name',
					'label'         => 'Display author',
					'name'          => 'author_name',
					'type'          => 'text',
					'default_value' => lvc_config( 'brand_name', get_bloginfo( 'name' ) ) . ' Team',
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'post',
					),
				),
			),
			'position' => 'side',
			'active'   => true,
		)
	);
} );

if ( ! function_exists( 'lvc_blog_image_url' ) ) {
	function lvc_blog_image_url( $post_id, $size = 'large' ) {
		$image_url = trim( (string) lvc_field( 'blog_media_image_url', $post_id ) );
		if ( '' !== $image_url ) {
			return lvc_priority_image_url( $image_url );
		}

		$image_url = get_the_post_thumbnail_url( $post_id, $size );
		return $image_url ? lvc_priority_image_url( (string) $image_url ) : '';
	}
}

if ( ! function_exists( 'lvc_article_read_time' ) ) {
	function lvc_article_read_time( $post_id ) {
		$manual = trim( (string) lvc_field( 'read_time', $post_id ) );
		if ( '' !== $manual ) {
			return $manual;
		}

		$content = (string) get_post_field( 'post_content', $post_id );
		$words   = str_word_count( wp_strip_all_tags( strip_shortcodes( $content ) ) );
		$minutes = max( 1, (int) ceil( $words / 220 ) );
		return sprintf( '%d min read', $minutes );
	}
}
