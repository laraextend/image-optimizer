<p align="center">
  <a href="https://packagist.org/packages/laraexten/image-optimizer"><img src="https://img.shields.io/packagist/v/laraexten/image-optimizer.svg?style=flat-square" alt="Latest Version on Packagist"></a>
  <a href="https://packagist.org/packages/laraexten/image-optimizer"><img src="https://img.shields.io/packagist/dt/laraexten/image-optimizer.svg?style=flat-square" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/laraexten/image-optimizer"><img src="https://img.shields.io/packagist/php-v/laraexten/image-optimizer.svg?style=flat-square" alt="PHP Version"></a>
  <a href="LICENSE.md"><img src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square" alt="License"></a>
</p>

# Laravel Image Optimizer

**Automatic image optimization, responsive variants and next-gen formats for Laravel — ready to use directly in Blade.**

`laraexten/image-optimizer` handles the heavy lifting for you: images are automatically resized, compressed, converted to modern formats (WebP, AVIF) and rendered as responsive `<img>` or `<picture>` tags. Comes with smart caching, Artisan commands, simple Blade helpers and Blade components.

---

## ✨ Features

- **🖼️ Four Blade Helpers** — `img()`, `responsive_img()`, `picture()` and `img_url()` for every use case
- **🧩 Four Blade Components** — `<x-laraexten::img>`, `<x-laraexten::responsive-img>`, `<x-laraexten::picture>`, `<x-laraexten::img-url>`
- **📐 Automatic Responsive Variants** — Generates 5 breakpoint sizes (0.5×, 0.75×, 1×, 1.5×, 2×) with `srcset`
- **🎨 Next-Gen Formats** — WebP, AVIF, JPEG, PNG — with automatic fallback if the server lacks support
- **⚡ Smart Caching** — Manifest-based cache with automatic invalidation when source files change
- **🔧 Artisan Commands** — `img:clear` and `img:warm` for cache management
- **⚙️ Configurable** — Publish `config/image-optimizer.php` to customize quality, formats, breakpoints and more
- **🏎️ Performance-Optimized** — Lazy loading, `fetchpriority`, `decoding="async"` by default
- **📦 Zero Config** — Works immediately after installation, no configuration required
- **🔄 GD & Imagick** — Automatic driver detection, uses whichever is available
- **🏷️ Flexible HTML Attributes** — Custom classes, IDs and arbitrary attributes supported
- **📁 Original Mode** — Serve images unmodified (without any processing) when needed

---

## 📋 Requirements

- **PHP** >= 8.2
- **Laravel** >= 10.x
- **Intervention Image** >= 3.0 (`intervention/image`)
- **GD** or **Imagick** PHP extension
- Optional: AVIF support in GD (`imageavif`) or Imagick

---

## 🚀 Installation

### 1. Install the package via Composer

```bash
composer require laraexten/image-optimizer
```

> The ServiceProvider is registered automatically via Laravel's Auto-Discovery.

### 2. Done!

That's it. No config files, no migrations, no additional steps needed.

### Optional: Publish Config

```bash
php artisan vendor:publish --tag=image-optimizer-config
```

This creates `config/image-optimizer.php` in your Laravel project where you can customize quality settings, output directory, breakpoints and more.

---

## 📖 Usage

### Source Images

Source images are referenced relative to the **project root** (`base_path()`). Images can live anywhere in your project, for example:

```
resources/views/pages/home/hero.jpg
resources/images/logo.png
storage/app/uploads/photo.jpg
```

Optimized variants are automatically stored in `public/img/optimized/` and served from there.

---

## 🖼️ Blade Helpers

### 1. `img()` — Single Optimized Image

Generates a simple `<img>` tag with an optimized image. No `srcset` — ideal for icons, logos and fixed-size images.

```blade
{!! img(
    src: 'resources/images/logo.jpg',
    alt: 'Company Logo',
    width: 200,
    format: 'webp',
) !!}
```

**Output:**
```html
<img src="/img/optimized/a1b2c3d4e5f6/logo-200w.webp"
     alt="Company Logo"
     loading="lazy"
     decoding="async"
     fetchpriority="auto"
     width="200"
     height="80">
```

