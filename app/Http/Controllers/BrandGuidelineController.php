<?php

namespace App\Http\Controllers;

use App\Models\BrandAsset;
use App\Models\BrandAuditLog;
use App\Models\BrandGuideline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BrandGuidelineController extends Controller
{
    /* ── Dashboard ── */
    public function index()
    {
        $user = Auth::user();
        $brand = BrandGuideline::forAccount($user->account_id)->first();

        // Auto-create draft if none exists
        if (!$brand) {
            $brand = BrandGuideline::create([
                'account_id' => $user->account_id,
                'status' => 'draft',
                'created_by' => $user->id,
            ]);
            BrandAuditLog::log($brand->id, 'created', 'foundation');
        }

        $brand->load('assets');

        $assetStats = [
            'total' => $brand->assets->count(),
            'by_category' => $brand->assets->groupBy('category')->map->count(),
        ];

        $recentLogs = BrandAuditLog::where('brand_guideline_id', $brand->id)
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'section' => $log->section,
                'user_name' => $log->user?->name ?? 'System',
                'changes' => $log->changes,
                'created_at' => $log->created_at->diffForHumans(),
            ]);

        return Inertia::render('BrandFoundation/Index', [
            'brand' => $brand,
            'assetStats' => $assetStats,
            'recentLogs' => $recentLogs,
            'assetCategories' => BrandAsset::CATEGORIES,
        ]);
    }

    /* ── Update Foundation (Purpose/Vision/Mission/Values/Personality/Positioning) ── */
    public function updateFoundation(Request $request)
    {
        $validated = $request->validate([
            'brand_purpose' => 'nullable|string|max:2000',
            'brand_vision' => 'nullable|string|max:2000',
            'brand_mission' => 'nullable|string|max:2000',
            'brand_promise' => 'nullable|string|max:2000',
            'brand_values' => 'nullable|array',
            'brand_values.*.name' => 'required|string',
            'brand_values.*.description' => 'nullable|string',
            'brand_values.*.icon' => 'nullable|string',
            'brand_personality' => 'nullable|array',
            'brand_personality.*.trait' => 'required|string',
            'brand_personality.*.score' => 'required|numeric|min:0|max:10',
            'brand_positioning' => 'nullable|array',
            'tagline' => 'nullable|string|max:500',
            'value_propositions' => 'nullable|array',
        ]);

        $brand = $this->getBrand();
        $brand->update(array_merge($validated, ['updated_by' => Auth::id()]));

        BrandAuditLog::log($brand->id, 'updated', 'foundation');

        return redirect()->back()->with('success', 'Brand Strategy đã được cập nhật!');
    }

    /* ── Update Visual Identity (Colors/Fonts/Logo) ── */
    public function updateVisual(Request $request)
    {
        $validated = $request->validate([
            'primary_colors' => 'nullable|array',
            'primary_colors.*.name' => 'required|string',
            'primary_colors.*.hex' => 'required|string|max:7',
            'secondary_colors' => 'nullable|array',
            'neutral_colors' => 'nullable|array',
            'font_primary' => 'nullable|string|max:100',
            'font_secondary' => 'nullable|string|max:100',
            'font_config' => 'nullable|array',
            'logo_guidelines' => 'nullable|array',
        ]);

        $brand = $this->getBrand();
        $brand->update(array_merge($validated, ['updated_by' => Auth::id()]));

        BrandAuditLog::log($brand->id, 'updated', 'visual');

        return redirect()->back()->with('success', 'Visual Identity đã được cập nhật!');
    }

    /* ── Update Voice & Messaging ── */
    public function updateVoice(Request $request)
    {
        $validated = $request->validate([
            'voice_traits' => 'nullable|array',
            'voice_traits.*.trait' => 'required|string',
            'voice_traits.*.description' => 'nullable|string',
            'voice_traits.*.do_example' => 'nullable|string',
            'voice_traits.*.dont_example' => 'nullable|string',
            'tone_variations' => 'nullable|array',
            'writing_guidelines' => 'nullable|array',
        ]);

        $brand = $this->getBrand();
        $brand->update(array_merge($validated, ['updated_by' => Auth::id()]));

        BrandAuditLog::log($brand->id, 'updated', 'voice');

        return redirect()->back()->with('success', 'Brand Voice đã được cập nhật!');
    }

    /* ── Upload Logo ── */
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'variant' => 'required|in:logo_primary,logo_horizontal,logo_icon,logo_white',
            'file' => 'required|file|mimes:png,jpg,jpeg,svg,webp|max:5120',
        ]);

        $brand = $this->getBrand();
        $path = $request->file('file')->store('brand/logos/' . $brand->account_id, 'public');

        $brand->update([
            $request->variant => '/storage/' . $path,
            'updated_by' => Auth::id(),
        ]);

        BrandAuditLog::log($brand->id, 'updated', 'logo', ['variant' => $request->variant]);

        return redirect()->back()->with('success', 'Logo đã được upload!');
    }

    /* ── Upload Brand Asset ── */
    public function uploadAsset(Request $request)
    {
        $request->validate([
            'category' => 'required|in:' . implode(',', array_keys(BrandAsset::CATEGORIES)),
            'name' => 'required|string|max:255',
            'file' => 'required|file|mimes:png,jpg,jpeg,svg,webp,pdf,ai,eps,psd|max:20480',
            'tags' => 'nullable|array',
        ]);

        $brand = $this->getBrand();
        $file = $request->file('file');
        $path = $file->store('brand/assets/' . $brand->account_id, 'public');

        BrandAsset::create([
            'brand_guideline_id' => $brand->id,
            'category' => $request->category,
            'name' => $request->name,
            'file_path' => '/storage/' . $path,
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'tags' => $request->tags ?? [],
            'metadata' => $this->extractFileMetadata($file),
            'uploaded_by' => Auth::id(),
        ]);

        BrandAuditLog::log($brand->id, 'asset_uploaded', 'asset', ['name' => $request->name, 'category' => $request->category]);

        return redirect()->back()->with('success', 'Asset đã được upload!');
    }

    /* ── Delete Brand Asset ── */
    public function deleteAsset(BrandAsset $brandAsset)
    {
        $path = str_replace('/storage/', '', $brandAsset->file_path);
        Storage::disk('public')->delete($path);

        BrandAuditLog::log($brandAsset->brand_guideline_id, 'asset_deleted', 'asset', ['name' => $brandAsset->name]);
        $brandAsset->delete();

        return redirect()->back()->with('success', 'Asset đã bị xóa!');
    }

    /* ── Publish ── */
    public function publish()
    {
        $brand = $this->getBrand();
        $brand->update([
            'status' => BrandGuideline::STATUS_ACTIVE,
            'published_at' => now(),
            'updated_by' => Auth::id(),
        ]);

        BrandAuditLog::log($brand->id, 'published', 'foundation');

        return redirect()->back()->with('success', 'Brand Guidelines đã được publish!');
    }

    /* ── Helpers ── */
    private function getBrand(): BrandGuideline
    {
        return BrandGuideline::forAccount(Auth::user()->account_id)->firstOrFail();
    }

    private function extractFileMetadata($file): array
    {
        $meta = ['original_name' => $file->getClientOriginalName()];
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $info = @getimagesize($file->getRealPath());
            if ($info) { $meta['width'] = $info[0]; $meta['height'] = $info[1]; }
        }
        return $meta;
    }
}
