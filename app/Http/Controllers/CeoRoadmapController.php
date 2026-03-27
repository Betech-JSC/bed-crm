<?php

namespace App\Http\Controllers;

use App\Models\CeoRoadmapPhase;
use App\Models\CeoRoadmapMilestone;
use App\Models\CeoRoadmapTest;
use App\Models\CeoRoadmapTestAttempt;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CeoRoadmapController extends Controller
{
    public function index(Request $request)
    {
        $accountId = auth()->user()->account_id;
        $userId = auth()->id();

        $phases = CeoRoadmapPhase::where('account_id', $accountId)
            ->with(['milestones.tests.attempts' => fn($q) => $q->where('user_id', $userId)])
            ->orderBy('sort_order')
            ->get()
            ->map(function ($phase) {
                $milestones = $phase->milestones->map(function ($ms) {
                    $tests = $ms->tests->map(function ($test) {
                        $bestAttempt = $test->attempts->sortByDesc('score')->first();
                        return [
                            'id' => $test->id,
                            'title' => $test->title,
                            'description' => $test->description,
                            'question_count' => is_array($test->questions) ? count($test->questions) : 0,
                            'questions' => $test->questions,
                            'passing_score' => $test->passing_score,
                            'time_limit_minutes' => $test->time_limit_minutes,
                            'best_score' => $bestAttempt?->score,
                            'passed' => $bestAttempt?->passed ?? false,
                            'attempts_count' => $test->attempts->count(),
                        ];
                    });

                    $allPassed = $tests->count() > 0 && $tests->every(fn($t) => $t['passed']);

                    return [
                        'id' => $ms->id,
                        'phase_id' => $ms->phase_id,
                        'title' => $ms->title,
                        'description' => $ms->description,
                        'skills' => $ms->skills ?? [],
                        'resources' => $ms->resources ?? [],
                        'sort_order' => $ms->sort_order,
                        'tests' => $tests->values(),
                        'completed' => $allPassed,
                    ];
                });

                $completedCount = $milestones->where('completed', true)->count();

                return [
                    'id' => $phase->id,
                    'title' => $phase->title,
                    'description' => $phase->description,
                    'icon' => $phase->icon,
                    'color' => $phase->color,
                    'sort_order' => $phase->sort_order,
                    'milestones' => $milestones->values(),
                    'milestone_count' => $milestones->count(),
                    'completed_count' => $completedCount,
                    'progress' => $milestones->count() > 0 ? round($completedCount / $milestones->count() * 100) : 0,
                ];
            });

        $totalMilestones = $phases->sum('milestone_count');
        $completedMilestones = $phases->sum('completed_count');
        $totalTests = $phases->sum(fn($p) => collect($p['milestones'])->sum(fn($m) => $m['tests']->count()));
        $passedTests = $phases->sum(fn($p) => collect($p['milestones'])->sum(fn($m) => $m['tests']->where('passed', true)->count()));

        return Inertia::render('CeoRoadmap/Index', [
            'phases' => $phases,
            'stats' => [
                'total_phases' => $phases->count(),
                'total_milestones' => $totalMilestones,
                'completed_milestones' => $completedMilestones,
                'total_tests' => $totalTests,
                'passed_tests' => $passedTests,
                'overall_progress' => $totalMilestones > 0 ? round($completedMilestones / $totalMilestones * 100) : 0,
            ],
        ]);
    }

    // ── Phase CRUD ──

    public function storePhase(Request $request)
    {
        $v = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:30',
            'sort_order' => 'nullable|integer',
        ]);
        $v['account_id'] = auth()->user()->account_id;
        CeoRoadmapPhase::create($v);
        return back()->with('success', 'Đã tạo giai đoạn mới.');
    }

    public function updatePhase(Request $request, CeoRoadmapPhase $phase)
    {
        $v = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:30',
            'sort_order' => 'nullable|integer',
        ]);
        $phase->update($v);
        return back()->with('success', 'Đã cập nhật giai đoạn.');
    }

    public function deletePhase(CeoRoadmapPhase $phase)
    {
        $phase->delete();
        return back()->with('success', 'Đã xóa giai đoạn.');
    }

    // ── Milestone CRUD ──

    public function storeMilestone(Request $request)
    {
        $v = $request->validate([
            'phase_id' => 'required|exists:ceo_roadmap_phases,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'skills' => 'nullable|array',
            'resources' => 'nullable|array',
            'sort_order' => 'nullable|integer',
        ]);
        CeoRoadmapMilestone::create($v);
        return back()->with('success', 'Đã tạo cột mốc mới.');
    }

    public function updateMilestone(Request $request, CeoRoadmapMilestone $milestone)
    {
        $v = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'skills' => 'nullable|array',
            'resources' => 'nullable|array',
            'sort_order' => 'nullable|integer',
        ]);
        $milestone->update($v);
        return back()->with('success', 'Đã cập nhật cột mốc.');
    }

    public function deleteMilestone(CeoRoadmapMilestone $milestone)
    {
        $milestone->delete();
        return back()->with('success', 'Đã xóa cột mốc.');
    }

    // ── Test CRUD ──

    public function storeTest(Request $request)
    {
        $v = $request->validate([
            'milestone_id' => 'required|exists:ceo_roadmap_milestones,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'questions' => 'required|array|min:1',
            'questions.*.q' => 'required|string',
            'questions.*.options' => 'required|array|min:2',
            'questions.*.correct' => 'required|integer|min:0',
            'passing_score' => 'nullable|integer|min:0|max:100',
            'time_limit_minutes' => 'nullable|integer|min:1',
        ]);
        CeoRoadmapTest::create($v);
        return back()->with('success', 'Đã tạo bài test.');
    }

    public function updateTest(Request $request, CeoRoadmapTest $test)
    {
        $v = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'questions' => 'required|array|min:1',
            'questions.*.q' => 'required|string',
            'questions.*.options' => 'required|array|min:2',
            'questions.*.correct' => 'required|integer|min:0',
            'passing_score' => 'nullable|integer|min:0|max:100',
            'time_limit_minutes' => 'nullable|integer|min:1',
        ]);
        $test->update($v);
        return back()->with('success', 'Đã cập nhật bài test.');
    }

    public function deleteTest(CeoRoadmapTest $test)
    {
        $test->delete();
        return back()->with('success', 'Đã xóa bài test.');
    }

    // ── Test Taking ──

    public function submitTest(Request $request, CeoRoadmapTest $test)
    {
        $request->validate(['answers' => 'required|array']);

        $questions = $test->questions;
        $answers = $request->answers;
        $correct = 0;
        $total = count($questions);

        foreach ($questions as $i => $q) {
            if (isset($answers[$i]) && (int) $answers[$i] === (int) $q['correct']) {
                $correct++;
            }
        }

        $score = $total > 0 ? round($correct / $total * 100) : 0;
        $passed = $score >= $test->passing_score;

        CeoRoadmapTestAttempt::create([
            'test_id' => $test->id,
            'user_id' => auth()->id(),
            'answers' => $answers,
            'score' => $score,
            'passed' => $passed,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        return back()->with($passed ? 'success' : 'warning',
            $passed ? "Chúc mừng! Bạn đạt {$score}% — Đậu!" : "Bạn đạt {$score}% — Cần ≥{$test->passing_score}% để đậu."
        );
    }

    // ── Seed Default Data ──

    public function seedDefaults(Request $request)
    {
        $accountId = auth()->user()->account_id;
        if (CeoRoadmapPhase::where('account_id', $accountId)->exists()) {
            return back()->with('info', 'Dữ liệu mẫu đã tồn tại.');
        }

        $phasesData = [
            ['title' => 'Tự quản lý bản thân', 'description' => 'Nền tảng lãnh đạo bắt đầu từ việc quản lý chính mình', 'icon' => 'pi pi-user', 'color' => '#6366f1', 'milestones' => [
                ['title' => 'Quản lý thời gian', 'description' => 'Tối ưu hóa năng suất cá nhân', 'skills' => ['Prioritization','Time-blocking','Deep work']],
                ['title' => 'Ra quyết định', 'description' => 'Phát triển khả năng ra quyết định nhanh và chính xác', 'skills' => ['Data-driven','Risk assessment','Decisiveness']],
                ['title' => 'Trí tuệ cảm xúc', 'description' => 'Kiểm soát cảm xúc và thấu hiểu người khác', 'skills' => ['Self-awareness','Empathy','Emotional regulation']],
                ['title' => 'Thương hiệu cá nhân', 'description' => 'Xây dựng uy tín và ảnh hưởng', 'skills' => ['Public speaking','Networking','Thought leadership']],
            ]],
            ['title' => 'Xây dựng đội nhóm', 'description' => 'Tuyển dụng, phát triển và giữ chân người tài', 'icon' => 'pi pi-users', 'color' => '#f59e0b', 'milestones' => [
                ['title' => 'Tuyển dụng A-Players', 'description' => 'Thu hút và chọn nhân tài hàng đầu', 'skills' => ['Interviewing','Culture fit','Employer branding']],
                ['title' => 'Ủy quyền hiệu quả', 'description' => 'Giao việc đúng người, đúng cách', 'skills' => ['Delegation','Trust building','Accountability']],
                ['title' => 'Văn hóa doanh nghiệp', 'description' => 'Thiết kế văn hóa thúc đẩy hiệu suất', 'skills' => ['Values design','Culture building','Team rituals']],
            ]],
            ['title' => 'Tư duy chiến lược', 'description' => 'Nhìn xa trông rộng và lập kế hoạch dài hạn', 'icon' => 'pi pi-compass', 'color' => '#3b82f6', 'milestones' => [
                ['title' => 'Phân tích thị trường', 'description' => 'Hiểu thị trường, đối thủ và cơ hội', 'skills' => ['Market research','Competitive analysis','Trend spotting']],
                ['title' => 'Lập kế hoạch chiến lược', 'description' => 'Xây dựng chiến lược 3-5 năm', 'skills' => ['Vision setting','OKR framework','Strategic planning']],
                ['title' => 'Đổi mới sáng tạo', 'description' => 'Thúc đẩy đổi mới liên tục', 'skills' => ['Design thinking','Innovation culture','Disruption mindset']],
            ]],
            ['title' => 'Quản trị tài chính', 'description' => 'Nắm vững tài chính doanh nghiệp', 'icon' => 'pi pi-chart-line', 'color' => '#10b981', 'milestones' => [
                ['title' => 'Đọc báo cáo tài chính', 'description' => 'Hiểu P&L, Balance Sheet, Cash Flow', 'skills' => ['Financial literacy','P&L analysis','Cash flow management']],
                ['title' => 'Gọi vốn & Đầu tư', 'description' => 'Chiến lược huy động vốn và phân bổ nguồn lực', 'skills' => ['Fundraising','Investor relations','Capital allocation']],
                ['title' => 'Quản lý rủi ro', 'description' => 'Phát hiện và giảm thiểu rủi ro tài chính', 'skills' => ['Risk management','Scenario planning','Crisis management']],
            ]],
            ['title' => 'Lãnh đạo tổ chức', 'description' => 'Mở rộng và quản lý tổ chức hiệu quả', 'icon' => 'pi pi-sitemap', 'color' => '#8b5cf6', 'milestones' => [
                ['title' => 'Thiết kế tổ chức', 'description' => 'Cấu trúc tổ chức tối ưu cho tăng trưởng', 'skills' => ['Org design','Scaling','Process optimization']],
                ['title' => 'Quản lý thay đổi', 'description' => 'Dẫn dắt tổ chức qua các giai đoạn chuyển đổi', 'skills' => ['Change management','Stakeholder alignment','Communication']],
                ['title' => 'Xây dựng đội ngũ lãnh đạo', 'description' => 'Phát triển thế hệ lãnh đạo tiếp theo', 'skills' => ['Coaching','Mentoring','Succession planning']],
            ]],
            ['title' => 'Tầm nhìn CEO', 'description' => 'Trở thành CEO tầm cỡ với ảnh hưởng rộng lớn', 'icon' => 'pi pi-star', 'color' => '#ef4444', 'milestones' => [
                ['title' => 'Tư duy toàn cầu', 'description' => 'Mở rộng tầm nhìn ra quốc tế', 'skills' => ['Global mindset','Cross-cultural leadership','International expansion']],
                ['title' => 'Trách nhiệm xã hội', 'description' => 'CEO có trách nhiệm với cộng đồng', 'skills' => ['CSR','ESG','Sustainable business']],
                ['title' => 'Di sản lãnh đạo', 'description' => 'Xây dựng tổ chức vượt thời gian', 'skills' => ['Legacy building','Board governance','Industry leadership']],
            ]],
        ];

        $sampleQuestions = [
            [
                ['q' => 'Phương pháp quản lý thời gian nào hiệu quả nhất cho CEO?', 'options' => ['Làm mọi thứ cùng lúc', 'Time-blocking và Deep work', 'Chỉ làm việc khi có cảm hứng', 'Ủy quyền tất cả'], 'correct' => 1, 'explanation' => 'Time-blocking giúp CEO tập trung vào công việc quan trọng nhất.'],
                ['q' => 'Kỹ năng nào quan trọng nhất khi ra quyết định?', 'options' => ['Luôn theo trực giác', 'Phân tích dữ liệu kết hợp kinh nghiệm', 'Hỏi ý kiến tất cả mọi người', 'Trì hoãn quyết định'], 'correct' => 1, 'explanation' => 'CEO giỏi kết hợp dữ liệu và kinh nghiệm để ra quyết định.'],
                ['q' => 'Trí tuệ cảm xúc giúp CEO như thế nào?', 'options' => ['Để thao túng người khác', 'Hiểu và quản lý cảm xúc bản thân và đội ngũ', 'Luôn che giấu cảm xúc', 'Không liên quan đến lãnh đạo'], 'correct' => 1, 'explanation' => 'EQ giúp CEO xây dựng mối quan hệ và lãnh đạo hiệu quả.'],
                ['q' => 'Yếu tố nào quan trọng nhất khi xây dựng thương hiệu cá nhân?', 'options' => ['Số lượng follower trên mạng xã hội', 'Sự nhất quán giữa lời nói và hành động', 'Chi nhiều tiền cho quảng cáo', 'Sao chép phong cách người nổi tiếng'], 'correct' => 1, 'explanation' => 'Authenticity là nền tảng của thương hiệu cá nhân bền vững.'],
                ['q' => 'CEO nên dành bao nhiêu % thời gian cho công việc chiến lược?', 'options' => ['10%', '30-40%', '50-60%', '90%'], 'correct' => 2, 'explanation' => 'CEO nên dành 50-60% thời gian cho tư duy chiến lược và xây dựng đội ngũ.'],
            ],
        ];

        foreach ($phasesData as $i => $pd) {
            $milestones = $pd['milestones'];
            unset($pd['milestones']);
            $pd['account_id'] = $accountId;
            $pd['sort_order'] = $i;
            $phase = CeoRoadmapPhase::create($pd);

            foreach ($milestones as $j => $md) {
                $md['phase_id'] = $phase->id;
                $md['sort_order'] = $j;
                $ms = CeoRoadmapMilestone::create($md);

                CeoRoadmapTest::create([
                    'milestone_id' => $ms->id,
                    'title' => "Đánh giá: {$ms->title}",
                    'description' => "Bài test đánh giá năng lực về {$ms->title}",
                    'questions' => $sampleQuestions[0],
                    'passing_score' => 70,
                    'time_limit_minutes' => 10,
                ]);
            }
        }

        return back()->with('success', 'Đã tạo dữ liệu mẫu lộ trình CEO.');
    }
}
