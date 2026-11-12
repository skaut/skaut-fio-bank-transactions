=== Fio bank - transactions ===
Contributors: skaut, davidulus, marekdedic, kalich5
Tags: skaut, block, fio, transactions, bank
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 2.1.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Displays a table of transactions from Fio banka accounts using a block.

== Description ==

= Limited support =

The plugin has limited support. No new features will be added; it is only checked from time to time that everything works. You can keep using the plugin.

The plugin displays a table of transactions from a Fio banka (Czech bank) account using the "Fio bank transactions" block. You can add multiple accounts and show them on different pages and posts.

= Minimum requirements =

* WordPress 7.0 or newer
* PHP 8.3 or newer

= The "Fio bank transactions" block =

In the block sidebar you can set:

* the account,
* the period: the last N days (at most 90, because without an additional authorization Fio banka provides only the last 90 days), the last 7 days, this month or last month,
* a filter by the specific symbol and by the beginning of the variable symbol,
* the table display: search (on/off), pagination (off = all transactions at once) and the number of rows per page (10, 25, 50, 100 or all),
* the displayed columns: date, amount, counterparty account, counterparty name, VS, SS, KS, message for recipient, note and type.

Above the table, visitors can switch between all, incoming and outgoing transactions.

Only users with the `edit_others_posts` capability (editors and administrators) can insert the table; the capability can be changed with the `fio_bank_transactions_embed_capability` filter. Data from Fio banka is cached for 15 minutes (filter `fio_bank_transactions_cache_expiration`); if the connection fails, the last downloaded data is shown.

= External services =

