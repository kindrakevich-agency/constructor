# Constructor - Drupal 11 Installation Profile

A customizable Drupal 11 installation profile with a multi-step setup wizard for configuring languages, content types, modules, layout, and AI content integration.

![Constructor Installation Wizard](installer.png)

## Features

- **Multi-step Installation Wizard**: 8-step guided setup process
  - Choose Language: Select installation language
  - Database Setup: Configure database connection
  - Site Basics: Site name, admin account, email, site description
  - Languages: Multi-language support with content and interface translation
  - Content Types: Pre-configured types with customizable fields (FAQ, Article, etc.)
  - Modules: Core and custom module selection
  - Design & Layout: Theme settings and block configuration
  - AI Integration: OpenAI-powered content generation with automatic FAQ creation

- **Pre-configured Modules**:
  - Pathauto: Automatic URL alias generation
  - Views: Content listing and display
  - Simple Metatag: SEO metatags with path-based overrides
  - Simple Sitemap Generator: XML sitemaps (domain module optional)
  - OpenAI Provider: AI content generation
  - Content FAQ: FAQ content type with accordion block
  - Content Team: Team member content type with carousel block
  - Language Switcher: Custom language switching dropdown

- **Development-Ready**:
  - CSS/JS aggregation disabled by default
  - Twig debug mode enabled
  - All caching disabled (render, page, dynamic page)
  - Full HTML text format included

- **Custom Theme**: Modern, responsive theme built with Tailwind CSS v4
  - Dark mode support (class-based toggle)
  - Full template override for clean HTML output
  - Plyr video player integration
  - Mobile-first responsive design

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

4. Install Drupal with the Constructor profile:
   ```bash
   lando drush site:install constructor --yes --account-name=admin --account-pass=admin
   ```

5. Access your site at: https://constructor.lndo.site

### Theme Development

1. Navigate to the theme directory:
   ```bash
   cd web/themes/custom/constructor_theme
   ```

2. Install Node dependencies:
   ```bash
   npm install
   ```

3. Build CSS:
   ```bash
   npm run build
   ```

4. Watch for changes (development):
   ```bash
   npm run watch
   ```

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

## Project Structure

```
constructor/
├── .lando.yml                    # Lando configuration
├── composer.json                 # PHP dependencies
├── web/
│   ├── modules/
│   │   └── custom/
│   │       ├── content_faq/              # FAQ content type module
│   │       ├── content_team/             # Team member content type module
│   │       ├── language_switcher/        # Language switcher block
│   │       ├── simple_metatag/           # SEO module
│   │       └── simple_sitemap_generator/ # Sitemap module
│   ├── profiles/
│   │   └── custom/
│   │       └── constructor/              # Installation profile
│   │           ├── config/install/       # Default configurations
│   │           ├── themes/
│   │           │   └── constructor_install/  # Installer theme
│   │           └── src/Form/             # Wizard form classes
│   └── themes/
│       └── custom/
│           └── constructor_theme/        # Custom frontend theme
│               ├── css/                  # Compiled CSS
│               ├── js/                   # JavaScript files
│               ├── src/input.css         # Tailwind source
│               └── templates/            # Twig templates
│                   ├── block/
│                   ├── content/
│                   ├── field/
│                   ├── form/
│                   ├── layout/
│                   ├── misc/
│                   ├── navigation/
│                   ├── partials/
│                   ├── user/
│                   └── views/
└── README.md
```

## Installation Wizard Steps

### Step 1: Site Basics
- Site name and slogan
- Site email
- Administrator account (username, email, password)

### Step 2: Languages
- Default language selection
- Enable multilingual support
- Add additional languages

### Step 3: Content Types
- Select from pre-configured content types (all enabled by default):
  - Basic Page
  - Article
  - Team Member (with carousel block)
  - FAQ (with accordion block)

### Step 4: Modules
- Core modules: Contact, Search, Media, etc.
- Custom modules: Simple Metatag, Simple Sitemap Generator

### Step 5: Design & Layout
- Color scheme selection
- Dark mode toggle (enabled by default)
- Front page configuration

### Step 6: AI Integration
- OpenAI API configuration
- Model selection (GPT-4o, GPT-4, etc.)
- Default prompts per content type

## Theme Features

### Tailwind CSS v4
The theme uses Tailwind CSS v4 with the standalone CLI:
- CSS nesting support
- Class-based dark mode via `@variant dark`
- Custom component styles

### Template Structure
All Drupal templates are overridden for clean HTML output:
- Minimal wrapper elements
- Semantic HTML5 structure
- Tailwind utility classes

### Dark Mode
Toggle dark mode by adding/removing the `dark` class on the `<html>` element:
```javascript
document.documentElement.classList.toggle('dark');
```

## Post-Installation Steps

After the installation wizard completes, you may want to configure the following:

### Block Placement
Blocks are not automatically placed during installation. Visit `/admin/structure/block` to place:
- **FAQ Block**: Place in the Content region for frontpage
- **Team Block**: Place in the Content region for frontpage
- **Language Switcher**: Place in Secondary Menu region (if multilingual)
- **Main Navigation**: Place in Primary Menu region
- **Site Branding**: Place in Header region

### Content Translation (Automatic)
When you configure additional languages during installation and provide an OpenAI API key:
- The `content_translation` module is automatically installed
- FAQ and Team content types are automatically enabled for translation
- AI-generated content is automatically translated to all configured languages
- A post-installation setup runs to complete the translation process
- FAQ and Team blocks/pages automatically display content in the current language

To manually configure content translation later, visit `/admin/config/regional/content-language`

## Configuration

### OpenAI Settings
After installation, configure OpenAI at `/admin/config/services/openai`:
- API Key (required)
- Default model
- Temperature and max tokens

### Pathauto Patterns
Default patterns are configured:
- Content: `[node:content-type]/[node:title]`
- Taxonomy terms: `[term:vocabulary]/[term:name]`

## Development

### Building Theme CSS
```bash
cd web/themes/custom/constructor_theme
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
