<?php
/**
 * FastCGI Cache Purger logic for EgniTech One theme.
 *
 * @package EgniTech_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if the server software is Nginx.
 *
 * @return bool
 */
function egnitech_one_is_nginx(): bool {
	$server_software = $_SERVER['SERVER_SOFTWARE'] ?? '';
	return stripos( $server_software, 'nginx' ) !== false;
}

/**
 * Check if PHP is running via FastCGI (FPM or generic CGI-FCGI).
 *
 * @return bool
 */
function egnitech_one_is_fastcgi_active(): bool {
	$sapi_type = php_sapi_name();
	return in_array( $sapi_type, array( 'fpm-fcgi', 'cgi-fcgi' ), true );
}

/**
 * Get the current site host name (e.g., homegears.in).
 *
 * @return string
 */
function egnitech_one_get_current_host(): string {
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	return $host ? strtolower( $host ) : '';
}

/**
 * Get the configured cache directory path.
 *
 * @return string
 */
function egnitech_one_get_cache_directory(): string {
	$host = egnitech_one_get_current_host();
	if ( empty( $host ) ) {
		$host = $_SERVER['HTTP_HOST'] ?? 'example.com';
	}
	$default_path = '/var/cache/nginx/' . strtolower( $host );
	return get_option( 'egnitech_one_fastcgi_cache_path', $default_path );
}

/**
 * Check if FastCGI Caching options are enabled.
 *
 * @return bool
 */
function egnitech_one_is_fastcgi_cache_enabled(): bool {
	return get_option( 'egnitech_one_fastcgi_cache_enabled', 'no' ) === 'yes';
}

/**
 * Safely purge cache files belonging to the current site from the cache directory.
 *
 * Iterates recursively through the configured cache directory, opens each cache file,
 * reads the Nginx KEY header, and unlinks the file only if the key belongs to the current host.
 *
 * @return array Array with success count and error message if any.
 */
function egnitech_one_purge_all_site_cache(): array {
	$cache_dir = egnitech_one_get_cache_directory();
	$host      = egnitech_one_get_current_host();

	if ( empty( $cache_dir ) || ! is_dir( $cache_dir ) ) {
		return array(
			'success' => false,
			'count'   => 0,
			'message' => __( 'Cache directory does not exist or is invalid.', 'egnitech-one' ),
		);
	}

	if ( ! is_writable( $cache_dir ) ) {
		return array(
			'success' => false,
			'count'   => 0,
			'message' => __( 'Cache directory is not writable.', 'egnitech-one' ),
		);
	}

	if ( empty( $host ) ) {
		return array(
			'success' => false,
			'count'   => 0,
			'message' => __( 'Could not determine the site domain.', 'egnitech-one' ),
		);
	}

	$purged_count = 0;
	try {
		$directory = new RecursiveDirectoryIterator( $cache_dir, RecursiveDirectoryIterator::SKIP_DOTS );
		$iterator  = new RecursiveIteratorIterator( $directory, RecursiveIteratorIterator::CHILD_FIRST );

		foreach ( $iterator as $file ) {
			if ( $file->isFile() ) {
				$filepath = $file->getPathname();
				
				// Open file and read the first 512 bytes to parse the Nginx KEY header
				$handle = fopen( $filepath, 'r' );
				if ( $handle ) {
					$header = fread( $handle, 512 );
					fclose( $handle );

					// Look for the "KEY: " line in Nginx cache file header
					// Format usually: KEY: httpsGETexample.com/path
					if ( preg_match( '/KEY:\s*([^\r\n]+)/i', $header, $matches ) ) {
						$nginx_key = $matches[1];
						
						// Check if the key belongs to the current host
						if ( stripos( $nginx_key, $host ) !== false ) {
							if ( unlink( $filepath ) ) {
								$purged_count++;
							}
						}
					}
				}
			}
		}
	} catch ( Exception $e ) {
		return array(
			'success' => false,
			'count'   => $purged_count,
			'message' => $e->getMessage(),
		);
	}

	delete_transient( 'egnitech_one_fastcgi_cache_size' );

	return array(
		'success' => true,
		'count'   => $purged_count,
		'message' => sprintf( __( 'Cleared %d cache files successfully.', 'egnitech-one' ), $purged_count ),
	);
}

