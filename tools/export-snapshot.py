#!/usr/bin/env python3
"""Build tools/content-snapshot.json from the LOCAL site database (Local by Flywheel).
Usage: python3 wp-content/tools/export-snapshot.py
Replaces database IDs with media file names and the local URL with {{home}} so the snapshot
can be applied to any copy of the site by tools/content-update.php."""
import json, os, re, subprocess, sys

R = os.path.expanduser("~/Library/Application Support/Local/run/kh7Wikk8X")
B = os.path.expanduser("~/Library/Application Support/Local/lightning-services/mysql-8.0.35+4/bin/darwin-arm64/bin")
LOCAL = "http://two-stars-tour-and-safari.local"
OUT = sys.argv[1] if len(sys.argv) > 1 else os.path.join(os.path.dirname(os.path.abspath(__file__)), "content-snapshot.json")
DB = os.environ.get("TS_DB", "local")


def sql(q):
    r = subprocess.run([B + "/mysql", f"--socket={R}/mysql/mysqld.sock", "-uroot", "-proot", DB, "-N", "-r", "-e", q],
                       capture_output=True, text=True)
    return r.stdout.rstrip("\n")


def rows(q):
    out = sql(q)
    return [l.split("\t") for l in out.split("\n")] if out else []


def meta(pid, key):
    return sql(f"select meta_value from wp_postmeta where post_id={pid} and meta_key='{key}' limit 1")


att_file = {int(i): f for i, f in rows("select post_id, meta_value from wp_postmeta where meta_key='_wp_attached_file'")}


def att_token(att_id):
    f = att_file.get(int(att_id))
    return "@att:" + os.path.basename(f).lower() if f else None


def tokenize(x):
    if isinstance(x, dict):
        out = {}
        for k, v in x.items():
            if k == "id" and isinstance(v, int) and isinstance(x.get("url"), str) and "/wp-content/uploads/" in x["url"]:
                out[k] = att_token(v) or v
            else:
                out[k] = tokenize(v)
        return out
    if isinstance(x, list):
        return [tokenize(v) for v in x]
    if isinstance(x, str):
        return x.replace(LOCAL, "{{home}}")
    return x


def unser_ids(s):
    return [int(n) for n in re.findall(r"i:\d+;i:(\d+);", s)]


YOAST = ["_yoast_wpseo_title", "_yoast_wpseo_metadesc", "_yoast_wpseo_focuskw"]
snap = {"generated": sql("select now()"), "attachments": [], "alts": {}, "posts": [], "options": {"twostars_price_currency": "USD"}}

# attachments added in this project (2026/10) and every alt text
new_files = set()
for pid, f in att_file.items():
    if f.startswith("2026/10/"):
        base = os.path.basename(f)
        alt = meta(pid, "_wp_attachment_image_alt")
        snap["attachments"].append({"file": base, "path": f, "alt": alt})
        new_files.add(base.lower())
for pid, f in att_file.items():
    alt = meta(pid, "_wp_attachment_image_alt")
    base = os.path.basename(f).lower()
    if alt and base not in new_files:
        snap["alts"][base] = alt

# pages
pages = {"home": 7, "tours": 25, "destinations": 59, "about": 74, "faqs": 103, "contact": 168, "reviews": 332, "gallery": 349}
for slug, pid in pages.items():
    item = {"type": "page", "slugs": [slug], "meta": {}}
    el = meta(pid, "_elementor_data")
    if el:
        item["elementor"] = tokenize(json.loads(el))
    for k in YOAST:
        v = meta(pid, k)
        if v:
            item["meta"][k] = v
    snap["posts"].append(item)
# booking page: SEO only
snap["posts"].append({"type": "page", "slugs": ["booking"], "meta": {k: meta(61, k) for k in YOAST if meta(61, k)}})

# tours
tours = {
    284: ["sandwich-harbour-tour", "sandwich-harbour-4x4-adventure"],
    285: ["walvis-bay-flamingo-tour", "flamingo-and-pink-lake-tour"],
    286: ["swakopmund-city-tour-dune-7"],
    287: ["spitzkoppe-day-tour"],
    288: ["private-namib-coast-tour", "private-tour"],
}
for pid, slugs in tours.items():
    # fields are multi-line; fetch each separately
    title = sql(f"select post_title from wp_posts where ID={pid}")
    name = sql(f"select post_name from wp_posts where ID={pid}")
    content = sql(f"select post_content from wp_posts where ID={pid}")
    excerpt = sql(f"select post_excerpt from wp_posts where ID={pid}")
    item = {"type": "tour", "slugs": slugs, "fields": {"title": title, "slug": name, "content": content, "excerpt": excerpt}, "meta": {}}
    # multi-line values need a per-key fetch
    keys = [r[0] for r in rows(f"select meta_key from wp_postmeta where post_id={pid} and (meta_key like '\\_twostars\\_tour\\_%' or meta_key in ('_yoast_wpseo_title','_yoast_wpseo_metadesc','_yoast_wpseo_focuskw'))")]
    for k in keys:
        if k in ("_twostars_tour_linked_destinations",):
            continue  # destination IDs differ per site; keep the target's own links
        v = meta(pid, k)
        if k == "_twostars_tour_gallery_images":
            item["meta"][k] = {"_t": "att_list", "files": [os.path.basename(att_file[i]).lower() for i in unser_ids(v) if i in att_file]}
        else:
            item["meta"][k] = v.replace(LOCAL, "{{home}}")
    thumb = meta(pid, "_thumbnail_id")
    if thumb and int(thumb) in att_file:
        item["meta"]["_thumbnail_id"] = att_token(thumb)
    snap["posts"].append(item)

