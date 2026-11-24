"""Small helpers that serialise WordPress block markup for the APPA patterns.

Every helper returns valid, editor-compatible block markup (comment delimiters + saved HTML).
Only presets and block style variations are used – no raw design values.
"""
import json
from html import escape

ARROW = "arrow"
WIDE = "69rem"  # content width of page sections (1104 px, as in the Elementor/Mockup layout)


def _attrs(d):
    d = {k: v for k, v in d.items() if v not in (None, "", {}, [])}
    return (" " + json.dumps(d, ensure_ascii=False, separators=(",", ":"))) if d else ""


def _cls(*names):
    return " ".join(n for n in names if n)


def _indent(s, n=1):
    pad = "\t" * n
    return "\n".join(pad + line if line.strip() else line for line in s.split("\n"))


def join(*parts):
    return "\n\n".join(p for p in parts if p)


def sp(slug):
    return f"var:preset|spacing|{slug}"


def sp_css(slug):
    return f"var(--wp--preset--spacing--{slug})"


def padding(top=None, bottom=None):
    style, css = {}, []
    if top:
        style["top"] = sp(top)
        css.append(f"padding-top:{sp_css(top)}")
    if bottom:
        style["bottom"] = sp(bottom)
        css.append(f"padding-bottom:{sp_css(bottom)}")
    return style, css


def group(inner, *, tag="div", variation=None, cls=None, anchor=None, align=None, layout=None,
          pad=None, gap=None, margin_top=None, inner_html_cls=None):
    attrs, style, css = {}, {}, []
    if tag != "div":
        attrs["tagName"] = tag
    if anchor:
        attrs["anchor"] = anchor
    if align:
        attrs["align"] = align
    classes = _cls(f"is-style-{variation}" if variation else "", cls or "")
    if classes:
        attrs["className"] = classes
    spacing = {}
    if pad:
        p, c = padding(*pad)
        spacing["padding"] = p
        css += c
    if gap:
        spacing["blockGap"] = sp(gap)
    if margin_top:
        spacing["margin"] = {"top": sp(margin_top)}
        css.append(f"margin-top:{sp_css(margin_top)}")
    if spacing:
        style["spacing"] = spacing
    if style:
        attrs["style"] = style
    attrs["layout"] = layout or {"type": "constrained"}
    html_cls = _cls("wp-block-group", f"align{align}" if align else "", classes)
    id_attr = f' id="{anchor}"' if anchor else ""
    style_attr = f' style="{";".join(css)}"' if css else ""
    return (f"<!-- wp:group{_attrs(attrs)} -->\n<{tag}{id_attr} class=\"{html_cls}\"{style_attr}>\n"
            f"{_indent(inner)}\n</{tag}>\n<!-- /wp:group -->")


def section(inner, *, variation=None, anchor=None, cls=None, pad=("section", "section"), gap=None):
    """Full-width page section with the wide content width."""
    return group(inner, tag="section", variation=variation, anchor=anchor, cls=cls, align="full", pad=pad, gap=gap,
                 layout={"type": "constrained", "contentSize": WIDE})


def narrow(inner, gap=None, cls=None):
    """Left-aligned block with the (narrower) global content width, e.g. for long text or accordions."""
    return group(inner, cls=cls, gap=gap, layout={"type": "constrained", "justifyContent": "left"})


def stack(inner, gap=None, cls=None, variation=None):
    """Vertical group in normal flow (no flex: nested grids with container queries need a definite width)."""
    return group(inner, variation=variation, cls=cls, gap=gap, layout={"type": "default"})


def row(inner, justify="space-between", align="center", wrap=True, gap=None, cls=None):
    layout = {"type": "flex", "flexWrap": "wrap" if wrap else "nowrap", "justifyContent": justify,
              "verticalAlignment": align}
    return group(inner, layout=layout, gap=gap, cls=cls)


