<?php
/**
 * Plugin Name: Velocity Dotazník
 * Description: Multi-step qualification questionnaire on /dotaznik with resumable sessions, reminder e-mails, Notion sync and an admin overview. Independent of Bricks.
 * Version: 1.0.0
 * Author: Velocity
 * Text Domain: velocity-dotaznik
 * Requires PHP: 7.4
 *
 * @package VelocityDotaznik
 */

defined( 'ABSPATH' ) || exit;

define( 'VDQ_VERSION', '1.0.0' );
define( 'VDQ_FILE', __FILE__ );
define( 'VDQ_DIR', plugin_dir_path( __FILE__ ) );
define( 'VDQ_URL', plugin_dir_url( __FILE__ ) );

require_once VDQ_DIR . 'includes/class-vdq-settings.php';
require_once VDQ_DIR . 'includes/class-vdq-form.php';
require_once VDQ_DIR . 'includes/class-vdq-store.php';
require_once VDQ_DIR . 'includes/class-vdq-verify.php';
require_once VDQ_DIR . 'includes/class-vdq-mailer.php';
require_once VDQ_DIR . 'includes/class-vdq-posthog.php';
require_once VDQ_DIR . 'includes/class-vdq-notion.php';
require_once VDQ_DIR . 'includes/class-vdq-rest.php';
require_once VDQ_DIR . 'includes/class-vdq-frontend.php';
require_once VDQ_DIR . 'includes/class-vdq-cron.php';
require_once VDQ_DIR . 'includes/class-vdq-admin.php';

register_activation_hook( __FILE__, array( 'VDQ_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'VDQ_Plugin', 'deactivate' ) );

/**
 * Wires the plugin together.
 */
final class VDQ_Plugin {

	public static function boot() {
		VDQ_Store::maybe_upgrade();
		VDQ_Rest::init();
		VDQ_Frontend::init();
		VDQ_Cron::init();
		VDQ_Notion::init();
		if ( is_admin() ) {
			VDQ_Admin::init();
		}
	}

	public static function activate() {
		VDQ_Store::install();
		VDQ_Frontend::add_rewrite_rules();
		flush_rewrite_rules();
		VDQ_Cron::schedule();
	}

	public static function deactivate() {
		VDQ_Cron::unschedule();
		flush_rewrite_rules();
	}
}

add_action( 'plugins_loaded', array( 'VDQ_Plugin', 'boot' ) );
