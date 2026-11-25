#!/usr/bin/env python3
"""Generate wordpress/themes/appa/theme.json from tokens/tokens.json.

    python3 scripts/build_theme_json.py

theme.json is generated – change tokens or this script, never the output.
"""
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
T = json.loads((ROOT / "tokens/tokens.json").read_text("utf-8"))
OUT = ROOT / "wordpress/themes/appa/theme.json"


def px(v):
    return f"{v / 16:g}rem"


def fluid(lo, hi):
    """clamp() between 360px and the wide layout width."""
    vmin, vmax = 360, T["layout"]["wide"]
    slope = (hi - lo) / (vmax - vmin)
    base = lo - slope * vmin
    return f"clamp({px(lo)}, {base / 16:.4f}rem + {slope * 100:.4f}vw, {px(hi)})"


def preset(kind, slug):
    return f"var(--wp--preset--{kind}--{slug})"


def custom(*path):
    return "var(--wp--custom--" + "--".join(path) + ")"


C = lambda s: preset("color", s)
FS = lambda s: preset("font-size", s)
SP = lambda s: preset("spacing", s)

palette = [{"slug": s, "name": T["color_name"][s], "color": v} for s, v in T["color"].items()]
gradients = [{"slug": s, "name": g["name"], "gradient": g["value"]} for s, g in T["gradient"].items()]
font_sizes = []
for slug, f in T["font_size"].items():
    entry = {"slug": slug, "name": f["name"], "size": px(f["max"])}
    if f["min"] != f["max"]:
        entry["fluid"] = {"min": px(f["min"]), "max": px(f["max"])}
    else:
        entry["fluid"] = False
    font_sizes.append(entry)
spacing = [{"slug": s, "name": f"{v}px", "size": px(v)} for s, v in T["space"].items()]
spacing += [{"slug": s, "name": f["name"], "size": fluid(f["min"], f["max"])} for s, f in T["space_fluid"].items()]

font = T["font"]
font_faces = [{
    "fontFamily": font["family"], "fontStyle": f["style"], "fontWeight": "100 900", "fontDisplay": "swap",
    "src": [f"file:./assets/fonts/{f['file']}"], "unicodeRange": f["range"],
} for f in font["files"]]

lh = T["line_height"]
focus = {"outline": {"color": C("blue"), "style": "solid", "width": "2px", "offset": "2px"}}

