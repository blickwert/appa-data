#!/usr/bin/env python3
"""
APPA – Elementor Pro Template-Generator

Baut aus dem UXMagic/Tailwind-Mockup (../APPA-Mockup) native Elementor-Pro-
Templates (Flexbox-/Grid-Container + Standard-Widgets, keine HTML-Widgets).

Ausgabe (relativ zu diesem Ordner):
  templates/*.json              Einzel-Templates (Vorlagen > Importieren)
  appa-elementor-templates.zip  alle Einzel-Templates in einem ZIP
  appa-kit.zip                  Website-Kit: Global Colors/Fonts, Theme-Builder
                                Header/Footer inkl. Bedingungen, alle Seiten

Aufruf:  python3 build.py
"""

import copy
import hashlib
import json
import os
import zipfile
from datetime import datetime, timezone

HERE = os.path.dirname(os.path.abspath(__file__))
OUT_TPL = os.path.join(HERE, "templates")
ELEMENTOR_VERSION = "3.30.0"

IMG = "https://uxmagic.blob.core.windows.net/public/agent-images/"
IMG_HERO = IMG + "appa-hero-1787310408423-mr0edyb5t2r.png"
IMG_EVENT1 = IMG + "appa-event-1-1787310427059-0ox6xea5ag88.png"
IMG_EVENT2 = IMG + "appa-event-2-1787310440508-o2my4nignp.png"
IMG_ABOUT = IMG + "appa-about-1787310453392-lszqhjkxdxs.png"
IMG_MEMBER = IMG + "appa-membership-1787310465584-kbm2jmjt5ns.png"
IMG_AVATAR = "https://randomuser.me/api/portraits/women/44.jpg"

# Ziel-URLs der Seiten (WordPress-Slugs nach dem Import)
URL = {
    "home": "/",
    "about": "/ueber-uns/",
    "events": "/veranstaltungen/",
    "membership": "/mitgliedschaft/",
    "contact": "/kontakt/",
    "login": "/mitgliederbereich/",
    "dashboard": "/mitgliederbereich-uebersicht/",
    "privacy": "/datenschutz/",
    "imprint": "/impressum/",
}

# ---------------------------------------------------------------------------
# Design-Tokens (1:1 aus styles.css / :root des Mockups)
# ---------------------------------------------------------------------------

SYSTEM_COLORS = [
    ("primary", "Primär – Rot", "#D93629"),          # --primary
    ("secondary", "Sekundär – Navy", "#12345A"),     # --foreground
    ("text", "Text – Gedämpft", "#52677A"),          # --muted-foreground
    ("accent", "Akzent – Gold", "#E9B434"),          # --accent
]
CUSTOM_COLORS = [
    ("appawhite", "Hintergrund – Weiß", "#FFFFFF"),   # --background / --card
    ("appalight", "Hellblau", "#EAF3F8"),             # --secondary
    ("appamuted", "Grau hell", "#F4F7F8"),            # --muted
    ("appagreen", "Grün", "#77AA41"),                 # --tertiary
    ("appaborder", "Rahmen", "#D7E3EA"),              # --border / --input
    ("appaongreen", "Text auf Grün", "#343434"),      # --tertiary-foreground
    ("appaocean", "Fokus – Blau", "#328AC9"),         # --ring
    ("appagoldtxt", "Gold – Text", "#9F6D00"),        # --accent-text
]
HEX = {cid: hexv for cid, _, hexv in SYSTEM_COLORS + CUSTOM_COLORS}

FONT = "Inter"


def _typo(weight, size, lh, lh_unit="em", size_t=None, size_m=None,
          lh_t=None, lh_m=None, ls=None, transform=None):
    t = {
        "typography_typography": "custom",
        "typography_font_family": FONT,
        "typography_font_weight": str(weight),
        "typography_font_size": {"unit": "px", "size": size, "sizes": []},
        "typography_line_height": {"unit": lh_unit, "size": lh, "sizes": []},
    }
    if size_t is not None:
        t["typography_font_size_tablet"] = {"unit": "px", "size": size_t, "sizes": []}
    if size_m is not None:
        t["typography_font_size_mobile"] = {"unit": "px", "size": size_m, "sizes": []}
    if lh_t is not None:
        t["typography_line_height_tablet"] = {"unit": lh_unit, "size": lh_t, "sizes": []}
    if lh_m is not None:
        t["typography_line_height_mobile"] = {"unit": lh_unit, "size": lh_m, "sizes": []}
    if ls is not None:
        t["typography_letter_spacing"] = {"unit": "px", "size": ls, "sizes": []}
    if transform:
        t["typography_text_transform"] = transform
    return t


# Tailwind-Größen: text-xs 12/16, sm 14/20, base 16/24, lg 18/28, xl 20/28,
# 2xl 24/32, 3xl 30/36, 4xl 36/40, 5xl 48/48, 6xl 60/60; lg: == Elementor Desktop
SYSTEM_TYPO = [
    ("primary", "Überschrift", _typo(700, 30, 1.2)),
    ("secondary", "Zwischentitel", _typo(600, 20, 1.4)),
    ("text", "Fließtext", _typo(400, 16, 1.15)),  # Mockup: html{line-height:1.15}
    ("accent", "Button", _typo(600, 16, 1.15)),
]
CUSTOM_TYPO = [
    ("appah1hero", "H1 Hero (60/36)", _typo(700, 60, 1, size_t=36, size_m=36, lh_t=1.111, lh_m=1.111)),
    ("appah1page", "H1 Seite (48/36)", _typo(700, 48, 1, size_t=36, size_m=36, lh_t=1.111, lh_m=1.111)),
    ("appah1sm", "H1 Klein (36)", _typo(700, 36, 1.111)),
    ("appah1dash", "H1 Dashboard (36/30)", _typo(700, 36, 1.111, size_t=30, size_m=30, lh_t=1.2, lh_m=1.2)),
    ("appah2", "H2 Sektion (30)", _typo(700, 30, 1.2)),
    ("appah2sm", "H2 Klein (24)", _typo(700, 24, 1.333)),
    ("appah3", "H3 Karte (20)", _typo(700, 20, 1.4)),
    ("appah3sm", "H3 Kompakt (16)", _typo(700, 16, 1.15)),
    ("appaquote", "Zitat (30/24)", _typo(600, 30, 1.2, size_t=24, size_m=24, lh_t=1.333, lh_m=1.333)),
    ("appacardttl", "Kartentitel 600 (20)", _typo(600, 20, 1.4)),
    ("appaprice", "Preis (24)", _typo(700, 24, 1.333)),
    ("appalogo", "Logo (20)", _typo(700, 20, 1.4)),
    ("appaeyebrow", "Eyebrow (14, versal)", _typo(600, 14, 1.429, ls=1.4, transform="uppercase")),
    ("appalabel", "Label klein (12, versal)", _typo(600, 12, 1.333, ls=0.6, transform="uppercase")),
    ("appalead", "Lead (18)", _typo(400, 18, 1.556)),
    ("appaleadlong", "Lead lang (18/32)", _typo(400, 18, 32, lh_unit="px")),
    ("appabodyrel", "Fließtext locker (16/28)", _typo(400, 16, 28, lh_unit="px")),
    ("appabodysb", "Fließtext 600", _typo(600, 16, 1.15)),
    ("appasmall", "Klein (14)", _typo(400, 14, 1.429)),
    ("appasmallsb", "Klein 600 (14)", _typo(600, 14, 1.429)),
    ("appaxs", "Mini (12)", _typo(400, 12, 1.333)),
    ("appaxssb", "Mini 600 (12)", _typo(600, 12, 1.333)),
    ("appabtn", "Button (16)", _typo(600, 16, 1.15)),
    ("appabtnsm", "Button klein (14)", _typo(600, 14, 1.429)),
    ("appanav", "Navigation (14)", _typo(600, 14, 1.429)),
    ("appalinklg", "Link groß (18)", _typo(600, 18, 1.556)),
]

RADIUS = 12          # --radius 0.75rem (rounded-lg und rounded-xl)
SHADOW_MD = {        # Tailwind shadow-md (erste Ebene)
    "horizontal": 0, "vertical": 4, "blur": 6, "spread": -1,
    "color": "rgba(0, 0, 0, 0.1)",
}
SHADOW_SM = {"horizontal": 0, "vertical": 1, "blur": 3, "spread": 0, "color": "rgba(0, 0, 0, 0.1)"}

# ---------------------------------------------------------------------------
# Low-Level-Helfer für Elementor-Settings
# ---------------------------------------------------------------------------


def px(v, unit="px"):
    return {"unit": unit, "size": v, "sizes": []}


def custom(v):
    return {"unit": "custom", "size": v, "sizes": []}


def dims(t=0, r=None, b=None, l=None, unit="px"):
    r = t if r is None else r
    b = t if b is None else b
    l = r if l is None else l
    return {"unit": unit, "top": str(t), "right": str(r), "bottom": str(b),
            "left": str(l), "isLinked": t == r == b == l}


def gap(row, col=None):
    col = row if col is None else col
    return {"unit": "px", "size": col, "column": str(col), "row": str(row),
            "isLinked": row == col}


def link(url, external=False):
    return {"url": url, "is_external": "on" if external else "", "nofollow": "",
            "custom_attributes": ""}


def icon(value):
    lib = {"fas": "fa-solid", "far": "fa-regular", "fab": "fa-brands"}[value.split(" ")[0]]
    return {"value": value, "library": lib}


NO_ICON = {"value": "", "library": ""}


def is_global(v):
    return isinstance(v, str) and not v.startswith(("#", "rgb"))


def merge(*parts):
    out = {}
    for p in parts:
        for k, v in p.items():
            if k == "__globals__":
                out.setdefault("__globals__", {}).update(v)
            else:
                out[k] = v
    return out


def color(key, value):
    """Farbe als globale Referenz (ID) oder als Literal (#hex / rgba)."""
    if value is None:
        return {}
    if is_global(value):
        return {"__globals__": {key: "globals/colors?id=" + value}}
    return {key: value}


def typo(prefix, tid):
    if tid is None:
        return {}
    return {"__globals__": {prefix + "_typography": "globals/typography?id=" + tid}}


