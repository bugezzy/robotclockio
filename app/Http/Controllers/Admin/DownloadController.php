<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Release;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DownloadController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const PLATFORM_LABELS = [
        'windows' => 'Windows',
        'linux' => 'Linux',
    ];

    /**
     * Show the kiosk desktop app downloads: the current latest build per
     * platform for everyone, plus (system only) the full version history
     * and the form to publish a new one.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $latestByPlatform = Release::where('is_latest', true)->get()->keyBy('platform');

        return Inertia::render('admin/Downloads', [
            'platforms' => collect(self::PLATFORM_LABELS)
                ->map(function (string $label, string $platform) use ($latestByPlatform) {
                    $latest = $latestByPlatform->get($platform);

                    return [
                        'platform' => $platform,
                        'label' => $label,
                        'available' => $latest !== null,
                        'version' => $latest?->version,
                        'url' => $latest?->url,
                    ];
                })
                ->values(),
            'canManage' => $user->isSystem(),
            'releases' => $user->isSystem()
                ? Release::with('uploadedBy:id,full_name')
                    ->orderByDesc('created_at')
                    ->limit(20)
                    ->get()
                    ->map(fn (Release $release) => [
                        'id' => $release->id,
                        'platform' => $release->platform,
                        'version' => $release->version,
                        'is_latest' => $release->is_latest,
                        'created_at' => $release->created_at->toIso8601String(),
                        'uploaded_by' => $release->uploadedBy?->name,
                    ])
                : [],
        ]);
    }

    /**
     * Publish a new kiosk build for a platform. System only — this
     * immediately becomes that platform's "latest", tagging the previous
     * one off; history is never deleted, only superseded.
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSystem(), 403);

        $data = $request->validate([
            'platform' => ['required', Rule::in(array_keys(self::PLATFORM_LABELS))],
            'version' => [
                'required', 'string', 'max:50', 'regex:/^\d+\.\d+\.\d+$/',
                Rule::unique(Release::class)->where('platform', $request->input('platform')),
            ],
            'file' => ['required', 'file', 'max:512000'],
        ]);

        $path = $data['file']->storeAs(
            "downloads/{$data['platform']}/{$data['version']}",
            $data['file']->getClientOriginalName(),
            'public',
        );

        DB::connection((new Release)->getConnectionName())->transaction(function () use ($data, $path, $request) {
            Release::where('platform', $data['platform'])->update(['is_latest' => false]);

            Release::create([
                'platform' => $data['platform'],
                'version' => $data['version'],
                'url' => Storage::disk('public')->url($path),
                'is_latest' => true,
                'created_by' => $request->user()->id,
            ]);
        });

        return back();
    }
}
