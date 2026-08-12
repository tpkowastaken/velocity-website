#!/usr/bin/env python3
"""Generate Bricks JSON for the Velocity Typografie specimen page.

Source: claude-design/_ds/.../tokens/typography.css (Bebas Neue + Poppins scale).
"""

from __future__ import annotations

import json
from pathlib import Path

OUT = Path(__file__).resolve().parent / "typografie.json"
PUSH = Path(__file__).resolve().parent / "push_typografie.json"

BASE = "https://dev.velocity.ooo"


def color(var: str) -> dict[str, str]:
    return {"raw": f"var(--{var})"}


def pad(t: str, r: str, b: str, l: str) -> dict[str, str]:
    return {"top": t, "right": r, "bottom": b, "left": l}


def el(eid: str, name: str, parent: str | int, settings: dict, label: str | None = None) -> dict:
    node = {"id": eid, "name": name, "parent": parent, "settings": settings}
    if label:
        node["label"] = label
    return node


def section(eid: str, bg: str, py: str = "var(--space-section)") -> dict:
    return el(
        eid,
        "section",
        0,
        {
            "_background": {"color": color(bg)},
            "_padding": pad(py, "0px", py, "0px"),
        },
        label=eid,
    )


def container(eid: str, parent: str) -> dict:
    return el(
        eid,
        "container",
        parent,
        {
            "_maxWidth": "var(--wrap)",
            "_width": "100%",
            "_margin": pad("0px", "auto", "0px", "auto"),
            "_padding": pad("0px", "var(--space-xl)", "0px", "var(--space-xl)"),
        },
    )


def eyebrow(eid: str, parent: str, text: str) -> dict:
    return el(
        eid,
        "text-basic",
        parent,
        {
            "text": text,
            "_typography": {
                "color": color("voltage"),
                "font-family": "Poppins",
                "font-size": "var(--text-xs)",
                "font-weight": "600",
                "letter-spacing": "0.08em",
                "text-transform": "uppercase",
            },
            "_margin": pad("0px", "0px", "10px", "0px"),
        },
    )


def heading(
    eid: str,
    parent: str,
    tag: str,
    text: str,
    *,
    size: str,
    color_var: str,
    mb: str = "0px",
    lh: str = "0.87",
    css: str | None = None,
) -> dict:
    settings: dict = {
        "tag": tag,
        "text": text,
        "_typography": {
            "color": color(color_var),
            "font-family": "Bebas Neue",
            "font-size": size,
            "line-height": lh,
            "letter-spacing": "0.02em",
        },
        "_margin": pad("0px", "0px", mb, "0px"),
    }
    if css:
        settings["_cssCustom"] = css
    return el(eid, "heading", parent, settings)


def body(
    eid: str,
    parent: str,
    text: str,
    *,
    color_var: str,
    size: str = "var(--text-base)",
    weight: str = "400",
    lh: str = "1.6",
    mt: str = "0px",
    mb: str = "0px",
    maxw: str | None = None,
) -> dict:
    settings: dict = {
        "text": text,
        "_typography": {
            "color": color(color_var),
            "font-family": "Poppins",
            "font-size": size,
            "font-weight": weight,
            "line-height": lh,
        },
        "_margin": pad(mt, "0px", mb, "0px"),
    }
    if maxw:
        settings["_maxWidth"] = maxw
    return el(eid, "text-basic", parent, settings)


def label(eid: str, parent: str, text: str, color_var: str) -> dict:
    return el(
        eid,
        "text-basic",
        parent,
        {
            "text": text,
            "_typography": {
                "color": color(color_var),
                "font-family": "Poppins",
                "font-size": "var(--text-xs)",
                "font-weight": "600",
                "letter-spacing": "0.08em",
                "text-transform": "uppercase",
            },
            "_margin": pad("0px", "0px", "var(--space-2)", "0px"),
        },
    )


