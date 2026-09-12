# Homepage choreography pass

This is a presentation-only successor to [the homepage presentation redesign](homepage-presentation.md). Home remains published Page 74, the static front page, rendered through `the_content()`. No journey strategy, Property/Team records, Atlas workflow, mapping provider, enquiry system or Instagram connection architecture was replaced.

## What changed

The redundant “Commercial Real Estate Services Built Around Your Objective” introduction is removed. A compact objective selector leads directly into the journey. An Atlas intent carries through automatically; “Change objective” remains available. The human/technology statement now belongs to the Team introduction rather than a separate composition. The sequence remains Atlas → Journey → Opportunities → Property Types → Team → In the Field → Human conversion.

The desktop journey uses a full-width photographic canvas with a restrained numbered rail and supporting copy below. The same document and three real property elements persist across stages. On each stage change, a single measurement pass records their previous/final rectangles; Web Animations animates only their transforms. CSS moves the document and reveals the relevant annotations. There is no scroll hijacking, timer-based advance, extra map instance or motion dependency.

- Define: an oversized, readable native Brief document, with prompts rather than invented client answers.
- Explore: the document contracts onto the existing Atlas reference image. Actual projected map points connect to property photographs where they are in view. Off-frame locations are labeled honestly. Mobile shows one property annotation to preserve geographic space.
- Compare: three substantial photographic territories on desktop, using real published names, locality, transaction and an existing size/site metric. Mobile presents one property at a time with swipe and explicit controls. Fit remains qualified; no suitability or availability is invented.
- Validate: “Look closer” carries the selected comparison image into the full photographic scene. Four numbered questions expose the existing approved explanations; mobile reveals one restrained explanation at a time.
- Negotiate: one large selected term, a quiet term rail, supporting explanation and abstract document rules. There are no fabricated confidential values or simulated legal documents.
- Execute: the composition clears into the existing milestone progression and advisor conclusion.

Owner, investor and management journeys reuse the same canvas, rail, choreography and native artifact sources. All 24 native stage blocks are byte-for-byte identical to the pre-pass Home content. The one-time `bin/setup-home-choreography.php` script backs up Home, saves a revision, changes only the two editorial wrappers and skips an already-updated page.

Opportunities retain their established portfolio architecture with a larger frame, next-property edge and coordinated copy emphasis. Property-type territories expand on pointer, focus or explicit selection and expose a real count, Directory link and short description. Descriptions are editable in the native block inspector and render safely on the server. Team uses a larger portrait, oversized overlapping name and next-person edge, keeping the verified roster in its normal order. In the Field is a staggered contact sheet with mixed photographic proportions, numbering, native horizontal scrolling and accessible rail controls. It continues to identify the unconnected Instagram fallback as genuine property photography.

The light Atlas map receives only contrast refinements: slightly stronger property markers, locality labels, arterial casings and left-side copy/map blending. Its business logic and mobile sheet system are unchanged.

## Semantics, accessibility and performance

The exact single H1 remains “Houston Commercial Real Estate, Made Clear.” A functional journey H2 replaces the removed marketing headline; five other principal H2s and all six service entities remain in server-rendered HTML. Without enhancement the canvas is removed, leaving the native journeys, artifact prompts, properties, people, types and conversion content readable. The editor retains native copy and static dynamic-block previews.

Stage links support arrow keys, Home and End. Current-stage state, explicit previous/next buttons, live counts, focus outlines and non-drag rail controls remain. Inactive scene controls are inert. The scene uses `overflow: clip` to avoid invisible scrolling when a child receives keyboard focus. Reduced-motion handling skips FLIP animation, disables CSS transitions/smooth scrolling and cancels an in-flight property animation if the preference changes.

Images retain WordPress responsive sources, lazy loading and reserved photographic areas. Shared images reuse the existing property sources; the browser can reuse cached files. The scene creates no second MapLibre instance. No performance score is claimed.

## Verification checkpoint

- All 13 existing PHP suites passed. The homepage suite now passes 341 checks, including description escaping, removal of redundant wrappers and exact preservation of all 24 native stages.
- All 37 JavaScript tests passed. Blocks and Atlas build successfully; seven changed/new PHP files lint; presentation JavaScript syntax and `git diff --check` pass.
- Browser layout checks passed at 320, 375, 390, 430, 768, 1024, 1280 and 1440 pixels: no document overflow and no clipped Define, Compare or long Negotiate content.
- Final visual review corrected portrait overlap so only the oversized Team name overlaps the photograph. Every profile's name fits and its role, biography and link clear the photograph at all eight widths. Mobile property-type descriptions and 44px Directory links remain visible; Explore Office opens the correct filtered Directory.
- All four Atlas intent handoffs retain all four published opportunities and avoid asking for the objective again. Keyboard stage navigation, mobile comparison next/previous, preferred-property continuity, diligence reveals, topic selection, property-type expansion, mobile menu and accessible Atlas sheet expansion were exercised.
- No relevant frontend console errors were observed. The pre-existing duplicate `WP_DEBUG` configuration warning remains.
- SQL comparisons confirm eight Property rows, 59 attachments, four Team records, five enquiries, metadata, taxonomy, front-page settings and other Pages unchanged. Sugarwell/Sienna remain drafts; FM1093 price suppression remains. All 98 protected file hashes match, including migration systems, Docker, Property systems and the Instagram provider.

**Review capture status:** native browser windows are currently returning empty accessibility state and no screenshot. A request to wake/unlock the Mac is pending. In-app browser visual inspection remains available, but the required PNG export set has not been saved. Browser security policy rejected opening a captured screenshot as a data URL; that export route was abandoned. Native reduced-motion/JavaScript-disabled visual verification and mouse-drag verification should be completed when the native capture surface is available; automated source/fallback checks already pass. No screenshots from the previous pass are represented as new evidence.

Audit logs and the safety snapshot are in ignored `var/homepage-choreography-qa/`. Nothing is staged or committed.
