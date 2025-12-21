# Constructor - Drupal 11 Installation Profile

## Project Overview

A custom Drupal 11 installation profile with multi-step setup wizard, custom theme with Tailwind CSS, and AI integration via OpenAI.

**Stack:**
- Drupal 11
- Lando for local development
- Tailwind CSS v4 with custom breakpoints (3xl: 1920px, 4xl: 2560px, 5xl: 3200px)
- Swiper.js v11, Plyr.js v3.7.8, PhotoSwipe v5.4.4 for frontend components

**10-Step Installation Wizard:**
| Step | Name | Description |
|------|------|-------------|
| 1 | Choose Language | Drupal core main language selection |
| 2 | Verify Requirements | System requirements check |
| 3 | Database | Database configuration |
| 4 | Install | Install Drupal core + profile modules |
| 5 | Languages | Additional languages + multilingual settings |
| 6 | Site Basics | Site name, admin account, email |
| 7 | Content Types | Select/create content types |
| 8 | Modules | Enable optional modules |
| 9 | Design & Layout | Colors, dark mode, block regions |
| 10 | AI Integration | OpenAI API configuration |

---

## Project Structure

```
Constructor/
├── web/
│   ├── profiles/custom/constructor/           # Installation profile
│   │   ├── constructor.info.yml               # Profile definition
│   │   ├── constructor.profile                # Wizard logic + example page route
│   │   ├── constructor.install                # Installation hooks
│   │   ├── constructor.routing.yml            # Routes (/example)
│   │   ├── src/Form/                          # Wizard form classes
│   │   ├── src/Controller/                    # Controllers (ExampleController)
│   │   ├── config/install/                    # Default configs
│   │   └── themes/constructor_install/        # Installer theme
│   │
│   ├── themes/custom/constructor_theme/       # Main frontend theme
│   │   ├── constructor_theme.info.yml
│   │   ├── constructor_theme.libraries.yml
│   │   ├── package.json                       # Tailwind v4
│   │   ├── tailwind.config.js
│   │   ├── src/input.css
│   │   ├── templates/pages/                   # Page templates including example
│   │   └── example.html                       # HTML prototype copy
│   │
│   └── modules/custom/
│       ├── openai_provider/                   # OpenAI API integration
│       ├── simple_metatag/                    # SEO metatags
│       └── simple_sitemap_generator/          # XML sitemaps
│
├── index.html                                 # Master HTML prototype
└── TODO.md                                    # This file
```

---

## Development Commands

```bash
# Lando
lando start / lando stop / lando rebuild
lando drush <command>
lando composer <command>
lando mysql

# Install Site
lando drush site:install constructor \
  --account-name=admin \
  --account-pass=admin \
  --site-name="Constructor Site" -y

# Clear Cache
lando drush cr

# Theme Build
cd web/themes/custom/constructor_theme
npm install
npm run build   # Production
npm run watch   # Development

# Example Page
# Visit /example after installation (route defined in profile)
```

---

## Phase 1: Installation Profile

### Profile Core Files

| File | Status | Notes |
|------|--------|-------|
| `constructor.info.yml` | ✅ Done | Drupal 11, pathauto, token, views |
| `constructor.profile` | ✅ Done | 6-step wizard, CLI support, theme hook |
| `constructor.install` | ✅ Done | Theme, pathauto patterns, vocabularies |
| `constructor.routing.yml` | ✅ Done | /example route |
| `constructor.libraries.yml` | ✅ Done | Installer CSS/JS |
| `src/Controller/ExampleController.php` | ✅ Done | Example page controller |

### Wizard Forms

| Form | Step | Status |
|------|------|--------|
| `MinimalConfigureForm.php` | - | ✅ Done - Auto-submits core configure |
| `LanguagesForm.php` | 1 | ✅ Done - Additional languages + multilingual |
| `SiteBasicsForm.php` | 2 | ✅ Done - Site name, admin account |
| `ContentTypesForm.php` | 3 | ✅ Done - 8 content types + custom |
| `ModulesForm.php` | 4 | ✅ Done - Core modules, SEO settings |
| `DesignLayoutForm.php` | 5 | ✅ Done - Colors, dark mode, regions |
| `AIIntegrationForm.php` | 6 | ✅ Done - OpenAI API configuration |
| `InstallerFormBase.php` | - | ✅ Done - Base class |