# destinations: thumbnails only
for pid, slug in {278: "sandwich-harbour", 279: "walvis-bay-lagoon", 280: "pink-salt-lake"}.items():
    thumb = meta(pid, "_thumbnail_id")
    if thumb and int(thumb) in att_file:
        snap["posts"].append({"type": "destination", "slugs": [slug], "meta": {"_thumbnail_id": att_token(thumb)}})

# FAQs
for fid in [r[0] for r in rows("select ID from wp_posts where post_type='faq' and post_status='publish' order by menu_order, ID")]:
    snap["posts"].append({
        "type": "faq", "create": True, "status": "publish",
        "slugs": [sql(f"select post_name from wp_posts where ID={fid}")],
        "title": sql(f"select post_title from wp_posts where ID={fid}"),
        "content": sql(f"select post_content from wp_posts where ID={fid}"),
        "menu_order": int(sql(f"select menu_order from wp_posts where ID={fid}") or 0),
        "fields": {"title": sql(f"select post_title from wp_posts where ID={fid}"), "content": sql(f"select post_content from wp_posts where ID={fid}")},
        "meta": {},
    })

# gallery page list lives in an option (attachment IDs): export as file names
opt = sql("select option_value from wp_options where option_name='twostars_gallery_library_images'")
gal_ids = [int(n) for n in re.findall(r"i:\d+;i:(\d+);", opt)] or [int(n) for n in re.findall(r"\d+", opt)]
snap["option_att_lists"] = {"twostars_gallery_library_images": [os.path.basename(att_file[i]).lower() for i in gal_ids if i in att_file]}

# new pages, team and guides (created on the target site if missing)
def export_created(ptype, slugs, status="publish", thumb=True):
    pid = sql(f"select ID from wp_posts where post_type='{ptype}' and post_name='{slugs[0]}' limit 1")
    if not pid:
        return
    item = {
        "type": ptype, "create": True, "status": status, "slugs": slugs,
        "title": sql(f"select post_title from wp_posts where ID={pid}"),
        "content": sql(f"select post_content from wp_posts where ID={pid}"),
        "fields": {
            "title": sql(f"select post_title from wp_posts where ID={pid}"),
            "slug": slugs[0],
            "content": sql(f"select post_content from wp_posts where ID={pid}"),
            "excerpt": sql(f"select post_excerpt from wp_posts where ID={pid}"),
            "status": status,
        },
        "meta": {k: meta(pid, k) for k in YOAST if meta(pid, k)},
    }
    t = meta(pid, "_thumbnail_id")
    if thumb and t and int(t) in att_file:
        item["meta"]["_thumbnail_id"] = att_token(t)
    snap["posts"].append(item)


for slug, alt in [("terms", []), ("privacy", []), ("walvis-bay-shore-excursions", []), ("private-tours", []), ("guides", ["blog"])]:
    export_created("page", [slug] + alt)
export_created("team_member", ["tulimeke-tuli"])
for slug in ("sandwich-harbour-what-to-wear-and-bring", "walvis-bay-cruise-passengers-port-day"):
    export_created("post", [slug], status="draft")

snap["remove_media"] = ["swakopmund.jpg"]  # Tuli asked for this photo to be removed
snap["option_page_ids"] = {"page_for_posts": "guides"}
snap["option_merge"] = {"twostars_tracking": {"google_profile_url": "https://share.google/Htz6pMs4mSZQ2CXJm"}}

with open(OUT, "w") as fh:
    json.dump(snap, fh, ensure_ascii=False, indent=1)
text = open(OUT).read()
print("wrote", OUT, len(text) // 1024, "KB;", len(snap["posts"]), "items;", len(snap["attachments"]), "new media")
for bad in ("tour_id", "two-stars-tour-and-safari.local"):
    print(" contains", bad, text.count(bad))
