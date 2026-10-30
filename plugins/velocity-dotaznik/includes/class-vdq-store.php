<?php
/**
 * Database access: sessions and unsubscribed e-mails.
 *
 * Sessions keep the e-mail in its own column (it is looked up for reminders and
 * unsubscribes) and every other answer in the `answers` JSON. The URL token is
 * the session uuid plus an HMAC of it (see token_for()), so nothing secret is
 * stored and a resume link can be rebuilt for the reminder e-mail. A NULL
 * submission_done_at means "in progress".
 *
 * @package VelocityDotaznik
 */

defined( 'ABSPATH' ) || exit;

final class VDQ_Store {

	const DB_VERSION = 1;

	public static function sessions_table() {
		global $wpdb;
		return $wpdb->prefix . 'vdq_sessions';
	}

	public static function unsub_table() {
		global $wpdb;
		return $wpdb->prefix . 'vdq_unsubscribes';
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$s       = self::sessions_table();
		$u       = self::unsub_table();

		dbDelta(
			"CREATE TABLE $s (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			uuid char(36) NOT NULL,
			email varchar(190) NOT NULL DEFAULT '',
			answers longtext NOT NULL,
			step varchar(32) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			last_edited_at datetime NOT NULL,
			submission_done_at datetime DEFAULT NULL,
			reminder_sent_at datetime DEFAULT NULL,
			notion_page_id varchar(64) NOT NULL DEFAULT '',
			notion_synced_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uuid (uuid),
			KEY email (email),
			KEY last_edited_at (last_edited_at),
			KEY submission_done_at (submission_done_at)
		) $charset;"
		);

