<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\CustomResetPasswordNotification;
use App\Services\Auth\PasswordResetService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'https://admin.regulierungs-check.test', 'mail.default' => 'array']);
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role');
            $table->boolean('status')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->unsignedBigInteger('current_team_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    private function user(string $role = 'staff', bool $active = true): User
    {
        return User::create([
            'name' => 'Reset Test', 'email' => uniqid('reset-').'@example.test',
            'password' => Hash::make('Old-Password!123'), 'role' => $role, 'status' => $active,
        ]);
    }

    private function input(User $user, string $token): array
    {
        return ['email' => $user->email, 'token' => $token,
            'password' => 'New-Password!456', 'password_confirmation' => 'New-Password!456'];
    }

    public function test_request_sends_branded_notification_and_repeated_request_does_not_replace_token(): void
    {
        Notification::fake();
        $user = $this->user();
        $this->post('/forgot-password', ['email' => strtoupper($user->email)])
            ->assertSessionHasNoErrors()->assertSessionHas('status', PasswordResetService::SENT_MESSAGE);
        $token = Notification::sent($user, CustomResetPasswordNotification::class)->sole()->token;
        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHasNoErrors();
        Notification::assertSentToTimes($user, CustomResetPasswordNotification::class, 1);
        $this->assertTrue(Password::tokenExists($user, $token));
    }

    public function test_unknown_inactive_and_wrong_surface_accounts_are_not_sent_mail(): void
    {
        Notification::fake();
        foreach ([$this->user('staff', false)->email, $this->user('guest')->email, 'missing@example.test'] as $email) {
            $this->post('/forgot-password', compact('email'))
                ->assertSessionHasNoErrors()->assertSessionHas('status', PasswordResetService::SENT_MESSAGE);
        }
        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_request_is_rate_limited_by_ip_even_for_unknown_accounts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/forgot-password', ['email' => "missing{$i}@example.test"])->assertSessionHasNoErrors();
        }
        $this->post('/forgot-password', ['email' => 'sixth@example.test'])->assertSessionHasErrors('email');
    }

    public function test_valid_reset_rotates_remember_token_emits_event_and_consumes_token(): void
    {
        Event::fake([PasswordReset::class]);
        $user = $this->user();
        $user->setRememberToken('previous-remember-token');
        $user->save();
        $token = Password::createToken($user);
        $this->post('/reset-password', $this->input($user, $token))->assertRedirect(route('login'))->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('New-Password!456', $user->fresh()->password));
        $this->assertNotSame('previous-remember-token', $user->fresh()->remember_token);
        $this->assertFalse(Password::tokenExists($user, $token));
        Event::assertDispatched(PasswordReset::class);
        $this->post('/reset-password', $this->input($user, $token))->assertSessionHasErrors('email');
    }

    public function test_invalid_expired_wrong_account_and_wrong_surface_tokens_cannot_reset_password(): void
    {
        $user = $this->user();
        $other = $this->user();
        $token = Password::createToken($user);
        $this->post('/reset-password', $this->input($user, 'invalid'))->assertSessionHasErrors('email');
        $this->post('/reset-password', $this->input($other, $token))->assertSessionHasErrors('email');
        $wrongSurface = $this->user('guest');
        $this->post('/reset-password', $this->input($wrongSurface, Password::createToken($wrongSurface)))->assertSessionHasErrors('email');
        $this->travel(61)->minutes();
        $this->post('/reset-password', $this->input($user, $token))->assertSessionHasErrors('email');
        foreach ([$user, $other, $wrongSurface] as $unchanged) {
            $this->assertTrue(Hash::check('Old-Password!123', $unchanged->fresh()->password));
        }
    }

    public function test_password_confirmation_and_policy_are_enforced(): void
    {
        $user = $this->user();
        $token = Password::createToken($user);
        $input = $this->input($user, $token);
        $input['password_confirmation'] = 'different';
        $this->post('/reset-password', $input)->assertSessionHasErrors('password');
        $input['password'] = $input['password_confirmation'] = 'short';
        $this->post('/reset-password', $input)->assertSessionHasErrors('password');
        $this->assertTrue(Password::tokenExists($user, $token));
    }

    public function test_real_notification_renders_through_this_app_mail_transport(): void
    {
        $user = $this->user();
        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHasNoErrors();
        $messages = Mail::mailer()->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $message = $messages->first()->getOriginalMessage();
        $this->assertStringContainsString('Regulierungs-CHECK', $message->getSubject());
        $html = $message->getHtmlBody();
        $this->assertStringContainsString('https://admin.regulierungs-check.test/reset-password/', $html);
        $this->assertStringContainsString('60 Minuten', $html);
        $this->assertStringContainsString('nur einmal', $html);
        $this->assertStringNotContainsString('Minia', $html);
        $this->assertSame($user->email, $message->getTo()[0]->getAddress());
    }

    public function test_transport_failure_is_reported_as_error_instead_of_success(): void
    {
        $user = $this->user();
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('transport unavailable'));
        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHasErrors('email')->assertSessionMissing('status');
    }

    public function test_admin_entry_and_protected_routes_redirect_guests_to_login(): void
    {
        foreach (['/', '/admin', '/admin/employees', '/promotion'] as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
        $admin = $this->user('admin');
        $this->actingAs($admin)->get('/')->assertRedirect(route('admin.index'));
    }

    public function test_admin_forms_render_branded_and_prefill_the_reset_email(): void
    {
        $this->get('/forgot-password')->assertOk()->assertSee('Regulierungs-CHECK')->assertDontSee('Minia');
        $this->get('/reset-password/test-token?email=staff%40example.test')
            ->assertOk()->assertSee('staff@example.test')->assertSee('Passwort speichern')->assertDontSee('Signup now')
            ->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_employee_management_reset_requires_permission_and_uses_admin_notification(): void
    {
        Notification::fake();
        $admin = $this->user('admin');
        $staff = $this->user();
        $this->actingAs($admin);
        (new \App\Livewire\Admin\Employees)->sendPasswordReset($staff->id, app(PasswordResetService::class));
        Notification::assertSentTo($staff, CustomResetPasswordNotification::class);

        $this->actingAs($this->user('guest'));
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        (new \App\Livewire\Admin\Employees)->sendPasswordReset($staff->id, app(PasswordResetService::class));
    }
}
