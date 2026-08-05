# Current state

## Confirmed

- LMZ Dev workspace initialized.
- The embedded `ZUM ARTIKEL` button, link icon, and button-only color constant were removed from all News social-image formats.
- The title/excerpt block now uses the released lower image area with the existing 96 px format-scaled bottom margin.
- Social-image layout version is `4`, forcing cached images to be regenerated without the button.

## Verification

- `php artisan test tests\Unit\NewsSocialImageTest.php tests\Unit\NewsSocialImageRouteTest.php`: 19 tests passed, 94 assertions.
- `php -l` passed for the renderer, controller, and focused test file.
- `git diff --check` passed.
- Story, square, and landscape artifacts were rendered and visually checked.

## Risks and blockers

- Full-file Pint remains non-clean because these existing files contain broader legacy style and line-ending deviations; no broad formatter churn was applied for this narrow change.
