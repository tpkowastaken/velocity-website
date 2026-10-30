<?php
/**
 * /dotaznik/odhlaseni/?e=…&s=… : confirms before unsubscribing, so link
 * scanners in mail clients cannot unsubscribe people by opening the link.
 *
 * @package VelocityDotaznik
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- signed link.
$vdq_e     = isset( $_GET['e'] ) ? sanitize_text_field( wp_unslash( $_GET['e'] ) ) : '';
$vdq_s     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$vdq_email = VDQ_Mailer::verify( $vdq_e, $vdq_s );
$vdq_done  = $vdq_email && VDQ_Store::is_unsubscribed( $vdq_email );
?>
<!DOCTYPE html>
<html lang="cs">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0b0b0b">
<?php wp_head(); ?>
</head>
<body class="vdq-body">
<a class="vdq-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Velocity, domů">VELOCITY<span></span></a>
<main class="vdq vdq-unsub" data-e="<?php echo esc_attr( $vdq_e ); ?>" data-s="<?php echo esc_attr( $vdq_s ); ?>">
	<div class="vdq-panel is-in">
		<?php if ( ! $vdq_email ) : ?>
			<h1 class="vdq-title">Odkaz nefunguje</h1>
			<p class="vdq-sub">Odkaz pro odhlášení je neplatný nebo neúplný. Napište nám na <a href="mailto:marketing@velocity.ooo">marketing@velocity.ooo</a> a odhlásíme vás ručně.</p>
		<?php elseif ( $vdq_done ) : ?>
			<h1 class="vdq-title">Jste odhlášeni</h1>
			<p class="vdq-sub">Na <strong><?php echo esc_html( $vdq_email ); ?></strong> vám už žádné e-maily neposíláme.</p>
			<a class="vdq-btn vdq-btn-ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>">Zpět na web</a>
		<?php else : ?>
			<h1 class="vdq-title" data-unsub-title>Odhlásit se z e-mailů?</h1>
			<p class="vdq-sub" data-unsub-text>Na <strong><?php echo esc_html( $vdq_email ); ?></strong> vám pak už nepošleme žádné připomínky ani novinky.</p>
			<div class="vdq-actions">
				<button type="button" class="vdq-btn" data-unsub>Odhlásit se</button>
				<a class="vdq-btn vdq-btn-ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>" data-unsub-back>Zpět na web</a>
			</div>
		<?php endif; ?>
	</div>
</main>
<?php wp_footer(); ?>
</body>
</html>
