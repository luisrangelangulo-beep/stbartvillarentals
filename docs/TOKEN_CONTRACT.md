# Styling contract (`assets/brand.css`)

The core templates output **semantic class names** and expect a set of **CSS custom
properties** (design tokens). They ship with **no styling** — define everything here,
per brand, and the same markup restyles completely.

> This contract grows as templates land (Phase 2+). Treat the variable names below as
> the stable interface; templates will only ever reference these tokens + the `lvc-`
> class namespace, never hardcoded colors/fonts.

## Design tokens — define on `:root`
```css
:root {
  /* Surfaces (dark theme is the baseline for these brands) */
  --lvc-bg:            #0E1518;   /* page background */
  --lvc-bg-alt:        #0A1114;   /* alternating sections */
  --lvc-card:          #111111;   /* cards / panels */
  --lvc-border:        rgba(255,255,255,0.08);

  /* Text */
  --lvc-text:          #FBFBFB;   /* headings / primary */
  --lvc-soft:          rgba(251,251,251,0.86); /* body */
  --lvc-muted:         #9A9A9A;   /* labels / meta */

  /* Brand accent (the one value that most defines a brand) */
  --lvc-accent:        #00AA7B;
  --lvc-accent-hover:  #00C99A;
  --lvc-accent-soft:   rgba(0,170,123,0.12);

  /* Type */
  --lvc-font-display:  'Albert Sans', system-ui, sans-serif;
  --lvc-font-body:     'Lato', system-ui, sans-serif;

  /* Rhythm */
  --lvc-px:            5%;        /* horizontal page padding */
  --lvc-radius:        4px;
}
```

## Class namespace
All structural hooks use the `lvc-` prefix, grouped by component, e.g.:

| Component | Example classes |
|---|---|
| Layout | `.lvc-section`, `.lvc-section--alt`, `.lvc-container` |
| Hero | `.lvc-hero`, `.lvc-hero__title`, `.lvc-hero__sub`, `.lvc-hero__cta` |
| Buttons | `.lvc-btn`, `.lvc-btn--ghost` |
| Property card | `.lvc-card`, `.lvc-card__img`, `.lvc-card__name`, `.lvc-card__meta` |
| Grids | `.lvc-grid`, `.lvc-grid--3`, `.lvc-grid--2` |
| Filter bar | `.lvc-filter`, `.lvc-filter__select`, `.lvc-filter__chip` |
| Inquiry form | `.lvc-form`, `.lvc-form__row`, `.lvc-form__group`, `.lvc-form__submit` |
| FAQ | `.lvc-faq`, `.lvc-faq__item`, `.lvc-faq__q`, `.lvc-faq__a` |
| Section header | `.lvc-eyebrow`, `.lvc-sec-title` |
| Term archive | `.lvc-term`, `.lvc-term__head`, `.lvc-term__intro`, `.lvc-term__body`, `.lvc-term__siblings` |
| Mega menu | `.lvc-nav__mega-wrap`, `.lvc-nav__mega-toggle`, `.lvc-mega`, `.lvc-mega--drawer`, `.lvc-mega__col`, `.lvc-mega__col--compact`, `.lvc-mega__label`, `.lvc-mega__list`, `.lvc-mega__item`, `.lvc-mega__chip`, `.lvc-mega__name`, `.lvc-mega__count`, `.lvc-mega__all` |

### Mega menu notes
The panel is hidden by default and shown when `.lvc-nav__mega-wrap` gains `.is-open`
(added by `assets/theme.js`, not by CSS `:hover` — it must stay keyboard- and
touch-reachable). Brands style it however they like, but two things are load-bearing:

- **Anchor it to the header, not to the nav item.** A nav item that is
  `position: relative` will trap a `position: absolute` panel at its own width.
  Either set the panel `position: fixed` under the header height, or make the
  nav item `position: static`. This is the single most common way the panel
  ends up 200px wide.
- **`.lvc-mega__col--compact`** holds numeric chips (`3 BR`, `4 BR`, …). Lay it
  out as a wrapping row; as stacked rows it is mostly whitespace.

`.lvc-mega__count` carries a bare integer, printed live from the term. Add any
label (“villas”) with CSS `::after` — never bake it into content.

## Rules
- Brands **only** edit `theme-config.php` + this stylesheet. No template edits.
- Keep it dark-theme-first (per the Luxury UX baseline) unless a brand explicitly differs.
- Don't reintroduce inline `<style>` in templates — that's exactly what this core removes.
