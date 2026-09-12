# Task 6C — final mobile polish

Presentation-only changes in `atlas/src/mobile.js` and `mobile.css`; rebuilt view.js, view.css and manifest. No workflow, map, matching, REST, enquiry, PHP product code or desktop style changes.

- Removed visible internal sheet labels and replaced screen-reader state wording with customer-facing descriptions.
- Chevron buttons retain native keyboard interaction, 44px targets and descriptive accessible names: Show more map / Show more content.
- Higher-contrast grip; reduced handle height; refined header insets and full-width menu rows.
- Property Focus actions use a bounded two-column grid and stack at 340px or narrower. Both actions measured 44px high, inside the viewport, without horizontal clipping at 320/375/390px.
- Tighter Brief intro, choices, review, contact and success spacing; unchanged approved copy. Existing safe-area bottom reservation remains; lateral safe-area padding added to header/footer.
- Corrected inherited filter grid placement, consistent labels/Reset spacing and full-width Brief entry.

Browser checks: mobile widths 320/375/390; desktop screenshots at 1024/1280/1440 (mobile class absent, original layout retained). Verified drag, keyboard controls, menu open/close/Escape, absence of visible internal terminology, property actions, Brief questions/review/contact/success, owner footer, management final-choice clearance and empty relevant console error/warning log. Review's final action and management's final choice scroll above the sticky footer. Temporary successful-submission enquiry removed.

Build passed; JS 15 tests, Atlas PHP 61 checks, Core 84 checks, enquiries 50 checks. PHP template lint and git diff whitespace check passed. Existing duplicate WP_DEBUG bootstrap warning remains. Physical-device safe-area/browser-chrome behavior was not hardware tested; safe-area and dynamic viewport rules are retained.
