<?php
/**
 * HTML e-mail layout. Table based, inline styles, 600px.
 *
 * @var array $vars preheader, heading, paras, cta, after, summary, reason, email, unsubscribe, privacy
 * @package VelocityDotaznik
 */

defined( 'ABSPATH' ) || exit;

$font    = "'Poppins','Helvetica Neue',Helvetica,Arial,sans-serif";
$display = "'Bebas Neue','Arial Narrow','Helvetica Neue',Arial,sans-serif";
?>
<!DOCTYPE html>
<html lang="cs" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="color-scheme" content="light only">
<meta name="supported-color-schemes" content="light">
<title><?php echo esc_html( $vars['heading'] ); ?></title>
<style>
	@media (max-width:620px){ .vdq-card{padding:28px 22px !important} .vdq-h1{font-size:36px !important} }
	a{color:#0b0b0b}
</style>
</head>
<body style="margin:0;padding:0;background:#f1f0ef;-webkit-text-size-adjust:100%;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;"><?php echo esc_html( $vars['preheader'] ); ?>&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f1f0ef;">
	<tr>
		<td align="center" style="padding:32px 12px;">
			<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;">
				<tr>
					<td style="background:#0b0b0b;border-radius:16px 16px 0 0;padding:22px 40px;">
						<span style="font-family:<?php echo $display; // phpcs:ignore ?>;font-size:28px;letter-spacing:2px;color:#f1f0ef;line-height:1;font-weight:700;">VELOCITY</span><span style="display:inline-block;width:8px;height:8px;background:#cbff1e;border-radius:50%;margin-left:4px;"></span>
					</td>
				</tr>
				<tr>
					<td class="vdq-card" style="background:#ffffff;padding:40px;border-radius:0 0 16px 16px;font-family:<?php echo $font; // phpcs:ignore ?>;color:#0b0b0b;">
						<h1 class="vdq-h1" style="margin:0 0 24px;font-family:<?php echo $display; // phpcs:ignore ?>;font-size:44px;line-height:1;letter-spacing:.5px;font-weight:700;text-transform:uppercase;color:#0b0b0b;"><?php echo esc_html( $vars['heading'] ); ?></h1>
						<?php foreach ( $vars['paras'] as $p ) : ?>
							<p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#0b0b0b;"><?php echo wp_kses( $p, array( 'strong' => array(), 'a' => array( 'href' => array() ) ) ); ?></p>
						<?php endforeach; ?>

						<?php if ( $vars['cta'] ) : ?>
							<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:28px 0;">
								<tr>
									<td style="background:#cbff1e;border-radius:999px;">
										<a href="<?php echo esc_url( $vars['cta'][1] ); ?>" style="display:inline-block;padding:16px 32px;font-family:<?php echo $font; // phpcs:ignore ?>;font-size:16px;font-weight:600;color:#0b0b0b;text-decoration:none;border-radius:999px;"><?php echo esc_html( $vars['cta'][0] ); ?> &rarr;</a>
									</td>
								</tr>
							</table>
						<?php endif; ?>

						<?php foreach ( $vars['after'] as $p ) : ?>
							<p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#6b6b68;"><?php echo esc_html( $p ); ?></p>
						<?php endforeach; ?>

						<?php if ( $vars['summary'] ) : ?>
							<p style="margin:28px 0 8px;font-size:12px;font-weight:600;letter-spacing:1px;text-transform:uppercase;color:#6b6b68;">Vaše odpovědi</p>
							<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-top:1px solid #e8e7e4;">
								<?php foreach ( $vars['summary'] as $r ) : ?>
									<tr>
										<td style="padding:10px 12px 10px 0;border-bottom:1px solid #e8e7e4;font-size:13px;line-height:1.4;color:#6b6b68;vertical-align:top;width:45%;"><?php echo esc_html( $r[0] ); ?></td>
										<td style="padding:10px 0;border-bottom:1px solid #e8e7e4;font-size:14px;line-height:1.4;color:#0b0b0b;vertical-align:top;"><?php echo nl2br( esc_html( $r[1] ) ); ?></td>
									</tr>
								<?php endforeach; ?>
							</table>
						<?php endif; ?>

						<p style="margin:32px 0 0;font-size:15px;line-height:1.5;color:#0b0b0b;">Velocity Marketing</p>
					</td>
				</tr>
				<tr>
					<td style="padding:24px 40px 0;font-family:<?php echo $font; // phpcs:ignore ?>;font-size:12px;line-height:1.6;color:#6b6b68;text-align:center;">
						<?php echo esc_html( $vars['reason'] ); ?><br>
						Velocity je značka TIMACE, družstvo, IČO 23962500, Štúrova 1154/30, 142&nbsp;00 Praha&nbsp;4.<br>
						<a href="<?php echo esc_url( $vars['privacy'] ); ?>" style="color:#6b6b68;">Ochrana osobních údajů</a> &middot;
						<a href="<?php echo esc_url( $vars['unsubscribe'] ); ?>" style="color:#6b6b68;">Odhlásit se z e-mailů</a>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</body>
</html>
