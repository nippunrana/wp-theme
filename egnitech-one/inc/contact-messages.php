<?php
declare(strict_types=1);

/**
 * Messages: the saved copy of every message sent through a Contact Form block, listed in
 * wp-admin for administrators only. Messages are read here, never written: there is no
 * "Add New" and no editor. They hold visitors' personal data, so WordPress's Export and
 * Erase Personal Data tools cover them.
 *
 * @package EgniTech_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the private Messages post type. Every capability maps to manage_options, the
 * capability Theme Options needs: Editors and Shop Managers can read private posts, and
 * would otherwise see every visitor's details.
 */
function egnitech_one_register_message_post_type(): void {
	$admin = 'manage_options';

	register_post_type(
		'egnitech_message',
		array(
			'labels'              => array(
				'name'               => __( 'Messages', 'egnitech-one' ),
				'singular_name'      => __( 'Message', 'egnitech-one' ),
				'menu_name'          => __( 'Messages', 'egnitech-one' ),
				'all_items'          => __( 'All Messages', 'egnitech-one' ),
				'edit_item'          => __( 'Message', 'egnitech-one' ),
				'search_items'       => __( 'Search Messages', 'egnitech-one' ),
				'not_found'          => __( 'No messages yet. They appear here when someone uses a Contact Form block.', 'egnitech-one' ),
				'not_found_in_trash' => __( 'No messages in the Trash.', 'egnitech-one' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'show_in_rest'        => false,
			'menu_position'       => 26,
			'menu_icon'           => 'dashicons-email-alt',
			'supports'            => false,
			'rewrite'             => false,
			'query_var'           => false,
			'map_meta_cap'        => true,
			'capabilities'        => array(
				'create_posts'           => 'do_not_allow',
				'edit_posts'             => $admin,
				'edit_others_posts'      => $admin,
				'edit_private_posts'     => $admin,
				'edit_published_posts'   => $admin,
				'read_private_posts'     => $admin,
				'publish_posts'          => $admin,
				'delete_posts'           => $admin,
				'delete_others_posts'    => $admin,
				'delete_private_posts'   => $admin,
				'delete_published_posts' => $admin,
			),
		)
	);
}
add_action( 'init', 'egnitech_one_register_message_post_type' );

/**
 * Restore a message from the Trash as private. WordPress restores posts as drafts, which
 * would drop a message from the unread count and show it with a "Draft" label.
 *
 * @param string $new_status      Status WordPress would restore to.
 * @param int    $post_id         The post.
 * @param string $previous_status Status before it was trashed.
 * @return string Status to restore to.
 */
function egnitech_one_untrash_message_status( string $new_status, int $post_id, string $previous_status ): string {
	return 'egnitech_message' === get_post_type( $post_id ) ? 'private' : $new_status;
}
add_filter( 'wp_untrash_post_status', 'egnitech_one_untrash_message_status', 10, 3 );

/* ----- The list ----- */

/**
 * Columns of the Messages list.
 *
 * @param array<string, string> $columns Default columns.
 * @return array<string, string> The list's columns.
 */
function egnitech_one_message_columns( array $columns ): array {
	return array(
		'cb'               => $columns['cb'],
		'egnitech_from'    => __( 'From', 'egnitech-one' ),
		'egnitech_message' => __( 'Message', 'egnitech-one' ),
		'egnitech_page'    => __( 'Sent from', 'egnitech-one' ),
		'egnitech_mail'    => __( 'Email', 'egnitech-one' ),
		'egnitech_date'    => __( 'Received', 'egnitech-one' ),
	);
}
add_filter( 'manage_egnitech_message_posts_columns', 'egnitech_one_message_columns' );

/**
 * Sort the list by when messages arrived. WordPress's own date column is not used: it
 * labels every private post "Last Modified".
 *
 * @param array<string, string> $columns Sortable columns.
 * @return array<string, string> Sortable columns.
 */
function egnitech_one_message_sortable_columns( array $columns ): array {
	$columns['egnitech_date'] = 'date';
	return $columns;
}
add_filter( 'manage_edit-egnitech_message_sortable_columns', 'egnitech_one_message_sortable_columns' );

/**
 * Put the row actions (Read, Trash) under the sender.
 *
 * @param string $column Default primary column.
 * @param string $screen Screen ID.
 * @return string Primary column.
 */
function egnitech_one_message_primary_column( string $column, string $screen ): string {
	return 'edit-egnitech_message' === $screen ? 'egnitech_from' : $column;
}
add_filter( 'list_table_primary_column', 'egnitech_one_message_primary_column', 10, 2 );

/**
 * What the email status of a message says, in the list and on the message.
 *
 * @param int $post_id Message ID.
 * @return string Escaped HTML.
 */
function egnitech_one_message_mail_status( int $post_id ): string {
	return match ( (string) get_post_meta( $post_id, '_egnitech_mail', true ) ) {
		'sent'   => esc_html__( 'Sent', 'egnitech-one' ),
		'failed' => '<strong>' . esc_html__( 'Failed', 'egnitech-one' ) . '</strong><br><small>' . esc_html( (string) get_post_meta( $post_id, '_egnitech_mail_error', true ) ) . '</small>',
		'held'   => '<strong>' . esc_html__( 'Not sent', 'egnitech-one' ) . '</strong><br><small>' . esc_html__( 'Matched a word in Disallowed Comment Keys (Settings > Discussion).', 'egnitech-one' ) . '</small>',
		default  => esc_html__( 'Not sent', 'egnitech-one' ),
	};
}

/**
 * Print a Messages column.
 *
 * @param string $column  Column name.
 * @param int    $post_id Message ID.
 */
function egnitech_one_message_column( string $column, int $post_id ): void {
	$post = get_post( $post_id );

	switch ( $column ) {
		case 'egnitech_from':
			$email = (string) get_post_meta( $post_id, '_egnitech_email', true );
			printf(
				'<strong><a class="row-title" href="%1$s">%2$s</a></strong><br><a href="%3$s">%4$s</a>',
				esc_url( (string) get_edit_post_link( $post_id ) ),
				esc_html( $post->post_title ),
				esc_url( 'mailto:' . $email ),
				esc_html( $email )
			);
			break;

		case 'egnitech_message':
			$subject = (string) get_post_meta( $post_id, '_egnitech_subject', true );
			if ( get_post_meta( $post_id, '_egnitech_unread', true ) ) {
				echo '<span class="egnitech-message-new">' . esc_html__( 'New', 'egnitech-one' ) . '</span> ';
			}
			if ( '' !== $subject ) {
				echo '<strong>' . esc_html( $subject ) . '</strong> ';
			}
			echo esc_html( wp_trim_words( wp_specialchars_decode( $post->post_content, ENT_QUOTES ), 24 ) );
			if ( egnitech_one_contact_has_many_links( array( 'subject' => $subject, 'message' => $post->post_content ) ) ) {
				echo '<br><small>' . esc_html__( 'Possible spam: many links.', 'egnitech-one' ) . '</small>';
			}
			break;

		case 'egnitech_page':
			$page_id = (int) get_post_meta( $post_id, '_egnitech_page', true );
			if ( $page_id && get_post( $page_id ) ) {
				printf( '<a href="%1$s">%2$s</a>', esc_url( (string) get_permalink( $page_id ) ), esc_html( get_the_title( $page_id ) ) );
			} else {
				echo '&mdash;';
			}
			break;

		case 'egnitech_mail':
			echo egnitech_one_message_mail_status( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped when built.
			break;

		case 'egnitech_date':
			echo esc_html( get_the_date( '', $post ) . ' ' . get_the_time( '', $post ) );
			break;
	}
}
add_action( 'manage_egnitech_message_posts_custom_column', 'egnitech_one_message_column', 10, 2 );

/**
 * Row actions: Read and the Trash links. Messages cannot be edited, so Edit and Quick Edit go.
 *
 * @param array<string, string> $actions Default actions.
 * @param WP_Post               $post    The message.
 * @return array<string, string> Actions.
 */
function egnitech_one_message_row_actions( array $actions, WP_Post $post ): array {
	if ( 'egnitech_message' !== $post->post_type ) {
		return $actions;
	}

	unset( $actions['edit'], $actions['inline hide-if-no-js'], $actions['view'] );
	if ( 'trash' !== $post->post_status ) {
		$actions = array( 'read' => sprintf( '<a href="%1$s">%2$s</a>', esc_url( (string) get_edit_post_link( $post->ID ) ), esc_html__( 'Read', 'egnitech-one' ) ) ) + $actions;
	}
	return $actions;
}
add_filter( 'post_row_actions', 'egnitech_one_message_row_actions', 10, 2 );

/**
 * Bulk actions: Trash only, since messages cannot be edited.
 *
 * @param array<string, string> $actions Default bulk actions.
 * @return array<string, string> Bulk actions.
 */
function egnitech_one_message_bulk_actions( array $actions ): array {
	unset( $actions['edit'] );
	return $actions;
}
add_filter( 'bulk_actions-edit-egnitech_message', 'egnitech_one_message_bulk_actions' );

/**
 * Views above the list: every message is private, so the "Private" view only repeats "All".
 *
 * @param array<string, string> $views Default views.
 * @return array<string, string> Views.
 */
function egnitech_one_message_views( array $views ): array {
	unset( $views['private'], $views['mine'] );
	return $views;
}
add_filter( 'views_edit-egnitech_message', 'egnitech_one_message_views' );

/**
 * Drop the "Private" label after each sender: every message is private.
 *
 * @param array<string, string> $states Post states.
 * @param WP_Post               $post   The post.
 * @return array<string, string> States.
 */
function egnitech_one_message_states( array $states, WP_Post $post ): array {
	if ( 'egnitech_message' === $post->post_type ) {
		unset( $states['private'] );
	}
	return $states;
}
add_filter( 'display_post_states', 'egnitech_one_message_states', 10, 2 );

/* ----- Reading a message ----- */

/**
 * Show a message read-only: its details, its text and a Reply button. The editing boxes go.
 *
 * @param WP_Post $post The message.
 */
function egnitech_one_message_meta_boxes( WP_Post $post ): void {
	remove_meta_box( 'submitdiv', 'egnitech_message', 'side' );
	remove_meta_box( 'slugdiv', 'egnitech_message', 'normal' );
	add_meta_box( 'egnitech_message_body', __( 'Message', 'egnitech-one' ), 'egnitech_one_render_message_box', 'egnitech_message', 'normal', 'high' );
	add_meta_box( 'egnitech_message_actions', __( 'Reply', 'egnitech-one' ), 'egnitech_one_render_message_actions', 'egnitech_message', 'side', 'high' );
}
add_action( 'add_meta_boxes_egnitech_message', 'egnitech_one_message_meta_boxes' );

/**
 * The message and the details sent with it.
 *
 * @param WP_Post $post The message.
 */
function egnitech_one_render_message_box( WP_Post $post ): void {
	$email   = (string) get_post_meta( $post->ID, '_egnitech_email', true );
	$phone   = (string) get_post_meta( $post->ID, '_egnitech_phone', true );
	$page_id = (int) get_post_meta( $post->ID, '_egnitech_page', true );

	$rows = array(
		__( 'From', 'egnitech-one' )         => esc_html( $post->post_title ),
		__( 'Email', 'egnitech-one' )        => sprintf( '<a href="%1$s">%2$s</a>', esc_url( 'mailto:' . $email ), esc_html( $email ) ),
		__( 'Phone', 'egnitech-one' )        => '' !== $phone ? sprintf( '<a href="%1$s">%2$s</a>', esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) ), esc_html( $phone ) ) : '',
		__( 'Subject', 'egnitech-one' )      => esc_html( (string) get_post_meta( $post->ID, '_egnitech_subject', true ) ),
		__( 'Sent from', 'egnitech-one' )    => $page_id && get_post( $page_id ) ? sprintf( '<a href="%1$s">%2$s</a>', esc_url( (string) get_permalink( $page_id ) ), esc_html( get_the_title( $page_id ) ) ) : '',
		__( 'Received', 'egnitech-one' )     => esc_html( get_the_date( '', $post ) . ' ' . get_the_time( '', $post ) ),
		__( 'Email status', 'egnitech-one' ) => egnitech_one_message_mail_status( $post->ID ),
		__( 'Agreed to', 'egnitech-one' )    => esc_html( (string) get_post_meta( $post->ID, '_egnitech_consent', true ) ),
	);

	echo '<table class="form-table egnitech-message-details" role="presentation"><tbody>';
	foreach ( array_filter( $rows ) as $label => $value ) {
		printf( '<tr><th scope="row">%1$s</th><td>%2$s</td></tr>', esc_html( $label ), $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each value is escaped above.
	}
	echo '</tbody></table>';

	echo '<div class="egnitech-message-text">' . nl2br( esc_html( $post->post_content ) ) . '</div>';
}

/**
 * Reply and Trash buttons beside the message.
 *
 * @param WP_Post $post The message.
 */
function egnitech_one_render_message_actions( WP_Post $post ): void {
	$email   = (string) get_post_meta( $post->ID, '_egnitech_email', true );
	$subject = (string) get_post_meta( $post->ID, '_egnitech_subject', true );
	/* translators: %s: the message's subject, or the site name. */
	$reply   = sprintf( __( 'Re: %s', 'egnitech-one' ), wp_specialchars_decode( '' !== $subject ? $subject : get_bloginfo( 'name' ), ENT_QUOTES ) );

	printf(
		'<p><a class="button button-primary button-large" href="%1$s">%2$s</a></p>',
		esc_url( 'mailto:' . $email . '?subject=' . rawurlencode( $reply ) ),
		esc_html__( 'Reply by email', 'egnitech-one' )
	);
	printf(
		'<p><a class="submitdelete" href="%1$s">%2$s</a> &middot; <a href="%3$s">%4$s</a></p>',
		esc_url( (string) get_delete_post_link( $post->ID ) ),
		esc_html__( 'Move to Trash', 'egnitech-one' ),
		esc_url( admin_url( 'edit.php?post_type=egnitech_message' ) ),
		esc_html__( 'All messages', 'egnitech-one' )
	);
}

/**
 * Mark a message read when it is opened.
 */
function egnitech_one_mark_message_read(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- opening a message only marks it read.
	$post_id = isset( $_GET['post'] ) && is_string( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	if ( $post_id && 'egnitech_message' === get_post_type( $post_id ) && current_user_can( 'edit_post', $post_id ) ) {
		delete_post_meta( $post_id, '_egnitech_unread' );
	}
}
add_action( 'load-post.php', 'egnitech_one_mark_message_read' );

/**
 * The number of unread messages, held ones included.
 *
 * @return int Unread messages.
 */
function egnitech_one_unread_message_count(): int {
	$query = new WP_Query(
		array(
			'post_type'              => 'egnitech_message',
			'post_status'            => 'private',
			'meta_key'               => '_egnitech_unread', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- a handful of rows.
			'fields'                 => 'ids',
			'posts_per_page'         => 1,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);
	return (int) $query->found_posts;
}

/**
 * Show the unread count on the Messages menu, as WordPress does for comments.
 */
function egnitech_one_message_menu_count(): void {
	global $menu;

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$count = egnitech_one_unread_message_count();
	if ( ! $count ) {
		return;
	}

	foreach ( $menu as $key => $item ) {
		if ( 'edit.php?post_type=egnitech_message' === ( $item[2] ?? '' ) ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- adds the count badge to the menu label.
			$menu[ $key ][0] .= sprintf(
				' <span class="awaiting-mod count-%1$d"><span class="pending-count" aria-hidden="true">%2$s</span><span class="screen-reader-text">%3$s</span></span>',
				$count,
				esc_html( number_format_i18n( $count ) ),
				/* translators: %s: number of unread messages. */
				esc_html( sprintf( _n( '%s unread message', '%s unread messages', $count, 'egnitech-one' ), number_format_i18n( $count ) ) )
			);
			break;
		}
	}
}
add_action( 'admin_menu', 'egnitech_one_message_menu_count', 99 );

/**
 * Styles for the Messages list and a message, on those screens only.
 */
function egnitech_one_message_admin_styles(): void {
	$screen = get_current_screen();
	if ( ! $screen || 'egnitech_message' !== $screen->post_type ) {
		return;
	}
	?>
	<style>
		.egnitech-message-new { display: inline-block; padding: 0 6px; border-radius: 3px; background: #2271b1; color: #fff; font-size: 11px; line-height: 18px; }
		.column-egnitech_from { width: 20%; }
		.column-egnitech_page, .column-egnitech_mail { width: 14%; }
		.egnitech-message-details th { width: 120px; padding-block: 8px; }
		.egnitech-message-details td { padding-block: 8px; }
		.egnitech-message-text { margin-top: 12px; padding: 16px; border: 1px solid #dcdcde; border-radius: 4px; background: #f6f7f7; font-size: 14px; line-height: 1.6; overflow-wrap: anywhere; }
	</style>
	<?php
}
add_action( 'admin_head', 'egnitech_one_message_admin_styles' );

/* ----- Personal data ----- */

/**
 * Messages sent with one email address, one batch at a time.
 *
 * @param string $email Email address.
 * @param int    $page  Batch, from 1.
 * @return WP_Post[] Messages, in the Trash too.
 */
function egnitech_one_messages_for_email( string $email, int $page ): array {
	return get_posts(
		array(
			'post_type'      => 'egnitech_message',
			'post_status'    => array( 'any', 'trash' ),
			'meta_key'       => '_egnitech_email', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- privacy requests are rare.
			'meta_value'     => strtolower( $email ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- privacy requests are rare.
			'posts_per_page' => 50,
			'paged'          => $page,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);
}

/**
 * Tools > Export Personal Data: a person's messages.
 *
 * @param string $email Email address.
 * @param int    $page  Batch, from 1.
 * @return array{data: array, done: bool} Export data.
 */
function egnitech_one_export_messages( string $email, int $page = 1 ): array {
	$posts = egnitech_one_messages_for_email( $email, $page );
	$data  = array();

	foreach ( $posts as $post ) {
		$page_id = (int) get_post_meta( $post->ID, '_egnitech_page', true );
		$fields  = array(
			__( 'Name', 'egnitech-one' )      => $post->post_title,
			__( 'Email', 'egnitech-one' )     => (string) get_post_meta( $post->ID, '_egnitech_email', true ),
			__( 'Phone', 'egnitech-one' )     => (string) get_post_meta( $post->ID, '_egnitech_phone', true ),
			__( 'Subject', 'egnitech-one' )   => (string) get_post_meta( $post->ID, '_egnitech_subject', true ),
			__( 'Message', 'egnitech-one' )   => $post->post_content,
			__( 'Sent from', 'egnitech-one' ) => $page_id ? (string) get_permalink( $page_id ) : '',
			__( 'Received', 'egnitech-one' )  => $post->post_date,
			__( 'Agreed to', 'egnitech-one' ) => (string) get_post_meta( $post->ID, '_egnitech_consent', true ),
		);

		$item = array();
		foreach ( array_filter( $fields ) as $name => $value ) {
			$item[] = array(
				'name'  => $name,
				'value' => $value,
			);
		}

		$data[] = array(
			'group_id'    => 'egnitech-one-messages',
			'group_label' => __( 'Contact form messages', 'egnitech-one' ),
			'item_id'     => 'egnitech-message-' . $post->ID,
			'data'        => $item,
		);
	}

	return array(
		'data' => $data,
		'done' => count( $posts ) < 50,
	);
}

/**
 * Tools > Erase Personal Data: delete a person's messages for good.
 * Always reads the first batch: each deletion moves later messages forward.
 *
 * @param string $email Email address.
 * @param int    $page  Batch, unused.
 * @return array{items_removed: bool, items_retained: bool, messages: array, done: bool} Result.
 */
function egnitech_one_erase_messages( string $email, int $page = 1 ): array {
	$posts   = egnitech_one_messages_for_email( $email, 1 );
	$removed = false;
	foreach ( $posts as $post ) {
		$removed = (bool) wp_delete_post( $post->ID, true ) || $removed;
	}

	return array(
		'items_removed'  => $removed,
		'items_retained' => false,
		'messages'       => array(),
		'done'           => count( $posts ) < 50 || ! $removed,
	);
}

/**
 * Add the messages to WordPress's personal data export.
 *
 * @param array $exporters Registered exporters.
 * @return array Exporters.
 */
function egnitech_one_register_message_exporter( array $exporters ): array {
	$exporters['egnitech-one-messages'] = array(
		'exporter_friendly_name' => __( 'Contact form messages', 'egnitech-one' ),
		'callback'               => 'egnitech_one_export_messages',
	);
	return $exporters;
}
add_filter( 'wp_privacy_personal_data_exporters', 'egnitech_one_register_message_exporter' );

/**
 * Add the messages to WordPress's personal data erasure.
 *
 * @param array $erasers Registered erasers.
 * @return array Erasers.
 */
function egnitech_one_register_message_eraser( array $erasers ): array {
	$erasers['egnitech-one-messages'] = array(
		'eraser_friendly_name' => __( 'Contact form messages', 'egnitech-one' ),
		'callback'             => 'egnitech_one_erase_messages',
	);
	return $erasers;
}
add_filter( 'wp_privacy_personal_data_erasers', 'egnitech_one_register_message_eraser' );

/**
 * Suggest a paragraph for the site's Privacy Policy (Settings > Privacy > Policy Guide).
 */
function egnitech_one_message_privacy_policy(): void {
	$text = '<p class="privacy-policy-tutorial">' . esc_html__( 'Add this if a page on your site shows a Contact Form block.', 'egnitech-one' ) . '</p>'
		. '<p><strong class="privacy-policy-tutorial">' . esc_html__( 'Suggested text:', 'egnitech-one' ) . ' </strong>'
		. esc_html__( 'When you send us a message through the contact form, we collect your name, email address and message, your phone number and subject if you give them, and the page you sent it from. We use them only to reply to you. The message is emailed to us, and a copy is kept in this website\'s administration area until we delete it. You can ask us for a copy of your messages, or ask us to delete them, at any time.', 'egnitech-one' )
		. '</p><p>'
		. esc_html__( 'Before the form is sent, Cloudflare Turnstile may check that it comes from a person rather than a program. Cloudflare\'s privacy policy explains what it collects for that check.', 'egnitech-one' )
		. '</p>';

	wp_add_privacy_policy_content( __( 'Contact Form', 'egnitech-one' ), $text );
}
add_action( 'admin_init', 'egnitech_one_message_privacy_policy' );
