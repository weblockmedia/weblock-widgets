# Changelog

A formátum [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) szerint, verziózás [Semantic Versioning](https://semver.org/lang/hu/).

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
