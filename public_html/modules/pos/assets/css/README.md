# POS Module CSS Architecture

This document describes the CSS architecture for the POS (Point of Sale) module.

## File Structure

```
/assets/css/
├── pos-main.css        # Main stylesheet that imports all others
├── pos-variables.css   # CSS custom properties (variables)
├── pos-base.css        # Base styles and global resets
├── pos-components.css  # Core UI components (cards, buttons, inputs, tables)
├── pos-search.css      # Search dropdown specific styles
├── pos-payment.css     # Payment method specific styles
├── pos-quantity.css    # Quantity control specific styles
└── pos-layout.css      # Layout and responsive styles
```

## File Descriptions

### pos-main.css
The main entry point that imports all other CSS files in the correct order. This is the only file that needs to be linked in HTML.

### pos-variables.css
Contains all CSS custom properties (variables) used throughout the POS module:
- Color scheme
- Shadow definitions
- Typography settings

### pos-base.css
Global base styles including:
- CSS reset
- Typography
- Basic animations

### pos-components.css
Core UI components:
- `.pos-card` - Card components
- `.pos-input` - Input fields
- `.pos-btn` - Button variations
- `.pos-table` - Table styling
- `.pos-badge` - Badge components
- `.stats-card` - Statistics cards

### pos-search.css
Search functionality styles:
- `.search-dropdown` - Search dropdown container
- `.search-item` - Individual search result items

### pos-payment.css
Payment method specific styles:
- `.payment-method-card` - Payment option cards
- `.payment-detail` - Payment detail panels

### pos-quantity.css
Quantity control styles:
- `.quantity-controls` - Quantity adjustment controls
- `.quantity-btn` - Quantity buttons
- `.quantity-input` - Quantity input field

### pos-layout.css
Layout and responsive styles:
- Checkout section scrolling
- Responsive breakpoints
- Container adjustments

## Usage

To use the POS styles, simply include the main CSS file:

```html
<link rel="stylesheet" href="../assets/css/pos-main.css">
```

## Benefits of This Architecture

1. **Modularity**: Each file has a specific purpose
2. **Maintainability**: Easy to find and modify specific styles
3. **Reusability**: Components can be easily reused
4. **Performance**: Can be optimized with build tools
5. **Organization**: Clear separation of concerns

## Customization

To customize the POS module:

1. Modify variables in `pos-variables.css` for global changes
2. Edit specific component files for targeted changes
3. Add new component files as needed
4. Update `pos-main.css` to include new files

## Browser Support

These styles use modern CSS features including:
- CSS Custom Properties
- CSS Grid and Flexbox
- Modern selectors

Ensure target browsers support these features or include appropriate polyfills.
