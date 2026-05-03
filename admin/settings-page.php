<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

$options    = get_option( 'fww_social_publisher_options', [] );
$categories = get_categories( [ 'hide_empty' => false ] );
$filter_cats = array_map( 'intval', $options['category_filter'] ?? [] );

global $wpdb;
$log_table = $wpdb->prefix . 'fww_social_log';
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$logs = $wpdb->get_results(
	"SELECT l.*, p.post_title
	 FROM {$log_table} l
	 LEFT JOIN {$wpdb->posts} p ON l.post_id = p.ID
	 ORDER BY l.created_at DESC
	 LIMIT 50"
);
if ( $wpdb->last_error ) {
	$logs = [];
}
?>
<div class="wrap fww-sp-settings">

	<h1><?php esc_html_e( 'FWW Social Publisher', 'fww-social-publisher' ); ?></h1>

	<form method="post" action="options.php">
		<?php settings_fields( 'fww_social_publisher_options_group' ); ?>

		<h2><?php esc_html_e( 'Facebook', 'fww-social-publisher' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="fww_facebook_token">
						<?php esc_html_e( 'Page Access Token', 'fww-social-publisher' ); ?>
					</label>
				</th>
				<td>
					<input type="password"
					       id="fww_facebook_token"
					       name="fww_social_publisher_options[facebook_token]"
					       value="<?php echo esc_attr( $options['facebook_token'] ?? '' ); ?>"
					       class="regular-text"
					       autocomplete="new-password" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="fww_facebook_page_id">
						<?php esc_html_e( 'Page ID', 'fww-social-publisher' ); ?>
					</label>
				</th>
				<td>
					<input type="text"
					       id="fww_facebook_page_id"
					       name="fww_social_publisher_options[facebook_page_id]"
					       value="<?php echo esc_attr( $options['facebook_page_id'] ?? '' ); ?>"
					       class="regular-text" />
					<button type="button" id="fww-test-facebook" class="button">
						<?php esc_html_e( 'Test Connection', 'fww-social-publisher' ); ?>
					</button>
					<span id="fww-test-facebook-result" class="fww-test-result"></span>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Auto-Post', 'fww-social-publisher' ); ?></th>
				<td>
					<label>
						<input type="checkbox"
						       name="fww_social_publisher_options[auto_post_facebook]"
						       value="1"
						       <?php checked( 1, $options['auto_post_facebook'] ?? 1 ); ?> />
						<?php esc_html_e( 'Automatically post to Facebook when a post is published', 'fww-social-publisher' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Instagram', 'fww-social-publisher' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="fww_instagram_account_id">
						<?php esc_html_e( 'Business Account ID', 'fww-social-publisher' ); ?>
					</label>
				</th>
				<td>
					<input type="text"
					       id="fww_instagram_account_id"
					       name="fww_social_publisher_options[instagram_account_id]"
					       value="<?php echo esc_attr( $options['instagram_account_id'] ?? '' ); ?>"
					       class="regular-text" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="fww_instagram_token">
						<?php esc_html_e( 'Access Token', 'fww-social-publisher' ); ?>
					</label>
				</th>
				<td>
					<input type="password"
					       id="fww_instagram_token"
					       name="fww_social_publisher_options[instagram_token]"
					       value="<?php echo esc_attr( $options['instagram_token'] ?? '' ); ?>"
					       class="regular-text"
					       autocomplete="new-password" />
					<p class="description">
						<?php esc_html_e( 'Leave empty to use the Facebook token above.', 'fww-social-publisher' ); ?>
					</p>
					<button type="button" id="fww-test-instagram" class="button">
						<?php esc_html_e( 'Test Connection', 'fww-social-publisher' ); ?>
					</button>
					<span id="fww-test-instagram-result" class="fww-test-result"></span>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Auto-Post', 'fww-social-publisher' ); ?></th>
				<td>
					<label>
						<input type="checkbox"
						       name="fww_social_publisher_options[auto_post_instagram]"
						       value="1"
						       <?php checked( 1, $options['auto_post_instagram'] ?? 1 ); ?> />
						<?php esc_html_e( 'Automatically post to Instagram when a post is published (requires featured image)', 'fww-social-publisher' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Telegram', 'fww-social-publisher' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="fww_telegram_bot_token">
						<?php esc_html_e( 'Bot Token', 'fww-social-publisher' ); ?>
					</label>
				</th>
				<td>
					<input type="password"
					       id="fww_telegram_bot_token"
					       name="fww_social_publisher_options[telegram_bot_token]"
					       value="<?php echo esc_attr( $options['telegram_bot_token'] ?? '' ); ?>"
					       class="regular-text"
					       autocomplete="new-password" />
					<p class="description">
						<?php esc_html_e( 'Token from @BotFather, e.g. 123456789:ABCdef…', 'fww-social-publisher' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="fww_telegram_chat_id">
						<?php esc_html_e( 'Channel ID', 'fww-social-publisher' ); ?>
					</label>
				</th>
				<td>
					<input type="text"
					       id="fww_telegram_chat_id"
					       name="fww_social_publisher_options[telegram_chat_id]"
					       value="<?php echo esc_attr( $options['telegram_chat_id'] ?? '' ); ?>"
					       class="regular-text"
					       placeholder="@fww_wolfurt or -1001234567890" />
					<p class="description">
						<?php esc_html_e( 'Public channel username (e.g. @fww_wolfurt) or numeric chat ID. The bot must be admin of the channel.', 'fww-social-publisher' ); ?>
					</p>
					<button type="button" id="fww-test-telegram" class="button">
						<?php esc_html_e( 'Test Connection', 'fww-social-publisher' ); ?>
					</button>
					<span id="fww-test-telegram-result" class="fww-test-result"></span>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Auto-Post', 'fww-social-publisher' ); ?></th>
				<td>
					<label>
						<input type="checkbox"
						       name="fww_social_publisher_options[auto_post_telegram]"
						       value="1"
						       <?php checked( 1, $options['auto_post_telegram'] ?? 1 ); ?> />
						<?php esc_html_e( 'Automatically post to Telegram when a post is published', 'fww-social-publisher' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Content &amp; Filters', 'fww-social-publisher' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="fww_ki_meta_key">
						<?php esc_html_e( 'KI Content Creator – Meta Key', 'fww-social-publisher' ); ?>
					</label>
				</th>
				<td>
					<input type="text"
					       id="fww_ki_meta_key"
					       name="fww_social_publisher_options[ki_meta_key]"
					       value="<?php echo esc_attr( $options['ki_meta_key'] ?? '_ki_social_media_text' ); ?>"
					       class="regular-text" />
					<p class="description">
						<?php esc_html_e( 'Post meta key where the KI Content Creator plugin stores the social media text. Falls back to excerpt or post content if empty.', 'fww-social-publisher' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Category Filter', 'fww-social-publisher' ); ?></th>
				<td>
					<p class="description">
						<?php esc_html_e( 'Only auto-post from selected categories. Leave all unchecked to post from every category.', 'fww-social-publisher' ); ?>
					</p>
					<div class="fww-category-list">
						<?php if ( empty( $categories ) ) : ?>
							<em><?php esc_html_e( 'No categories found.', 'fww-social-publisher' ); ?></em>
						<?php else : ?>
							<?php foreach ( $categories as $cat ) : ?>
								<label>
									<input type="checkbox"
									       name="fww_social_publisher_options[category_filter][]"
									       value="<?php echo esc_attr( $cat->term_id ); ?>"
									       <?php checked( in_array( (int) $cat->term_id, $filter_cats, true ) ); ?> />
									<?php echo esc_html( $cat->name ); ?>
									<span class="fww-cat-count">(<?php echo (int) $cat->count; ?>)</span>
								</label>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>

	<?php /* ---- Activity Log ---- */ ?>
	<h2><?php esc_html_e( 'Activity Log', 'fww-social-publisher' ); ?></h2>

	<?php if ( empty( $logs ) ) : ?>
		<p><?php esc_html_e( 'No log entries yet.', 'fww-social-publisher' ); ?></p>
	<?php else : ?>
		<table class="widefat striped fww-log-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date',     'fww-social-publisher' ); ?></th>
					<th><?php esc_html_e( 'Post',     'fww-social-publisher' ); ?></th>
					<th><?php esc_html_e( 'Platform', 'fww-social-publisher' ); ?></th>
					<th><?php esc_html_e( 'Status',   'fww-social-publisher' ); ?></th>
					<th><?php esc_html_e( 'Message',  'fww-social-publisher' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $logs as $entry ) : ?>
					<tr>
						<td class="fww-log-date"><?php echo esc_html( $entry->created_at ); ?></td>
						<td>
							<?php if ( $entry->post_id ) : ?>
								<a href="<?php echo esc_url( (string) get_edit_post_link( (int) $entry->post_id ) ); ?>">
									<?php echo esc_html( $entry->post_title ?: '#' . $entry->post_id ); ?>
								</a>
							<?php else : ?>
								—
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( ucfirst( $entry->platform ) ); ?></td>
						<td>
							<span class="fww-status fww-status-<?php echo esc_attr( $entry->status ); ?>">
								<?php echo esc_html( ucfirst( $entry->status ) ); ?>
							</span>
						</td>
						<td><?php echo esc_html( $entry->message ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

</div>

<style>
.fww-sp-settings h2 { margin-top: 2em; }
.fww-category-list label { display: block; margin-bottom: 4px; }
.fww-cat-count { color: #646970; font-size: 12px; }
.fww-test-result { margin-left: 8px; font-style: italic; }
.fww-log-table { margin-top: 1em; }
.fww-log-date { white-space: nowrap; }
.fww-status { font-weight: 600; }
.fww-status-success { color: #00a32a; }
.fww-status-error   { color: #d63638; }
.fww-status-skipped { color: #646970; font-weight: normal; }
</style>
