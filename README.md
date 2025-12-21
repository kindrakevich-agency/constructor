# Constructor - Drupal 11 Installation Profile

A customizable Drupal 11 installation profile with a multi-step setup wizard for configuring languages, content types, modules, layout, and AI content integration.

## Features

- **Multi-step Installation Wizard**: 6-step guided setup process
  - Site Basics: Site name, admin account, email
  - Languages: Multi-language support with content and interface translation
  - Content Types: Pre-configured types with customizable fields
  - Modules: Core and custom module selection
  - Design & Layout: Theme settings and block configuration
  - AI Integration: OpenAI-powered content generation

- **Pre-configured Modules**:
  - Pathauto: Automatic URL alias generation
  - Views: Content listing and display
  - Simple Metatag: SEO metatags with path-based overrides
  - Simple Sitemap Generator: XML sitemaps with domain support
  - OpenAI Provider: AI content generation

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

## Project Structure

```
constructor/
├── .lando.yml                    # Lando configuration
├── composer.json                 # PHP dependencies
├── web/
│   ├── modules/
│   │   └── custom/
│   │       ├── simple_metatag/           # SEO module
│   │       └── simple_sitemap_generator/  # Sitemap module
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
- Select from pre-configured content types:
  - Basic Page
  - Article
  - Landing Page
  - Event
  - Service
  - Team Member
  - FAQ
  - Testimonial

### Step 4: Modules
- Core modules: Contact, Search, Media, etc.
- Custom modules: Simple Metatag, Simple Sitemap Generator

### Step 5: Design & Layout
- Color scheme selection
- Dark mode toggle
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