The plugin connects to the Fio banka API (https://fioapi.fio.cz) to download the transactions of the accounts you add. The request contains the API token of the account and the requested date range. It is sent when a page with the block is displayed and the cached data has expired (at most once every 15 minutes per account and period). Fio banka terms and privacy policy: https://www.fio.cz/docs/cz/API_Bankovnictvi.pdf and https://www.fio.cz/o-nas/ochrana-osobnich-udaju

= We are on GitHub =

[https://github.com/skaut/skaut-fio-bank-transactions/](https://github.com/skaut/skaut-fio-bank-transactions/)

== Installation ==

1. Install and activate the plugin.
2. Create a token in the Fio internet banking following [this guide](https://napoveda.fapi.cz/article/31-jak-vytvorit-token-ve-fio-bance) (in Czech).
3. Open "Fio Bank" > "Accounts" and click "Create the encryption key". The plugin creates the file wp-content/fio-bank-transactions-key.php with a random key. Alternatively, define the key in wp-config.php: `define( 'FIO_BANK_TRANSACTIONS_KEY', 'a long random string' );`.
4. Add an account in "Fio Bank" > "Accounts" and enter the token.
5. Insert the "Fio bank transactions" block into a page or a post.

== Frequently Asked Questions ==

= Where can I find the plugin documentation? =

[https://napoveda.skaut.cz/dobryweb/skaut-fio-bank-transactions](https://napoveda.skaut.cz/dobryweb/skaut-fio-bank-transactions) (in Czech)

= How do I get a Fio token? =

Create a new token following [this guide](https://napoveda.fapi.cz/article/31-jak-vytvorit-token-ve-fio-bance) (in Czech).

= How is the Fio token stored? =

The tokens are stored encrypted (libsodium) with a key that is not stored in the database, so a leaked database or backup does not reveal them. The key is in the file wp-content/fio-bank-transactions-key.php (created with the "Create the encryption key" button) or in the `FIO_BANK_TRANSACTIONS_KEY` constant in wp-config.php, which takes precedence. Accounts can be added only after the key exists. Tokens saved by older versions are encrypted automatically.

Do not delete or change the key and copy the key file when moving the site - otherwise the tokens have to be entered again. When the plugin is uninstalled, the key file is deleted together with the tokens.

The saved token is never shown in the administration again, only asterisks and its last four characters; it can only be replaced with a new token from the Fio internet banking. Use a token with the "view only" permission: it can only read the account history.

= Why can I display only the last 90 days? =

Without an additional authorization in the internet banking, the Fio API provides only the transactions of the last 90 days.

== Changelog ==

= 2.1.0 =
* Security: the Fio tokens are stored encrypted with a key outside the database - the "Create the encryption key" button creates wp-content/fio-bank-transactions-key.php, or define the FIO_BANK_TRANSACTIONS_KEY constant in wp-config.php; existing tokens are encrypted automatically
* Security: accounts can be added only after the encryption key exists; tokens are never saved as plain text
* Security: the saved token is no longer shown in the account settings, only asterisks and its last four characters; it can only be replaced with a new token
* Fix: deleting the plugin removes all its data - also accounts in the trash or unfinished drafts with their tokens, which were left behind before (new uninstall.php)

= 2.0.2 =
* Fix: the block settings in the editor were not translated - the texts are now translated in PHP and passed to the editor script

= 2.0.1 =
* Fix: "Could not connect to Fio banka" when the block settings were changed or several blocks used the same account - all periods now share one download per account (the Fio API allows one request per 30 seconds)
* Fix: after a failed request the API is not called again for 30 seconds; the last downloaded data is shown meanwhile

= 2.0.0 =
* All texts of the plugin are in English and prepared for translation on translate.wordpress.org
* The DataTables texts (search, paging) follow the site language
* Fix: the buttons "Incoming", "Outgoing" and "All transactions" did not filter the table when the search box was hidden
* Fix: updated link to the guide on how to create a Fio API token
* New: block settings "Show search", "Pagination" and "Rows per page" (10, 25, 50, 100 or all)
* New: buttons "All transactions", "Incoming" and "Outgoing" above the table
* Fix: the ordering arrows are always on the right side of the column header
* Removed: the [fio] shortcode and the button in the classic editor (TinyMCE); tables are inserted only with the block "Fio bank transactions". Existing [fio] shortcodes have to be replaced with the block.
* New: the admin menu "Fio Bank" uses the same dollar icon as the block
* The plugin is maintained again: the "discontinued support" notice was removed
* Security: the Fio API token is no longer written to the PHP error log
* Security: only editors and administrators (filter fio_bank_transactions_embed_capability) can display account transactions; contributors can no longer show any account in a post preview
* Security: the list of accounts is no longer printed on every admin page for every user
* Security: stricter validation when saving the token, escaping of all table values
* New: block "Fio bank transactions"
* New: block settings for period, days, columns, SS and VS prefix; new columns VS, SS, KS and counterparty name
* New: period "last 7 days", "this month" and "last month"
* Change: at most the last 90 days are shown (default 90 instead of 1 year), because the Fio API returns only 90 days of history without an extra authorization
* Fix: transactions are cached, so the Fio API limit (1 request per 30 seconds) no longer causes a connection error; the last downloaded data is shown when the API is unavailable
* Fix: the plugin now uses the new Fio API endpoint (fioapi.fio.cz)
* Fix: account number format "number/bank code"
* Fix: uninstall removes all accounts, tokens and cached data
* Updated DataTables to version 3 (no jQuery or moment.js needed), Guzzle and other dependencies
* Requires WordPress 7.0, tested with WordPress 7.1
* Requires PHP 8.3 (PHP 7.4–8.2 are no longer supported; security support for PHP 8.2 ends on 31 December 2026)

= 1.2.3 =
* Added a deprecation notice

= 1.2.2 =
* Fixed a critical error introduced in version 1.2.1

= 1.2.1 =
* Fixed an issue with translations in the shortcode

= 1.2.0 =
* New icon and branding without official Fio icons

= 1.1.3 =
* Fixed security issues

= 1.1.2 =
* Bug fixes

= 1.1.1 =
* Fixed sorting of payments by date

= 1.1 =
* Requires PHP 7.4 or newer
* Fixed the plugin functionality

= 0.1 =
* Shows data from the last year
* Requires PHP 7.1 or newer
* Requires WordPress 4.9.6 or newer
