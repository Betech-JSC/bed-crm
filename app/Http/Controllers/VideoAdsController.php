<?php

namespace App\Http\Controllers;

use App\Models\VideoProject;
use App\Models\VideoTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class VideoAdsController extends Controller
{
    /**
     * Display video projects dashboard.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'video_type']);
        $projects = Auth::user()->account->videoProjects()
            ->filter($filters)
            ->with('creator:id,first_name,last_name')
            ->orderByDesc('updated_at')
            ->paginate(12)
            ->through(fn ($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'description' => $p->description,
                'status' => $p->status,
                'status_info' => $p->status_info,
                'video_type' => $p->video_type,
                'target_platforms' => $p->target_platforms,
                'platform_labels' => $p->platform_labels,
                'aspect_ratio' => $p->aspect_ratio,
                'duration_seconds' => $p->duration_seconds,
                'scene_count' => $p->scene_count,
                'product_name' => $p->product_name,
                'product_price' => $p->product_price,
                'thumbnail_path' => $p->thumbnail_path,
                'creator' => $p->creator ? ['id' => $p->creator->id, 'name' => $p->creator->name] : null,
                'updated_at' => $p->updated_at->diffForHumans(),
                'created_at' => $p->created_at->toISOString(),
            ]);

        // Stats
        $account = Auth::user()->account;
        $stats = [
            'total' => $account->videoProjects()->count(),
            'draft' => $account->videoProjects()->where('status', 'draft')->count(),
            'producing' => $account->videoProjects()->where('status', 'producing')->count(),
            'published' => $account->videoProjects()->where('status', 'published')->count(),
        ];

        return Inertia::render('VideoAds/Index', [
            'projects' => $projects,
            'filters' => $filters,
            'stats' => $stats,
            'statuses' => VideoProject::getStatuses(),
            'videoTypes' => VideoProject::getVideoTypes(),
            'platforms' => VideoProject::getPlatforms(),
        ]);
    }

    /**
     * Show create form.
     */
    public function create(): Response
    {
        $templates = VideoTemplate::where(function ($q) {
            $q->where('is_system', true)
              ->orWhere('account_id', Auth::user()->account_id);
        })->active()->get()->map(fn ($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'category' => $t->category,
            'description' => $t->description,
            'scene_structure' => $t->scene_structure,
            'aspect_ratio' => $t->aspect_ratio,
            'duration_seconds' => $t->duration_seconds,
            'style_config' => $t->style_config,
            'is_system' => $t->is_system,
        ]);

        // Seed system templates if none exist
        if ($templates->isEmpty()) {
            $this->seedSystemTemplates();
            $templates = VideoTemplate::system()->active()->get()->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'category' => $t->category,
                'description' => $t->description,
                'scene_structure' => $t->scene_structure,
                'aspect_ratio' => $t->aspect_ratio,
                'duration_seconds' => $t->duration_seconds,
                'style_config' => $t->style_config,
                'is_system' => $t->is_system,
            ]);
        }

        return Inertia::render('VideoAds/Create', [
            'templates' => $templates,
            'videoTypes' => VideoProject::getVideoTypes(),
            'platforms' => VideoProject::getPlatforms(),
            'aspectRatios' => VideoProject::getAspectRatios(),
        ]);
    }

    /**
     * Store a new video project.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'video_type' => 'required|string',
            'target_platforms' => 'required|array|min:1',
            'aspect_ratio' => 'required|string',
            'duration_seconds' => 'nullable|integer|min:5|max:300',
            'product_name' => 'nullable|string|max:255',
            'product_highlights' => 'nullable|string',
            'product_price' => 'nullable|numeric|min:0',
            'product_url' => 'nullable|url',
            'promo_code' => 'nullable|string|max:50',
            'cta_text' => 'nullable|string|max:100',
            'cta_url' => 'nullable|url',
            'ai_scenes' => 'nullable|array',
        ]);

        $project = Auth::user()->account->videoProjects()->create(array_merge(
            $request->only([
                'title', 'description', 'video_type', 'target_platforms',
                'aspect_ratio', 'duration_seconds', 'product_name',
                'product_highlights', 'product_price', 'product_url',
                'promo_code', 'cta_text', 'cta_url', 'ai_scenes',
            ]),
            ['created_by' => Auth::id(), 'status' => VideoProject::STATUS_DRAFT]
        ));

        return Redirect::route('video-ads.edit', $project->id)->with('success', 'Đã tạo dự án video!');
    }

    /**
     * Show edit / production page.
     */
    public function edit(VideoProject $project): Response
    {
        return Inertia::render('VideoAds/Edit', [
            'project' => [
                'id' => $project->id,
                'title' => $project->title,
                'description' => $project->description,
                'status' => $project->status,
                'status_info' => $project->status_info,
                'video_type' => $project->video_type,
                'target_platforms' => $project->target_platforms,
                'platform_labels' => $project->platform_labels,
                'aspect_ratio' => $project->aspect_ratio,
                'duration_seconds' => $project->duration_seconds,
                'language' => $project->language,
                'ai_script' => $project->ai_script,
                'ai_scenes' => $project->ai_scenes,
                'ai_voiceover_text' => $project->ai_voiceover_text,
                'ai_music_suggestion' => $project->ai_music_suggestion,
                'ai_hashtags' => $project->ai_hashtags,
                'ai_caption' => $project->ai_caption,
                'product_name' => $project->product_name,
                'product_highlights' => $project->product_highlights,
                'product_price' => $project->product_price,
                'product_url' => $project->product_url,
                'promo_code' => $project->promo_code,
                'media_assets' => $project->media_assets,
                'thumbnail_path' => $project->thumbnail_path,
                'cta_text' => $project->cta_text,
                'cta_url' => $project->cta_url,
                'brand_color' => $project->brand_color,
                'publish_schedule' => $project->publish_schedule,
                'settings' => $project->settings,
                'created_at' => $project->created_at->toISOString(),
                'updated_at' => $project->updated_at->diffForHumans(),
            ],
            'statuses' => VideoProject::getStatuses(),
            'videoTypes' => VideoProject::getVideoTypes(),
            'platforms' => VideoProject::getPlatforms(),
            'aspectRatios' => VideoProject::getAspectRatios(),
        ]);
    }

    /**
     * Update video project.
     */
    public function update(Request $request, VideoProject $project): RedirectResponse
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'sometimes|string',
            'video_type' => 'sometimes|string',
            'target_platforms' => 'sometimes|array',
            'aspect_ratio' => 'sometimes|string',
            'duration_seconds' => 'nullable|integer',
            'ai_script' => 'nullable|string',
            'ai_scenes' => 'nullable|array',
            'ai_voiceover_text' => 'nullable|string',
            'ai_music_suggestion' => 'nullable|string',
            'ai_hashtags' => 'nullable|array',
            'ai_caption' => 'nullable|string',
            'product_name' => 'nullable|string',
            'product_highlights' => 'nullable|string',
            'product_price' => 'nullable|numeric',
            'product_url' => 'nullable|string',
            'promo_code' => 'nullable|string',
            'cta_text' => 'nullable|string',
            'cta_url' => 'nullable|string',
            'brand_color' => 'nullable|string',
        ]);

        $project->update($request->only([
            'title', 'description', 'status', 'video_type', 'target_platforms',
            'aspect_ratio', 'duration_seconds', 'ai_script', 'ai_scenes',
            'ai_voiceover_text', 'ai_music_suggestion', 'ai_hashtags', 'ai_caption',
            'product_name', 'product_highlights', 'product_price', 'product_url',
            'promo_code', 'cta_text', 'cta_url', 'brand_color',
        ]));

        return Redirect::back()->with('success', 'Đã cập nhật!');
    }

    /**
     * AI Generate Script — the core AI feature.
     */
    public function generateScript(Request $request, VideoProject $project): JsonResponse
    {
        $prompt = $this->buildScriptPrompt($project);

        try {
            $apiKey = config('services.gemini.api_key', env('GEMINI_API_KEY'));

            if (!$apiKey) {
                // Return a mock response for development
                return response()->json($this->getMockScriptResponse($project));
            }

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}", [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    'temperature' => 0.8,
                    'topP' => 0.95,
                    'maxOutputTokens' => 4096,
                    'responseMimeType' => 'application/json',
                ],
            ]);

            if ($response->successful()) {
                $text = $response->json('candidates.0.content.parts.0.text', '');
                $data = json_decode($text, true) ?? $this->getMockScriptResponse($project);

                // Save to project
                $project->update([
                    'ai_script' => $data['script'] ?? '',
                    'ai_scenes' => $data['scenes'] ?? [],
                    'ai_voiceover_text' => $data['voiceover'] ?? '',
                    'ai_music_suggestion' => $data['music_suggestion'] ?? '',
                    'ai_hashtags' => $data['hashtags'] ?? [],
                    'ai_caption' => $data['caption'] ?? '',
                    'status' => VideoProject::STATUS_SCRIPTING,
                ]);

                return response()->json($data);
            }

            return response()->json($this->getMockScriptResponse($project));

        } catch (\Exception $e) {
            return response()->json($this->getMockScriptResponse($project));
        }
    }

    /**
     * AI Generate Caption & Hashtags.
     */
    public function generateCaption(Request $request, VideoProject $project): JsonResponse
    {
        $platform = $request->input('platform', 'tiktok');

        $caption = "🔥 {$project->product_name} — Sản phẩm đang HOT!\n\n";
        $caption .= $project->product_highlights ? "✅ " . str_replace("\n", "\n✅ ", $project->product_highlights) . "\n\n" : '';
        $caption .= $project->promo_code ? "🎁 Mã giảm giá: {$project->promo_code}\n" : '';
        $caption .= $project->product_price ? "💰 Chỉ " . number_format($project->product_price, 0, ',', '.') . "đ\n" : '';
        $caption .= $project->cta_url ? "\n👉 {$project->cta_url}\n" : '';

        $hashtags = ['#' . str_replace(' ', '', $project->product_name ?? 'sanpham')];
        $typeHashtags = [
            'product' => ['#review', '#unboxing', '#muangay', '#sanphamhot'],
            'promo' => ['#khuyenmai', '#giamgia', '#flashsale', '#deal'],
            'tutorial' => ['#huongdan', '#meo', '#tips', '#howtouse'],
            'ugc' => ['#honest_review', '#recommend', '#musthave'],
        ];
        $hashtags = array_merge($hashtags, $typeHashtags[$project->video_type] ?? ['#trending']);

        $project->update(['ai_caption' => $caption, 'ai_hashtags' => $hashtags]);

        return response()->json(['caption' => $caption, 'hashtags' => $hashtags]);
    }

    /**
     * Delete project.
     */
    public function destroy(VideoProject $project): RedirectResponse
    {
        $project->delete();
        return Redirect::route('video-ads')->with('success', 'Đã xóa dự án.');
    }

    /**
     * Duplicate project.
     */
    public function duplicate(VideoProject $project): RedirectResponse
    {
        $new = $project->replicate();
        $new->title = $project->title . ' (bản sao)';
        $new->status = VideoProject::STATUS_DRAFT;
        $new->created_by = Auth::id();
        $new->published_at = null;
        $new->save();

        return Redirect::route('video-ads.edit', $new->id)->with('success', 'Đã tạo bản sao!');
    }

    // ══════════════════════════
    //  PRIVATE METHODS
    // ══════════════════════════

    private function buildScriptPrompt(VideoProject $project): string
    {
        $types = VideoProject::getVideoTypes();
        $typeLabel = $types[$project->video_type]['label'] ?? $project->video_type;

        return "Bạn là chuyên gia sáng tạo video quảng cáo cho mạng xã hội (TikTok, Facebook, Instagram).
Hãy tạo kịch bản video quảng cáo với thông tin sau:

Loại video: {$typeLabel}
Sản phẩm: {$project->product_name}
Giá: " . ($project->product_price ? number_format($project->product_price, 0) . 'đ' : 'Chưa có') . "
Điểm nổi bật: {$project->product_highlights}
Mã khuyến mãi: " . ($project->promo_code ?: 'Không có') . "
CTA: " . ($project->cta_text ?: 'Mua ngay') . "
Thời lượng: " . ($project->duration_seconds ?: 30) . " giây
Tỷ lệ: {$project->aspect_ratio}
Nền tảng: " . implode(', ', $project->target_platforms ?? []) . "
Ngôn ngữ: Tiếng Việt

Trả về JSON với format:
{
  \"script\": \"Kịch bản dạng text đầy đủ\",
  \"scenes\": [
    {\"scene\": 1, \"label\": \"Tên cảnh\", \"duration\": 3, \"visual\": \"Mô tả hình ảnh\", \"text_overlay\": \"Text hiện trên màn hình\", \"voiceover\": \"Text đọc\", \"transition\": \"cut/fade/zoom\"}
  ],
  \"voiceover\": \"Toàn bộ text voiceover liên tục\",
  \"music_suggestion\": \"Gợi ý nhạc nền (mood, tempo, genre)\",
  \"hashtags\": [\"#hashtag1\", \"#hashtag2\"],
  \"caption\": \"Caption đăng kèm video\"
}

Lưu ý:
- Hook 3 giây đầu phải cực kỳ thu hút
- Ngôn ngữ tự nhiên, gần gũi, trending
- Phù hợp văn hóa Việt Nam
- Tối ưu cho nền tảng đã chọn";
    }

    private function getMockScriptResponse(VideoProject $project): array
    {
        $name = $project->product_name ?: 'Sản phẩm';
        $price = $project->product_price ? number_format($project->product_price, 0, ',', '.') . 'đ' : '';

        return [
            'script' => "Hook: \"Ai chưa biết {$name} thì phải xem ngay!\"\n\nGiới thiệu sản phẩm với các tính năng nổi bật.\n\nDemo sử dụng thực tế.\n\nKết quả ấn tượng.\n\nCTA: \"Link mua ở bio, nhanh tay kẻo hết!\"",
            'scenes' => [
                ['scene' => 1, 'label' => 'Hook — Thu hút', 'duration' => 3, 'visual' => 'Close-up reaction ngạc nhiên', 'text_overlay' => "🔥 {$name}", 'voiceover' => "Ai chưa biết {$name} thì phải xem ngay!", 'transition' => 'zoom_in'],
                ['scene' => 2, 'label' => 'Giới thiệu sản phẩm', 'duration' => 5, 'visual' => 'Unboxing, xoay sản phẩm 360°', 'text_overlay' => 'Sản phẩm HOT nhất', 'voiceover' => "Đây là {$name}, sản phẩm đang cháy hàng!", 'transition' => 'slide'],
                ['scene' => 3, 'label' => 'Demo & Tính năng', 'duration' => 8, 'visual' => 'Demo sử dụng thực tế', 'text_overlay' => $project->product_highlights ? explode("\n", $project->product_highlights)[0] : 'Tính năng nổi bật', 'voiceover' => $project->product_highlights ?: 'Sản phẩm với nhiều tính năng vượt trội', 'transition' => 'cut'],
                ['scene' => 4, 'label' => 'Kết quả / Before-After', 'duration' => 6, 'visual' => 'So sánh trước và sau', 'text_overlay' => 'Hiệu quả thực tế ✅', 'voiceover' => 'Và đây là kết quả sau khi sử dụng!', 'transition' => 'wipe'],
                ['scene' => 5, 'label' => 'CTA — Kêu gọi hành động', 'duration' => 4, 'visual' => "Logo + giá {$price} + CTA button", 'text_overlay' => ($project->cta_text ?: 'Mua ngay') . ($project->promo_code ? " — MÃ: {$project->promo_code}" : ''), 'voiceover' => 'Link mua ở bio, nhanh tay kẻo hết nhé!', 'transition' => 'fade'],
            ],
            'voiceover' => "Ai chưa biết {$name} thì phải xem ngay! Đây là sản phẩm đang cháy hàng. " . ($project->product_highlights ?: '') . " Và đây là kết quả thực tế. Link mua ở bio, nhanh tay kẻo hết!",
            'music_suggestion' => 'Nhạc trending TikTok, tempo nhanh 120-130 BPM, mood vui tươi năng động. Gợi ý: nhạc nền lo-fi beat hoặc trending sound.',
            'hashtags' => ['#' . str_replace(' ', '', $name), '#review', '#musthave', '#trending', '#viral', '#tiktokmademebuyit', '#fyp', '#xuhuong'],
            'caption' => "🔥 {$name} — Sản phẩm HOT nhất!\n\n" . ($project->product_highlights ? "✅ " . str_replace("\n", "\n✅ ", $project->product_highlights) . "\n\n" : '') . ($price ? "💰 Chỉ {$price}\n" : '') . ($project->promo_code ? "🎁 Mã giảm giá: {$project->promo_code}\n" : '') . "\n👉 Link ở bio!",
        ];
    }

    private function seedSystemTemplates(): void
    {
        foreach (VideoTemplate::getSystemTemplates() as $tpl) {
            VideoTemplate::create(array_merge($tpl, ['is_system' => true]));
        }
    }
}
