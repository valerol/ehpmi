# Hero text and CTA ownership decision

Before Stage 6 field changes, the active implementation and dev data were inspected.

- The three `hero_slide` records contain only title, featured image and Order. They contain no text or CTA fields.
- Homepage hero copy is one block widget (`block-2`) assigned to the global `slider-text` sidebar.
- There is no current CTA in the hero.
- The global copy overlays the carousel independently of which slide is active.

Decision: retain the global Slider text ownership and do not introduce a per-slide text or CTA model in Stage 6. This is the least-surprising model because it preserves the existing editorial surface, text, behavior and accepted appearance. Per-slide fields would create a new editorial contract and visible slide-specific behavior without current content evidence.

Frozen widget instance SHA-256: `264d3a5dea705381b45bb43be47b86e580407e5865c8cc11d23caee00b449e3e`.
