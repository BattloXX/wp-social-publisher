<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FWW_Instagram_API {

	private const API_BASE = 'https://graph.facebook.com/v19.0';

	/**
	 * Full two-step Instagram post: create container → publish.
	 *
	 * @return string|WP_Error Media ID on success.
	 */
	public function post( string $ig_id, string $token, string $image_url, string $caption ): string|WP_Error {
		$container_id = $this->create_container( $ig_id, $token, $image_url, $caption );

		if ( is_wp_error( $container_id ) ) {
			return $container_id;
		}

		return $this->publish_container( $ig_id, $token, $container_id );
	}

	/**
	 * Step 1 – create an unpublished media container.
	 *
	 * @return string|WP_Error Container ID on success.
	 */
	public function create_container( string $ig_id, string $token, string $image_url, string $caption ): string|WP_Error {
		$response = wp_remote_post(
			self::API_BASE . '/' . rawurlencode( $ig_id ) . '/media',
			[
				'timeout' => 30,
				'body'    => [
					'image_url'    => $image_url,
					'caption'      => $caption,
					'access_token' => $token,
				],
			]
		);

		return $this->parse_response( $response );
	}

	/**
	 * Step 2 – publish a previously created container.
	 *
	 * @return string|WP_Error Media ID on success.
	 */
	public function publish_container( string $ig_id, string $token, string $container_id ): string|WP_Error {
		$response = wp_remote_post(
			self::API_BASE . '/' . rawurlencode( $ig_id ) . '/media_publish',
			[
				'timeout' => 30,
				'body'    => [
					'creation_id'  => $container_id,
					'access_token' => $token,
				],
			]
		);

		return $this->parse_response( $response );
	}

	/**
	 * Verify that the token and account ID are valid.
	 *
	 * @return string|WP_Error Account username on success.
	 */
	public function test_connection( string $ig_id, string $token ): string|WP_Error {
		if ( empty( $token ) || empty( $ig_id ) ) {
			return new WP_Error(
				'missing_credentials',
				__( 'Instagram credentials are not configured.', 'fww-social-publisher' )
			);
		}

		$url = add_query_arg(
			[
				'fields'       => 'id,username,name',
				'access_token' => $token,
			],
			self::API_BASE . '/' . rawurlencode( $ig_id )
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

		return $body['username'] ?? $body['name'] ?? __( 'Connected', 'fww-social-publisher' );
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