/**
 * Purge the cache for a specific URL.
 *
 * Generates Nginx cache keys for the URL, calculates MD5 hashes, and deletes matching files.
 * Alternatively, if HTTP purge is selected, sends an HTTP PURGE request to the loopback URL.
 *
 * @param string $url The absolute URL to purge.
 * @return bool True on success, false on failure.
 */
function egnitech_one_purge_url( string $url ): bool {
	if ( ! egnitech_one_is_fastcgi_cache_enabled() ) {
		return false;
	}

	$purge_method = get_option( 'egnitech_one_fastcgi_purge_method', 'filesystem' );

	if ( 'http_purge' === $purge_method ) {
		// Use HTTP PURGE loopback request
		// Replace scheme or URL component for purge target if Nginx module requires /purge/ prefix
		$purge_url = str_replace( array( 'http://', 'https://' ), array( 'http://', 'https://' ), $url );
		
		// Send request with method PURGE
		$response = wp_remote_request( $purge_url, array(
			'method'      => 'PURGE',
			'sslverify'   => false,
			'timeout'     => 5,
			'redirection' => 0,
		) );

		return ! is_wp_error( $response ) && in_array( wp_remote_retrieve_response_code( $response ), array( 200, 204, 404 ), true );
	}

	// Filesystem purging: calculate potential MD5 cache keys
	$parsed_url = wp_parse_url( $url );
	$scheme     = $parsed_url['scheme'] ?? ( is_ssl() ? 'https' : 'http' );
	$host       = $parsed_url['host'] ?? '';
	$path       = $parsed_url['path'] ?? '/';
	$query      = isset( $parsed_url['query'] ) ? '?' . $parsed_url['query'] : '';
	
	if ( empty( $host ) ) {
		return false;
	}

	// Nginx cache keys can vary based on configuration. We calculate potential keys:
	// 1. $scheme . 'GET' . $host . $path . $query (Standard)
	// 2. $scheme . 'GET' . $host . $path (No query)
	// 3. $scheme . 'GET' . $host . '/index.php' (If rewritten internally in WordPress setups)
	
	$possible_keys = array(
		$scheme . 'GET' . $host . $path . $query,
		$scheme . 'GET' . $host . $path,
	);

	if ( '/' === $path || empty( $path ) ) {
		$possible_keys[] = $scheme . 'GET' . $host . '/index.php' . $query;
		$possible_keys[] = $scheme . 'GET' . $host . '/index.php';
	}

	$cache_dir = egnitech_one_get_cache_directory();
	$purged    = false;

	foreach ( $possible_keys as $key ) {
		$hash = md5( $key );
		
		// levels=1:2 directory structure
		// Level 1: Last 1 char of MD5
		// Level 2: Next 2 chars of MD5
		$level1 = substr( $hash, -1 );
		$level2 = substr( $hash, -3, 2 );

		$filepath = rtrim( $cache_dir, '/' ) . '/' . $level1 . '/' . $level2 . '/' . $hash;

		if ( file_exists( $filepath ) ) {
			if ( unlink( $filepath ) ) {
				$purged = true;
			}
		}
	}

	delete_transient( 'egnitech_one_fastcgi_cache_size' );

	return $purged;
}

/**
 * Hook to clear the cache when a post status changes (publish, update, delete).
 *
 * @param string  $new_status New post status.
 * @param string  $old_status Old post status.
 * @param WP_Post $post       Post object.
 */
function egnitech_one_purge_post_on_transition( string $new_status, string $old_status, WP_Post $post ): void {
	// Only purge on updates, publications, or deletions of public post types
	$public_statuses = array( 'publish', 'trash' );
	if ( ! in_array( $new_status, $public_statuses, true ) && ! in_array( $old_status, $public_statuses, true ) ) {
		return;
	}

	if ( wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) ) {
		return;
	}

	// 1. Purge the specific post/page URL
	$post_url = get_permalink( $post->ID );
	if ( $post_url ) {
		egnitech_one_purge_url( $post_url );
	}

	// 2. Purge the homepage
	egnitech_one_purge_url( home_url( '/' ) );

	// 3. Purge parent post URL if applicable
	if ( ! empty( $post->post_parent ) ) {
		$parent_url = get_permalink( $post->post_parent );
		if ( $parent_url ) {
			egnitech_one_purge_url( $parent_url );
		}
	}
}
add_action( 'transition_post_status', 'egnitech_one_purge_post_on_transition', 10, 3 );