def grid(inner, columns=3, min_width="18rem", gap="6", cls=None):
    return group(inner, layout={"type": "grid", "columnCount": columns, "minimumColumnWidth": min_width}, gap=gap,
                 cls=cls)


def card(inner, variation="card", gap="4", cls=None):
    return group(inner, variation=variation, gap=gap, cls=cls, layout={"type": "default"})


def heading(text, level=2, size=None, cls=None, anchor=None):
    attrs = {"level": level} if level != 2 else {}
    if anchor:
        attrs["anchor"] = anchor
    if cls:
        attrs["className"] = cls
    if size:
        attrs["fontSize"] = size
    html_cls = _cls("wp-block-heading", cls, f"has-{size}-font-size" if size else "")
    id_attr = f' id="{anchor}"' if anchor else ""
    return f"<!-- wp:heading{_attrs(attrs)} -->\n<h{level}{id_attr} class=\"{html_cls}\">{text}</h{level}>\n<!-- /wp:heading -->"


def p(text, variation=None, size=None, cls=None, color=None, align=None):
    attrs = {}
    if align:
        attrs["align"] = align
    classes = _cls(f"is-style-{variation}" if variation else "", cls or "")
    if classes:
        attrs["className"] = classes
    if color:
        attrs["textColor"] = color
    if size:
        attrs["fontSize"] = size
    html_cls = _cls(f"has-text-align-{align}" if align else "", classes,
                    f"has-{color}-color has-text-color" if color else "", f"has-{size}-font-size" if size else "")
    cls_attr = f' class="{html_cls}"' if html_cls else ""
    return f"<!-- wp:paragraph{_attrs(attrs)} -->\n<p{cls_attr}>{text}</p>\n<!-- /wp:paragraph -->"


def eyebrow(text):
    return p(text, variation="eyebrow")


def button(text, url, variation=None, bindings=None, full=False):
    attrs = {}
    if full:
        attrs["width"] = 100
    if bindings:
        attrs["metadata"] = {"bindings": bindings}
    if variation:
        attrs["className"] = f"is-style-{variation}"
    href = f' href="{url}"' if url else ""
    ext = ' target="_blank" rel="noreferrer noopener"' if url and url.startswith("http") and "appa" not in url else ""
    width_cls = "has-custom-width wp-block-button__width-100" if full else ""
    return (f"<!-- wp:button{_attrs(attrs)} -->\n<div class=\"{_cls('wp-block-button', width_cls, attrs.get('className'))}\">"
            f"<a class=\"wp-block-button__link wp-element-button\"{href}{ext}>{text}</a></div>\n<!-- /wp:button -->")


def buttons(*items, justify=None, cls=None):
    attrs = {"layout": {"type": "flex", "justifyContent": justify}} if justify else {}
    if cls:
        attrs["className"] = cls
    cls = _cls("wp-block-buttons", cls, f"is-content-justification-{justify}" if justify else "")
    return f"<!-- wp:buttons{_attrs(attrs)} -->\n<div class=\"{cls}\">\n{_indent(join(*items))}\n</div>\n<!-- /wp:buttons -->"


def arrow(text, url):
    return buttons(button(text, url, ARROW))


def icon(name, size=None, tone=None, shape=None):
    """Theme icon. shape: None = plain glyph in the tone colour, "circle" = navy glyph on a round tone-coloured disc,
    "tile" = glyph in the tone colour on a light rounded square."""
    attrs = {"icon": name}
    if size:
        attrs["size"] = size
    if tone:
        attrs["tone"] = tone
    if shape:
        attrs["shape"] = shape
    return f"<!-- wp:bw/icon{_attrs(attrs)} /-->"


