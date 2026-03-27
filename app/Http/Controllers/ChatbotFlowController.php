<?php

namespace App\Http\Controllers;

use App\Models\ChatbotFlow;
use App\Models\ChatbotMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ChatbotFlowController extends Controller
{
    public function index(Request $request): Response
    {
        $accountId = Auth::user()->account_id;

        $flows = ChatbotFlow::where('account_id', $accountId)
            ->latest()
            ->paginate(15)
            ->through(fn ($f) => [
                'id' => $f->id,
                'name' => $f->name,
                'description' => $f->description,
                'trigger_type' => $f->trigger_type,
                'status' => $f->status,
                'nodes_count' => count($f->nodes ?? []),
                'conversations_count' => $f->conversations_count,
                'leads_captured' => $f->leads_captured,
                'conversion_rate' => $f->conversion_rate,
                'updated_at' => $f->updated_at->format('d/m/Y'),
            ]);

        $stats = [
            'total_flows' => ChatbotFlow::where('account_id', $accountId)->count(),
            'active_flows' => ChatbotFlow::where('account_id', $accountId)->where('status', 'active')->count(),
            'total_conversations' => ChatbotFlow::where('account_id', $accountId)->sum('conversations_count'),
            'total_leads' => ChatbotFlow::where('account_id', $accountId)->sum('leads_captured'),
        ];

        return Inertia::render('ChatbotFlows/Index', [
            'flows' => $flows,
            'stats' => $stats,
            'nodeTypes' => ChatbotFlow::getNodeTypes(),
            'triggerTypes' => ChatbotFlow::getTriggerTypes(),
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'nullable|string',
            'trigger_value' => 'nullable|string',
            'nodes' => 'nullable|array',
            'edges' => 'nullable|array',
            'settings' => 'nullable|array',
        ]);

        $user = Auth::user();
        ChatbotFlow::create([
            'account_id' => $user->account_id,
            'created_by' => $user->id,
            'nodes' => $validated['nodes'] ?? $this->defaultNodes(),
            'edges' => $validated['edges'] ?? $this->defaultEdges(),
            ...$validated,
        ]);

        return redirect()->back()->with('success', 'Đã tạo chatbot flow!');
    }

    public function update(Request $request, ChatbotFlow $chatbotFlow): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'nullable|string',
            'trigger_value' => 'nullable|string',
            'nodes' => 'nullable|array',
            'edges' => 'nullable|array',
            'settings' => 'nullable|array',
            'status' => 'nullable|in:draft,active,paused',
        ]);

        $chatbotFlow->update($validated);
        return redirect()->back()->with('success', 'Đã lưu!');
    }

    public function destroy(ChatbotFlow $chatbotFlow): \Illuminate\Http\RedirectResponse
    {
        $chatbotFlow->delete();
        return redirect()->back()->with('success', 'Đã xóa.');
    }

    public function toggleStatus(ChatbotFlow $chatbotFlow): \Illuminate\Http\RedirectResponse
    {
        $chatbotFlow->update([
            'status' => $chatbotFlow->status === 'active' ? 'paused' : 'active',
        ]);
        return redirect()->back()->with('success', 'Đã cập nhật.');
    }

    private function defaultNodes(): array
    {
        return [
            ['id' => 'start', 'type' => 'message', 'data' => ['message' => 'Xin chào! 👋 Tôi có thể giúp gì cho bạn?'], 'position' => ['x' => 250, 'y' => 50]],
            ['id' => 'options1', 'type' => 'options', 'data' => ['message' => 'Bạn cần hỗ trợ gì?', 'options' => ['Tư vấn dịch vụ', 'Báo giá', 'Hỗ trợ kỹ thuật']], 'position' => ['x' => 250, 'y' => 200]],
            ['id' => 'collect', 'type' => 'collect_info', 'data' => ['fields' => ['name', 'email', 'phone'], 'message' => 'Để tôi tư vấn tốt hơn, bạn vui lòng cho biết:'], 'position' => ['x' => 250, 'y' => 400]],
            ['id' => 'end', 'type' => 'end', 'data' => ['message' => 'Cảm ơn bạn! Chúng tôi sẽ liên hệ sớm nhất! 🙏'], 'position' => ['x' => 250, 'y' => 600]],
        ];
    }

    private function defaultEdges(): array
    {
        return [
            ['id' => 'e1', 'source' => 'start', 'target' => 'options1'],
            ['id' => 'e2', 'source' => 'options1', 'target' => 'collect'],
            ['id' => 'e3', 'source' => 'collect', 'target' => 'end'],
        ];
    }
}
