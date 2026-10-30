<?php
/**
 * Plugin settings stored in the `vdq_settings` option.
 *
 * Any key can be overridden in wp-config.php with a constant named
 * VDQ_<KEY in upper case>, e.g. define( 'VDQ_NOTION_SECRET', '...' ).
 *
 * Credentials are stored per environment (<key>_dev, <key>_prod) in the same
 * option, so a migration can copy it as is. The site URL picks the set.
 *
 * @package VelocityDotaznik
 */

defined( 'ABSPATH' ) || exit;

final class VDQ_Settings {

	const OPTION = 'vdq_settings';

	/** Keys resolved from <key>_dev or <key>_prod. */
	const SCOPED = array( 'resend_key', 'notion_secret', 'notion_database' );

	/** Hosts that use the production credentials. Any other host is dev. */
	const PROD_HOSTS = array( 'velocity.ooo', 'www.velocity.ooo' );

	public static function defaults() {
		return array(
			'from_email'      => 'marketing@velocity.ooo',
			'from_name'       => 'Velocity Marketing',
			'reply_to'        => 'matyas@velocity.ooo',
			'reply_name'      => 'Matyáš Brůna',
			'notify_email'    => 'matyas@velocity.ooo',
			'resend_key'      => '',
			'resend_key_dev'  => '',
			'resend_key_prod' => '',
			'reminder_hours'  => 4,
			'notion_secret'   => '',
			'notion_database' => '',
			'notion_secret_dev'    => '',
			'notion_secret_prod'   => '',
			'notion_database_dev'  => '',
			'notion_database_prod' => '',
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
		foreach ( self::SCOPED as $key ) {
			if ( ! self::is_constant( $key ) ) {
				$all[ $key ] = $all[ $key . '_' . self::env() ];
			}
		}
		return $all;
	}

	/** @return string 'prod' or 'dev' */
	public static function env() {
		$host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
		return in_array( $host, self::PROD_HOSTS, true ) ? 'prod' : 'dev';
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
