=== Fio bank - transactions ===
Contributors: skaut, davidulus, marekdedic, kalich5
Tags: skaut, block, fio, transactions
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 1.3.4
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Plugin pro zobrazování tabulky transakcí z Fio banky přes blok.

== Description ==

<h2> Minimální požadavky</h2>
- WordPress 7.0 a vyšší
- PHP 8.3 a vyšší

Plugin pro zobrazování tabulky transakcí z Fio banky přes blok „Transakce z Fio banky“. Je možno přidávat více účtů a zobrazovat si je na různých stránkách a příspěvcích.

Tabulku mohou vkládat jen uživatelé s oprávněním `edit_others_posts` (editor a výš); oprávnění lze změnit filtrem `fio_bank_transactions_embed_capability`. Data z Fio banky se ukládají do mezipaměti na 15 minut (filtr `fio_bank_transactions_cache_expiration`), při chybě spojení se zobrazí poslední stažená data.

<h2>Blok „Transakce z Fio banky“</h2>
V postranním panelu bloku se nastavuje:

- účet,
- období: posledních N dní (max. 90, Fio banka bez dodatečné autorizace vydá jen posledních 90 dní), posledních 7 dní, tento měsíc nebo minulý měsíc,
- filtr podle specifického symbolu a začátku variabilního symbolu,
- zobrazené sloupce: datum, částka, protiúčet, název protiúčtu, VS, SS, KS, zpráva pro příjemce, poznámka, typ.

Nad tabulkou může návštěvník přepínat mezi všemi, příchozími a odchozími transakcemi.

**Jsme na GitHubu**
[https://github.com/skaut/skaut-fio-bank-transactions/](https://github.com/skaut/skaut-fio-bank-transactions/)

== Installation ==
1. Stáhnout si plugin a aktivovat
2. Získat a zadat token podle [návodu.](https://napoveda.fapi.cz/article/144-jak-vygenerovat-token-ve-fio-bance)

== Frequently Asked Questions ==
**Nápověda k pluginu**
[https://napoveda.skaut.cz/dobryweb/skaut-fio-bank-transactions](https://napoveda.skaut.cz/dobryweb/skaut-fio-bank-transactions)

**Jak získat Fio token?**
Je potřeba vytvořit nový token podle [návodu.](https://napoveda.fapi.cz/article/144-jak-vygenerovat-token-ve-fio-bance)

== Screenshots ==


== Changelog ==

= 1.3.4 =
* New: buttons "Všechny transakce", "Příchozí" and "Odchozí" above the table
* Fix: the ordering arrows are always on the right side of the column header
* Removed: the [fio] shortcode and the button in the classic editor (TinyMCE); tables are inserted only with the block "Transakce z Fio banky". Existing [fio] shortcodes have to be replaced with the block.
* New: the admin menu "Fio Bank" uses the same dollar icon as the block
* The plugin is maintained again: the "discontinued support" notice was removed
* Security: the Fio API token is no longer written to the PHP error log
* Security: only editors and administrators (filter fio_bank_transactions_embed_capability) can display account transactions; contributors can no longer show any account in a post preview
* Security: the list of accounts is no longer printed on every admin page for every user
* Security: stricter validation when saving the token, escaping of all table values
* New: block "Transakce z Fio banky"
* New: block settings for period, days, columns, SS and VS prefix; new columns VS, SS, KS and counterparty name
* New: period "last 7 days", "this month" and "last month"
* Change: at most the last 90 days are shown (default 90 instead of 1 year), because the Fio API returns only 90 days of history without an extra authorization
* Fix: transactions are cached, so the Fio API limit (1 request per 30 seconds) no longer causes "Chyba při spojení s Fio bankou"; the last downloaded data is shown when the API is unavailable
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
* oprava chyb

= 1.1.1 =
* oprava řazení plateb podle data

= 1.1 =
* PHP 7.4 a vyšší
* oprava funkčnosti pluginu

= 0.1 =
* Zobrazuje data stará 1 rok
* PHP 7.1 a vyšší
* vyžadován WordPress 4.9.6 a vyšší
