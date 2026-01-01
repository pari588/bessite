# Pump Page Popup Redesign - December 2025

## Overview
Redesigned the Customer Care popup on the pumps page (`/xsite/mod/pumps/x-pumps.php`) with a premium industrial aesthetic matching the Bombay Engineering brand.

## Files Modified
- `/home/bombayengg/public_html/xsite/mod/pumps/x-pumps.php`

## Backup Created
- `/home/bombayengg/public_html/xsite/mod/pumps/x-pumps.php.backup_[timestamp]`

## Design Features

### Visual Design
1. **Premium Header**
   - Deep blue gradient: `#0a1f3d` → `#157bba` → `#1e90d0`
   - Subtle cross-pattern texture overlay (SVG data URI)
   - Frosted glass backdrop blur effect

2. **Color Palette**
   - Primary: `#157bba` (matches `--mellis-base`)
   - Dark navy: `#0a1f3d`
   - WhatsApp green: `#25D366` → `#128C7E`
   - Neutral grays for address section

3. **Typography**
   - Headers: Libre Baskerville (serif) - matches site design system
   - Body: Manrope (sans-serif) - matches site design system
   - Clear hierarchy with uppercase labels and bold values

### Animations
```css
@keyframes popupSlideIn - Smooth entrance with bounce
@keyframes pulseGlow - Pulsing glow on phone icon
@keyframes shimmer - Subtle shimmer on CTA button
```

### Interactive Elements
1. **Phone CTA Card**
   - Gradient background with shimmer effect
   - Hover: slides right + adds shadow
   - Arrow indicator changes color on hover

2. **WhatsApp Button**
   - Green gradient with glow shadow
   - Hover: lifts up with enhanced shadow

3. **Close Button**
   - Frosted glass circle
   - Rotates 90° on hover

### UX Features
- Click outside overlay to close
- ESC key to close
- Smooth fade transitions (300-400ms)
- Shows only once per session (sessionStorage)
- Mobile responsive (480px breakpoint)

## Code Structure

### CSS Classes (all prefixed with `pump-popup-`)
```
.pump-popup-overlay      - Full screen overlay with blur
.pump-popup-card         - Main white card container
.pump-popup-header       - Blue gradient header section
.pump-popup-close        - Close button (X)
.pump-popup-icon         - Phone icon with pulse animation
.pump-popup-body         - Content area
.pump-popup-cta          - Phone number card
.pump-popup-cta-icon     - Icon container in CTA
.pump-popup-cta-content  - Text content in CTA
.pump-popup-cta-label    - Small uppercase label
.pump-popup-cta-value    - Large phone number
.pump-popup-cta-arrow    - Arrow indicator
.pump-popup-whatsapp     - WhatsApp button
.pump-popup-address      - Address section
.pump-popup-address-icon - Location icon
.pump-popup-address-content - Address text
```

### JavaScript Events
```javascript
// Auto-show on page load (once per session)
setTimeout → fadeIn after 800ms

// Close triggers:
- Click on .pump-popup-overlay (outside card)
- Click on .pump-popup-close button
- Press ESC key
```

## Contact Information Displayed
- **Helpline:** 9228880505 (tel: link)
- **WhatsApp:** +91 7428713838
- **Address:** Crompton Greaves Consumer Electricals Ltd., 05GBD, Godrej Business District, Pirojshanagar, Vikhroli (West), Mumbai - 400079

## Testing
1. Visit any pump page: `https://www.bombayengg.net/pump/`
2. Popup appears after 800ms delay
3. To test again: Clear sessionStorage or use incognito window

## Rollback
To restore the original popup:
```bash
cp /home/bombayengg/public_html/xsite/mod/pumps/x-pumps.php.backup_[timestamp] /home/bombayengg/public_html/xsite/mod/pumps/x-pumps.php
```

## Future Improvements (Optional)
- Add Crompton logo to header
- Add operating hours indicator
- Add language toggle for regional support
- Track popup interactions with analytics
