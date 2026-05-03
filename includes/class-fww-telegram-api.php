<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FWW_Telegram_API {

	private const API_BASE = 'https://api.telegram.org/bot';

	/**
	 * Post to a Telegram channel: photo if available, otherwise text message.
	 *
	 * @return string|WP_Error Message ID on success.
	 */
	public function post( string $chat_id, string $token, string $text, string $image_url = '' ): string|WP_Error {
		return $image_url
			? $this->send_photo( $chat_id, $token, $image_url, $text )
			: $this->send_message( $chat_id, $token, $text );
	}

	/**
	 * Send a photo with caption.
	 *
	 * @return string|WP_Error Message ID on success.
	 */
	public function send_photo( string $chat_id, string $token, string $image_url, string $caption ): string|WP_Error {
		// Telegram caption limit is 1024 characters.
		$caption = mb_substr( $caption, 0, 1024 );

		$response = wp_remote_post(
			self::API_BASE . $token . '/sendPhoto',
			[
				'timeout' => 30,
				'body'    => [
					'chat_id'    => $chat_id,
					'photo'      => $image_url,
					'caption'    => $caption,
					'parse_mode' => 'HTML',
				],
			]
		);

		return $this->parse_response( $response );
	}

	/**
	 * Send a plain text message.
	 *
	 * @return string|WP_Error Message ID on success.
	 */
	public function send_message( string $chat_id, string $token, string $text ): string|WP_Error {
		// Telegram message limit is 4096 characters.
		$text = mb_substr( $text, 0, 4096 );

		$response = wp_remote_post(
			self::API_BASE . $token . '/sendMessage',
			[
				'timeout' => 30,
				'body'    => [
					'chat_id'                  => $chat_id,
					'text'                     => $text,
					'parse_mode'               => 'HTML',
					'disable_web_page_preview' => false,
				],
			]
		);

		return $this->parse_response( $response );
	}

	/**
	 * Verify bot token and channel ID are valid.
	 *
	 * @return string|WP_Error Bot username on success.
	 */
	public function test_connection( string $chat_id, string $token ): string|WP_Error {
		if ( empty( $token ) || empty( $chat_id ) ) {
			return new WP_Error(
				'missing_credentials',
				__( 'Telegram credentials are not configured.', 'fww-social-publisher' )
			);
		}

		// Verify bot token via getMe.
		$me = wp_remote_get(
			self::API_BASE . $token . '/getMe',
			[ 'timeout' => 15 ]
		);

		if ( is_wp_error( $me ) ) {
			return $me;
		}

		$me_body = json_decode( wp_remote_retrieve_body( $me ), true );

		if ( empty( $me_body['ok'] ) ) {
			return new WP_Error(
				'api_error',
				$me_body['description'] ?? __( 'Invalid bot token.', 'fww-social-publisher' )
			);
		}

		$bot_name = $me_body['result']['username'] ?? 'bot';

		// Verify channel access via getChat.
		$chat = wp_remote_post(
			self::API_BASE . $token . '/getChat',
			[
				'timeout' => 15,
				'body'    => [ 'chat_id' => $chat_id ],
			]
		);

		if ( is_wp_error( $chat ) ) {
			return $chat;
		}

		$chat_body = json_decode( wp_remote_retrieve_body( $chat ), true );

		if ( empty( $chat_body['ok'] ) ) {
			return new WP_Error(
				'api_error',
				/* translators: 1: bot username, 2: error description */
				sprintf(
					__( 'Bot @%1$s OK, but channel error: %2$s', 'fww-social-publisher' ),
					$bot_name,
					$chat_body['description'] ?? __( 'unknown error', 'fww-social-publisher' )
				)
			);
		}

		$chat_title = $chat_body['result']['title'] ?? $chat_body['result']['username'] ?? $chat_id;

		return sprintf( '@%s → %s', $bot_name, $chat_title );
	}

	// -------------------------------------------------------------------------

	/**
	 * @param array|WP_Error $response
	 * @return string|WP_Error
	 */
	private function parse_response( array|WP_Error $response ): string|WP_Error {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['ok'] ) ) {
			return new WP_Error(
				'api_error',
				$body['description'] ?? sprintf(
					/* translators: %d: HTTP status code */
					__( 'Telegram API error (HTTP %d)', 'fww-social-publisher' ),
					$code
				)
			);
		}

		return (string) ( $body['result']['message_id'] ?? 'ok' );
	}
}
