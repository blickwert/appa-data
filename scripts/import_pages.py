#!/usr/bin/env python3
"""Create or update the APPA pages, the front page setting and the main menu via the WordPress REST API.

    WP_URL=https://… WP_USER=… WP_APP_PASSWORD=… [WP_BRIDGE_KEY=…] python3 scripts/import_pages.py [--dry-run]

The page content is taken from the theme's page patterns ("appa/page-<slug>"), rendered by WordPress itself,
so internal links and image URLs match the target installation. Existing pages are updated in place
(a revision is kept by WordPress); nothing else is deleted.

If the BW WP Bridge on the target requires its connection key for all REST requests, set WP_BRIDGE_KEY;
it is sent in the header X-BW-Bridge-Key (hyphens, not underscores).
"""
import base64
import json
import os
import sys
import urllib.error
import urllib.request

PAGES = [  # slug, title, pattern
    ("startseite", "Startseite", "appa/page-home"),
    ("ueber-uns", "Über uns", "appa/page-ueber-uns"),
    ("positive-psychologie", "Positive Psychologie", "appa/page-positive-psychologie"),
    ("mitgliedschaft", "Mitgliedschaft", "appa/page-mitgliedschaft"),
    ("kontakt", "Kontakt", "appa/page-kontakt"),
    ("impressum", "Impressum", "appa/page-impressum"),
    ("datenschutz", "Datenschutz", "appa/page-datenschutz"),
]
MENU = ["ueber-uns", "positive-psychologie", "veranstaltungen", "mitgliedschaft"]  # Kontakt/Impressum: footer
DRY = "--dry-run" in sys.argv

BASE = os.environ["WP_URL"].rstrip("/") + "/wp-json/"
AUTH = "Basic " + base64.b64encode(f"{os.environ['WP_USER']}:{os.environ['WP_APP_PASSWORD']}".encode()).decode()
HEADERS = {"Authorization": AUTH, "Content-Type": "application/json"}
if os.environ.get("WP_BRIDGE_KEY"):
    HEADERS["X-BW-Bridge-Key"] = os.environ["WP_BRIDGE_KEY"]


def api(method, path, data=None):
    req = urllib.request.Request(BASE + path, method=method, headers=HEADERS)
    body = json.dumps(data).encode() if data is not None else None
    try:
        with urllib.request.urlopen(req, body, timeout=60) as res:
            return json.loads(res.read() or b"null")
    except urllib.error.HTTPError as e:
        sys.exit(f"{method} {path}: HTTP {e.code} {e.read()[:300]!r}")


def main():
    patterns = {p["name"]: p["content"] for p in api("GET", "wp/v2/block-patterns/patterns")}
    ids = {}
    for slug, title, pattern in PAGES:
        if pattern not in patterns:
            sys.exit(f"Pattern {pattern} fehlt – ist das Theme 'appa' aktiv?")
        found = api("GET", f"wp/v2/pages?slug={slug}&status=publish,draft,private&_fields=id")
        data = {"title": title, "slug": slug, "status": "publish", "template": "page-full",
                "content": patterns[pattern]}
        if DRY:
            print(("update" if found else "create"), slug)
            continue
        page = api("POST", f"wp/v2/pages/{found[0]['id']}" if found else "wp/v2/pages", data)
        ids[slug] = page["id"]
        print(("aktualisiert" if found else "angelegt"), slug, page["id"])
    if DRY:
        return

    api("POST", "wp/v2/settings", {"show_on_front": "page", "page_on_front": ids["startseite"]})

    home = api("GET", "")["home"].rstrip("/")
    links = []
    for slug in MENU:
        if slug == "veranstaltungen":  # archive of the event post type (plugin bw-appa)
            links.append(json.dumps({"label": "Veranstaltungen", "type": "custom", "url": f"{home}/veranstaltungen/",
                                     "kind": "custom"}, ensure_ascii=False).join(("<!-- wp:navigation-link ", " /-->")))
        else:
            title = next(t for s, t, _ in PAGES if s == slug)
            links.append(json.dumps({"label": title, "type": "page", "id": ids[slug], "kind": "post-type"},
                                    ensure_ascii=False).join(("<!-- wp:navigation-link ", " /-->")))
    menus = api("GET", "wp/v2/navigation?per_page=100&status=publish,draft&_fields=id,title")
    menu = next((m for m in menus if m["title"]["rendered"] == "Hauptmenü"), None)
    data = {"title": "Hauptmenü", "status": "publish", "content": "\n".join(links)}
    menu = api("POST", f"wp/v2/navigation/{menu['id']}" if menu else "wp/v2/navigation", data)
    print("Hauptmenü", menu["id"], "– Startseite", ids["startseite"])


if __name__ == "__main__":
    main()