def margin(t=0, b=0, key="_margin"):
    if not t and not b:
        return {}
    return {key: dims(t, 0, b, 0)}


def bg(value, prefix="background"):
    return merge({prefix + "_background": "classic"}, color(prefix + "_color", value))


def border(width=1, col="appaborder", prefix="border", sides=None):
    w = dims(*sides) if sides else dims(width)
    return merge({prefix + "_border": "solid", prefix + "_width": w},
                 color(prefix + "_color", col))


# ---------------------------------------------------------------------------
# Elemente
# ---------------------------------------------------------------------------


def C(*children, **s):
    """Container. Wichtige Kurz-Parameter:
    dir: row|column  (+ dir_t / dir_m), justify, align, wrap, gap (int|tuple),
    boxed: True -> Inhalt auf Kit-Breite, w: Breite in %, pad: dims-Tupel
    """
    st = {}
    grid = s.pop("grid", None)
    if grid is not None:
        st["container_type"] = "grid"
        cols, cols_t, cols_m = grid
        st["grid_columns_grid"] = cols if isinstance(cols, dict) else px(cols, "fr")
        st["grid_columns_grid_tablet"] = cols_t if isinstance(cols_t, dict) else px(cols_t, "fr")
        st["grid_columns_grid_mobile"] = cols_m if isinstance(cols_m, dict) else px(cols_m, "fr")
        st["grid_rows_grid"] = custom("auto")
        st["grid_rows_grid_tablet"] = custom("auto")
        st["grid_rows_grid_mobile"] = custom("auto")
        g = s.pop("gap", 0)
        g = g if isinstance(g, tuple) else (g, g)
        st["grid_gaps"] = gap(*g)
        if "align" in s:
            st["grid_align_items"] = s.pop("align")
    else:
        st["flex_direction"] = s.pop("dir", "column")
        for bp in ("_tablet", "_mobile"):
            if "dir" + bp[:2] in s:
                st["flex_direction" + bp] = s.pop("dir" + bp[:2])
        if "justify" in s:
            st["flex_justify_content"] = s.pop("justify")
        if "align" in s:
            st["flex_align_items"] = s.pop("align")
        for bp in ("_tablet", "_mobile"):
            if "align" + bp[:2] in s:
                st["flex_align_items" + bp] = s.pop("align" + bp[:2])
        # Elementor bricht Container mobil standardmäßig um (wrap) – Tailwind nicht
        wrap = "wrap" if s.pop("wrap", False) else "nowrap"
        st["flex_wrap"] = wrap
        st["flex_wrap_tablet"] = wrap
        st["flex_wrap_mobile"] = wrap
        g = s.pop("gap", 0)
        g = g if isinstance(g, tuple) else (g, g)
        st["flex_gap"] = gap(*g)
        for bp in ("_tablet", "_mobile"):
            if "gap" + bp[:2] in s:
                gg = s.pop("gap" + bp[:2])
                gg = gg if isinstance(gg, tuple) else (gg, gg)
                st["flex_gap" + bp] = gap(*gg)

    boxed = s.pop("boxed", False)
    st["content_width"] = "boxed" if boxed else "full"
    if isinstance(boxed, int) and not isinstance(boxed, bool):
        st["boxed_width"] = px(boxed)

    if "w" in s:
        st["width"] = px(s.pop("w"), "%")
        st["width_tablet"] = px(s.pop("w_t", 100), "%")
        st["width_mobile"] = px(s.pop("w_m", 100), "%")
    pad = s.pop("pad", (0,))
    st["padding"] = dims(*pad)
    for bp in ("_tablet", "_mobile"):
        if "pad" + bp[:2] in s:
            st["padding" + bp] = dims(*s.pop("pad" + bp[:2]))
    if "mt" in s or "mb" in s:
        st.update(margin(s.pop("mt", 0), s.pop("mb", 0), key="margin"))
    if "minh" in s:
        st["min_height"] = px(s.pop("minh"))
    for bp in ("_tablet", "_mobile"):
        if "minh" + bp[:2] in s:
            st["min_height" + bp] = px(s.pop("minh" + bp[:2]))
    if "bg" in s:
        st = merge(st, bg(s.pop("bg")))
    if "bgimg" in s:
        st.update({
            "background_background": "classic",
            "background_image": {"url": s.pop("bgimg"), "id": "", "size": "", "alt": "", "source": "library"},
            "background_position": "center center",
            "background_repeat": "no-repeat",
            "background_size": "cover",
        })
    if "overlay" in s:
        st = merge(st, {"background_overlay_background": "classic"},
                   color("background_overlay_color", s.pop("overlay")),
                   {"background_overlay_opacity": px(s.pop("overlay_opacity", 1), "")})
    if "border" in s:
        b = s.pop("border")
        st = merge(st, border(*b) if isinstance(b, tuple) else border())
    if "bsides" in s:   # z.B. (0,0,1,0) = nur unten
        st = merge(st, border(sides=s.pop("bsides"), col=s.pop("bcol", "appaborder")))
    if "radius" in s:
        st["border_radius"] = dims(s.pop("radius"))
    if "shadow" in s:
        st["box_shadow_box_shadow_type"] = "yes"
        st["box_shadow_box_shadow"] = s.pop("shadow")
    if s.pop("overflow_hidden", False):
        st["overflow"] = "hidden"
    if "maxw" in s:  # Breite mit max-width: 100% (Tailwind max-w-*)
        st["width"] = px(s.pop("maxw"))
        st["width_tablet"] = px(100, "%")
        st["width_mobile"] = px(100, "%")
    if s.pop("grow", False):
        st.update({"_flex_size": "custom", "_flex_grow": 1, "_flex_shrink": 1})
    if "shrink0" in s:
        s.pop("shrink0")
        st["_flex_size"] = "none"
    if "anchor" in s:
        st["_element_id"] = s.pop("anchor")
    if "url" in s:
        st["html_tag"] = "a"
        st["link"] = link(s.pop("url"))
    if "tag" in s:
        st["html_tag"] = s.pop("tag")
    hide = s.pop("hide", ())
    for d in hide:
        st["hide_" + d] = "hidden-" + d
    if "sticky" in s:
        st["sticky"] = s.pop("sticky")
        st["sticky_on"] = s.pop("sticky_on", ["desktop", "tablet", "mobile"])
    if "z" in s:
        st["z_index"] = s.pop("z")
    if "title" in s:
        st["_title"] = s.pop("title")
    if "css_classes" in s:
        st["css_classes"] = s.pop("css_classes")
    st = merge(st, s.pop("extra", {}))
    assert not s, "Unbekannte Container-Parameter: %s" % s
    return {"elType": "container", "settings": st, "elements": list(children)}


def W(wtype, settings):
    return {"elType": "widget", "widgetType": wtype, "settings": settings, "elements": []}


def H(text, t="appah2", c="secondary", tag="div", mt=0, mb=0, align=None,
      url=None, maxw=None, extra=None):
    """Heading-Widget – auch für einzeilige Texte (Eyebrows, Labels)."""
    s = merge({"title": text, "header_size": tag}, typo("typography", t),
              color("title_color", c), margin(mt, mb))
    if align:
        s["align"] = align
    if url:
        s["link"] = link(url)
    if maxw:
        s.update({"_element_width": "initial", "_element_custom_width": px(maxw)})
    return W("heading", merge(s, extra or {}))


def P(html, t="text", c="text", mt=0, mb=0, align=None, link_c=None, maxw=None, extra=None):
    """Text-Editor-Widget für Fließtext."""
    if not html.lstrip().startswith("<"):
        html = "<p>" + html + "</p>"
    s = merge({"editor": html, "paragraph_spacing": px(0)}, typo("typography", t),
              color("text_color", c), margin(mt, mb))
    if link_c:
        s = merge(s, color("link_color", link_c), color("link_hover_color", link_c))
    if align:
        s["align"] = align
    if maxw:
        s.update({"_element_width": "initial", "_element_custom_width": px(maxw)})
    return W("text-editor", merge(s, extra or {}))


def BTN(text, url="#", v="primary", ico=None, size="md", mt=0, full=False,
        icon_left=False, extra=None):
    """Button. v: primary | outline | outline-bg | link | ghost"""
    s = {"text": text, "link": link(url), "border_radius": dims(RADIUS)}
    s = merge(s, typo("typography", "appabtnsm" if size == "xs" else "appabtn"))
    padx = {"md": 24, "sm": 20, "xs": 16}[size]
    s["text_padding"] = dims(13, padx)  # 16px * 1.15 + 26px ≈ min-h-11 (44px)
    if size == "xs":
        s["text_padding"] = dims(12, 16)
    if v == "primary":
        s = merge(s, bg("primary"), color("button_text_color", "appawhite"),
                  {"button_background_hover_background": "classic",
                   "button_background_hover_color": "#BF2C20"},
                  color("hover_color", "appawhite"))
    elif v in ("outline", "outline-bg"):
        s = merge(s, bg("appawhite" if v == "outline-bg" else "rgba(255, 255, 255, 0)"),
                  color("button_text_color", "secondary"),
                  {"border_border": "solid", "border_width": dims(1)},
                  color("border_color", "appaborder"),
                  color("hover_color", "primary"), color("button_hover_border_color", "primary"))
    elif v in ("link", "ghost"):
        s = merge(s, bg("rgba(255, 255, 255, 0)"),
                  color("button_text_color", "primary" if v == "link" else "text"),
                  color("hover_color", "secondary"),
                  {"text_padding": dims(0)})
    if ico:
        s["selected_icon"] = icon(ico)
        s["icon_align"] = "left" if icon_left else "right"
        s["icon_indent"] = px(8)
    if full:
        s["align"] = "justify"
    return W("button", merge(s, margin(mt, 0), extra or {}))


def ICON(ico, c="primary", size=24, mt=0, view="default", bgc=None, pad=None,
         shape="circle", radius=None, align="left", url=None):
    s = merge({"selected_icon": icon(ico), "view": view, "align": align, "size": px(size)},
              color("primary_color", bgc if view == "stacked" else c), margin(mt, 0))
    if view == "stacked":
        s = merge(s, color("secondary_color", c), {"shape": shape})
        if pad is not None:
            s["icon_padding"] = px(pad)
        if radius is not None:
            s["border_radius"] = dims(radius)
    if url:
        s["link"] = link(url)
    return W("icon", s)


