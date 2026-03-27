<?php

namespace App\Http\Controllers;

use App\Models\AiAgent;
use App\Models\AiAgentConversation;
use App\Models\AiKnowledgeBase;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AiAgentController extends Controller
{
    /**
     * Agent grid — list all agents.
     */
    public function index()
    {
        $accountId = auth()->user()->account_id;

        $agents = AiAgent::where('account_id', $accountId)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(fn ($agent) => [
                'id' => $agent->id,
                'name' => $agent->name,
                'slug' => $agent->slug,
                'type' => $agent->type,
                'type_meta' => AiAgent::TYPES[$agent->type] ?? AiAgent::TYPES['custom'],
                'description' => $agent->description,
                'avatar' => $agent->avatar,
                'is_active' => $agent->is_active,
                'model_config' => $agent->model_config,
                'tools' => $agent->tools ?? [],
                'knowledge_base_ids' => $agent->knowledge_base_ids ?? [],
                'total_conversations' => $agent->total_conversations,
                'total_messages' => $agent->total_messages,
                'avg_satisfaction' => $agent->avg_satisfaction,
                'system_prompt' => $agent->system_prompt,
                'created_at' => $agent->created_at->format('d/m/Y'),
            ]);

        $knowledgeBases = AiKnowledgeBase::where('account_id', $accountId)
            ->where('status', 'ready')
            ->get(['id', 'name', 'type']);

        return Inertia::render('AiAgents/Index', [
            'agents' => $agents,
            'agentTypes' => AiAgent::TYPES,
            'agentTools' => AiAgent::TOOLS,
            'knowledgeBases' => $knowledgeBases,
        ]);
    }

    /**
     * Create a new agent.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:' . implode(',', array_keys(AiAgent::TYPES)),
            'description' => 'nullable|string|max:1000',
            'system_prompt' => 'nullable|string',
            'knowledge_base_ids' => 'nullable|array',
            'tools' => 'nullable|array',
            'model_config' => 'nullable|array',
        ]);

        $validated['account_id'] = auth()->user()->account_id;
        $validated['slug'] = \Str::slug($validated['name']) . '-' . \Str::random(6);
        $validated['created_by'] = auth()->id();
        $validated['is_active'] = true;

        // Default model config if not provided
        if (empty($validated['model_config'])) {
            $validated['model_config'] = [
                'provider' => 'gemini',
                'model' => 'gemini-2.5-flash',
                'temperature' => 0.7,
                'max_tokens' => 4096,
            ];
        }

        AiAgent::create($validated);

        return back()->with('success', 'Đã tạo AI Agent.');
    }

    /**
     * Update agent.
     */
    public function update(Request $request, AiAgent $aiAgent)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'system_prompt' => 'nullable|string',
            'knowledge_base_ids' => 'nullable|array',
            'tools' => 'nullable|array',
            'model_config' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $aiAgent->update($validated);

        return back()->with('success', 'Đã cập nhật Agent.');
    }

    /**
     * Toggle agent active/inactive.
     */
    public function toggle(AiAgent $aiAgent)
    {
        $aiAgent->update(['is_active' => !$aiAgent->is_active]);
        return back()->with('success', $aiAgent->is_active ? 'Agent đã kích hoạt.' : 'Agent đã tạm dừng.');
    }

    /**
     * Agent chat page.
     */
    public function chat(AiAgent $aiAgent)
    {
        $userId = auth()->id();

        // Get conversation history
        $conversations = AiAgentConversation::where('agent_id', $aiAgent->id)
            ->where('user_id', $userId)
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'title' => $c->title ?: 'Chat #' . $c->id,
                'message_count' => $c->message_count,
                'updated_at' => $c->updated_at->diffForHumans(),
            ]);

        // Get or create active conversation
        $activeConversation = AiAgentConversation::where('agent_id', $aiAgent->id)
            ->where('user_id', $userId)
            ->latest()
            ->first();

        $kbs = $aiAgent->knowledge_base_ids
            ? AiKnowledgeBase::whereIn('id', $aiAgent->knowledge_base_ids)->pluck('name')
            : collect();

        return Inertia::render('AiAgents/Chat', [
            'agent' => [
                'id' => $aiAgent->id,
                'name' => $aiAgent->name,
                'slug' => $aiAgent->slug,
                'type' => $aiAgent->type,
                'type_meta' => AiAgent::TYPES[$aiAgent->type] ?? AiAgent::TYPES['custom'],
                'description' => $aiAgent->description,
                'avatar' => $aiAgent->avatar,
                'system_prompt' => $aiAgent->system_prompt,
                'model_config' => $aiAgent->model_config,
                'tools' => $aiAgent->tools ?? [],
                'knowledge_bases' => $kbs,
            ],
            'conversations' => $conversations,
            'activeConversation' => $activeConversation ? [
                'id' => $activeConversation->id,
                'title' => $activeConversation->title,
                'messages' => $activeConversation->messages ?? [],
            ] : null,
        ]);
    }

    /**
     * Send message to agent (simulated response).
     */
    public function sendMessage(Request $request, AiAgent $aiAgent)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'conversation_id' => 'nullable|exists:ai_agent_conversations,id',
        ]);

        $userId = auth()->id();

        // Get or create conversation
        if ($validated['conversation_id']) {
            $conversation = AiAgentConversation::findOrFail($validated['conversation_id']);
        } else {
            $conversation = AiAgentConversation::create([
                'agent_id' => $aiAgent->id,
                'user_id' => $userId,
                'title' => \Str::limit($validated['message'], 80),
                'messages' => [],
                'message_count' => 0,
                'tokens_used' => 0,
            ]);
        }

        // Add user message
        $conversation->addMessage('user', $validated['message']);

        // Generate simulated AI response
        $response = $this->generateSimulatedResponse($aiAgent, $validated['message']);
        $conversation->addMessage('assistant', $response['content'], $response['sources']);

        // Update stats
        $tokensUsed = rand(200, 800);
        $conversation->update(['tokens_used' => $conversation->tokens_used + $tokensUsed]);
        $aiAgent->increment('total_messages', 2);
        if ($conversation->message_count <= 2) {
            $aiAgent->increment('total_conversations');
        }

        return back()->with('success', 'OK');
    }

    /**
     * Rate a conversation.
     */
    public function rateConversation(Request $request, AiAgentConversation $conversation)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:500',
        ]);

        $conversation->update([
            'satisfaction_rating' => $validated['rating'],
            'feedback' => $validated['feedback'] ?? null,
        ]);

        // Update agent avg satisfaction
        $agent = $conversation->agent;
        $avg = AiAgentConversation::where('agent_id', $agent->id)
            ->whereNotNull('satisfaction_rating')
            ->avg('satisfaction_rating');
        $agent->update(['avg_satisfaction' => round($avg, 2)]);

        return back()->with('success', 'Cảm ơn đánh giá.');
    }

    /**
     * Delete agent.
     */
    public function destroy(AiAgent $aiAgent)
    {
        $aiAgent->delete();
        return back()->with('success', 'Đã xóa Agent.');
    }

    /**
     * Simulate AI response based on agent type.
     */
    private function generateSimulatedResponse(AiAgent $agent, string $message): array
    {
        $responses = [
            'sales' => [
                'content' => "Dựa trên phân tích dữ liệu CRM, tôi có một số nhận xét:\n\n" .
                    "📊 **Phân tích**: Câu hỏi của bạn liên quan đến chiến lược sales. " .
                    "Dựa trên dữ liệu pipeline hiện tại, tôi thấy:\n\n" .
                    "1. **Conversion rate** từ Qualified → Proposal đang ở 45% (trung bình ngành 35-40%)\n" .
                    "2. **Deal velocity** trung bình 18 ngày — có thể cải thiện bằng follow-up nhanh hơn\n" .
                    "3. **Win rate** tháng này 62% — cao hơn tháng trước (55%)\n\n" .
                    "💡 **Đề xuất**: Tập trung vào deals có giá trị > 50M đang ở stage Negotiation. " .
                    "Gửi case study thành công của khách hàng cùng ngành để tăng confidence.",
                'sources' => ['Sales Playbook', 'Pipeline Analytics Q1/2026', 'Win/Loss Report'],
            ],
            'support' => [
                'content' => "Tôi đã tìm kiếm trong Knowledge Base hỗ trợ và tìm thấy thông tin liên quan:\n\n" .
                    "📋 **Giải pháp**:\n\n" .
                    "Vấn đề bạn mô tả thường gặp và có thể giải quyết bằng:\n\n" .
                    "1. **Kiểm tra**: Đảm bảo phiên bản CRM đã cập nhật mới nhất\n" .
                    "2. **Thực hiện**: Vào Settings → Cache → Clear all cache\n" .
                    "3. **Xác nhận**: Refresh browser (Ctrl+Shift+R) và thử lại\n\n" .
                    "Nếu vẫn gặp lỗi, vui lòng cung cấp screenshot để tôi phân tích thêm.\n\n" .
                    "⏱ SLA: Ticket sẽ được xử lý trong 4 giờ làm việc.",
                'sources' => ['FAQ Knowledge Base', 'Troubleshooting Guide v2.1', 'SLA Policy'],
            ],
            'content' => [
                'content' => "Tôi đã phân tích yêu cầu nội dung của bạn. Đây là đề xuất:\n\n" .
                    "✍️ **Outline bài viết**:\n\n" .
                    "**Tiêu đề**: {keyword chính} — Hướng dẫn Toàn diện {năm}\n\n" .
                    "1. **Giới thiệu** (100 từ) — Hook + pain point\n" .
                    "2. **{Topic 1}** (200 từ) — Giải thích concept cốt lõi\n" .
                    "3. **{Topic 2}** (200 từ) — Hướng dẫn step-by-step\n" .
                    "4. **{Topic 3}** (200 từ) — Ví dụ thực tế / case study\n" .
                    "5. **Kết luận** (100 từ) — Summary + CTA\n\n" .
                    "📊 **SEO Suggestions**: Focus keyword density 1.5-2%, add internal links, schema FAQ markup.\n\n" .
                    "Bạn muốn tôi viết full article không?",
                'sources' => ['SEO Keyword Research', 'Content Calendar', 'Top 10 SERP Analysis'],
            ],
            'analytics' => [
                'content' => "📊 **Phân tích dữ liệu**:\n\n" .
                    "Dựa trên dữ liệu CRM 30 ngày gần nhất:\n\n" .
                    "| Metric | Giá trị | Trend |\n" .
                    "|--------|---------|-------|\n" .
                    "| Revenue | 2.4 tỷ VNĐ | ↑ 12% |\n" .
                    "| New Leads | 156 | ↑ 8% |\n" .
                    "| Win Rate | 58% | ↓ 3% |\n" .
                    "| Avg Deal Size | 180M | ↑ 15% |\n\n" .
                    "🔍 **Insights**:\n" .
                    "- Win rate giảm do deals lớn hơn → cycle dài hơn\n" .
                    "- Leads từ organic search tăng mạnh (+25%)\n" .
                    "- Cần focus vào deals bị stuck >14 ngày (23 deals)\n\n" .
                    "📈 **Forecast Q2**: Ước tính revenue 7.8 tỷ (mục tiêu 8 tỷ, khả thi 97.5%)",
                'sources' => ['Revenue Dashboard', 'Lead Analytics', 'Pipeline Report'],
            ],
            'hr' => [
                'content' => "Tôi đã tra cứu thông tin trong hệ thống HR:\n\n" .
                    "📋 **Thông tin**:\n\n" .
                    "Theo chính sách công ty hiện hành:\n\n" .
                    "1. **Nghỉ phép**: 12 ngày/năm (tích lũy theo thâm niên, +1 ngày/năm, tối đa 18 ngày)\n" .
                    "2. **Quy trình xin nghỉ**: Tạo request trong HRM → Manager approve → HR confirm\n" .
                    "3. **Remote work**: Áp dụng hybrid (3 ngày office, 2 ngày remote/tuần)\n" .
                    "4. **Training budget**: 5M/người/năm cho khóa học chuyên môn\n\n" .
                    "Cần hỗ trợ thêm về chính sách nào khác không?",
                'sources' => ['HR Policy Handbook 2026', 'Employee Guidelines', 'Company Culture Doc'],
            ],
            'custom' => [
                'content' => "Tôi đã xử lý yêu cầu của bạn.\n\n" .
                    "Dựa trên knowledge base đã được training, đây là câu trả lời:\n\n" .
                    "\"" . $message . "\" — Đây là câu hỏi thú vị. " .
                    "Tôi cần thêm context để đưa ra câu trả lời chính xác hơn.\n\n" .
                    "Bạn có thể cung cấp thêm thông tin:\n" .
                    "- Bối cảnh cụ thể?\n" .
                    "- Mục tiêu bạn muốn đạt được?\n" .
                    "- Deadline hoặc ràng buộc nào?",
                'sources' => ['General Knowledge Base'],
            ],
        ];

        return $responses[$agent->type] ?? $responses['custom'];
    }
}
