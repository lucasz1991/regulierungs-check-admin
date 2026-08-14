<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\CustomVerifyEmail;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('staff');
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('current_team_id')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_email_verification_screen_is_branded_and_responsive_without_minia_dependencies(): void
    {
        if (! Features::enabled(Features::emailVerification())) {
            $this->markTestSkipped('Email verification not enabled.');
        }

        $user = $this->unverifiedUser('staff');

        $this->actingAs($user)
            ->get('/email/verify')
            ->assertOk()
            ->assertSee('Regulierungs-CHECK Zugang')
            ->assertSee('E-Mail-Adresse bestätigen')
            ->assertSee($user->email)
            ->assertSee('Bestätigungs-E-Mail erneut senden')
            ->assertSee('site-images/logo/logo-icon.png', false)
            ->assertDontSee('Minia')
            ->assertDontSee('Themesbrand')
            ->assertDontSee('swiper', false)
            ->assertDontSee('minifinds_logo.png', false);
    }

    public function test_admin_and_promotion_staff_routes_do_not_require_email_verification(): void
    {
        $adminMiddleware = app('router')->getRoutes()->getByName('admin.index')->gatherMiddleware();
        $promotionMiddleware = app('router')->getRoutes()->getByName('promotion.console')->gatherMiddleware();

        $this->assertNotContains('verified', $adminMiddleware);
        $this->assertNotContains('verified', $promotionMiddleware);
        $this->assertNotContains('verified', config('jetstream.middleware'));
        $this->assertContains('account.active', $adminMiddleware);
        $this->assertContains('account.active', $promotionMiddleware);
    }

    public function test_verification_email_uses_admin_application_url_and_regulierungs_check_copy(): void
    {
        $user = $this->unverifiedUser('staff');

        URL::forceRootUrl('https://admin.regulierungs-check.test');

        try {
            $mail = (new CustomVerifyEmail)->toMail($user);
        } finally {
            URL::forceRootUrl(null);
        }

        $this->assertSame('admin.regulierungs-check.test', parse_url($mail->actionUrl, PHP_URL_HOST));
        $this->assertStringContainsString('/email/verify/', $mail->actionUrl);
        $this->assertSame('E-Mail-Adresse für Regulierungs-CHECK bestätigen', $mail->subject);
        $this->assertSame('E-Mail-Adresse bestätigen', $mail->actionText);
    }

    public function test_verification_email_can_be_resent(): void
    {
        Notification::fake();
        $user = $this->unverifiedUser('staff');

        $this->actingAs($user)
            ->from('/email/verify')
            ->post('/email/verification-notification')
            ->assertRedirect('/email/verify')
            ->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentTo($user, CustomVerifyEmail::class);
    }

    public function test_email_can_be_verified(): void
    {
        if (! Features::enabled(Features::emailVerification())) {
            $this->markTestSkipped('Email verification not enabled.');
        }

        Event::fake();

        $user = $this->unverifiedUser('staff');

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(RouteServiceProvider::HOME.'?verified=1');
    }

    public function test_email_can_not_be_verified_with_invalid_hash(): void
    {
        if (! Features::enabled(Features::emailVerification())) {
            $this->markTestSkipped('Email verification not enabled.');
        }

        $user = $this->unverifiedUser('staff');

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    private function unverifiedUser(string $role): User
    {
        return User::factory()->unverified()->create([
            'role' => $role,
            'status' => true,
        ]);
    }
}
