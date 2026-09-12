# Properties Task 2 — Mobile directory

Implemented a directory-only mobile experience below 768px. Desktop CSS rules and Gutenberg page content remain intact; Atlas source/build, property metadata/taxonomies, Docker and individual property templates are unchanged.

- List is the initial mobile view regardless of the block's desktop default. Desktop view choice is retained across breakpoint changes.
- Compact native cards use existing safe formatters for at most two type-aware metrics. Land uses acreage and suppressed pricing stays suppressed.
- Native dialog contains draft type/transaction/size/area filters, a live matching count, Clear and explicit Apply. Escape/focus restoration use native dialog behavior. Applying, chips and reset share the existing URL/filter engine.
- Map JavaScript and CSS load only when a map view is requested. Existing MapLibre/PMTiles/worker/style are reused. Mobile marker selections show a local thumbnail preview without scrolling away to the hidden list.
- Mobile CSS contains safe-area padding, dynamic viewport sizing, a scrollable filter body with reachable actions, and 44px controls. Map height accounts for the mobile controls and active chips.

Validation: 320/375/390 portrait, 667×375 filter dialog, 768 boundary, and 1024/1280/1440 desktop checks. Checked draft/apply, all four filters, Land acreage, chips, zero results/reset, Largest sort, shared URL reload/Back/Forward, lazy map loading, map selection and preview, keyboard Escape/focus restoration. No horizontal page overflow observed. Browser console checked for relevant errors.

Automated checks: build; 8 directory JS tests; 15 Atlas JS tests; 36 directory PHP checks; 84 Core assertions; 61 Atlas PHP checks; 50 enquiry assertions; PHP lint and git diff whitespace validation. Existing duplicate WP_DEBUG configuration warning remains outside this task's scope.

No new property/team records or enquiry submissions. No commits created.
