<?php
/**
 * Scheduled work: reminder e-mails, Notion catch-up, retention cleanup.
 *
 * Runs on WP-Cron every 15 minutes. WP-Cron only fires on traffic, so a real
 * server cron hitting wp-cron.php keeps the 4-hour reminder on time.
 *
 * @package VelocityDotaznik
 */

defined( 'ABSPATH' ) || exit;

final class VDQ_Cron {

	public static function init() {
		add_filter( 'cron_schedules', array( __CLASS__, 'intervals' ) );
		add_action( 'vdq_tick', array( __CLASS__, 'tick' ) );
		add_action( 'vdq_daily', array( __CLASS__, 'daily' ) );
		add_action( 'init', array( __CLASS__, 'schedule' ) );
	}

	public static function intervals( $s ) {
		$s['vdq_15min'] = array( 'interval' => 15 * MINUTE_IN_SECONDS, 'display' => 'Every 15 minutes' );
		return $s;
	}

	public static function schedule() {
		if ( ! wp_next_scheduled( 'vdq_tick' ) ) {
			wp_schedule_event( time() + 60, 'vdq_15min', 'vdq_tick' );
		}
		if ( ! wp_next_scheduled( 'vdq_daily' ) ) {
			wp_schedule_event( time() + 300, 'daily', 'vdq_daily' );
		}
	}

	public static function unschedule() {
		wp_clear_scheduled_hook( 'vdq_tick' );
		wp_clear_scheduled_hook( 'vdq_daily' );
	}

	public static function tick() {
		self::send_reminders();
		if ( VDQ_Notion::configured() ) {
			foreach ( VDQ_Store::notion_backlog( 20 ) as $row ) {
				VDQ_Notion::sync( $row );
			}
		}
	}

	public static function send_reminders() {
		$hours = max( 1, (float) VDQ_Settings::get( 'reminder_hours' ) );
		$sent  = 0;
		foreach ( VDQ_Store::due_reminders( $hours ) as $row ) {
			// Claim the row first so a parallel cron run cannot send twice.
			if ( ! VDQ_Store::claim_reminder( $row['id'] ) ) {
				continue;
			}
			$res = VDQ_Mailer::send_reminder( $row, VDQ_Store::token( $row ) );
			if ( true === $res ) {
				$sent++;
				VDQ_PostHog::capture( 'dotaznik_reminder_sent', $row['email'], array( 'step' => $row['step'], 'dotaznik_session' => $row['uuid'] ) );
			} elseif ( 'vdq_unsubscribed' !== $res->get_error_code() ) {
				VDQ_Store::release_reminder( $row['id'] );
			}
			VDQ_Notion::queue( $row['id'] );
		}
		return $sent;
	}

	public static function daily() {
		VDQ_Store::purge_expired();
	}
}
