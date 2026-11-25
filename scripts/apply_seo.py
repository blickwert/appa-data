#!/usr/bin/env python3
"""Sets the Yoast SEO title, description, focus keyword and noindex of pages and events from scripts/seo.json.

    WP_URL=https://… WP_USER=… WP_APP_PASSWORD=… [WP_BRIDGE_KEY=…] python3 scripts/apply_seo.py [--dry-run]

Writes post meta through the BW WP Bridge (bw-bridge/v1/meta/{id}); Yoast's meta is not writable through the core
REST API.
"""

import base64
import json
import os
import sys
import urllib.error
import urllib.parse
import urllib.request

HERE = os.path.dirname(os.path.abspath(__file__))
BASE = os.environ["WP_URL"].rstrip("/") + "/wp-json/"
AUTH = "Basic " + base64.b64encode(f"{os.environ['WP_USER']}:{os.environ['WP_APP_PASSWORD']}".encode()).decode()
HEADERS = {"Authorization": AUTH, "Content-Type": "application/json"}
if os.environ.get("WP_BRIDGE_KEY"):
    HEADERS["X-BW-Bridge-Key"] = os.environ["WP_BRIDGE_KEY"]
DRY = "--dry-run" in sys.argv


def api(method, path, data=None):
    req = urllib.request.Request(BASE + path, method=method, headers=HEADERS)
    body = json.dumps(data).encode() if data is not None else None
    try:
        with urllib.request.urlopen(req, body, timeout=60) as res:
            return json.loads(res.read() or b"null")
    except urllib.error.HTTPError as e:
        sys.exit(f"{method} {path}: HTTP {e.code} {e.read()[:300]!r}")


def meta_for(entry):
    meta = {}
    if "title" in entry:
        meta["_yoast_wpseo_title"] = entry["title"]
    if "description" in entry:
        meta["_yoast_wpseo_metadesc"] = entry["description"]
    if "focus" in entry:
        meta["_yoast_wpseo_focuskw"] = entry["focus"]
    meta["_yoast_wpseo_meta-robots-noindex"] = "1" if entry.get("noindex") else ""
    return meta


def main():
    with open(os.path.join(HERE, "seo.json"), encoding="utf-8") as f:
        seo = json.load(f)
    for route, entries in (("pages", seo["pages"]), ("appa_event", seo.get("events", {}))):
        for slug, entry in entries.items():
            found = api("GET", f"wp/v2/{route}?slug={urllib.parse.quote(slug)}&_fields=id&status=publish")
            if not found:
                print(f"– {route}/{slug}: nicht gefunden")
                continue
            post_id = found[0]["id"]
            meta = meta_for(entry)
            result = api("POST", f"bw-bridge/v1/meta/{post_id}", {"set": meta, "delete": [], "dry_run": DRY})
            changed = [k for k, c in result.get("changes", {}).items() if c.get("old") != c.get("new")]
            print(f"✓ {route}/{slug} ({post_id}): {', '.join(changed) or 'unverändert'}")
    print("Probelauf, nichts gespeichert" if DRY else "fertig")


if __name__ == "__main__":
    main()