def specimen_row(
    wrap_id: str,
    lab_id: str,
    sample_id: str,
    parent: str,
    token_label: str,
    sample: str,
    *,
    family: str,
    size: str,
    color_var: str,
    label_color: str,
    weight: str = "400",
    lh: str = "0.87",
    tracking: str = "0.02em",
    transform: str | None = "uppercase",
    css: str | None = None,
) -> list[dict]:
    wrap = el(
        wrap_id,
        "div",
        parent,
        {
            "_padding": pad("var(--space-5)", "0px", "var(--space-5)", "0px"),
            "_border": {
                "width": {"top": "0px", "right": "0px", "bottom": "1px", "left": "0px"},
                "style": "solid",
                "color": color("border-subtle") if label_color != "voltage" else color("border-inverse"),
            },
            "_width": "100%",
        },
        label=token_label,
    )
    typo: dict = {
        "color": color(color_var),
        "font-family": family,
        "font-size": size,
        "font-weight": weight,
        "line-height": lh,
        "letter-spacing": tracking,
    }
    if transform:
        typo["text-transform"] = transform
    sample_settings: dict = {"text": sample, "_typography": typo}
    if css:
        sample_settings["_cssCustom"] = css
    return [
        wrap,
        label(lab_id, wrap_id, token_label, label_color),
        el(sample_id, "text-basic", wrap_id, sample_settings),
    ]


