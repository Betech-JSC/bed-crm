<?php

namespace App\Http\Controllers;

use App\Models\AiContentTemplate;
use App\Models\AiGeneratedContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AiContentController extends Controller
{
    public function index(Request $request): Response
    {
        $accountId = Auth::user()->account_id;
        $tab = $request->input('tab', 'generate');

        $templates = AiContentTemplate::where('account_id', $accountId)
            ->orWhere('is_system', true)
            ->orderByDesc('usage_count')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'category' => $t->category,
                'description' => $t->description,
                'system_prompt' => $t->system_prompt,
                'user_prompt_template' => $t->user_prompt_template,
                'variables' => $t->variables,
                'is_system' => $t->is_system,
                'usage_count' => $t->usage_count,
            ]);

        $history = AiGeneratedContent::where('account_id', $accountId)
            ->with('template:id,name')
            ->latest()
            ->paginate(15)
            ->through(fn ($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'content_type' => $c->content_type,
                'template_name' => $c->template?->name,
                'generated_content' => $c->generated_content,
                'seo_suggestions' => $c->seo_suggestions,
                'status' => $c->status,
                'created_at' => $c->created_at->format('d/m/Y H:i'),
            ]);

        $stats = [
            'total_generated' => AiGeneratedContent::where('account_id', $accountId)->count(),
            'total_templates' => $templates->count(),
            'published' => AiGeneratedContent::where('account_id', $accountId)->where('status', 'published')->count(),
            'most_used' => $templates->sortByDesc('usage_count')->first()['name'] ?? '—',
        ];

        return Inertia::render('AiContent/Index', [
            'templates' => $templates,
            'history' => $history,
            'stats' => $stats,
            'categories' => AiContentTemplate::getCategories(),
            'currentTab' => $tab,
        ]);
    }

    public function generate(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'template_id' => 'nullable|exists:ai_content_templates,id',
            'title' => 'required|string|max:255',
            'content_type' => 'required|string',
            'input_data' => 'nullable|array',
            'input_data.keyword' => 'nullable|string',
            'input_data.topic' => 'nullable|string',
            'input_data.tone' => 'nullable|string',
            'input_data.length' => 'nullable|string',
            'input_data.language' => 'nullable|string',
        ]);

        $user = Auth::user();

        // Build generated content (simulated - in production, call AI API)
        $inputData = $validated['input_data'] ?? [];
        $keyword = $inputData['keyword'] ?? $validated['title'];
        $tone = $inputData['tone'] ?? 'professional';
        $length = $inputData['length'] ?? 'medium';

        $generatedContent = $this->simulateAiContent($validated['content_type'], $keyword, $tone, $length);
        $seoSuggestions = $this->generateSeoSuggestions($keyword, $validated['title']);

        $content = AiGeneratedContent::create([
            'account_id' => $user->account_id,
            'created_by' => $user->id,
            'template_id' => $validated['template_id'] ?? null,
            'title' => $validated['title'],
            'content_type' => $validated['content_type'],
            'input_data' => $inputData,
            'generated_content' => $generatedContent,
            'seo_suggestions' => $seoSuggestions,
        ]);

        if ($validated['template_id']) {
            AiContentTemplate::where('id', $validated['template_id'])->increment('usage_count');
        }

        return redirect()->back()->with('success', 'Đã tạo nội dung AI!');
    }

    public function storeTemplate(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string',
            'description' => 'nullable|string',
            'system_prompt' => 'required|string',
            'user_prompt_template' => 'required|string',
            'variables' => 'nullable|array',
        ]);

        AiContentTemplate::create([
            'account_id' => Auth::user()->account_id,
            ...$validated,
        ]);

        return redirect()->back()->with('success', 'Đã tạo template!');
    }

    public function destroy(AiGeneratedContent $aiGeneratedContent): \Illuminate\Http\RedirectResponse
    {
        $aiGeneratedContent->delete();
        return redirect()->back()->with('success', 'Đã xóa.');
    }

    private function simulateAiContent(string $type, string $keyword, string $tone, string $length): string
    {
        $lengths = ['short' => 200, 'medium' => 500, 'long' => 1000];
        $words = $lengths[$length] ?? 500;

        return match ($type) {
            'blog' => "# {$keyword}\n\n## Giới thiệu\n\nBài viết toàn diện về **{$keyword}** — phân tích chi tiết xu hướng, lợi ích và cách tối ưu hiệu quả cho doanh nghiệp.\n\n## Tại sao {$keyword} quan trọng?\n\nTrong bối cảnh digital marketing ngày càng cạnh tranh, {$keyword} đóng vai trò quyết định trong việc thu hút và giữ chân khách hàng...\n\n## 5 Chiến lược {$keyword} hiệu quả\n\n### 1. Nghiên cứu và phân tích\nBắt đầu bằng việc hiểu rõ thị trường mục tiêu và đối thủ cạnh tranh.\n\n### 2. Xây dựng nội dung chất lượng\nTạo nội dung có giá trị, giải quyết vấn đề thực tế của khách hàng.\n\n### 3. Tối ưu SEO on-page\nĐảm bảo mỗi trang đều được tối ưu với keyword phù hợp.\n\n### 4. Xây dựng backlinks\nTạo liên kết chất lượng từ các website uy tín.\n\n### 5. Đo lường và cải thiện\nTheo dõi KPIs và liên tục điều chỉnh chiến lược.\n\n## Kết luận\n\n{$keyword} là yếu tố không thể thiếu cho sự tăng trưởng bền vững. Áp dụng những chiến lược trên sẽ giúp doanh nghiệp bạn đạt được kết quả tốt nhất.",
            'social' => "🚀 {$keyword}\n\nBạn đang tìm cách tối ưu {$keyword}?\n\n✅ Tip 1: Bắt đầu từ nghiên cứu\n✅ Tip 2: Tạo nội dung giá trị\n✅ Tip 3: Đo lường kết quả\n\n💡 Chia sẻ cho ai cần!\n\n#marketing #digital #{$keyword}",
            'email' => "Subject: Cách tối ưu {$keyword} cho doanh nghiệp\n\nChào [Tên],\n\nBạn có biết {$keyword} có thể tăng doanh thu lên 200%?\n\nTrong bài viết mới nhất, chúng tôi chia sẻ 5 chiến lược đã được kiểm chứng:\n\n1. Nghiên cứu thị trường\n2. Tối ưu nội dung\n3. Xây dựng quy trình\n4. Automation\n5. Đo lường ROI\n\n👉 Đọc ngay: [link]\n\nTrân trọng,\nBED CRM Team",
            default => "Nội dung AI về {$keyword} với tone {$tone} và độ dài {$length}.",
        };
    }

    private function generateSeoSuggestions(string $keyword, string $title): array
    {
        return [
            'meta_title' => "{$title} | Hướng dẫn chi tiết 2026",
            'meta_description' => "Tìm hiểu về {$keyword} — chiến lược, công cụ và tips tối ưu hiệu quả cho doanh nghiệp. Cập nhật xu hướng mới nhất.",
            'h1_suggestion' => $title,
            'focus_keyword' => $keyword,
            'secondary_keywords' => ["{$keyword} là gì", "cách tối ưu {$keyword}", "{$keyword} 2026"],
            'readability_score' => rand(65, 95),
            'word_count_suggestion' => '1500-2000 từ',
        ];
    }
}