**Note:** Main language is selected in Drupal core Step 1. Additional languages are configured in our custom Step 5 (LanguagesForm).

### Profile TODO

- [ ] Test CLI installation with default values
- [ ] Add translations directory with .po files
- [ ] Create default content during installation
- [ ] Add profile logo and screenshot
- [ ] Export additional config (image styles, text formats)

---

## Phase 2: Custom Modules

### 2.1 OpenAI Provider

**Location:** `web/modules/custom/openai_provider/`
**Status:** Info file exists, needs implementation

| Task | Status |
|------|--------|
| `openai_provider.routing.yml` | ⬜ Pending |
| `openai_provider.services.yml` | ⬜ Pending |
| `src/Service/OpenAIClient.php` | ⬜ Pending |
| `src/Form/SettingsForm.php` | ⬜ Pending |
| `config/schema/openai_provider.schema.yml` | ⬜ Pending |
| Text generation API | ⬜ Pending |
| Image generation API (DALL-E) | ⬜ Pending |
| Content form integration | ⬜ Pending |

### 2.2 Simple Metatag

**Location:** `web/modules/custom/simple_metatag/`
**Status:** Info file exists, needs implementation

| Task | Status |
|------|--------|
| Hook into node/term view | ⬜ Pending |
| Settings form for patterns | ⬜ Pending |
| Path-based overrides | ⬜ Pending |
| Token integration | ⬜ Pending |

### 2.3 Simple Sitemap Generator

**Location:** `web/modules/custom/simple_sitemap_generator/`
**Dependency:** Requires `domain` module

| Task | Status |
|------|--------|
| XML sitemap generation | ⬜ Pending |
| Multi-domain support | ⬜ Pending |
| Custom URLs | ⬜ Pending |
| Cron regeneration | ⬜ Pending |

---

## Phase 3: Constructor Theme

### 3.1 Theme Setup

| File | Status |
|------|--------|
| `constructor_theme.info.yml` | ✅ Done - 18 regions |
| `constructor_theme.libraries.yml` | ✅ Done - Swiper, Plyr, PhotoSwipe |
| `package.json` | ✅ Done - Tailwind v4 |
| `tailwind.config.js` | ✅ Done - Custom breakpoints |
| `src/input.css` | ✅ Done - Tailwind source |
| `example.html` | ✅ Done - Prototype copy |

### 3.2 Tailwind CSS Configuration

- [ ] Run `npm install` in theme directory
- [ ] Run `npm run build` to compile CSS
- [ ] Set up watch mode for development
- [ ] Configure PurgeCSS for production

### 3.3 Templates

| Template | Status |
|----------|--------|
| `page.html.twig` | ✅ Done - Basic structure |
| `constructor-example-page.html.twig` | ✅ Done - Full prototype |
| `node.html.twig` | ⬜ Needs Tailwind |
| `block.html.twig` | ⬜ Needs Tailwind |

### 3.4 Global Components

#### Header
- [ ] Create header region template
- [ ] Logo placement
- [ ] Main navigation menu
- [ ] Language switcher (modal on desktop, drawer on mobile)
- [ ] Theme toggle (dark/light mode)
- [ ] Book a Demo CTA button
- [ ] Mobile hamburger menu
- [ ] Sticky header behavior

#### Footer
- [ ] Create footer region template
- [ ] Logo and tagline
- [ ] Footer navigation columns
- [ ] Social media links
- [ ] Copyright text

#### Modals & Drawers
- [ ] Booking modal (desktop)
- [ ] Booking drawer (mobile)
- [ ] Language selector modal (desktop)
- [ ] Language selector drawer (mobile)
- [ ] Mobile menu drawer

#### Dark Mode
- [ ] Implement `darkMode: 'class'` strategy
- [ ] Theme toggle button
- [ ] Persist preference in localStorage
- [ ] System preference detection

### 3.5 Block Templates

