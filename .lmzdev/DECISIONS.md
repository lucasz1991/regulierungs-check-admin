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

## 2026-08-07 | Persist per-News, per-format social-image layouts

- Store a complete normalized configuration under nullable `posts.social_image_settings`; existing News without JSON keep the previous layout exactly.
- Keep Story, Post, and Link settings independent, while the logo color remains a download-time variant as before.
- Expose only finite dropdown values and validate them server-side with German messages, preventing malformed JSON from breaking the renderer.
- Require an explicit save before preview/download refresh; while settings are dirty, the download action is disabled so the file always matches the visible saved state.
- Use the shared Base project for the database migration and cast the JSON column in both Post models.
