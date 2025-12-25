# Constructor - Drupal 11 Installation Profile

A customizable Drupal 11 installation profile with a multi-step setup wizard for configuring languages, content types, modules, layout, and AI content integration.

## Screenshots

### Installation Wizard
![Installation Wizard](installer.png)

### Frontend Design
![Frontend Design](design.png)

## Features

- **Multi-step Installation Wizard**: 7-step guided setup process
  - Choose Language: Select installation language
  - Database Setup: Configure database connection
  - Site Basics: Site name, admin account, email, site description
  - Languages: Multi-language support with content and interface translation
  - Content Types: Pre-configured types with customizable fields
  - Design & Layout: Theme settings and block configuration
  - AI Integration: OpenAI-powered content generation

- **Custom Modules**:
  - **OpenAI Provider**: AI content generation with GPT and DALL-E support
  - **Simple Metatag**: SEO metatags with path-based overrides and Google Tag support
  - **Simple Sitemap Generator**: XML sitemaps with multi-domain support
  - **Content FAQ**: FAQ content type with accordion block
  - **Content Team**: Team member content type with carousel block
  - **Content Services**: Services content type with methods block
  - **Content Article**: Article content type with video support and blocks
  - **Content Commerce**: Product content type with e-commerce blocks
  - **Language Switcher**: Custom language switching modal/drawer
  - **Constructor Hero**: Configurable Hero, What We Do, and Booking Modal blocks
  - **Form Sender**: Universal API for sending form data via Email and Telegram
  - **Contact Form**: Contact form block with configurable fields, map, and success modal
  - **Gallery**: Image gallery with admin interface and PhotoSwipe lightbox
  - **Pricing Plans**: Pricing plans with configurable block and Form Sender integration

- **Pre-configured Core Modules**:
  - Pathauto: Automatic URL alias generation
  - Views: Content listing and display
  - Media: Media library management
  - Content Translation: Multilingual content support

- **Development-Ready**:
  - CSS/JS aggregation disabled by default
  - Twig debug mode enabled
  - All caching disabled (render, page, dynamic page)
  - Full HTML text format included

- **Custom Theme**: Modern, responsive theme built with Tailwind CSS v4
  - Dark mode support (class-based toggle)
  - Full template override for clean HTML output
  - Plyr video player integration
  - Swiper.js carousels
  - PhotoSwipe lightbox
  - Mobile-first responsive design
  - Custom breakpoints (3xl: 1920px, 4xl: 2560px, 5xl: 3200px)

- **Multilingual Support**:
  - Ukrainian translations included for all custom modules
  - Interface translation
  - Content translation with AI-powered automatic translation

## Requirements

- PHP 8.3 or higher
- MySQL 8.0 or MariaDB 10.6+
- Composer 2.x
- Node.js 22+ (for Tailwind CSS v4)
- Lando (for local development)

## Installation

### Using Lando (Recommended)

1. Clone the repository:
   ```bash
   git clone https://github.com/kindrakevich-agency/constructor.git
   cd constructor
   ```

2. Start Lando:
   ```bash
   lando start
   ```

3. Install Composer dependencies:
   ```bash
   lando composer install
   ```

4. Access your site at: https://constructor.lndo.site and follow the installation wizard

   Or install via CLI:
   ```bash
   lando drush site:install constructor --yes --account-name=admin --account-pass=admin
   ```

### Theme Development

1. Navigate to the theme directory:
   ```bash
   cd web/themes/custom/constructor_theme
   ```

2. Install Node dependencies:
   ```bash
   npm install
   ```

3. Build CSS (use Node 22+):
   ```bash
   nvm use 22
   npm run build
   ```

4. Watch for changes (development):
   ```bash
   npm run watch
   ```

## Project Structure

