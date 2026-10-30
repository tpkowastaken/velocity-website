<?php
/**
 * Front-end routes: /dotaznik/ (the form) and /dotaznik/odhlaseni/ (unsubscribe).
 *
 * Both pages are rendered by the plugin's own templates. Theme and builder
 * styles and scripts are dropped there so nothing competes with the form.
 * wp_head() still runs, so the site's PostHog snippet keeps working.
 *
 * @package VelocityDotaznik
 */

defined( 'ABSPATH' ) || exit;

final class VDQ_Frontend {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'render' ), 0 );
	}

	public static function add_rewrite_rules() {
		add_rewrite_rule( '^dotaznik/odhlaseni/?$', 'index.php?vdq_route=unsubscribe', 'top' );
		add_rewrite_rule( '^dotaznik/?$', 'index.php?vdq_route=form', 'top' );
	}

	public static function query_vars( $vars ) {
		$vars[] = 'vdq_route';
		return $vars;
	}

	public static function route() {
		return (string) get_query_var( 'vdq_route' );
	}

	public static function render() {
		$route = self::route();
		if ( ! in_array( $route, array( 'form', 'unsubscribe' ), true ) ) {
			return;
		}
		global $wp_query;
		$wp_query->is_404 = false;
		status_header( 200 );
		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow' );
		show_admin_bar( false );

		add_filter( 'pre_get_document_title', array( __CLASS__, 'title' ), 99 );
		add_filter( 'wp_robots', 'wp_robots_no_robots' );
		add_filter( 'rank_math/frontend/robots', array( __CLASS__, 'robots' ) );
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ), 9999 );
		add_action( 'wp_print_styles', array( __CLASS__, 'strip_assets' ), 9999 );
		add_action( 'wp_print_scripts', array( __CLASS__, 'strip_assets' ), 9999 );
		add_action( 'wp_print_footer_scripts', array( __CLASS__, 'strip_assets' ), 1 );
		// FlyingPress: do not cache or optimise these pages.
		add_filter( 'flying_press_is_cacheable', '__return_false' );
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		include VDQ_DIR . ( 'form' === $route ? 'templates/page-form.php' : 'templates/page-unsubscribe.php' );
		exit;
	}

	public static function title() {
		return 'unsubscribe' === self::route() ? 'Odhlášení z e-mailů | Velocity' : 'Dotazník | Velocity';
	}

	public static function robots( $robots ) {
		$robots['index']  = 'noindex';
		$robots['follow'] = 'nofollow';
		return $robots;
	}

	private static function handles() {
		return array( 'vdq-fonts', 'vdq-form' );
	}

	public static function assets() {
		$css_ver = VDQ_VERSION . '.' . filemtime( VDQ_DIR . 'assets/form.css' );
		$js_ver  = VDQ_VERSION . '.' . filemtime( VDQ_DIR . 'assets/form.js' );
		wp_enqueue_style( 'vdq-fonts', VDQ_URL . 'assets/fonts.css', array(), VDQ_VERSION );
		wp_enqueue_style( 'vdq-form', VDQ_URL . 'assets/form.css', array( 'vdq-fonts' ), $css_ver );
		wp_enqueue_script( 'vdq-form', VDQ_URL . 'assets/form.js', array(), $js_ver, true );
		wp_localize_script(
			'vdq-form',
			'VDQ',
			array(
				'route'       => self::route(),
				'api'         => esc_url_raw( rest_url( VDQ_Rest::NS ) ),
				'formUrl'     => VDQ_Settings::form_url(),
				'privacyUrl'  => VDQ_Settings::privacy_url(),
				'homeUrl'     => home_url( '/' ),
				'partnerUrl'  => (string) VDQ_Settings::get( 'partner_url' ),
				'definition'  => 'form' === self::route() ? VDQ_Form::public_definition() : null,
			)
		);
	}

	/** Removes every style and script that is not ours. */
	public static function strip_assets() {
		global $wp_styles, $wp_scripts;
		$keep = self::handles();
		foreach ( array( $wp_styles, $wp_scripts ) as $deps ) {
			if ( ! $deps ) {
				continue;
			}
			foreach ( (array) $deps->queue as $handle ) {
				if ( ! in_array( $handle, $keep, true ) ) {
					$deps->dequeue( $handle );
				}
			}
		}
	}
}