/**
 * Format bytes into human readable size.
 *
 * @param float $bytes     Number of bytes.
 * @param int   $precision Decimal precision.
 * @return string
 */
function egnitech_one_format_bytes( float $bytes, int $precision = 2 ): string {
	$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );

	$bytes = max( $bytes, 0 );
	$pow   = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
	$pow   = min( $pow, count( $units ) - 1 );

	$bytes /= pow( 1024, $pow );

	return round( $bytes, $precision ) . ' ' . $units[ $pow ];
}

/**
 * Get the size of the FastCGI cache files belonging to this site.
 *
 * @param bool $force_recalc Force recalculation instead of using cached value.
 * @return array Calculated size properties.
 */
function egnitech_one_get_site_cache_size( bool $force_recalc = false ): array {
	$cache_key = 'egnitech_one_fastcgi_cache_size';
	$cached    = get_transient( $cache_key );

	if ( ! $force_recalc && is_array( $cached ) ) {
		return $cached;
	}

	$cache_dir = egnitech_one_get_cache_directory();
	$host      = egnitech_one_get_current_host();

	if ( empty( $cache_dir ) || ! is_dir( $cache_dir ) || empty( $host ) ) {
		return array(
			'bytes'     => -1,
			'formatted' => __( 'Not calculated', 'egnitech-one' ),
			'timestamp' => 0,
		);
	}

	$total_bytes = 0;

	try {
		$directory = new RecursiveDirectoryIterator( $cache_dir, RecursiveDirectoryIterator::SKIP_DOTS );
		$iterator  = new RecursiveIteratorIterator( $directory, RecursiveIteratorIterator::CHILD_FIRST );

		foreach ( $iterator as $file ) {
			if ( $file->isFile() ) {
				$filepath = $file->getPathname();
				$handle   = fopen( $filepath, 'r' );
				if ( $handle ) {
					$header = fread( $handle, 512 );
					fclose( $handle );

					if ( preg_match( '/KEY:\s*([^\r\n]+)/i', $header, $matches ) ) {
						$nginx_key = $matches[1];
						if ( stripos( $nginx_key, $host ) !== false ) {
							$total_bytes += $file->getSize();
						}
					}
				}
			}
		}
	} catch ( Exception $e ) {
		// Suppress iterator errors
	}

	$data = array(
		'bytes'     => $total_bytes,
		'formatted' => egnitech_one_format_bytes( $total_bytes ),
		'timestamp' => time(),
	);

	// Cache the result for 24 hours (or until cleared/recalculated)
	set_transient( $cache_key, $data, DAY_IN_SECONDS );

	return $data;
}

/**
 * AJAX Handler for clearing the entire site's cache.
 */
function egnitech_one_ajax_clear_entire_cache(): void {
	check_ajax_referer( 'egnitech_one_clear_cache_nonce', 'security' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'egnitech-one' ) ) );
	}

	$result = egnitech_one_purge_all_site_cache();

	// Reset cache size transient to 0 bytes
	set_transient( 'egnitech_one_fastcgi_cache_size', array(
		'bytes'     => 0,
		'formatted' => egnitech_one_format_bytes( 0 ),
		'timestamp' => time(),
	), DAY_IN_SECONDS );

	if ( $result['success'] ) {
		wp_send_json_success( array(
			'message'   => $result['message'],
			'formatted' => egnitech_one_format_bytes( 0 ),
		) );
	} else {
		wp_send_json_error( array( 'message' => $result['message'] ) );
	}
}
add_action( 'wp_ajax_egnitech_one_clear_fastcgi_cache', 'egnitech_one_ajax_clear_entire_cache' );

/**
 * AJAX Handler for recalculating the cache size on demand.
 */
function egnitech_one_ajax_recalculate_cache_size(): void {
	check_ajax_referer( 'egnitech_one_clear_cache_nonce', 'security' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Permission denied.', 'egnitech-one' ) ) );
	}

	$size_data = egnitech_one_get_site_cache_size( true );

	wp_send_json_success( array(
		'formatted' => $size_data['formatted'],
		'message'   => __( 'Cache size recalculated.', 'egnitech-one' ),
	) );
}
add_action( 'wp_ajax_egnitech_one_recalculate_cache_size', 'egnitech_one_ajax_recalculate_cache_size' );

