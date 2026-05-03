<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$publisher    = FWW_Social_Publisher::get_instance();
$fb_posted    = get_post_meta( $post->ID, '_fww_facebook_posted', true );
$ig_posted    = get_post_meta( $post->ID, '_fww_instagram_posted', true );
$has_image    = (bool) get_post_thumbnail_id( $post->ID );
$social_text  = $publisher->get_social_text( $post->ID );
$permalink    = get_permalink( $post->ID );

$whatsapp_text = get_the_title( $post->ID ) . "\n\n" . $social_text . "\n\n" . $permalink;
?>
<div id="fww-sp-metabox">

	<?php /* ---- Facebook ---- */ ?>
	<div class="fww-platform-row">
		<p class="fww-platform-status">
			<span class="dashicons dashicons-facebook-alt"></span>
			<strong><?php esc_html_e( 'Facebook', 'fww-social-publisher' ); ?></strong>
			<?php if ( $fb_posted ) : ?>
				<span class="fww-badge fww-badge-posted">
					<?php printf(
						/* translators: %s: date/time */
						esc_html__( 'Posted %s', 'fww-social-publisher' ),
						esc_html( $fb_posted )
					); ?>
				</span>
			<?php else : ?>
				<span class="fww-badge fww-badge-pending">
					<?php esc_html_e( 'Not posted', 'fww-social-publisher' ); ?>
				</span>
			<?php endif; ?>
		</p>

		<button type="button"
		        id="fww-post-facebook"
		        class="button button-secondary fww-action-btn"
		        data-post-id="<?php echo esc_attr( $post->ID ); ?>">
			<?php esc_html_e( 'Post to Facebook now', 'fww-social-publisher' ); ?>
		</button>
		<span class="fww-spinner" id="fww-facebook-spinner"></span>
		<span class="fww-feedback" id="fww-facebook-feedback" aria-live="polite"></span>
	</div>

	<?php /* ---- Instagram ---- */ ?>
	<div class="fww-platform-row">
		<p class="fww-platform-status">
			<span class="dashicons dashicons-instagram"></span>
			<strong><?php esc_html_e( 'Instagram', 'fww-social-publisher' ); ?></strong>
			<?php if ( $ig_posted ) : ?>
				<span class="fww-badge fww-badge-posted">
					<?php printf(
						esc_html__( 'Posted %s', 'fww-social-publisher' ),
						esc_html( $ig_posted )
					); ?>
				</span>
			<?php elseif ( ! $has_image ) : ?>
				<span class="fww-badge fww-badge-warning">
					<?php esc_html_e( 'Needs featured image', 'fww-social-publisher' ); ?>
				</span>
			<?php else : ?>
				<span class="fww-badge fww-badge-pending">
					<?php esc_html_e( 'Not posted', 'fww-social-publisher' ); ?>
				</span>
			<?php endif; ?>
		</p>

		<button type="button"
		        id="fww-post-instagram"
		        class="button button-secondary fww-action-btn"
		        data-post-id="<?php echo esc_attr( $post->ID ); ?>"
		        <?php disabled( ! $has_image ); ?>>
			<?php esc_html_e( 'Post to Instagram now', 'fww-social-publisher' ); ?>
		</button>
		<span class="fww-spinner" id="fww-instagram-spinner"></span>
		<span class="fww-feedback" id="fww-instagram-feedback" aria-live="polite"></span>
	</div>

	<?php /* ---- WhatsApp ---- */ ?>
	<div class="fww-platform-row fww-whatsapp-row">
		<p class="fww-platform-status">
			<strong><?php esc_html_e( 'WhatsApp', 'fww-social-publisher' ); ?></strong>
		</p>

		<textarea id="fww-whatsapp-text"
		          class="widefat"
		          rows="5"
		          readonly
		          aria-label="<?php esc_attr_e( 'WhatsApp text', 'fww-social-publisher' ); ?>"><?php echo esc_textarea( $whatsapp_text ); ?></textarea>

		<div class="fww-whatsapp-actions">
			<button type="button" id="fww-copy-whatsapp" class="button button-secondary">
				<?php esc_html_e( 'Copy to Clipboard', 'fww-social-publisher' ); ?>
			</button>
			<a href="https://wa.me/"
			   target="_blank"
			   rel="noopener noreferrer"
			   class="button button-secondary">
				<?php esc_html_e( 'Open WhatsApp', 'fww-social-publisher' ); ?>
			</a>
		</div>
	</div>

</div><!-- #fww-sp-metabox -->

<style>
#fww-sp-metabox { font-size: 13px; }
.fww-platform-row { padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
.fww-platform-row:last-child { border-bottom: none; }
.fww-platform-status { margin: 0 0 6px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.fww-platform-status .dashicons { font-size: 16px; width: 16px; height: 16px; color: #646970; }
.fww-badge { font-size: 11px; font-weight: normal; padding: 2px 6px; border-radius: 3px; }
.fww-badge-posted  { background: #d1e7dd; color: #0a4a1e; }
.fww-badge-pending { background: #f0f0f1; color: #50575e; }
.fww-badge-warning { background: #fcf9e8; color: #6e4c02; }
.fww-action-btn { margin-right: 4px !important; }
.fww-spinner { display: inline-block; vertical-align: middle; visibility: hidden; }
.fww-spinner.is-active { visibility: visible; }
.fww-feedback { display: block; margin-top: 4px; font-style: italic; font-size: 12px; min-height: 1em; }
.fww-feedback.success { color: #00a32a; }
.fww-feedback.error   { color: #d63638; }
#fww-whatsapp-text { font-family: monospace; font-size: 11px; resize: vertical; margin-bottom: 6px; }
.fww-whatsapp-actions { display: flex; gap: 6px; flex-wrap: wrap; }
</style>