**All Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `src` | `string` | — | Path to source file (relative to `base_path()`) |
| `alt` | `string` | `''` | Alt text for accessibility |
| `width` | `?int` | `null` | Desired width in pixels (null = original size) |
| `height` | `?int` | `null` | Desired height in pixels (null = auto-calculated from aspect ratio) |
| `class` | `string` | `''` | CSS class(es) for the `<img>` element |
| `format` | `string` | `'webp'` | Target format: `webp`, `avif`, `jpg`, `png` |
| `loading` | `string` | `'lazy'` | Loading behavior: `lazy` or `eager` |
| `fetchpriority` | `string` | `'auto'` | Fetch priority: `auto`, `high`, `low` |
| `id` | `?string` | `null` | HTML ID for the element |
| `original` | `bool` | `false` | `true` = serve original file without optimization |
| `attributes` | `array` | `[]` | Additional HTML attributes as key-value array |

---

### 2. `responsive_img()` — Responsive with srcset

Generates an `<img>` with `srcset` and `sizes` — the browser automatically picks the best matching size.

```blade
{!! responsive_img(
    src: 'resources/images/hero.jpg',
    alt: 'Hero Banner',
    width: 800,
    format: 'webp',
    fetchpriority: 'high',
    sizes: '(max-width: 768px) 100vw, 800px',
) !!}
```

**Output:**
```html
<img src="/img/optimized/f6e5d4c3b2a1/hero-800w.webp"
     srcset="/img/optimized/f6e5d4c3b2a1/hero-400w.webp 400w,
            /img/optimized/f6e5d4c3b2a1/hero-600w.webp 600w,
            /img/optimized/f6e5d4c3b2a1/hero-800w.webp 800w,
            /img/optimized/f6e5d4c3b2a1/hero-1200w.webp 1200w,
            /img/optimized/f6e5d4c3b2a1/hero-1600w.webp 1600w"
     sizes="(max-width: 768px) 100vw, 800px"
     alt="Hero Banner"
     loading="eager"
     decoding="async"
     fetchpriority="high"
     width="800"
     height="450">
```

**Additional Parameters (on top of `img()`):**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `sizes` | `string` | `'100vw'` | The `sizes` attribute for responsive selection |

> **Tip:** When using `fetchpriority: 'high'`, `loading` is automatically set to `eager`.

---

### 3. `picture()` — Multi-Format with Fallback

Generates a `<picture>` element with a `<source>` for each modern format and an `<img>` fallback. **The best choice for maximum performance** — the browser picks the best supported format.

```blade
{!! picture(
    src: 'resources/images/hero.jpg',
    alt: 'Hero Banner',
    width: 800,
    formats: ['avif', 'webp'],
    fallbackFormat: 'jpg',
    fetchpriority: 'high',
    sizes: '(max-width: 768px) 100vw, 800px',
    class: 'hero-picture',
    imgClass: 'hero-img',
) !!}
```

**Output:**
```html
<picture class="hero-picture">
    <source type="image/avif"
            srcset="/img/optimized/.../hero-400w.avif 400w,
                   /img/optimized/.../hero-600w.avif 600w,
                   /img/optimized/.../hero-800w.avif 800w,
                   /img/optimized/.../hero-1200w.avif 1200w,
                   /img/optimized/.../hero-1600w.avif 1600w"
            sizes="(max-width: 768px) 100vw, 800px">
    <source type="image/webp"
            srcset="/img/optimized/.../hero-400w.webp 400w,
                   /img/optimized/.../hero-600w.webp 600w,
                   /img/optimized/.../hero-800w.webp 800w,
                   /img/optimized/.../hero-1200w.webp 1200w,
                   /img/optimized/.../hero-1600w.webp 1600w"
            sizes="(max-width: 768px) 100vw, 800px">
    <img src="/img/optimized/.../hero-800w.jpg"
         srcset="/img/optimized/.../hero-400w.jpg 400w,
                /img/optimized/.../hero-600w.jpg 600w,
                /img/optimized/.../hero-800w.jpg 800w,
                /img/optimized/.../hero-1200w.jpg 1200w,
                /img/optimized/.../hero-1600w.jpg 1600w"
         sizes="(max-width: 768px) 100vw, 800px"
         alt="Hero Banner"
         loading="eager"
         decoding="async"
         fetchpriority="high"
         width="800"
         height="450"
         class="hero-img">
</picture>
```

