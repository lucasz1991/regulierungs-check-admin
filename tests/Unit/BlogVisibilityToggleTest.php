<?php

namespace Tests\Unit;

use App\Livewire\Admin\Cms\WebContent\Blog\BlogList;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BlogVisibilityToggleTest extends TestCase
{
    private const CONNECTION = 'blog_visibility_testing';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => self::CONNECTION,
            'database.connections.'.self::CONNECTION => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        DB::purge(self::CONNECTION);

        Schema::connection(self::CONNECTION)->create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->string('key');
            $table->longText('value')->nullable();
            $table->timestamps();
        });

        Schema::connection(self::CONNECTION)->create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->string('title');
            $table->timestamps();
        });

        DB::table('settings')->insert([
            'type' => 'webcontent',
            'key' => 'blog_enabled',
            'value' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('posts')->insert([
            'type' => 'blog',
            'title' => 'Existing admin post',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge(self::CONNECTION);

        parent::tearDown();
    }

    public function test_admin_content_remains_available_and_toggle_persists_raw_values(): void
    {
        $component = new BlogList;
        $component->mount();

        $this->assertFalse($component->blogEnabled);
        $this->assertCount(1, $component->posts);

        $component->updatedBlogEnabled(true);
        $this->assertSame('1', (string) DB::table('settings')->where('key', 'blog_enabled')->value('value'));

        $component->updatedBlogEnabled(false);
        $this->assertSame('0', (string) DB::table('settings')->where('key', 'blog_enabled')->value('value'));
        $this->assertCount(1, $component->posts);
    }
}
