<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class NewsCacheVersion
{
    public const SETTING_TYPE = 'webcontent';

    public const SETTING_KEY = 'news_cache_version';

    /**
     * Publish a new generation in the shared database. UUIDs prevent two
     * closely timed admin writes from reusing the same public cache key.
     */
    public static function bump(): string
    {
        $version = (string) Str::uuid();

        DB::transaction(function () use ($version): void {
            $settingId = DB::table('settings')
                ->where('type', self::SETTING_TYPE)
                ->where('key', self::SETTING_KEY)
                ->latest('id')
                ->lockForUpdate()
                ->value('id');

            if ($settingId !== null) {
                DB::table('settings')
                    ->where('id', $settingId)
                    ->update([
                        'value' => $version,
                        'updated_at' => now(),
                    ]);

                return;
            }

            DB::table('settings')->insert([
                'type' => self::SETTING_TYPE,
                'key' => self::SETTING_KEY,
                'value' => $version,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return $version;
    }
}
