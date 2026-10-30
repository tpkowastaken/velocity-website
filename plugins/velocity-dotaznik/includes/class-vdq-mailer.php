<?php
/**
 * Transactional e-mails: the 4-hour reminder and the submission confirmation.
 *
 * Sends through the Resend API when a key is configured, otherwise wp_mail().
 * Unsubscribed addresses never receive anything.
 *
 * @package VelocityDotaznik
 */

defined( 'ABSPATH' ) || exit;

final class VDQ_Mailer {

	/* ---------------------------------------------------------------------
	 * Unsubscribe links (HMAC signed, no database lookup needed)
	 * ------------------------------------------------------------------- */

	public static function sign( $email ) {
		return substr( hash_hmac( 'sha256', strtolower( $email ), wp_salt( 'auth' ) . 'vdq-unsub' ), 0, 32 );
	}

	public static function encode_email( $email ) {
		return rtrim( strtr( base64_encode( strtolower( $email ) ), '+/', '-_' ), '=' );
	}

	public static function decode_email( $e ) {
		$email = base64_decode( strtr( (string) $e, '-_', '+/' ), true );
		return $email && is_email( $email ) ? strtolower( $email ) : '';
	}

	public static function verify( $e, $s ) {
		$email = self::decode_email( $e );
		return ( $email && is_string( $s ) && hash_equals( self::sign( $email ), $s ) ) ? $email : '';
	}

	public static function unsubscribe_url( $email ) {
		return add_query_arg(
			array(
				'e' => self::encode_email( $email ),
				's' => self::sign( $email ),
			),
			home_url( '/dotaznik/odhlaseni/' )
		);
	}

	public static function one_click_url( $email ) {
		return add_query_arg(
			array(
				'e' => self::encode_email( $email ),
				's' => self::sign( $email ),
			),
			rest_url( 'velocity-dotaznik/v1/unsubscribe' )
		);
	}

	/* ---------------------------------------------------------------------
	 * Messages
	 * ------------------------------------------------------------------- */

	/**
	 * @param array  $row   Session row.
	 * @param string $token Plain session token for the resume link.
	 */
	public static function send_reminder( array $row, $token, $to = null ) {
		$to   = $to ? $to : $row['email'];
		$link = add_query_arg(
			array(
				'utm_source'   => 'email',
				'utm_medium'   => 'transactional',
				'utm_campaign' => 'dotaznik_reminder',
				'ref'          => 'reminder',
			),
			VDQ_Settings::form_url( $token )
		);
		$html = self::render(
			array(
				'preheader' => 'Vaše odpovědi jsme uložili. Dokončení zabere asi 2 minuty.',
				'heading'   => 'Dotazník máte rozpracovaný',
				'paras'     => array(
					'Dobrý den,',
					'začali jste vyplňovat dotazník na webu Velocity. Vaše odpovědi jsme uložili, takže můžete pokračovat přesně tam, kde jste skončili. Zbývá jen pár otázek.',
				),
				'cta'       => array( 'Dokončit dotazník', $link ),
				'after'     => array( 'Pokud jste dotazník vyplňovat nechtěli, tento e-mail můžete ignorovat. Další připomínku už neposíláme.' ),
				'reason'    => 'Tento e-mail jsme poslali jednorázově, protože jste na velocity.ooo zadali svůj e-mail a dotazník nedokončili.',
				'email'     => $to,
			)
		);
		$text = "Dobrý den,\n\nzačali jste vyplňovat dotazník na webu Velocity. Vaše odpovědi jsme uložili, můžete pokračovat tam, kde jste skončili:\n$link\n\nPokud jste dotazník vyplňovat nechtěli, tento e-mail můžete ignorovat. Další připomínku už neposíláme.\n\n" . self::text_footer( $to );
		return self::send( $to, 'Dotazník máte rozpracovaný', $html, $text, 'reminder' );
	}

	public static function send_confirmation( array $row, $to = null ) {
		$to      = $to ? $to : $row['email'];
		$answers = VDQ_Store::answers( $row );
		$name    = isset( $answers['name'] ) ? trim( (string) $answers['name'] ) : '';
		$first   = $name ? strtok( $name, ' ' ) : '';
		$partner = VDQ_Form::is_partner_fit( $answers );
		$summary = array();
		foreach ( VDQ_Form::grouped( $answers ) as $group ) {
			if ( 'goals' === $group['id'] ) {
				continue;
			}
			foreach ( $group['rows'] as $r ) {
				if ( 'checkbox' !== $r[2]['type'] ) {
					$summary[] = array( $r[0], $r[1] );
				}
			}
		}
		$paras = array(
			$first ? 'Dobrý den, ' . esc_html( $first ) . ',' : 'Dobrý den,',
			$partner && ! empty( $answers['consent_partner'] )
				? 'děkujeme za vyplnění dotazníku. Vaše odpovědi projdeme a ozveme se vám e-mailem. S vaším souhlasem je předáme také naší partnerské agentuře Fellaship s.r.o.'
				: 'děkujeme za vyplnění dotazníku. Odpovědi máme a <strong>do 24 hodin se vám ozveme</strong> s dalším postupem.',
			'Kdybyste chtěli cokoli doplnit, stačí odpovědět na tento e-mail. Odpověď dorazí přímo Matyáši Brůnovi.',
		);
		$html = self::render(
			array(
				'preheader' => 'Odpovědi máme, brzy se vám ozveme.',
				'heading'   => 'Dotazník máme',
				'paras'     => $paras,
				'summary'   => $summary,
				'reason'    => 'Tento e-mail jsme poslali, protože jste na velocity.ooo odeslali dotazník.',
				'email'     => $to,
			)
		);
		$text = wp_strip_all_tags( implode( "\n\n", $paras ) ) . "\n\nVaše odpovědi:\n";
		foreach ( $summary as $r ) {
			$text .= '- ' . $r[0] . ': ' . $r[1] . "\n";
		}
		$text .= "\n" . self::text_footer( $to );
		return self::send( $to, 'Dotazník máme, brzy se ozveme', $html, $text, 'confirmation' );
	}