def build() -> list[dict]:
    els: list[dict] = []

    # ----- Hero -----
    els += [
        section("tysec1", "bedrock", "var(--space-hero)"),
        container("tycnt1", "tysec1"),
        eyebrow("tyeb01", "tycnt1", "Typografie"),
        heading(
            "tyhd01",
            "tycnt1",
            "h1",
            "TYPOGRAFIE",
            size="var(--display-1)",
            color_var="signal",
            css="@media (max-width: 900px) { %root% { font-size: 50px !important; } }",
        ),
        body(
            "tybd01",
            "tycnt1",
            "Dve rodiny. Bebas Neue na nadpisy. Poppins na telo. Ostrost, zadna vata.",
            color_var="text-muted-inverse",
            size="var(--text-lede)",
            mt="var(--space-5)",
            maxw="520px",
        ),
    ]
    # Fix Czech diacritics in hero body
    els[-1]["settings"]["text"] = (
        "Dvě rodiny. Bebas Neue na nadpisy. Poppins na tělo. Ostrost, žádná vata."
    )

    # ----- Bebas Neue display scale -----
    els += [
        section("tysec2", "signal"),
        container("tycnt2", "tysec2"),
        heading("tyhd02", "tycnt2", "h2", "BEBAS NEUE", size="var(--display-2)", color_var="text-strong", mb="var(--space-3)"),
        body(
            "tybd02",
            "tycnt2",
            "Nadpisy a odvážná tvrzení. Line-height 0.87. Tracking 0.02em. Vždy verzálky.",
            color_var="text-muted",
            mb="var(--space-5)",
            maxw="560px",
        ),
    ]
    display_rows = [
        ("tyd1w", "tyd1l", "tyd1s", "display-1 / 64px", "JAK PRODAT GENERACI", "var(--display-1)"),
        ("tyd2w", "tyd2l", "tyd2s", "display-2 / 44px", "VŠE. MĚŘITELNÉ. V ČASE.", "var(--display-2)"),
        ("tyd3w", "tyd3l", "tyd3s", "display-3 / 32px", "AUTENTICITU NEUVIDÍTE V TABULCE", "var(--display-3)"),
        ("tyd4w", "tyd4l", "tyd4s", "display-4 / 24px", "GEN Z MARKETING", "var(--display-4)"),
    ]
    for wrap, lab, samp, tok, sample, size in display_rows:
        els += specimen_row(
            wrap, lab, samp, "tycnt2", tok, sample,
            family="Bebas Neue",
            size=size,
            color_var="text-strong",
            label_color="text-muted",
            css="@media (max-width: 900px) { %root% { font-size: 34px !important; } }" if wrap == "tyd1w" else None,
        )

    # ----- Poppins body scale -----
    els += [
        section("tysec3", "white"),
        container("tycnt3", "tysec3"),
        heading("tyhd03", "tycnt3", "h2", "POPPINS", size="var(--display-2)", color_var="text-strong", mb="var(--space-3)"),
        body(
            "tybd03",
            "tycnt3",
            "Tělo, akcent, odkazy. Sentence case. Line-height 1.6 u odstavců. Váhy 300 až 700.",
            color_var="text-muted",
            mb="var(--space-5)",
            maxw="560px",
        ),
    ]
    weight_rows = [
        ("tyw3w", "tyw3l", "tyw3s", "Light 300", "300", "Stavíme obsah, který dokážeme čísly."),
        ("tyw4w", "tyw4l", "tyw4s", "Regular 400", "400", "Stavíme obsah, který dokážeme čísly."),
        ("tyw5w", "tyw5l", "tyw5s", "Medium 500", "500", "Stavíme obsah, který dokážeme čísly."),
        ("tyw6w", "tyw6l", "tyw6s", "Semibold 600", "600", "Stavíme obsah, který dokážeme čísly."),
        ("tyw7w", "tyw7l", "tyw7s", "Bold 700", "700", "Stavíme obsah, který dokážeme čísly."),
    ]
    for wrap, lab, samp, tok, weight, sample in weight_rows:
        els += specimen_row(
            wrap, lab, samp, "tycnt3", tok, sample,
            family="Poppins",
            size="var(--text-md)",
            color_var="text-body",
            label_color="text-muted",
            weight=weight,
            lh="1.6",
            tracking="0em",
            transform=None,
        )

    # Size scale on the same light band, nested grid
    els.append(
        el(
            "tysizg",
            "div",
            "tycnt3",
            {
                "_display": "grid",
                "_gridTemplateColumns": "repeat(2, 1fr)",
                "_gridGap": "var(--space-5)",
                "_margin": pad("var(--space-6)", "0px", "0px", "0px"),
                "_width": "100%",
                "_cssCustom": "@media (max-width: 900px) { %root% { grid-template-columns: 1fr !important; } }",
            },
            label="Body sizes",
        )
    )
    size_cells = [
        ("tysxsw", "tysxsl", "tysxss", "text-xs / 12px", "var(--text-xs)", "Verzálkový popisek. Tracking 0.08em."),
        ("tyssmw", "tyssml", "tyssms", "text-sm / 13px", "var(--text-sm)", "Drobný popis, metadata, copyright."),
        ("tysbsw", "tysbsl", "tysbss", "text-base / 15px", "var(--text-base)", "Základní odstavec a tlačítka."),
        ("tysmdw", "tysmdl", "tysmds", "text-md / 16px", "var(--text-md)", "Zvýrazněné tělo, perex karet."),
        ("tyslgw", "tyslgl", "tyslgs", "text-lg / 20px", "var(--text-lg)", "Úvodní věta pod nadpisem."),
        ("tysldw", "tysldl", "tyslds", "text-lede / 18px", "var(--text-lede)", "Perex pod h1. Měřený řádek."),
    ]
    for wrap, lab, samp, tok, size, sample in size_cells:
        wrap_el = el(wrap, "div", "tysizg", {"_width": "100%"}, label=tok)
        els += [
            wrap_el,
            label(lab, wrap, tok, "text-muted"),
            el(
                samp,
                "text-basic",
                wrap,
                {
                    "text": sample,
                    "_typography": {
                        "color": color("text-body"),
                        "font-family": "Poppins",
                        "font-size": size,
                        "line-height": "1.6",
                    },
                },
            ),
        ]

    # ----- Pairing -----
    els += [
        section("tysec4", "bedrock"),
        container("tycnt4", "tysec4"),
        heading("tyhd04", "tycnt4", "h2", "PÁROVÁNÍ", size="var(--display-2)", color_var="signal", mb="var(--space-3)"),
        body(
            "tybd04",
            "tycnt4",
            "Bebas nad Poppins. Takhle vypadá lockup na webu.",
            color_var="text-muted-inverse",
            mb="var(--space-6)",
            maxw="480px",
        ),
        heading(
            "typh01",
            "tycnt4",
            "h3",
            "JAK PRODAT GENERACI, KTERÁ NEKUPUJE OD NUDNÝCH ZNAČEK.",
            size="var(--display-1)",
            color_var="signal",
            css="@media (max-width: 900px) { %root% { font-size: 34px !important; } }",
        ),
        body(
            "typb01",
            "tycnt4",
            "Stavíme obsah pro generaci Z, který skutečně funguje. Žádný náhodný obsah. Vše stoji na výzkumu a strategii.",
            color_var="text-muted-inverse",
            size="var(--text-lede)",
            mt="var(--space-5)",
            maxw="520px",
        ),
    ]
    els[-1]["settings"]["text"] = (
        "Stavíme obsah pro generaci Z, který skutečně funguje. Žádný náhodný obsah. Vše stojí na výzkumu a strategii."
    )
    els.append(
        el(
            "tybtns",
            "div",
            "tycnt4",
            {
                "_display": "flex",
                "_direction": "row",
                "_flexWrap": "wrap",
                "_columnGap": "var(--space-3)",
                "_rowGap": "var(--space-3)",
                "_margin": pad("var(--space-6)", "0px", "0px", "0px"),
            },
            label="Pairing CTAs",
        )
    )
    els.append(
        el(
            "tybtn1",
            "button",
            "tybtns",
            {
                "text": "Zjistěte, jestli jsme pro vás",
                "link": {"type": "external", "url": f"{BASE}/kontakt/"},
                "style": "primary",
                "_background": {"color": color("voltage")},
                "_typography": {
                    "color": color("bedrock"),
                    "font-family": "Poppins",
                    "font-weight": "700",
                    "font-size": "var(--text-base)",
                },
                "_padding": pad("15px", "28px", "15px", "28px"),
                "_border": {"width": {"top": "0px", "right": "0px", "bottom": "0px", "left": "0px"}},
            },
            label="Primary CTA",
        )
    )
    els.append(
        el(
            "tybtn2",
            "button",
            "tybtns",
            {
                "text": "NAŠE VÝSLEDKY",
                "link": {"type": "external", "url": f"{BASE}/reference/"},
                "style": "outline",
                "_background": {"color": {"raw": "transparent"}},
                "_border": {
                    "width": {"top": "1.5px", "right": "1.5px", "bottom": "1.5px", "left": "1.5px"},
                    "style": "solid",
                    "color": color("voltage"),
                },
                "_typography": {
                    "color": color("voltage"),
                    "font-family": "Bebas Neue",
                    "font-size": "var(--display-4)",
                    "letter-spacing": "0.08em",
                },
                "_padding": pad("15px", "28px", "15px", "28px"),
            },
            label="Caps outline CTA",
        )
    )

    # ----- Rules (2-col, not 3 cards) -----
    els += [
        section("tysec5", "signal"),
        container("tycnt5", "tysec5"),
        heading("tyhd05", "tycnt5", "h2", "PRAVIDLA", size="var(--display-2)", color_var="text-strong", mb="var(--space-6)"),
        el(
            "tyrule",
            "div",
            "tycnt5",
            {
                "_display": "grid",
                "_gridTemplateColumns": "1fr 1fr",
                "_columnGap": "var(--space-7)",
                "_rowGap": "var(--space-6)",
                "_width": "100%",
                "_cssCustom": "@media (max-width: 900px) { %root% { grid-template-columns: 1fr !important; } }",
            },
            label="Rules split",
        ),
        el("tyrl", "div", "tyrule", {"_width": "100%"}, label="Headings rules"),
        heading("tyrlh", "tyrl", "h3", "NADPISY", size="var(--display-3)", color_var="text-strong", mb="var(--space-3)"),
        body(
            "tyrlb",
            "tyrl",
            "Bebas Neue. Vždy verzálky. Line-height 0.87. Tracking 0.02em. U all-caps tlačítek 0.08em. Hierarchii drží váha a barva, ne jen velikost.",
            color_var="text-muted",
        ),
        el("tyrr", "div", "tyrule", {"_width": "100%"}, label="Body rules"),
        heading("tyrrh", "tyrr", "h3", "TĚLO", size="var(--display-3)", color_var="text-strong", mb="var(--space-3)"),
        body(
            "tyrrb",
            "tyrr",
            "Poppins. Sentence case. Line-height 1.6. Voltage jen na malý text, odkazy a ikony. Nikdy na velkou plochu. Emoji nepoužíváme.",
            color_var="text-muted",
        ),
    ]

    return els


def main() -> None:
    elements = build()
    OUT.write_text(json.dumps(elements, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    payload = {"post_id": 65, "area": "content", "elements": elements}
    PUSH.write_text(json.dumps(payload, ensure_ascii=False) + "\n", encoding="utf-8")
    print(f"Wrote {len(elements)} elements to {OUT.name} and {PUSH.name}")


if __name__ == "__main__":
    main()
