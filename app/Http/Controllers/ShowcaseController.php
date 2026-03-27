<?php

namespace App\Http\Controllers;

use App\Models\ShowcaseCollection;
use App\Models\ShowcaseItem;
use App\Services\AI\AiGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ShowcaseController extends Controller
{
    public function __construct(private AiGateway $ai) {}

    /* ── Dashboard ── */
    public function index()
    {
        $user = Auth::user();

        $collections = ShowcaseCollection::forAccount($user->account_id)
            ->with(['items', 'creator'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'industry' => $c->industry,
                'client_name' => $c->client_name,
                'notes' => $c->notes,
                'items_count' => $c->items->count(),
                'items' => $c->items->map(fn ($i) => [
                    'id' => $i->id,
                    'url' => $i->url,
                    'title' => $i->title,
                    'industry' => $i->industry,
                    'analysis' => $i->analysis,
                    'source' => $i->source,
                    'is_own_project' => $i->is_own_project,
                ]),
                'creator_name' => $c->creator?->name ?? 'System',
                'created_at' => $c->created_at->diffForHumans(),
            ]);

        return Inertia::render('Showcase/Index', [
            'collections' => $collections,
            'industries' => $this->industries(),
        ]);
    }

    /* ── AI Analyze URL ── */
    public function analyzeUrl(Request $request): JsonResponse
    {
        set_time_limit(120);

        $request->validate([
            'url' => 'required|url|max:500',
            'language' => 'nullable|in:vi,en',
        ]);

        $url = $request->url;
        $lang = $request->language ?? 'vi';

        $prompt = $this->buildAnalyzePrompt($url, $lang);

        try {
            $result = $this->ai->chat($prompt, [
                'response_format' => 'json',
                'temperature' => 0.4,
                'max_tokens' => 3000,
                'timeout' => 90,
                'system_prompt' => 'You are an expert web design analyst and UX consultant. Analyze websites professionally and provide detailed, actionable insights. Always respond in valid JSON.',
            ]);

            $analysis = json_decode($result['content'], true);

            if (!$analysis) {
                // Try to extract JSON from content
                preg_match('/\{[\s\S]*\}/', $result['content'], $matches);
                $analysis = $matches ? json_decode($matches[0], true) : null;
            }

            return response()->json([
                'success' => true,
                'analysis' => $analysis ?? ['error' => 'Could not parse AI response'],
                'ai_model' => $result['metadata']['model'] ?? 'unknown',
                'tokens_used' => $result['metadata']['tokens_used'] ?? 0,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI không thể phân tích website: ' . $e->getMessage(),
            ], 500);
        }
    }

    /* ── AI Discover by Industry ── */
    public function discoverByIndustry(Request $request): JsonResponse
    {
        set_time_limit(120);

        $request->validate([
            'industry' => 'required|string|max:100',
            'count' => 'nullable|integer|min:1|max:5',
            'style_preference' => 'nullable|string|max:200',
            'language' => 'nullable|in:vi,en',
        ]);

        $industry = $request->industry;
        $count = $request->count ?? 3;
        $style = $request->style_preference;
        $lang = $request->language ?? 'vi';

        $prompt = $this->buildDiscoverPrompt($industry, $count, $style, $lang);

        try {
            $result = $this->ai->chat($prompt, [
                'response_format' => 'json',
                'temperature' => 0.6,
                'max_tokens' => 4096,
                'timeout' => 90,
                'system_prompt' => 'You are a senior creative director with deep knowledge of web design trends across all industries worldwide. You know real, existing websites and can recommend the best references. Always respond in valid JSON.',
            ]);

            $websites = json_decode($result['content'], true);

            if (!$websites) {
                preg_match('/\{[\s\S]*\}/', $result['content'], $matches);
                $websites = $matches ? json_decode($matches[0], true) : null;
            }

            return response()->json([
                'success' => true,
                'websites' => $websites,
                'ai_model' => $result['metadata']['model'] ?? 'unknown',
                'tokens_used' => $result['metadata']['tokens_used'] ?? 0,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI không thể tìm website: ' . $e->getMessage(),
            ], 500);
        }
    }

    /* ── Save Collection ── */
    public function saveCollection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'industry' => 'nullable|string|max:100',
            'client_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.url' => 'required|string|max:500',
            'items.*.title' => 'required|string|max:255',
            'items.*.industry' => 'nullable|string',
            'items.*.analysis' => 'nullable|array',
            'items.*.source' => 'nullable|string',
            'items.*.is_own_project' => 'nullable|boolean',
        ]);

        $user = Auth::user();

        $collection = ShowcaseCollection::create([
            'account_id' => $user->account_id,
            'title' => $validated['title'],
            'industry' => $validated['industry'],
            'client_name' => $validated['client_name'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => $user->id,
        ]);

        foreach ($validated['items'] as $item) {
            ShowcaseItem::create([
                'collection_id' => $collection->id,
                'url' => $item['url'],
                'title' => $item['title'],
                'industry' => $item['industry'] ?? $validated['industry'],
                'analysis' => $item['analysis'] ?? null,
                'source' => $item['source'] ?? 'manual',
                'is_own_project' => $item['is_own_project'] ?? false,
            ]);
        }

        return response()->json(['success' => true, 'collection_id' => $collection->id, 'message' => 'Collection đã được lưu!']);
    }

    /* ── Delete Collection ── */
    public function deleteCollection(ShowcaseCollection $collection): JsonResponse
    {
        $collection->delete();
        return response()->json(['success' => true]);
    }

    /* ── Delete Item ── */
    public function deleteItem(ShowcaseItem $item): JsonResponse
    {
        $item->delete();
        return response()->json(['success' => true]);
    }

    /* ═══ AI PROMPTS ═══ */

    private function buildAnalyzePrompt(string $url, string $lang): string
    {
        $langLabel = $lang === 'vi' ? 'tiếng Việt' : 'English';

        return <<<PROMPT
Phân tích website sau: {$url}

Trả về JSON với cấu trúc sau (response bằng {$langLabel}):
{
  "title": "Tên website/thương hiệu",
  "url": "{$url}",
  "industry": "Ngành nghề",
  "design_score": 8.5,
  "summary": "Tóm tắt 2-3 câu về website",
  "highlights": {
    "layout": "Nhận xét về layout/bố cục",
    "typography": "Nhận xét về typography",
    "color_scheme": "Nhận xét về màu sắc",
    "ux_flow": "Nhận xét về trải nghiệm người dùng",
    "animation": "Nhận xét về animation/motion",
    "mobile_friendly": "Có responsive tốt không"
  },
  "tech_stack": ["Next.js", "TailwindCSS", "Framer Motion"],
  "strengths": ["Điểm mạnh 1", "Điểm mạnh 2", "Điểm mạnh 3"],
  "weaknesses": ["Điểm yếu 1", "Điểm yếu 2"],
  "target_audience": "Đối tượng mục tiêu",
  "similar_sites": ["url1.com", "url2.com"],
  "client_summary": "Đoạn văn ngắn formatted sẵn để gửi cho khách hàng giới thiệu website này như một reference"
}
PROMPT;
    }

    private function buildDiscoverPrompt(string $industry, int $count, ?string $style, string $lang): string
    {
        $langLabel = $lang === 'vi' ? 'tiếng Việt' : 'English';
        $styleNote = $style ? "\nPhong cách mong muốn: {$style}" : '';

        return <<<PROMPT
Tìm {$count} website showcase tốt nhất cho ngành: {$industry}{$styleNote}

Yêu cầu:
- Các website phải THỰC SỰ TỒN TẠI và có thể truy cập
- Ưu tiên website có design xuất sắc, hiện đại
- Đa dạng phong cách (minimalist, bold, corporate, creative)
- Bao gồm cả website quốc tế và Việt Nam nếu phù hợp

Trả về JSON (response bằng {$langLabel}):
{
  "industry": "{$industry}",
  "websites": [
    {
      "title": "Tên website",
      "url": "https://example.com",
      "industry": "{$industry}",
      "design_score": 9.0,
      "summary": "Mô tả ngắn 2-3 câu",
      "highlights": {
        "layout": "...",
        "typography": "...",
        "color_scheme": "...",
        "ux_flow": "...",
        "animation": "..."
      },
      "tech_stack": ["React", "GSAP"],
      "strengths": ["Điểm mạnh 1", "Điểm mạnh 2"],
      "why_reference": "Tại sao website này phù hợp làm tham khảo cho ngành {$industry}",
      "client_summary": "Đoạn formatted sẵn gửi khách"
    }
  ],
  "overall_recommendation": "Tóm tắt và gợi ý chung cho khách"
}
PROMPT;
    }

    private function industries(): array
    {
        return [
            'technology' => 'Công nghệ / SaaS',
            'ecommerce' => 'Thương mại điện tử',
            'healthcare' => 'Y tế / Sức khỏe',
            'education' => 'Giáo dục',
            'finance' => 'Tài chính / Fintech',
            'realestate' => 'Bất động sản',
            'fnb' => 'F&B / Nhà hàng',
            'travel' => 'Du lịch / Hospitality',
            'fashion' => 'Thời trang / Beauty',
            'automotive' => 'Ô tô / Xe máy',
            'construction' => 'Xây dựng / Nội thất',
            'logistics' => 'Logistics / Vận tải',
            'media' => 'Truyền thông / Agency',
            'manufacturing' => 'Sản xuất / Công nghiệp',
            'ngo' => 'NGO / Phi lợi nhuận',
            'portfolio' => 'Portfolio cá nhân',
            'corporate' => 'Doanh nghiệp / Corporate',
            'startup' => 'Startup / Landing Page',
        ];
    }
}
