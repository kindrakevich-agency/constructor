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

- **Custom Theme**: Modern, responsive theme with CSS variables

## Requirements

- PHP 8.3 or higher
- MySQL 8.0 or MariaDB 10.6+
- Composer 2.x
- Lando (for local development)

## Installation

### Using Lando (Recommended)

1. Clone the repository:
   ```bash
   git clone <repository-url> constructor
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
   lando install
   ```
   Or manually:
   ```bash
   lando drush site:install constructor --yes --account-name=admin --account-pass=admin
   ```

5. Access your site at: https://constructor.lndo.site

### Manual Installation

1. Install Composer dependencies:
   ```bash
   composer install
   ```

2. Create `web/sites/default/settings.php` from default template

3. Install via Drush:
   ```bash
   ./vendor/bin/drush site:install constructor --yes
   ```

## Lando Commands

| Command | Description |
|---------|-------------|
| `lando start` | Start the Lando environment |
| `lando stop` | Stop the Lando environment |
| `lando rebuild` | Rebuild the Lando environment |
| `lando install` | Install Drupal with Constructor profile |
| `lando drush <cmd>` | Run Drush commands |
| `lando composer <cmd>` | Run Composer commands |
| `lando cr` | Clear Drupal caches |
| `lando uli` | Generate one-time login link |
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
│   │           ├── config/
│   │           │   └── install/          # Default configurations
│   │           ├── css/                  # Installer styles
│   │           ├── js/                   # Installer scripts
│   │           └── src/
│   │               └── Form/             # Wizard form classes
│   └── themes/
│       └── custom/
│           └── constructor_theme/        # Custom theme
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
- Content and interface translation options

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
- Add custom content types

### Step 4: Modules
- Core modules: Contact, Search, Media, etc.
- Custom modules: Simple Metatag, Simple Sitemap Generator
- SEO configuration options

### Step 5: Design & Layout
- Color scheme selection
- Dark mode toggle
- Front page configuration
- Block region setup (Header, Sidebar, Footer)

### Step 6: AI Integration
- OpenAI API configuration
- Model selection (GPT-4o, GPT-4, etc.)
- Temperature and token settings
- Default prompts per content type
- AI content suggestions toggle

## Configuration

### OpenAI Settings

After installation, configure OpenAI at `/admin/config/services/openai`:
- API Key (required)
- Organization ID (optional)
- Default model
- Temperature and max tokens

### Pathauto Patterns

Default patterns are configured:
- Content: `[node:content-type]/[node:title]`
- Taxonomy terms: `[term:vocabulary]/[term:name]`

Customize at `/admin/config/search/path/patterns`.

## Custom Modules

### Simple Metatag
Provides SEO metatags with path-based overrides.
- Configure at `/admin/config/search/simple-metatag`

### Simple Sitemap Generator
Generates XML sitemaps with domain support.
- Configure at `/admin/config/search/simple-sitemap-generator`

## Development

### Adding New Content Types

1. Add to `ContentTypesForm.php` in the `$default_types` array
2. Configure default fields and extra fields
3. Test the installation wizard

### Modifying the Theme

The theme uses CSS variables for easy customization:
```css
:root {
  --color-primary: #2563eb;
  --color-text: #1e293b;
  /* ... */
}
```

### Running Tests

```bash
lando drush pm:list --status=enabled  # Verify modules
lando drush status                     # Check site status
lando drush cr                         # Clear caches
```

## Troubleshooting

### Common Issues

1. **Composer memory limit**: Increase PHP memory limit or use `COMPOSER_MEMORY_LIMIT=-1 composer install`

2. **Permission issues**: Ensure `web/sites/default/files` is writable

3. **Database connection**: Verify `.lando.yml` database credentials match `settings.php`

### Logs

View Drupal logs:
```bash
lando drush watchdog:show
```

## License

GPL-2.0-or-later

## Credits

Built with Drupal 11 and Lando.
