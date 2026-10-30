<?php
/**
 * Mirrors every session that has an e-mail into a Notion database.
 *
 * Columns come from the `notion` key of each question in config/form.php plus
 * the system columns in system_schema(). "Upravit schéma" in the settings
 * creates missing columns and fixes types.
 *
 * @package VelocityDotaznik
 */

defined( 'ABSPATH' ) || exit;

final class VDQ_Notion {

	const API     = 'https://api.notion.com/v1';
	const VERSION = '2022-06-28';
	const TITLE   = 'Lead';

	public static function init() {
		add_action( 'vdq_notion_sync', array( __CLASS__, 'sync_id' ) );
	}

	public static function configured() {
		return VDQ_Settings::get( 'notion_secret' ) && self::database_id();
	}

	/** Accepts a database id or a full Notion URL. */
	public static function database_id() {
		$raw = (string) VDQ_Settings::get( 'notion_database' );
		if ( preg_match( '/([a-f0-9]{32})(?:\?|$)/i', str_replace( '-', '', $raw ), $m ) ) {
			return strtolower( $m[1] );
		}
		return '';
	}

	/** Debounced sync shortly after an edit. */
	public static function queue( $id ) {
		if ( ! self::configured() ) {
			return;
		}
		if ( ! wp_next_scheduled( 'vdq_notion_sync', array( (int) $id ) ) ) {
			wp_schedule_single_event( time() + 30, 'vdq_notion_sync', array( (int) $id ) );
		}
	}

