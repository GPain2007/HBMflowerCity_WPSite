"""Unit tests for scripts/check_site.py (no network needed)."""
import hashlib
import sys
from pathlib import Path

import pytest

sys.path.insert(0, str(Path(__file__).resolve().parent.parent / "scripts"))
import check_site as cs  # noqa: E402

ROOT = Path(__file__).resolve().parent.parent


@pytest.mark.parametrize(
    "path",
    ["wp-config.php", "sub/wp-config.php", ".env", ".env.production", "backup.sql",
     "db.sql.gz", "wp-content/debug.log", "error_log", "site.wpress", "old.bak"],
)
def test_secret_files_are_detected(path):
    assert cs.find_secret_files([path]) == [path]


@pytest.mark.parametrize(
    "path",
    ["wp-config-sample.php", "README.md", "wp-content/plugins/x/sql.php",
     "wp-content/uploads/wc-logs/image.log", "environment.php"],
)
def test_ordinary_files_are_not_flagged(path):
    assert cs.find_secret_files([path]) == []


def test_parse_wp_version():
    assert cs.parse_wp_version("<?php\n$wp_version = '6.7.2';\n") == "6.7.2"
    assert cs.parse_wp_version("<?php\n") is None


def test_parse_header_plugin_and_theme_styles():
    plugin = "<?php\n/*\n * Plugin Name:       Super Blank\n * Version:           1.1.0\n */"
    theme = "/*\nTheme Name: Astra\nVersion: 4.8.10\n*/"
    assert cs.parse_header(plugin, "Plugin Name") == "Super Blank"
    assert cs.parse_header(plugin, "Version") == "1.1.0"
    assert cs.parse_header(theme, "Version") == "4.8.10"
    assert cs.parse_header(theme, "Author") is None


@pytest.mark.parametrize(
    "installed, latest, outdated",
    [("6.7.2", "6.8.3", True), ("6.8.3", "6.8.3", False), ("3.27.6", "3.9.0", False),
     ("24.5", "26.1", True), ("1.9.3.2", "1.9.3.10", True), ("1.10", "1.9", False)],
)
def test_is_outdated(installed, latest, outdated):
    assert cs.is_outdated(installed, latest) is outdated


def test_compare_checksums(tmp_path):
    (tmp_path / "a.php").write_bytes(b"good")
    (tmp_path / "b.php").write_bytes(b"tampered")
    expected = {
        "a.php": hashlib.md5(b"good").hexdigest(),
        "b.php": hashlib.md5(b"original").hexdigest(),
        "c.php": "0" * 32,
        "wp-content/plugins/hello.php": "0" * 32,
    }
    changed, missing = cs.compare_checksums(expected, tmp_path, "md5", cs.core_optional)
    assert changed == ["b.php"]
    assert missing == ["c.php"]


def test_large_images(tmp_path):
    (tmp_path / "2025").mkdir()
    (tmp_path / "2025/big.JPG").write_bytes(b"x" * 2048)
    (tmp_path / "2025/small.png").write_bytes(b"x" * 10)
    (tmp_path / "2025/notes.txt").write_bytes(b"x" * 4096)
    assert cs.large_images(tmp_path, limit=1024) == [("2025/big.JPG", 2048)]
    assert cs.large_images(tmp_path / "missing") == []


# --- Checks against the files actually in this repo -----------------------

def test_repo_wordpress_version_is_readable():
    assert cs.parse_wp_version((ROOT / "wp-includes/version.php").read_text())


def test_repo_plugins_are_readable():
    plugins = cs.installed_plugins()
    assert {"elementor", "wordpress-seo", "wpforms-lite"} <= set(plugins)
    assert all(cs.version_tuple(v) > (0,) for v in plugins.values())


def test_no_credential_files_tracked():
    assert cs.find_secret_files(cs.tracked_files()) == []