def LIST(items, view="traditional", ic="appagreen", tc="text", t="text", space=16,
         isize=20, indent=12, mt=0, inline_align=None, icon_top=False, link_hover=None):
    lst = []
    for i, it in enumerate(items):
        text, ico, url = (it + (None, None))[:3] if isinstance(it, tuple) else (it, None, None)
        e = {"_id": "li%05d" % i, "text": text,
             "selected_icon": icon(ico) if ico else NO_ICON}
        if url:
            e["link"] = link(url)
        lst.append(e)
    s = merge({"view": view, "icon_list": lst, "space_between": px(space),
               "icon_size": px(isize), "text_indent": px(indent)},
              color("icon_color", ic), color("text_color", tc),
              typo("icon_typography", t), margin(mt, 0))
    if link_hover:
        s = merge(s, color("text_color_hover", link_hover))
    if inline_align:
        s["icon_align"] = inline_align
    if icon_top:
        s["icon_self_vertical_align"] = "flex-start"
        s["icon_vertical_offset"] = px(2)
    return W("icon-list", s)


def IMAGE(url, alt, ratio=None, radius=None, shadow=None, position=None, extra=None):
    s = {"image": {"url": url, "id": "", "alt": alt, "source": "library", "size": ""},
         "image_size": "full", "width": px(100, "%"), "align": "left"}
    if ratio:
        # aspect-video / aspect-[4/3] + object-cover
        s["height"] = custom("auto; aspect-ratio: %s" % ratio)
        s["object-fit"] = "cover"
        if position:
            s["object-position"] = position
    if radius is not None:
        s["image_border_radius"] = dims(radius)
    if shadow:
        s["image_box_shadow_box_shadow_type"] = "yes"
        s["image_box_shadow_box_shadow"] = shadow
    return W("image", merge(s, extra or {}))


def DIVIDER(mt=0, mb=0, c="appaborder"):
    return W("divider", merge({"style": "solid", "weight": px(1), "gap": px(0)},
                              color("color", c), margin(mt, mb)))


# ---------------------------------------------------------------------------
# Wiederkehrende Bausteine
# ---------------------------------------------------------------------------

def section(*children, bg=None, py=(80, 80), py_t=None, py_m=None, **kw):
    """Vollbreite Sektion mit Inhalt auf max-w-6xl (1152px inkl. 24px Rand)."""
    t, b = py
    kw.setdefault("pad", (t, 24, b, 24))
    if py_t:
        kw["pad_t"] = (py_t[0], 24, py_t[1], 24)
    if py_m:
        kw["pad_m"] = (py_m[0], 24, py_m[1], 24)
    if bg:
        kw["bg"] = bg
    kw.setdefault("boxed", True)
    return C(*children, **kw)


def eyebrow(text, mt=0, mb=0, c="primary", align=None):
    return H(text, "appaeyebrow", c, tag="p", mt=mt, mb=mb, align=align)


def span_cols(spans, gap_px, total=12):
    """Exakte Tailwind-Spaltenbreiten (col-span-x einer 12er-Grid) als CSS-Grid."""
    parts = []
    for sp in spans[:-1]:
        pct = sp / total * 100
        # Breite = sp*(100% - (total-1)*g)/total + (sp-1)*g = sp/total*100% - g*(total-sp)/total
        sub = gap_px * (total - sp) / total
        parts.append("calc(%s%% - %spx)" % (_num(pct), _num(sub)))
    parts.append("minmax(0, 1fr)")
    return custom(" ".join(parts))


def _num(v):
    return ("%.3f" % v).rstrip("0").rstrip(".")


def cta_green(title, text, btn, eyebrow_text=None):
    """Grüne Mitglied-werden-Leiste (Home, Über uns, Kontakt)."""
    left = [H(title, "appah2", "appaongreen", tag="h2", mt=8 if eyebrow_text else 0),
            P(text, "text", "appaongreen", mt=12 if eyebrow_text else 8, maxw=672 if eyebrow_text else None)]
    if eyebrow_text:
        left.insert(0, eyebrow(eyebrow_text, c="appaongreen"))
    return section(
        C(*left, pad=(0,), grow=True, title="Text"),
        BTN(btn, URL["membership"]),
        bg="appagreen", py=(56, 56), dir="row", dir_t="column" if eyebrow_text else "row",
        dir_m="column", justify="space-between", align="center",
        align_t="stretch" if eyebrow_text else "center", align_m="stretch",
        gap=28 if eyebrow_text else 24, title="CTA Mitgliedschaft")


def logo(url=URL["home"]):
    return H('APPA<span style="color: var(--e-global-color-primary);">.</span>',
             "appalogo", "secondary", url=url)


# ---------------------------------------------------------------------------
# Theme-Builder: Header & Footer
# ---------------------------------------------------------------------------

def nav_menu():
    s = merge({
        "menu": "hauptmenue",
        "layout": "horizontal",
        "align_items": "right",
        "pointer": "none",
        "submenu_icon": icon("fas fa-chevron-down"),
        "dropdown": "tablet",
        "full_width": "stretch",
        "text_align": "aside",
        "toggle": "burger",
        "toggle_align": "right",
        "padding_horizontal_menu_item": px(0),
        "padding_vertical_menu_item": px(0),
        "menu_space_between": px(28),
        "toggle_size": px(24),
        "toggle_border_width": px(0),
        "toggle_border_radius": px(RADIUS),
        "padding_horizontal_dropdown_item": px(24),
        "padding_vertical_dropdown_item": px(12),
        "dropdown_top_distance": px(21),
    }, typo("menu_typography", "appanav"), typo("dropdown_typography", "appanav"),
        color("color_menu_item", "secondary"), color("color_menu_item_hover", "primary"),
        color("color_menu_item_active", "primary"),
        color("color_dropdown_item", "secondary"), color("background_color_dropdown_item", "appawhite"),
        color("color_dropdown_item_hover", "primary"), color("background_color_dropdown_item_hover", "appamuted"),
        color("color_dropdown_item_active", "primary"), color("background_color_dropdown_item_active", "appalight"),
        color("toggle_color", "secondary"), color("toggle_background_color", "rgba(255, 255, 255, 0)"))
    return W("nav-menu", s)


def tpl_header():
    return [C(logo(), nav_menu(),
              boxed=True, dir="row", justify="space-between", align="center",
              pad=(20, 24), bg="appawhite", bsides=(0, 0, 1, 0), z=50,
              tag="header", title="Header")]


FOOTER_LINKS = [("Home", None, URL["home"]), ("Über uns", None, URL["about"]),
                ("Veranstaltungen", None, URL["events"]),
                ("Mitgliedschaft", None, URL["membership"]), ("Kontakt", None, URL["contact"])]


def footer_links(items, tc="appawhite", hover="accent", gap_px=20):
    return LIST(items, view="inline", tc=tc, t="appasmall", space=gap_px, isize=0, indent=0,
                link_hover=hover)


def tpl_footer_home():
    return [C(
        C(H("APPA.", "appalogo", "appawhite"),
          P("Austrian Positive Psychology Association", "appasmall", "rgba(255, 255, 255, 0.8)", mt=8),
          pad=(0,), title="Marke"),
        footer_links(FOOTER_LINKS),
        P('© 2026 APPA · <a href="%s">Datenschutz</a> · <a href="%s">Impressum</a>' % (URL["privacy"], URL["imprint"]),
          "appasmall", "rgba(255, 255, 255, 0.8)", link_c="rgba(255, 255, 255, 0.8)"),
        boxed=True, dir="row", dir_m="column", justify="space-between", align="flex-start",
        gap=24, wrap=True, pad=(40, 24), bg="secondary", tag="footer", title="Footer")]


def tpl_footer_compact():
    return [C(
        P("© 2026 APPA · Austrian Positive Psychology Association", "appasmall", "appawhite"),
        footer_links([("Home", None, URL["home"]), ("Kontakt", None, URL["contact"]),
                      ("Datenschutz", None, URL["privacy"]), ("Impressum", None, URL["imprint"])]),
        boxed=True, dir="row", dir_m="column", justify="space-between", align="center",
        align_m="flex-start", gap=20, pad=(36, 24), bg="secondary", tag="footer", title="Footer")]


def member_header():
    return C(logo(),
             BTN("Zur Website", URL["home"], "ghost", ico="fas fa-arrow-left", size="sm",
                 icon_left=True, extra=merge(color("button_text_color", "secondary"))),
             boxed=True, dir="row", justify="space-between", align="center",
             pad=(20, 24), bg="appawhite", bsides=(0, 0, 1, 0), tag="header", title="Header Mitgliederbereich")


def member_footer():
    return C(P("© 2026 APPA · Austrian Positive Psychology Association", "appasmall", "text"),
             footer_links([("Kontakt", None, URL["contact"]), ("Datenschutz", None, URL["privacy"]),
                           ("Impressum", None, URL["imprint"])], tc="text", hover="primary"),
             boxed=True, dir="row", dir_m="column", justify="space-between", align="center",
             align_m="flex-start", gap=12, pad=(24, 24), bg="appawhite", bsides=(1, 0, 0, 0),
             tag="footer", title="Footer Mitgliederbereich")


# ---------------------------------------------------------------------------
# Seiten
# ---------------------------------------------------------------------------

def card_event_home(img, alt, meta, title, sub, btn, btn_v):
    return C(
        IMAGE(img, alt, ratio="16 / 9"),
        C(H(meta, "appasmallsb", "primary", tag="p"),
          H(title, "appah3", "secondary", tag="h3", mt=12),
          P(sub, "text", "text", mt=12),
          C(BTN(btn, URL["events"], btn_v, size="sm"), pad=(24, 0, 0, 0), dir="row"),
          pad=(24,), title="Inhalt"),
        bg="appawhite", radius=RADIUS, overflow_hidden=True, shadow=SHADOW_MD, pad=(0,),
        tag="article", title="Event-Karte")