```
constructor/
├── .lando.yml                    # Lando configuration
├── .lando/
│   ├── php.ini                   # PHP configuration (timeouts, memory)
│   └── nginx.conf                # Nginx configuration (timeouts)
├── composer.json                 # PHP dependencies
├── web/
│   ├── modules/
│   │   └── custom/
│   │       ├── constructor_hero/         # Hero blocks module
│   │       ├── contact_form/             # Contact form block module
│   │       ├── content_article/          # Article content type module
│   │       ├── content_commerce/         # Commerce/Product module
│   │       ├── content_faq/              # FAQ content type module
│   │       ├── content_services/         # Services content type module
│   │       ├── content_team/             # Team member content type module
│   │       ├── form_sender/              # Form sending API (Email/Telegram)
│   │       ├── gallery/                  # Image gallery with admin
│   │       ├── language_switcher/        # Language switcher block
│   │       ├── openai_provider/          # OpenAI integration
│   │       ├── pricing_plans/            # Pricing plans block
│   │       ├── simple_metatag/           # SEO module
│   │       └── simple_sitemap_generator/ # Sitemap module
│   ├── profiles/
│   │   └── custom/
│   │       └── constructor/              # Installation profile
│   │           ├── config/install/       # Default configurations
│   │           ├── themes/
│   │           │   └── constructor_install/  # Installer theme
│   │           ├── translations/         # Profile translations
│   │           └── src/Form/             # Wizard form classes
│   └── themes/
│       └── custom/
│           └── constructor_theme/        # Custom frontend theme
│               ├── css/                  # Compiled CSS
│               ├── js/                   # JavaScript files
│               ├── src/input.css         # Tailwind source
│               ├── translations/         # Theme translations
│               └── templates/            # Twig templates
└── README.md
```

## Custom Modules

### Constructor Hero
Provides configurable hero blocks:
- **Hero Block**: Full-width hero with title, description, email form, stats, rating, image
- **What We Do Block**: Section with badge, title, description, CTA buttons
- **Booking Modal Block**: Configurable booking form modal/drawer

All elements are configurable via block settings in the admin UI.

### Content Article
- Article content type with body, image, and YouTube video URL fields
- **Articles Block**: Grid display of latest articles with video thumbnails
- **Article Video Block**: Full-width video banner for video articles
- Articles listing page at `/articles`
- Single article page with Plyr video player

### Content Commerce
- Product content type with multiple images, pricing, colors, sizes
- Product Category taxonomy
- Commerce settings (currency, shipping info)
- **Product Carousel Block**: Swiper-based product slider with collection banner
- **Product Sale Hero Block**: "Hottest Sale" promotional banner
- **Single Product Block**: Product detail with gallery, color swatches, size selector
- **Products List Block**: Category filters, feature cards, product grid
- Products listing page at `/products`

### Content FAQ
- FAQ content type with question/answer fields
- FAQ Block with accordion display
- FAQ page with contact CTA

### Content Services
- Service content type with description and icon
- Services Block for listing services
- Service Methods Block with image and features

### Content Team
- Team Member content type with photo, position, bio
- Team Block with carousel display
- Team page listing

### Form Sender
Universal API for sending form data:
- Email sending with configurable recipient and subject prefix
- Telegram Bot API integration
- Settings page at `/admin/config/services/form-sender`
- Test sending functionality
- Usage: `\Drupal::service('form_sender')->send($data)`

### Contact Form
Contact form block with:
- Configurable header (title, subtitle, description)
- Form fields: Name, Email, Company, Subject, Message
- Honeypot spam protection (no CAPTCHA needed)
- Contact info section (phone, email, working hours)
- Interactive OpenStreetMap with configurable coordinates
- Office address card with directions link
- Success modal (desktop) / drawer (mobile) matching language switcher style
- Pure JavaScript form submission via JSON API (no jQuery)

### Gallery
Image gallery module with:
- Admin interface at `/admin/content/gallery` (tab under Content)
- Add image form with managed file upload
- Image list with thumbnails, alt text, date, delete button
- **Gallery Block**: Configurable block with label, title, description, CTA button
- Gallery page at `/gallery` with all images
- PhotoSwipe lightbox integration for fullscreen viewing
- Example images created during installation

### Pricing Plans
Pricing plans module with:
- Pricing Plan content type (title, price, period, features, recommended flag)
- **Pricing Block**: Configurable pricing cards display
- Form Sender integration for plan selection
- Success modal/drawer on form submission
- Example plans (Basic, Pro, Enterprise) created during installation
- Ukrainian translations included

