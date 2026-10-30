<?php
/**
 * Plugin settings stored in the `vdq_settings` option.
 *
 * Any key can be overridden in wp-config.php with a constant named
 * VDQ_<KEY in upper case>, e.g. define( 'VDQ_NOTION_SECRET', '...' ).
 *
 * @package VelocityDotaznik
 */

defined( 'ABSPATH' ) || exit;

final class VDQ_Settings {

	const OPTION = 'vdq_settings';

	public static function defaults() {
		return array(
			'from_email'      => 'marketing@velocity.ooo',
			'from_name'       => 'Velocity Marketing',
			'reply_to'        => 'maty@velocity.ooo',
			'reply_name'      => 'Matyáš Brůna',
			'resend_key'      => '',
			'reminder_hours'  => 4,
			'notion_secret'   => '',
			'notion_database' => '',
			'partner_url'     => '',
			'privacy_url'     => '/ochrana-osobnich-udaju/',
			'posthog_key'     => 'phc_mjwZkz2mvXSPxjeTXmrDtb7dhcbz4aDYw5KoMgSdEWKL',
			'posthog_host'    => 'https://blueberry.tomapli.cz',
		);
	}

	public static function all() {
		$saved = get_option( self::OPTION, array() );
		$all   = wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
		foreach ( array_keys( $all ) as $key ) {
			$const = 'VDQ_' . strtoupper( $key );
			if ( defined( $const ) ) {
				$all[ $key ] = constant( $const );
			}
		}
		return $all;
	}

	public static function get( $key ) {
		$all = self::all();
		return isset( $all[ $key ] ) ? $all[ $key ] : null;
	}

	public static function is_constant( $key ) {
		return defined( 'VDQ_' . strtoupper( $key ) );
	}

	public static function update( array $values ) {
		$saved = get_option( self::OPTION, array() );
		$saved = is_array( $saved ) ? $saved : array();
		update_option( self::OPTION, array_merge( $saved, $values ), false );
	}

	/** Absolute privacy policy URL. */
	public static function privacy_url() {
		$url = (string) self::get( 'privacy_url' );
		return 0 === strpos( $url, '/' ) ? home_url( $url ) : $url;
	}

	public static function form_url( $token = '' ) {
		$url = home_url( '/dotaznik/' );
		return $token ? add_query_arg( 'session', rawurlencode( $token ), $url ) : $url;
	}
}
