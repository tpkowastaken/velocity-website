<?php
/**
 * Reads config/form.php and applies its conditions, sanitising and validation.
 * assets/form.js mirrors evaluate() and validate_value(); keep them in sync.
 *
 * @package VelocityDotaznik
 */

defined( 'ABSPATH' ) || exit;

final class VDQ_Form {

	private static $definition = null;

	/** The questionnaire definition. Filter `vdq_form_definition` to change it from code. */
	public static function definition() {
		if ( null === self::$definition ) {
			$def              = require VDQ_DIR . 'config/form.php';
			self::$definition = apply_filters( 'vdq_form_definition', $def );
		}
		return self::$definition;
	}

	public static function steps() {
		return self::definition()['steps'];
	}

	public static function step( $id ) {
		foreach ( self::steps() as $step ) {
			if ( $step['id'] === $id ) {
				return $step;
			}
		}
		return null;
	}

	/** Every question keyed by id, each with its `step` id added. */
	public static function questions() {
		$out = array();
		foreach ( self::steps() as $step ) {
			foreach ( $step['questions'] as $q ) {
				$q['step']       = $step['id'];
				$out[ $q['id'] ] = $q;
			}
		}
		return $out;
	}

	/** Hidden boolean fields that questions declare through `skip_field`. */
	public static function skip_fields() {
		$out = array();
		foreach ( self::questions() as $q ) {
			if ( ! empty( $q['skip_field'] ) ) {
				$out[ $q['skip_field'] ] = $q['step'];
			}
		}
		return $out;
	}

	/** Steps of the questionnaire proper (before and including submit_step). */
	public static function pre_steps() {
		$out = array();
		foreach ( self::steps() as $step ) {
			if ( empty( $step['post'] ) ) {
				$out[] = $step;
			}
		}
		return $out;
	}

	public static function post_steps() {
		$out = array();
		foreach ( self::steps() as $step ) {
			if ( ! empty( $step['post'] ) ) {
				$out[] = $step;
			}
		}
		return $out;
	}

	public static function is_partner_fit( array $answers ) {
		return self::evaluate( self::definition()['partner_fit'], $answers );
	}

	/* ---------------------------------------------------------------------
	 * Conditions
	 * ------------------------------------------------------------------- */

	public static function evaluate( $cond, array $a ) {
		if ( true === $cond || null === $cond ) {
			return true;
		}
		if ( false === $cond ) {
			return false;
		}
		if ( isset( $cond['all'] ) ) {
			foreach ( $cond['all'] as $c ) {
				if ( ! self::evaluate( $c, $a ) ) {
					return false;
				}
			}
			return true;
		}
		if ( isset( $cond['any'] ) ) {
			foreach ( $cond['any'] as $c ) {
				if ( self::evaluate( $c, $a ) ) {
					return true;
				}
			}
			return false;
		}
		if ( isset( $cond['not'] ) ) {
			return ! self::evaluate( $cond['not'], $a );
		}
		$v = isset( $a[ $cond['q'] ] ) ? $a[ $cond['q'] ] : null;
		if ( array_key_exists( 'eq', $cond ) ) {
			return $v === $cond['eq'];
		}
		if ( array_key_exists( 'neq', $cond ) ) {
			return $v !== $cond['neq'];
		}
		if ( isset( $cond['in'] ) ) {
			return in_array( $v, $cond['in'], true );
		}
		if ( isset( $cond['has'] ) ) {
			return is_array( $v ) && in_array( $cond['has'], $v, true );
		}
		if ( array_key_exists( 'filled', $cond ) ) {
			return self::is_filled( $v ) === (bool) $cond['filled'];
		}
		return false;
	}

	public static function is_filled( $v ) {
		if ( is_array( $v ) ) {
			return count( $v ) > 0;
		}
		if ( is_bool( $v ) ) {
			return $v;
		}
		return null !== $v && '' !== trim( (string) $v );
	}

	public static function is_visible( array $q, array $a ) {
		return ! isset( $q['show_if'] ) || self::evaluate( $q['show_if'], $a );
	}

	public static function is_required( array $q, array $a ) {
		$r = isset( $q['required'] ) ? $q['required'] : false;
		return is_array( $r ) ? self::evaluate( $r, $a ) : (bool) $r;
	}

	public static function step_visible( array $step, array $a ) {
		return ! isset( $step['show_if'] ) || self::evaluate( $step['show_if'], $a );
	}