	public static function request( $method, $path, $body = null ) {
		$args = array(
			'method'  => $method,
			'timeout' => 20,
			'headers' => array(
				'Authorization'  => 'Bearer ' . VDQ_Settings::get( 'notion_secret' ),
				'Notion-Version' => self::VERSION,
				'Content-Type'   => 'application/json',
			),
		);
		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}
		$res = wp_remote_request( self::API . $path, $args );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		$json = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( $code >= 300 ) {
			$msg = isset( $json['message'] ) ? $json['message'] : 'HTTP ' . $code;
			return new WP_Error( 'vdq_notion_' . $code, $msg, array( 'status' => $code ) );
		}
		return $json;
	}

	/* ---------------------------------------------------------------------
	 * Schema
	 * ------------------------------------------------------------------- */

	private static function select( array $names ) {
		$colors = array( 'gray', 'brown', 'orange', 'yellow', 'green', 'blue', 'purple', 'pink', 'red' );
		$out    = array();
		foreach ( array_values( $names ) as $i => $n ) {
			$out[] = array(
				'name'  => mb_substr( str_replace( ',', ' ', $n ), 0, 100 ),
				'color' => $colors[ $i % count( $colors ) ],
			);
		}
		return array( 'options' => $out );
	}

	public static function system_schema() {
		$steps = array();
		foreach ( VDQ_Form::steps() as $s ) {
			$steps[] = $s['title'];
		}
		return array(
			self::TITLE             => array( 'title' => new stdClass() ),
			'Status'                => array( 'select' => self::select( array( 'Rozpracováno', 'Odesláno' ) ) ),
			'Odesláno'              => array( 'date' => new stdClass() ),
			'Naposledy upraveno'    => array( 'date' => new stdClass() ),
			'Vytvořeno'             => array( 'date' => new stdClass() ),
			'Krok'                  => array( 'select' => self::select( $steps ) ),
			'Cíle vyplněny'         => array( 'date' => new stdClass() ),
			'Připomínka odeslána'   => array( 'date' => new stdClass() ),
			'Odhlášen z e-mailů'    => array( 'checkbox' => new stdClass() ),
			'Pro Fellaship'         => array( 'checkbox' => new stdClass() ),
			'Session ID'            => array( 'rich_text' => new stdClass() ),
			'Detail ve WP'          => array( 'url' => new stdClass() ),
		);
	}

	public static function question_schema() {
		$out = array();
		foreach ( VDQ_Form::questions() as $q ) {
			if ( empty( $q['notion'] ) ) {
				continue;
			}
			switch ( $q['type'] ) {
				case 'email':
					$def = array( 'email' => new stdClass() );
					break;
				case 'url':
				case 'handle':
					$def = array( 'url' => new stdClass() );
					break;
				case 'tel':
					$def = array( 'phone_number' => new stdClass() );
					break;
				case 'checkbox':
					$def = array( 'checkbox' => new stdClass() );
					break;
				case 'single':
					$def = array( 'select' => self::select( wp_list_pluck( $q['options'], 'label' ) ) );
					break;
				case 'multi':
				case 'social':
					$def = array( 'multi_select' => self::select( wp_list_pluck( $q['options'], 'label' ) ) );
					break;
				default:
					$def = array( 'rich_text' => new stdClass() );
			}
			$out[ $q['notion'] ] = $def;
		}
		return $out;
	}

	public static function schema() {
		return array_merge( self::system_schema(), self::question_schema() );
	}

	/**
	 * Creates missing columns, fixes types and renames the title column.
	 *
	 * @param bool $remove_old Also delete columns that are not in the schema.
	 * @return array|WP_Error report
	 */
	public static function sync_schema( $remove_old = false ) {
		$db = self::request( 'GET', '/databases/' . self::database_id() );
		if ( is_wp_error( $db ) ) {
			return $db;
		}
		$want   = self::schema();
		$patch  = array();
		$report = array( 'added' => array(), 'changed' => array(), 'removed' => array(), 'kept' => array() );
		foreach ( $db['properties'] as $name => $prop ) {
			if ( 'title' === $prop['type'] ) {
				if ( self::TITLE !== $name ) {
					$patch[ $name ]      = array( 'name' => self::TITLE );
					$report['changed'][] = $name . ' → ' . self::TITLE;
				}
				continue;
			}
			if ( isset( $want[ $name ] ) ) {
				$type = key( $want[ $name ] );
				if ( $type !== $prop['type'] ) {
					$patch[ $name ]      = $want[ $name ];
					$report['changed'][] = $name . ' (' . $prop['type'] . ' → ' . $type . ')';
				}
			} elseif ( $remove_old ) {
				$patch[ $name ]      = null;
				$report['removed'][] = $name;
			} else {
				$report['kept'][] = $name;
			}
		}
		foreach ( $want as $name => $def ) {
			if ( self::TITLE !== $name && ! isset( $db['properties'][ $name ] ) ) {
				$patch[ $name ]    = $def;
				$report['added'][] = $name;
			}
		}
		if ( $patch ) {
			$res = self::request(
				'PATCH',
				'/databases/' . self::database_id(),
				array(
					'title'      => array( array( 'text' => array( 'content' => 'Leady z Dotazníku' ) ) ),
					'properties' => $patch,
				)
			);
			if ( is_wp_error( $res ) ) {
				return $res;
			}
		}
		return $report;
	}

	/* ---------------------------------------------------------------------
	 * Rows
	 * ------------------------------------------------------------------- */

	private static function text( $s ) {
		$s = (string) $s;
		return array( array( 'type' => 'text', 'text' => array( 'content' => mb_substr( $s, 0, 2000 ) ) ) );
	}

	private static function date( $mysql_gmt ) {
		return $mysql_gmt ? array( 'start' => gmdate( 'c', strtotime( $mysql_gmt . ' UTC' ) ) ) : null;
	}

	public static function page_properties( array $row ) {
		$a     = VDQ_Store::answers( $row );
		$step  = VDQ_Form::step( $row['step'] );
		$title = ! empty( $a['name'] ) ? $a['name'] . ' (' . $row['email'] . ')' : $row['email'];
		$props = array(
			self::TITLE           => array( 'title' => self::text( $title ) ),
			'Status'              => array( 'select' => array( 'name' => $row['submission_done_at'] ? 'Odesláno' : 'Rozpracováno' ) ),
			'Odesláno'            => array( 'date' => self::date( $row['submission_done_at'] ) ),
			'Naposledy upraveno'  => array( 'date' => self::date( $row['last_edited_at'] ) ),
			'Vytvořeno'           => array( 'date' => self::date( $row['created_at'] ) ),
			'Krok'                => array( 'select' => $step ? array( 'name' => $step['title'] ) : null ),
			'Cíle vyplněny'       => array( 'date' => self::date( isset( $a['_goals_done_at'] ) ? $a['_goals_done_at'] : null ) ),
			'Připomínka odeslána' => array( 'date' => self::date( $row['reminder_sent_at'] ) ),
			'Odhlášen z e-mailů'  => array( 'checkbox' => VDQ_Store::is_unsubscribed( $row['email'] ) ),
			'Pro Fellaship'       => array( 'checkbox' => VDQ_Form::is_partner_fit( $a ) ),
			'Session ID'          => array( 'rich_text' => self::text( $row['uuid'] ) ),
			'Detail ve WP'        => array( 'url' => admin_url( 'admin.php?page=vdq&view=' . (int) $row['id'] ) ),
		);
		foreach ( VDQ_Form::questions() as $id => $q ) {
			if ( empty( $q['notion'] ) ) {
				continue;
			}
			$v       = isset( $a[ $id ] ) && VDQ_Form::is_visible( $q, $a ) ? $a[ $id ] : null;
			$name    = $q['notion'];
			$filled  = VDQ_Form::is_filled( $v );
			switch ( $q['type'] ) {
				case 'email':
					$props[ $name ] = array( 'email' => $filled && is_email( $v ) ? $v : null );
					break;
				case 'url':
					$url            = $filled ? VDQ_Form::normalize_url( $v ) : '';
					$props[ $name ] = array( 'url' => $url ? $url : null );
					break;
				case 'handle':
					$url            = $filled ? VDQ_Form::profile_url( $q['network'], $v ) : '';
					$props[ $name ] = array( 'url' => $url ? $url : null );
					break;
				case 'tel':
					$props[ $name ] = array( 'phone_number' => $filled ? $v : null );
					break;
				case 'checkbox':
					$props[ $name ] = array( 'checkbox' => (bool) $v );
					break;
				case 'single':
					$props[ $name ] = array( 'select' => $filled ? array( 'name' => mb_substr( str_replace( ',', ' ', VDQ_Form::option_label( $q, $v ) ), 0, 100 ) ) : null );
					break;
				case 'multi':
				case 'social':
					$names = array();
					foreach ( $filled ? $v : array() as $x ) {
						$names[] = array( 'name' => mb_substr( str_replace( ',', ' ', VDQ_Form::option_label( $q, $x ) ), 0, 100 ) );
					}
					$props[ $name ] = array( 'multi_select' => $names );
					break;
				default:
					$props[ $name ] = array( 'rich_text' => $filled ? self::text( $v ) : array() );
			}
		}
		return $props;
	}

	public static function sync_id( $id ) {
		$row = VDQ_Store::find( (int) $id );
		return $row ? self::sync( $row ) : false;
	}

	/** @return true|WP_Error */
	public static function sync( array $row ) {
		if ( ! self::configured() || '' === $row['email'] ) {
			return new WP_Error( 'vdq_notion_off', 'Notion není nastavený nebo chybí e-mail.' );
		}
		$props = self::page_properties( $row );
		$res   = null;
		if ( $row['notion_page_id'] ) {
			$res = self::request( 'PATCH', '/pages/' . $row['notion_page_id'], array( 'properties' => $props, 'archived' => false ) );
			if ( is_wp_error( $res ) && in_array( $res->get_error_data()['status'] ?? 0, array( 400, 404 ), true ) && false !== stripos( $res->get_error_message(), 'archived' ) ) {
				$res = null;
			} elseif ( is_wp_error( $res ) && 404 === ( $res->get_error_data()['status'] ?? 0 ) ) {
				$res = null;
			}
		}
		if ( null === $res ) {
			$res = self::request(
				'POST',
				'/pages',
				array(
					'parent'     => array( 'database_id' => self::database_id() ),
					'properties' => $props,
				)
			);
		}
		if ( is_wp_error( $res ) ) {
			update_option( 'vdq_notion_last_error', array( gmdate( 'Y-m-d H:i:s' ), (int) $row['id'], $res->get_error_message() ), false );
			return $res;
		}
		VDQ_Store::update_fields(
			$row['id'],
			array(
				'notion_page_id'   => str_replace( '-', '', $res['id'] ),
				'notion_synced_at' => VDQ_Store::now(),
			)
		);
		return true;
	}
}
