<?php
/**
 * Velocity Dotazník: the questionnaire definition.
 *
 * This file is the single source of truth for steps, questions, options,
 * validation and conditional logic. The front end, the REST API, the admin
 * detail view, the e-mails and the Notion sync all read from it.
 *
 * Editing rules
 * - Question `id`s are stored in the database. Renaming one orphans old answers.
 * - Option `value`s are stored too. Change the `label`, keep the `value`.
 * - Conditions (`show_if`):
 *     ['q' => 'id', 'eq' => 'value']        equals
 *     ['q' => 'id', 'neq' => 'value']       not equals
 *     ['q' => 'id', 'in' => ['a', 'b']]     one of
 *     ['q' => 'id', 'has' => 'value']       multi choice contains
 *     ['q' => 'id', 'filled' => true]       answered (false = not answered)
 *     ['all' => [ ...conditions ]]          every condition
 *     ['any' => [ ...conditions ]]          at least one
 *     ['not' => condition]
 * - `required` may be true, false or a condition (required only when it passes).
 * - `inline` => true shows the question together with the one above it
 *   instead of waiting for it in the one-question-at-a-time reveal.
 * - `notion` => property name in the Notion database. Omit to skip.
 *
 * Types: email, text, textarea, url, tel, single, multi, social, handle, checkbox.
 *
 * @package VelocityDotaznik
 */

defined( 'ABSPATH' ) || exit;

// Leads that Velocity hands over (B2B, or a budget under 30k CZK).
$vdq_partner_fit = array(
	'any' => array(
		array( 'q' => 'company_type', 'eq' => 'b2b' ),
		array( 'q' => 'budget', 'eq' => 'lt30' ),
	),
);