	/* ---------------------------------------------------------------------
	 * Sanitising (autosave keeps half-typed values) and validation
	 * ------------------------------------------------------------------- */

	/**
	 * Keeps only known keys, sanitised by type. Values do not have to be valid yet.
	 *
	 * @param array      $input   Raw answers from the client.
	 * @param array|null $only    Restrict to these step ids.
	 */
	public static function sanitize( array $input, $only = null ) {
		$out = array();
		foreach ( self::questions() as $id => $q ) {
			if ( ! array_key_exists( $id, $input ) || ( $only && ! in_array( $q['step'], $only, true ) ) ) {
				continue;
			}
			$out[ $id ] = self::sanitize_value( $q, $input[ $id ] );
		}
		foreach ( self::skip_fields() as $field => $step ) {
			if ( array_key_exists( $field, $input ) && ( ! $only || in_array( $step, $only, true ) ) ) {
				$out[ $field ] = (bool) $input[ $field ];
			}
		}
		return $out;
	}

	public static function sanitize_value( array $q, $v ) {
		$max = isset( $q['max'] ) ? (int) $q['max'] : 300;
		switch ( $q['type'] ) {
			case 'checkbox':
				return (bool) $v;
			case 'multi':
			case 'social':
				$allowed = wp_list_pluck( $q['options'], 'value' );
				$v       = is_array( $v ) ? array_values( array_intersect( array_map( 'strval', $v ), $allowed ) ) : array();
				return array_values( array_unique( $v ) );
			case 'single':
				$v = is_scalar( $v ) ? (string) $v : '';
				return in_array( $v, wp_list_pluck( $q['options'], 'value' ), true ) ? $v : '';
			case 'textarea':
				return mb_substr( sanitize_textarea_field( is_scalar( $v ) ? (string) $v : '' ), 0, $max );
			case 'email':
				return mb_substr( strtolower( trim( sanitize_text_field( is_scalar( $v ) ? (string) $v : '' ) ) ), 0, 190 );
			case 'handle':
				return self::normalize_handle( is_scalar( $v ) ? (string) $v : '', isset( $q['network'] ) ? $q['network'] : '' );
			case 'url':
				return mb_substr( trim( sanitize_text_field( is_scalar( $v ) ? (string) $v : '' ) ), 0, 255 );
			default:
				return mb_substr( sanitize_text_field( is_scalar( $v ) ? (string) $v : '' ), 0, $max );
		}
	}

	/** Returns an error message, or '' when the value is acceptable. */
	public static function validate_value( array $q, $v, array $a ) {
		if ( ! self::is_filled( $v ) ) {
			return self::is_required( $q, $a ) ? 'Toto pole je povinné.' : '';
		}
		switch ( $q['type'] ) {
			case 'email':
				return is_email( $v ) ? '' : 'Zkontrolujte prosím e-mail.';
			case 'url':
				return self::normalize_url( $v ) ? '' : 'Zkontrolujte prosím adresu webu.';
			case 'tel':
				$digits = preg_replace( '/\D/', '', $v );
				return ( preg_match( '/^\+?[\d\s\-\/().]+$/', $v ) && strlen( $digits ) >= 9 && strlen( $digits ) <= 15 ) ? '' : 'Zkontrolujte prosím telefon.';
			case 'handle':
				return preg_match( '/^[A-Za-z0-9._\-]{1,60}$/', $v ) ? '' : 'Zadejte jen uživatelské jméno, např. vasefirma.';
			case 'social':
			case 'multi':
				return count( $v ) ? '' : 'Vyberte alespoň jednu možnost.';
		}
		return '';
	}

	/**
	 * Validates the visible questions of the given steps.
	 *
	 * @return array question id => message
	 */
	public static function validate_steps( array $step_ids, array $a ) {
		$errors = array();
		foreach ( self::steps() as $step ) {
			if ( ! in_array( $step['id'], $step_ids, true ) || ! self::step_visible( $step, $a ) ) {
				continue;
			}
			foreach ( $step['questions'] as $q ) {
				if ( ! self::is_visible( $q, $a ) ) {
					continue;
				}
				$msg = self::validate_value( $q, isset( $a[ $q['id'] ] ) ? $a[ $q['id'] ] : null, $a );
				if ( $msg ) {
					$errors[ $q['id'] ] = $msg;
				}
			}
		}
		return $errors;
	}

	/** Drops answers to questions that are hidden by the current answers. */
	public static function prune_hidden( array $a ) {
		foreach ( self::questions() as $id => $q ) {
			if ( isset( $a[ $id ] ) && ! self::is_visible( $q, $a ) ) {
				unset( $a[ $id ] );
			}
		}
		return $a;
	}

