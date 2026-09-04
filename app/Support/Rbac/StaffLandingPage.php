<?php

namespace App\Support\Rbac;

use App\Models\User;

final class StaffLandingPage
{
    /** @var array<string, string> */
    private const PERMISSION_ROUTES = [
        'admin.dashboard.view' => 'admin.index',
        'promotion.wins.record' => 'promotion.console',
        'content.web.manage' => 'admin.webcontentmanager',
        'content.news.manage' => 'admin.webcontent.news',
        'content.pagebuilder.manage' => 'admin.cms.edit-project',
        'ratings.structure.manage' => 'admin.ratingstructure.index',
        'messages.manage' => 'admin.messages',
        'tasks.manage' => 'admin.tasks',
        'exports.manage' => 'admin.exports',
        'users.manage' => 'admin.users',
        'mails.manage' => 'admin.mails',
        'contacts.manage' => 'admin.contacts',
        'reviews.manage' => 'admin.reviews.claim-ratings',
    ];

    public function routeName(User $user): string
    {
        if ($user->isAdmin()) {
            return 'admin.index';
        }

        foreach (self::PERMISSION_ROUTES as $permission => $routeName) {
            if ($user->hasRbacPermission($permission)) {
                return $routeName;
            }
        }

        return 'home';
    }
}
