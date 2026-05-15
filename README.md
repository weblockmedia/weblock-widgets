# Weblock Widgets

Agency-tulajdonú WordPress plugin a **Trustindex.io** és **Elfsight.com** előfizetések kiváltására. Egységes pluginban kínál Google Reviews, Instagram, Facebook, YouTube és Google Maps widgeteket — shortcode-dal és Gutenberg block-ként egyaránt.

## Funkciók (Fázis 1 — MVP, v0.1.0)

| Widget | Shortcode | Block | Layout |
|---|---|---|---|
| Google Reviews | `[wlw_google_reviews]` | `weblock-widgets/google-reviews` | grid, list, carousel |
| Instagram Feed | `[wlw_instagram_feed]` | `weblock-widgets/instagram-feed` | grid, slider |
| Facebook Feed | `[wlw_facebook_feed]` | `weblock-widgets/facebook-feed` | list, grid |
| YouTube Gallery | `[wlw_youtube_gallery]` | `weblock-widgets/youtube-gallery` | grid, list |
| Google Maps | `[wlw_google_map]` | `weblock-widgets/google-map` | embed |

## Telepítés

1. Töltsd le a legutóbbi release ZIP-et: https://github.com/weblockmedia/weblock-widgets/releases
2. WP Admin → Plugins → Add New → Upload Plugin → kiválasztod a ZIP-et → Install Now → Activate
3. WP Admin → **Weblock Widgets** menü → töltsd ki az API kulcsokat
4. Használd a shortcode-ot vagy a Gutenberg blokkot bármelyik oldalon

A telepítés után a plugin automatikusan frissül a GitHub Releases-ről (Plugin Update Checker).

## Privát repo + auto-update

Ha privát a GitHub repo, a `wp-config.php`-be add hozzá:
```php
define( 'WLW_GITHUB_TOKEN', 'ghp_xxxxx' ); // fine-grained token: contents read
```

## Példák

### Google Reviews — grid layout, 6 vélemény, csak 4+ csillag
```
[wlw_google_reviews place_id="ChIJN1t_tDeuEmsRUsoyG83frY4" count="6" layout="grid" min_rating="4"]
```

### Instagram — 9 kép grid
```
[wlw_instagram_feed count="9" layout="grid"]
```

### Facebook — utolsó 5 poszt list nézetben
```
[wlw_facebook_feed count="5" layout="list"]
```

### YouTube channel utolsó 6 videója
```
[wlw_youtube_gallery channel_id="UCBR8-60-B28hp2BmDPdntcQ" count="6" layout="grid"]
```

### YouTube playlist
```
[wlw_youtube_gallery playlist_id="PLxxxxxxxxxxx" count="10"]
```

### Google Map
```
[wlw_google_map address="Budapest, Király utca 1." zoom="15" height="400"]
```

## Architektúra

```
weblock-widgets/
├── weblock-widgets.php          # Bootstrap, hooks, PUC init
├── includes/
│   ├── Core/
│   │   ├── Loader.php           # autoloader, plugin boot
│   │   ├── Admin.php            # admin settings page
│   │   └── ApiCache.php         # transient + remote_get wrapper
│   └── Widgets/
│       ├── AbstractWidget.php   # közös shortcode + block alaposztály
│       ├── Reviews/GoogleReviews.php
│       ├── Social/InstagramFeed.php
│       ├── Social/FacebookFeed.php
│       ├── Social/YoutubeGallery.php
│       └── Tools/GoogleMaps.php
├── templates/                   # PHP template-ek widget+layout szerint
├── assets/css/widgets.css       # fluid, mobile-first frontend stílusok
├── assets/css/admin.css
├── assets/js/widgets.js         # carousel logika
├── vendor/plugin-update-checker/  # YahnisElsts/PUC v5.5
└── languages/                   # i18n (hu_HU később)
```

## Reszponzív breakpointok

Mobile-first CSS, `clamp()` fluid méretezés, soha nincs hardcoded layout px.

| Breakpoint | px | Mit változik |
|---|---|---|
| Mobile | 0-479 | 1-2 oszlop, carousel nav rejtve |
| Mobile L | 480-767 | 2-3 oszlop |
| Tablet | 768-1023 | Facebook list horizontális, carousel nav megjelenik |
| Desktop | 1024-1439 | 3-4 oszlop reviews/grid |
| Desktop XL | 1440-1919 | 4 oszlop reviews |
| 4K | 1920+ | 4-6 oszlop IG |

## Licensz

GPL-2.0-or-later

## Roadmap

Lásd: `~/Desktop/Claude/wordpress/docs/09-sajat-plugin-trustindex-elfsight.md` (Fázis 1-2-3-4 ütemezés)
