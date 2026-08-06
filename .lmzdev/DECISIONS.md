# Decisions

Record durable decisions with date, context, decision, and consequences.

## 2026-08-05 | News social-image CTA removal

- Removed the complete graphical article CTA instead of hiding only its text.
- Re-anchored the remaining text content to the existing bottom margin so the released space is used cleanly in every format.
- Increased `NewsSocialImageController::LAYOUT_VERSION` to `4` so cached legacy images cannot retain the old CTA.

## 2026-08-06 | Preserve the removed CTA footprint

- Supersedes the previous decision to use the released CTA space for text.
- Keeps the former 108 px button height completely empty in addition to the existing 96 px bottom margin.
- Increased the layout version to `5` so cached version-4 images are regenerated with the corrected spacing.
