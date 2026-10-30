<?php
/**
 * Standalone page for /dotaznik/. assets/form.js builds the form inside #vdq.
 *
 * @package VelocityDotaznik
 */

defined( 'ABSPATH' ) || exit;
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
<main id="vdq" class="vdq" aria-live="polite">
	<div class="vdq-boot" aria-hidden="true"><span></span></div>
	<noscript><p class="vdq-noscript">Dotazník potřebuje zapnutý JavaScript.</p></noscript>
</main>
<?php wp_footer(); ?>
</body>
</html>
