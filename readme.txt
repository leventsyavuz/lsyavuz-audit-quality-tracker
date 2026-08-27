=== Lsyavuz Audit & Quality Tracker ===
Contributors: lsyavuz
Tags: audit, log, activity, security, tracking
Requires at least: 6.0
Tested up to: 7.1
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight, secure, and modern audit log tool to track post changes, plugin activities, and user logins with a beautiful dashboard.

== Description ==

Keeping track of what happens on your WordPress site shouldn't mean sacrificing performance. **WP Light Audit & Quality Tracker** is built with a "Security and Performance First" approach. It logs critical site activities in a dedicated, optimized custom database table instead of bloating your core database.

Perfect for site administrators, webmasters, and digital marketing teams who need a clean, visual representation of site activity without the heavy overhead of complex security plugins.

### Key Features
* **Lightweight Architecture:** Uses a custom database table to ensure your site stays lightning fast.
* **Modern Dashboard:** Built-in Chart.js integration provides stunning, animated visual reports of your site's activity.
* **Smart Filtering:** Easily filter activities by daily, weekly, monthly, or yearly ranges using AJAX (no page reloads).
* **Noise Reduction:** Automatically ignores "Auto Drafts", revisions, and background system processes so you only see what matters.
* **Translation Ready:** Fully i18n compliant. Built natively in English and ready to be translated into any language.

### What Does It Track?
* Content changes (Posts/Pages created, updated, or trashed).
* Plugin activities (Activations and deactivations).
* System access (Successful user logins).

== Installation ==

1. Upload the `lsyavuz-audit-quality-tracker` folder to the `/wp-content/plugins/` directory, or install directly through the WordPress plugin screen.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the new "Quality Audit" menu in your WordPress admin panel to view the modern dashboard.

== Frequently Asked Questions ==

= Will this slow down my website? =
No. Unlike many other audit plugins that save logs as Custom Post Types (CPTs) which bloat the `wp_posts` table, this plugin uses a dedicated, highly optimized custom database table. It also aggressively filters out background noise (like autosaves).

= Can I export the data for external reporting? =
In version 1.0.0, data is viewed directly in the dashboard. Advanced export features (like clean CSV/JSON endpoints for Looker Studio or Google Analytics) are planned for future releases.

== Screenshots ==

1. The modern, Chart.js powered dashboard showing activity distribution.
2. The recent records table displaying user actions clearly.

== Changelog ==

= 1.0.0 =
* Initial release.
* Added custom DB table architecture.
* Added post, plugin, and login tracking with noise filtering.
* Integrated Chart.js for visual reporting.
* AJAX-powered dashboard filtering (Daily, Weekly, Monthly, Yearly).