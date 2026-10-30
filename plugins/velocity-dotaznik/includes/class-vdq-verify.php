<?php
/**
 * Server-side checks for the website field and social handles.
 *
 * Results: 'ok' (confirmed), 'notfound' (confirmed missing), 'unknown' (the
 * network did not tell us). The form never blocks on these.
 *
 * @package VelocityDotaznik
 */

defined( 'ABSPATH' ) || exit;

final class VDQ_Verify {

	const UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

	private static function get( $url, array $headers = array() ) {
		// wp_safe_remote_get refuses private and loopback addresses.
		return wp_safe_remote_get(
			$url,
			array(
				'timeout'             => 7,
				'redirection'         => 5,
				'user-agent'          => self::UA,
				'headers'             => array_merge( array( 'Accept-Language' => 'cs,en;q=0.8' ), $headers ),
				'limit_response_size' => 600000,
			)
		);
	}

	/** @return array{status: string, url: string} */
	public static function website( $raw ) {
		$url = VDQ_Form::normalize_url( $raw );
		if ( ! $url ) {
			return array( 'status' => 'invalid', 'url' => '' );
		}
		$key    = 'vdq_vw_' . md5( $url );
		$cached = get_transient( $key );
		if ( $cached ) {
			return $cached;
		}
		$res    = self::get( $url );
		$status = 'notfound';
		if ( ! is_wp_error( $res ) ) {
			$code   = (int) wp_remote_retrieve_response_code( $res );
			$body   = trim( (string) wp_remote_retrieve_body( $res ) );
			$status = ( 200 === $code && '' !== $body ) ? 'ok' : ( in_array( $code, array( 401, 403, 429, 503 ), true ) ? 'unknown' : 'notfound' );
		}
		$out = array( 'status' => $status, 'url' => $url );
		set_transient( $key, $out, 'ok' === $status ? DAY_IN_SECONDS : 10 * MINUTE_IN_SECONDS );
		return $out;
	}

	/** @return array{status: string, handle: string} */
	public static function handle( $network, $raw ) {
		$handle = VDQ_Form::normalize_handle( (string) $raw, $network );
		if ( ! preg_match( '/^[A-Za-z0-9._\-]{1,60}$/', $handle ) ) {
			return array( 'status' => 'invalid', 'handle' => $handle );
		}
		$key    = 'vdq_vh_' . md5( $network . '|' . strtolower( $handle ) );
		$cached = get_transient( $key );
		if ( $cached ) {
			return $cached;
		}
		switch ( $network ) {
			case 'instagram':
				$status = self::instagram( $handle );
				break;
			case 'tiktok':
				$status = self::tiktok( $handle );
				break;
			case 'facebook':
				$status = self::facebook( $handle );
				break;
			default:
				$status = 'unknown';
		}
		$out = array( 'status' => $status, 'handle' => $handle );
		set_transient( $key, $out, 'unknown' === $status ? 10 * MINUTE_IN_SECONDS : DAY_IN_SECONDS );
		return $out;
	}

	private static function instagram( $h ) {
		$res = self::get(
			'https://i.instagram.com/api/v1/users/web_profile_info/?username=' . rawurlencode( $h ),
			array( 'x-ig-app-id' => '936619743392459' )
		);
		if ( is_wp_error( $res ) ) {
			return 'unknown';
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		if ( 404 === $code ) {
			return 'notfound';
		}
		$json = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( 200 === $code && isset( $json['data'] ) ) {
			return empty( $json['data']['user'] ) ? 'notfound' : 'ok';
		}
		return 'unknown';
	}

	private static function tiktok( $h ) {
		$res = self::get( 'https://www.tiktok.com/@' . rawurlencode( $h ) );
		if ( is_wp_error( $res ) ) {
			return 'unknown';
		}
		$body = (string) wp_remote_retrieve_body( $res );
		if ( preg_match( '/"uniqueId":"' . preg_quote( $h, '/' ) . '"/i', $body ) ) {
			return 'ok';
		}
		if ( preg_match( '/"statusCode":\s*(10221|10202|10222)/', $body ) || 404 === (int) wp_remote_retrieve_response_code( $res ) ) {
			return 'notfound';
		}
		return 'unknown';
	}

	private static function facebook( $h ) {
		$res = self::get( 'https://www.facebook.com/' . rawurlencode( $h ) );
		if ( is_wp_error( $res ) ) {
			return 'unknown';
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		if ( 404 === $code ) {
			return 'notfound';
		}
		$body = (string) wp_remote_retrieve_body( $res );
		if ( 200 === $code && preg_match( '/<meta property="og:title" content="([^"]+)"/', $body, $m ) && ! preg_match( '/^(Facebook|Log in|Přihlásit)/i', $m[1] ) ) {
			return 'ok';
		}
		return 'unknown';
	}
}