	/* ---------------------------------------------------------------------
	 * Transport
	 * ------------------------------------------------------------------- */

	public static function send( $to, $subject, $html, $text, $kind ) {
		if ( VDQ_Store::is_unsubscribed( $to ) ) {
			return new WP_Error( 'vdq_unsubscribed', 'Adresa je odhlášená.' );
		}
		$s         = VDQ_Settings::all();
		$from      = sprintf( '%s <%s>', $s['from_name'], $s['from_email'] );
		$reply     = $s['reply_name'] ? sprintf( '%s <%s>', $s['reply_name'], $s['reply_to'] ) : $s['reply_to'];
		$list_unsub = sprintf( '<%s>, <mailto:%s?subject=unsubscribe>', self::one_click_url( $to ), $s['from_email'] );

		if ( ! empty( $s['resend_key'] ) ) {
			$res = wp_remote_post(
				'https://api.resend.com/emails',
				array(
					'timeout' => 15,
					'headers' => array(
						'Authorization' => 'Bearer ' . $s['resend_key'],
						'Content-Type'  => 'application/json',
					),
					'body'    => wp_json_encode(
						array(
							'from'     => $from,
							'to'       => array( $to ),
							'reply_to' => $reply,
							'subject'  => $subject,
							'html'     => $html,
							'text'     => $text,
							'headers'  => array(
								'List-Unsubscribe'      => $list_unsub,
								'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
							),
							'tags'     => array( array( 'name' => 'type', 'value' => $kind ) ),
						)
					),
				)
			);
			if ( is_wp_error( $res ) ) {
				self::log( $kind, $to, $res->get_error_message() );
				return $res;
			}
			$code = (int) wp_remote_retrieve_response_code( $res );
			if ( $code >= 300 ) {
				self::log( $kind, $to, 'Resend ' . $code . ': ' . wp_remote_retrieve_body( $res ) );
				return new WP_Error( 'vdq_resend', 'Resend ' . $code );
			}
			self::log( $kind, $to, 'ok (resend)' );
			return true;
		}

		$alt = function ( $phpmailer ) use ( $text ) {
			$phpmailer->AltBody = $text;
		};
		add_action( 'phpmailer_init', $alt );
		$sender = function ( $phpmailer ) use ( $s ) {
			$phpmailer->Sender = $s['from_email'];
		};
		add_action( 'phpmailer_init', $sender );
		$ok = wp_mail(
			$to,
			$subject,
			$html,
			array(
				'Content-Type: text/html; charset=UTF-8',
				'From: ' . $from,
				'Reply-To: ' . $reply,
				'List-Unsubscribe: ' . $list_unsub,
				'List-Unsubscribe-Post: List-Unsubscribe=One-Click',
			)
		);
		remove_action( 'phpmailer_init', $alt );
		remove_action( 'phpmailer_init', $sender );
		self::log( $kind, $to, $ok ? 'ok (wp_mail)' : 'wp_mail failed' );
		return $ok ? true : new WP_Error( 'vdq_mail', 'wp_mail failed' );
	}

	/** Keeps the last 50 send results for the settings screen. */
	private static function log( $kind, $to, $result ) {
		$log   = get_option( 'vdq_mail_log', array() );
		$log   = is_array( $log ) ? $log : array();
		array_unshift( $log, array( gmdate( 'Y-m-d H:i:s' ), $kind, $to, $result ) );
		update_option( 'vdq_mail_log', array_slice( $log, 0, 50 ), false );
	}

	private static function text_footer( $email ) {
		return "Velocity Marketing\nVelocity je značka TIMACE, družstvo, IČO 23962500, Štúrova 1154/30, 142 00 Praha 4.\nOdhlásit se z e-mailů: " . self::unsubscribe_url( $email );
	}

	private static function render( array $vars ) {
		$vars = wp_parse_args(
			$vars,
			array(
				'preheader' => '',
				'heading'   => '',
				'paras'     => array(),
				'cta'       => null,
				'after'     => array(),
				'summary'   => array(),
				'reason'    => '',
				'email'     => '',
			)
		);
		$vars['unsubscribe'] = self::unsubscribe_url( $vars['email'] );
		$vars['privacy']     = VDQ_Settings::privacy_url();
		ob_start();
		include VDQ_DIR . 'templates/email.php';
		return ob_get_clean();
	}
}
