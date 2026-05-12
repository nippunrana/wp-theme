<?php
/**
 * Frontend execution for Custom Scripts.
 * Combines scripts into 6 optimized blocks and uses minimal vanilla JS for deferred injection.
 *
 * @package EgniTech_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EgniTech_One_Custom_Scripts {

	private static $blocks = [
		'header_normal'    => '',
		'footer_normal'    => '',
		'header_after_dom' => '',
		'footer_after_dom' => '',
		'header_delayed'   => '',
		'footer_delayed'   => '',
	];

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'prepare_scripts' ] );
		add_action( 'wp_head', [ __CLASS__, 'output_header_normal' ], 99 );
		add_action( 'wp_footer', [ __CLASS__, 'output_footer_scripts' ], 99 );
	}

	/**
	 * Read from DB and concatenate into the 6 strings once per request.
	 */
	public static function prepare_scripts() {
		$raw_scripts = get_option( 'egnitech_one_custom_scripts', '[]' );
		$scripts     = json_decode( $raw_scripts, true );

		if ( ! is_array( $scripts ) || empty( $scripts ) ) {
			return;
		}

		foreach ( $scripts as $s ) {
			// Skip inactive scripts or empty code.
			$is_active = isset( $s['is_active'] ) ? filter_var( $s['is_active'], FILTER_VALIDATE_BOOLEAN ) : true;
			if ( ! $is_active || empty( $s['code'] ) ) {
				continue;
			}

			$loc  = isset( $s['location'] ) ? $s['location'] : 'header'; // header or footer
			$type = isset( $s['load_type'] ) ? $s['load_type'] : 'normal'; // normal, after_dom, delayed_3s

			// Map type to block key
			if ( 'delayed_3s' === $type ) {
				$key_suffix = 'delayed';
			} elseif ( 'after_dom' === $type ) {
				$key_suffix = 'after_dom';
			} else {
				$key_suffix = 'normal';
			}

			$block_key = $loc . '_' . $key_suffix;

			if ( isset( self::$blocks[ $block_key ] ) ) {
				self::$blocks[ $block_key ] .= $s['code'] . "\n";
			}
		}
	}

	/**
	 * Output immediate header scripts.
	 */
	public static function output_header_normal() {
		if ( ! empty( self::$blocks['header_normal'] ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo self::$blocks['header_normal'];
		}
	}

	/**
	 * Output immediate footer scripts AND the inline JS worker for deferred scripts.
	 */
	public static function output_footer_scripts() {
		// Output Normal Footer Scripts
		if ( ! empty( self::$blocks['footer_normal'] ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo self::$blocks['footer_normal'];
		}

		// Check if we have any deferred scripts at all
		$has_after_dom = ! empty( self::$blocks['header_after_dom'] ) || ! empty( self::$blocks['footer_after_dom'] );
		$has_delayed   = ! empty( self::$blocks['header_delayed'] ) || ! empty( self::$blocks['footer_delayed'] );

		if ( ! $has_after_dom && ! $has_delayed ) {
			return; // Nothing else to do
		}

		?>
		<script id="egnitech-deferred-scripts-worker">
			(function() {
				// Safely inject raw HTML (including <script> tags) so they execute.
				function injectHTML(htmlString, targetSelector) {
					if (!htmlString.trim()) return;
					var target = document.querySelector(targetSelector);
					if (!target) return;
					try {
						var fragment = document.createRange().createContextualFragment(htmlString);
						target.appendChild(fragment);
					} catch(e) {
						console.error('EgniTech One: Failed to inject custom scripts.', e);
					}
				}

				// Data encoded safely using base64 via PHP
				var config = {
					afterDomHeader: "<?php echo base64_encode( self::$blocks['header_after_dom'] ); ?>",
					afterDomFooter: "<?php echo base64_encode( self::$blocks['footer_after_dom'] ); ?>",
					delayedHeader: "<?php echo base64_encode( self::$blocks['header_delayed'] ); ?>",
					delayedFooter: "<?php echo base64_encode( self::$blocks['footer_delayed'] ); ?>"
				};

				function decodeHtml(b64) {
					return b64 ? decodeURIComponent(escape(window.atob(b64))) : '';
				}

				function injectClass(type) {
					if (type === 'after_dom') {
						injectHTML(decodeHtml(config.afterDomHeader), 'head');
						injectHTML(decodeHtml(config.afterDomFooter), 'body');
					} else if (type === 'delayed') {
						injectHTML(decodeHtml(config.delayedHeader), 'head');
						injectHTML(decodeHtml(config.delayedFooter), 'body');
					}
				}

				// 1. After DOM Processing
				<?php if ( $has_after_dom ) : ?>
					if (document.readyState === 'loading') {
						document.addEventListener('DOMContentLoaded', function() {
							injectClass('after_dom');
						});
					} else {
						injectClass('after_dom');
					}
				<?php endif; ?>

				// 2. Delayed 3s Processing
				<?php if ( $has_delayed ) : ?>
					setTimeout(function() {
						injectClass('delayed');
					}, 3000);
				<?php endif; ?>
			})();
		</script>
		<?php
	}
}

EgniTech_One_Custom_Scripts::init();