return array(

	'version' => 1,

	// Steps up to and including `submit_step` are the questionnaire. Finishing
	// that step sets submission_done_at and sends the confirmation e-mail.
	'submit_step' => 'contact',

	// Who sees the goals step (step 5). Everyone else skips straight to the end.
	'partner_fit' => $vdq_partner_fit,

	'steps' => array(

		array(
			'id'       => 'start',
			'title'    => 'Pojďme na to',
			'subtitle' => 'Pár otázek, zabere to asi 3 minuty. Vše se průběžně ukládá.',
			'cta'      => 'Pokračovat',
			'notice'   => 'Kliknutím na Pokračovat souhlasíte, že vám nedokončený dotazník jednou připomeneme e-mailem. <a href="{privacy_url}" target="_blank" rel="noopener">Ochrana osobních údajů</a>',
			'questions' => array(
				array(
					'id'          => 'email',
					'type'        => 'email',
					'label'       => 'Jaký je váš pracovní e-mail?',
					'placeholder' => 'jmeno@firma.cz',
					'required'    => true,
					'notion'      => 'E-mail',
				),
				array(
					'id'       => 'company_type',
					'type'     => 'single',
					'label'    => 'Co nejlépe vystihuje vaši firmu?',
					'required' => true,
					'columns'  => 2,
					'options'  => array(
						array( 'value' => 'b2b', 'label' => 'B2B / B2G', 'hint' => 'Prodáváme firmám nebo státu' ),
						array( 'value' => 'b2c', 'label' => 'B2C', 'hint' => 'Prodáváme lidem' ),
					),
					'notion'   => 'Typ firmy',
				),
			),
		),

		array(
			'id'       => 'company',
			'title'    => 'Vaše firma',
			'subtitle' => 'Stačí web. Když ho nemáte, odpovězte na dvě krátké otázky.',
			'cta'      => 'Pokračovat',
			'questions' => array(
				array(
					'id'          => 'website',
					'type'        => 'url',
					'label'       => 'Jaký máte web?',
					'placeholder' => 'vasefirma.cz',
					'verify'      => true,
					'skip_label'  => 'Nemáme web',
					'skip_field'  => 'no_website',
					'required'    => array( 'q' => 'no_website', 'filled' => false ),
					'notion'      => 'Web',
				),
				array(
					'id'       => 'sector',
					'type'     => 'single',
					'label'    => 'V jakém sektoru podnikáte?',
					'required' => array( 'q' => 'website', 'filled' => false ),
					'columns'  => 3,
					'options'  => array(
						array( 'value' => 'services', 'label' => 'Služby' ),
						array( 'value' => 'ecommerce', 'label' => 'E-commerce' ),
						array( 'value' => 'saas', 'label' => 'SaaS' ),
						array( 'value' => 'retail', 'label' => 'Kamenný obchod' ),
						array( 'value' => 'other', 'label' => 'Jiný' ),
					),
					'notion'   => 'Sektor',
				),
				array(
					'id'          => 'sector_other',
					'type'        => 'text',
					'label'       => 'Jaký?',
					'placeholder' => 'Např. výroba, gastro, vzdělávání…',
					'inline'      => true,
					'max'         => 120,
					'show_if'     => array( 'q' => 'sector', 'eq' => 'other' ),
					'required'    => true,
					'notion'      => 'Sektor (jiný)',
				),
				array(
					'id'          => 'description',
					'type'        => 'textarea',
					'label'       => 'Popište vaši firmu ve 3 větách',
					'hint'        => 'Např. co prodáváte, komu, za kolik.',
					'max'         => 300,
					'required'    => array( 'q' => 'website', 'filled' => false ),
					'notion'      => 'Popis firmy',
				),
			),
		),

		array(
			'id'       => 'social',
			'title'    => 'Sociální sítě a rozpočet',
			'subtitle' => 'Vyberte sítě, které firma má. Profil ověříme za vás.',
			'cta'      => 'Pokračovat',
			'questions' => array(
				array(
					'id'       => 'socials',
					'type'     => 'social',
					'label'    => 'Jaké sociální sítě používáte?',
					'required' => true,
					'options'  => array(
						array( 'value' => 'instagram', 'label' => 'Instagram' ),
						array( 'value' => 'tiktok', 'label' => 'TikTok' ),
						array( 'value' => 'facebook', 'label' => 'Facebook' ),
						array( 'value' => 'none', 'label' => 'Zatím žádné', 'exclusive' => true ),
					),
					'notion'   => 'Sociální sítě',
				),
				array(
					'id'          => 'instagram',
					'type'        => 'handle',
					'network'     => 'instagram',
					'label'       => 'Instagram',
					'placeholder' => 'vasefirma',
					'inline'      => true,
					'show_if'     => array( 'q' => 'socials', 'has' => 'instagram' ),
					'required'    => true,
					'notion'      => 'Instagram',
				),
				array(
					'id'          => 'tiktok',
					'type'        => 'handle',
					'network'     => 'tiktok',
					'label'       => 'TikTok',
					'placeholder' => 'vasefirma',
					'inline'      => true,
					'show_if'     => array( 'q' => 'socials', 'has' => 'tiktok' ),
					'required'    => true,
					'notion'      => 'TikTok',
				),
				array(
					'id'          => 'facebook',
					'type'        => 'handle',
					'network'     => 'facebook',
					'label'       => 'Facebook',
					'placeholder' => 'vasefirma',
					'inline'      => true,
					'show_if'     => array( 'q' => 'socials', 'has' => 'facebook' ),
					'required'    => true,
					'notion'      => 'Facebook',
				),
				array(
					'id'       => 'social_usage',
					'type'     => 'single',
					'label'    => 'Jak často je využíváte?',
					'required' => true,
					'show_if'  => array(
						'all' => array(
							array( 'q' => 'socials', 'filled' => true ),
							array( 'not' => array( 'q' => 'socials', 'has' => 'none' ) ),
						),
					),
					'options'  => array(
						array( 'value' => 'active', 'label' => 'Aktivně, pravidelně publikujeme' ),
						array( 'value' => 'sometimes', 'label' => 'Občas něco sdílíme' ),
						array( 'value' => 'unused', 'label' => 'Máme je, ale nevyužíváme' ),
					),
					'notion'   => 'Využití sítí',
				),
				array(
					'id'       => 'budget',
					'type'     => 'single',
					'label'    => 'Jaký je váš měsíční rozpočet na social media marketing?',
					'required' => true,
					'columns'  => 3,
					'options'  => array(
						array( 'value' => 'lt30', 'label' => 'do 30 tis. Kč' ),
						array( 'value' => '30-35', 'label' => '30–35 tis. Kč' ),
						array( 'value' => '35-60', 'label' => '35–60 tis. Kč' ),
						array( 'value' => '60-100', 'label' => '60–100 tis. Kč' ),
						array( 'value' => '100+', 'label' => '100 tis. Kč a více' ),
					),
					'notion'   => 'Rozpočet SMM',
				),
				array(
					'id'       => 'ad_spend',
					'type'     => 'single',
					'label'    => 'Kolik měsíčně utratíte za Meta Ads?',
					'required' => true,
					'columns'  => 3,
					'options'  => array(
						array( 'value' => 'none', 'label' => 'Nepoužíváme' ),
						array( 'value' => 'lt5', 'label' => 'do 5 tis. Kč' ),
						array( 'value' => '5-10', 'label' => '5–10 tis. Kč' ),
						array( 'value' => '10-20', 'label' => '10–20 tis. Kč' ),
						array( 'value' => '20-30', 'label' => '20–30 tis. Kč' ),
						array( 'value' => '30-50', 'label' => '30–50 tis. Kč' ),
						array( 'value' => '50-100', 'label' => '50–100 tis. Kč' ),
						array( 'value' => '100-300', 'label' => '100–300 tis. Kč' ),
						array( 'value' => '300+', 'label' => '300 tis. Kč a více' ),
					),
					'notion'   => 'Meta Ads spend',
				),
			),
		),

		array(
			'id'       => 'contact',
			'title'    => 'Komu se ozveme?',
			'subtitle' => 'Poslední krok. Po odeslání se vám ozveme do 24 hodin.',
			'cta'      => 'Odeslat dotazník',
			'notice'   => 'Údaje použijeme k vyřízení vaší poptávky. <a href="{privacy_url}" target="_blank" rel="noopener">Ochrana osobních údajů</a>',
			'questions' => array(
				array(
					'id'       => 'source',
					'type'     => 'single',
					'label'    => 'Odkud jste se o nás dozvěděli?',
					'required' => true,
					'columns'  => 2,
					'options'  => array(
						array( 'value' => 'google', 'label' => 'Google' ),
						array( 'value' => 'linkedin', 'label' => 'LinkedIn' ),
						array( 'value' => 'referral', 'label' => 'Doporučení' ),
						array( 'value' => 'other', 'label' => 'Jinde' ),
					),
					'notion'   => 'Zdroj',
				),
				array(
					'id'          => 'source_other',
					'type'        => 'text',
					'label'       => 'Kde?',
					'placeholder' => 'Např. Instagram, podcast, akce…',
					'inline'      => true,
					'max'         => 120,
					'show_if'     => array( 'q' => 'source', 'eq' => 'other' ),
					'required'    => false,
					'notion'      => 'Zdroj (jiný)',
				),
				array(
					'id'           => 'name',
					'type'         => 'text',
					'label'        => 'Jak se jmenujete?',
					'placeholder'  => 'Jméno a příjmení',
					'autocomplete' => 'name',
					'max'          => 120,
					'required'     => true,
					'notion'       => 'Jméno',
				),
				array(
					'id'       => 'role',
					'type'     => 'single',
					'label'    => 'Jaká je vaše pracovní pozice?',
					'required' => true,
					'columns'  => 2,
					'options'  => array(
						array( 'value' => 'smm', 'label' => 'Social Media Manager' ),
						array( 'value' => 'influencer', 'label' => 'Influencer Marketing Manager' ),
						array( 'value' => 'head_social', 'label' => 'Head of Social Media Marketing' ),
						array( 'value' => 'digital', 'label' => 'Digital Marketing Manager' ),
						array( 'value' => 'cmo', 'label' => 'CMO' ),
						array( 'value' => 'other', 'label' => 'Jiná' ),
					),
					'notion'   => 'Pozice',
				),
				array(
					'id'          => 'role_other',
					'type'        => 'text',
					'label'       => 'Jaká?',
					'placeholder' => 'Např. majitel, CEO…',
					'inline'      => true,
					'max'         => 120,
					'show_if'     => array( 'q' => 'role', 'eq' => 'other' ),
					'required'    => true,
					'notion'      => 'Pozice (jiná)',
				),
				array(
					'id'           => 'phone',
					'type'         => 'tel',
					'label'        => 'Telefon',
					'hint'         => 'Zavoláme jen kvůli této poptávce.',
					'placeholder'  => '+420 777 123 456',
					'autocomplete' => 'tel',
					'required'     => false,
					'notion'       => 'Telefon',
				),
				array(
					'id'       => 'consent_newsletter',
					'type'     => 'checkbox',
					'label'    => 'Chci občas dostat e-mail s tipy na sociální sítě a novinkami Velocity. Odhlásit se jde jedním klikem.',
					'required' => false,
					'notion'   => 'Souhlas: newsletter',
				),
				array(
					'id'       => 'consent_partner',
					'type'     => 'checkbox',
					'label'    => 'Souhlasím s předáním mých odpovědí a kontaktu partnerské agentuře Fellaship s.r.o., která se mi může ozvat s nabídkou.',
					'required' => false,
					'show_if'  => $vdq_partner_fit,
					'notion'   => 'Souhlas: Fellaship',
				),
			),
		),

		array(
			'id'        => 'goals',
			'post'      => true,
			'show_if'   => array( 'not' => $vdq_partner_fit ),
			'title'     => 'Hotovo, máme vše potřebné',
			'subtitle'  => 'Matyáš Brůna se vám ozve do 24 hodin. Mezitím se můžete podívat na náš rozbor, nebo nám rovnou přesněji popsat vaše cíle.',
			'cta'       => 'Odeslat cíle',
			'opt_in'    => 'Popsat naše cíle',
			'questions' => array(
				array(
					'id'       => 'genz',
					'type'     => 'single',
					'label'    => 'Gen Z je pro nás…',
					'required' => true,
					'options'  => array(
						array( 'value' => 'main', 'label' => 'hlavní zákaznická skupina' ),
						array( 'value' => 'upcoming', 'label' => 'nastávající zákaznická skupina' ),
						array( 'value' => 'secondary', 'label' => 'vedlejší zákaznická skupina' ),
						array( 'value' => 'potential', 'label' => 'potenciální zákaznická skupina' ),
						array( 'value' => 'relevant', 'label' => 'relevantní zákaznická skupina' ),
						array( 'value' => 'none', 'label' => 'Gen Z pro nás není zákaznickou skupinou' ),
					),
					'notion'   => 'Gen Z',
				),
				array(
					'id'       => 'goal',
					'type'     => 'single',
					'label'    => 'Jakého výsledku chcete dosáhnout?',
					'required' => true,
					'columns'  => 3,
					'options'  => array(
						array( 'value' => 'reach', 'label' => 'Nárůst dosahu' ),
						array( 'value' => 'followers', 'label' => 'Nárůst followers' ),
						array( 'value' => 'leads', 'label' => 'Nárůst leadů' ),
						array( 'value' => 'sales', 'label' => 'Nárůst prodejů' ),
						array( 'value' => 'other', 'label' => 'Jiné' ),
					),
					'notion'   => 'Cíl',
				),
				array(
					'id'          => 'goal_other',
					'type'        => 'text',
					'label'       => 'Jaký?',
					'inline'      => true,
					'max'         => 200,
					'show_if'     => array( 'q' => 'goal', 'eq' => 'other' ),
					'required'    => true,
					'notion'      => 'Cíl (jiný)',
				),
				array(
					'id'       => 'horizon',
					'type'     => 'single',
					'label'    => 'Kolik měsíců máte na tento cíl vyhrazeno?',
					'required' => true,
					'columns'  => 5,
					'options'  => array(
						array( 'value' => '0-3', 'label' => '0–3' ),
						array( 'value' => '3-6', 'label' => '3–6' ),
						array( 'value' => '6-12', 'label' => '6–12' ),
						array( 'value' => '12-18', 'label' => '12–18' ),
						array( 'value' => '18+', 'label' => '18+' ),
					),
					'notion'   => 'Horizont (měsíce)',
				),
				array(
					'id'       => 'looking_for',
					'type'     => 'single',
					'label'    => 'Co hledáte?',
					'required' => true,
					'columns'  => 2,
					'options'  => array(
						array( 'value' => 'audit', 'label' => 'Audit sociálních sítí a Meta Ads' ),
						array( 'value' => 'strategy', 'label' => 'Konzultaci strategie' ),
						array( 'value' => 'production', 'label' => 'Produkci obsahu' ),
						array( 'value' => 'other', 'label' => 'Jiné' ),
					),
					'notion'   => 'Co hledají',
				),
				array(
					'id'       => 'blockers',
					'type'     => 'textarea',
					'label'    => 'Co vás nejvíce brzdí v dosažení cílů?',
					'max'      => 1500,
					'required' => true,
					'notion'   => 'Co je brzdí',
				),
				array(
					'id'       => 'notes',
					'type'     => 'textarea',
					'label'    => 'Je ještě něco, co bychom měli vědět?',
					'max'      => 1500,
					'required' => false,
					'notion'   => 'Poznámka',
				),
			),
		),
	),

	// Educational content shown after submitting (plain card, no Bricks).
	'education' => array(
		array(
			'url'     => '/case-studies/',
			'image'   => 'https://embed-ssl.wistia.com/deliveries/d227b34f6eb225daea1bc81fefaf2a2e.jpg?image_crop_resized=960x540',
			'eyebrow' => 'Case study · video rozbor',
			'title'   => 'Co bychom na sociálních sítích e‑kondomy.cz změnili jako první',
			'text'    => 'Kompletní rozbor účtu zdarma: 6 minut videa + článek s konkrétními kroky.',
			'cta'     => 'Pustit rozbor',
		),
	),
);
