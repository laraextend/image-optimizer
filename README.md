<p align="center">
  <a href="https://packagist.org/packages/laraextend/media-toolkit"><img src="https://img.shields.io/packagist/v/laraextend/media-toolkit.svg?style=flat-square" alt="Latest Version on Packagist"></a>
  <a href="https://packagist.org/packages/laraextend/media-toolkit"><img src="https://img.shields.io/packagist/dt/laraextend/media-toolkit.svg?style=flat-square" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/laraextend/media-toolkit"><img src="https://img.shields.io/packagist/php-v/laraextend/media-toolkit.svg?style=flat-square" alt="PHP Version"></a>
  <a href="LICENSE.md"><img src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square" alt="License"></a>
</p>

# Laravel Media Toolkit

**A comprehensive Laravel media toolkit for automatic image optimization, responsive variants, next-gen formats and more — ready to use directly in Blade.**

`laraextend/media-toolkit` handles the heavy lifting for you: images are automatically resized, compressed, converted to modern formats (WebP, AVIF) and rendered as responsive `<img>` or `<picture>` tags. Comes with smart caching, Artisan commands, simple Blade helpers and Blade components.

> **Roadmap:** Future releases will extend the toolkit to cover animated images (GIF/APNG/WebP animated), vector graphics (SVG), audio and video processing.

---

## ✨ Features

- **🖼️ Four Blade Helpers** — `img()`, `responsive_img()`, `picture()` and `img_url()` for every use case
- **🧩 Four Blade Components** — `<x-laraextend::img>`, `<x-laraextend::responsive-img>`, `<x-laraextend::picture>`, `<x-laraextend::img-url>`
- **📐 Automatic Responsive Variants** — Generates 5 breakpoint sizes (0.5×, 0.75×, 1×, 1.5×, 2×) with `srcset`
- **🎨 Next-Gen Formats** — WebP, AVIF, JPEG, PNG — with automatic fallback if the server lacks support
- **⚡ Smart Caching** — Manifest-based cache with automatic invalidation when source files change
- **🔧 Artisan Commands** — `media:img-clear` and `media:img-warm` for cache management
- **⚙️ Configurable** — Publish `config/media-toolkit.php` to customize quality, formats, breakpoints and more
- **🏎️ Performance-Optimized** — Lazy loading, `fetchpriority`, `decoding="async"` by default
- **🛡️ Memory-Safe Fallback** — Automatically serves original images when GD memory would be exceeded
- **📦 Zero Config** — Works immediately after installation, no configuration required
- **🔄 GD & Imagick** — Automatic driver detection, uses whichever is available
- **🏷️ Flexible HTML Attributes** — Custom classes, IDs and arbitrary attributes supported
- **📁 Original Mode** — Serve images unmodified (without any processing) when needed
- **⚡ Livewire & Alpine.js Ready** — Blade components forward `wire:*`, `x-*` and `data-*` attributes automatically

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
composer require laraextend/media-toolkit
```

> The ServiceProvider is registered automatically via Laravel's Auto-Discovery.

### 2. Done!

That's it. No config files, no migrations, no additional steps needed.

### 3. Optional Configuration

If you want to customize defaults, publish the config file:

```bash
php artisan vendor:publish --tag=media-toolkit-config
```

Published file: `config/media-toolkit.php`

Default config schema:

```php
return [
    'driver'     => env('MEDIA_TOOLKIT_DRIVER', 'auto'),
    'output_dir' => env('MEDIA_TOOLKIT_OUTPUT_DIR', 'img/optimized'),

    'responsive' => [
        'size_factors' => [0.5, 0.75, 1.0, 1.5, 2.0],
        'min_width'    => 100,
    ],

    'quality' => [
        'webp' => 80,
        'avif' => 65,
        'jpg'  => 82,
        'jpeg' => 82,
        'png'  => 85,
    ],

    'defaults' => [
        'format'          => 'webp',
        'picture_formats' => ['avif', 'webp'],
        'fallback_format' => 'jpg',
        'loading'         => 'lazy',
        'fetchpriority'   => 'auto',
        'sizes'           => '100vw',
    ],
];
```

Example `.env` overrides:

```dotenv
MEDIA_TOOLKIT_DRIVER=auto
MEDIA_TOOLKIT_OUTPUT_DIR=img/optimized
```

---

## 📖 Usage

### Source Images

Source images are referenced relative to the **project root** (`base_path()`). Images can live anywhere in your project, for example:

```
resources/views/pages/home/hero.jpg
resources/images/logo.png
storage/app/uploads/photo.jpg
```

Optimized variants are automatically stored in `public/<output_dir>/` (default: `public/img/optimized/`) and served from there.

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
| `width` | `?int` | `null` | Desired width in pixels (null + null height = original width) |
| `height` | `?int` | `null` | Desired height in pixels (null + null width = original height) |
| `class` | `string` | `''` | CSS class(es) for the `<img>` element |
| `format` | `?string` | config default (`webp`) | Target format: `webp`, `avif`, `jpg`, `png` |
| `loading` | `?string` | config default (`lazy`) | Loading behavior: `lazy` or `eager` |
| `fetchpriority` | `?string` | config default (`auto`) | Fetch priority: `auto`, `high`, `low` |
| `id` | `?string` | `null` | HTML ID for the element |
| `original` | `bool` | `false` | `true` = serve original file without optimization |
| `attributes` | `array` | `[]` | Additional HTML attributes as key-value array |

> If only one dimension is provided, the other one is calculated proportionally from the original image.

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

**Additional Parameters (on top of `img()`):**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `sizes` | `?string` | config default (`100vw`) | The `sizes` attribute for responsive selection |

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

**Additional Parameters (on top of the previous ones):**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `formats` | `?array` | config default (`['avif', 'webp']`) | Modern formats for `<source>` elements |
| `fallbackFormat` | `?string` | config default (`jpg`) | Format for the `<img>` fallback element |
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
| `format` | `?string` | config default (`webp`) | Target format |
| `original` | `bool` | `false` | `true` = URL of the original file |

---

## 🧩 Blade Components

All four helpers are also available as Blade components under the `laraextend` namespace. They accept the same parameters as the helper functions.

> **Note:** Because `attributes` is a reserved word in Blade components, use `extra-attributes` to pass an explicit attribute array. Attributes placed directly on the component tag (e.g. `wire:key`, `data-*`) are forwarded automatically.

### `<x-laraextend::img>` — Single Optimized Image

```blade
<x-laraextend::img
    src="resources/images/logo.png"
    alt="Company Logo"
    :width="200"
    format="webp"
