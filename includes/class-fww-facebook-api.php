<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FWW_Facebook_API {

	private const API_BASE = 'https://graph.facebook.com/v19.0';

	/**
	 * Post a photo with caption to a Facebook Page.
	 *
	 * @return string|WP_Error Object ID on success.
	 */
	public function post_photo( string $page_id, string $token, string $image_url, string $message ): string|WP_Error {
		$response = wp_remote_post(
			self::API_BASE . '/' . rawurlencode( $page_id ) . '/photos',
			[
				'timeout' => 30,
				'body'    => [
					'url'          => $image_url,
					'message'      => $message,
					'access_token' => $token,
				],
			]
		);

		return $this->parse_response( $response );
	}

	/**
	 * Post a link/text update to a Facebook Page feed.
	 *
	 * @return string|WP_Error Post ID on success.
	 */
	public function post_feed( string $page_id, string $token, string $message, string $link = '' ): string|WP_Error {
		$body = [
			'message'      => $message,
			'access_token' => $token,
		];

		if ( ! empty( $link ) ) {
			$body['link'] = $link;
		}

		$response = wp_remote_post(
			self::API_BASE . '/' . rawurlencode( $page_id ) . '/feed',
			[
				'timeout' => 30,
				'body'    => $body,
			]
		);

		return $this->parse_response( $response );
	}

	/**
	 * Verify that the token and page ID are valid.
	 *
	 * @return string|WP_Error Page name on success.
	 */
	public function test_connection( string $page_id, string $token ): string|WP_Error {
		if ( empty( $token ) || empty( $page_id ) ) {
			return new WP_Error(
				'missing_credentials',
				__( 'Facebook credentials are not configured.', 'fww-social-publisher' )
			);
		}

		$url = add_query_arg(
			[
				'fields'       => 'name,id',
				'access_token' => $token,
			],
			self::API_BASE . '/' . rawurlencode( $page_id )
		);

		$response = wp_remote_get( $url, [ 'timeout' => 15 ] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! empty( $body['error'] ) ) {
			return new WP_Error(
				'api_error',
				$body['error']['message'] ?? __( 'Unknown API error', 'fww-social-publisher' )
			);
		}

		return $body['name'] ?? __( 'Connected', 'fww-social-publisher' );
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

		if ( ! empty( $body['error'] ) ) {
			return new WP_Error(
				'api_error',
				$body['error']['message'] ?? __( 'Unknown API error', 'fww-social-publisher' )
			);
		}

		if ( 200 !== $code ) {
			return new WP_Error(
				'http_error',
				/* translators: %d: HTTP status code */
				sprintf( __( 'HTTP error %d', 'fww-social-publisher' ), $code )
			);
		}

		return $body['id'] ?? 'ok';
	}
}
