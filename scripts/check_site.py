#!/usr/bin/env python3
"""Health checks for the HBM Flower City WordPress backup.

Run from the repository root:

    python3 scripts/check_site.py            # all checks (needs internet)
    python3 scripts/check_site.py --offline  # only checks that need no network

Checks:
  secrets    fail if files that hold credentials are tracked by git
  core       fail if a WordPress core file differs from the official release
             (a sign of tampering or malware); missing optional files are skipped
  plugins    fail if a wordpress.org plugin file differs from its official release
  updates    warn when WordPress, plugins or the theme are behind the latest release
  media      warn about uploaded images over 1 MB (they slow the site down)

Only the Python standard library is used. Results are also written as Markdown
to $GITHUB_STEP_SUMMARY when it is set (GitHub Actions).
"""

from __future__ import annotations

import argparse
import hashlib
import json
import os
import re
import subprocess
import sys
import urllib.error
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent

# Files that must never be committed: they hold database passwords, security
# keys, database dumps or environment secrets.
SECRET_PATTERNS = [
    re.compile(r"(^|/)wp-config\.php$"),
    re.compile(r"(^|/)\.env(\..*)?$"),
    re.compile(r"\.sql(\.gz|\.zip)?$"),
    re.compile(r"(^|/)(debug|error)\.log$|(^|/)error_log$"),
    re.compile(r"\.(wpress|bak)$"),
]

# Core files that sites commonly delete or that WordPress rewrites itself.
CORE_OPTIONAL_PREFIXES = ("wp-content/",)
CORE_OPTIONAL_FILES = {"readme.html", "license.txt", "wp-config-sample.php"}

LARGE_IMAGE_BYTES = 1024 * 1024
IMAGE_EXTENSIONS = {".jpg", ".jpeg", ".png", ".gif", ".webp"}


# --------------------------------------------------------------------------
# Helpers (pure functions, unit-tested in tests/test_check_site.py)
# --------------------------------------------------------------------------

def find_secret_files(tracked: list[str]) -> list[str]:
    """Return tracked paths that match a secret-file pattern."""
    return sorted(p for p in tracked if any(r.search(p) for r in SECRET_PATTERNS))


def parse_wp_version(version_php: str) -> str | None:
    """Read $wp_version from the text of wp-includes/version.php."""
    m = re.search(r"\$wp_version\s*=\s*'([^']+)'", version_php)
    return m.group(1) if m else None


def parse_header(text: str, field: str) -> str | None:
    """Read a WordPress file header field such as 'Version' or 'Stable tag'."""
    m = re.search(rf"^[ \t/*#@]*{re.escape(field)}:\s*(.+?)\s*$", text, re.M | re.I)
    return m.group(1).strip() if m else None


def version_tuple(v: str) -> tuple[int, ...]:
    """'6.7.2' -> (6, 7, 2); non-numeric parts count as 0."""
    return tuple(int(p) if p.isdigit() else 0 for p in re.split(r"[.\-]", v))


def is_outdated(installed: str, latest: str) -> bool:
    return version_tuple(installed) < version_tuple(latest)


def compare_checksums(expected: dict[str, str], base: Path, algo: str,
                      optional=lambda path: False) -> tuple[list[str], list[str]]:
    """Compare files under `base` with expected {relative_path: hexdigest}.

    Returns (changed, missing). Missing files for which optional(path) is true
    are ignored.
    """
    changed, missing = [], []
    for rel, digest in sorted(expected.items()):
        path = base / rel
        if not path.is_file():
            if not optional(rel):
                missing.append(rel)
            continue
        actual = hashlib.new(algo, path.read_bytes()).hexdigest()
        if actual.lower() != digest.lower():
            changed.append(rel)
    return changed, missing


def core_optional(rel: str) -> bool:
    return rel.startswith(CORE_OPTIONAL_PREFIXES) or rel in CORE_OPTIONAL_FILES


def large_images(uploads: Path, limit: int = LARGE_IMAGE_BYTES) -> list[tuple[str, int]]:
    """Images under uploads/ bigger than `limit` bytes, largest first."""
    if not uploads.is_dir():
        return []
    found = [
        (str(p.relative_to(uploads)), p.stat().st_size)
        for p in uploads.rglob("*")
        if p.is_file() and p.suffix.lower() in IMAGE_EXTENSIONS and p.stat().st_size > limit
    ]
    return sorted(found, key=lambda x: -x[1])


# --------------------------------------------------------------------------
# Network
# --------------------------------------------------------------------------

def fetch_json(url: str):
    req = urllib.request.Request(url, headers={"User-Agent": "hbm-site-check"})
    with urllib.request.urlopen(req, timeout=30) as r:
        return json.loads(r.read().decode())


# --------------------------------------------------------------------------
# Checks
# --------------------------------------------------------------------------

class Report:
    def __init__(self):
        self.lines: list[str] = []
        self.failed = False

    def ok(self, msg):
        self.lines.append(f"- ✅ {msg}")

    def warn(self, msg):
        self.lines.append(f"- ⚠️ {msg}")

    def fail(self, msg):
        self.failed = True
        self.lines.append(f"- ❌ {msg}")


def tracked_files() -> list[str]:
    out = subprocess.run(["git", "ls-files"], cwd=ROOT, capture_output=True, text=True, check=True)
    return out.stdout.splitlines()


