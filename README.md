# Constructor - Drupal 11 Installation Profile

A customizable Drupal 11 installation profile with a multi-step setup wizard for configuring languages, content types, modules, layout, and AI content integration.

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
  - **Content Team**: Team member content type with block display
  - **Content Services**: Services content type with methods block
  - **Language Switcher**: Custom language switching modal/drawer
  - **Constructor Hero**: Configurable Hero, What We Do, and Booking Modal blocks

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
├── composer.json                 # PHP dependencies
├── web/
│   ├── modules/
│   │   └── custom/
│   │       ├── constructor_hero/         # Hero blocks module
│   │       ├── content_faq/              # FAQ content type module
│   │       ├── content_services/         # Services content type module
│   │       ├── content_team/             # Team member content type module
│   │       ├── language_switcher/        # Language switcher block
│   │       ├── openai_provider/          # OpenAI integration
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
  - Article
  - Team Member
  - FAQ
  - Service

### Step 6: Design & Layout
- Dark mode toggle
- Front page configuration
- Block placement

### Step 7: AI Integration
- OpenAI API configuration
- Model selection (GPT-4o, GPT-4, etc.)
- Automatic content generation

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

1. **Node.js version**: Tailwind CSS v4 requires Node.js 22+. Use nvm to switch:
   ```bash
   nvm use 22
   ```

2. **Permission issues**: Ensure `web/sites/default/files` is writable

3. **Test themes visible**: Add to settings.php:
   ```php
   $settings['extension_discovery_scan_tests'] = FALSE;
   ```

## License

GPL-2.0-or-later

## Repository

https://github.com/kindrakevich-agency/constructor