**Additional Parameters (on top of the previous ones):**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `formats` | `array` | `['avif', 'webp']` | Modern formats for `<source>` elements |
| `fallbackFormat` | `string` | `'jpg'` | Format for the `<img>` fallback element |
| `imgClass` | `string` | `''` | CSS class(es) for the `<img>` element |
| `sourceClass` | `string` | `''` | CSS class(es) for all `<source>` elements |

> **Note:** The `class` parameter on `picture()` is applied to the `<picture>` element, not to `<img>`.

---

### 4. `img_url()` — URL Only

Returns only the URL of the optimized image — perfect for CSS backgrounds, OG tags and anywhere you just need a URL.

```blade
{{-- CSS Background --}}
<div style="background-image: url('{{ img_url(src: 'resources/images/bg.jpg', width: 1920) }}')">

{{-- Open Graph Meta --}}
<meta property="og:image" content="{{ url(img_url(src: 'resources/images/og.jpg', width: 1200, format: 'jpg')) }}">

{{-- Original URL --}}
<a href="{{ img_url(src: 'resources/images/download.png', original: true) }}">Download</a>
```

**Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `src` | `string` | — | Path to source file |
| `width` | `?int` | `null` | Desired width (null = original) |
| `format` | `string` | `'webp'` | Target format |
| `original` | `bool` | `false` | `true` = URL of the original file |

---

## 🧩 Blade Components

All four helpers are also available as Blade components under the `laraexten` namespace. They accept the same parameters as the helper functions.

> **Note:** Because `attributes` is a reserved word in Blade components, use `extra-attributes` instead.

### `<x-laraexten::img>` — Single Optimized Image

```blade
<x-laraexten::img
    src="resources/images/logo.png"
    alt="Company Logo"
    :width="200"
    format="webp"
/>
```

---

### `<x-laraexten::responsive-img>` — Responsive with srcset

```blade
<x-laraexten::responsive-img
    src="resources/images/hero.jpg"
    alt="Hero Banner"
    :width="800"
    fetchpriority="high"
    sizes="(max-width: 768px) 100vw, 800px"
/>
```

---

### `<x-laraexten::picture>` — Multi-Format with Fallback

```blade
<x-laraexten::picture
    src="resources/images/hero.jpg"
    alt="Hero Banner"
    :width="800"
    :formats="['avif', 'webp']"
    fallback-format="jpg"
    fetchpriority="high"
    sizes="(max-width: 768px) 100vw, 800px"
    class="hero-picture"
    img-class="hero-img"
/>
```

---

### `<x-laraexten::img-url>` — URL Only

```blade
<div style="background-image: url('<x-laraexten::img-url src="resources/images/bg.jpg" :width="1920" />')">
```

---

## ⚙️ Configuration

After publishing the config file, you can customize all default values:

```bash
php artisan vendor:publish --tag=image-optimizer-config
```

**`config/image-optimizer.php`:**

```php
return [
    // Output directory relative to public/
    'output_dir' => 'img/optimized',

    // Image quality per format (1–100)
    'quality' => [
        'webp' => 80,
        'avif' => 65,
        'jpg'  => 82,
        'jpeg' => 82,
        'png'  => 85,
    ],

    // Responsive breakpoint multipliers
    'size_factors' => [0.5, 0.75, 1.0, 1.5, 2.0],

    // Minimum variant width in pixels
    'min_width' => 100,

    // Default formats
    'default_format'  => 'webp',
    'picture_formats' => ['avif', 'webp'],
    'fallback_format' => 'jpg',

    // Default HTML attributes
    'loading'       => 'lazy',
    'fetchpriority' => 'auto',
    'sizes'         => '100vw',
];
```

---

## 📐 Responsive Variants — How It Works

When you specify a `width` of e.g. `800`, the following variants are automatically generated:

| Factor | Calculation | Result |
|--------|-------------|--------|
| 0.5× | 800 × 0.5 | **400w** |
| 0.75× | 800 × 0.75 | **600w** |
| 1.0× | 800 × 1.0 | **800w** |
| 1.5× | 800 × 1.5 | **1200w** |
| 2.0× | 800 × 2.0 | **1600w** |

