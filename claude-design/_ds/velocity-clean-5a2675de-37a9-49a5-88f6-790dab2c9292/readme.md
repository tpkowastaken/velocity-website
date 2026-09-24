# Velocity — Design System

**Velocity** is a Czech **marketing & creative agency** ("marketingová & kreativní agentura") specialising in **Gen-Z content** — audits, content strategy, and monthly content production for brands in fashion/streetwear, beauty, supplements, and lifestyle. The brand voice is bold, confident, and results-driven: *"Autenticitu neuvidíte v tabulce. Ale změřit se dá."* (You won't see authenticity in a spreadsheet. But it can be measured.)

This design system encodes Velocity's visual language — the four-colour brand palette, the Bebas Neue / Poppins type pairing, the sharp-edged component set, and the agency's marketing-site UI — as tokens, React components, and reference screens for building on-brand interfaces and assets.

## Sources used
- **`uploads/Velocity Brand Guide_2026_4_27.pdf`** — 10-page brand guide: colour palette (Bedrock / Signal / Voltage / Chrome), fonts, logo rules, buttons, business cards, banners. Primary source of truth.
- **`cc/Typografie.dc.html`** — a hand-built HTML typography & component reference (in Czech) that already defined the token names, spacing scale, radii, and component styling. The token CSS here is lifted directly from it.
- **`uploads/velocity_logo_{voltage,black,white}.svg`** — the Velocity "V" mark (author: Michaela Levíčková, via Canva). Delivered without fills; fills were added to match each filename. Copied to `assets/logo/`.
- **`uploads/openart-…png`** — a 4096px chrome/liquid-metal 3D render of the V mark (the "Chrome" premium visual). Downscaled to `assets/brand/chrome-v.png`.
- **`uploads/IMG_*.jpg`** — team portraits shot on dark studio backgrounds. Copied to `assets/team/`.

## Root index / manifest
- **`styles.css`** — global entry point (consumers link this). `@import`s only.
- **`tokens/`** — `colors.css`, `typography.css`, `spacing.css`, `radius.css`, `fonts.css`.
- **`foundations/`** — specimen cards (Colors, Type, Spacing, Brand) for the Design System tab.
- **`components/`** — reusable React primitives (see list below).
- **`ui_kits/website/`** — the Velocity agency marketing site, a full interactive recreation.
- **`assets/`** — `logo/`, `brand/` (chrome render), `team/` (portraits).
- **`thumbnail.html`** — homepage tile.
- **`SKILL.md`** — Agent-Skill wrapper for use in Claude Code.

## Components
Grouped by concern under `components/`. All read the CSS custom properties; import via `const { X } = window.VelocityClean_5a2675`.

- **forms/** — `Button`, `IconButton`, `Input`, `Select`, `Checkbox`, `Radio`, `Switch`
- **display/** — `Badge`, `Tag`, `Card`
- **feedback/** — `Alert`, `Tooltip`
- **navigation/** — `Tabs`
- **brand/** — `Logo`, `Banner`

## UI kits
- **Website** (`ui_kits/website/`) — single-page agency site: sticky nav, dark hero with the chrome V, three-step services (with pricing + tabs), team strip, an interactive lead-capture form (fill → submit → success), and a footer CTA banner.

---

## CONTENT FUNDAMENTALS

**Language.** Primary language is **Czech**. Copy is direct and punchy, often broken into short, staccato fragments for rhythm: *"Vše. Měřitelné. V čase."* Sentences are confident statements, not hedged.

**Tone.** Bold, expert, a little provocative — the brand principles are *simplicity, perfection, reliability, boldness, expertise*. It speaks to founders/marketers who are frustrated with what isn't working ("Most companies don't know how to sell to Gen Z and lose hundreds of thousands on nonsense"). It positions Velocity as the measured, data-backed alternative to vague "authenticity."

**Person.** Addresses the client as **vy/vaše** (you/your) — "Zkoušeli jste UGC…", "vaše nejefektivnější reklamy". Velocity speaks as **my** (we) implicitly. Second-person, benefit-led.

**Casing.** Headlines and bold statements are **ALL CAPS** (Bebas Neue). Body is sentence case. All-caps is used at smaller sizes with regular/lighter weight (per the guide). Buttons come in both sentence-case (Poppins) and all-caps (Bebas) forms.

**Numbers & proof.** Concrete and prominent — prices in **Kč** (40 000 Kč, 70 000 Kč/měsíc), "Gen Z skóre", "~20 kusů obsahu měsíčně", "do 48 hodin". Measurability is the core promise; surface real numbers.

**Emoji.** **Not used.** The brand hashtags (#GEN-Z, #ENERGY, #PREMIUM, #SHARPNESS) appear as styling/tone markers in the brand guide but not as UI copy. Keep interfaces emoji-free.

**Vibe.** Premium-edgy meets Gen-Z energy. Serious about results, sharp in delivery. Think matte black hardware with one electric-green accent light.

---

## VISUAL FOUNDATIONS

**Colour.** Four brand colours, used with discipline:
- **BEDROCK `#0b0b0b`** — the default. Main backgrounds, bold text, logos. Authority, boldness, premium. Simple, clean, professional.
- **SIGNAL `#f1f0ef`** — high-contrast rest surfaces; "air" where the viewer chills out. Best as a background. Softens a Bedrock/Voltage mix.
- **VOLTAGE `#cbff1e`** — the accent, and the only saturated colour. Small text, icons, links, text-selection highlight, and buttons. **Never on large coloured areas.** Primarily on dark backgrounds; be intentional if used otherwise (e.g. buttons on light).
- **CHROME** — a metallic/liquid-metal gradient. Edgy, premium, sharp. Logo and hero moments only. **Never paired with Voltage.**
- **Pairing rule:** SIGNAL and CHROME are *never* paired with VOLTAGE. At most 1–2 background colours per composition.

**Type.** Two families:
- **Bebas Neue** — headings, bold statements. Line-height **1.2**, small letter-spacing (0.02em default; 0.08em for all-caps buttons). Condensed, tall, all-caps energy. The 1.2 leading keeps Czech diacritics off the line above.
- **Poppins** — body, accent/intro text, links. Line-height **0.80–1.10** for tight display body, up to 1.6 for paragraphs. Weights 300–700; italic for emphasis. Prices/numbers in semibold/bold.

**Spacing.** Strict **4 / 8 px scale** (`--space-1` 4 → `--space-10` 128). Nothing off-scale appears. Section vertical padding 64–96px; horizontal 48px; card/heading gaps 24px; button/tag gaps 12px.

**Backgrounds.** Mostly flat solids — Bedrock or Signal or white. Subtle diagonal (135°) gradients on cards and dark surfaces (bedrock→#050505, signal→#e9e9e7). The hero uses a plain Bedrock field with the chrome V floating in it. No busy patterns, no textures. The one signature graphic device is a **clipped diagonal voltage wedge** (polygon clip-path) on banners.

**Imagery.** Team/brand photography is shot on **dark, cool-toned studio backgrounds** with a single hard key light — moody, high-contrast, desaturated-cool. Portraits are square-cropped, aligned top. Sits naturally on Bedrock.

**Corners & edges.** Velocity is **sharp**. Buttons, badges, icon buttons and banners are **square (`--radius-none` 0)**. Radii are reserved: `--radius-sm` 4px for inputs/small cards, `--radius-md` 8px for cards/banners, `--radius-pill` 999px for tags and switches.

**Borders.** `1.5px` solid strokes are the default outline weight (buttons, inputs, checkboxes). Hairline `1px` for dividers and subtle card borders. On light: `--border-subtle` (#d8d7d4) or `--bedrock`. On dark: `--border-inverse` (rgba white 18%).

**Shadows.** Soft, low, cool, **never coloured**. `--shadow-card: 0 12px 24px -12px rgba(0,0,0,0.15)`. Cards float gently; most UI is flat. No inner shadows, no glow.

**Cards.** Signal (or bedrock) surface, subtle 135° gradient wash, 1px border, 8px radius, soft card shadow. Bebas title, muted Poppins body, price + arrow-icon-button footer.

**Motion.** Quick and unfussy — `--dur-fast` 100ms / `--dur-base` 120ms, `--ease-out` (no bounce). The signature interaction is a **press scale to 0.96** on buttons (0.92 on icon buttons). Hover = colour shift (links/nav go Voltage; outline fills darken). Switches slide; tooltips fade. No elaborate entrance animations.

**Hover states.** Links and nav items shift to **Voltage**. Buttons hold colour and rely on the press-scale. Tags/tabs shift fill/underline.

**Press states.** `transform: scale(0.96)` (buttons), `0.92` (icon buttons). No colour change on press.

**Transparency & blur.** The sticky header uses `rgba(11,11,11,0.86)` + `backdrop-filter: blur(12px)`. Otherwise transparency is limited to inverse borders. No frosted-glass everywhere.

**Layout.** Content maxes at **1200px**, centred, 48px side padding. Sticky top nav on dark. Generous vertical rhythm between full-width sections that alternate Bedrock / Signal / white.

---

## ICONOGRAPHY

Velocity has **no bespoke icon set** in the provided sources. The brand's own iconography in the reference DC is built from **bold Unicode glyphs inside square icon buttons** — `→` (continue), `+` (add), `↓` (download), `×` (close) — rendered heavy (font-weight 800), often with `-webkit-text-stroke` to thicken them. This arrow-and-plus, high-weight, monochrome approach is the house style: functional, sharp, no decorative icons.

- **No icon font or SVG sprite** ships with the brand. The `IconButton` component is designed to take a glyph or an SVG child.
- **Emoji are not used.**
- **The only real vector asset is the Velocity "V" logo** — provided as SVG (voltage/black/white) and as a chrome 3D render. The `Logo` component inlines the mark so it's colourable.
- **Recommendation / intentional addition:** when a richer icon set is needed, use **Lucide** (https://lucide.dev) via CDN — its 1.5–2px stroke, square-cornered, monochrome style matches Velocity's sharp weight. This is a *substitution*, flagged here: no icon library was in the source. Keep icons monochrome (Bedrock or Signal), reserve Voltage for the occasional accent glyph.

### Intentional additions
- **Lucide icons** (CDN) — suggested icon set; none was provided. See above.
- **`Tabs`, `Tooltip`, `Switch`, `Radio`** — standard primitives implied by the reference DC's "opakovatelné prvky" section; built to match its exact styling.

---

## Notes & substitutions
- **Fonts** are loaded from **Google Fonts** (Bebas Neue + Poppins) — the brand names these exact families, so the match is 1:1. If you have licensed font binaries, drop them in and swap `tokens/fonts.css` for local `@font-face` rules.
- **Logo SVGs** shipped with an empty style class (no fill); correct fills were injected per filename. Originals' geometry is untouched.