def page_home():
    hero = C(
        C(eyebrow("Austrian Positive Psychology Association", mb=20),
          H("Österreichische Gesellschaft für Positive Psychologie", "appah1hero", "secondary", tag="h1"),
          P("Austrian Positive Psychology Association", "appalead", "text", mt=24, maxw=576),
          C(BTN("Die APPA kennenlernen", URL["about"], ico="fas fa-arrow-right"),
            BTN("Veranstaltungen", URL["events"], "outline"),
            dir="row", wrap=True, gap=12, mt=36, title="Buttons"),
          w=50, pad=(96, 24), pad_t=(64, 24), justify="center", z=1, title="Hero Text"),
        C(C(H("Austausch von Wissenschaft und Praxis", "appacardttl", "secondary", tag="p"),
            P("Positives Wissen wirksam in den Alltag bringen.", "appasmall", "text", mt=4),
            bg="appawhite", radius=RADIUS, pad=(20,), shadow=SHADOW_MD, title="Bild-Karte"),
          w=50, bgimg=IMG_HERO, justify="flex-end", pad=(32, 24), minh_t=280,
          title="Hero Bild"),
        boxed=1152, dir="row", dir_t="column", pad=(0,), minh=520, bg="appalight",
        overflow_hidden=True, title="Hero")

    intro = section(
        C(eyebrow("Die APPA"),
          H("Wissen, das Menschen stärkt.", "appah2", tag="h2", mt=12), title="Titel"),
        C(P("Die Austrian Positive Psychology Association fördert den Austausch von Wissenschaft und "
            "Praxis der Positiven Psychologie in Österreich. Wir verbinden Forschende, Praktizierende und "
            "Interessierte durch Weiterbildung, Veranstaltungen und fachlichen Dialog. Die Austrian Positive "
            "Psychology Association fördert den Austausch von Wissenschaft und Praxis der Positiven "
            "Psychologie in Österreich. Wir verbinden Forschende, Praktizierende und Interessierte durch "
            "Weiterbildung, Veranstaltungen und fachlichen Dialog.", "appaleadlong", "text"),
          C(BTN("Mehr erfahren", URL["about"], "link", ico="fas fa-arrow-right"), dir="row", mt=28),
          title="Text"),
        grid=(span_cols([4, 8], 40), 1, 1), gap=40, title="Die APPA")

    events = section(
        C(C(eyebrow("Nächste Termine"), H("Veranstaltungen", "appah2", tag="h2", mt=8), title="Titel"),
          BTN("Alle Veranstaltungen", URL["events"], "link", ico="fas fa-arrow-right"),
          dir="row", wrap=True, justify="space-between", align="flex-end", gap=16, title="Kopf"),
        C(card_event_home(IMG_EVENT1, "Speaker presenting at a positive psychology lecture",
                          "[Datum] · Live-Stream via Zoom",
                          "Öffentlicher Vortrag: Positive Psychologie im Berufsalltag",
                          "[Uhrzeit] · Online", "Anmelden", "primary"),
          card_event_home(IMG_EVENT2, "Attendees networking at a psychology conference",
                          "[Datum] · [Universität/Ort]",
                          "APPA Fachtagung: Wohlbefinden, Resilienz und Gesundheit",
                          "Save the date · Programm folgt", "Details", "outline"),
          grid=(2, 2, 1), gap=28, title="Karten"),
        bg="appamuted", gap=36, title="Veranstaltungen")

    quote = section(
        ICON("fas fa-quote-left", "accent", 32, align="center"),
        H("„Das Ziel der Positiven Psychologie ist es, das Leben der Menschen zu verbessern, indem wir "
          "ihre Stärken erkennen und fördern.“", "appaquote", "secondary", tag="blockquote",
          mt=20, align="center"),
        H("— Martin Seligman", "text", "text", tag="p", mt=20, align="center"),
        boxed=848, title="Zitat")

    cta = cta_green("Mitglied werden bei der APPA",
                    "Bleiben Sie mit unserem Newsletter, Forschung und neuen Weiterbildungsangeboten verbunden.",
                    "Mehr erfahren", eyebrow_text="Teil der Community werden")
    return [hero, intro, events, quote, cta]


def page_header_band(eyebrow_text, title, text):
    """Hellblaue Seitenkopf-Sektion (Veranstaltungen, Kontakt)."""
    return section(eyebrow(eyebrow_text),
                   H(title, "appah1page", "secondary", tag="h1", mt=12),
                   P(text, "appalead", "text", mt=20, maxw=672),
                   bg="appalight", py=(80, 80), py_t=(64, 64), py_m=(64, 64), title="Seitenkopf")


def info_card(ico, ic, title, text, big=False, anchor=None):
    kw = {"anchor": anchor} if anchor else {}
    return C(ICON(ico, ic, 30),
             H(title, "appah2sm" if big else "appah3", "secondary", tag="h3", mt=20),
             P(text, "appabodyrel" if big else "text", "text", mt=12),
             C(BTN("Mehr erfahren", "#", "link", ico="fas fa-arrow-right"), dir="row", mt=24),
             bg="appawhite", radius=RADIUS, shadow=SHADOW_MD, pad=(28,), tag="article",
             title=title, **kw)


def board_card(color_id, role):
    return C(ICON("fas fa-user", "secondary", 30, view="stacked", bgc=color_id, pad=25),
             H("[Name]", "appah3sm", "secondary", tag="p", mt=20),
             H(role, "appasmall", "text", tag="p"),
             bg="appalight", radius=RADIUS, pad=(20,), align="flex-start", title="Vorstand")


def page_about():
    hero = C(
        C(eyebrow("Die Association"),
          H("Über uns", "appah1sm", "secondary", tag="h1", mt=8),
          bg="appawhite", radius=RADIUS, pad=(20, 28), title="Titel-Box",
          extra={"width": custom("auto")}),
        boxed=True, bgimg=IMG_ABOUT, overlay="secondary", overlay_opacity=0.3,
        minh=380, justify="flex-end", align="flex-start", pad=(0, 24, 56, 24),
        overflow_hidden=True, title="Hero")

    subnav = section(
        LIST([("Mission und Ziele", None, "#mission"), ("Positive Psychologie", None, "#psychologie"),
              ("Vorstand", None, "#vorstand"), ("Verzeichnis", None, "#verzeichnis"),
              ("Kooperationen", None, "#kooperationen")],
             view="inline", tc="secondary", t="appasmallsb", space=24, isize=0, indent=0,
             link_hover="primary"),
        bg="appalight", py=(16, 16), bsides=(0, 0, 1, 0), title="Sprungnavigation")

    mission = section(
        C(eyebrow("Was ist die APPA"),
          H("Wissenschaft und Praxis miteinander verbinden.", "appah2", tag="h2", mt=12), title="Titel"),
        P("Die APPA fördert den Austausch von Forschung, Wissenschaft und Praxis der Positiven Psychologie "
          "in Österreich. Sie bietet Weiterbildungsangebote, stärkt die Anwendung im Alltag und richtet "
          "sich an Forschende, Praktizierende und Interessierte.", "appaleadlong", "text"),
        grid=(span_cols([4, 8], 40), 1, 1), gap=40, anchor="mission", title="Mission")

    cards = section(
        C(info_card("fas fa-magic", "primary", "Was ist Positive Psychologie?",
                    "Eine junge Forschungsrichtung, die erforscht und kultiviert, was das Leben lebenswert macht.",
                    big=True),
          info_card("fas fa-bullseye", "appagreen", "Unsere Mission",
                    "Wissen, Weiterbildung und praktische Anwendung in Österreich fördern."),
          info_card("fas fa-users", "primary", "Öffentliches Mitgliederverzeichnis",
                    "Expertinnen, Experten und Anwender:innen in Österreich finden.", anchor="verzeichnis"),
          info_card("fas fa-handshake", "appagoldtxt", "Kooperationen",
                    "Gemeinsam mit Organisationen und Fachleuten Positive Psychologie stärken.",
                    anchor="kooperationen"),
          info_card("fas fa-award", "appagreen", "Förderpreise",
                    "Informationen zu Preis und Voraussetzungen für Einreichungen."),
          info_card("fas fa-layer-group", "primary", "Kommissionen & Statuten",
                    "Übersicht der APPA-Kommissionen und Downloads zu den Statuten."),
          grid=(3, 2, 1), gap=24, title="Karten"),
        bg="appamuted", anchor="psychologie", title="Positive Psychologie")
    # erste Karte über zwei Spalten (lg:col-span-2)
    first = cards["elements"][0]["elements"][0]
    first["settings"].update({"grid_column": "custom", "grid_column_custom": "span 2",
                              "grid_column_tablet": "custom", "grid_column_custom_tablet": "span 1",
                              "grid_column_mobile": "custom", "grid_column_custom_mobile": "span 1"})

    board = section(
        C(C(eyebrow("Menschen der APPA"), H("Vorstand", "appah2", tag="h2", mt=8), title="Titel"),
          H("Namen und Fotos folgen", "appasmall", "text", tag="p"),
          dir="row", justify="space-between", align="flex-end", gap=24, title="Kopf"),
        C(board_card("accent", "Präsident:in APPA"), board_card("appagreen", "Vizepräsident:in"),
          board_card("accent", "Finanzen"), board_card("appagreen", "Sekretariat/Aktuar:in"),
          board_card("accent", "Beisitz"),
          grid=(5, 2, 1), gap=20, title="Vorstand-Grid"),
        gap=36, anchor="vorstand", title="Vorstand")

    cta = cta_green("Mitglied werden bei der APPA",
                    "Austausch, Forschung und Weiterbildung gemeinsam voranbringen.",
                    "Mitgliedschaft entdecken")
    return [hero, subnav, mission, cards, board, cta]


