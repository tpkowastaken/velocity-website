#!/usr/bin/env python3
"""Generate Bricks element JSON files for Velocity agency site."""

from __future__ import annotations

import json
import random
import string
from pathlib import Path
from typing import Any

OUT_DIR = Path(__file__).resolve().parent

BASE = "https://dev.velocity.ooo"
LOGO_VOLTAGE = f"{BASE}/wp-content/uploads/2026/07/velocity-voltage.svg"
LOGO_WHITE = f"{BASE}/wp-content/uploads/2026/07/velocity-white.svg"
CHROME_V = f"{BASE}/wp-content/uploads/2026/07/chrome-v.png"
PHONE = "+420604620011"
PHONE_DISPLAY = "604 620 011"

# --- helpers -----------------------------------------------------------------

_used_ids: set[str] = set()


def _new_id() -> str:
    chars = string.ascii_lowercase + string.digits
    while True:
        cid = "".join(random.choice(chars) for _ in range(6))
        if cid not in _used_ids:
            _used_ids.add(cid)
            return cid


def el(id: str, name: str, parent: str | int, settings: dict[str, Any]) -> dict[str, Any]:
    return {
        "id": id,
        "name": name,
        "parent": parent,
        "children": [],
        "settings": settings,
    }


def color(var: str) -> dict[str, str]:
    return {"raw": f"var(--{var})"}


def pad(t: str, r: str, b: str, l: str) -> dict[str, str]:
    return {"top": t, "right": r, "bottom": b, "left": l}


def typo(**kwargs: Any) -> dict[str, Any]:
    out: dict[str, Any] = {}
    for k, v in kwargs.items():
        if k == "color" and isinstance(v, str):
            out["color"] = color(v)
        else:
            out[k] = v
    return out


def link_ext(url: str, new_tab: bool = False) -> dict[str, Any]:
    return {"type": "external", "url": url, "newTab": new_tab}


def image_settings(url: str, alt: str = "") -> dict[str, Any]:
    return {
        "image": {
            "id": 0,
            "url": url,
            "filename": url.rsplit("/", 1)[-1],
            "size": "full",
            "full": url,
            "alt": alt,
        }
    }


def mobile_grid_1col() -> str:
    return "@media (max-width: 900px) { grid-template-columns: 1fr !important; gap: 20px !important; }"


def mobile_split_1col() -> str:
    return "@media (max-width: 900px) { grid-template-columns: 1fr !important; gap: 20px !important; }"


def mobile_h1() -> str:
    return "@media (max-width: 900px) { font-size: 50px !important; }"


def mobile_h2() -> str:
    return "@media (max-width: 900px) { font-size: 34px !important; }"


class Doc:
    """Flat Bricks element tree builder."""

    def __init__(self) -> None:
        self._elements: dict[str, dict[str, Any]] = {}
        self._order: list[str] = []

    def add(
        self,
        eid: str | None,
        name: str,
        parent: str | int,
        settings: dict[str, Any] | None = None,
        label: str | None = None,
    ) -> str:
        if eid is None:
            eid = _new_id()
        node = el(eid, name, parent, settings or {})
        if label:
            node["label"] = label
        self._elements[eid] = node
        self._order.append(eid)
        if parent and parent != 0 and parent in self._elements:
            self._elements[parent]["children"].append(eid)
        return eid

    def export(self) -> list[dict[str, Any]]:
        return [self._elements[i] for i in self._order]


def btn_primary(doc: Doc, eid: str | None, parent: str, text: str, url: str) -> str:
    return doc.add(
        eid,
        "button",
        parent,
        {
            "text": text,
            "link": link_ext(url),
            "style": "primary",
            "_background": {"color": color("voltage")},
            "_typography": typo(
                color="bedrock",
                fontFamily={"raw": "var(--font-body)"},
                fontWeight="700",
                fontSize="15px",
            ),
            "_padding": pad("15px", "28px", "15px", "28px"),
            "_border": {"width": {"top": "0", "right": "0", "bottom": "0", "left": "0"}},
        },
        label=text[:32],
    )


def btn_outline(doc: Doc, eid: str | None, parent: str, text: str, url: str) -> str:
    return doc.add(
        eid,
        "button",
        parent,
        {
            "text": text,
            "link": link_ext(url),
            "style": "outline",
            "_background": {"color": {"raw": "transparent"}},
            "_border": {
                "width": {"top": "1.5", "right": "1.5", "bottom": "1.5", "left": "1.5"},
                "style": "solid",
                "color": color("voltage"),
            },
            "_typography": typo(
                color="voltage",
                fontFamily={"raw": "var(--font-body)"},
                fontWeight="600",
                fontSize="15px",
            ),
            "_padding": pad("15px", "28px", "15px", "28px"),
        },
        label=text[:32],
    )


def section_band(
    doc: Doc,
    sid: str | None,
    cid: str | None,
    bg_var: str,
    padding_y: str = "var(--space-section)",
) -> tuple[str, str]:
    sid = doc.add(
        sid,
        "section",
        0,
        {
            "_background": {"color": color(bg_var)},
            "_padding": pad(padding_y, "0", padding_y, "0"),
        },
    )
    cid = doc.add(
        cid,
        "container",
        sid,
        {
            "_maxWidth": {"raw": "var(--wrap)"},
            "_width": "100%",
            "_margin": pad("0", "auto", "0", "auto"),
            "_padding": pad("0", "var(--space-xl)", "0", "var(--space-xl)"),
        },
    )
    return sid, cid


def eyebrow(doc: Doc, parent: str, text: str, color_var: str = "voltage") -> str:
    return doc.add(
        None,
        "text-basic",
        parent,
        {
            "text": text,
            "_typography": typo(
                color=color_var,
                fontFamily={"raw": "var(--font-body)"},
                fontSize="12px",
                fontWeight="600",
                letterSpacing="0.08em",
                textTransform="uppercase",
            ),
            "_margin": pad("0", "0", "10px", "0"),
        },
    )


def h_display(
    doc: Doc,
    parent: str,
    tag: str,
    text: str,
    size: str = "56px",
    color_var: str = "signal",
    mb: str = "0",
    css_custom: str | None = None,
) -> str:
    settings: dict[str, Any] = {
        "tag": tag,
        "text": text,
        "_typography": typo(
            color=color_var,
            fontFamily={"raw": "var(--font-display)"},
            fontSize=size,
            lineHeight="0.9",
            letterSpacing="0.02em",
        ),
        "_margin": pad("0", "0", mb, "0"),
    }
    if css_custom:
        settings["_cssCustom"] = css_custom
    return doc.add(None, "heading", parent, settings)


def body_text(doc: Doc, parent: str, text: str, color_var: str = "text-muted-inverse", size: str = "17px") -> str:
    return doc.add(
        None,
        "text-basic",
        parent,
        {
            "text": text,
            "_typography": typo(
                color=color_var,
                fontFamily={"raw": "var(--font-body)"},
                fontSize=size,
                lineHeight="1.6",
            ),
        },
    )


def nav_link(doc: Doc, parent: str, text: str, url: str) -> str:
    return doc.add(
        None,
        "text-basic",
        parent,
        {
            "text": text,
            "link": link_ext(url),
            "_typography": typo(
                color="text-muted-inverse",
                fontFamily={"raw": "var(--font-body)"},
                fontSize="14px",
                fontWeight="500",
            ),
        },
    )


def vimeo_video(
    doc: Doc,
    parent: str,
    vimeo_id: str,
    autoplay: bool = False,
) -> str:
    settings: dict[str, Any] = {
        "source": "vimeo",
        "vimeoId": vimeo_id,
        "aspectRatio": "9:16",
        "_width": "100%",
        "_height": "100%",
    }
    if autoplay:
        settings["autoplay"] = True
        settings["loop"] = True
        settings["muted"] = True
        settings["controls"] = False
    return doc.add(None, "video", parent, settings, label=f"Vimeo {vimeo_id}")