/**
 * Add a button to the admin bar to clear the FastCGI Cache.
 *
 * @param WP_Admin_Bar $wp_admin_bar Admin Bar object.
 */
function egnitech_one_admin_bar_clear_cache( $wp_admin_bar ): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! egnitech_one_is_nginx() || ! egnitech_one_is_fastcgi_cache_enabled() ) {
		return;
	}

	// 1. Parent Node
	$wp_admin_bar->add_node( array(
		'id'    => 'egnitech-clear-fastcgi-cache',
		'title' => '<span class="ab-icon dashicons-performance" style="top: 2px;"></span>' . __( 'FastCGI Cache', 'egnitech-one' ),
		'href'  => '#',
	) );

	// 2. Child Node: Clear Cache action
	$wp_admin_bar->add_node( array(
		'parent' => 'egnitech-clear-fastcgi-cache',
		'id'     => 'egnitech-clear-fastcgi-cache-action',
		'title'  => '<span class="ab-icon dashicons-trash" style="top: 2px;"></span>' . __( 'Clear Entire Cache', 'egnitech-one' ),
		'href'   => '#',
		'meta'   => array(
			'title' => __( 'Clear Entire Cache', 'egnitech-one' ),
			'class' => 'egnitech-clear-cache-admin-bar-btn',
		),
	) );

	// Get cache size info (transient-cached or fallback)
	$size_info      = egnitech_one_get_site_cache_size( false );
	$formatted_size = ( $size_info['bytes'] === -1 ) ? __( 'Not calculated', 'egnitech-one' ) : $size_info['formatted'];

	// 3. Child Node: Size Display
	$wp_admin_bar->add_node( array(
		'parent' => 'egnitech-clear-fastcgi-cache',
		'id'     => 'egnitech-fastcgi-cache-size-display',
		'title'  => sprintf( __( 'Size: %s', 'egnitech-one' ), '<strong class="egnitech-ab-size">' . esc_html( $formatted_size ) . '</strong>' ),
		'href'   => '#',
	) );

	// 4. Child Node: Recalculate action
	$wp_admin_bar->add_node( array(
		'parent' => 'egnitech-clear-fastcgi-cache',
		'id'     => 'egnitech-recalculate-fastcgi-cache-size',
		'title'  => '<span class="ab-icon dashicons-update" style="top: 2px;"></span>' . __( 'Recalculate Size', 'egnitech-one' ),
		'href'   => '#',
		'meta'   => array(
			'title' => __( 'Recalculate Cache Size', 'egnitech-one' ),
			'class' => 'egnitech-recalc-size-admin-bar-btn',
		),
	) );
}
add_action( 'admin_bar_menu', 'egnitech_one_admin_bar_clear_cache', 100 );

/**
 * Print script to handle admin bar AJAX actions (clear cache & recalculate size).
 */