def event_card(img, alt, date, time, title, place_icon, place, text, b1, b2):
    return C(
        IMAGE(img, alt, ratio="16 / 9"),
        C(C(H(date, "appabodysb", "secondary", tag="span",
              extra=merge(bg("appalight", "_background"), {"_padding": dims(8, 12), "_border_radius": dims(RADIUS),
                                                          "_element_width": "auto"})),
            H(time, "appabodysb", "primary", tag="span"),
            dir="row", align="center", gap=12, title="Datum"),
          H(title, "appah2sm", "secondary", tag="h2", mt=20),
          LIST([(place, place_icon)], ic="appagreen", tc="text", isize=20, indent=8, mt=16),
          P(text, "appabodyrel", "text", mt=20),
          C(BTN(b1, "#", "primary", size="sm"), BTN(b2, "#", "outline", size="sm"),
            dir="row", wrap=True, gap=12, mt=28, title="Buttons"),
          C(H("Teilen", "appasmall", "text", tag="span"),
            W("share-buttons", merge({
                "share_buttons": [{"_id": "sb1", "button": "facebook"}, {"_id": "sb2", "button": "twitter"},
                                  {"_id": "sb3", "button": "linkedin"}],
                "view": "icon", "skin": "minimal", "shape": "square", "columns": "0",
                "alignment": "right", "button_size": px(1.1, ""), "icon_size": px(1.25, "em"),
                "color_source": "custom", "column_gap": px(4), "row_gap": px(0)},
                color("primary_color", "rgba(255, 255, 255, 0)"), color("secondary_color", "secondary"),
                color("primary_color_hover", "rgba(255, 255, 255, 0)"), color("secondary_color_hover", "primary"))),
            dir="row", justify="space-between", align="center", mt=28, pad=(20, 0, 0, 0),
            bsides=(1, 0, 0, 0), title="Teilen"),
          pad=(28,), title="Inhalt"),
        bg="appawhite", border=True, radius=RADIUS, overflow_hidden=True, shadow=SHADOW_MD,
        pad=(0,), tag="article", title="Event-Karte")


def page_events():
    head = page_header_band("APPA Kalender", "Veranstaltungen",
                            "Impulse aus Forschung und Praxis, neue Perspektiven und Begegnung innerhalb "
                            "der Positiven Psychologie.")
    cards = section(
        event_card(IMG_EVENT1, "Speaker presenting at a positive psychology lecture", "Di., [Datum]",
                   "[Uhrzeit]", "Öffentlicher Vortrag: Positive Psychologie im Berufsalltag",
                   "fas fa-desktop", "Live-Stream via Zoom",
                   "Wie stärken wir Wohlbefinden, Beziehungen und Leistung im Arbeitsalltag? Ein öffentlicher "
                   "Impuls mit anschliessendem Austausch.", "Anmelden", "Details"),
        event_card(IMG_EVENT2, "Attendees networking at a psychology conference", "[Datum]", "[Uhrzeit]",
                   "APPA Fachtagung: Wohlbefinden, Resilienz und Gesundheit", "fas fa-map-marker-alt",
                   "[Universität/Ort in Österreich]",
                   "Save the date! Wissenschaftliche Perspektiven auf Wohlbefinden, Resilienz und Gesundheit. "
                   "Informationen zu Programm und Anmeldung folgen.", "Details", "Termin vormerken"),
        grid=(2, 1, 1), gap=32, py=(64, 64), title="Termine")
    news = section(
        C(eyebrow("Keine Termine verpassen"),
          H("APPA-News direkt ins Postfach", "appah2", tag="h2", mt=8),
          P("Erhalten Sie Hinweise zu neuen Veranstaltungen und aktuellen Entwicklungen.", "text", "text", mt=12),
          title="Text"),
        BTN("Mitglied werden", URL["membership"]),
        bg="appamuted", py=(64, 64), dir="row", dir_m="column", justify="space-between",
        align="center", gap=28, title="Newsletter")
    return [head, cards, news]


def tier_card(title, text, btn_v, highlight=False):
    kids = []
    if highlight:
        kids.append(H("FÜR DIE PRAXIS", "appasmallsb", "primary", tag="p", mb=12))
    kids += [H(title, "appah3", "secondary", tag="h3"),
             P(text, "appabodyrel", "text", mt=12),
             H('€ [Betrag] <span style="font-size:14px;font-weight:400;color:var(--e-global-color-text);">/ Jahr</span>',
               "appaprice", "primary", tag="p", mt=28),
             BTN("Mitglied werden", "#", btn_v, full=True, mt=28,
                 extra={"text_padding": dims(13, 0)})]
    kw = {"border": (2, "primary"), "shadow": SHADOW_MD} if highlight else {"border": True}
    return C(*kids, radius=RADIUS, pad=(28,), tag="article", title=title, **kw)


def member_dir_card():
    return C(H("[Name]", "appabodysb", "secondary", tag="p"),
             H("[Titel/Funktion] · [Ort]", "appasmall", "primary", tag="p", mt=4),
             P("Profil und Kontakt folgen, sobald Mitgliederdaten vorliegen.", "appasmall", "text", mt=16),
             bg="appawhite", radius=RADIUS, pad=(24,), tag="article", title="Mitglied")


def page_membership():
    intro = section(
        C(eyebrow("Teil der APPA werden"),
          H("Informationen zur Mitgliedschaft", "appah1page", "secondary", tag="h1", mt=12),
          P("Vernetzen Sie sich mit Menschen, die Positive Psychologie in Österreich erforschen, anwenden "
            "und weiterdenken.", "appaleadlong", "text", mt=24),
          C(BTN("Jetzt anmelden", "#mitgliedschaft-arten", ico="fas fa-arrow-right"), dir="row", mt=32),
          title="Text"),
        IMAGE(IMG_MEMBER, "Two professionals discussing research notes in a library", ratio="4 / 3",
              radius=RADIUS, shadow=SHADOW_MD, position="top center"),
        grid=(2, 1, 1), gap=48, align="center", py=(80, 80), py_t=(64, 64), py_m=(64, 64),
        title="Intro")

    info = section(
        C(H("Allgemeine Informationen", "appah2", tag="h2"),
          LIST([("Für Teilnehmende und Absolvent:innen relevanter österreichischer Lehrgänge kann das erste "
                 "Jahr gratis sein. [Bitte bestätigen]", "fas fa-check"),
                ("Die Mitgliedschaft verlängert sich automatisch um ein weiteres Jahr.", "fas fa-check"),
                ("Eine Kündigung ist bis Ende des Kalenderjahres schriftlich per E-Mail mitzuteilen.",
                 "fas fa-check")], space=16, mt=28, icon_top=True),
          title="Allgemein"),
        C(H("Ihre Vorteile", "appah2", tag="h2"),
          C(*[C(P(t, "text", "text"), bg="appawhite", radius=RADIUS, pad=(16,), title="Vorteil")
              for t in ("Aktueller Newsletter und internationaler Ausblick",
                        "Zugang zu Forschungsarbeiten und Videomaterial",
                        "Vergünstigte APPA-Veranstaltungen und Fachtagung",
                        "Vernetzung und Eintrag im Mitgliederverzeichnis")],
            grid=(2, 2, 1), gap=16, mt=28, title="Vorteile-Grid"),
          title="Vorteile"),
        grid=(2, 1, 1), gap=48, bg="appalight", py=(64, 64), title="Informationen")

    tiers = section(
        C(eyebrow("Passende Form wählen"), H("Arten der Mitgliedschaft", "appah2", tag="h2", mt=8),
          maxw=672, title="Titel"),
        C(tier_card("Ordentliche Mitgliedschaft",
                    "Für Psycholog:innen mit Hochschulabschluss und österreichischem Bezug.", "outline"),
          tier_card("Assoziierte Mitgliedschaft",
                    "Für Berufstätige mit Bezug zu Positiver Psychologie, etwa Pädagogik, Sozialarbeit, "
                    "Coaching oder Beratung.", "primary", highlight=True),
          tier_card("Studentische Mitgliedschaft",
                    "Für Psychologiestudierende, die in Österreich studieren und einen Abschluss anstreben.",
                    "outline"),
          grid=(3, 1, 1), gap=24, title="Karten"),
        gap=36, anchor="mitgliedschaft-arten", title="Arten der Mitgliedschaft")

    directory = section(
        C(C(eyebrow("Öffentliches Mitgliederverzeichnis"),
            H("Expertise sichtbar machen", "appah2", tag="h2", mt=8), title="Titel"),
          BTN("Mitglieder suchen", "#", "outline-bg", ico="fas fa-search", size="sm", icon_left=True),
          dir="row", dir_m="column", justify="space-between", align="flex-end", align_m="stretch",
          gap=16, title="Kopf"),
        C(member_dir_card(), member_dir_card(), member_dir_card(),
          grid=(3, 3, 1), gap=20, title="Mitglieder-Grid"),
        bg="appamuted", gap=32, title="Mitgliederverzeichnis")
    return [intro, info, tiers, directory]


def contact_form():
    fields = [
        ("name", "text", "Vor- und Nachname", "Ihr Name", "50"),
        ("email", "email", "E-Mail-Adresse", "name@beispiel.at", "50"),
        ("subject", "text", "Betreff", "Worum geht es?", "100"),
        ("message", "textarea", "Nachricht", "Ihre Nachricht an die APPA", "100"),
    ]
    ff = []
    for cid, ftype, label, ph, width in fields:
        f = {"_id": cid, "custom_id": cid, "field_type": ftype, "field_label": label,
             "placeholder": ph, "required": "true", "width": width, "width_mobile": "100"}
        if ftype == "textarea":
            f["rows"] = 6
        ff.append(f)
    ff.append({"_id": "privacy", "custom_id": "privacy", "field_type": "acceptance", "field_label": "",
               "acceptance_text": "Ich habe die Datenschutzhinweise gelesen.", "required": "true",
               "width": "100"})
    s = merge({
        "form_name": "APPA Kontakt",
        "form_fields": ff,
        "input_size": "md",
        "show_labels": "true",
        "mark_required": "",
        "button_text": "Nachricht senden",
        "button_size": "md",
        "button_width": "",
        "selected_button_icon": icon("fas fa-paper-plane"),
        "button_icon_align": "right",
        "button_icon_indent": px(8),
        "submit_actions": ["email"],
        "email_to": "info@appa.at",
        "email_subject": "Neue Nachricht über appa.at",
        "success_message": "Vielen Dank! Ihre Nachricht wurde gesendet.",
        "error_message": "Beim Senden ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.",
        "required_field_message": "Dieses Feld ist erforderlich.",
        "invalid_message": "Ungültige Eingabe.",
        "column_gap": px(20),
        "row_gap": px(20),
        "label_spacing": px(8),
        "field_border_width": dims(1),
        "field_border_radius": dims(RADIUS),
        "button_border_radius": dims(RADIUS),
        "button_text_padding": dims(13, 24),
    }, typo("label_typography", "appasmallsb"), color("label_color", "secondary"),
        typo("field_typography", "text"), color("field_text_color", "secondary"),
        color("field_background_color", "appawhite"), color("field_border_color", "appaborder"),
        typo("html_typography", "appasmall"), color("html_color", "text"),
        typo("button_typography", "appabtn"), color("button_background_color", "primary"),
        color("button_text_color", "appawhite"), color("button_background_hover_color", "#BF2C20"),
        color("button_hover_color", "appawhite"))
    return W("form", s)