	public static function normalize_url( $v ) {
		$v = trim( (string) $v );
		if ( '' === $v ) {
			return '';
		}
		if ( ! preg_match( '#^https?://#i', $v ) ) {
			$v = 'https://' . $v;
		}
		$host = wp_parse_url( $v, PHP_URL_HOST );
		if ( ! $host || false === strpos( $host, '.' ) || ! filter_var( $v, FILTER_VALIDATE_URL ) ) {
			return '';
		}
		return esc_url_raw( $v );
	}

	public static function normalize_handle( $v, $network ) {
		$v = trim( $v );
		$v = preg_replace( '#^(https?://)?(www\.|m\.|vm\.)?(instagram\.com|tiktok\.com|facebook\.com|fb\.com)/#i', '', $v );
		$v = preg_replace( '#[?\#].*$#', '', $v );
		$v = trim( $v, "/ \t" );
		$v = ltrim( $v, '@' );
		$v = preg_replace( '#/.*$#', '', $v );
		return mb_substr( $v, 0, 60 );
	}

	public static function profile_url( $network, $handle ) {
		$bases = array(
			'instagram' => 'https://www.instagram.com/%s/',
			'tiktok'    => 'https://www.tiktok.com/@%s',
			'facebook'  => 'https://www.facebook.com/%s',
		);
		return isset( $bases[ $network ] ) && '' !== $handle ? sprintf( $bases[ $network ], rawurlencode( $handle ) ) : '';
	}

	/* ---------------------------------------------------------------------
	 * Human readable answers (admin, e-mail, Notion)
	 * ------------------------------------------------------------------- */

	public static function option_label( array $q, $value ) {
		foreach ( isset( $q['options'] ) ? $q['options'] : array() as $o ) {
			if ( $o['value'] === $value ) {
				return $o['label'];
			}
		}
		return (string) $value;
	}

	public static function format( array $q, $v ) {
		if ( ! self::is_filled( $v ) && 'checkbox' !== $q['type'] ) {
			return '';
		}
		switch ( $q['type'] ) {
			case 'checkbox':
				return $v ? 'Ano' : 'Ne';
			case 'single':
				return self::option_label( $q, $v );
			case 'multi':
			case 'social':
				return implode( ', ', array_map( function ( $x ) use ( $q ) {
					return VDQ_Form::option_label( $q, $x );
				}, $v ) );
			case 'handle':
				return '@' . $v;
		}
		return (string) $v;
	}

	/**
	 * Answers grouped by step for display.
	 *
	 * @return array[] each: [ 'step' => title, 'rows' => [ [label, value, question] ] ]
	 */
	public static function grouped( array $a ) {
		$out = array();
		foreach ( self::steps() as $step ) {
			$rows = array();
			foreach ( $step['questions'] as $q ) {
				if ( ! array_key_exists( $q['id'], $a ) || ! self::is_visible( $q, $a ) ) {
					continue;
				}
				$val = self::format( $q, $a[ $q['id'] ] );
				if ( '' !== $val ) {
					$rows[] = array( $q['label'], $val, $q );
				}
			}
			if ( ! empty( $step['questions'][0]['skip_field'] ) && ! empty( $a[ $step['questions'][0]['skip_field'] ] ) ) {
				$rows[] = array( $step['questions'][0]['label'], $step['questions'][0]['skip_label'], $step['questions'][0] );
			}
			if ( $rows ) {
				$out[] = array( 'step' => $step['title'], 'id' => $step['id'], 'rows' => $rows );
			}
		}
		return $out;
	}

	/** The definition as sent to the browser. */
	public static function public_definition() {
		$def     = self::definition();
		$privacy = esc_url( VDQ_Settings::privacy_url() );
		foreach ( $def['steps'] as &$step ) {
			foreach ( array( 'notice', 'subtitle' ) as $k ) {
				if ( isset( $step[ $k ] ) ) {
					$step[ $k ] = str_replace( '{privacy_url}', $privacy, $step[ $k ] );
				}
			}
			foreach ( $step['questions'] as &$q ) {
				unset( $q['notion'] );
			}
			unset( $q );
		}
		unset( $step );
		foreach ( $def['education'] as &$card ) {
			if ( 0 === strpos( $card['url'], '/' ) ) {
				$card['url'] = home_url( $card['url'] );
			}
		}
		unset( $card );
		return $def;
	}
}