function egnitech_one_admin_bar_clear_cache_scripts(): void {
	if ( ! is_admin_bar_showing() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! egnitech_one_is_nginx() || ! egnitech_one_is_fastcgi_cache_enabled() ) {
		return;
	}
	?>
	<script type="text/javascript">
	(function() {
		document.addEventListener('DOMContentLoaded', function() {
			// Clear Cache Handler
			var clearBtn = document.querySelector('.egnitech-clear-cache-admin-bar-btn a');
			if (clearBtn) {
				clearBtn.addEventListener('click', function(e) {
					e.preventDefault();
					if (clearBtn.getAttribute('data-working') === '1') return;

					var originalText = clearBtn.innerHTML;
					clearBtn.setAttribute('data-working', '1');
					clearBtn.innerHTML = '<span class="ab-icon dashicons-update spin" style="animation: spin 1s linear infinite; top: 2px;"></span>' + <?php echo wp_json_encode( __( 'Clearing…', 'egnitech-one' ) ); ?>;

					// Add dynamic styling for rotation if not already in style
					if (!document.getElementById('egnitech-spin-style')) {
						var style = document.createElement('style');
						style.id = 'egnitech-spin-style';
						style.innerHTML = '@keyframes spin { 100% { transform: rotate(360deg); } }';
						document.head.appendChild(style);
					}

					var data = new URLSearchParams();
					data.append('action', 'egnitech_one_clear_fastcgi_cache');
					data.append('security', <?php echo wp_json_encode( wp_create_nonce( 'egnitech_one_clear_cache_nonce' ) ); ?>);

					fetch(<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, {
						method: 'POST',
						body: data,
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded'
						}
					})
					.then(function(response) { return response.json(); })
					.then(function(res) {
						clearBtn.removeAttribute('data-working');
						if (res.success) {
							clearBtn.innerHTML = '<span class="ab-icon dashicons-yes" style="top: 2px; color: #46b450;"></span>' + <?php echo wp_json_encode( __( 'Cleared!', 'egnitech-one' ) ); ?>;
							
							// Update Admin Bar display
							var sizeDisplayEl = document.querySelector('#wp-admin-bar-egnitech-fastcgi-cache-size-display .egnitech-ab-size');
							if (sizeDisplayEl) {
								sizeDisplayEl.textContent = res.data.formatted || '0 B';
							}

							// Update Options page size display if open
							var optionsSizeVal = document.getElementById('egnitech-fastcgi-cache-size-val');
							if (optionsSizeVal) {
								optionsSizeVal.textContent = res.data.formatted || '0 B';
							}

							setTimeout(function() {
								clearBtn.innerHTML = originalText;
							}, 3000);
						} else {
							clearBtn.innerHTML = '<span class="ab-icon dashicons-warning" style="top: 2px; color: #dc3232;"></span>' + <?php echo wp_json_encode( __( 'Error', 'egnitech-one' ) ); ?>;
							alert(res.data.message || <?php echo wp_json_encode( __( 'Failed to clear cache.', 'egnitech-one' ) ); ?>);
							setTimeout(function() {
								clearBtn.innerHTML = originalText;
							}, 5000);
						}
					})
					.catch(function() {
						clearBtn.removeAttribute('data-working');
						clearBtn.innerHTML = '<span class="ab-icon dashicons-warning" style="top: 2px; color: #dc3232;"></span>' + <?php echo wp_json_encode( __( 'Failed', 'egnitech-one' ) ); ?>;
						setTimeout(function() {
							clearBtn.innerHTML = originalText;
						}, 5000);
					});
				});
			}

			// Recalculate Size Handler
			var recalcBtn = document.querySelector('.egnitech-recalc-size-admin-bar-btn a');
			var sizeDisplay = document.querySelector('#wp-admin-bar-egnitech-fastcgi-cache-size-display .egnitech-ab-size');
			if (recalcBtn && sizeDisplay) {
				recalcBtn.addEventListener('click', function(e) {
					e.preventDefault();
					if (recalcBtn.getAttribute('data-working') === '1') return;

					var originalText = recalcBtn.innerHTML;
					recalcBtn.setAttribute('data-working', '1');
					recalcBtn.innerHTML = '<span class="ab-icon dashicons-update spin" style="animation: spin 1s linear infinite; top: 2px;"></span>' + <?php echo wp_json_encode( __( 'Calculating…', 'egnitech-one' ) ); ?>;

					var data = new URLSearchParams();
					data.append('action', 'egnitech_one_recalculate_cache_size');
					data.append('security', <?php echo wp_json_encode( wp_create_nonce( 'egnitech_one_clear_cache_nonce' ) ); ?>);

					fetch(<?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, {
						method: 'POST',
						body: data,
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded'
						}
					})
					.then(function(response) { return response.json(); })
					.then(function(res) {
						recalcBtn.removeAttribute('data-working');
						recalcBtn.innerHTML = originalText;
						if (res.success) {
							sizeDisplay.textContent = res.data.formatted;
							var optionsSizeVal = document.getElementById('egnitech-fastcgi-cache-size-val');
							if (optionsSizeVal) {
								optionsSizeVal.textContent = res.data.formatted;
							}
						} else {
							alert(res.data.message || <?php echo wp_json_encode( __( 'Failed to recalculate size.', 'egnitech-one' ) ); ?>);
						}
					})
					.catch(function() {
						recalcBtn.removeAttribute('data-working');
						recalcBtn.innerHTML = originalText;
					});
				});
			}
		});
	})();
	</script>
	<?php
}
add_action( 'wp_print_footer_scripts', 'egnitech_one_admin_bar_clear_cache_scripts', 999 );
add_action( 'admin_print_footer_scripts', 'egnitech_one_admin_bar_clear_cache_scripts', 999 );