def page_contact():
    head = page_header_band("Wir freuen uns auf Ihre Nachricht", "Kontakt aufnehmen",
                            "Fragen zur APPA, zur Mitgliedschaft oder zu unseren Veranstaltungen? Schreiben Sie uns.")
    left = C(
        H("APPA – Austrian Positive Psychology Association", "appah2sm", "secondary", tag="h2"),
        P("Haben Sie Fragen an die APPA? Schreiben Sie uns gerne eine E-Mail.", "appabodyrel", "text", mt=20),
        C(BTN("info@appa.at", "mailto:info@appa.at", "link", ico="fas fa-external-link-alt",
              extra=typo("typography", "appalinklg")), dir="row", mt=20),
        C(LIST([('<strong style="color:var(--e-global-color-secondary);">Herausgeber</strong><br>APPA<br>'
                 '[Adresse/Sitz in Graz oder Wien]<br>Österreich', "fas fa-map-marker-alt"),
                ('<strong style="color:var(--e-global-color-secondary);">Webmaster</strong><br>info@appa.at',
                 "far fa-envelope")], space=20, icon_top=True),
          mt=36, pad=(28, 0, 0, 0), bsides=(1, 0, 0, 0), title="Adresse"),
        H("Folge uns", "appabodysb", "secondary", tag="p", mt=36),
        W("social-icons", merge({
            "social_icon_list": [
                {"_id": "s1", "social_icon": icon("fab fa-linkedin-in"), "link": link("https://www.linkedin.com/", True)},
                {"_id": "s2", "social_icon": icon("fab fa-instagram"), "link": link("https://www.instagram.com/", True)}],
            "shape": "rounded", "align": "left", "icon_color": "custom",
            "icon_size": px(20), "icon_padding": px(0.6, "em"), "icon_spacing": px(16),
            "border_radius": dims(RADIUS)},
            color("icon_primary_color", "appalight"), color("icon_secondary_color", "secondary"),
            color("hover_primary_color", "primary"), color("hover_secondary_color", "appawhite"),
            margin(16, 0))),
        title="Kontaktinfos")
    right = C(H("Ihre Nachricht", "appah2sm", "secondary", tag="h2"),
              P("Wir melden uns so bald wie möglich bei Ihnen zurück.", "text", "text", mt=8, mb=28),
              contact_form(),
              bg="appamuted", radius=RADIUS, shadow=SHADOW_MD, pad=(36,), pad_m=(28, 28, 28, 28),
              title="Formular")
    body = section(left, right, grid=(span_cols([2, 3], 48, total=5), 1, 1), gap=48, title="Kontakt")
    cta = cta_green("Mitglied werden bei der APPA",
                    "Bleiben Sie über Positive Psychologie in Österreich informiert.", "Mehr erfahren")
    return [head, body, cta]


def page_login():
    login = W("login", merge({
        "show_labels": "yes",
        "custom_labels": "yes",
        "user_label": "E-Mail-Adresse",
        "user_placeholder": "name@beispiel.at",
        "password_label": "Passwort",
        "password_placeholder": "Ihr Passwort",
        "button_text": "Anmelden",
        "button_size": "md",
        "show_lost_password": "yes",
        "show_register": "",
        "show_remember_me": "",
        "show_logged_in_message": "yes",
        "redirect_after_login": "yes",
        "redirect_url": link(URL["dashboard"]),
        "align": "justify",
        "row_gap": px(20),
        "field_border_width": px(1),
        "field_border_radius": px(RADIUS),
        "button_border_radius": px(RADIUS),
        "button_text_padding": dims(13, 24),
        "links_spacing": px(0),
    }, typo("label_typography", "appasmallsb"), color("label_color", "secondary"),
        typo("field_typography", "text"), color("field_text_color", "secondary"),
        color("field_background_color", "appawhite"), color("field_border_color", "appaborder"),
        typo("button_typography", "appabtn"), color("button_background_color", "primary"),
        color("button_text_color", "appawhite"), color("button_background_hover_color", "#BF2C20"),
        typo("links_typography", "appasmallsb"), color("links_color", "primary"),
        color("links_hover_color", "secondary")))
    card = C(
        ICON("fas fa-lock", "primary", 24, view="stacked", bgc="appalight", pad=12, shape="square", radius=RADIUS),
        eyebrow("Geschützter Bereich", mt=24),
        H("Mitgliederbereich", "appah2", "secondary", tag="h1", mt=8),
        P("Melden Sie sich an, um Ihre Mitgliedschaft, exklusive Inhalte und Veranstaltungen zu verwalten.",
          "text", "text", mt=12),
        C(login, mt=32, title="Login"),
        C(ICON("fas fa-info-circle", "appagreen", 18),
          P('Nur für APPA-Mitglieder. Bei Fragen zu Ihrem Zugang kontaktieren Sie bitte '
            '<a href="mailto:info@appa.at" style="font-weight:600;">info@appa.at</a>.',
            "appasmall", "text", link_c="primary", extra={"_flex_size": "custom", "_flex_grow": 1, "_flex_shrink": 1}),
          dir="row", align="flex-start", gap=12, mt=28, pad=(24, 0, 0, 0), bsides=(1, 0, 0, 0), title="Hinweis"),
        bg="appawhite", border=True, radius=RADIUS, shadow=SHADOW_MD, pad=(36,), pad_m=(28, 28, 28, 28),
        align="flex-start", title="Login-Karte")
    main = C(
        C(card,
          P('Noch nicht Mitglied? <a href="%s" style="font-weight:600;">Informationen zur Mitgliedschaft</a>'
            % URL["membership"], "appasmall", "text", mt=24, align="center", link_c="text"),
          boxed=400, pad=(56, 24), title="Login-Spalte"),
        bg="appamuted", justify="center", pad=(0,), title="Login-Bereich",
        extra={"min_height": custom("calc(100vh - 130px)")})
    return [member_header(), main, member_footer()]


def dash_nav_item(text, ico, active=False, url="#"):
    return BTN(text, url, "ghost", ico=ico, icon_left=True, full=True, extra=merge(
        {"text_padding": dims(10, 16), "content_align": "start", "icon_indent": px(12),
         "border_radius": dims(RADIUS)},
        typo("typography", "appabodysb" if active else "text"),
        color("button_text_color", "secondary" if active else "text"),
        bg("appalight" if active else "rgba(255, 255, 255, 0)"),
        {"button_background_hover_background": "classic"},
        color("button_background_hover_color", "appalight" if active else "appamuted"),
        color("hover_color", "secondary")))


def dash_section_head(title, action):
    return C(H(title, "appah3", "secondary", tag="h2"),
             BTN(action, "#", "link", size="sm", extra=typo("typography", "appasmallsb")),
             dir="row", justify="space-between", align="center", title="Kopf")


def stat_card(label, value, sub, dot=False):
    val = H(value, "appah3sm", "secondary", tag="p")
    if dot:
        val = C(W("icon", merge({"selected_icon": icon("fas fa-circle"), "size": px(10), "view": "default"},
                                color("primary_color", "appagreen"))),
                val, dir="row", align="center", gap=8, title="Status")
    return C(H(label, "appalabel", "text", tag="p"),
             C(val, mt=12, title="Wert"),
             H(sub, "appasmall", "text", tag="p", mt=8),
             bg="appawhite", border=True, radius=RADIUS, pad=(20,), tag="article", title=label)


def doc_row(ico, title, meta, last=False):
    return C(ICON(ico, "primary", 20, view="stacked", bgc="appalight", pad=10, shape="square", radius=RADIUS),
             C(H(title, "appasmallsb", "secondary", tag="p"), H(meta, "appaxs", "text", tag="p", mt=4),
               grow=True, title="Titel"),
             ICON("fas fa-download", "primary", 18, url="#"),
             dir="row", align="center", gap=12, pad=(16,),
             **({} if last else {"bsides": (0, 0, 1, 0)}), tag="article", title=title)


def dashboard_header():
    welcome_user = C(
        IMAGE(IMG_AVATAR, "Porträt", extra={
            "width": px(36), "height": px(36), "object-fit": "cover",
            "image_border_radius": dims(50, unit="%"),
            "__dynamic__": {"image": tag_shortcode("user-profile-picture", {})}}),
        C(H("Anna Berger", "appasmallsb", "secondary", tag="p",
            extra={"__dynamic__": {"title": tag_shortcode("user-info", {"type": "display_name"})}}),
          H("Ordentliches Mitglied", "appaxs", "text", tag="p"), title="Name"),
        dir="row", align="center", gap=12, hide=("mobile",), title="Benutzer")
    return C(
        C(logo(),
          H("Mitgliederbereich", "appasmallsb", "text", tag="span",
            extra=merge({"_border_border": "solid", "_border_width": dims(0, 0, 0, 1),
                         "_padding": dims(0, 0, 0, 16), "hide_mobile": "hidden-mobile"},
                        color("_border_color", "appaborder"))),
          dir="row", align="center", gap=16, title="Marke"),
        C(ICON("far fa-bell", "secondary", 20, url="#"), welcome_user,
          dir="row", align="center", gap=16, title="Aktionen"),
        boxed=1232, dir="row", justify="space-between", align="center", pad=(16, 28), pad_m=(16, 20, 16, 20),
        bg="appawhite", bsides=(0, 0, 1, 0), tag="header", title="Header Mitgliederbereich")


def tag_shortcode(name, settings, tid=None):
    from urllib.parse import quote
    tid = tid or hashlib.md5((name + json.dumps(settings)).encode()).hexdigest()[:7]
    enc = quote(json.dumps(settings, separators=(",", ":"), ensure_ascii=False), safe="")
    return '[elementor-tag id="%s" name="%s" settings="%s"]' % (tid, name, enc)