theme = {
    "$schema": "https://schemas.wp.org/trunk/theme.json",
    "version": 3,
    "settings": {
        "appearanceTools": True,
        "useRootPaddingAwareAlignments": True,
        "layout": {"contentSize": px(T["layout"]["content"]), "wideSize": px(T["layout"]["wide"])},
        "color": {
            "palette": palette, "gradients": gradients,
            "custom": False, "customGradient": False, "customDuotone": False,
            "defaultPalette": False, "defaultGradients": False, "defaultDuotone": False,
            "duotone": [], "link": True,
        },
        "typography": {
            "fluid": {"minViewportWidth": "360px", "maxViewportWidth": px(T["layout"]["wide"])},
            "customFontSize": False, "defaultFontSizes": False, "dropCap": False,
            "fontStyle": True, "fontWeight": True, "letterSpacing": False, "textDecoration": False,
            "fontFamilies": [{"slug": "inter", "name": "Inter", "fontFamily": font["stack"], "fontFace": font_faces}],
            "fontSizes": font_sizes,
        },
        "spacing": {
            # No px: patterns use rem and % only; sizes come from the presets.
            "units": ["rem", "%"], "defaultSpacingSizes": False, "spacingSizes": spacing,
            "customSpacingSize": False,
        },
        "shadow": {"defaultPresets": False,
                   "presets": [{"slug": s, "name": v["name"], "shadow": v["value"]} for s, v in T["shadow"].items()]},
        # appearanceTools would allow free borders per block; the design sets borders only through
        # block styles (styles/blocks/*.json) and elements, which stay unaffected by these switches.
        "border": {"color": False, "radius": False, "style": False, "width": False},
        "custom": {
            "radius": {k: px(v) if v < 999 else "999px" for k, v in T["radius"].items()},
            "border-width": {k: f"{v}px" for k, v in T["border_width"].items()},
            "line-height": lh,
            "letter-spacing": T["letter_spacing"],
            "transition": "150ms ease",
            # Width of the member area (header and two-column layout), wider than the content sections.
            "layout": {"dashboard": px(T["layout"]["dashboard"])},
        },
        "blocks": {
            "core/button": {"border": {"radius": False}},
        },
    },
    "styles": {
        "color": {"background": C("white"), "text": C("slate")},
        "typography": {"fontFamily": preset("font-family", "inter"), "fontSize": FS("base"), "lineHeight": str(lh["body"])},
        "spacing": {"blockGap": SP("6"), "padding": {"left": SP("gutter"), "right": SP("gutter")}},
        "elements": {
            "heading": {"color": {"text": C("navy")},
                        "typography": {"fontWeight": "700", "lineHeight": str(lh["heading"])}},
            "h1": {"typography": {"fontSize": FS("huge"), "lineHeight": str(lh["tight"])}},
            "h2": {"typography": {"fontSize": FS("xxx-large")}},
            "h3": {"typography": {"fontSize": FS("xl"), "lineHeight": str(lh["snug"])}},
            "h4": {"typography": {"fontSize": FS("base"), "lineHeight": str(lh["snug"])}},
            # Darker red for text links: plain red is too weak on the light section colours (WCAG 1.4.3).
            "link": {"color": {"text": C("red-dark")}, "typography": {"textDecoration": "underline"},
                     ":hover": {"color": {"text": C("navy")}},
                     ":focus-visible": focus},
            "button": {
                "color": {"background": C("red"), "text": C("white")},
                "border": {"radius": custom("radius", "lg"), "width": "0"},
                "spacing": {"padding": {"top": SP("3"), "bottom": SP("3"), "left": SP("5"), "right": SP("5")}},
                "typography": {"fontSize": FS("sm"), "fontWeight": "600", "lineHeight": str(lh["snug"]),
                               "textDecoration": "none"},
                ":hover": {"color": {"background": C("red-dark"), "text": C("white")}},
                ":focus-visible": focus,
            },
            "caption": {"color": {"text": C("slate")}, "typography": {"fontSize": FS("sm")}},
        },
        "blocks": {
            "core/navigation": {"typography": {"fontSize": FS("sm"), "fontWeight": "600"},
                                "color": {"text": C("navy")},
                                "elements": {"link": {"typography": {"textDecoration": "none"},
                                                      ":hover": {"color": {"text": C("red")}}}}},
            "core/site-title": {"typography": {"fontSize": FS("xl"), "fontWeight": "700"}, "color": {"text": C("navy")},
                                "elements": {"link": {"color": {"text": C("navy")}, "typography": {"textDecoration": "none"}}}},
            "core/quote": {"typography": {"fontSize": FS("xxx-large"), "fontWeight": "600", "lineHeight": str(lh["heading"])},
                           "color": {"text": C("navy")},
                           "border": {"width": "0"},
                           "elements": {"cite": {"typography": {"fontSize": FS("sm"), "fontWeight": "400", "fontStyle": "normal"},
                                                 "color": {"text": C("slate")}}}},
            "core/separator": {"color": {"text": C("line")}, "border": {"width": "1px"}},
            "core/list": {"spacing": {"padding": {"left": SP("5")}}},
            "core/details": {"border": {"bottom": {"color": C("line"), "width": "1px", "style": "solid"}},
                             "spacing": {"padding": {"top": SP("4"), "bottom": SP("4")}}},
            "core/post-title": {"color": {"text": C("navy")},
                                "elements": {"link": {"color": {"text": C("navy")}, "typography": {"textDecoration": "none"},
                                                      ":hover": {"color": {"text": C("red")}}}}},
            "core/image": {"border": {"radius": custom("radius", "lg")}},
        },
    },
    "templateParts": [
        {"name": "header", "title": "Header", "area": "header"},
        {"name": "footer", "title": "Footer", "area": "footer"},
        {"name": "header-member", "title": "Header Mitgliederbereich (Login)", "area": "header"},
        {"name": "footer-member", "title": "Footer Mitgliederbereich", "area": "footer"},
        {"name": "header-dashboard", "title": "Header Mitgliederbereich (Übersicht)", "area": "header"},
        {"name": "member-sidebar", "title": "Mitgliederbereich: Navigation", "area": "uncategorized"},
    ],
    "customTemplates": [
        {"name": "page-full", "title": "Seite ohne Titel (Abschnitte)", "postTypes": ["page"]},
        {"name": "page-member", "title": "Mitgliederbereich: Login", "postTypes": ["page"]},
        {"name": "page-member-dashboard", "title": "Mitgliederbereich: Übersicht", "postTypes": ["page"]},
    ],
}

OUT.parent.mkdir(parents=True, exist_ok=True)
OUT.write_text(json.dumps(theme, ensure_ascii=False, indent="\t") + "\n", "utf-8")
print("->", OUT.relative_to(ROOT))
