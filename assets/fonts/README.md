# Path Pilot Icon Font

This directory contains the custom icon font for the Path Pilot plugin.

## Generating the Font

To generate or update the icon font:

1. Collect all SVG icons in the `src/assets/images/icons/` directory
2. Go to [IcoMoon App](https://icomoon.io/app/)
3. Click "Import Icons" and select all SVGs from the icons directory
4. Select all imported icons
5. Click "Generate Font" at the bottom
6. Click the "Download" button
7. Extract the downloaded zip file
8. Copy the font files (`*.eot`, `*.svg`, `*.ttf`, `*.woff`, `*.woff2`) to this directory

## Icon Codes

When generating the font, IcoMoon will provide you with CSS and HTML code snippets. 
Make sure to update the codes in the `path-pilot-icons.css` file with the correct unicode values.

Example:
```css
.pp-icon-compass:before {
  content: "\e900";
}
```

## Using Icons in the Plugin

To use the icons in the plugin:

1. Make sure the `path-pilot-icons.css` file is properly enqueued
2. Use the icons in HTML with the appropriate class:

```html
<i class="pp-icon-compass"></i>
```

3. For admin menu icons, use the CSS selectors like:

```css
#adminmenu #toplevel_page_path-pilot-home .wp-menu-image:before {
    font-family: "path-pilot-icons" !important;
    content: "\e900";
}
```

## Adding New Icons

1. Create new SVG icons and place them in the `src/assets/images/icons/` directory
2. Regenerate the font using the steps above
3. Update the CSS code in `path-pilot-icons.css` with the new icon codes 