**Automatic constraints:**
- Variants smaller than **100px** are skipped
- Variants **wider than the original image** are skipped
- If the original is ≤ 2× the target width, the **original width** is added as an additional variant
- Duplicates are automatically removed

---

## 🎨 Supported Formats & Quality Settings

| Format | MIME Type | Default Quality | Notes |
|--------|-----------|-----------------|-------|
| **WebP** | `image/webp` | 80 | Best balance of size and quality |
| **AVIF** | `image/avif` | 65 | Smallest file size, slower compression |
| **JPEG** | `image/jpeg` | 82 | Universally compatible |
| **PNG** | `image/png` | 85 | Lossless, ideal for graphics with transparency |

### Automatic Format Fallback

If a format is not supported by the server, the fallback chain kicks in:

```
AVIF → WebP → JPEG
```

No need to worry — the package automatically selects the best available format.

---

## ⚡ Performance Features

### Automatic Loading Behavior

```blade
{{-- Default: Lazy Loading --}}
{!! img(src: '...', alt: '...') !!}
{{-- → loading="lazy" decoding="async" --}}

{{-- Above the Fold: Eager Loading + High Priority --}}
{!! img(src: '...', alt: '...', fetchpriority: 'high') !!}
{{-- → loading="eager" decoding="async" fetchpriority="high" --}}
```

> When using `fetchpriority: 'high'`, `loading` is automatically set to `eager`, even if explicitly set to `lazy`.

### Custom HTML Attributes

Use the `attributes` parameter (helpers) or `:extra-attributes` (components) to add any HTML attributes:

```blade
{{-- Helper --}}
{!! img(
    src: 'resources/images/photo.jpg',
    alt: 'Photo',
    width: 600,
    attributes: [
        'data-lightbox' => 'gallery',
        'data-caption' => 'A beautiful photo',
        'style' => 'border-radius: 8px',
    ],
) !!}

{{-- Component --}}
<x-laraexten::img
    src="resources/images/photo.jpg"
    alt="Photo"
    :width="600"
    :extra-attributes="['data-lightbox' => 'gallery', 'style' => 'border-radius: 8px']"
/>
```

---

## 🔧 Artisan Commands

### Clear Cache

Deletes all optimized image variants from `public/img/optimized/`:

```bash
php artisan img:clear
```

```
✓ 42 cache entries cleared.
```

### Warm Cache

Checks all cached variants and regenerates outdated ones (when the source file has changed):

```bash
php artisan img:warm
```

```
Checking cache for outdated images...
✓ 3 regenerated, 39 up to date.
⚠ Source file not found: resources/images/deleted-image.jpg
```

> **Tip:** Run `img:warm` after every deployment to ensure all variants are up to date.

### In Your Deployment Pipeline

```bash
# In your deployment script:
php artisan img:clear    # Optional: rebuild everything from scratch
php artisan img:warm     # Or: only regenerate outdated variants
```

---

## 💾 Caching — How It Works

Each combination of source file + width + format + mode creates a **dedicated cache directory** in `public/img/optimized/`:

```
public/img/optimized/
├── a1b2c3d4e5f6/          ← Hash of the combination
│   ├── manifest.json       ← Metadata + variant info
│   ├── hero-400w.webp
│   ├── hero-600w.webp
│   ├── hero-800w.webp
│   ├── hero-1200w.webp
│   └── hero-1600w.webp
├── f6e5d4c3b2a1/
│   ├── manifest.json
│   └── logo-200w.webp
└── originals/              ← Unmodified originals (when original: true)
    └── a1b2c3d4-photo.jpg
```

### Automatic Invalidation

The `manifest.json` stores the **timestamp of the source file**. On every request, the following check runs:

1. Does the cache directory with manifest exist? → **Yes**: Check timestamp
2. Has the source file changed? → **No**: Serve cached variants ✓
3. Has the source file changed? → **Yes**: Delete old cache, generate new variants

**This means:** You never have to manually clear the cache when replacing images. The package detects changes automatically.

---

## 🛠️ Practical Examples

