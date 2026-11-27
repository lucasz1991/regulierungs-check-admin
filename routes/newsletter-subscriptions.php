<?php

use App\Livewire\Admin\NewsletterSubscribers;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'account.active', 'can:newsletter.view'])
    ->get('/admin/newsletter/abonnements', NewsletterSubscribers::class)->name('admin.newsletter.subscriptions');
