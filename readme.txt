=== Weblock Widgets ===
Contributors: weblockgroup
Tags: google reviews, instagram, facebook, youtube, google maps
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Google Reviews, Instagram, Facebook, YouTube és Google Maps widgetek egy pluginban — Trustindex és Elfsight kiváltása.

== Description ==

A `weblock-widgets` plugin a Weblock Group saját, agency-tulajdonú megoldása a Trustindex.io és Elfsight.com előfizetések kiváltására.

**Beépített widgetek (Fázis 1 — MVP):**

* Google Reviews (shortcode + Gutenberg block)
* Instagram Feed
* Facebook Page Feed
* YouTube Channel / Playlist Gallery
* Google Maps embed

**Funkciók:**

* Shortcode és Gutenberg block minden widgethez
* Reszponzív (mobile-first, 5 töréspont)
* Beépített transient cache (konfigurálható TTL)
* Per-site API kulcs admin felületen
* Plugin Update Checker (GitHub Releases auto-update)

== Installation ==

1. Töltsd fel a `weblock-widgets` mappát a `/wp-content/plugins/` könyvtárba (vagy ZIP-ből Admin → Plugins → Add New → Upload Plugin)
2. Aktiváld a plugint
3. Lépj a **Weblock Widgets** menübe és töltsd ki az API kulcsokat
4. Használd a shortcode-okat vagy a Gutenberg blokkokat

== Changelog ==

= 0.8.0 =
* ÚJ: E-mail aláírás generátor — 3 sablon (szöveges, profilképpel, saját logóval)
* Google csillag rating + értékelések száma + link az aláírásban
* HTML output (másolható Gmail / Outlook / Mailchimp aláírásba)
* Új meta-flag: output_type (shortcode|html) — copy gomb a HTML-t másolja shortcode helyett

= 0.7.0 =
* ÚJ: Trustmark Badge widget — 11 trust badge (SSL, biztonságos fizetés, spammentes, ingyenes szállítás, 30 napos pénzvisszafizetés, stb.)
* 3 stílus: Pirula (címke + tooltip), Kompakt, Kártya
* 3 méret: Kicsi, Közepes, Nagy
* Új "Trust" kategória a galériában
* UI: search/tab radius lecsökkentve 999px-ről 4px-re

= 0.6.1 =
* ÚJ: Kategória-szűrő pill tab-okkal (Mind / Vélemények / Közösségi / Trust / Eszközök / Galéria / Értékesítés / Kapcsolat / Form)
* ÚJ: Keresőmező a widget galéria fölött (élő szűrés név + leírás alapján, ESC ürítés)
* Tab-ok mutatják a widget számot kategóriánként, üres kategóriák elrejtve

= 0.6.0 =
* ÚJ: Twitter / X Feed widget (no API) — felhasználónév-alapú timeline, hivatalos Twitter widgets.js
* ÚJ: Pinterest Feed widget (no API) — profil, board vagy egy pin beágyazása

= 0.5.0 =
* ÚJ: TikTok Feed widget (no API) — TikTok videók beágyazása URL-lista alapján, hivatalos TikTok embed.js-szel

= 0.4.0 =
* ⚡ NAGY VÁLTOZTATÁS: 4 widget API kulcs NÉLKÜL működik (csak Google Reviews igényel kulcsot)
* YouTube Gallery — RSS feed-re átálltunk (no API key)
* Google Map — közvetlen maps.google.com iframe (no API key)
* Facebook Feed — hivatalos Page Plugin iframe (no API key, csak Page URL)
* Instagram Feed — Instagram saját embed.js + URL lista (no API key, csak poszt URL-ek)
* Settings page leegyszerűsítve: csak 1 API kulcs (Google Reviews-hoz)
* textarea mezőtípus a konfigurátorban

= 0.3.0 =
* ÚJ: Place ID kereső a Google Reviews konfigurátorban — beírod a cégnevet + várost, kilistázza a Google találatokat, klikk → auto-kitölti a Place ID-t
* AJAX endpoint: `wlw_search_place` (Google Places Text Search API)

= 0.2.0 =
* ÚJ: Vizuális admin felület — widget galéria kártyákkal, paraméter-konfigurátor élő előnézettel
* ÚJ: Shortcode auto-generálás + 1-kattintásos vágólapra másolás
* ÚJ: Külön Beállítások és Súgó oldal
* ÚJ: AJAX preview endpoint (admin-only)
* Widget metadata struktúra (`get_meta()`) — generic form-generálás

= 0.1.0 =
* Első release: 5 widget (Google Reviews, Instagram Feed, Facebook Feed, YouTube Gallery, Google Maps)