def page_dashboard():
    sidebar = C(
        C(dash_nav_item("Übersicht", "fas fa-th-large", active=True),
          dash_nav_item("Veranstaltungen", "far fa-calendar"),
          dash_nav_item("Dokumente", "far fa-folder"),
          dash_nav_item("Profil", "far fa-user"),
          gap=4, title="Navigation"),
        C(pad=(0,), grow=True, title="Abstand"),
        C(ICON("far fa-question-circle", "appagreen", 20),
          H("Brauchen Sie Hilfe?", "appasmallsb", "secondary", tag="p", mt=12),
          H("Kontakt aufnehmen", "appasmallsb", "text", tag="p", mt=4, url=URL["contact"]),
          bg="appamuted", radius=RADIUS, pad=(16,), title="Hilfe"),
        C(BTN("Zur öffentlichen Website", URL["home"], "ghost", ico="fas fa-sign-out-alt", icon_left=True,
              full=True, extra=merge({"text_padding": dims(10, 16), "icon_indent": px(12), "content_align": "start"},
                          typo("typography", "appasmall"))), mt=20, title="Logout"),
        extra={"width": px(240), "_flex_size": "none"}, pad=(28, 16), bsides=(0, 1, 0, 0),
        minh=760, hide=("tablet", "mobile"), tag="aside", title="Sidebar")

    welcome = C(
        C(eyebrow("Mitgliederbereich"),
          H("Willkommen zurück, Anna", "appah1dash", "secondary", tag="h1", mt=8,
            extra={"__dynamic__": {"title": tag_shortcode(
                "user-info", {"type": "first_name", "before": "Willkommen zurück, ", "fallback": "Willkommen zurück"})}}),
          P("Hier finden Sie Ihre wichtigsten Informationen auf einen Blick.", "text", "text", mt=8),
          title="Begrüßung"),
        BTN("Mitgliedschaft aktiv", "#", "ghost", ico="far fa-check-circle", size="xs", icon_left=True,
            extra=merge(bg("appalight"), color("button_text_color", "secondary"),
                        color("icon_color", "appagreen"), typo("typography", "appasmallsb"),
                        {"text_padding": dims(12, 16)})),
        dir="row", dir_m="column", justify="space-between", align="flex-end", align_m="stretch",
        gap=16, title="Kopf")

    mobile_tabs = C(
        *[BTN(t, "#", "ghost", size="xs", extra=merge(
            {"text_padding": dims(12, 16), "border_radius": dims(0)},
            typo("typography", "appasmallsb" if i == 0 else "appasmall"),
            color("button_text_color", "secondary" if i == 0 else "text"),
            ({"border_border": "solid", "border_width": dims(0, 0, 2, 0)} if i == 0 else {}),
            color("border_color", "primary") if i == 0 else {}))
          for i, t in enumerate(["Übersicht", "Veranstaltungen", "Dokumente", "Profil"])],
        dir="row", gap=4, mt=28, bsides=(0, 0, 1, 0), hide=("desktop",),
        extra={"overflow": "auto", "flex_wrap": "nowrap"}, title="Tabs mobil")

    membership = C(
        dash_section_head("Meine Mitgliedschaft", "Details anzeigen"),
        C(stat_card("Status", "Aktiv", "Mitglied seit 2023", dot=True),
          stat_card("Mitgliedschaftsart", "Ordentliche Mitgliedschaft", "Jahresbeitrag entrichtet"),
          stat_card("Nächste Verlängerung", "01. Jänner 2027", "Automatische Verlängerung"),
          grid=(3, 3, 1), gap=16, mt=16, title="Kennzahlen"),
        C(BTN('Mitgliedsbestätigung herunterladen <span style="font-size:14px;font-weight:400;'
              'color:var(--e-global-color-text);margin-left:8px;">PDF</span>', "#", "outline-bg",
              ico="fas fa-download", icon_left=True, size="sm",
              extra=color("icon_color", "primary")), dir="row", mt=16),
        mt=32, title="Meine Mitgliedschaft")

    ev1 = C(
        C(H("14", "appaxssb", "secondary", tag="p", align="center"),
          H("OKT", "appasmallsb", "secondary", tag="p", align="center", extra=typo("typography", "appah3sm")),
          H("2026", "appaxs", "text", tag="p", align="center"),
          bg="appalight", radius=RADIUS, pad=(12, 16), shrink0=True, title="Datum"),
        C(C(H("Exklusiv für Mitglieder", "appaxssb", "appaongreen", tag="span",
              extra=merge(bg("appagreen", "_background"), {"_padding": dims(4, 10), "_border_radius": dims(999),
                                                          "_element_width": "auto"})),
            H("Online", "appaxssb", "text", tag="span",
              extra=merge(bg("appamuted", "_background"), {"_padding": dims(4, 10), "_border_radius": dims(999),
                                                          "_element_width": "auto"})),
            dir="row", wrap=True, gap=8, title="Badges"),
          H("Werkstattgespräch: Stärkenorientierte Führung", "appah3sm", "secondary", tag="h3", mt=12),
          H("18:00–19:30 Uhr · Live-Stream via Zoom", "appasmall", "text", tag="p", mt=4),
          grow=True, title="Inhalt"),
        C(BTN("Anmelden", "#", size="xs", extra=typo("typography", "appasmallsb")), title="Aktion"),
        dir="row", dir_m="column", gap=20, bg="appawhite", border=True, radius=RADIUS, pad=(20,),
        tag="article", title="Event")
    ev2 = C(
        ICON("fas fa-play", "secondary", 24, view="stacked", bgc="accent", pad=16, shape="square", radius=RADIUS),
        C(H("AUFZEICHNUNG", "appaxssb", "text", tag="span"),
          H("Positive Psychologie im Berufsalltag", "appah3sm", "secondary", tag="h3", mt=8),
          H("Vortrag vom 18. Juni 2026 · 52 Minuten", "appasmall", "text", tag="p", mt=4),
          grow=True, title="Inhalt"),
        C(BTN("Ansehen", "#", "outline", size="xs", extra=typo("typography", "appasmallsb")), title="Aktion"),
        dir="row", dir_m="column", gap=20, bg="appawhite", border=True, radius=RADIUS, pad=(20,),
        tag="article", title="Aufzeichnung")

    events = C(dash_section_head("Veranstaltungen für Mitglieder", "Alle Veranstaltungen"),
               C(ev1, ev2, gap=16, mt=16, title="Liste"), title="Veranstaltungen")
    docs = C(dash_section_head("Dokumente & Downloads", "Alle"),
             C(doc_row("far fa-file-alt", "Tagungsunterlagen 2026", "PDF · 4,2 MB"),
               doc_row("fas fa-book-open", "APPA Jahresbericht 2025", "PDF · 1,8 MB"),
               doc_row("far fa-file", "Statuten der APPA", "PDF · 320 KB", last=True),
               bg="appawhite", border=True, radius=RADIUS, mt=16, overflow_hidden=True, title="Dokumente"),
             title="Dokumente")
    two_col = C(events, docs, grid=(span_cols([3, 2], 28, total=5), 1, 1), gap=28, mt=36,
                title="Veranstaltungen & Dokumente")

    profile = C(
        C(C(H("Mein Profil", "appah3", "secondary", tag="h2"),
            P("Ihre Kontaktdaten und Sichtbarkeit im öffentlichen Mitgliederverzeichnis.", "appasmall", "text", mt=4),
            title="Text"),
          BTN("Profil bearbeiten", "#"),
          dir="row", dir_m="column", justify="space-between", align="center", align_m="stretch",
          gap=20, title="Kopf"),
        C(C(H("Im öffentlichen Verzeichnis sichtbar", "appabodysb", "secondary", tag="p"),
            P("Sie können von Interessierten und Fachkolleg:innen gefunden werden.", "appasmall", "text", mt=4),
            title="Text"),
          W("icon", merge({"selected_icon": icon("fas fa-toggle-on"), "size": px(40), "view": "default"},
                          color("primary_color", "appagreen"))),
          dir="row", justify="space-between", align="center", gap=16, mt=24, pad=(20, 0, 0, 0),
          bsides=(1, 0, 0, 0), title="Sichtbarkeit"),
        bg="appalight", radius=RADIUS, pad=(24,), mt=36, title="Mein Profil")

    main = C(welcome, mobile_tabs, membership, two_col, profile,
             grow=True, pad=(36,), pad_t=(28, 20, 96, 20), pad_m=(28, 20, 96, 20), title="Inhalt")

    body = C(sidebar, main, boxed=1232, dir="row", pad=(0,), title="Dashboard")

    bottom_nav = C(
        *[BTN(t, "#", "ghost", ico=ico, size="xs", extra=merge(
            {"text_padding": dims(10, 4), "align": "justify", "icon_align": "top", "icon_indent": px(4),
             "content_align": "center"},
            typo("typography", "appaxssb" if i == 0 else "appaxs"),
            color("button_text_color", "primary" if i == 0 else "text")))
          for i, (t, ico) in enumerate([("Übersicht", "fas fa-th-large"), ("Events", "far fa-calendar"),
                                        ("Dokumente", "far fa-folder"), ("Profil", "far fa-user")])],
        grid=(4, 4, 4), gap=0, bg="appawhite", bsides=(1, 0, 0, 0), sticky="bottom",
        sticky_on=["tablet", "mobile"], hide=("desktop",), z=60, title="Bottom-Navigation mobil")
    return [dashboard_header(), body, bottom_nav]


# ---------------------------------------------------------------------------
# Kit (Website-Einstellungen)
# ---------------------------------------------------------------------------