def write_json(name: str, elements: list[dict[str, Any]]) -> None:
    path = OUT_DIR / name
    path.write_text(json.dumps(elements, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")


# --- header ------------------------------------------------------------------

def build_header() -> list[dict[str, Any]]:
    doc = Doc()
    sec = doc.add(
        None,
        "section",
        0,
        {
            "tag": "header",
            "_position": "sticky",
            "_top": "0",
            "_zIndex": "40",
            "_background": {"color": color("bedrock")},
            "_border": {
                "width": {"top": "0", "right": "0", "bottom": "1", "left": "0"},
                "style": "solid",
                "color": color("border-inverse"),
            },
        },
        label="Header",
    )
    wrap = doc.add(
        None,
        "container",
        sec,
        {
            "_maxWidth": {"raw": "var(--wrap)"},
            "_width": "100%",
            "_margin": pad("0", "auto", "0", "auto"),
            "_padding": pad("14px", "var(--space-xl)", "14px", "var(--space-xl)"),
        },
    )
    row = doc.add(
        None,
        "div",
        wrap,
        {
            "_display": "flex",
            "_direction": "row",
            "_alignItems": "center",
            "_gap": "28px",
            "_flexWrap": "wrap",
        },
        label="Header row",
    )

    logo = doc.add(
        None,
        "div",
        row,
        {
            "_display": "flex",
            "_direction": "row",
            "_alignItems": "center",
            "_gap": "11px",
            "link": link_ext("/"),
        },
        label="Logo",
    )
    doc.add(
        None,
        "image",
        logo,
        {
            **image_settings(LOGO_VOLTAGE, "Velocity"),
            "_height": "30px",
            "_width": "auto",
        },
    )
    doc.add(
        None,
        "text-basic",
        logo,
        {
            "text": "VELOCITY",
            "_typography": typo(
                color="signal",
                fontFamily={"raw": "var(--font-display)"},
                fontSize="26px",
                letterSpacing="0.06em",
            ),
        },
    )

    nav = doc.add(
        None,
        "div",
        row,
        {
            "_display": "flex",
            "_direction": "row",
            "_alignItems": "center",
            "_gap": "26px",
            "_margin": pad("0", "0", "0", "32px"),
        },
        label="Nav",
    )
    nav_link(doc, nav, "Home", "/")
    nav_link(doc, nav, "Služby", "/sluzby/")
    nav_link(doc, nav, "Reference", "/reference/")
    nav_link(doc, nav, "O nás", "/onas/")

    cta = doc.add(
        None,
        "div",
        row,
        {
            "_display": "flex",
            "_direction": "row",
            "_alignItems": "center",
            "_gap": "10px",
            "_margin": pad("0", "0", "0", "auto"),
        },
        label="Header CTAs",
    )
    doc.add(
        None,
        "button",
        cta,
        {
            "text": PHONE_DISPLAY,
            "link": link_ext(f"tel:{PHONE}"),
            "_background": {"color": {"raw": "transparent"}},
            "_border": {
                "width": {"top": "1.5", "right": "1.5", "bottom": "1.5", "left": "1.5"},
                "style": "solid",
                "color": color("voltage"),
            },
            "_typography": typo(
                color="voltage",
                fontFamily={"raw": "var(--font-display)"},
                fontSize="20px",
                fontWeight="600",
                letterSpacing="0.02em",
            ),
            "_padding": pad("11px", "18px", "11px", "18px"),
        },
        label="Phone CTA",
    )
    btn_primary(doc, None, cta, "Zjistěte, jestli jsme pro vás", "/kontakt/")

    return doc.export()


# --- footer ------------------------------------------------------------------

def build_footer() -> list[dict[str, Any]]:
    doc = Doc()
    sec = doc.add(
        None,
        "section",
        0,
        {
            "tag": "footer",
            "_background": {"color": color("bedrock")},
            "_border": {
                "width": {"top": "1", "right": "0", "bottom": "0", "left": "0"},
                "style": "solid",
                "color": color("border-inverse"),
            },
            "_padding": pad("64px", "0", "40px", "0"),
        },
        label="Footer",
    )
    wrap = doc.add(
        None,
        "container",
        sec,
        {
            "_maxWidth": {"raw": "var(--wrap)"},
            "_width": "100%",
            "_margin": pad("0", "auto", "0", "auto"),
            "_padding": pad("0", "var(--space-xl)", "0", "var(--space-xl)"),
        },
    )

    cta_row = doc.add(
        None,
        "div",
        wrap,
        {
            "_display": "grid",
            "_gridTemplateColumns": "1.4fr 1fr",
            "_alignItems": "center",
            "_gap": "40px",
            "_padding": pad("0", "0", "40px", "0"),
            "_border": {
                "width": {"top": "0", "right": "0", "bottom": "1", "left": "0"},
                "style": "solid",
                "color": color("border-inverse"),
            },
            "_cssCustom": mobile_split_1col(),
        },
        label="Footer CTA band",
    )
    cta_txt = doc.add(None, "div", cta_row, {})
    h_display(doc, cta_txt, "h3", "ZJISTĚTE, JESTLI JSME PRO VÁS", "44px", "signal", "10px")
    body_text(
        doc,
        cta_txt,
        "Sedm otázek. Podle odpovědí poznáte, jestli dává spolupráce smysl — a my se vám ozveme.",
        "text-muted-inverse",
        "15px",
    )
    cta_btns = doc.add(
        None,
        "div",
        cta_row,
        {"_display": "flex", "_direction": "column", "_gap": "12px"},
    )
    btn_primary(doc, None, cta_btns, "Vyplnit dotazník", "/kontakt/")
    btn_outline(doc, None, cta_btns, PHONE_DISPLAY, f"tel:{PHONE}")

    bottom = doc.add(
        None,
        "div",
        wrap,
        {
            "_display": "flex",
            "_direction": "row",
            "_justifyContent": "space-between",
            "_alignItems": "center",
            "_gap": "16px",
            "_flexWrap": "wrap",
            "_padding": pad("28px", "0", "0", "0"),
        },
        label="Footer bottom",
    )
    logo = doc.add(
        None,
        "div",
        bottom,
        {"_display": "flex", "_direction": "row", "_alignItems": "center", "_gap": "11px"},
    )
    doc.add(None, "image", logo, {**image_settings(LOGO_WHITE, "Velocity"), "_height": "26px"})
    doc.add(
        None,
        "text-basic",
        logo,
        {
            "text": "VELOCITY",
            "_typography": typo(
                color="signal",
                fontFamily={"raw": "var(--font-display)"},
                fontSize="22px",
                letterSpacing="0.06em",
            ),
        },
    )
    doc.add(
        None,
        "text-basic",
        bottom,
        {
            "text": "© 2026 Velocity — marketingová & kreativní agentura",
            "_typography": typo(color="text-muted-inverse", fontSize="13px"),
        },
    )
    return doc.export()


# --- home --------------------------------------------------------------------

def build_home() -> list[dict[str, Any]]:
    doc = Doc()

    # Hero
    hero_sec = doc.add(
        None,
        "section",
        0,
        {
            "_background": {"color": color("bedrock")},
            "_padding": pad("var(--space-hero)", "0", "var(--space-hero)", "0"),
            "_overflow": "hidden",
        },
        label="Hero",
    )
    hero_wrap = doc.add(
        None,
        "container",
        hero_sec,
        {
            "_maxWidth": {"raw": "var(--wrap)"},
            "_width": "100%",
            "_margin": pad("0", "auto", "0", "auto"),
            "_padding": pad("0", "var(--space-xl)", "0", "var(--space-xl)"),
        },
    )
    hero_split = doc.add(
        None,
        "div",
        hero_wrap,
        {
            "_display": "grid",
            "_gridTemplateColumns": "1.05fr 0.95fr",
            "_alignItems": "center",
            "_gap": "64px",
            "_cssCustom": mobile_split_1col(),
        },
    )
    hero_left = doc.add(None, "div", hero_split, {})
    badges = doc.add(
        None,
        "div",
        hero_left,
        {"_display": "flex", "_direction": "row", "_gap": "8px", "_margin": pad("0", "0", "22px", "0")},
    )
    doc.add(
        None,
        "text-basic",
        badges,
        {
            "text": "Gen Z marketing",
            "_background": {"color": color("voltage")},
            "_padding": pad("6px", "11px", "6px", "11px"),
            "_typography": typo(
                color="bedrock",
                fontSize="12px",
                fontWeight="600",
                letterSpacing="0.08em",
                textTransform="uppercase",
            ),
        },
    )
    doc.add(
        None,
        "text-basic",
        badges,
        {
            "text": "Kreativní agentura",
            "_border": {
                "width": {"top": "1", "right": "1", "bottom": "1", "left": "1"},
                "style": "solid",
                "color": color("border-inverse"),
            },
            "_padding": pad("6px", "11px", "6px", "11px"),
            "_typography": typo(
                color="signal",
                fontSize="12px",
                fontWeight="600",
                letterSpacing="0.08em",
                textTransform="uppercase",
            ),
        },
    )
    h_display(
        doc,
        hero_left,
        "h1",
        "JAK PRODAT GENERACI, KTERÁ NEKUPUJE OD",
        "96px",
        "signal",
        "0",
        mobile_h1(),
    )
    h_display(doc, hero_left, "h2", "NUDNÝCH ZNAČEK.", "96px", "voltage", "0", mobile_h1())
    body_text(
        doc,
        hero_left,
        "Stavíme obsah pro generaci Z, který skutečně funguje — a dokážeme to čísly. "
        "Žádný náhodný obsah. Vše stojí na výzkumu a strategii.",
    )
    hero_btns = doc.add(
        None,
        "div",
        hero_left,
        {
            "_display": "flex",
            "_direction": "row",
            "_gap": "12px",
            "_flexWrap": "wrap",
            "_margin": pad("30px", "0", "0", "0"),
        },
    )
    btn_primary(doc, None, hero_btns, "Zjistěte, jestli jsme pro vás", "/kontakt/")
    btn_outline(doc, None, hero_btns, "Naše výsledky", "/reference/")
    hero_right = doc.add(None, "div", hero_split, {"_display": "flex", "_justifyContent": "center"})
    doc.add(
        None,
        "image",
        hero_right,
        {
            **image_settings(CHROME_V, "Velocity"),
            "_width": "100%",
            "_maxWidth": "360px",
        },
    )

    # Reels
    reels_sec = doc.add(
        None,
        "section",
        0,
        {
            "_background": {"color": color("bedrock")},
            "_border": {
                "width": {"top": "1", "right": "0", "bottom": "0", "left": "0"},
                "style": "solid",
                "color": color("border-inverse"),
            },
        },
        label="Reels",
    )
    reels_c = doc.add(
        None,
        "container",
        reels_sec,
        {
            "_maxWidth": {"raw": "var(--wrap)"},
            "_width": "100%",
            "_margin": pad("0", "auto", "0", "auto"),
            "_padding": pad("72px", "var(--space-xl)", "8px", "var(--space-xl)"),
        },
    )
    reels_intro = doc.add(
        None,
        "div",
        reels_c,
        {
            "_display": "flex",
            "_justifyContent": "space-between",
            "_alignItems": "flex-end",
            "_gap": "20px",
            "_flexWrap": "wrap",
        },
    )
    intro_left = doc.add(None, "div", reels_intro, {})
    eyebrow(doc, intro_left, "Naše práce")
    h_display(doc, intro_left, "h2", "REELS, KTERÉ LIDÉ DOKOUKAJÍ", css_custom=mobile_h2())
    doc.add(
        None,
        "text-basic",
        reels_intro,
        {
            "text": "Reels pro e-commerce značky. Klikněte a pusťte se zvukem — tak jak je vidí generace Z ve feedu.",
            "_typography": typo(color="text-muted-inverse", fontSize="15px", lineHeight="1.55"),
            "_maxWidth": "320px",
        },
    )

    scroll = doc.add(
        None,
        "div",
        reels_sec,
        {
            "_display": "flex",
            "_direction": "row",
            "_gap": "16px",
            "_overflow": "auto",
            "_padding": pad("0", "var(--space-xl)", "40px", "var(--space-xl)"),
        },
        label="Reels scroll",
    )
    reels_data = [
        ("1212116515", "FM Tennis", True),
        ("1212120384", "Doctor Optic", False),
        ("1212180498", "Speed Comp", False),
        ("1212119129", "FM Tennis", False),
        ("1212120267", "Doctor Optic", False),
    ]
    for vid, brand, autoplay in reels_data:
        card = doc.add(None, "div", scroll, {"_width": "246px", "_flexShrink": "0"})
        frame = doc.add(
            None,
            "div",
            card,
            {
                "_width": "100%",
                "_aspectRatio": "9/16",
                "_background": {"color": color("bedrock-800")},
                "_border": {
                    "width": {"top": "1", "right": "1", "bottom": "1", "left": "1"},
                    "style": "solid",
                    "color": color("border-inverse"),
                },
                "_overflow": "hidden",
                "_position": "relative",
            },
        )
        vimeo_video(doc, frame, vid, autoplay)
        cap = doc.add(
            None,
            "div",
            card,
            {
                "_display": "flex",
                "_justifyContent": "space-between",
                "_alignItems": "center",
                "_margin": pad("10px", "0", "0", "0"),
            },
        )
        doc.add(
            None,
            "text-basic",
            cap,
            {
                "text": brand,
                "_typography": typo(
                    color="signal",
                    fontFamily={"raw": "var(--font-display)"},
                    fontSize="18px",
                    letterSpacing="0.03em",
                ),
            },
        )
        doc.add(
            None,
            "text-basic",
            cap,
            {"text": "Reel", "_typography": typo(color="text-muted-inverse", fontSize="12px")},
        )

    # Principles
    _, pr_c = section_band(doc, None, None, "signal")
    eyebrow(doc, pr_c, "Jak pracujeme", "text-muted")
    h_display(doc, pr_c, "h2", "VÍME, CO JE NAŠE — A CO PODLE NÁS NEFUNGUJE", css_custom=mobile_h2())
    pr_grid = doc.add(
        None,
        "div",
        pr_c,
        {
            "_display": "grid",
            "_gridTemplateColumns": "1fr 1fr",
            "_gap": "24px",
            "_margin": pad("40px", "0", "0", "0"),
            "_cssCustom": mobile_split_1col(),
        },
    )
    do_list = [
        "Diagnostikujeme obsah přes Gen Z skóre — měřitelně, ne od oka.",
        "Stavíme obsahovou strategii na deep research o vás i konkurenci.",
        "Píšeme scénáře, briefy a produkujeme ~20 kusů obsahu měsíčně.",
        "Točíme high-production a vedeme vás u low-production natáčení.",
        "Reportujeme čísla a pracujeme naostro s vaším marketing týmem.",
    ]
    dont_list = [
        "Neděláme jednorázové zakázky bez návaznosti.",
        "Nepracujeme bez strategie jako základu.",
        "Neděláme obsah pro klienty bez přístupu k datům.",
        "Nekopírujeme obsah napříč platformami — to Gen Z pozná.",
        "Neřešíme za vás postování — social management neděláme.",
    ]
    _principles_col(doc, pr_grid, "CO DĚLÁME", do_list, dark=True)
    _principles_col(doc, pr_grid, "CO NEDĚLÁME", dont_list, dark=False)

    # Services teaser
    _, svc_c = section_band(doc, None, None, "white")
    svc_head = doc.add(
        None,
        "div",
        svc_c,
        {
            "_display": "flex",
            "_justifyContent": "space-between",
            "_alignItems": "flex-end",
            "_gap": "20px",
            "_flexWrap": "wrap",
            "_margin": pad("0", "0", "44px", "0"),
        },
    )
    svc_t = doc.add(None, "div", svc_head, {})
    eyebrow(doc, svc_t, "Co děláme", "text-muted")
    h_display(doc, svc_t, "h2", "TŘI KROKY K OBSAHU, KTERÝ PRODÁVÁ", css_custom=mobile_h2())
    btn_outline(doc, None, svc_head, "Všechny služby →", "/sluzby/")
    svc_grid = doc.add(
        None,
        "div",
        svc_c,
        {
            "_display": "grid",
            "_gridTemplateColumns": "repeat(3, 1fr)",
            "_gap": "24px",
            "_cssCustom": mobile_grid_1col(),
        },
    )
    services = [
        ("Krok 1", "Gen Z Audit", "Diagnostika vašeho obsahu. Výstupem je Gen Z skóre a jasný akční plán, kam se pustit první.", "Výstup: Gen Z skóre"),
        ("Krok 2", "Obsahová strategie", "Deep research na odvětví, vás a konkurenci. Témata, formáty a funnel. Staví na auditu.", "Výstup: strategie + plán"),
        ("Krok 3", "Core Offer", "~20 kusů obsahu měsíčně — short-form videa, statiky, grafiky, carousely, AI images.", "Výstup: obsah každý měsíc"),
    ]
    for step, title, desc, outcome in services:
        _service_card(doc, svc_grid, step, title, desc, outcome)

    # Stats
    _, st_c = section_band(doc, None, None, "bedrock", "80px")
    eyebrow(doc, st_c, "Doctor Optic · Instagram organic")
    h_display(doc, st_c, "h2", "ČÍSLA, NE POCITY", css_custom=mobile_h2())
    stats = [
        ("750K", "zhlédnutí z 16 videí"),
        ("8 %", "průměrný engagement rate"),
        ("360K", "views + 20K likes nejsilnější video"),
        ("+1 500", "nových followerů"),
    ]
    st_grid = doc.add(
        None,
        "div",
        st_c,
        {
            "_display": "grid",
            "_gridTemplateColumns": "repeat(4, 1fr)",
            "_gap": "20px",
            "_margin": pad("44px", "0", "0", "0"),
            "_cssCustom": mobile_grid_1col(),
        },
    )
    for big, label in stats:
        _stat_cell(doc, st_grid, big, label)

    # Reviews
    _, rv_c = section_band(doc, None, None, "signal")
    eyebrow(doc, rv_c, "Co říkají klienti", "text-muted")
    h_display(doc, rv_c, "h2", "SPOLUPRÁCE, KTERÁ DÁVÁ SMYSL", "56px", "text-body", "44px", mobile_h2())
    reviews = [
        ("Poprvé jsme u obsahu věděli, proč funguje. Ne pocit, ale číslo a plán — a čísla šla nahoru hned první měsíc.", "Marketingová ředitelka", "Fashion e-shop"),
        ("Nejdřív mě štvalo, že bez strategie nic neudělají. Pak jsem pochopil, že přesně tohle nám celou dobu chybělo.", "Zakladatel", "Beauty značka"),
        ("Reels od Velocity poznám ve feedu na první pohled. A hlavně je lidi dokoukají — což se o naší dřívější agentuře říct nedalo.", "Brand manažer", "Sportovní výživa"),
    ]
    rv_grid = doc.add(
        None,
        "div",
        rv_c,
        {
            "_display": "grid",
            "_gridTemplateColumns": "repeat(3, 1fr)",
            "_gap": "24px",
            "_cssCustom": mobile_grid_1col(),
        },
    )
    for quote, name, role in reviews:
        _review_card(doc, rv_grid, quote, name, role)

    return doc.export()


def _principles_col(doc: Doc, parent: str, title: str, lines: list[str], dark: bool) -> None:
    bg = "bedrock" if dark else "white"
    col = doc.add(
        None,
        "div",
        parent,
        {
            "_background": {"color": color(bg)},
            "_padding": pad("36px", "36px", "36px", "36px"),
            "_borderRadius": {"raw": "var(--radius-md)"},
        },
    )
    title_color = "voltage" if dark else "text-body"
    doc.add(
        None,
        "heading",
        col,
        {
            "tag": "h3",
            "text": title,
            "_typography": typo(
                color=title_color,
                fontFamily={"raw": "var(--font-display)"},
                fontSize="26px",
                letterSpacing="0.03em",
            ),
            "_margin": pad("0", "0", "22px", "0"),
        },
    )
    for line in lines:
        row = doc.add(
            None,
            "div",
            col,
            {
                "_display": "flex",
                "_direction": "row",
                "_gap": "14px",
                "_padding": pad("13px", "0", "13px", "0"),
                "_border": {
                    "width": {"top": "1", "right": "0", "bottom": "0", "left": "0"},
                    "style": "solid",
                    "color": color("border-inverse" if dark else "border-subtle"),
                },
            },
        )
        mark = "→" if dark else "×"
        mark_color = "voltage" if dark else "text-muted"
        doc.add(
            None,
            "text-basic",
            row,
            {
                "text": mark,
                "_typography": typo(
                    color=mark_color,
                    fontFamily={"raw": "var(--font-display)"} if dark else {"raw": "var(--font-body)"},
                    fontSize="20px",
                ),
            },
        )
        doc.add(
            None,
            "text-basic",
            row,
            {
                "text": line,
                "_typography": typo(
                    color="signal" if dark else "text-body",
                    fontSize="15px",
                    lineHeight="1.5",
                ),
            },
        )


def _service_card(doc: Doc, parent: str, step: str, title: str, desc: str, outcome: str) -> None:
    card = doc.add(
        None,
        "div",
        parent,
        {
            "_border": {
                "width": {"top": "1", "right": "1", "bottom": "1", "left": "1"},
                "style": "solid",
                "color": color("border-subtle"),
            },
            "_borderRadius": {"raw": "var(--radius-md)"},
            "_background": {"color": color("gray-200")},
            "_padding": pad("30px", "30px", "30px", "30px"),
            "_display": "flex",
            "_direction": "column",
            "_minHeight": "240px",
        },
    )
    doc.add(
        None,
        "text-basic",
        card,
        {
            "text": step,
            "_typography": typo(
                color="text-muted",
                fontSize="11px",
                fontWeight="700",
                letterSpacing="0.08em",
                textTransform="uppercase",
            ),
        },
    )
    doc.add(
        None,
        "heading",
        card,
        {
            "tag": "h3",
            "text": title,
            "_typography": typo(
                color="text-body",
                fontFamily={"raw": "var(--font-display)"},
                fontSize="28px",
                letterSpacing="0.02em",
            ),
            "_margin": pad("12px", "0", "10px", "0"),
        },
    )
    doc.add(
        None,
        "text-basic",
        card,
        {"text": desc, "_typography": typo(color="text-muted", fontSize="14px", lineHeight="1.55")},
    )
    doc.add(
        None,
        "text-basic",
        card,
        {
            "text": outcome,
            "_margin": pad("18px", "0", "0", "0"),
            "_typography": typo(
                color="voltage-deep",
                fontFamily={"raw": "var(--font-display)"},
                fontSize="17px",
                letterSpacing="0.03em",
            ),
        },
    )


def _stat_cell(doc: Doc, parent: str, big: str, label: str) -> None:
    cell = doc.add(
        None,
        "div",
        parent,
        {
            "_border": {
                "width": {"top": "2", "right": "0", "bottom": "0", "left": "0"},
                "style": "solid",
                "color": color("voltage"),
            },
            "_padding": pad("18px", "0", "0", "0"),
        },
    )
    doc.add(
        None,
        "heading",
        cell,
        {
            "tag": "div",
            "text": big,
            "_typography": typo(
                color="signal",
                fontFamily={"raw": "var(--font-display)"},
                fontSize="58px",
                lineHeight="0.9",
                letterSpacing="0.01em",
            ),
        },
    )
    doc.add(
        None,
        "text-basic",
        cell,
        {
            "text": label,
            "_margin": pad("8px", "0", "0", "0"),
            "_typography": typo(color="text-muted-inverse", fontSize="13px", lineHeight="1.5"),
        },
    )


def _review_card(doc: Doc, parent: str, quote: str, name: str, role: str) -> None:
    card = doc.add(
        None,
        "div",
        parent,
        {
            "_background": {"color": color("white")},
            "_border": {
                "width": {"top": "1", "right": "1", "bottom": "1", "left": "1"},
                "style": "solid",
                "color": color("border-subtle"),
            },
            "_borderRadius": {"raw": "var(--radius-md)"},
            "_padding": pad("30px", "30px", "30px", "30px"),
            "_display": "flex",
            "_direction": "column",
        },
    )
    doc.add(
        None,
        "text-basic",
        card,
        {
            "text": '"',
            "_typography": typo(
                color="voltage-deep",
                fontFamily={"raw": "var(--font-display)"},
                fontSize="30px",
                lineHeight="0.6",
            ),
        },
    )
    doc.add(
        None,
        "text-basic",
        card,
        {
            "text": quote,
            "_margin": pad("0", "0", "22px", "0"),
            "_typography": typo(color="text-body", fontSize="15px", lineHeight="1.6"),
        },
    )
    doc.add(
        None,
        "text-basic",
        card,
        {
            "text": name,
            "_typography": typo(color="text-body", fontSize="14px", fontWeight="700"),
        },
    )
    doc.add(
        None,
        "text-basic",
        card,
        {"text": role, "_typography": typo(color="text-muted", fontSize="13px")},
    )


# --- sluzby ------------------------------------------------------------------

def build_sluzby() -> list[dict[str, Any]]:
    doc = Doc()
    _, hero_c = section_band(doc, None, None, "bedrock", "84px")
    eyebrow(doc, hero_c, "Služby")
    h_display(
        doc,
        hero_c,
        "h1",
        "TŘI KROKY. ŽÁDNÝ OBSAH BEZ STRATEGIE.",
        "76px",
        css_custom=mobile_h1(),
    )
    body_text(
        doc,
        hero_c,
        "Postupujeme vždy ve stejném pořadí. Nejdřív měříme, pak stavíme strategii, teprve potom tvoříme. "
        "Přeskočit kroky nejde — a to je záměr.",
    )

    _, list_c = section_band(doc, None, None, "white", "72px")
    services_full = [
        ("01", "Gen Z Audit", "Kde jste teď", "/audit/", "Konec dohadů o „autentičnosti“. Dostanete jedno číslo a jasný seznam, kde začít.",
         "Změříme potenciál vašeho obsahu u generace Z. Váhované metriky z 50+ výzkumných zdrojů dají jedno číslo — Gen Z skóre — a seznam oblastí, na které se vyplatí zaměřit jako první.",
         ["Gen Z skóre", "Akční plán priorit", "Bez přístupu do analytik"]),
        ("02", "Obsahová strategie", "Kam jdeme", "/strategie/", "Bez strategie nic dalšího neděláme. Je to základ všeho, co pak vyprodukujeme.",
         "Deep research na vaše odvětví, značku a konkurenci. Z toho postavíme témata, formáty a funnel pro TikTok, Instagram i Meta Ads.",
         ["Research odvětví", "Témata & formáty", "Content plán"]),
        ("03", "Core Offer", "Jak to děláme", "/core/", "Scénáře, briefy a editing jsou na nás. Vy postujete a sledujete čísla.",
         "Zhruba 20 kusů obsahu měsíčně: short-form videa, statiky, grafiky, carousely a AI images.",
         ["~20 kusů / měsíc", "High + low production", "Měsíční reporty"]),
    ]
    for num, title, step, href, _tagline, long, points in services_full:
        _service_row(doc, list_c, num, title, step, href, long, points)

    _, pipe_c = section_band(doc, None, None, "bedrock", "72px")
    h_display(doc, pipe_c, "h2", "JAK PROBÍHÁ SPOLUPRÁCE", css_custom=mobile_h2())
    pipeline = [
        ("01", "Dotazník", "Sesbíráme kontext o značce, cílech a datech."),
        ("02", "Research", "Odvětví, klient, konkurence — do hloubky."),
        ("03", "Strategie", "Témata, formáty a funnel. Content plán a kalendář."),
        ("04", "Produkce", "Brief → scénáře → natáčecí den → editing → kontrola."),
        ("05", "Report", "Čísla, progress a další krok. Každý měsíc."),
    ]
    pipe_grid = doc.add(
        None,
        "div",
        pipe_c,
        {
            "_display": "grid",
            "_gridTemplateColumns": "repeat(5, 1fr)",
            "_gap": "20px",
            "_margin": pad("40px", "0", "0", "0"),
            "_cssCustom": mobile_grid_1col(),
        },
    )
    for k, t, d in pipeline:
        cell = doc.add(
            None,
            "div",
            pipe_grid,
            {
                "_border": {
                    "width": {"top": "2", "right": "0", "bottom": "0", "left": "0"},
                    "style": "solid",
                    "color": color("voltage"),
                },
                "_padding": pad("16px", "0", "0", "0"),
            },
        )
        doc.add(
            None,
            "text-basic",
            cell,
            {
                "text": k,
                "_typography": typo(
                    color="voltage",
                    fontFamily={"raw": "var(--font-display)"},
                    fontSize="22px",
                    letterSpacing="0.02em",
                ),
            },
        )
        doc.add(
            None,
            "heading",
            cell,
            {
                "tag": "h3",
                "text": t,
                "_typography": typo(
                    color="signal",
                    fontFamily={"raw": "var(--font-display)"},
                    fontSize="21px",
                    letterSpacing="0.02em",
                ),
                "_margin": pad("8px", "0", "8px", "0"),
            },
        )
        doc.add(
            None,
            "text-basic",
            cell,
            {"text": d, "_typography": typo(color="text-muted-inverse", fontSize="13px", lineHeight="1.5")},
        )

    return doc.export()


def _service_row(
    doc: Doc,
    parent: str,
    num: str,
    title: str,
    step: str,
    href: str,
    long: str,
    points: list[str],
) -> None:
    row = doc.add(
        None,
        "block",
        parent,
        {
            "link": link_ext(href),
            "_border": {
                "width": {"top": "1", "right": "1", "bottom": "1", "left": "1"},
                "style": "solid",
                "color": color("border-subtle"),
            },
            "_borderRadius": {"raw": "var(--radius-md)"},
            "_background": {"color": color("white")},
            "_padding": pad("40px", "40px", "40px", "40px"),
            "_display": "grid",
            "_gridTemplateColumns": "120px 1fr",
            "_gap": "32px",
            "_margin": pad("0", "0", "28px", "0"),
            "_cssCustom": "@media (max-width: 900px) { grid-template-columns: 1fr !important; gap: 12px !important; padding: 28px !important; }",
        },
    )
    doc.add(
        None,
        "heading",
        row,
        {
            "tag": "div",
            "text": num,
            "_typography": typo(
                color="voltage-deep",
                fontFamily={"raw": "var(--font-display)"},
                fontSize="84px",
                lineHeight="0.8",
            ),
            "_cssCustom": "@media (max-width: 900px) { font-size: 52px !important; }",
        },
    )
    body = doc.add(None, "div", row, {})
    doc.add(
        None,
        "heading",
        body,
        {
            "tag": "h2",
            "text": title,
            "_typography": typo(
                color="text-body",
                fontFamily={"raw": "var(--font-display)"},
                fontSize="40px",
                letterSpacing="0.02em",
            ),
        },
    )
    doc.add(
        None,
        "text-basic",
        body,
        {
            "text": step,
            "_margin": pad("0", "0", "18px", "0"),
            "_typography": typo(
                color="text-muted",
                fontSize="12px",
                fontWeight="700",
                letterSpacing="0.06em",
                textTransform="uppercase",
            ),
        },
    )
    body_text(doc, body, long, "text-body", "16px")
    tags = doc.add(
        None,
        "div",
        body,
        {"_display": "flex", "_direction": "row", "_gap": "10px", "_flexWrap": "wrap", "_margin": pad("20px", "0", "0", "0")},
    )
    for p in points:
        doc.add(
            None,
            "text-basic",
            tags,
            {
                "text": p,
                "_background": {"color": color("bedrock")},
                "_padding": pad("7px", "13px", "7px", "13px"),
                "_borderRadius": {"raw": "var(--radius-pill)"},
                "_typography": typo(color="signal", fontSize="13px"),
            },
        )


# --- service detail ----------------------------------------------------------

SERVICES = {
    "audit": {
        "num": "01",
        "step": "Kde jste teď",
        "title": "Gen Z Audit",
        "tagline": "Konec dohadů o „autentičnosti“. Dostanete jedno číslo a jasný seznam, kde začít.",
        "h2": "ZMĚŘÍME, KDE OBSAH ZTRÁCÍ POZORNOST",
        "long": "Změříme potenciál vašeho obsahu u generace Z. Váhované metriky z 50+ výzkumných zdrojů dají jedno číslo — Gen Z skóre — a seznam oblastí, na které se vyplatí zaměřit jako první. Funguje pro organiku i pro Meta Ads.",
        "points": ["Gen Z skóre", "Akční plán priorit", "Bez přístupu do analytik"],
        "benefits": [
            ("Gen Z skóre", "Jedno srozumitelné číslo, které říká, jak váš obsah rezonuje u generace Z."),
            ("Priority", "Seznam oblastí, které mají největší dopad — víte přesně, kde začít."),
            ("Rychlý výstup", "Diagnostiku zvládneme i bez přístupu do vašich analytik."),
        ],
        "others": [("02", "Kam jdeme", "Obsahová strategie", "/strategie/"), ("03", "Jak to děláme", "Core Offer", "/core/")],
    },
    "strategie": {
        "num": "02",
        "step": "Kam jdeme",
        "title": "Obsahová strategie",
        "tagline": "Bez strategie nic dalšího neděláme. Je to základ všeho, co pak vyprodukujeme.",
        "h2": "STRATEGIE, ZE KTERÉ VZNIKÁ KAŽDÝ KUS OBSAHU",
        "long": "Deep research na vaše odvětví, značku a konkurenci. Z toho postavíme témata, formáty a funnel pro TikTok, Instagram i Meta Ads. Dostanete content plán, se kterým ví celý tým, co, kdy a proč tvořit.",
        "points": ["Research odvětví", "Témata & formáty", "Content plán"],
        "benefits": [
            ("Deep research", "Odvětví, značka a konkurence do hloubky — víme, s čím soupeříte."),
            ("Témata & funnel", "Formáty a témata napříč platformami, poskládané do funnelu."),
            ("Content plán", "Konkrétní kalendář, podle kterého se dá rovnou tvořit."),
        ],
        "others": [("01", "Kde jste teď", "Gen Z Audit", "/audit/"), ("03", "Jak to děláme", "Core Offer", "/core/")],
    },
    "core": {
        "num": "03",
        "step": "Jak to děláme",
        "title": "Core Offer",
        "tagline": "Scénáře, briefy a editing jsou na nás. Vy postujete a sledujete čísla.",
        "h2": "~20 KUSŮ OBSAHU MĚSÍČNĚ, KTERÝ PRODÁVÁ",
        "long": "Zhruba 20 kusů obsahu měsíčně: short-form videa (high i low production), statiky, grafiky, carousely a AI images. Vedeme vás u natáčení, které zvládnete sami, a high-production točíme celý my. Otevřeni i částečně performance-based spolupráci.",
        "points": ["~20 kusů / měsíc", "High + low production", "Měsíční reporty"],
        "benefits": [
            ("Kompletní produkce", "Videa, statiky, grafiky, carousely i AI images — celá měsíční várka."),
            ("Scénáře a briefy", "Píšeme scénáře, děláme briefy a editing. Vy jen natočíte a postnete."),
            ("Reporty", "Každý měsíc čísla, progress a jasný další krok."),
        ],
        "others": [("01", "Kde jste teď", "Gen Z Audit", "/audit/"), ("02", "Kam jdeme", "Obsahová strategie", "/strategie/")],
    },
}


def build_service_detail(key: str) -> list[dict[str, Any]]:
    svc = SERVICES[key]
    doc = Doc()

    hero_sec = doc.add(
        None,
        "section",
        0,
        {
            "_background": {"color": color("bedrock")},
            "_padding": pad("84px", "0", "64px", "0"),
            "_overflow": "hidden",
        },
    )
    hero_c = doc.add(
        None,
        "container",
        hero_sec,
        {
            "_maxWidth": {"raw": "var(--wrap)"},
            "_width": "100%",
            "_margin": pad("0", "auto", "0", "auto"),
            "_padding": pad("0", "var(--space-xl)", "0", "var(--space-xl)"),
        },
    )
    doc.add(
        None,
        "text-basic",
        hero_c,
        {
            "text": "← Všechny služby",
            "link": link_ext("/sluzby/"),
            "_margin": pad("0", "0", "20px", "0"),
            "_typography": typo(color="text-muted-inverse", fontSize="13px"),
        },
    )
    meta = doc.add(
        None,
        "div",
        hero_c,
        {"_display": "flex", "_alignItems": "center", "_gap": "16px", "_margin": pad("0", "0", "16px", "0")},
    )
    doc.add(
        None,
        "text-basic",
        meta,
        {
            "text": svc["num"],
            "_typography": typo(
                color="voltage",
                fontFamily={"raw": "var(--font-display)"},
                fontSize="30px",
            ),
        },
    )
    doc.add(
        None,
        "text-basic",
        meta,
        {
            "text": svc["step"],
            "_typography": typo(
                color="text-muted-inverse",
                fontSize="12px",
                fontWeight="700",
                letterSpacing="0.08em",
                textTransform="uppercase",
            ),
        },
    )
    h_display(doc, hero_c, "h1", svc["title"], "82px", css_custom=mobile_h1())
    body_text(doc, hero_c, svc["tagline"], "text-muted-inverse", "18px")
    btns = doc.add(
        None,
        "div",
        hero_c,
        {"_display": "flex", "_gap": "12px", "_flexWrap": "wrap", "_margin": pad("30px", "0", "0", "0")},
    )
    btn_primary(doc, None, btns, "Zjistěte, jestli jsme pro vás", "/kontakt/")
    btn_outline(doc, None, btns, PHONE_DISPLAY, f"tel:{PHONE}")

    _, about_c = section_band(doc, None, None, "white", "80px")
    split = doc.add(
        None,
        "div",
        about_c,
        {
            "_display": "grid",
            "_gridTemplateColumns": "1fr 1fr",
            "_gap": "40px",
            "_alignItems": "start",
            "_cssCustom": mobile_split_1col(),
        },
    )
    left = doc.add(None, "div", split, {})
    eyebrow(doc, left, "O co jde", "text-muted")
    h_display(doc, left, "h2", svc["h2"], css_custom=mobile_h2())
    right = doc.add(None, "div", split, {})
    body_text(doc, right, svc["long"], "text-body", "17px")
    tags = doc.add(
        None,
        "div",
        right,
        {"_display": "flex", "_flexWrap": "wrap", "_gap": "10px", "_margin": pad("22px", "0", "0", "0")},
    )
    for p in svc["points"]:
        doc.add(
            None,
            "text-basic",
            tags,
            {
                "text": p,
                "_background": {"color": color("bedrock")},
                "_padding": pad("7px", "13px", "7px", "13px"),
                "_borderRadius": {"raw": "var(--radius-pill)"},
                "_typography": typo(color="signal", fontSize="13px"),
            },
        )

    doc.add(
        None,
        "text-basic",
        about_c,
        {
            "text": "Co dostanete",
            "_margin": pad("40px", "0", "24px", "0"),
            "_typography": typo(
                color="text-muted",
                fontSize="12px",
                fontWeight="600",
                letterSpacing="0.08em",
                textTransform="uppercase",
            ),
        },
    )
    ben_grid = doc.add(
        None,
        "div",
        about_c,
        {
            "_display": "grid",
            "_gridTemplateColumns": "repeat(3, 1fr)",
            "_gap": "24px",
            "_cssCustom": mobile_grid_1col(),
        },
    )
    for t, d in svc["benefits"]:
        cell = doc.add(
            None,
            "div",
            ben_grid,
            {
                "_border": {
                    "width": {"top": "2", "right": "0", "bottom": "0", "left": "0"},
                    "style": "solid",
                    "color": color("bedrock"),
                },
                "_padding": pad("18px", "0", "0", "0"),
            },
        )
        doc.add(
            None,
            "heading",
            cell,
            {
                "tag": "h3",
                "text": t,
                "_typography": typo(
                    color="text-body",
                    fontFamily={"raw": "var(--font-display)"},
                    fontSize="24px",
                    letterSpacing="0.02em",
                ),
                "_margin": pad("0", "0", "8px", "0"),
            },
        )
        doc.add(
            None,
            "text-basic",
            cell,
            {"text": d, "_typography": typo(color="text-muted", fontSize="14px", lineHeight="1.55")},
        )

    _, other_c = section_band(doc, None, None, "signal", "64px")
    eyebrow(doc, other_c, "Další služby", "text-muted")
    other_grid = doc.add(
        None,
        "div",
        other_c,
        {
            "_display": "grid",
            "_gridTemplateColumns": "1fr 1fr",
            "_gap": "24px",
            "_margin": pad("24px", "0", "0", "0"),
            "_cssCustom": mobile_split_1col(),
        },
    )
    for num, step, title, href in svc["others"]:
        card = doc.add(
            None,
            "block",
            other_grid,
            {
                "link": link_ext(href),
                "_background": {"color": color("white")},
                "_border": {
                    "width": {"top": "1", "right": "1", "bottom": "1", "left": "1"},
                    "style": "solid",
                    "color": color("border-subtle"),
                },
                "_borderRadius": {"raw": "var(--radius-md)"},
                "_padding": pad("26px", "30px", "26px", "30px"),
            },
        )
        doc.add(
            None,
            "text-basic",
            card,
            {
                "text": f"{num} · {step}",
                "_typography": typo(
                    color="text-muted",
                    fontSize="11px",
                    fontWeight="700",
                    letterSpacing="0.06em",
                    textTransform="uppercase",
                ),
            },
        )
        doc.add(
            None,
            "heading",
            card,
            {
                "tag": "h3",
                "text": title,
                "_typography": typo(
                    color="text-body",
                    fontFamily={"raw": "var(--font-display)"},
                    fontSize="30px",
                    letterSpacing="0.02em",
                ),
            },
        )

    return doc.export()


# --- reference ---------------------------------------------------------------

def build_reference() -> list[dict[str, Any]]:
    doc = Doc()
    _, hero_c = section_band(doc, None, None, "bedrock", "84px")
    eyebrow(doc, hero_c, "Case study")
    h_display(
        doc,
        hero_c,
        "h1",
        "DOCTOR OPTIC:",
        "80px",
        css_custom=mobile_h1(),
    )
    h_display(doc, hero_c, "h2", "750 000 VIEWS Z 16 VIDEÍ", "80px", "voltage", css_custom=mobile_h1())
    body_text(
        doc,
        hero_c,
        "Organický obsah na Instagramu, bez placené reklamy a dokonce bez přístupu do analytik. "
        "Ukázka toho, co umí strategie postavená na Gen Z skóre.",
    )

    _, stats_c = section_band(doc, None, None, "bedrock", "40px")
    stats = [
        ("750K", "zhlédnutí z 16 videí"),
        ("8 %", "průměrný engagement rate"),
        ("360K", "views + 20K likes nejsilnější video"),
        ("+1 500", "nových followerů"),
    ]
    st_grid = doc.add(
        None,
        "div",
        stats_c,
        {
            "_display": "grid",
            "_gridTemplateColumns": "repeat(4, 1fr)",
            "_gap": "20px",
            "_cssCustom": mobile_grid_1col(),
        },
    )
    for big, label in stats:
        _stat_cell(doc, st_grid, big, label)

    reels_sec = doc.add(
        None,
        "section",
        0,
        {"_background": {"color": color("bedrock")}, "_padding": pad("0", "0", "24px", "0")},
    )
    reels_c = doc.add(
        None,
        "container",
        reels_sec,
        {
            "_maxWidth": {"raw": "var(--wrap)"},
            "_margin": pad("0", "auto", "0", "auto"),
            "_padding": pad("0", "var(--space-xl)", "0", "var(--space-xl)"),
        },
    )
    doc.add(
        None,
        "heading",
        reels_c,
        {
            "tag": "h2",
            "text": "VÝBĚR REELS",
            "_typography": typo(
                color="signal",
                fontFamily={"raw": "var(--font-display)"},
                fontSize="30px",
                letterSpacing="0.03em",
            ),
        },
    )
    scroll = doc.add(
        None,
        "div",
        reels_sec,
        {
            "_display": "flex",
            "_direction": "row",
            "_gap": "16px",
            "_overflow": "auto",
            "_padding": pad("0", "var(--space-xl)", "44px", "var(--space-xl)"),
        },
    )
    reels_data = [
        ("1212116515", "FM Tennis"),
        ("1212120384", "Doctor Optic"),
        ("1212180498", "Speed Comp"),
        ("1212119129", "FM Tennis"),
        ("1212120267", "Doctor Optic"),
    ]
    for vid, brand in reels_data:
        card = doc.add(None, "div", scroll, {"_width": "246px", "_flexShrink": "0"})
        frame = doc.add(
            None,
            "div",
            card,
            {
                "_aspectRatio": "9/16",
                "_background": {"color": color("bedrock-800")},
                "_border": {
                    "width": {"top": "1", "right": "1", "bottom": "1", "left": "1"},
                    "style": "solid",
                    "color": color("border-inverse"),
                },
                "_overflow": "hidden",
            },
        )
        vimeo_video(doc, frame, vid, vid == "1212116515")

    _, case_c = section_band(doc, None, None, "signal", "80px")
    case_split = doc.add(
        None,
        "div",
        case_c,
        {
            "_display": "grid",
            "_gridTemplateColumns": "1fr 1fr",
            "_gap": "40px",
            "_alignItems": "start",
            "_cssCustom": mobile_split_1col(),
        },
    )
    case_left = doc.add(None, "div", case_split, {})
    eyebrow(doc, case_left, "Jak jsme to udělali", "text-muted")
    h_display(doc, case_left, "h2", "STRATEGIE MÍSTO NÁHODY", css_custom=mobile_h2())
    case_right = doc.add(
        None,
        "div",
        case_split,
        {"_display": "flex", "_direction": "column", "_gap": "18px"},
    )
    case_steps = [
        ("Gen Z skóre napřed", "Změřili jsme, kde obsah ztrácí pozornost, a určili priority. Žádné střílení naslepo."),
        ("Hooky, které drží", "Postavili jsme scénáře na pattern interruptech a open loopech — divák nemá důvod odscrollovat."),
        ("Formát pro platformu", "Obsah nativní pro Instagram, ne kopie z jiné sítě. Přesně to, co Gen Z čeká ve feedu."),
    ]
    for t, d in case_steps:
        box = doc.add(
            None,
            "div",
            case_right,
            {
                "_background": {"color": color("white")},
                "_border": {
                    "width": {"top": "1", "right": "1", "bottom": "1", "left": "1"},
                    "style": "solid",
                    "color": color("border-subtle"),
                },
                "_borderRadius": {"raw": "var(--radius-md)"},
                "_padding": pad("24px", "24px", "24px", "24px"),
            },
        )
        doc.add(
            None,
            "heading",
            box,
            {
                "tag": "h3",
                "text": t,
                "_typography": typo(
                    color="text-body",
                    fontFamily={"raw": "var(--font-display)"},
                    fontSize="22px",
                    letterSpacing="0.02em",
                ),
                "_margin": pad("0", "0", "8px", "0"),
            },
        )
        doc.add(
            None,
            "text-basic",
            box,
            {"text": d, "_typography": typo(color="text-muted", fontSize="14px", lineHeight="1.55")},
        )

    return doc.export()


# --- onas --------------------------------------------------------------------

def build_onas() -> list[dict[str, Any]]:
    doc = Doc()
    _, hero_c = section_band(doc, None, None, "bedrock", "84px")
    eyebrow(doc, hero_c, "O nás")
    h_display(
        doc,
        hero_c,
        "h1",
        "MĚŘÍME AUTENTICITU. STAVÍME OBSAH. PRODÁVÁME.",
        "78px",
        css_custom=mobile_h1(),
    )
    body_text(
        doc,
        hero_c,
        "Velocity je marketingová a kreativní agentura zaměřená na Gen Z obsah pro e-commerce značky. "
        "Malý tým, který dělá strategii, produkci i čísla.",
    )

    team_sec = doc.add(
        None,
        "section",
        0,
        {
            "_background": {"color": color("bedrock")},
            "_border": {
                "width": {"top": "1", "right": "0", "bottom": "0", "left": "0"},
                "style": "solid",
                "color": color("border-inverse"),
            },
            "_padding": pad("64px", "0", "80px", "0"),
        },
    )
    team_c = doc.add(
        None,
        "container",
        team_sec,
        {
            "_maxWidth": {"raw": "var(--wrap)"},
            "_margin": pad("0", "auto", "0", "auto"),
            "_padding": pad("0", "var(--space-xl)", "0", "var(--space-xl)"),
        },
    )
    doc.add(
        None,
        "heading",
        team_c,
        {
            "tag": "h2",
            "text": "CORE TÝM",
            "_typography": typo(
                color="signal",
                fontFamily={"raw": "var(--font-display)"},
                fontSize="30px",
                letterSpacing="0.03em",
            ),
            "_margin": pad("0", "0", "32px", "0"),
        },
    )
    team = [
        ("Albert", "Levis", "Kreativní a marketingová strategie, šéf produkce, kameraman, grafik."),
        ("Matyáš", "Maty · Brunyys", "Sales, akvizice a Key Account Manager. Komunikace s klienty."),
        ("Daniel", "Dan · Vlčis", "Finance a projektový management."),
        ("Tomáš", "Tom", "IT, stavba systémů, web a automatizace."),
    ]
    team_grid = doc.add(
        None,
        "div",
        team_c,
        {
            "_display": "grid",
            "_gridTemplateColumns": "repeat(4, 1fr)",
            "_gap": "20px",
            "_cssCustom": mobile_grid_1col(),
        },
    )
    for i, (name, alias, role) in enumerate(team, 1):
        member = doc.add(None, "div", team_grid, {})
        doc.add(
            None,
            "div",
            member,
            {
                "_aspectRatio": "1",
                "_background": {
                    "image": {
                        "url": f"{BASE}/wp-content/uploads/2026/07/portrait-{i}.jpg",
                        "id": 0,
                    }
                },
                "_borderRadius": {"raw": "var(--radius-sm)"},
            },
        )
        doc.add(
            None,
            "text-basic",
            member,
            {
                "text": name,
                "_margin": pad("14px", "0", "0", "0"),
                "_typography": typo(
                    color="signal",
                    fontFamily={"raw": "var(--font-display)"},
                    fontSize="24px",
                    letterSpacing="0.02em",
                ),
            },
        )
        doc.add(
            None,
            "text-basic",
            member,
            {
                "text": alias,
                "_typography": typo(color="voltage", fontSize="12px"),
            },
        )
        doc.add(
            None,
            "text-basic",
            member,
            {"text": role, "_typography": typo(color="text-muted-inverse", fontSize="13px", lineHeight="1.5")},
        )

    _, pr_c = section_band(doc, None, None, "signal", "80px")
    h_display(doc, pr_c, "h2", "NA ČEM SI ZAKLÁDÁME", css_custom=mobile_h2())
    principles = [
        ("Měřitelnost", "Autenticitu neuvidíte v tabulce. Ale změřit se dá — a my ji měříme."),
        ("Strategie napřed", "Žádný náhodný obsah. Nejdřív víme proč, teprve pak tvoříme."),
        ("Ostrost", "Premium provedení, Gen Z energie. Žádný korporátní jazyk, žádná vata."),
    ]
    pr_grid = doc.add(
        None,
        "div",
        pr_c,
        {
            "_display": "grid",
            "_gridTemplateColumns": "repeat(3, 1fr)",
            "_gap": "24px",
            "_margin": pad("40px", "0", "0", "0"),
            "_cssCustom": mobile_grid_1col(),
        },
    )
    for t, d in principles:
        cell = doc.add(
            None,
            "div",
            pr_grid,
            {
                "_border": {
                    "width": {"top": "2", "right": "0", "bottom": "0", "left": "0"},
                    "style": "solid",
                    "color": color("bedrock"),
                },
                "_padding": pad("18px", "0", "0", "0"),
            },
        )
        doc.add(
            None,
            "heading",
            cell,
            {
                "tag": "h3",
                "text": t,
                "_typography": typo(
                    color="text-body",
                    fontFamily={"raw": "var(--font-display)"},
                    fontSize="24px",
                    letterSpacing="0.02em",
                ),
                "_margin": pad("0", "0", "8px", "0"),
            },
        )
        doc.add(
            None,
            "text-basic",
            cell,
            {"text": d, "_typography": typo(color="text-muted", fontSize="14px", lineHeight="1.55")},
        )

    return doc.export()


# --- kontakt -----------------------------------------------------------------

def build_kontakt() -> list[dict[str, Any]]:
    doc = Doc()
    _, c = section_band(doc, None, None, "bedrock", "var(--space-hero)")
    eyebrow(doc, c, "Kontakt")
    h_display(doc, c, "h1", "ZJISTĚTE, JESTLI JSME PRO VÁS", "76px", css_custom=mobile_h1())
    body_text(
        doc,
        c,
        "Krátký dotazník připravujeme — zatím nám napište nebo zavolejte. "
        "Projdeme vaši situaci a řekneme, jestli dává spolupráce smysl.",
        "text-muted-inverse",
    )
    btns = doc.add(
        None,
        "div",
        c,
        {"_display": "flex", "_direction": "column", "_gap": "12px", "_margin": pad("32px", "0", "0", "0"), "_maxWidth": "420px"},
    )
    btn_primary(doc, None, btns, "Vyplnit dotazník (brzy)", "/kontakt/")
    btn_outline(doc, None, btns, PHONE_DISPLAY, f"tel:{PHONE}")
    doc.add(
        None,
        "text-basic",
        c,
        {
            "text": "Dotazník se 7 otázkami doplníme brzy. Do té doby vám stačí telefon nebo e-mail.",
            "_margin": pad("24px", "0", "0", "0"),
            "_typography": typo(color="text-muted-inverse", fontSize="14px", lineHeight="1.55"),
        },
    )
    return doc.export()


# --- manifest & main ---------------------------------------------------------

MANIFEST = [
    ("header.json", "Sticky site header with logo, nav, phone and primary CTA"),
    ("footer.json", "Footer CTA band, white logo, copyright"),
    ("home.json", "Home page: hero, reels, principles, services, stats, reviews"),
    ("sluzby.json", "Services overview with pipeline steps"),
    ("audit.json", "Gen Z Audit service detail page"),
    ("strategie.json", "Content strategy service detail page"),
    ("core.json", "Core Offer service detail page"),
    ("reference.json", "Doctor Optic case study and reels"),
    ("onas.json", "About page with team and principles"),
    ("kontakt.json", "Lead CTA page with phone; questionnaire placeholder"),
]


def main() -> None:
    builders = {
        "header.json": build_header,
        "footer.json": build_footer,
        "home.json": build_home,
        "sluzby.json": build_sluzby,
        "audit.json": lambda: build_service_detail("audit"),
        "strategie.json": lambda: build_service_detail("strategie"),
        "core.json": lambda: build_service_detail("core"),
        "reference.json": build_reference,
        "onas.json": build_onas,
        "kontakt.json": build_kontakt,
    }

    counts: dict[str, int] = {}
    for filename, builder in builders.items():
        _used_ids.clear()
        elements = builder()
        write_json(filename, elements)
        counts[filename] = len(elements)

    manifest = {
        "generated_by": "build_elements.py",
        "files": [{"file": f, "description": d, "element_count": counts[f]} for f, d in MANIFEST],
        "total_elements": sum(counts.values()),
    }
    write_json("manifest.json", manifest)

    print("Generated Bricks element JSON files:")
    for f, d in MANIFEST:
        print(f"  {f}: {counts[f]} elements — {d}")
    print(f"  manifest.json")
    print(f"Total: {sum(counts.values())} elements across {len(counts)} content files")


if __name__ == "__main__":
    main()