| Block Type | Template | Required Module | Status |
|------------|----------|-----------------|--------|
| `hero_primary` | `block--hero-primary.html.twig` | Core theme | ⬜ |
| `hero_secondary` | `block--hero-secondary.html.twig` | Core theme | ⬜ |
| `hero_product_sale` | `block--hero-product-sale.html.twig` | Core theme | ⬜ |
| `product_carousel` | `block--product-carousel.html.twig` | `constructor_products` | ⬜ |
| `products_list` | `block--products-list.html.twig` | `constructor_products` | ⬜ |
| `single_product` | `block--single-product.html.twig` | `constructor_products` | ⬜ |
| `team_carousel` | `block--team-carousel.html.twig` | `constructor_team` | ⬜ |
| `services` | `block--services.html.twig` | Core theme | ⬜ |
| `methods` | `block--methods.html.twig` | Core theme | ⬜ |
| `testimonials` | `block--testimonials.html.twig` | Core theme | ⬜ |
| `video_banner` | `block--video-banner.html.twig` | Core theme | ⬜ |
| `pricing` | `block--pricing.html.twig` | Core theme | ⬜ |
| `car_hero` | `block--car-hero.html.twig` | `constructor_vehicles` | ⬜ |
| `use_cases` | `block--use-cases.html.twig` | Core theme | ⬜ |
| `faq` | `block--faq.html.twig` | `constructor_faq` | ⬜ |
| `contact` | `block--contact.html.twig` | Core theme | ⬜ |
| `gallery` | `block--gallery.html.twig` | Core theme | ⬜ |

### 3.6 Block Type Fields (for each block)

#### Hero Primary
- Title (formatted text)
- Subtitle (formatted text)
- CTA Button text/link
- Stats (paragraph reference - repeatable)
- Background image (media reference)

#### Hero Secondary
- Label badge text
- Title (formatted text)
- Description (formatted text)
- Primary/Secondary CTA buttons
- Partner logos (media reference - multiple)

#### Services Block
- Section title/description
- Service items (paragraph - repeatable): Icon, Title, Description

#### Testimonials Block
- Section title/description
- Items (paragraph): Author name/title/image, Quote, Rating

#### Video Banner Block
- Section title/description
- Video URL or Media reference
- Poster image

#### Pricing Block
- Section title/description
- Monthly/annual toggle
- Plans (paragraph): Name, Description, Prices, Features, CTA, Recommended badge

#### FAQ Block
- Section title/subtitle
- Contact link
- FAQ items: Question, Answer

#### Contact Block
- Section title/description
- Contact info items (paragraph): Icon, Label, Value
- Enable contact form (boolean)

#### Gallery Block
- Section label/title/description
- CTA button
- Gallery images (media - multiple)

---

## Phase 4: Future Modules

### 4.1 Products Module (`constructor_products`)

- [ ] Product content type (Title, SKU, Price, Sale price, Images, Category, Variants)
- [ ] Product Category/Tags taxonomies
- [ ] Views for listing, carousel, grid
- [ ] Product detail page template
- [ ] Add to cart / Cart / Checkout

### 4.2 Team Module (`constructor_team`)

- [ ] Team Member content type (Name, Title, Photo, Bio, Social links)
- [ ] Department taxonomy
- [ ] Views for team listing
- [ ] Team carousel block

### 4.3 FAQ Module (`constructor_faq`)

- [ ] FAQ content type (Question, Answer, Category, Weight)
- [ ] FAQ Category taxonomy
- [ ] Views for FAQ listing
- [ ] Accordion block

### 4.4 Vehicles Module (`constructor_vehicles`)

- [ ] Vehicle content type (Title, Make, Year, Images, Price, Specs)
- [ ] Vehicle Make taxonomy
- [ ] Views for vehicle listing
- [ ] Vehicle comparison feature

### 4.5 Booking Module (`constructor_booking`)

- [ ] Booking request content type or Webform
- [ ] Booking modal/drawer component
- [ ] Email notifications
- [ ] Admin booking management

---

## Phase 5: Installer Theme

