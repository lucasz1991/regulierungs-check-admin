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

## Verification

- `php artisan test tests\Unit\NewsSocialImageTest.php tests\Unit\NewsSocialImageRouteTest.php`: 19 tests passed, 94 assertions.
- `php -l` passed for the renderer, controller, and focused test file.
- `git diff --check` passed.
- Story, square, and landscape artifacts were re-rendered and visually checked with the preserved CTA-height spacing.
- `php artisan test tests\Unit\NewsSocialImageTest.php tests\Unit\NewsSocialImageRouteTest.php tests\Unit\NewsCacheVersionTest.php`: 37 tests passed, 180 assertions.
- The Base migration was checked with `php artisan migrate --pretend`; PHP syntax, `git diff --check`, and the News Tailwind contract passed.
- Custom Story, Post, and Link layouts were rendered and visually inspected.
- Latest focused verification: 39 tests passed with 196 assertions; the News Tailwind contract and both repository diff checks passed.

## Risks and blockers

- Full-file Pint remains non-clean because these existing files contain broader legacy style and line-ending deviations; no broad formatter churn was applied for this narrow change.
- A signed-in local Admin browser session was unavailable, so no temporary user or credentials were inserted. The focused Livewire render test covers the complete settings disclosure and accessibility contract.
- Global `php artisan view:cache` still fails on the pre-existing unresolved `x-admin-layout` component outside this modal; direct Livewire rendering succeeds.
