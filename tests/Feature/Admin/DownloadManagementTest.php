<?php

namespace Tests\Feature\Admin;

use App\Models\Release;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DownloadManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_downloads_page_shows_no_build_when_none_published(): void
    {
        $system = User::factory()->system()->create();

        $this->actingAs($system)
            ->get(route('admin.downloads.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Downloads')
                ->where('platforms.0.available', false)
                ->where('platforms.1.available', false),
            );
    }

    public function test_downloads_page_shows_the_latest_release_per_platform(): void
    {
        $system = User::factory()->system()->create();

        Release::factory()->create(['platform' => 'windows', 'version' => '1.0.0', 'is_latest' => false]);
        Release::factory()->latest()->create(['platform' => 'windows', 'version' => '1.1.0']);
        Release::factory()->latest()->create(['platform' => 'linux', 'version' => '2.0.0']);

        $this->actingAs($system)
            ->get(route('admin.downloads.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Downloads')
                ->where('platforms.0.version', '1.1.0')
                ->where('platforms.1.version', '2.0.0'),
            );
    }

    public function test_non_system_users_cannot_manage_downloads(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->get(route('admin.downloads.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Downloads')
                ->where('canManage', false)
                ->where('releases', []),
            );

        $this->actingAs($owner)
            ->post(route('admin.downloads.store'), [
                'platform' => 'windows',
                'version' => '1.0.0',
                'file' => UploadedFile::fake()->create('kiosk.exe', 100),
            ])
            ->assertForbidden();
    }

    public function test_system_user_can_publish_a_new_release(): void
    {
        Storage::fake('public');

        $system = User::factory()->system()->create();

        $response = $this->actingAs($system)->post(route('admin.downloads.store'), [
            'platform' => 'windows',
            'version' => '1.0.0',
            'file' => UploadedFile::fake()->create('kiosk.exe', 100),
        ]);

        $response->assertSessionHasNoErrors();

        $release = Release::where('platform', 'windows')->where('version', '1.0.0')->first();

        $this->assertNotNull($release);
        $this->assertTrue($release->is_latest);
        $this->assertSame($system->id, $release->created_by);
        Storage::disk('public')->assertExists('downloads/windows/1.0.0/kiosk.exe');
    }

    public function test_publishing_a_new_release_supersedes_the_previous_latest(): void
    {
        Storage::fake('public');

        $system = User::factory()->system()->create();
        $previous = Release::factory()->latest()->create(['platform' => 'windows', 'version' => '1.0.0']);

        $this->actingAs($system)->post(route('admin.downloads.store'), [
            'platform' => 'windows',
            'version' => '1.1.0',
            'file' => UploadedFile::fake()->create('kiosk.exe', 100),
        ]);

        $this->assertFalse($previous->refresh()->is_latest);
        $this->assertTrue(Release::where('version', '1.1.0')->first()->is_latest);
    }

    public function test_publishing_a_duplicate_version_for_the_same_platform_is_rejected(): void
    {
        Storage::fake('public');

        $system = User::factory()->system()->create();
        Release::factory()->create(['platform' => 'windows', 'version' => '1.0.0']);

        $response = $this->actingAs($system)->post(route('admin.downloads.store'), [
            'platform' => 'windows',
            'version' => '1.0.0',
            'file' => UploadedFile::fake()->create('kiosk.exe', 100),
        ]);

        $response->assertSessionHasErrors('version');
        $this->assertSame(1, Release::where('platform', 'windows')->where('version', '1.0.0')->count());
    }

    public function test_publishing_rejects_a_malformed_version(): void
    {
        Storage::fake('public');

        $system = User::factory()->system()->create();

        $response = $this->actingAs($system)->post(route('admin.downloads.store'), [
            'platform' => 'windows',
            'version' => 'not-a-version',
            'file' => UploadedFile::fake()->create('kiosk.exe', 100),
        ]);

        $response->assertSessionHasErrors('version');
        $this->assertSame(0, Release::count());
    }
}
