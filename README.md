# HBM Flower City (WordPress)

This is a functioning website for **HBM Flower City**, a wedding florist operating in the Austin, Texas area, built with **WordPress**. This repository holds a backup of the site's WordPress files (core, theme, plugins and uploaded media) so the site can be moved to a new domain or host.

[![CI](https://github.com/GPain2007/HBMflowerCity_WPSite/actions/workflows/ci.yml/badge.svg)](https://github.com/GPain2007/HBMflowerCity_WPSite/actions/workflows/ci.yml)

**Domain:** [hbmflower-city.com](https://hbmflower-city.com)
**React version of the site:** [hbmAnimated](https://github.com/GPain2007/hbmAnimated)

## What's in this repo

| Path | What it is |
| --- | --- |
| `wp-admin/`, `wp-includes/`, root `wp-*.php` | WordPress core |
| `wp-content/themes/astra/` | The Astra theme |
| `wp-content/plugins/` | Elementor, Yoast SEO, WPForms Lite and Super Blank |
| `wp-content/uploads/` | Uploaded media (site photos, about 97 MB), plus Elementor and WPForms generated files |
| `.htaccess` | WordPress permalink rules for the server |
| `.private/config.json` | Hostinger's theme and plugin update addresses (no secrets) |
| `scripts/check_site.py` | Health checks run by CI (see below) |
| `tests/` | Unit tests for the checks |

**Not in this repo:** the database. In WordPress, the pages, Elementor layouts, menus, form settings and site options all live in the MySQL database. `wp-config.php`, which holds the database password and security keys, is also deliberately left out (see [Known issues](#known-issues)).

## Tech stack

| Part | Version |
| --- | --- |
| [WordPress](https://wordpress.org/) | 6.7.2 |
| [Astra](https://wpastra.com/) theme | 4.8.10 |
| [Elementor](https://elementor.com/) page builder | 3.27.6 |
| [Yoast SEO](https://yoast.com/wordpress/plugins/seo/) | 24.5 |
| [WPForms Lite](https://wpforms.com/) contact/inquiry forms | 1.9.3.2 |
| Super Blank (blank-canvas starter) | 1.1.0 |
| Hosting | [Hostinger](https://www.hostinger.com/) (Apache/LiteSpeed, PHP, MySQL) |

## Restoring or moving the site

You need two things: these files and a database export.

1. **Export the database** from the current host (Hostinger hPanel → Databases → phpMyAdmin → Export, or a plugin such as All-in-One WP Migration or Duplicator). Store it privately; never commit it here.
2. **Upload the files** in this repo to the new host's web root.
3. **Create `wp-config.php`** from `wp-config-sample.php` with the new database name, user, password and host, and fresh security keys from <https://api.wordpress.org/secret-key/1.1/salt/>.
4. **Import the database** on the new host.
5. **If the domain changes,** update the site address (Settings → General) and replace the old domain throughout the database with a search-and-replace tool (for example `wp search-replace 'https://hbmflower-city.com' 'https://new-domain.com'` with WP-CLI, or the Better Search Replace plugin), then re-save Settings → Permalinks.

### Running locally

Any local WordPress environment works, e.g. [LocalWP](https://localwp.com/) or Docker:

```bash
git clone https://github.com/GPain2007/HBMflowerCity_WPSite.git
cd HBMflowerCity_WPSite
cp wp-config-sample.php wp-config.php   # then fill in local database settings
```

Then import the database export and point the site address at your local URL.

## Checks and CI

`scripts/check_site.py` (Python standard library only) checks the backup:

| Check | Fails or warns |
| --- | --- |
| Credential files tracked (`wp-config.php`, `.env`, `.sql` dumps, logs, `.bak`) | Fails |
| WordPress core files match the official 6.7.2 release (catches tampering or malware) | Fails |
| Plugin files match their official wordpress.org releases | Fails |
| WordPress, plugins and Astra are on the latest version | Warns |
| Uploaded images over 1 MB | Warns |

```bash
python3 scripts/check_site.py            # all checks (needs internet)
python3 scripts/check_site.py --offline  # secrets and media only
python3 -m pytest -q tests               # unit tests for the checks
```

[`.github/workflows/ci.yml`](.github/workflows/ci.yml) runs the unit tests, a PHP syntax check and the site checks:

- on every push and pull request to `main`
- every Monday at 13:37 UTC (early Monday morning, Central time), so new WordPress or plugin releases show up even in weeks with no commits
- on demand from the Actions tab

The results appear in the run's summary. If a scheduled run fails, the workflow opens a "Weekly CI check failed" issue (or comments on the open one).

## Known issues

Open problems are tracked in [GitHub Issues](https://github.com/GPain2007/HBMflowerCity_WPSite/issues). The main ones:

- **`wp-config.php` was committed** in March 2025, so the database password and security keys are in this public repo's history. Removing the file from the repo doesn't remove it from history: the password and keys need to be changed on the host.
- No database export is backed up, so this repo can't restore the site on its own.
- WordPress 6.7.2 and the plugins are from early 2025 and behind current releases.
- 16 uploaded photos are over 1 MB each (28 MB in total).

## Recent changes

This log is kept up to date by a weekly review.

- **2026-11-13:** README rewritten. `wp-config.php` removed from the repo and added to `.gitignore`. Added site health checks (`scripts/check_site.py`), unit tests and GitHub Actions CI.
