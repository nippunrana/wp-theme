<?php
declare(strict_types=1);

/**
 * EgniTech One Child Theme — functions and definitions.
 *
 * @package EgniTech_One_Child
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', function (): void {
	register_block_pattern_category(
		'egnitech-one-child',
		[ 'label' => __( 'EgniTech One Child', 'egnitech-one-child' ) ]
	);
} );