**Location:** `web/profiles/custom/constructor/themes/constructor_install/`

| File | Status |
|------|--------|
| `constructor_install.info.yml` | ✅ Done |
| `constructor_install.libraries.yml` | ✅ Done |
| `css/install.css` | ✅ Done |
| `package.json` | ✅ Done (Tailwind v4) |
| Form templates | ✅ Done |

### Installer TODO

- [ ] Compile Tailwind CSS
- [ ] Test all 6 wizard steps styling
- [ ] Add step indicator component
- [ ] Responsive mobile layout

---

## Phase 6: Content Types

### Default Content Types (via wizard)

| Type | Extra Fields |
|------|--------------|
| `page` | body, image, tags |
| `article` | body, image, tags |
| `landing_page` | body, image, tags |
| `event` | + event_date, location |
| `service` | body, image, tags |
| `team_member` | + position, email |
| `faq` | body only |
| `testimonial` | + author_name, company |

---

## Phase 7: Configuration

### Profile Config Files

| Config | Status |
|--------|--------|
| `system.site.yml` | ✅ Done |
| `system.theme.yml` | ✅ Done |
| `node.settings.yml` | ✅ Done |
| `pathauto.pattern.content.yml` | ✅ Done |
| `pathauto.pattern.taxonomy_term.yml` | ✅ Done |
| `pathauto.settings.yml` | ✅ Done |
| `taxonomy.vocabulary.tags.yml` | ✅ Done |
| `taxonomy.vocabulary.categories.yml` | ✅ Done |
| `user.role.site_administrator.yml` | ✅ Done |
| `user.role.content_editor.yml` | ✅ Done |

### Additional Config Needed

- [ ] Block placement configs
- [ ] View configs
- [ ] Image styles
- [ ] Text formats

---

## Phase 8: SEO & Performance

### SEO
- [ ] Pathauto patterns configured
- [ ] Metatag configuration
- [ ] Schema.org structured data
- [ ] XML sitemap

### Performance
- [ ] CSS/JS aggregation
- [ ] Tailwind PurgeCSS
- [ ] Responsive images, lazy loading
- [ ] Caching strategy

---

## Phase 9: Testing

### Installation Tests
- [ ] Fresh install via browser (6 steps)
- [ ] Fresh install via CLI
- [ ] Test with different languages
- [ ] Test with different content types
- [ ] Test AI form with valid/invalid key

### Theme Tests
- [ ] All blocks render correctly
- [ ] Dark mode toggle
- [ ] Mobile responsive
- [ ] Language switcher modal/drawer
- [ ] Booking modal/drawer
- [ ] Big screens (3xl/4xl/5xl)
- [ ] Cross-browser testing
- [ ] Accessibility audit (WCAG 2.1)

---

## Priority Order

### Immediate (Required for Installation)

1. ✅ Profile wizard forms
2. ✅ Profile batch processing
3. ✅ Example page route
4. ⬜ **OpenAI Provider service class**
5. ⬜ **Theme Tailwind compilation**

### High Priority

6. ⬜ Block type templates
7. ⬜ Header/footer templates
8. ⬜ Dark mode functionality
9. ⬜ Mobile menu/modals

### Medium Priority

10. ⬜ Products module
11. ⬜ Team module
12. ⬜ FAQ module
13. ⬜ Vehicles module

### Lower Priority

14. ⬜ Booking module
15. ⬜ Multilingual content
16. ⬜ SEO optimization
17. ⬜ Performance tuning

---

## Notes

1. **HTML Prototype:** `index.html` is the source of truth for all frontend blocks

2. **Example Page:** Route `/example` defined in profile, template in theme

3. **Tailwind Config:** JIT compilation with custom breakpoints:
   - `3xl: 1920px`
   - `4xl: 2560px`
   - `5xl: 3200px`

4. **PhotoSwipe:** Uses ES modules - requires `type="module"` on script tags

5. **Dark Mode:** Uses `darkMode: 'class'` strategy in Tailwind

6. **Fonts:** Inter with Cyrillic subset support

7. **Libraries:** Swiper v11, Plyr v3.7.8, PhotoSwipe v5.4.4