		dbDelta(
			"CREATE TABLE $u (
			email varchar(190) NOT NULL,
			created_at datetime NOT NULL,
			source varchar(20) NOT NULL DEFAULT '',
			PRIMARY KEY  (email)
		) $charset;"
		);

		update_option( 'vdq_db_version', self::DB_VERSION, false );
	}

	public static function maybe_upgrade() {
		if ( (int) get_option( 'vdq_db_version' ) < self::DB_VERSION ) {
			self::install();
		}
	}

	public static function now() {
		return current_time( 'mysql', true );
	}

	/** URL token: 32 hex chars of the uuid + 32 chars of its HMAC. */
	public static function token_for( $uuid ) {
		$hex = str_replace( '-', '', $uuid );
		$sig = rtrim( strtr( base64_encode( hash_hmac( 'sha256', $hex, wp_salt( 'secure_auth' ) . 'vdq-session', true ) ), '+/', '-_' ), '=' );
		return $hex . substr( $sig, 0, 32 );
	}

	/* ---------------------------------------------------------------------
	 * Sessions
	 * ------------------------------------------------------------------- */

	/** @return array{0: array, 1: string} the new row and its URL token */
	public static function create() {
		global $wpdb;
		$uuid = wp_generate_uuid4();
		$now  = self::now();
		$wpdb->insert(
			self::sessions_table(),
			array(
				'uuid'           => $uuid,
				'answers'        => '{}',
				'created_at'     => $now,
				'last_edited_at' => $now,
			)
		);
		return array( self::find( (int) $wpdb->insert_id ), self::token_for( $uuid ) );
	}

	public static function find( $id ) {
		global $wpdb;
		$t = self::sessions_table();
		return self::hydrate( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE id = %d", $id ), ARRAY_A ) );
	}

	public static function find_by_token( $token ) {
		global $wpdb;
		if ( ! is_string( $token ) || ! preg_match( '/^([a-f0-9]{32})[A-Za-z0-9_-]{32}$/', $token, $m ) ) {
			return null;
		}
		$h    = $m[1];
		$uuid = substr( $h, 0, 8 ) . '-' . substr( $h, 8, 4 ) . '-' . substr( $h, 12, 4 ) . '-' . substr( $h, 16, 4 ) . '-' . substr( $h, 20 );
		if ( ! hash_equals( self::token_for( $uuid ), $token ) ) {
			return null;
		}
		$t = self::sessions_table();
		return self::hydrate( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $t WHERE uuid = %s", $uuid ), ARRAY_A ) );
	}

	public static function token( array $row ) {
		return self::token_for( $row['uuid'] );
	}

	private static function hydrate( $row ) {
		if ( ! $row ) {
			return null;
		}
		$answers = json_decode( $row['answers'], true );
		$row['answers'] = is_array( $answers ) ? $answers : array();
		return $row;
	}

	/** All answers including the e-mail column. */
	public static function answers( array $row ) {
		$a = $row['answers'];
		if ( '' !== $row['email'] ) {
			$a['email'] = $row['email'];
		}
		return $a;
	}

	/**
	 * Writes answers (the e-mail goes to its own column) and bumps last_edited_at.
	 */
	public static function update_answers( array $row, array $answers, $step = null, array $extra = array() ) {
		global $wpdb;
		// Only a valid e-mail goes to the column (reminders, unsubscribes, Notion).
		$email = $row['email'];
		if ( array_key_exists( 'email', $answers ) ) {
			$email = is_email( $answers['email'] ) ? strtolower( (string) $answers['email'] ) : '';
			if ( '' !== $email ) {
				unset( $answers['email'] );
			}
		}
		$data = array_merge(
			array(
				'answers'        => wp_json_encode( $answers, JSON_UNESCAPED_UNICODE ),
				'email'          => $email,
				'last_edited_at' => self::now(),
			),
			$extra
		);
		if ( null !== $step ) {
			$data['step'] = substr( (string) $step, 0, 32 );
		}
		$wpdb->update( self::sessions_table(), $data, array( 'id' => $row['id'] ) );
		return self::find( $row['id'] );
	}

	public static function update_fields( $id, array $data ) {
		global $wpdb;
		return false !== $wpdb->update( self::sessions_table(), $data, array( 'id' => $id ) );
	}

	/** Sets reminder_sent_at only if still empty. True when this call won. */
	public static function claim_reminder( $id ) {
		global $wpdb;
		$t = self::sessions_table();
		return 1 === (int) $wpdb->query( $wpdb->prepare( "UPDATE $t SET reminder_sent_at = %s WHERE id = %d AND reminder_sent_at IS NULL", self::now(), $id ) );
	}

	/** Lets a failed reminder go out again on the next tick. */
	public static function release_reminder( $id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . self::sessions_table() . ' SET reminder_sent_at = NULL WHERE id = %d', $id ) );
	}

	public static function delete( $id ) {
		global $wpdb;
		return (bool) $wpdb->delete( self::sessions_table(), array( 'id' => $id ) );
	}

	/**
	 * Admin listing.
	 *
	 * @param array $args status (all|done|open|empty), search, orderby, order, per_page, page.
	 * @return array{0: array[], 1: int}
	 */
	public static function query( array $args ) {
		global $wpdb;
		$t     = self::sessions_table();
		$where = array( '1=1' );
		$vals  = array();
		switch ( isset( $args['status'] ) ? $args['status'] : 'all' ) {
			case 'done':
				$where[] = 'submission_done_at IS NOT NULL';
				break;
			case 'open':
				$where[] = "submission_done_at IS NULL AND email <> ''";
				break;
			case 'empty':
				$where[] = "submission_done_at IS NULL AND email = ''";
				break;
			default:
				$where[] = "email <> ''";
		}
		if ( ! empty( $args['search'] ) ) {
			$like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[] = '(email LIKE %s OR answers LIKE %s)';
			$vals[]  = $like;
			$vals[]  = $like;
		}
		$orderby = in_array( isset( $args['orderby'] ) ? $args['orderby'] : '', array( 'email', 'created_at', 'last_edited_at', 'submission_done_at' ), true ) ? $args['orderby'] : 'last_edited_at';
		$order   = ( isset( $args['order'] ) && 'asc' === strtolower( $args['order'] ) ) ? 'ASC' : 'DESC';
		$per     = isset( $args['per_page'] ) ? max( 1, (int) $args['per_page'] ) : 30;
		$page    = isset( $args['page'] ) ? max( 1, (int) $args['page'] ) : 1;
		$w       = implode( ' AND ', $where );

		$count_sql = "SELECT COUNT(*) FROM $t WHERE $w";
		$rows_sql  = "SELECT * FROM $t WHERE $w ORDER BY $orderby $order LIMIT %d OFFSET %d";
		$total     = (int) ( $vals ? $wpdb->get_var( $wpdb->prepare( $count_sql, $vals ) ) : $wpdb->get_var( $count_sql ) );
		$rows      = $wpdb->get_results( $wpdb->prepare( $rows_sql, array_merge( $vals, array( $per, ( $page - 1 ) * $per ) ) ), ARRAY_A );
		return array( array_map( array( __CLASS__, 'hydrate' ), $rows ), $total );
	}

	public static function counts() {
		global $wpdb;
		$t = self::sessions_table();
		return $wpdb->get_row(
			"SELECT SUM(email <> '') AS all_with_email,
				SUM(submission_done_at IS NOT NULL) AS done,
				SUM(submission_done_at IS NULL AND email <> '') AS open,
				SUM(submission_done_at IS NULL AND email = '') AS empty
			FROM $t",
			ARRAY_A
		);
	}

	/** Unfinished sessions with an e-mail, idle for $hours, never reminded. */
	public static function due_reminders( $hours, $limit = 50 ) {
		global $wpdb;
		$t      = self::sessions_table();
		$u      = self::unsub_table();
		$before = gmdate( 'Y-m-d H:i:s', time() - (int) ( $hours * HOUR_IN_SECONDS ) );
		$after  = gmdate( 'Y-m-d H:i:s', time() - 7 * DAY_IN_SECONDS );
		$rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT s.* FROM $t s LEFT JOIN $u u ON u.email = s.email
				WHERE s.submission_done_at IS NULL AND s.reminder_sent_at IS NULL AND s.email <> ''
				AND u.email IS NULL AND s.last_edited_at <= %s AND s.last_edited_at >= %s
				ORDER BY s.last_edited_at ASC LIMIT %d",
				$before,
				$after,
				$limit
			),
			ARRAY_A
		);
		return array_map( array( __CLASS__, 'hydrate' ), $rows );
	}

	/** Sessions whose Notion page is older than their last edit. */
	public static function notion_backlog( $limit = 20 ) {
		global $wpdb;
		$t    = self::sessions_table();
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $t WHERE email <> '' AND (notion_synced_at IS NULL OR notion_synced_at < last_edited_at)
				ORDER BY last_edited_at ASC LIMIT %d",
				$limit
			),
			ARRAY_A
		);
		return array_map( array( __CLASS__, 'hydrate' ), $rows );
	}

	/**
	 * Retention: empty sessions after 7 days, unfinished after 180, submitted after 730.
	 */
	public static function purge_expired() {
		global $wpdb;
		$t = self::sessions_table();
		$d = function ( $days ) {
			return gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
		};
		$n  = (int) $wpdb->query( $wpdb->prepare( "DELETE FROM $t WHERE email = '' AND submission_done_at IS NULL AND last_edited_at < %s", $d( 7 ) ) );
		$n += (int) $wpdb->query( $wpdb->prepare( "DELETE FROM $t WHERE submission_done_at IS NULL AND last_edited_at < %s", $d( 180 ) ) );
		$n += (int) $wpdb->query( $wpdb->prepare( "DELETE FROM $t WHERE submission_done_at IS NOT NULL AND last_edited_at < %s", $d( 730 ) ) );
		return $n;
	}

	/* ---------------------------------------------------------------------
	 * Unsubscribes
	 * ------------------------------------------------------------------- */

	public static function is_unsubscribed( $email ) {
		global $wpdb;
		if ( '' === (string) $email ) {
			return false;
		}
		$u = self::unsub_table();
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM $u WHERE email = %s", strtolower( $email ) ) );
	}

	public static function unsubscribe( $email, $source ) {
		global $wpdb;
		$email = strtolower( trim( $email ) );
		if ( ! is_email( $email ) ) {
			return false;
		}
		$u = self::unsub_table();
		return false !== $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO $u (email, created_at, source) VALUES (%s, %s, %s)", $email, self::now(), substr( $source, 0, 20 ) ) );
	}

	public static function resubscribe( $email ) {
		global $wpdb;
		return (bool) $wpdb->delete( self::unsub_table(), array( 'email' => strtolower( trim( $email ) ) ) );
	}

	public static function unsubscribes( $search = '' ) {
		global $wpdb;
		$u = self::unsub_table();
		if ( $search ) {
			return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $u WHERE email LIKE %s ORDER BY created_at DESC LIMIT 500", '%' . $wpdb->esc_like( $search ) . '%' ), ARRAY_A );
		}
		return $wpdb->get_results( "SELECT * FROM $u ORDER BY created_at DESC LIMIT 500", ARRAY_A );
	}
}
