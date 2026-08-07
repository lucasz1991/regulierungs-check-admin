# Current state

## Confirmed

- LMZ Dev workspace initialized.
- The embedded `ZUM ARTIKEL` button, link icon, and button-only color constant were removed from all News social-image formats.
- The title/excerpt block keeps the former 108 px button height empty above the existing 96 px format-scaled bottom margin.
- Social-image layout version is `5`, forcing cached images to be regenerated with the corrected spacing.
- Each News can now persist separate Story, Post, and Link layout settings in `posts.social_image_settings`.
- The collapsible modal controls typography, horizontal/bottom/section spacing, logo size, and category size through allowlisted dropdown values.
- Social-image layout version is now `6`; the normalized JSON configuration is part of the cache fingerprint.
- Saving is scoped to the active Story, Post, or Link format; its preview URL and cache entry change immediately, while other format settings, dirty state, and cached images remain untouched.
- The former central settings dropdown is replaced by seven image-anchored hover/focus/tap hotspots for logo, category, title, text gap, excerpt, side padding, and bottom spacing.
- Every select change now persists and re-renders immediately without a save button; logo variants are persisted separately per format as well.
- The shared-database migration for `posts.social_image_settings` was executed successfully on 2026-08-07.

## Verification

- `php artisan test tests\Unit\NewsSocialImageTest.php tests\Unit\NewsSocialImageRouteTest.php`: 19 tests passed, 94 assertions.
- `php -l` passed for the renderer, controller, and focused test file.
- `git diff --check` passed.
- Story, square, and landscape artifacts were re-rendered and visually checked with the preserved CTA-height spacing.
- `php artisan test tests\Unit\NewsSocialImageTest.php tests\Unit\NewsSocialImageRouteTest.php tests\Unit\NewsCacheVersionTest.php`: 37 tests passed, 180 assertions.
- The Base migration was checked with `php artisan migrate --pretend`; PHP syntax, `git diff --check`, and the News Tailwind contract passed.
- Custom Story, Post, and Link layouts were rendered and visually inspected.
- Latest focused verification: 39 tests passed with 196 assertions; the News Tailwind contract and both repository diff checks passed.
- Final verification: 40 focused tests passed with 206 assertions. Authenticated browser QA confirmed desktop hover/click, mobile tap, immediate preview URL revision, database persistence after reload, and isolated Story/Post values and logo variants.

## Risks and blockers

- Full-file Pint remains non-clean because these existing files contain broader legacy style and line-ending deviations; no broad formatter churn was applied for this narrow change.
- Authenticated QA used a uniquely named temporary local Admin and News record; both records and the generated image-cache directory were deleted and verified absent afterward.
- Global `php artisan view:cache` still fails on the pre-existing unresolved `x-admin-layout` component outside this modal; direct Livewire rendering succeeds.
