<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FWW_Social_Publisher {

	private static ?FWW_Social_Publisher $instance = null;

	private function __construct() {
		$this->init_hooks();
	}

	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	// -------------------------------------------------------------------------
	// Bootstrap
	// -------------------------------------------------------------------------

	private function init_hooks(): void {
		add_action( 'admin_menu',              [ $this, 'add_settings_page' ] );
		add_action( 'admin_init',              [ $this, 'register_settings' ] );
		add_action( 'add_meta_boxes',          [ $this, 'add_meta_box' ] );
		add_action( 'admin_enqueue_scripts',   [ $this, 'enqueue_scripts' ] );
		add_action( 'transition_post_status',  [ $this, 'on_publish' ], 10, 3 );
		add_action( 'admin_notices',           [ $this, 'maybe_show_failed_notice' ] );

		add_action( 'wp_ajax_fww_post_to_facebook', [ $this, 'ajax_post_to_facebook' ] );
		add_action( 'wp_ajax_fww_post_to_instagram',[ $this, 'ajax_post_to_instagram' ] );
		add_action( 'wp_ajax_fww_post_to_telegram', [ $this, 'ajax_post_to_telegram' ] );
		add_action( 'wp_ajax_fww_test_facebook',    [ $this, 'ajax_test_facebook' ] );
		add_action( 'wp_ajax_fww_test_instagram',   [ $this, 'ajax_test_instagram' ] );
		add_action( 'wp_ajax_fww_test_telegram',    [ $this, 'ajax_test_telegram' ] );
	}

	// -------------------------------------------------------------------------
	// Activation / Deactivation
	// -------------------------------------------------------------------------

	public static function activate(): void {
		self::create_log_table();

		add_option( 'fww_social_publisher_options', [
			'auto_post_facebook'  => 1,
			'auto_post_instagram' => 1,
			'auto_post_telegram'  => 1,
			'category_filter'     => [],
			'ki_meta_key'         => '_claude_social_media_text',
		] );
	}

	public static function deactivate(): void {
		// intentionally empty
	}

	private static function create_log_table(): void {
		global $wpdb;

		$table           = $wpdb->prefix . 'fww_social_log';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table} (
			id         bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id    bigint(20) unsigned NOT NULL DEFAULT 0,
			platform   varchar(50)         NOT NULL DEFAULT '',
			status     varchar(20)         NOT NULL DEFAULT '',
			message    text                NOT NULL,
			created_at datetime            NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY post_id  (post_id),
			KEY platform (platform),
			KEY status   (status)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	// -------------------------------------------------------------------------
	// Settings
	// -------------------------------------------------------------------------

	public function add_settings_page(): void {
		add_options_page(
			__( 'FWW Social Publisher', 'fww-social-publisher' ),
			__( 'FWW Social Publisher', 'fww-social-publisher' ),
			'manage_options',
			'fww-social-publisher',
			[ $this, 'render_settings_page' ]
		);
	}

	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require FWW_SP_PLUGIN_DIR . 'admin/settings-page.php';
	}

	public function register_settings(): void {
		register_setting(
			'fww_social_publisher_options_group',
			'fww_social_publisher_options',
			[ 'sanitize_callback' => [ $this, 'sanitize_options' ] ]
		);
	}

	public function sanitize_options( array $input ): array {
		$clean = [];

		$clean['facebook_token']       = sanitize_text_field( $input['facebook_token']       ?? '' );
		$clean['facebook_page_id']     = sanitize_text_field( $input['facebook_page_id']     ?? '' );
		$clean['instagram_account_id'] = sanitize_text_field( $input['instagram_account_id'] ?? '' );
		$clean['instagram_token']      = sanitize_text_field( $input['instagram_token']      ?? '' );
		$clean['telegram_bot_token']   = sanitize_text_field( $input['telegram_bot_token']   ?? '' );
		$clean['telegram_chat_id']     = sanitize_text_field( $input['telegram_chat_id']     ?? '' );
		$clean['auto_post_facebook']   = ! empty( $input['auto_post_facebook'] )  ? 1 : 0;
		$clean['auto_post_instagram']  = ! empty( $input['auto_post_instagram'] ) ? 1 : 0;
		$clean['auto_post_telegram']   = ! empty( $input['auto_post_telegram'] )  ? 1 : 0;
		$clean['ki_meta_key']          = sanitize_text_field( $input['ki_meta_key'] ?? '_ki_social_media_text' );

		$cats = $input['category_filter'] ?? [];
		$clean['category_filter'] = is_array( $cats ) ? array_map( 'absint', $cats ) : [];

		return $clean;
	}

	// -------------------------------------------------------------------------
	// Meta Box
	// -------------------------------------------------------------------------

	public function add_meta_box(): void {
		add_meta_box(
			'fww-social-publisher',
			__( 'FWW Social Publisher', 'fww-social-publisher' ),
			[ $this, 'render_meta_box' ],
			'post',
			'side',
			'high'
		);
	}

	public function render_meta_box( WP_Post $post ): void {
		require FWW_SP_PLUGIN_DIR . 'admin/meta-box.php';
	}

	// -------------------------------------------------------------------------
	// Scripts
	// -------------------------------------------------------------------------

	public function enqueue_scripts( string $hook ): void {
		$allowed = [ 'post.php', 'post-new.php', 'settings_page_fww-social-publisher' ];
		if ( ! in_array( $hook, $allowed, true ) ) {
			return;
		}

		wp_enqueue_script(
			'fww-social-publisher-admin',
			FWW_SP_PLUGIN_URL . 'assets/admin.js',
			[ 'jquery' ],
			FWW_SP_VERSION,
			true
		);

		global $post;

		wp_localize_script( 'fww-social-publisher-admin', 'fwwSP', [
			'ajax_url'       => admin_url( 'admin-ajax.php' ),
			'nonce_meta_box' => wp_create_nonce( 'fww_meta_box_nonce' ),
			'nonce_settings' => wp_create_nonce( 'fww_settings_nonce' ),
			'post_id'        => isset( $post->ID ) ? (int) $post->ID : 0,
			'i18n'           => [
				'posting' => __( 'Posting…',          'fww-social-publisher' ),
				'testing' => __( 'Testing…',          'fww-social-publisher' ),
				'copied'  => __( 'Copied!',           'fww-social-publisher' ),
				'copy'    => __( 'Copy to Clipboard', 'fww-social-publisher' ),
			],
		] );
	}

	// -------------------------------------------------------------------------
	// Auto-post on publish
	// -------------------------------------------------------------------------

	public function on_publish( string $new_status, string $old_status, WP_Post $post ): void {
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}
		if ( 'post' !== $post->post_type ) {
			return;
		}
		if ( ! $this->post_matches_category_filter( $post->ID ) ) {
			return;
		}

		$options = get_option( 'fww_social_publisher_options', [] );
		$failed  = [];

		if ( ! empty( $options['auto_post_facebook'] ) ) {
			if ( ! $this->do_post_facebook( $post->ID ) ) {
				$failed[] = 'Facebook';
			}
		}

		if ( ! empty( $options['auto_post_instagram'] ) ) {
			if ( ! $this->do_post_instagram( $post->ID ) ) {
				$failed[] = 'Instagram';
			}
		}

		if ( ! empty( $options['auto_post_telegram'] ) ) {
			if ( ! $this->do_post_telegram( $post->ID ) ) {
				$failed[] = 'Telegram';
			}
		}

		if ( ! empty( $failed ) ) {
			set_transient( 'fww_post_failed_' . $post->ID, implode( ', ', $failed ), MINUTE_IN_SECONDS * 5 );
		}
	}

	// -------------------------------------------------------------------------
	// Admin notice for failed auto-posts
	// -------------------------------------------------------------------------

	public function maybe_show_failed_notice(): void {
		global $post;

		if ( empty( $post->ID ) ) {
			return;
		}

		$failed = get_transient( 'fww_post_failed_' . $post->ID );
		if ( ! $failed ) {
			return;
		}

		delete_transient( 'fww_post_failed_' . $post->ID );

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			sprintf(
				/* translators: %s: comma-separated platform names */
				esc_html__( 'FWW Social Publisher: Auto-post failed for %s. Check the log on the settings page.', 'fww-social-publisher' ),
				esc_html( $failed )
			)
		);
	}

	// -------------------------------------------------------------------------
	// Core posting logic
	// -------------------------------------------------------------------------

	public function do_post_facebook( int $post_id ): bool {
		if ( get_post_meta( $post_id, '_fww_facebook_posted', true ) ) {
			return false;
		}

		$options = get_option( 'fww_social_publisher_options', [] );
		$token   = $options['facebook_token']   ?? '';
		$page_id = $options['facebook_page_id'] ?? '';

		if ( empty( $token ) || empty( $page_id ) ) {
			$this->log( $post_id, 'facebook', 'error', __( 'Facebook credentials not configured.', 'fww-social-publisher' ) );
			return false;
		}

		$message      = $this->get_social_text( $post_id );
		$permalink    = (string) get_permalink( $post_id );
		$thumbnail_id = get_post_thumbnail_id( $post_id );
		$image_url    = $thumbnail_id ? ( (string) wp_get_attachment_image_url( $thumbnail_id, 'large' ) ) : '';

		$api    = new FWW_Facebook_API();
		$result = $image_url
			? $api->post_photo( $page_id, $token, $image_url, $message . "\n\n" . $permalink )
			: $api->post_feed(  $page_id, $token, $message, $permalink );

		if ( is_wp_error( $result ) ) {
			$this->log( $post_id, 'facebook', 'error', $result->get_error_message() );
			return false;
		}

		update_post_meta( $post_id, '_fww_facebook_posted', current_time( 'mysql' ) );
		$this->log(
			$post_id, 'facebook', 'success',
			/* translators: %s: Facebook object ID */
			sprintf( __( 'Posted successfully. Object ID: %s', 'fww-social-publisher' ), $result )
		);
		return true;
	}

	public function do_post_instagram( int $post_id ): bool {
		if ( get_post_meta( $post_id, '_fww_instagram_posted', true ) ) {
			return false;
		}

		$thumbnail_id = get_post_thumbnail_id( $post_id );
		if ( ! $thumbnail_id ) {
			$this->log( $post_id, 'instagram', 'skipped', __( 'No featured image – Instagram post skipped.', 'fww-social-publisher' ) );
			return false;
		}

		$options = get_option( 'fww_social_publisher_options', [] );
		$token   = ! empty( $options['instagram_token'] ) ? $options['instagram_token'] : ( $options['facebook_token'] ?? '' );
		$ig_id   = $options['instagram_account_id'] ?? '';

		if ( empty( $token ) || empty( $ig_id ) ) {
			$this->log( $post_id, 'instagram', 'error', __( 'Instagram credentials not configured.', 'fww-social-publisher' ) );
			return false;
		}

		$caption   = $this->get_social_text( $post_id ) . "\n\n" . (string) get_permalink( $post_id );
		$image_url = (string) wp_get_attachment_image_url( $thumbnail_id, 'large' );

		$api    = new FWW_Instagram_API();
		$result = $api->post( $ig_id, $token, $image_url, $caption );

		if ( is_wp_error( $result ) ) {
			$this->log( $post_id, 'instagram', 'error', $result->get_error_message() );
			return false;
		}

		update_post_meta( $post_id, '_fww_instagram_posted', current_time( 'mysql' ) );
		$this->log(
			$post_id, 'instagram', 'success',
			/* translators: %s: Instagram media ID */
			sprintf( __( 'Posted successfully. Media ID: %s', 'fww-social-publisher' ), $result )
		);
		return true;
	}

	public function do_post_telegram( int $post_id ): bool {
		if ( get_post_meta( $post_id, '_fww_telegram_posted', true ) ) {
			return false;
		}

		$options  = get_option( 'fww_social_publisher_options', [] );
		$token    = $options['telegram_bot_token'] ?? '';
		$chat_id  = $options['telegram_chat_id']   ?? '';

		if ( empty( $token ) || empty( $chat_id ) ) {
			$this->log( $post_id, 'telegram', 'error', __( 'Telegram credentials not configured.', 'fww-social-publisher' ) );
			return false;
		}

		$title         = get_the_title( $post_id );
		$social_text   = $this->get_social_text( $post_id );
		$permalink     = (string) get_permalink( $post_id );
		$thumbnail_id  = get_post_thumbnail_id( $post_id );
		$image_url     = $thumbnail_id ? ( (string) wp_get_attachment_image_url( $thumbnail_id, 'large' ) ) : '';

		// Build HTML-formatted message. All parts are escaped for Telegram's HTML parse mode.
		$text = '<b>' . htmlspecialchars( $title,       ENT_QUOTES | ENT_HTML5, 'UTF-8' ) . '</b>'
			. "\n\n"
			. htmlspecialchars( $social_text, ENT_QUOTES | ENT_HTML5, 'UTF-8' )
			. "\n\n"
			. htmlspecialchars( $permalink,   ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		$api    = new FWW_Telegram_API();
		$result = $api->post( $chat_id, $token, $text, $image_url );

		if ( is_wp_error( $result ) ) {
			$this->log( $post_id, 'telegram', 'error', $result->get_error_message() );
			return false;
		}

		update_post_meta( $post_id, '_fww_telegram_posted', current_time( 'mysql' ) );
		$this->log(
			$post_id, 'telegram', 'success',
			/* translators: %s: Telegram message ID */
			sprintf( __( 'Posted successfully. Message ID: %s', 'fww-social-publisher' ), $result )
		);
		return true;
	}

	// -------------------------------------------------------------------------
	// AJAX handlers
	// -------------------------------------------------------------------------

	public function ajax_post_to_facebook(): void {
		check_ajax_referer( 'fww_meta_box_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'fww-social-publisher' ) ] );
		}

		$post_id = absint( $_POST['post_id'] ?? 0 );
		if ( ! $post_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid post ID.', 'fww-social-publisher' ) ] );
		}

		delete_post_meta( $post_id, '_fww_facebook_posted' );
		$success = $this->do_post_facebook( $post_id );

		if ( $success ) {
			$date = get_post_meta( $post_id, '_fww_facebook_posted', true );
			wp_send_json_success( [
				'message' => sprintf(
					/* translators: %s: date/time */
					__( 'Posted to Facebook on %s', 'fww-social-publisher' ),
					esc_html( $date )
				),
			] );
		}

		wp_send_json_error( [ 'message' => __( 'Failed to post to Facebook. Check the log for details.', 'fww-social-publisher' ) ] );
	}

	public function ajax_post_to_instagram(): void {
		check_ajax_referer( 'fww_meta_box_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'fww-social-publisher' ) ] );
		}

		$post_id = absint( $_POST['post_id'] ?? 0 );
		if ( ! $post_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid post ID.', 'fww-social-publisher' ) ] );
		}

		delete_post_meta( $post_id, '_fww_instagram_posted' );
		$success = $this->do_post_instagram( $post_id );

		if ( $success ) {
			$date = get_post_meta( $post_id, '_fww_instagram_posted', true );
			wp_send_json_success( [
				'message' => sprintf(
					/* translators: %s: date/time */
					__( 'Posted to Instagram on %s', 'fww-social-publisher' ),
					esc_html( $date )
				),
			] );
		}

		// Distinguish "skipped" vs real error
		global $wpdb;
		$table      = $wpdb->prefix . 'fww_social_log';
		$last_entry = $wpdb->get_row( $wpdb->prepare(
			"SELECT status, message FROM {$table} WHERE post_id = %d AND platform = 'instagram' ORDER BY created_at DESC LIMIT 1",
			$post_id
		) );

		$msg = ( $last_entry && 'skipped' === $last_entry->status )
			? __( 'Instagram post skipped: no featured image set.', 'fww-social-publisher' )
			: __( 'Failed to post to Instagram. Check the log for details.', 'fww-social-publisher' );

		wp_send_json_error( [ 'message' => $msg ] );
	}

	public function ajax_post_to_telegram(): void {
		check_ajax_referer( 'fww_meta_box_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'fww-social-publisher' ) ] );
		}

		$post_id = absint( $_POST['post_id'] ?? 0 );
		if ( ! $post_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid post ID.', 'fww-social-publisher' ) ] );
		}

		delete_post_meta( $post_id, '_fww_telegram_posted' );
		$success = $this->do_post_telegram( $post_id );

		if ( $success ) {
			$date = get_post_meta( $post_id, '_fww_telegram_posted', true );
			wp_send_json_success( [
				'message' => sprintf(
					/* translators: %s: date/time */
					__( 'Posted to Telegram on %s', 'fww-social-publisher' ),
					esc_html( $date )
				),
			] );
		}

		wp_send_json_error( [ 'message' => __( 'Failed to post to Telegram. Check the log for details.', 'fww-social-publisher' ) ] );
	}

	public function ajax_test_facebook(): void {
		check_ajax_referer( 'fww_settings_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'fww-social-publisher' ) ] );
		}

		$options = get_option( 'fww_social_publisher_options', [] );
		$api     = new FWW_Facebook_API();
		$result  = $api->test_connection( $options['facebook_page_id'] ?? '', $options['facebook_token'] ?? '' );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		wp_send_json_success( [
			'message' => sprintf(
				/* translators: %s: page name */
				__( 'Connected as: %s', 'fww-social-publisher' ),
				esc_html( $result )
			),
		] );
	}

	public function ajax_test_instagram(): void {
		check_ajax_referer( 'fww_settings_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'fww-social-publisher' ) ] );
		}

		$options = get_option( 'fww_social_publisher_options', [] );
		$token   = ! empty( $options['instagram_token'] ) ? $options['instagram_token'] : ( $options['facebook_token'] ?? '' );
		$api     = new FWW_Instagram_API();
		$result  = $api->test_connection( $options['instagram_account_id'] ?? '', $token );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		wp_send_json_success( [
			'message' => sprintf(
				/* translators: %s: account username */
				__( 'Connected: @%s', 'fww-social-publisher' ),
				esc_html( $result )
			),
		] );
	}

	public function ajax_test_telegram(): void {
		check_ajax_referer( 'fww_settings_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'fww-social-publisher' ) ] );
		}

		$options = get_option( 'fww_social_publisher_options', [] );
		$api     = new FWW_Telegram_API();
		$result  = $api->test_connection( $options['telegram_chat_id'] ?? '', $options['telegram_bot_token'] ?? '' );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		wp_send_json_success( [
			'message' => sprintf(
				/* translators: %s: bot → channel info string */
				__( 'Connected: %s', 'fww-social-publisher' ),
				esc_html( $result )
			),
		] );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Returns the social media text for a post.
	 * Priority: WP-Claude-Optimizer meta field (configurable) → post excerpt → trimmed content.
	 * Handles the B64: prefix used by WP-Claude-Optimizer to store base64-encoded values.
	 */
	public function get_social_text( int $post_id ): string {
		$options  = get_option( 'fww_social_publisher_options', [] );
		$meta_key = $options['ki_meta_key'] ?? '_claude_social_media_text';

		if ( ! empty( $meta_key ) ) {
			$raw = get_post_meta( $post_id, $meta_key, true );
			if ( ! empty( $raw ) ) {
				return wp_strip_all_tags( $this->decode_meta_value( $raw ) );
			}
		}

		$post = get_post( $post_id );
		if ( ! empty( $post->post_excerpt ) ) {
			return wp_strip_all_tags( $post->post_excerpt );
		}

		return wp_trim_words( wp_strip_all_tags( $post->post_content ), 55 );
	}

	/**
	 * Returns the WhatsApp text for a post.
	 * Uses the dedicated _claude_whatsapp_text from WP-Claude-Optimizer if present,
	 * otherwise constructs title + social text + permalink.
	 */
	public function get_whatsapp_text( int $post_id ): string {
		$raw = get_post_meta( $post_id, '_claude_whatsapp_text', true );
		if ( ! empty( $raw ) ) {
			return wp_strip_all_tags( $this->decode_meta_value( $raw ) );
		}

		return get_the_title( $post_id )
			. "\n\n"
			. $this->get_social_text( $post_id )
			. "\n\n"
			. (string) get_permalink( $post_id );
	}

	/**
	 * Decodes a value stored by WP-Claude-Optimizer.
	 * Values are optionally base64-encoded with a "B64:" prefix.
	 */
	private function decode_meta_value( string $value ): string {
		if ( str_starts_with( $value, 'B64:' ) ) {
			$decoded = base64_decode( substr( $value, 4 ), true );
			return $decoded !== false ? $decoded : $value;
		}
		return $value;
	}

	private function post_matches_category_filter( int $post_id ): bool {
		$options     = get_option( 'fww_social_publisher_options', [] );
		$filter_cats = $options['category_filter'] ?? [];

		if ( empty( $filter_cats ) ) {
			return true;
		}

		$post_cats = wp_get_post_categories( $post_id, [ 'fields' => 'ids' ] );
		return ! empty( array_intersect( $post_cats, array_map( 'intval', $filter_cats ) ) );
	}

	public function log( int $post_id, string $platform, string $status, string $message ): void {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'fww_social_log',
			[
				'post_id'    => $post_id,
				'platform'   => $platform,
				'status'     => $status,
				'message'    => $message,
				'created_at' => current_time( 'mysql' ),
			],
			[ '%d', '%s', '%s', '%s', '%s' ]
		);
	}
}
