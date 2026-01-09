# Member4VM Theme

Modern, professional Laravel theme with glassmorphism effects and comprehensive dark mode support.

## Features

- **Glassmorphism Design** - Modern glass effects with backdrop blur
- **Dark Mode** - Full dark mode support with smooth transitions
- **Responsive** - Mobile-first design, works on all devices
- **Accessible** - WCAG 2.1 AA compliant
- **Performance** - Optimized animations and transitions
- **OKLCH Colors** - Modern color science for consistent appearance

## Layouts

### Guest Layout
- Split-screen design (desktop)
- Left: Branded banner with logo and features
- Right: Authentication forms with glass card effect
- Stacks vertically on mobile

### App Layout
- Fixed darker sidebar (independently scrollable)
- Collapsible sidebar
- Header with search, notifications, dark mode toggle
- Responsive with mobile overlay

## Color Palette

**Primary (Professional Blue):**
- 500: `oklch(0.55 0.20 240)` - Main brand color

**Status Colors:**
- Success: `oklch(0.65 0.18 145)` - Green
- Warning: `oklch(0.75 0.15 85)` - Amber
- Danger: `oklch(0.60 0.22 25)` - Red

## Typography

**Modern Professional Pairing** (UI/UX Pro Max Recommendation)

- **Headings:** Poppins (weights: 400, 500, 600, 700)
- **Body:** Open Sans (weights: 300, 400, 500, 600, 700)
- **Code:** JetBrains Mono

This pairing creates strong visual hierarchy while maintaining excellent readability.

## Components

Reusable Blade components:
- `card` - Glass cards with variants
- `button` - Button with variants (primary, secondary, danger, ghost)
- `form-input` - Form input with label, hint, error
- `dark-mode-toggle` - Theme switcher
- `sidebar-menu` - Sidebar navigation
- `header-nav` - Header navigation

## Keyboard Shortcuts

- `Cmd/Ctrl + K` - Focus search
- `Cmd/Ctrl + B` - Toggle sidebar
- `Cmd/Ctrl + D` - Toggle dark mode

## Customization

Edit `css/theme.css` to customize:
- Colors (OKLCH values)
- Spacing
- Border radius
- Animations

## Browser Support

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+

## Accessibility

- Keyboard navigation
- Screen reader friendly
- Focus indicators
- ARIA labels
- Reduced motion support
