<?php
/**
 * Server-side PostHog events (things that happen without the browser:
 * reminder sent, unsubscribed). Front-end events live in assets/form.js.
 *
 * @package VelocityDotaznik
 */

defined( 'ABSPATH' ) || exit;

final class VDQ_PostHog {

	public static function capture( $event, $distinct_id, array $props = array() ) {
		$key  = VDQ_Settings::get( 'posthog_key' );
		$host = untrailingslashit( (string) VDQ_Settings::get( 'posthog_host' ) );
		if ( ! $key || ! $host || ! $distinct_id ) {
			return;
		}
		wp_remote_post(
			$host . '/i/v0/e/',
			array(
				'timeout'  => 3,
				'blocking' => false,
				'headers'  => array( 'Content-Type' => 'application/json' ),
				'body'     => wp_json_encode(
					array(
						'api_key'     => $key,
						'event'       => $event,
						'distinct_id' => $distinct_id,
						'properties'  => array_merge( array( '$lib' => 'velocity-dotaznik' ), $props ),
						'timestamp'   => gmdate( 'c' ),
					)
				),
			)
		);
	}
}