def check_secrets(rep: Report):
    found = find_secret_files(tracked_files())
    if found:
        rep.fail("Files with credentials are tracked by git: " + ", ".join(found))
    else:
        rep.ok("No credential files are tracked")


def installed_plugins() -> dict[str, str]:
    plugins = {}
    for d in sorted((ROOT / "wp-content/plugins").glob("*/")):
        for f in sorted(d.glob("*.php")):
            text = f.read_text(errors="ignore")[:8192]
            if parse_header(text, "Plugin Name"):
                version = parse_header(text, "Version")
                if version:
                    plugins[d.name] = version
                break
    return plugins


def check_core(rep: Report, wp_version: str):
    url = f"https://api.wordpress.org/core/checksums/1.0/?version={wp_version}&locale=en_US"
    data = fetch_json(url)
    expected = data.get("checksums") or {}
    if not expected:
        rep.warn(f"No official checksums found for WordPress {wp_version}")
        return
    changed, missing = compare_checksums(expected, ROOT, "md5", core_optional)
    if changed or missing:
        detail = []
        if changed:
            detail.append("changed: " + ", ".join(changed[:20]))
        if missing:
            detail.append("missing: " + ", ".join(missing[:20]))
        rep.fail(f"WordPress {wp_version} core files don't match the official release ({'; '.join(detail)})")
    else:
        rep.ok(f"WordPress {wp_version} core files match the official release")


def check_plugins(rep: Report, plugins: dict[str, str]):
    for slug, version in plugins.items():
        url = f"https://downloads.wordpress.org/plugin-checksums/{slug}/{version}.json"
        try:
            data = fetch_json(url)
        except urllib.error.HTTPError as e:
            if e.code == 404:
                rep.warn(f"{slug} {version}: no official checksums (not on wordpress.org)")
                continue
            raise
        files = data.get("files", {})
        base = ROOT / "wp-content/plugins" / slug
        changed = []
        missing = []
        for rel, sums in sorted(files.items()):
            path = base / rel
            if not path.is_file():
                missing.append(rel)
                continue
            accepted = sums.get("md5")
            accepted = accepted if isinstance(accepted, list) else [accepted]
            actual = hashlib.md5(path.read_bytes()).hexdigest()
            if actual not in accepted:
                changed.append(rel)
        if changed:
            rep.fail(f"{slug} {version}: files differ from the official release: " + ", ".join(changed[:20]))
        elif missing:
            rep.warn(f"{slug} {version}: {len(missing)} official file(s) missing")
        else:
            rep.ok(f"{slug} {version} matches the official release")


def check_updates(rep: Report, wp_version: str, plugins: dict[str, str], theme_version: str | None):
    latest_core = fetch_json("https://api.wordpress.org/core/version-check/1.7/")["offers"][0]["current"]
    if is_outdated(wp_version, latest_core):
        rep.warn(f"WordPress {wp_version} is behind the latest release ({latest_core})")
    else:
        rep.ok(f"WordPress {wp_version} is current")

    for slug, version in plugins.items():
        try:
            info = fetch_json(
                f"https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request[slug]={slug}"
            )
        except urllib.error.HTTPError:
            rep.warn(f"{slug}: couldn't look up the latest version")
            continue
        latest = info.get("version")
        if not latest:
            rep.warn(f"{slug}: not listed on wordpress.org")
        elif is_outdated(version, latest):
            rep.warn(f"{slug} {version} is behind the latest release ({latest})")
        else:
            rep.ok(f"{slug} {version} is current")

    if theme_version:
        info = fetch_json("https://api.wordpress.org/themes/info/1.2/?action=theme_information&request[slug]=astra")
        latest = info.get("version")
        if latest and is_outdated(theme_version, latest):
            rep.warn(f"Astra theme {theme_version} is behind the latest release ({latest})")
        elif latest:
            rep.ok(f"Astra theme {theme_version} is current")


def check_media(rep: Report):
    big = large_images(ROOT / "wp-content/uploads")
    if big:
        total = sum(s for _, s in big) / 1048576
        top = ", ".join(f"{p} ({s / 1048576:.1f} MB)" for p, s in big[:5])
        rep.warn(f"{len(big)} uploaded images are over 1 MB ({total:.0f} MB total). Largest: {top}")
    else:
        rep.ok("No uploaded images over 1 MB")


def main(argv=None) -> int:
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("--offline", action="store_true", help="skip checks that need the internet")
    args = ap.parse_args(argv)

    rep = Report()
    wp_version = parse_wp_version((ROOT / "wp-includes/version.php").read_text())
    plugins = installed_plugins()
    style = ROOT / "wp-content/themes/astra/style.css"
    theme_version = parse_header(style.read_text(errors="ignore")[:4096], "Version") if style.exists() else None

    check_secrets(rep)
    check_media(rep)
    if not args.offline:
        check_core(rep, wp_version)
        check_plugins(rep, plugins)
        check_updates(rep, wp_version, plugins, theme_version)

    text = "## Site checks\n\n" + "\n".join(rep.lines) + "\n"
    print(text)
    summary = os.environ.get("GITHUB_STEP_SUMMARY")
    if summary:
        with open(summary, "a") as fh:
            fh.write(text)
    return 1 if rep.failed else 0


if __name__ == "__main__":
    sys.exit(main())
