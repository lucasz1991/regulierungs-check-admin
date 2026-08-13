<?php

namespace App\Support\Rbac;

final class RbacCatalog
{
    public const PROMOTION_TEAM_NAME = 'Promotion';

    /** @return array<string, array<string, string>> */
    public static function groups(): array
    {
        return [
            'Administration' => [
                'admin.dashboard.view' => 'Dashboard ansehen',
                'settings.manage' => 'Systemeinstellungen verwalten',
                'roles.manage' => 'Teams und Rechte verwalten',
                'staff.manage' => 'Mitarbeiter und Einladungen verwalten',
                'audit.view' => 'Systemprotokoll ansehen',
            ],
            'Inhalte und Kommunikation' => [
                'content.web.manage' => 'Web-Inhalte verwalten',
                'content.news.manage' => 'News verwalten',
                'content.pagebuilder.manage' => 'Pagebuilder verwalten',
                'ratings.structure.manage' => 'Bewertungsstruktur verwalten',
                'messages.manage' => 'Nachrichten verwalten',
                'mails.manage' => 'E-Mails verwalten',
                'contacts.manage' => 'Kontakte verwalten',
                'reviews.manage' => 'Bewertungen verwalten',
            ],
            'Betrieb' => [
                'tasks.manage' => 'Aufgaben verwalten',
                'exports.manage' => 'Exporte verwalten',
                'users.manage' => 'Benutzer verwalten',
            ],
            'Promotion' => [
                'promotion.wins.record' => 'Gewinn gemeinsam erfassen',
                'promotion.wins.view_all' => 'Gewinnerliste ansehen',
                'promotion.fulfillment.onsite' => 'Vor-Ort-Gewinn ausgeben',
                'promotion.fulfillment.external' => 'Externe Erfuellung ausloesen',
                'promotion.campaigns.manage' => 'Kampagnen verwalten',
                'promotion.prizes.manage' => 'Preise verwalten',
                'promotion.corrections.manage' => 'Gewinnvorgaenge korrigieren',
                'promotion.audit.view' => 'Promotion-Audit ansehen',
            ],
        ];
    }

    /** @return list<string> */
    public static function allPermissions(): array
    {
        return array_values(array_merge(...array_values(array_map('array_keys', self::groups()))));
    }

    /** @return list<string> */
    public static function promotionTeamPermissions(): array
    {
        return [
            'promotion.wins.record',
            'promotion.wins.view_all',
            'promotion.fulfillment.onsite',
        ];
    }

    /** @return list<string> */
    public static function adminOnlyPermissions(): array
    {
        return [
            'settings.manage',
            'roles.manage',
            'staff.manage',
            'audit.view',
            'promotion.fulfillment.external',
            'promotion.campaigns.manage',
            'promotion.prizes.manage',
            'promotion.corrections.manage',
            'promotion.audit.view',
        ];
    }

    public static function isKnown(string $permission): bool
    {
        return in_array($permission, self::allPermissions(), true);
    }

    public static function isAdminOnly(string $permission): bool
    {
        return in_array($permission, self::adminOnlyPermissions(), true);
    }

    /** @param iterable<string>|array<string, bool> $permissions
     * @return array<string, bool>
     */
    public static function normalize(iterable $permissions): array
    {
        $normalized = [];

        foreach ($permissions as $key => $value) {
            $permission = is_int($key) ? (string) $value : (string) $key;
            $granted = is_int($key) ? true : (bool) $value;

            if ($granted && self::isKnown($permission)) {
                $normalized[$permission] = true;
            }
        }

        ksort($normalized);

        return $normalized;
    }

    /** @return array<string, bool> */
    public static function promotionTeamMatrix(): array
    {
        return self::normalize(self::promotionTeamPermissions());
    }
}