def kit_settings():
    def entries(items, kind):
        out = []
        for cid, title, val in items:
            if kind == "color":
                out.append({"_id": cid, "title": title, "color": val})
            else:
                e = {"_id": cid, "title": title}
                e.update(val)
                out.append(e)
        return out

    s = {
        "system_colors": entries(SYSTEM_COLORS, "color"),
        "custom_colors": entries(CUSTOM_COLORS, "color"),
        "system_typography": entries(SYSTEM_TYPO, "typo"),
        "custom_typography": entries(CUSTOM_TYPO, "typo"),
        "default_generic_fonts": "Sans-serif",
        "site_name": "APPA",
        "site_description": "Austrian Positive Psychology Association",
        # Layout: max-w-6xl (72rem) mit px-6 -> Inhalt 1104px + 24px Rand
        "container_width": px(1104),
        "container_padding": dims(0, 24),
        "space_between_widgets": gap(0),
        "page_title_selector": "h1.entry-title",
        "viewport_mobile": 767,
        "viewport_tablet": 1024,
        # Theme Style
        "body_background_background": "classic",
        "body_background_color": "#FFFFFF",
        "button_border_radius": dims(RADIUS),
        "button_padding": dims(13, 24),
        "form_field_border_border": "solid",
        "form_field_border_width": dims(1),
        "form_field_border_radius": dims(RADIUS),
        "form_field_padding": dims(12, 16),
        "form_field_focus_border_border": "solid",
        "form_field_focus_border_width": dims(1),
    }
    s = merge(s,
              color("body_color", "secondary"), typo("body_typography", "text"),
              color("link_normal_color", "primary"), color("link_hover_color", "secondary"),
              color("h1_color", "secondary"), typo("h1_typography", "appah1page"),
              color("h2_color", "secondary"), typo("h2_typography", "appah2"),
              color("h3_color", "secondary"), typo("h3_typography", "appah3"),
              color("h4_color", "secondary"), typo("h4_typography", "appah3sm"),
              color("h5_color", "secondary"), color("h6_color", "secondary"),
              typo("button_typography", "appabtn"), color("button_text_color", "appawhite"),
              {"button_background_background": "classic"}, color("button_background_color", "primary"),
              color("button_hover_text_color", "appawhite"),
              {"button_hover_background_background": "classic"},
              {"button_hover_background_color": "#BF2C20"},
              color("form_label_color", "secondary"), typo("form_label_typography", "appasmallsb"),
              typo("form_field_typography", "text"), color("form_field_text_color", "secondary"),
              color("form_field_background_color", "appawhite"),
              color("form_field_border_color", "appaborder"),
              color("form_field_focus_border_color", "appaocean"))
    # Globale Referenzen im Kit selbst werden aufgelöst -> konkrete Werte zusätzlich setzen
    return s


# ---------------------------------------------------------------------------
# Ausgabe
# ---------------------------------------------------------------------------

def finalize(elements, seed):
    counter = [0]

    def walk(el, depth, parent_row=False):
        counter[0] += 1
        el = copy.deepcopy(el)
        st = el.get("settings", {})
        if parent_row and el["elType"] == "container" and "width" not in st:
            # Container in einer Zeile: Inhaltsbreite statt 100 % (wie ein Flex-Item in Tailwind)
            st["width"] = custom("auto")
        row = (el["elType"] == "container" and st.get("container_type") != "grid"
               and st.get("flex_direction") == "row")
        el["id"] = hashlib.md5(("%s:%d" % (seed, counter[0])).encode()).hexdigest()[:7]
        if el["elType"] == "container":
            el["isInner"] = depth > 0
        el["elements"] = [walk(c, depth + 1, row) for c in el.get("elements", [])]
        return {k: el[k] for k in ("id", "elType", "isInner", "widgetType", "settings", "elements") if k in el}

    return [walk(e, 0) for e in elements]


PAGES = [
    # slug, Titel, Builder, Typ, Seiten-Template, Datei
    ("home", "APPA – Home", page_home, "page", "elementor_header_footer", "page-home"),
    ("about", "APPA – Über uns", page_about, "page", "elementor_header_footer", "page-ueber-uns"),
    ("events", "APPA – Veranstaltungen", page_events, "page", "elementor_header_footer", "page-veranstaltungen"),
    ("membership", "APPA – Mitgliedschaft", page_membership, "page", "elementor_header_footer", "page-mitgliedschaft"),
    ("contact", "APPA – Kontakt", page_contact, "page", "elementor_header_footer", "page-kontakt"),
    ("login", "APPA – Mitgliederbereich Login", page_login, "page", "elementor_canvas", "page-mitgliederbereich-login"),
    ("dashboard", "APPA – Mitgliederbereich Übersicht", page_dashboard, "page", "elementor_canvas",
     "page-mitgliederbereich-uebersicht"),
]
THEME = [
    ("header", "APPA – Header", tpl_header, "header", "header-hauptnavigation",
     [{"type": "include", "name": "general", "sub_name": "", "sub_id": ""}]),
    ("footer", "APPA – Footer (Home)", tpl_footer_home, "footer", "footer-home",
     [{"type": "include", "name": "general", "sub_name": "singular", "sub_id": ""}]),
    ("footer_compact", "APPA – Footer (kompakt)", tpl_footer_compact, "footer", "footer-kompakt", []),
]
SECTIONS = [
    ("cta", "APPA – Block: CTA Mitglied werden", lambda: [cta_green(
        "Mitglied werden bei der APPA",
        "Bleiben Sie mit unserem Newsletter, Forschung und neuen Weiterbildungsangeboten verbunden.",
        "Mehr erfahren", eyebrow_text="Teil der Community werden")], "section", "block-cta-mitglied-werden"),
    ("pagehead", "APPA – Block: Seitenkopf", lambda: [page_header_band(
        "Eyebrow", "Seitentitel", "Kurzer einleitender Text zur Seite.")], "section", "block-seitenkopf"),
]


def template_json(title, ttype, content, page_settings=None):
    return {"content": content, "page_settings": page_settings or [], "version": "0.4",
            "title": title, "type": ttype}


def write_json(path, data):
    with open(path, "w", encoding="utf-8") as fh:
        json.dump(data, fh, ensure_ascii=False, indent=1)
        fh.write("\n")


def main():
    os.makedirs(OUT_TPL, exist_ok=True)
    for f in os.listdir(OUT_TPL):
        if f.endswith(".json"):
            os.remove(os.path.join(OUT_TPL, f))

    built = {}
    for key, title, fn, ttype, tpl, fname in PAGES:
        content = finalize(fn(), fname)
        ps = {"template": tpl, "hide_title": "yes"}
        built[key] = (title, ttype, content, ps, fname)
        write_json(os.path.join(OUT_TPL, fname + ".json"), template_json(title, ttype, content, ps))
    for key, title, fn, ttype, fname, _cond in THEME:
        content = finalize(fn(), fname)
        built[key] = (title, ttype, content, [], fname)
        write_json(os.path.join(OUT_TPL, fname + ".json"), template_json(title, ttype, content))
    for key, title, fn, ttype, fname in SECTIONS:
        content = finalize(fn(), fname)
        write_json(os.path.join(OUT_TPL, fname + ".json"), template_json(title, ttype, content))

    # Alle Einzel-Templates als ZIP (Elementor > Vorlagen > Importieren akzeptiert ZIP)
    with zipfile.ZipFile(os.path.join(HERE, "appa-elementor-templates.zip"), "w", zipfile.ZIP_DEFLATED) as z:
        for f in sorted(os.listdir(OUT_TPL)):
            z.write(os.path.join(OUT_TPL, f), f)

    build_kit(built)
    write_json(os.path.join(HERE, "kit-site-settings.json"), {"settings": kit_settings()})
    print("OK:", len(os.listdir(OUT_TPL)), "Templates")


def build_kit(built):
    """Website-Kit im Format von Elementor > Werkzeuge > Website-Kit importieren."""
    now = datetime(2026, 10, 2, tzinfo=timezone.utc).strftime("%Y-%m-%d %H:%M:%S")
    manifest = {
        "name": "appa",
        "title": "APPA – Austrian Positive Psychology Association",
        "description": "Native Elementor-Pro-Umsetzung des APPA-Mockups (UXMagic).",
        "author": "APPA",
        "version": "3.0",
        "elementor_version": ELEMENTOR_VERSION,
        "created": now,
        "thumbnail": False,
        "site": "https://appa.at",
        "site-settings": {
            "theme": False, "globalColors": True, "globalFonts": True, "themeStyleSettings": True,
            "generalSettings": True, "experiments": False, "customCode": False, "customIcons": False,
            "customFonts": False, "classes": False, "variables": False,
        },
        "templates": {},
        "content": {"page": {}},
        "wp-content": {"page": {}},
        "plugins": [
            {"name": "Elementor", "plugin": "elementor/elementor", "pluginUri": "https://elementor.com/",
             "version": ELEMENTOR_VERSION},
            {"name": "Elementor Pro", "plugin": "elementor-pro/elementor-pro", "pluginUri": "https://elementor.com/",
             "version": ELEMENTOR_VERSION},
        ],
        "taxonomies": {},
    }
    files = {"site-settings.json": {"settings": kit_settings()}}
    tid = 1000
    for key, title, fn, ttype, fname, cond in THEME:
        tid += 1
        t, ty, content, ps, _ = built[key]
        manifest["templates"][str(tid)] = {
            "title": title, "doc_type": ttype, "thumbnail": False,
            "conditions": cond, "location": ttype,
        }
        files["templates/%d.json" % tid] = {"content": content, "page_settings": ps, "version": "0.4",
                                            "title": title, "type": ttype}
    slugs = {"home": "home", "about": "ueber-uns", "events": "veranstaltungen", "membership": "mitgliedschaft",
             "contact": "kontakt", "login": "mitgliederbereich", "dashboard": "mitgliederbereich-uebersicht"}
    page_titles = {"home": "Home", "about": "Über uns", "events": "Veranstaltungen",
                   "membership": "Mitgliedschaft", "contact": "Kontakt", "login": "Mitgliederbereich",
                   "dashboard": "Mitgliederbereich – Übersicht"}
    pid = 100
    for key, *_ in PAGES:
        pid += 1
        _t, _ty, content, ps, _ = built[key]
        manifest["content"]["page"][str(pid)] = {
            "title": page_titles[key], "excerpt": "", "doc_type": "wp-page", "thumbnail": False,
            "url": "https://appa.at/" + ("" if key == "home" else slugs[key] + "/"),
            "terms": [], "show_on_front": key == "home",
        }
        files["content/page/%d.json" % pid] = {"content": content, "settings": ps, "metadata": []}
    files["manifest.json"] = manifest
    with zipfile.ZipFile(os.path.join(HERE, "appa-kit.zip"), "w", zipfile.ZIP_DEFLATED) as z:
        for name, data in files.items():
            z.writestr(name, json.dumps(data, ensure_ascii=False, indent=1))


if __name__ == "__main__":
    main()