### Hero Banner (Above the Fold)

```blade
{{-- Helper --}}
{!! picture(
    src: 'resources/views/pages/home/hero.jpg',
    alt: 'Welcome to our App',
    width: 1200,
    formats: ['avif', 'webp'],
    fallbackFormat: 'jpg',
    fetchpriority: 'high',
    sizes: '100vw',
    class: 'w-full',
    imgClass: 'w-full h-auto object-cover',
) !!}

{{-- Component --}}
<x-laraexten::picture
    src="resources/views/pages/home/hero.jpg"
    alt="Welcome to our App"
    :width="1200"
    :formats="['avif', 'webp']"
    fallback-format="jpg"
    fetchpriority="high"
    sizes="100vw"
    class="w-full"
    img-class="w-full h-auto object-cover"
/>
```

### Product Image in a Card

```blade
{{-- Helper --}}
{!! responsive_img(
    src: 'resources/images/products/' . $product->image,
    alt: $product->name,
    width: 400,
    format: 'webp',
    sizes: '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 400px',
    class: 'rounded-lg shadow-md',
) !!}

{{-- Component --}}
<x-laraexten::responsive-img
    :src="'resources/images/products/' . $product->image"
    :alt="$product->name"
    :width="400"
    format="webp"
    sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 400px"
    class="rounded-lg shadow-md"
/>
```

### Logo (Fixed Size)

```blade
<x-laraexten::img
    src="resources/images/logo.png"
    alt="Company Logo"
    :width="180"
    format="webp"
    loading="eager"
/>
```

### CSS Background with Optimized Image

```blade
<section style="background-image: url('{{ img_url(
    src: 'resources/images/bg-pattern.jpg',
    width: 1920,
    format: 'webp',
) }}')">
    <h1>Welcome</h1>
</section>
```

### Open Graph & Social Media Meta Tags

```blade
<meta property="og:image" content="{{ url(img_url(
    src: 'resources/images/og-image.jpg',
    width: 1200,
    format: 'jpg',
)) }}">
<meta property="og:image:width" content="1200">
```

### Gallery with Lightbox

```blade
@foreach ($images as $image)
    {!! responsive_img(
        src: $image->path,
        alt: $image->caption,
        width: 600,
        format: 'webp',
        sizes: '(max-width: 768px) 100vw, 33vw',
        class: 'gallery-thumb cursor-pointer',
        attributes: [
            'data-lightbox' => 'gallery',
            'data-full' => img_url(src: $image->path, width: 1800, format: 'jpg'),
        ],
    ) !!}
@endforeach
```

---

## ⚠️ Error Handling

In **local development** (`APP_ENV=local`), errors are rendered as HTML comments:

```html
<!-- IMG ERROR: File not found: resources/images/missing.jpg -->
```

In **production**, missing images return an empty string — no visible errors for end users.

---

## 🔀 GD vs. Imagick

The package automatically detects the available driver:

| Feature | GD | Imagick |
|---------|:--:|:-------:|
| JPEG | ✅ | ✅ |
| PNG | ✅ | ✅ |
| WebP | ✅ (if `imagewebp` available) | ✅ |
| AVIF | ✅ (if `imageavif` available, PHP 8.1+) | ✅ (if Imagick compiled with AVIF) |

> **Recommendation:** Imagick generally offers better quality and performance for large images. If available, it is automatically preferred.

---

## 📂 .gitignore

Add the optimization directory to your `.gitignore` — variants are generated automatically:

```gitignore
/public/img/optimized/
```

---

## 🤝 Contributing

Contributions are welcome! Please fork the repository, create your feature branch and submit a pull request.

```bash
git clone https://github.com/laraexten/image-optimizer.git
cd image-optimizer
composer install
```

---

## 📄 Changelog

All notable changes are documented in the [CHANGELOG](CHANGELOG.md).

---

## 🔒 Security

If you discover a security vulnerability, please send an email to [security@laraexten.dev](mailto:security@laraexten.dev) instead of creating a public issue.

---

## 📜 License

MIT License. See [LICENSE](LICENSE.md) for details.

---

<p align="center">
  Made with ❤️ by <a href="https://github.com/laraexten">LaraExten</a>
</p>