### Language Switcher
- Language selection modal (desktop)
- Language selection drawer (mobile)
- Automatic placement in header and footer

### OpenAI Provider
- GPT text generation
- DALL-E image generation
- Settings form for API configuration
- Test form for API validation

### Simple Metatag
- Automatic metatag generation for nodes and terms
- Path-based metatag overrides
- Google Tag (gtag.js) integration
- Multi-domain support (with Domain module)

### Simple Sitemap Generator
- XML sitemap generation
- Multi-domain support
- Custom URLs
- Configurable content types
- Cache management

## Lando Commands

| Command | Description |
|---------|-------------|
| `lando start` | Start the Lando environment |
| `lando stop` | Stop the Lando environment |
| `lando rebuild` | Rebuild the Lando environment |
| `lando drush <cmd>` | Run Drush commands |
| `lando composer <cmd>` | Run Composer commands |
| `lando mysql` | Access MySQL CLI |

## Database Connection

### Internal (from Lando containers)
| Setting | Value |
|---------|-------|
| Host | `database` |
| Port | `3306` |
| Database | `drupal` |
| Username | `drupal` |
| Password | `drupal` |

### External (from host machine)
| Setting | Value |
|---------|-------|
| Host | `127.0.0.1` |
| Port | Run `lando info` to get current port |
| Database | `drupal` |
| Username | `drupal` |
| Password | `drupal` |

## Installation Wizard Steps

### Step 1: Choose Language
- Select installation language (English, Ukrainian, etc.)

### Step 2: Database Setup
- Configure database connection

### Step 3: Site Basics
- Site name and slogan
- Site email
- Administrator account (username, email, password)

### Step 4: Languages
- Enable multilingual support
- Add additional languages
- Configure content translation

### Step 5: Content Types
- Select from pre-configured content types:
  - Basic Page
  - Article (with video support)
  - Team Member
  - FAQ
  - Service
  - Product (e-commerce)

### Step 6: Design & Layout
- Dark mode toggle
- Front page configuration
- Block placement

### Step 7: AI Integration
- OpenAI API configuration
- Model selection (GPT-4o, GPT-4, etc.)
- Automatic content generation for all enabled content types

## Theme Features

### Tailwind CSS v4
The theme uses Tailwind CSS v4 with the standalone CLI:
- CSS nesting support
- Class-based dark mode via `@variant dark`
- Custom component styles
- Custom breakpoints for large screens

### Template Structure
All Drupal templates are overridden for clean HTML output:
- Minimal wrapper elements
- Semantic HTML5 structure
- Tailwind utility classes

### Dark Mode
Toggle dark mode by adding/removing the `dark` class on the `<html>` element.
Theme toggle button included in header.

## Translations

Ukrainian translations are included for:
- All custom modules
- Constructor theme
- Installation profile

Import translations:
```bash
lando drush locale:import uk /app/web/modules/custom/MODULE_NAME/translations/uk.po --override=all
```

## Development

### Building Theme CSS
```bash
cd web/themes/custom/constructor_theme
nvm use 22
npm run build
```

### Clearing Caches
```bash
lando drush cr
```

### Viewing Logs
```bash
lando drush watchdog:show
```

## Troubleshooting

### Common Issues

1. **504 Gateway Timeout during installation**: The installation with AI content generation can take several minutes. The Lando configuration includes extended timeouts (10 minutes) for nginx and PHP. If you still get timeouts:
   ```bash
   lando rebuild -y
   ```

2. **Node.js version**: Tailwind CSS v4 requires Node.js 22+. Use nvm to switch:
   ```bash
   nvm use 22
   ```

3. **Permission issues**: Ensure `web/sites/default/files` is writable

4. **Test themes visible**: Add to settings.php:
   ```php
   $settings['extension_discovery_scan_tests'] = FALSE;
   ```

5. **Clear database for fresh install**:
   ```bash
   lando drush sql-drop -y
   ```

## License

GPL-2.0-or-later

## Repository

https://github.com/kindrakevich-agency/constructor