/>
```

---

### `<x-laraextend::responsive-img>` — Responsive with srcset

```blade
<x-laraextend::responsive-img
    src="resources/images/hero.jpg"
    alt="Hero Banner"
    :width="800"
    fetchpriority="high"
    sizes="(max-width: 768px) 100vw, 800px"
/>
```

---

### `<x-laraextend::picture>` — Multi-Format with Fallback

```blade
<x-laraextend::picture
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

### `<x-laraextend::img-url>` — URL Only

```blade
<div style="background-image: url('<x-laraextend::img-url src="resources/images/bg.jpg" :width="1920" />')">
```

---

## ⚡ Livewire & Alpine.js

Blade components forward all attributes from the Blade attribute bag automatically — including `wire:*`, `x-*`, `@*` and `data-*`. You do **not** need `extra-attributes` for Livewire or Alpine directives.

### Attribute routing

| Component | Bag attributes applied to |
|-----------|--------------------------
| `<x-laraextend::img>` | `<img>` |
| `<x-laraextend::responsive-img>` | `<img>` |
| `<x-laraextend::picture>` | `<picture>` (outermost element) |

For `<picture>`, bag attributes land on the outer `<picture>` tag so that `wire:key` is placed on the correct element for Livewire DOM diffing. Use `extra-attributes` to pass additional attributes specifically to the inner `<img>`.

### Examples

```blade
{{-- Livewire list — wire:key on <img> --}}
@foreach ($images as $image)
    <x-laraextend::img
        :src="$image->path"
        :alt="$image->title"
        :width="400"
        wire:key="image-{{ $image->id }}"
    />
@endforeach

{{-- Livewire list — wire:key on <picture> (outermost) --}}
@foreach ($images as $image)
    <x-laraextend::picture
        :src="$image->path"
        :alt="$image->title"
        :width="400"
        wire:key="picture-{{ $image->id }}"
    />
@endforeach

{{-- Alpine.js lazy-reveal --}}
<x-laraextend::img
    src="resources/images/hero.jpg"
    alt="Hero"
    :width="800"
    x-show="visible"
    x-transition
/>

{{-- wire:navigate for SPA-style page loads --}}
<x-laraextend::img
    src="resources/images/logo.png"
    alt="Logo"
    :width="180"
    wire:navigate
/>
```

> **Tip:** `wire:navigate` and similar boolean-like directives render as `wire:navigate=""`, which is valid HTML and works correctly with Livewire.

---

## ⚙️ Configuration

After publishing the config file, you can customize all default values:

```bash
php artisan vendor:publish --tag=media-toolkit-config
```

**`config/media-toolkit.php`:**

