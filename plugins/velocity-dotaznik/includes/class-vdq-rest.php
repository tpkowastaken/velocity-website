<?php
/**
 * REST API: velocity-dotaznik/v1
 *
 * POST /sessions                  create a session (optionally with answers)
 * GET  /sessions/{token}          load
 * POST /sessions/{token}          autosave answers + current step
 * POST /sessions/{token}/submit   finish the questionnaire (sets submission_done_at)
 * POST /sessions/{token}/goals    finish the optional goals step
 * POST /verify/website            check that a website answers 200
 * POST /verify/handle             check a social profile
 * POST /unsubscribe               signed one-click unsubscribe
 *
 * The session token in the URL is the only credential. Routes are public.
 *
 * @package VelocityDotaznik
 */

defined( 'ABSPATH' ) || exit;

final class VDQ_Rest {

	const NS = 'velocity-dotaznik/v1';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes() {
		$public = '__return_true';
		$token  = '(?P<token>[A-Za-z0-9_-]{64})';

		register_rest_route( self::NS, '/sessions', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'create' ), 'permission_callback' => $public ) );
		register_rest_route(
			self::NS,
			'/sessions/' . $token,
			array(
				array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'load' ), 'permission_callback' => $public ),
				array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'save' ), 'permission_callback' => $public ),
			)
		);
		register_rest_route( self::NS, '/sessions/' . $token . '/submit', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'submit' ), 'permission_callback' => $public ) );
		register_rest_route( self::NS, '/sessions/' . $token . '/goals', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'goals' ), 'permission_callback' => $public ) );
		register_rest_route( self::NS, '/verify/website', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'verify_website' ), 'permission_callback' => $public ) );
		register_rest_route( self::NS, '/verify/handle', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'verify_handle' ), 'permission_callback' => $public ) );
		register_rest_route( self::NS, '/unsubscribe', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'unsubscribe' ), 'permission_callback' => $public ) );
	}

	/* ---------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------- */

	/** Fixed-window limiter per client IP. */
	private static function limited( $bucket, $max, $window = HOUR_IN_SECONDS ) {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key = 'vdq_rl_' . $bucket . '_' . md5( $ip . wp_salt( 'nonce' ) );
		$n   = (int) get_transient( $key );
		if ( $n >= $max ) {
			return true;
		}
		set_transient( $key, $n + 1, $window );
		return false;
	}

	private static function error( $code, $message, $status, $extra = array() ) {
		return new WP_Error( $code, $message, array_merge( array( 'status' => $status ), $extra ) );
	}

	private static function session( WP_REST_Request $req ) {
		$row = VDQ_Store::find_by_token( $req['token'] );
		return $row ? $row : self::error( 'vdq_not_found', 'Dotazník nenalezen.', 404 );
	}

	/** What the browser gets back. */
	public static function state( array $row, $token = null ) {
		$a = VDQ_Store::answers( $row );
		unset( $a['_goals_done_at'] );
		$res = rest_ensure_response(
			array(
				'token'       => $token ? $token : VDQ_Store::token( $row ),
				'session'     => $row['uuid'],
				'answers'     => (object) $a,
				'step'        => $row['step'],
				'submitted'   => (bool) $row['submission_done_at'],
				'goals_done'  => ! empty( $row['answers']['_goals_done_at'] ),
				'partner_fit' => VDQ_Form::is_partner_fit( $a ),
			)
		);
		$res->header( 'Cache-Control', 'no-store' );
		return $res;
	}

	/** JSON params, also when the body arrives as text/plain (navigator.sendBeacon). */
	private static function param( WP_REST_Request $req, $key ) {
		$v = $req->get_param( $key );
		if ( null === $v ) {
			$body = json_decode( (string) $req->get_body(), true );
			$v    = is_array( $body ) && isset( $body[ $key ] ) ? $body[ $key ] : null;
		}
		return $v;
	}

	private static function payload_answers( WP_REST_Request $req ) {
		$answers = self::param( $req, 'answers' );
		if ( ! is_array( $answers ) ) {
			return array();
		}
		if ( strlen( wp_json_encode( $answers ) ) > 30000 ) {
			return self::error( 'vdq_too_large', 'Příliš dlouhé odpovědi.', 413 );
		}
		return $answers;
	}

	private static function step_param( WP_REST_Request $req ) {
		$step = (string) self::param( $req, 'step' );
		return VDQ_Form::step( $step ) ? $step : null;
	}

	/* ---------------------------------------------------------------------
	 * Sessions
	 * ------------------------------------------------------------------- */

	public static function create( WP_REST_Request $req ) {
		if ( self::limited( 'create', 30 ) ) {
			return self::error( 'vdq_rate', 'Příliš mnoho pokusů. Zkuste to prosím později.', 429 );
		}
		$answers = self::payload_answers( $req );
		if ( is_wp_error( $answers ) ) {
			return $answers;
		}
		list( $row, $token ) = VDQ_Store::create();
		$clean = VDQ_Form::sanitize( $answers, wp_list_pluck( VDQ_Form::pre_steps(), 'id' ) );
		$row   = VDQ_Store::update_answers( $row, $clean, self::step_param( $req ) );
		if ( '' !== $row['email'] ) {
			VDQ_Notion::queue( $row['id'] );
		}
		return self::state( $row, $token );
	}

	public static function load( WP_REST_Request $req ) {
		$row = self::session( $req );
		return is_wp_error( $row ) ? $row : self::state( $row );
	}

	public static function save( WP_REST_Request $req ) {
		$row = self::session( $req );
		if ( is_wp_error( $row ) ) {
			return $row;
		}
		$answers = self::payload_answers( $req );
		if ( is_wp_error( $answers ) ) {
			return $answers;
		}
		// After submitting, only the post steps (goals) stay editable.
		$steps = $row['submission_done_at'] ? wp_list_pluck( VDQ_Form::post_steps(), 'id' ) : wp_list_pluck( VDQ_Form::pre_steps(), 'id' );
		if ( ! empty( $row['answers']['_goals_done_at'] ) ) {
			return self::state( $row );
		}
		$merged = array_merge( VDQ_Store::answers( $row ), VDQ_Form::sanitize( $answers, $steps ) );
		$step   = self::step_param( $req );
		if ( $row['submission_done_at'] && $step && ! in_array( $step, $steps, true ) ) {
			$step = null;
		}
		$row = VDQ_Store::update_answers( $row, $merged, $step );
		if ( '' !== $row['email'] ) {
			VDQ_Notion::queue( $row['id'] );
		}
		return self::state( $row );
	}

	public static function submit( WP_REST_Request $req ) {
		$row = self::session( $req );
		if ( is_wp_error( $row ) ) {
			return $row;
		}
		if ( $row['submission_done_at'] ) {
			return self::state( $row );
		}
		$answers = self::payload_answers( $req );
		if ( is_wp_error( $answers ) ) {
			return $answers;
		}
		$pre    = wp_list_pluck( VDQ_Form::pre_steps(), 'id' );
		$merged = array_merge( VDQ_Store::answers( $row ), VDQ_Form::sanitize( $answers, $pre ) );
		$errors = VDQ_Form::validate_steps( $pre, $merged );
		if ( $errors ) {
			$first_step = VDQ_Form::questions()[ key( $errors ) ]['step'];
			return self::error( 'vdq_invalid', 'Některé odpovědi chybí nebo nejsou v pořádku.', 422, array( 'errors' => $errors, 'step' => $first_step ) );
		}
		$merged = VDQ_Form::prune_hidden( $merged );
		if ( ! empty( $merged['website'] ) ) {
			$merged['website'] = VDQ_Form::normalize_url( $merged['website'] );
		}
		$post = VDQ_Form::post_steps();
		$next = ( $post && VDQ_Form::step_visible( $post[0], $merged ) ) ? $post[0]['id'] : 'done';
		$row  = VDQ_Store::update_answers( $row, $merged, $next, array( 'submission_done_at' => VDQ_Store::now() ) );

		if ( ! empty( $merged['consent_newsletter'] ) ) {
			// A fresh, explicit opt-in overrides an older unsubscribe.
			VDQ_Store::resubscribe( $row['email'] );
		}
		VDQ_Mailer::send_confirmation( $row );
		VDQ_Mailer::send_notification( $row );
		VDQ_Notion::queue( $row['id'] );
		do_action( 'vdq_submitted', $row );
		return self::state( $row );
	}

	public static function goals( WP_REST_Request $req ) {
		$row = self::session( $req );
		if ( is_wp_error( $row ) ) {
			return $row;
		}
		if ( ! $row['submission_done_at'] ) {
			return self::error( 'vdq_not_submitted', 'Nejdřív odešlete dotazník.', 409 );
		}
		if ( ! empty( $row['answers']['_goals_done_at'] ) ) {
			return self::state( $row );
		}
		$answers = self::payload_answers( $req );
		if ( is_wp_error( $answers ) ) {
			return $answers;
		}
		$post   = wp_list_pluck( VDQ_Form::post_steps(), 'id' );
		$merged = array_merge( VDQ_Store::answers( $row ), VDQ_Form::sanitize( $answers, $post ) );
		$errors = VDQ_Form::validate_steps( $post, $merged );
		if ( $errors ) {
			return self::error( 'vdq_invalid', 'Některé odpovědi chybí.', 422, array( 'errors' => $errors ) );
		}
		$merged                   = VDQ_Form::prune_hidden( $merged );
		$merged['_goals_done_at'] = VDQ_Store::now();
		$row                      = VDQ_Store::update_answers( $row, $merged, 'done' );
		VDQ_Notion::queue( $row['id'] );
		return self::state( $row );
	}

	/* ---------------------------------------------------------------------
	 * Verification
	 * ------------------------------------------------------------------- */

	public static function verify_website( WP_REST_Request $req ) {
		if ( self::limited( 'verify', 120 ) ) {
			return self::error( 'vdq_rate', 'Příliš mnoho pokusů.', 429 );
		}
		return rest_ensure_response( VDQ_Verify::website( (string) $req->get_param( 'url' ) ) );
	}

	public static function verify_handle( WP_REST_Request $req ) {
		if ( self::limited( 'verify', 120 ) ) {
			return self::error( 'vdq_rate', 'Příliš mnoho pokusů.', 429 );
		}
		$network = (string) $req->get_param( 'network' );
		if ( ! in_array( $network, array( 'instagram', 'tiktok', 'facebook' ), true ) ) {
			return self::error( 'vdq_network', 'Neznámá síť.', 400 );
		}
		return rest_ensure_response( VDQ_Verify::handle( $network, (string) $req->get_param( 'handle' ) ) );
	}

	/* ---------------------------------------------------------------------
	 * Unsubscribe (form button and RFC 8058 one-click)
	 * ------------------------------------------------------------------- */

	public static function unsubscribe( WP_REST_Request $req ) {
		$email = VDQ_Mailer::verify( $req->get_param( 'e' ), $req->get_param( 's' ) );
		if ( ! $email ) {
			return self::error( 'vdq_bad_link', 'Odkaz pro odhlášení není platný.', 400 );
		}
		$source = $req->get_param( 'List-Unsubscribe' ) ? 'one-click' : 'link';
		VDQ_Store::unsubscribe( $email, $source );
		VDQ_PostHog::capture( 'dotaznik_unsubscribed', $email, array( 'source' => $source ) );
		global $wpdb;
		$t   = VDQ_Store::sessions_table();
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM $t WHERE email = %s", $email ) );
		foreach ( $ids as $id ) {
			VDQ_Notion::queue( $id );
		}
		return rest_ensure_response( array( 'ok' => true ) );
	}
}
