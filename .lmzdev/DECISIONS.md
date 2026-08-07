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
- Persist only the active format on each save, preserve unsaved edits in the other formats, and use a per-format preview revision plus per-format cache fingerprint.
- Do not change the News `updated_at` timestamp for social-image layout metadata, otherwise saving one format would invalidate all three image caches.

## 2026-08-07 | Inline auto-saving social-image controls

- Supersedes the explicit-save decision: every allowlisted select change is persisted immediately and increments only the active format's preview revision.
- Supersedes the download-only logo-variant decision: `logo_variant` is now stored independently inside every format's JSON configuration.
- Anchor controls to the visual element they affect; keep side spacing once at the left edge and bottom spacing once at the lower image edge.
- Open every control by hover, keyboard focus, or tap, with the mobile side marker moved inside the image so its complete 36 px target remains available.
- Execute the previously pending nullable JSON migration after runtime schema evidence proved the production-like local database did not yet contain the column.