```php
return [
    // Image processing driver: 'auto' (recommended), 'gd', or 'imagick'
    'driver'     => env('MEDIA_TOOLKIT_DRIVER', 'auto'),

    // Output directory relative to public/
    'output_dir' => env('MEDIA_TOOLKIT_OUTPUT_DIR', 'img/optimized'),

    // Responsive breakpoints
    'responsive' => [
        'size_factors' => [0.5, 0.75, 1.0, 1.5, 2.0], // multipliers of the requested width
        'min_width'    => 100,                           // skip variants narrower than this
    ],

    // Image quality per format (1–100)
    'quality' => [
        'webp' => 80,
        'avif' => 65,
        'jpg'  => 82,
        'jpeg' => 82,
        'png'  => 85,
    ],

    // Default HTML attributes and format choices
    'defaults' => [
        'format'          => 'webp',
        'picture_formats' => ['avif', 'webp'],
        'fallback_format' => 'jpg',
        'loading'         => 'lazy',
        'fetchpriority'   => 'auto',
        'sizes'           => '100vw',
    ],
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

Use the `attributes` parameter (helpers) or pass attributes directly on components:

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

{{-- Component — direct attributes are forwarded automatically --}}
<x-laraextend::img
    src="resources/images/photo.jpg"
    alt="Photo"
    :width="600"
    data-lightbox="gallery"
    style="border-radius: 8px"
/>

{{-- Component — programmatic attribute arrays via extra-attributes --}}
<x-laraextend::img
    src="resources/images/photo.jpg"
    alt="Photo"
    :width="600"
    :extra-attributes="['data-lightbox' => 'gallery', 'style' => 'border-radius: 8px']"
/>
```

### Memory-Safe Fallback (GD)

When using the GD driver, very large images can exceed PHP memory limits during optimization.
In that case, the package automatically falls back to the copied original image instead of throwing a fatal error.

Fallback output is marked on the rendered `<img>`:

```html
<img ... data-media-toolkit-status="original-fallback" data-media-toolkit-reason="memory-limit">
```

Possible `data-media-toolkit-reason` values:
- `memory-limit` — optimization skipped proactively due to memory estimate
- `optimization-error` — optimization failed and fallback was applied

---

## 🔧 Artisan Commands

### Clear Cache

Deletes all optimized image variants from `public/<output_dir>/` (default: `public/img/optimized/`):

```bash
php artisan media:img-clear
```

```
✓ 42 cache entries deleted.
```

### Warm Cache

Checks all cached variants and regenerates outdated ones (when the source file has changed):

```bash
php artisan media:img-warm
```

```
Checking cache for outdated images...
✓ 3 regenerated, 39 up to date.
⚠ Source file not found: resources/images/deleted-image.jpg
```

> **Tip:** Run `media:img-warm` after every deployment to ensure all variants are up to date.

### In Your Deployment Pipeline

```bash
# In your deployment script:
php artisan media:img-clear    # Optional: rebuild everything from scratch
php artisan media:img-warm     # Or: only regenerate outdated variants
```

---

## 💾 Caching — How It Works

Each combination of source file + width + format + mode creates a **dedicated cache directory** in `public/<output_dir>/` (default: `public/img/optimized/`):

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
<x-laraextend::picture
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
<x-laraextend::responsive-img
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
<x-laraextend::img
    src="resources/images/logo.png"
    alt="Company Logo"
    :width="180"
    format="webp"
    loading="eager"
/>
```

### CSS Background with Optimized Image

```blade
<section style="background-image: url('{{ img_url(src: 'resources/images/bg-pattern.jpg', width: 1920, format: 'webp') }}')">
    <h1>Welcome</h1>
</section>
```

### Open Graph & Social Media Meta Tags

```blade
<meta property="og:image" content="{{ url(img_url(src: 'resources/images/og-image.jpg', width: 1200, format: 'jpg')) }}">
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

### Livewire Repeater

```blade
{{-- wire:key on <img> --}}
@foreach ($items as $item)
    <x-laraextend::img
        :src="$item->image"
        :alt="$item->name"
        :width="300"
        wire:key="item-{{ $item->id }}"
        class="rounded"
    />
@endforeach

{{-- wire:key on <picture> (outermost element) --}}
@foreach ($items as $item)
    <x-laraextend::picture
        :src="$item->image"
        :alt="$item->name"
        :width="300"
        wire:key="item-{{ $item->id }}"
    />
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

Add the optimization directory to your `.gitignore` — variants are generated automatically (adjust if you changed `output_dir`):

```gitignore
/public/img/optimized/
```

---

## 🤝 Contributing

Contributions are welcome! Please fork the repository, create your feature branch and submit a pull request.

```bash
git clone https://github.com/laraextend/media-toolkit.git
cd media-toolkit
composer install
```

---

## 📄 Changelog

All notable changes are documented in the [CHANGELOG](CHANGELOG.md).

---

## 🔒 Security

If you discover a security vulnerability, please send an email to [security@laraextend.dev](mailto:security@laraextend.dev) instead of creating a public issue.

---

## 📜 License

MIT License. See [LICENSE](LICENSE.md) for details.

---

<p align="center">
  Made with ❤️ by <a href="https://github.com/laraextend">LaraExtend</a>
</p>
