<?php
/**
 * WP admin: Dotazník → Odpovědi / Odhlášení / Nastavení.
 *
 * @package VelocityDotaznik
 */

defined( 'ABSPATH' ) || exit;

final class VDQ_Admin {

	const CAP = 'manage_options';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		foreach ( array( 'save_settings', 'test_email', 'notion_test', 'notion_schema', 'notion_resync', 'delete', 'notion_sync_one', 'unsub_add', 'unsub_remove', 'export', 'run_cron' ) as $a ) {
			add_action( 'admin_post_vdq_' . $a, array( __CLASS__, 'action_' . $a ) );
		}
	}

	public static function menu() {
		add_menu_page( 'Dotazník', 'Dotazník', self::CAP, 'vdq', array( __CLASS__, 'page_sessions' ), 'dashicons-feedback', 26 );
		add_submenu_page( 'vdq', 'Odpovědi', 'Odpovědi', self::CAP, 'vdq', array( __CLASS__, 'page_sessions' ) );
		add_submenu_page( 'vdq', 'Odhlášení', 'Odhlášení', self::CAP, 'vdq-unsubscribes', array( __CLASS__, 'page_unsubscribes' ) );
		add_submenu_page( 'vdq', 'Nastavení dotazníku', 'Nastavení', self::CAP, 'vdq-settings', array( __CLASS__, 'page_settings' ) );
	}

	public static function assets( $hook ) {
		if ( false !== strpos( $hook, 'vdq' ) ) {
			wp_enqueue_style( 'vdq-admin', VDQ_URL . 'assets/admin.css', array(), VDQ_VERSION . '.' . filemtime( VDQ_DIR . 'assets/admin.css' ) );
		}
	}

	/* ---------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------- */

	private static function guard( $action ) {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( 'Nemáte oprávnění.' );
		}
		check_admin_referer( 'vdq_' . $action );
	}

	private static function back( $page, array $args = array() ) {
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php?page=' . $page ) ) );
		exit;
	}

	private static function action_url( $action, array $args = array() ) {
		return wp_nonce_url( add_query_arg( array_merge( array( 'action' => 'vdq_' . $action ), $args ), admin_url( 'admin-post.php' ) ), 'vdq_' . $action );
	}

	private static function form_open( $action ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="vdq_' . esc_attr( $action ) . '">';
		wp_nonce_field( 'vdq_' . $action );
	}

	private static function local( $mysql_gmt, $format = 'j. n. Y H:i' ) {
		return $mysql_gmt ? wp_date( $format, strtotime( $mysql_gmt . ' UTC' ) ) : '–';
	}

	private static function notice() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$msg = isset( $_GET['vdq_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['vdq_msg'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$err = ! empty( $_GET['vdq_err'] );
		if ( $msg ) {
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', $err ? 'error' : 'success', esc_html( $msg ) );
		}
	}

	private static function status_badge( array $row ) {
		if ( $row['submission_done_at'] ) {
			return '<span class="vdq-badge is-done">Odesláno</span>';
		}
		if ( '' === $row['email'] ) {
			return '<span class="vdq-badge is-empty">Bez e-mailu</span>';
		}
		$step = VDQ_Form::step( $row['step'] );
		return '<span class="vdq-badge is-open">Rozpracováno' . ( $step ? ' · ' . esc_html( $step['title'] ) : '' ) . '</span>';
	}

	/* ---------------------------------------------------------------------
	 * Odpovědi
	 * ------------------------------------------------------------------- */

	public static function page_sessions() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['view'] ) ) {
			self::page_detail( (int) $_GET['view'] );
			return;
		}
		$status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : 'all';
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$paged  = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
		$order  = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'last_edited_at';
		// phpcs:enable
		$per                 = 30;
		list( $rows, $total ) = VDQ_Store::query(
			array(
				'status'   => $status,
				'search'   => $search,
				'page'     => $paged,
				'per_page' => $per,
				'orderby'  => $order,
			)
		);
		$c      = VDQ_Store::counts();
		$tabs   = array(
			'all'   => array( 'Vše s e-mailem', (int) $c['all_with_email'] ),
			'done'  => array( 'Odeslané', (int) $c['done'] ),
			'open'  => array( 'Rozpracované', (int) $c['open'] ),
			'empty' => array( 'Bez e-mailu', (int) $c['empty'] ),
		);
		$fields = VDQ_Form::questions();
		?>
		<div class="wrap vdq-admin">
			<h1 class="wp-heading-inline">Odpovědi z dotazníku</h1>
			<a class="page-title-action" href="<?php echo esc_url( VDQ_Settings::form_url() ); ?>" target="_blank">Otevřít dotazník</a>
			<a class="page-title-action" href="<?php echo esc_url( self::action_url( 'export', array( 'status' => $status ) ) ); ?>">Export CSV</a>
			<hr class="wp-header-end">
			<?php self::notice(); ?>
			<ul class="subsubsub">
				<?php
				$links = array();
				foreach ( $tabs as $key => $t ) {
					$links[] = sprintf(
						'<li><a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
						esc_url( admin_url( 'admin.php?page=vdq&status=' . $key ) ),
						$status === $key ? 'current' : '',
						esc_html( $t[0] ),
						$t[1]
					);
				}
				echo implode( ' |</li>', $links ) . '</li>'; // phpcs:ignore
				?>
			</ul>
			<form method="get" class="search-box">
				<input type="hidden" name="page" value="vdq">
				<input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>">
				<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="E-mail, jméno, web…">
				<button class="button">Hledat</button>
			</form>
			<table class="widefat striped vdq-table">
				<thead>
					<tr>
						<th>Kontakt</th>
						<th>Typ</th>
						<th>Rozpočet</th>
						<th>Stav</th>
						<th><a href="<?php echo esc_url( add_query_arg( 'orderby', 'last_edited_at' ) ); ?>">Naposledy upraveno</a></th>
						<th><a href="<?php echo esc_url( add_query_arg( 'orderby', 'submission_done_at' ) ); ?>">Odesláno</a></th>
						<th>Připomínka</th>
					</tr>
				</thead>
				<tbody>
				<?php if ( ! $rows ) : ?>
					<tr><td colspan="7">Zatím nic.</td></tr>
				<?php endif; ?>
				<?php foreach ( $rows as $row ) : ?>
					<?php
					$a    = $row['answers'];
					$view = admin_url( 'admin.php?page=vdq&view=' . (int) $row['id'] );
					?>
					<tr>
						<td>
							<strong><a href="<?php echo esc_url( $view ); ?>"><?php echo esc_html( $row['email'] ? $row['email'] : '(bez e-mailu)' ); ?></a></strong>
							<?php if ( ! empty( $a['name'] ) ) : ?><br><span class="vdq-muted"><?php echo esc_html( $a['name'] ); ?></span><?php endif; ?>
							<?php if ( VDQ_Store::is_unsubscribed( $row['email'] ) ) : ?><br><span class="vdq-badge is-unsub">Odhlášen</span><?php endif; ?>
						</td>
						<td><?php echo esc_html( isset( $a['company_type'] ) ? VDQ_Form::format( $fields['company_type'], $a['company_type'] ) : '–' ); ?></td>
						<td><?php echo esc_html( isset( $a['budget'] ) ? VDQ_Form::format( $fields['budget'], $a['budget'] ) : '–' ); ?></td>
						<td><?php echo self::status_badge( $row ); // phpcs:ignore ?></td>
						<td><?php echo esc_html( self::local( $row['last_edited_at'] ) ); ?></td>
						<td><?php echo esc_html( self::local( $row['submission_done_at'] ) ); ?></td>
						<td><?php echo esc_html( self::local( $row['reminder_sent_at'] ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php
			$pages = (int) ceil( $total / $per );
			if ( $pages > 1 ) {
				echo '<div class="tablenav"><div class="tablenav-pages">';
				echo paginate_links( // phpcs:ignore
					array(
						'base'    => add_query_arg( 'paged', '%#%' ),
						'format'  => '',
						'current' => $paged,
						'total'   => $pages,
					)
				);
				echo '</div></div>';
			}
			?>
		</div>
		<?php
	}

	private static function page_detail( $id ) {
		$row = VDQ_Store::find( $id );
		if ( ! $row ) {
			echo '<div class="wrap"><h1>Záznam nenalezen</h1></div>';
			return;
		}
		$answers = VDQ_Store::answers( $row );
		$resume  = VDQ_Settings::form_url( VDQ_Store::token( $row ) );
		?>
		<div class="wrap vdq-admin">
			<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=vdq' ) ); ?>">&larr; Všechny odpovědi</a></p>
			<h1><?php echo esc_html( ! empty( $answers['name'] ) ? $answers['name'] : ( $row['email'] ? $row['email'] : 'Bez e-mailu' ) ); ?></h1>
			<?php self::notice(); ?>
			<div class="vdq-detail">
				<div class="vdq-detail-main">
					<?php foreach ( VDQ_Form::grouped( $answers ) as $group ) : ?>
						<div class="vdq-card">
							<h2><?php echo esc_html( $group['step'] ); ?></h2>
							<table class="vdq-answers">
								<?php foreach ( $group['rows'] as $r ) : ?>
									<tr>
										<th><?php echo esc_html( $r[0] ); ?></th>
										<td>
											<?php
											if ( 'url' === $r[2]['type'] ) {
												$u = VDQ_Form::normalize_url( $r[1] );
												echo $u ? '<a href="' . esc_url( $u ) . '" target="_blank" rel="noopener">' . esc_html( $r[1] ) . '</a>' : esc_html( $r[1] );
											} elseif ( 'handle' === $r[2]['type'] ) {
												echo '<a href="' . esc_url( VDQ_Form::profile_url( $r[2]['network'], ltrim( $r[1], '@' ) ) ) . '" target="_blank" rel="noopener">' . esc_html( $r[1] ) . '</a>';
											} elseif ( 'email' === $r[2]['type'] ) {
												echo '<a href="mailto:' . esc_attr( $r[1] ) . '">' . esc_html( $r[1] ) . '</a>';
											} elseif ( 'tel' === $r[2]['type'] ) {
												echo '<a href="tel:' . esc_attr( preg_replace( '/[^\d+]/', '', $r[1] ) ) . '">' . esc_html( $r[1] ) . '</a>';
											} else {
												echo nl2br( esc_html( $r[1] ) );
											}
											?>
										</td>
									</tr>
								<?php endforeach; ?>
							</table>
						</div>
					<?php endforeach; ?>
				</div>
				<aside class="vdq-detail-side">
					<div class="vdq-card">
						<h2>Stav</h2>
						<p><?php echo self::status_badge( $row ); // phpcs:ignore ?></p>
						<dl>
							<dt>Vytvořeno</dt><dd><?php echo esc_html( self::local( $row['created_at'] ) ); ?></dd>
							<dt>Naposledy upraveno</dt><dd><?php echo esc_html( self::local( $row['last_edited_at'] ) ); ?></dd>
							<dt>Odesláno</dt><dd><?php echo esc_html( self::local( $row['submission_done_at'] ) ); ?></dd>
							<dt>Cíle vyplněny</dt><dd><?php echo esc_html( self::local( isset( $answers['_goals_done_at'] ) ? $answers['_goals_done_at'] : null ) ); ?></dd>
							<dt>Připomínka odeslána</dt><dd><?php echo esc_html( self::local( $row['reminder_sent_at'] ) ); ?></dd>
							<dt>Pro Fellaship</dt><dd><?php echo VDQ_Form::is_partner_fit( $answers ) ? 'Ano' : 'Ne'; ?></dd>
							<dt>E-maily</dt><dd><?php echo VDQ_Store::is_unsubscribed( $row['email'] ) ? 'Odhlášen' : 'Přihlášen'; ?></dd>
							<dt>Notion</dt><dd><?php echo $row['notion_page_id'] ? '<a target="_blank" href="https://www.notion.so/' . esc_attr( $row['notion_page_id'] ) . '">Otevřít</a> · ' . esc_html( self::local( $row['notion_synced_at'] ) ) : '–'; ?></dd>
							<dt>Session ID</dt><dd><code><?php echo esc_html( $row['uuid'] ); ?></code></dd>
						</dl>
						<p><label>Odkaz pro pokračování<br><input type="text" readonly class="large-text" value="<?php echo esc_attr( $resume ); ?>" onclick="this.select()"></label></p>
						<p class="vdq-actions-row">
							<?php if ( VDQ_Notion::configured() && $row['email'] ) : ?>
								<a class="button" href="<?php echo esc_url( self::action_url( 'notion_sync_one', array( 'id' => $row['id'] ) ) ); ?>">Synchronizovat do Notion</a>
							<?php endif; ?>
							<a class="button button-link-delete" href="<?php echo esc_url( self::action_url( 'delete', array( 'id' => $row['id'] ) ) ); ?>" onclick="return confirm('Smazat tento záznam? Záznam v Notion zůstane.');">Smazat</a>
						</p>
					</div>
				</aside>
			</div>
		</div>
		<?php
	}

	public static function action_delete() {
		self::guard( 'delete' );
		VDQ_Store::delete( isset( $_GET['id'] ) ? (int) $_GET['id'] : 0 );
		self::back( 'vdq', array( 'vdq_msg' => 'Záznam smazán.' ) );
	}

	public static function action_notion_sync_one() {
		self::guard( 'notion_sync_one' );
		$id  = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		$res = VDQ_Notion::sync_id( $id );
		self::back(
			'vdq',
			is_wp_error( $res )
				? array( 'view' => $id, 'vdq_msg' => 'Notion: ' . $res->get_error_message(), 'vdq_err' => 1 )
				: array( 'view' => $id, 'vdq_msg' => 'Synchronizováno do Notion.' )
		);
	}

	public static function action_export() {
		self::guard( 'export' );
		$status            = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : 'all';
		list( $rows )      = VDQ_Store::query( array( 'status' => $status, 'per_page' => 100000 ) );
		$questions         = VDQ_Form::questions();
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="dotaznik-' . gmdate( 'Y-m-d' ) . '.csv"' );
		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" );
		$head = array( 'Session ID', 'Vytvořeno', 'Naposledy upraveno', 'Odesláno', 'Připomínka', 'Krok' );
		foreach ( $questions as $q ) {
			$head[] = $q['label'];
		}
		fputcsv( $out, $head );
		foreach ( $rows as $row ) {
			$a    = VDQ_Store::answers( $row );
			$line = array( $row['uuid'], self::local( $row['created_at'] ), self::local( $row['last_edited_at'] ), self::local( $row['submission_done_at'] ), self::local( $row['reminder_sent_at'] ), $row['step'] );
			foreach ( $questions as $id => $q ) {
				$line[] = isset( $a[ $id ] ) ? VDQ_Form::format( $q, $a[ $id ] ) : '';
			}
			fputcsv( $out, $line );
		}
		fclose( $out );
		exit;
	}

	/* ---------------------------------------------------------------------
	 * Odhlášení
	 * ------------------------------------------------------------------- */

	public static function page_unsubscribes() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$rows   = VDQ_Store::unsubscribes( $search );
		$labels = array( 'link' => 'Odkaz v e-mailu', 'one-click' => 'Tlačítko v e-mailovém klientovi', 'admin' => 'Ručně v adminu' );
		?>
		<div class="wrap vdq-admin">
			<h1>Odhlášené e-maily</h1>
			<?php self::notice(); ?>
			<p>Na tyto adresy plugin neposílá žádné e-maily (připomínky ani potvrzení).</p>
			<div class="vdq-inline-forms">
				<?php self::form_open( 'unsub_add' ); ?>
					<input type="email" name="email" required placeholder="email@firma.cz">
					<button class="button">Odhlásit adresu</button>
				</form>
				<form method="get">
					<input type="hidden" name="page" value="vdq-unsubscribes">
					<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Hledat e-mail">
					<button class="button">Hledat</button>
				</form>
			</div>
			<table class="widefat striped vdq-table">
				<thead><tr><th>E-mail</th><th>Odhlášeno</th><th>Jak</th><th></th></tr></thead>
				<tbody>
				<?php if ( ! $rows ) : ?>
					<tr><td colspan="4">Nikdo se zatím neodhlásil.</td></tr>
				<?php endif; ?>
				<?php foreach ( $rows as $r ) : ?>
					<tr>
						<td><?php echo esc_html( $r['email'] ); ?></td>
						<td><?php echo esc_html( self::local( $r['created_at'] ) ); ?></td>
						<td><?php echo esc_html( isset( $labels[ $r['source'] ] ) ? $labels[ $r['source'] ] : $r['source'] ); ?></td>
						<td><a href="<?php echo esc_url( self::action_url( 'unsub_remove', array( 'email' => $r['email'] ) ) ); ?>" onclick="return confirm('Znovu povolit e-maily pro tuto adresu?');">Znovu povolit</a></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public static function action_unsub_add() {
		self::guard( 'unsub_add' );
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$ok    = VDQ_Store::unsubscribe( $email, 'admin' );
		self::back( 'vdq-unsubscribes', $ok ? array( 'vdq_msg' => 'Adresa odhlášena.' ) : array( 'vdq_msg' => 'Neplatný e-mail.', 'vdq_err' => 1 ) );
	}

	public static function action_unsub_remove() {
		self::guard( 'unsub_remove' );
		VDQ_Store::resubscribe( isset( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : '' );
		self::back( 'vdq-unsubscribes', array( 'vdq_msg' => 'E-maily znovu povoleny.' ) );
	}

	/* ---------------------------------------------------------------------
	 * Nastavení
	 * ------------------------------------------------------------------- */

	private static function field( $key, $label, $type = 'text', $help = '' ) {
		$s     = VDQ_Settings::all();
		$const = VDQ_Settings::is_constant( $key );
		$val   = $s[ $key ];
		echo '<tr><th scope="row"><label for="vdq-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
		if ( 'password' === $type ) {
			printf(
				'<input type="password" id="vdq-%1$s" name="vdq[%1$s]" value="" class="regular-text" autocomplete="new-password" placeholder="%2$s" %3$s>',
				esc_attr( $key ),
				$val ? esc_attr( '••••••••' . substr( $val, -4 ) . ' (uloženo, prázdné = beze změny)' ) : '',
				$const ? 'disabled' : ''
			);
		} else {
			printf(
				'<input type="%4$s" id="vdq-%1$s" name="vdq[%1$s]" value="%2$s" class="regular-text" %3$s>',
				esc_attr( $key ),
				esc_attr( $val ),
				$const ? 'disabled' : '',
				esc_attr( $type )
			);
		}
		if ( $const ) {
			echo '<p class="description">Nastaveno konstantou ve wp-config.php.</p>';
		}
		if ( $help ) {
			echo '<p class="description">' . wp_kses_post( $help ) . '</p>';
		}
		echo '</td></tr>';
	}

	public static function page_settings() {
		$notion_err = get_option( 'vdq_notion_last_error' );
		$mail_log   = get_option( 'vdq_mail_log', array() );
		$schema     = get_transient( 'vdq_schema_report' );
		$next       = wp_next_scheduled( 'vdq_tick' );
		?>
		<div class="wrap vdq-admin">
			<h1>Nastavení dotazníku</h1>
			<?php self::notice(); ?>

			<?php self::form_open( 'save_settings' ); ?>
				<h2>E-maily</h2>
				<table class="form-table" role="presentation">
					<?php
					self::field( 'from_email', 'Odesílatel (e-mail)', 'email' );
					self::field( 'from_name', 'Odesílatel (jméno)' );
					self::field( 'reply_to', 'Odpovědi na (e-mail)', 'email' );
					self::field( 'reply_name', 'Odpovědi na (jméno)' );
					self::field( 'resend_key', 'Resend API klíč', 'password', 'Když je vyplněný, e-maily jdou přes <a href="https://resend.com" target="_blank">Resend</a>. Jinak přes wp_mail (sendmail hostingu).' );
					self::field( 'reminder_hours', 'Připomínka po (hodinách)', 'number', 'Jedna připomínka nedokončeného dotazníku po této době nečinnosti.' );
					?>
				</table>

				<h2>Notion</h2>
				<table class="form-table" role="presentation">
					<?php
					self::field( 'notion_secret', 'Notion integration secret', 'password', 'Vytvořte integraci na <a href="https://www.notion.so/profile/integrations" target="_blank">notion.so/profile/integrations</a> a databázi s ní sdílejte (… → Connections).' );
					self::field( 'notion_database', 'Databáze (URL nebo ID)', 'text', VDQ_Notion::database_id() ? 'ID: <code>' . esc_html( VDQ_Notion::database_id() ) . '</code>' : '' );
					?>
				</table>

				<h2>Ostatní</h2>
				<table class="form-table" role="presentation">
					<?php
					self::field( 'partner_url', 'Web Fellaship', 'url', 'Odkaz na konci dotazníku pro B2B a rozpočty do 30 tis. Prázdné = odkaz se neukáže.' );
					self::field( 'privacy_url', 'Zásady ochrany osobních údajů', 'text' );
					self::field( 'posthog_key', 'PostHog project key (server)', 'text' );
					self::field( 'posthog_host', 'PostHog host (server)', 'url' );
					?>
				</table>
				<?php submit_button( 'Uložit nastavení' ); ?>
			</form>

			<hr>
			<h2>Notion databáze</h2>
			<?php if ( ! VDQ_Notion::configured() ) : ?>
				<p>Nejdřív vyplňte secret a databázi.</p>
			<?php else : ?>
				<div class="vdq-inline-forms">
					<?php self::form_open( 'notion_test' ); ?><button class="button">Otestovat spojení</button></form>
					<?php self::form_open( 'notion_schema' ); ?>
						<label><input type="checkbox" name="remove_old" value="1"> smazat sloupce, které dotazník nepoužívá</label>
						<button class="button button-primary" onclick="return !this.form.remove_old.checked || confirm('Smazat nepoužívané sloupce včetně jejich dat?');">Upravit schéma databáze</button>
					</form>
					<?php self::form_open( 'notion_resync' ); ?><button class="button">Synchronizovat vše znovu</button></form>
				</div>
				<?php if ( $schema ) : ?>
					<pre class="vdq-pre"><?php echo esc_html( wp_json_encode( $schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ); ?></pre>
				<?php endif; ?>
				<?php if ( $notion_err ) : ?>
					<p class="vdq-muted">Poslední chyba: <?php echo esc_html( implode( ' · ', $notion_err ) ); ?></p>
				<?php endif; ?>
			<?php endif; ?>

			<hr>
			<h2>Testovací e-maily</h2>
			<?php self::form_open( 'test_email' ); ?>
				<input type="email" name="to" required value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>">
				<select name="kind">
					<option value="reminder">Připomínka (4 h)</option>
					<option value="confirmation">Potvrzení odeslání</option>
				</select>
				<button class="button">Poslat test</button>
			</form>
			<p class="vdq-muted">Test použije poslední odeslaný dotazník (nebo ukázková data). Odhlášené adresy nic nedostanou.</p>

			<h2>Plánovač</h2>
			<p>Další běh (připomínky, Notion): <?php echo esc_html( $next ? wp_date( 'j. n. Y H:i:s', $next ) : 'nenaplánováno' ); ?>.
			WP-Cron běží jen při návštěvě webu; pro přesné připomínky nastavte na serveru cron <code>*/5 * * * * curl -s <?php echo esc_html( site_url( 'wp-cron.php' ) ); ?> &gt;/dev/null</code>.</p>
			<?php self::form_open( 'run_cron' ); ?><button class="button">Spustit teď</button></form>

			<?php if ( $mail_log ) : ?>
				<h2>Poslední e-maily</h2>
				<table class="widefat striped vdq-table">
					<thead><tr><th>Kdy (UTC)</th><th>Typ</th><th>Komu</th><th>Výsledek</th></tr></thead>
					<tbody>
					<?php foreach ( array_slice( $mail_log, 0, 15 ) as $l ) : ?>
						<tr><td><?php echo esc_html( $l[0] ); ?></td><td><?php echo esc_html( $l[1] ); ?></td><td><?php echo esc_html( $l[2] ); ?></td><td><?php echo esc_html( $l[3] ); ?></td></tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function action_save_settings() {
		self::guard( 'save_settings' );
		$in   = isset( $_POST['vdq'] ) && is_array( $_POST['vdq'] ) ? wp_unslash( $_POST['vdq'] ) : array(); // phpcs:ignore
		$out  = array();
		$defs = VDQ_Settings::defaults();
		foreach ( $defs as $key => $default ) {
			if ( ! array_key_exists( $key, $in ) || VDQ_Settings::is_constant( $key ) ) {
				continue;
			}
			$v = trim( (string) $in[ $key ] );
			if ( in_array( $key, array( 'resend_key', 'notion_secret' ), true ) ) {
				if ( '' === $v ) {
					continue; // Keep the stored secret.
				}
				$out[ $key ] = sanitize_text_field( $v );
			} elseif ( 'reminder_hours' === $key ) {
				$out[ $key ] = max( 1, (float) $v );
			} elseif ( in_array( $key, array( 'from_email', 'reply_to' ), true ) ) {
				$out[ $key ] = sanitize_email( $v );
			} elseif ( in_array( $key, array( 'partner_url', 'posthog_host' ), true ) ) {
				$out[ $key ] = esc_url_raw( $v );
			} else {
				$out[ $key ] = sanitize_text_field( $v );
			}
		}
		VDQ_Settings::update( $out );
		self::back( 'vdq-settings', array( 'vdq_msg' => 'Uloženo.' ) );
	}

	public static function action_test_email() {
		self::guard( 'test_email' );
		$to   = isset( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : '';
		$kind = isset( $_POST['kind'] ) ? sanitize_key( $_POST['kind'] ) : 'reminder';
		list( $rows ) = VDQ_Store::query( array( 'status' => 'done', 'per_page' => 1 ) );
		$row = $rows ? $rows[0] : null;
		if ( ! $row ) {
			$row = array(
				'id'                 => 0,
				'uuid'               => '00000000-0000-4000-8000-000000000000',
				'email'              => $to,
				'step'               => 'social',
				'answers'            => array(
					'company_type' => 'b2c',
					'website'      => 'https://velocity.ooo',
					'socials'      => array( 'instagram', 'tiktok' ),
					'instagram'    => 'velocity',
					'tiktok'       => 'velocity',
					'social_usage' => 'sometimes',
					'budget'       => '35-60',
					'ad_spend'     => '10-20',
					'source'       => 'google',
					'name'         => 'Jana Testovací',
					'role'         => 'cmo',
				),
				'submission_done_at' => null,
			);
		}
		$res = 'confirmation' === $kind
			? VDQ_Mailer::send_confirmation( $row, $to )
			: VDQ_Mailer::send_reminder( $row, VDQ_Store::token_for( $row['uuid'] ), $to );
		self::back(
			'vdq-settings',
			is_wp_error( $res ) ? array( 'vdq_msg' => 'Chyba: ' . $res->get_error_message(), 'vdq_err' => 1 ) : array( 'vdq_msg' => 'Testovací e-mail odeslán na ' . $to . '.' )
		);
	}

	public static function action_notion_test() {
		self::guard( 'notion_test' );
		$db = VDQ_Notion::request( 'GET', '/databases/' . VDQ_Notion::database_id() );
		if ( is_wp_error( $db ) ) {
			self::back( 'vdq-settings', array( 'vdq_msg' => 'Notion: ' . $db->get_error_message(), 'vdq_err' => 1 ) );
		}
		$title = isset( $db['title'][0]['plain_text'] ) ? $db['title'][0]['plain_text'] : '(bez názvu)';
		self::back( 'vdq-settings', array( 'vdq_msg' => sprintf( 'Spojení funguje: databáze „%s“, %d sloupců.', $title, count( $db['properties'] ) ) ) );
	}

	public static function action_notion_schema() {
		self::guard( 'notion_schema' );
		$res = VDQ_Notion::sync_schema( ! empty( $_POST['remove_old'] ) );
		if ( is_wp_error( $res ) ) {
			self::back( 'vdq-settings', array( 'vdq_msg' => 'Notion: ' . $res->get_error_message(), 'vdq_err' => 1 ) );
		}
		set_transient( 'vdq_schema_report', $res, HOUR_IN_SECONDS );
		self::back( 'vdq-settings', array( 'vdq_msg' => 'Schéma upraveno.' ) );
	}

	public static function action_notion_resync() {
		self::guard( 'notion_resync' );
		global $wpdb;
		$t = VDQ_Store::sessions_table();
		$wpdb->query( "UPDATE $t SET notion_synced_at = NULL WHERE email <> ''" ); // phpcs:ignore
		$done = 0;
		foreach ( VDQ_Store::notion_backlog( 40 ) as $row ) {
			if ( true === VDQ_Notion::sync( $row ) ) {
				$done++;
			}
		}
		self::back( 'vdq-settings', array( 'vdq_msg' => sprintf( 'Synchronizováno %d záznamů, zbytek dorovná plánovač.', $done ) ) );
	}

	public static function action_run_cron() {
		self::guard( 'run_cron' );
		VDQ_Cron::tick();
		self::back( 'vdq-settings', array( 'vdq_msg' => 'Plánovač proběhl.' ) );
	}
}