def cover(php_src, inner, cls=None):
    """Full-width image band (core/cover, no overlay) with content at the bottom left of the section width."""
    attrs = {"url": php_src, "dimRatio": 0, "isUserOverlayColor": True, "contentPosition": "bottom left",
             "isDark": False, "align": "full", "className": cls,
             "layout": {"type": "constrained", "contentSize": WIDE}}
    html_cls = _cls("wp-block-cover alignfull is-light has-custom-content-position is-position-bottom-left", cls)
    return (f"<!-- wp:cover{_attrs(attrs)} -->\n<div class=\"{html_cls}\"><img class=\"wp-block-cover__image-background\" "
            f"alt=\"\" src=\"{php_src}\" data-object-fit=\"cover\"/><span aria-hidden=\"true\" "
            f"class=\"wp-block-cover__background has-background-dim-0 has-background-dim\"></span>"
            f"<div class=\"wp-block-cover__inner-container\">\n{_indent(inner)}\n</div></div>\n<!-- /wp:cover -->")


def image(php_src, alt, cls=None):
    attrs = {"sizeSlug": "full", "linkDestination": "none"}
    if cls:
        attrs["className"] = cls
    return (f"<!-- wp:image{_attrs(attrs)} -->\n<figure class=\"{_cls('wp-block-image size-full', cls)}\">"
            f"<img src=\"{php_src}\" alt=\"{escape(alt)}\"/></figure>\n<!-- /wp:image -->")


def columns(*cols, widths=None, gap="10", valign=None):
    attrs = {"style": {"spacing": {"blockGap": {"top": sp(gap), "left": sp(gap)}}}}
    if valign:
        attrs["verticalAlignment"] = valign
    out = []
    for i, c in enumerate(cols):
        w = widths[i] if widths else None
        cattrs = {"width": w} if w else {}
        style = f' style="flex-basis:{w}"' if w else ""
        vcls = f" is-vertically-aligned-{valign}" if valign else ""
        if valign:
            cattrs["verticalAlignment"] = valign
        out.append(f"<!-- wp:column{_attrs(cattrs)} -->\n<div class=\"wp-block-column{vcls}\"{style}>\n{_indent(c)}\n</div>\n<!-- /wp:column -->")
    vcls = f" are-vertically-aligned-{valign}" if valign else ""
    return f"<!-- wp:columns{_attrs(attrs)} -->\n<div class=\"wp-block-columns{vcls}\">\n{_indent(join(*out))}\n</div>\n<!-- /wp:columns -->"


def ul(items, cls=None):
    attrs = {"className": cls} if cls else {}
    lis = "\n".join(f"<!-- wp:list-item -->\n<li>{i}</li>\n<!-- /wp:list-item -->" for i in items)
    return f"<!-- wp:list{_attrs(attrs)} -->\n<ul class=\"{_cls('wp-block-list', cls)}\">\n{_indent(lis)}\n</ul>\n<!-- /wp:list -->"


def details(summary, *inner):
    return f"<!-- wp:details -->\n<details class=\"wp-block-details\"><summary>{summary}</summary>\n{_indent(join(*inner))}\n</details>\n<!-- /wp:details -->"


def quote(text, cite):
    return (f"<!-- wp:quote {{\"className\":\"is-style-large-centered\"}} -->\n<blockquote class=\"wp-block-quote is-style-large-centered\">"
            f"\n{_indent(p(text))}\n<cite>{cite}</cite></blockquote>\n<!-- /wp:quote -->")


def separator(wide=False):
    if wide:
        return ("<!-- wp:separator {\"className\":\"is-style-wide\"} -->\n<hr class=\"wp-block-separator "
                "has-alpha-channel-opacity is-style-wide\"/>\n<!-- /wp:separator -->")
    return "<!-- wp:separator -->\n<hr class=\"wp-block-separator has-alpha-channel-opacity\"/>\n<!-- /wp:separator -->"


def url(path=""):
    """PHP snippet for an internal URL, resolved by WordPress when the pattern is rendered."""
    return f"<?php echo esc_url( home_url( '/{path}' ) ); ?>"


def asset(path):
    """PHP snippet for a theme asset URL."""
    return f"<?php echo esc_url( get_theme_file_uri( '{path}' ) ); ?>"
