<?php

use App\Http\Controllers\NewsSocialImageController;
use App\Http\Controllers\PagebuilderProjectController;
use App\Http\Controllers\StaffInvitationController;
use App\Http\Middleware\SensitiveBearerPageHeaders;
use App\Livewire\Admin\AdminTasksList;
use App\Livewire\Admin\Cms\EditProject;
use App\Livewire\Admin\Employees;
use App\Livewire\Admin\Exports;
use App\Livewire\Admin\MailManagement;
use App\Livewire\Admin\ManageContacts;
use App\Livewire\Admin\PromotionAdministration;
use App\Livewire\Admin\RatingStructure\Index;
use App\Livewire\Admin\Reviews\ClaimRatingList;
use App\Livewire\Admin\Reviews\ShowClaimRating;
use App\Livewire\Admin\Safety;
use App\Livewire\Admin\TeamPermissions;
use App\Livewire\Admin\UserProfile;
use App\Livewire\Admin\Users;
use App\Livewire\AdminConfig;
use App\Livewire\AdminDashboard;
use App\Livewire\AdminMessageBox;
use App\Livewire\Promotion\PromotionConsole;
use App\Livewire\WebContentManager;
use App\Livewire\Welcome;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', Welcome::class)->name('home');

Route::middleware(['guest', SensitiveBearerPageHeaders::class])->group(function (): void {
    Route::get('/mitarbeiter/einladung/annehmen', [StaffInvitationController::class, 'accept'])->name('staff-invitations.accept');
    Route::post('/mitarbeiter/einladung/annehmen', [StaffInvitationController::class, 'store'])->name('staff-invitations.store');
    Route::get('/mitarbeiter/einladung/{token}', [StaffInvitationController::class, 'show'])->name('staff-invitations.show');
});

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'account.active'])->group(function (): void {
    Route::middleware(['promotion.enabled', 'can:promotion.wins.record'])->group(function (): void {
        Route::get('/promotion', PromotionConsole::class)->name('promotion.console');
    });

    Route::get('/admin', AdminDashboard::class)->middleware('can:admin.dashboard.view')->name('admin.index');
    Route::get('/config', AdminConfig::class)->middleware('can:settings.manage')->name('admin.config');

    Route::get('/web-content-manager', WebContentManager::class)->middleware('can:content.web.manage')->name('admin.webcontentmanager');
    Route::get('/web-content-manager/news', WebContentManager::class)->middleware('can:content.news.manage')->name('admin.webcontent.news');

    Route::middleware('can:content.pagebuilder.manage')->group(function (): void {
        Route::get('/admin/cms/edit-project/{projectId?}', EditProject::class)->name('admin.cms.edit-project');
        Route::post('/admin/pagebuilder/save', [PagebuilderProjectController::class, 'save']);
        Route::get('/admin/pagebuilder/load/{name}', [PagebuilderProjectController::class, 'load']);
        Route::post('/admin/pagebuilder/upload', [PagebuilderProjectController::class, 'uploadImage']);
        Route::get('/admin/pagebuilder/assets', [PagebuilderProjectController::class, 'getAssets']);
    });
    Route::middleware('can:content.news.manage')->group(function (): void {
        Route::get('/admin/news/{post}/social-image', [NewsSocialImageController::class, 'preview'])->name('admin.news.social-image.preview');
        Route::get('/admin/news/{post}/social-image/download', [NewsSocialImageController::class, 'download'])->name('admin.news.social-image.download');
    });

    Route::get('/rating-structure', Index::class)->middleware('can:ratings.structure.manage')->name('admin.ratingstructure.index');
    Route::get('/adminmessages', AdminMessageBox::class)->middleware('can:messages.manage')->name('admin.messages');
    Route::get('/admin/tasks', AdminTasksList::class)->middleware('can:tasks.manage')->name('admin.tasks');
    Route::get('/exports', Exports::class)->middleware('can:exports.manage')->name('admin.exports');
    Route::get('/users', Users::class)->middleware('can:users.manage')->name('admin.users');
    Route::get('/admin/user/{userId}', UserProfile::class)->middleware('can:users.manage')->name('admin.user-profile');
    Route::get('/admin/safety', Safety::class)->middleware('can:audit.view')->name('admin.safety');
    Route::get('/admin/employees', Employees::class)->middleware('can:staff.manage')->name('admin.employees');
    Route::get('/admin/teams/permissions', TeamPermissions::class)->middleware('can:roles.manage')->name('admin.team-permissions');
    // Volladmins muessen Kampagnen vorbereiten und pruefen koennen, waehrend
    // der oeffentliche/staffseitige Promotion-Flow noch deaktiviert ist.
    Route::get('/admin/promotion', PromotionAdministration::class)->middleware('can:promotion.campaigns.manage')->name('admin.promotion');
    Route::get('/admin/mails', MailManagement::class)->middleware('can:mails.manage')->name('admin.mails');
    Route::get('/admin/contacts', ManageContacts::class)->middleware('can:contacts.manage')->name('admin.contacts');
    Route::get('/admin/reviews/claim-ratings', ClaimRatingList::class)->middleware('can:reviews.manage')->name('admin.reviews.claim-ratings');
    Route::get('/admin/reviews/claim-rating/{ratingId}', ShowClaimRating::class)->middleware('can:reviews.manage')->name('admin.reviews.show');
});
