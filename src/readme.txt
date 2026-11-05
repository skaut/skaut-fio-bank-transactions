=== Fio bank - transactions ===
Contributors: skaut, davidulus, marekdedic, kalich5
Tags: skaut, shortcode, block, fio, transactions
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 1.3.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Plugin pro zobrazování tabulky transakcí z Fio banky přes shortcode nebo blok.

== PŘERUŠENÍ PODPORY PLUGINU ==

Plugin má přerušenou podporu a nebude dále aktualizován od dubna 2024. Je možné ho nadále používat, ale nebudou přidány žádné nová funkce a jakékoliv chyby nebudou opraveny.

== Description ==

<h2> Minimální požadavky</h2>
- WordPress 7.0 a vyšší
- PHP 8.3 a vyšší

Plugin pro zobrazování tabulky transakcí z Fio banky přes shortcode nebo blok „Transakce z Fio banky“. Je možno přidávat více účtů a zobrazovat si je na různých stránkách a příspěvcích.

Tabulku mohou vkládat jen uživatelé s oprávněním `edit_others_posts` (editor a výš); oprávnění lze změnit filtrem `fio_bank_transactions_embed_capability`. Data z Fio banky se ukládají do mezipaměti na 15 minut (filtr `fio_bank_transactions_cache_expiration`), při chybě spojení se zobrazí poslední stažená data.

<h2>Shortcode</h2>
`[fio account="123"]` – parametry:

- `account` – ID účtu (povinné)
- `days` – počet dní zpět, výchozí 365
- `columns` – sloupce oddělené čárkou: `date`, `amount`, `account`, `name`, `vs`, `ss`, `ks`, `message`, `comment`, `type`; výchozí `date,amount,account,message,comment,type`
- `ss` – zobrazit jen platby s tímto specifickým symbolem
- `vs_prefix` – zobrazit jen platby, jejichž variabilní symbol začíná zadanými číslicemi

Příklad: `[fio account="123" days="90" columns="date,amount,vs,message" vs_prefix="2026"]`

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

= 1.3.0 =
* Security: the Fio API token is no longer written to the PHP error log
* Security: only editors and administrators (filter fio_bank_transactions_embed_capability) can display account transactions; contributors can no longer show any account in a post preview
* Security: the list of accounts is no longer printed on every admin page for every user
* Security: stricter validation when saving the token, escaping of all table values
* New: block "Transakce z Fio banky"
* New: shortcode attributes days, columns, ss and vs_prefix; new columns VS, SS, KS and counterparty name
* Fix: transactions are cached, so the Fio API limit (1 request per 30 seconds) no longer causes "Chyba při spojení s Fio bankou"; the last downloaded data is shown when the API is unavailable
* Fix: the plugin now uses the new Fio API endpoint (fioapi.fio.cz)
* Fix: Czech texts in the TinyMCE dialog, account number format "number/bank code"
* Fix: uninstall removes all accounts, tokens and cached data
* Updated DataTables to version 3 (no jQuery or moment.js needed), Guzzle and other dependencies
* Requires WordPress 7.0 and PHP 8.3, tested with WordPress 7.1

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
