# Changelog

A formátum [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) szerint, verziózás [Semantic Versioning](https://semver.org/lang/hu/).

## [0.6.0] — 2026-05-15

### Hozzáadva
- **Twitter / X Feed widget** (`[wlw_twitter_feed]`) — no-API
  - Field-ek: `username` (kötelező, @ nélkül), `height`, `theme` (light/dark)
  - Hivatalos `platform.twitter.com/widgets.js` használata
- **Pinterest Feed widget** (`[wlw_pinterest_feed]`) — no-API
  - Field-ek: `url`, `type` (embedUser / embedBoard / embedPin), `width`, `height`
  - Hivatalos `assets.pinterest.com/js/pinit.js` használata
- Gutenberg block-ok mindkét új widgethez
- CSS: reszponzív iframe stílus

### Megjegyzés
- Google Business Feed eltolva v0.9.0-ra (Review platforms csoportba), mert a Google My Business posts API nem nyilvános, és a Manual Posts megoldás ott logikus

## [0.5.0] — 2026-05-15

### Hozzáadva
- **TikTok Feed widget** (`[wlw_tiktok_feed]`) — no-API, TikTok hivatalos `embed.js`-szel
  - Field-ek: `video_urls` (textarea, soronként/vesszővel), `columns` (1-3)
  - Video ID-t kinyeri a URL-ből regex-szel (`@user/video/ID` vagy `/v/ID` vagy `/t/ID` formák)
  - Gutenberg block: `weblock-widgets/tiktok-feed`
- Súgó tábla bővítve a TikTok sorral
- Frontend CSS: reszponzív grid 1-3 oszlop

## [0.4.0] — 2026-05-15

### Hozzáadva
- **`requires_api` meta flag** widgetekhez (true csak Google Reviews-nál)
- **textarea** mezőtípus a `render_field()`-ben (Instagram poszt URL-ek listája)
- Settings notice: "4 widget API kulcs nélkül működik" magyarázat
- Súgó oldalon mátrix-tábla: widget × kell-e API × mit kell csak

### Változott (BREAKING)
- **YouTube Gallery** → API hívás (`youtube/v3/search`) lecserélve **YouTube RSS feed**-re (`feeds/videos.xml?channel_id=...`)
  - Field-ek változatlanok (`channel_id`, `playlist_id`)
  - Max 15 videó (YouTube RSS limit)
- **Google Map** → Maps Embed API + key lecserélve `maps.google.com/maps?output=embed`-re
  - `place_id` és `mode` mezők eltávolítva (csak `address` marad)
- **Facebook Feed** → Graph API + token lecserélve **Page Plugin** iframe-re
  - Mezőcsere: `count` + `layout` + `show_image` ELTÁVOLÍTVA
  - Új mezők: `page_url`, `tabs` (timeline/events/messages), `height`, `show_cover`, `show_facepile`, `small_header`
- **Instagram Feed** → Graph API + token lecserélve **Instagram embed.js**-re
  - Mezőcsere: `count`, `layout`, `show_caption` (limited) ELTÁVOLÍTVA
  - Új mezők: `post_urls` (textarea, soronként/vesszővel), `columns` (1-4), `show_caption`
- **Settings page leegyszerűsítve**: csak `google_api_key` + `cache_ttl`
- **API status badge** a galériában: "Nem kell API kulcs" vs "API kulcs OK" vs "API kulcs kell"

### Eltávolítva
- `instagram_token`, `facebook_token`, `facebook_page_id`, `youtube_api_key` settings
- Google Reviews helper text frissítve: "ingyenes, havi $200 credit van"

## [0.3.0] — 2026-05-15

### Hozzáadva
- **Place ID kereső** a Google Reviews konfigurátorban
  - Cégnév + város beírásra Google Places Text Search-re hív
  - Eredmény-lista: név + csillag-rating + cím
  - Klikk találatra → auto-kitölti a hidden `place_id` mezőt
  - Ha közvetlenül `ChIJ...` Place ID-t írsz be → direkt használja
  - "Csere" gomb a kiválasztott Place ID cseréjéhez
- Új `place_search` mezőtípus a `render_field()`-ben (generic, bármilyen widget használhatja)
- Új AJAX endpoint: `wp_ajax_wlw_search_place` (admin-only, nonce-védett)

### Változott
- Google Reviews `place_id` mező type-ja `text` → `place_search`
- Mező címke: "Google Place ID" → "Cégnév vagy Google Place ID"

## [0.2.0] — 2026-05-15

### Hozzáadva — vizuális admin felület
- **Widget galéria** — 5 widget kártya ikonnal, leírással, API-státusz badge-dzsel (Beállítva / API kulcs kell)
- **Konfigurátor oldal** widgetenkénti paraméter-űrlappal
  - Mező-típusok: text, number, select, toggle (kapcsoló)
  - Kötelező mezők jelzése, segédszövegek (help), placeholder-ek
  - Élő shortcode-generálás minden form-változásra
  - Default értékeknél a paraméter NEM kerül be a shortcode-ba (tisztább kimenet)
- **AJAX előnézet** — `do_shortcode()` futtatás admin-only endpoint-ról, debounce-olva (600 ms)
- **Vágólapra másolás** — Clipboard API + `execCommand` fallback
- **Tab-os admin szerkezet**:
  - `Widgetek` (default landing) — galéria + konfigurátor
  - `Beállítások` — API kulcsok + Cache (TTL + flush)
  - `Súgó` — API kulcs beszerzési útmutatók
- Reszponzív admin CSS (mobile-first, 4 töréspont)
- Frontend `widgets.css` betöltése az admin felületben az élő preview hűségéhez

### Változott
- `AbstractWidget` osztály bővítve `get_meta()` metódussal (id, label, icon, color, description, fields[])
- Az 5 widget mindegyike implementálja `get_meta()`-t — generic UI-render

### Megjegyzés
- Shortcode-okat továbbra is támogatja, csak már NEM KÖTELEZŐ kézzel megírni
- Gutenberg block-ok érintetlenül működnek

## [0.1.0] — 2026-05-15

### Hozzáadva
- Plugin csontváz (autoloader, PSR-4-szerű namespace `WeblockWidgets\`)
- Admin settings page (Google API key, Instagram token, Facebook token+page id, YouTube key, cache TTL)
- `ApiCache` wrapper (transient + `wp_remote_get`, JSON parse, hibakezelés)
- **Google Reviews** widget — Places API, 3 layout (grid/list/carousel), shortcode `[wlw_google_reviews]` + Gutenberg block
- **Instagram Feed** widget — Graph API, 2 layout (grid/slider), shortcode `[wlw_instagram_feed]` + block
- **Facebook Feed** widget — Graph API, 2 layout (list/grid), shortcode `[wlw_facebook_feed]` + block
- **YouTube Gallery** widget — Data API v3, channel + playlist support, 2 layout, shortcode `[wlw_youtube_gallery]` + block
- **Google Maps** widget — Maps Embed API, shortcode `[wlw_google_map]` + block
- Reszponzív frontend CSS (mobile-first, 6 töréspont: 480/768/1024/1440/1920/2560), fluid `clamp()` méretezés
- Carousel JS (gomb + scroll-snap, prefers-reduced-motion-safe)
- Dark mode támogatás (`prefers-color-scheme` + `[data-theme="dark"]`)
- Plugin Update Checker (YahnisElsts/plugin-update-checker v5.5) GitHub Releases auto-update

### Tervezett (következő release)
- Yelp / TripAdvisor / Booking reviews (Fázis 2 — Trustindex parity)
- AI-válaszgenerálás Google Reviews-hoz
- Review-collector (email kampány + QR kód)
