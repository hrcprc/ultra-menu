# ultra-menu

## Mega menu behavior (Products list + right-side description)

This plugin now renders top-level items marked as **Enable Mega Menu for this item** as a full-width mega panel that:

- opens on hover/focus,
- is **500px high**,
- keeps content inside a centered container,
- shows submenu items on the **left**,
- shows the currently hovered item's description on the **right**.

### How to use

1. In **Appearance → Menus**, create a top-level item named `Products`.
2. Enable **Mega Menu** for that top-level item.
3. Add child menu items under `Products` (these become the left-side list).
4. Fill each child item's **Description** field (Screen Options → Description) for right-side content.
5. Render with:

```php
<?php if (function_exists('speexx_mega_menu')) : ?>
  <?php speexx_mega_menu([
    'theme_location' => 'primary',
    'menu_class' => 'speexx-primary-menu',
  ]); ?>
<?php endif; ?>
```

The plugin enqueues:

- `assets/speexx-mega-menu.css`
- `assets/speexx-mega-menu.js`

No extra theme CSS/JS is required for this layout.
