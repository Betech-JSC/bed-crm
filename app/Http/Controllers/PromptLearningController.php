<?php

namespace App\Http\Controllers;

use App\Models\PromptCategory;
use App\Models\PromptLesson;
use App\Models\PromptExercise;
use App\Models\PromptExerciseAttempt;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PromptLearningController extends Controller
{
    public function index(Request $request)
    {
        $accountId = auth()->user()->account_id;
        $userId = auth()->id();

        $categories = PromptCategory::where('account_id', $accountId)
            ->with(['lessons.exercises.attempts' => fn($q) => $q->where('user_id', $userId)])
            ->orderBy('sort_order')->get()
            ->map(function ($cat) {
                $lessons = $cat->lessons->sortBy('sort_order')->values()->map(function ($ls) {
                    $exercises = $ls->exercises->sortBy('sort_order')->values()->map(function ($ex) {
                        $bestAttempt = $ex->attempts->sortByDesc('rating')->first();
                        return [
                            'id' => $ex->id,
                            'title' => $ex->title,
                            'instruction' => $ex->instruction,
                            'sample_prompt' => $ex->sample_prompt,
                            'expected_output' => $ex->expected_output,
                            'difficulty' => $ex->difficulty,
                            'sort_order' => $ex->sort_order,
                            'best_rating' => $bestAttempt?->rating,
                            'completed' => ($bestAttempt?->rating ?? 0) >= 3,
                            'attempts_count' => $ex->attempts->count(),
                            'last_prompt' => $bestAttempt?->user_prompt,
                        ];
                    });

                    return [
                        'id' => $ls->id,
                        'title' => $ls->title,
                        'content' => $ls->content,
                        'examples' => $ls->examples ?? [],
                        'tips' => $ls->tips ?? [],
                        'sort_order' => $ls->sort_order,
                        'exercises' => $exercises,
                        'exercises_count' => $exercises->count(),
                        'completed_count' => $exercises->where('completed', true)->count(),
                    ];
                });

                $totalExercises = $lessons->sum('exercises_count');
                $completedExercises = $lessons->sum('completed_count');

                return [
                    'id' => $cat->id,
                    'title' => $cat->title,
                    'description' => $cat->description,
                    'level' => $cat->level,
                    'icon' => $cat->icon,
                    'color' => $cat->color,
                    'sort_order' => $cat->sort_order,
                    'lessons' => $lessons,
                    'lessons_count' => $lessons->count(),
                    'total_exercises' => $totalExercises,
                    'completed_exercises' => $completedExercises,
                    'progress' => $totalExercises > 0 ? round($completedExercises / $totalExercises * 100) : 0,
                ];
            });

        $totalLessons = $categories->sum('lessons_count');
        $totalExercises = $categories->sum('total_exercises');
        $completedExercises = $categories->sum('completed_exercises');

        return Inertia::render('PromptLearning/Index', [
            'categories' => $categories,
            'stats' => [
                'total_categories' => $categories->count(),
                'total_lessons' => $totalLessons,
                'total_exercises' => $totalExercises,
                'completed_exercises' => $completedExercises,
                'overall_progress' => $totalExercises > 0 ? round($completedExercises / $totalExercises * 100) : 0,
            ],
        ]);
    }

    // ── Category CRUD ──
    public function storeCategory(Request $request)
    {
        $v = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'level' => 'nullable|in:beginner,intermediate,advanced,expert',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:30',
            'sort_order' => 'nullable|integer',
        ]);
        $v['account_id'] = auth()->user()->account_id;
        PromptCategory::create($v);
        return back()->with('success', 'Đã tạo danh mục.');
    }

    public function updateCategory(Request $request, PromptCategory $category)
    {
        $v = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'level' => 'nullable|in:beginner,intermediate,advanced,expert',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:30',
            'sort_order' => 'nullable|integer',
        ]);
        $category->update($v);
        return back()->with('success', 'Đã cập nhật.');
    }

    public function deleteCategory(PromptCategory $category)
    {
        $category->delete();
        return back()->with('success', 'Đã xóa danh mục.');
    }

    // ── Lesson CRUD ──
    public function storeLesson(Request $request)
    {
        $v = $request->validate([
            'category_id' => 'required|exists:prompt_categories,id',
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'examples' => 'nullable|array',
            'tips' => 'nullable|array',
            'sort_order' => 'nullable|integer',
        ]);
        PromptLesson::create($v);
        return back()->with('success', 'Đã tạo bài học.');
    }

    public function updateLesson(Request $request, PromptLesson $lesson)
    {
        $v = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'examples' => 'nullable|array',
            'tips' => 'nullable|array',
            'sort_order' => 'nullable|integer',
        ]);
        $lesson->update($v);
        return back()->with('success', 'Đã cập nhật bài học.');
    }

    public function deleteLesson(PromptLesson $lesson)
    {
        $lesson->delete();
        return back()->with('success', 'Đã xóa bài học.');
    }

    // ── Exercise CRUD ──
    public function storeExercise(Request $request)
    {
        $v = $request->validate([
            'lesson_id' => 'required|exists:prompt_lessons,id',
            'title' => 'required|string|max:255',
            'instruction' => 'nullable|string',
            'sample_prompt' => 'nullable|string',
            'expected_output' => 'nullable|string',
            'difficulty' => 'nullable|in:easy,medium,hard',
            'sort_order' => 'nullable|integer',
        ]);
        PromptExercise::create($v);
        return back()->with('success', 'Đã tạo bài tập.');
    }

    public function updateExercise(Request $request, PromptExercise $exercise)
    {
        $v = $request->validate([
            'title' => 'required|string|max:255',
            'instruction' => 'nullable|string',
            'sample_prompt' => 'nullable|string',
            'expected_output' => 'nullable|string',
            'difficulty' => 'nullable|in:easy,medium,hard',
            'sort_order' => 'nullable|integer',
        ]);
        $exercise->update($v);
        return back()->with('success', 'Đã cập nhật bài tập.');
    }

    public function deleteExercise(PromptExercise $exercise)
    {
        $exercise->delete();
        return back()->with('success', 'Đã xóa bài tập.');
    }

    // ── Submit Exercise ──
    public function submitExercise(Request $request, PromptExercise $exercise)
    {
        $request->validate([
            'user_prompt' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
            'notes' => 'nullable|string',
        ]);

        PromptExerciseAttempt::create([
            'exercise_id' => $exercise->id,
            'user_id' => auth()->id(),
            'user_prompt' => $request->user_prompt,
            'rating' => $request->rating,
            'notes' => $request->notes,
        ]);

        return back()->with('success', 'Đã lưu bài tập! Đánh giá: ' . $request->rating . '/5 ⭐');
    }

    // ── Seed Defaults ──
    public function seedDefaults()
    {
        $accountId = auth()->user()->account_id;
        if (PromptCategory::where('account_id', $accountId)->exists()) {
            return back()->with('info', 'Dữ liệu mẫu đã tồn tại.');
        }

        $data = [
            [
                'title' => 'Cơ bản — Nền tảng Prompt', 'level' => 'beginner', 'icon' => 'pi pi-book', 'color' => '#10b981',
                'description' => 'Hiểu cấu trúc prompt, nguyên tắc cơ bản và cách giao tiếp hiệu quả với AI',
                'lessons' => [
                    [
                        'title' => 'Prompt là gì?', 'content' => 'Prompt là lệnh/câu hỏi bạn gửi cho AI. Chất lượng output phụ thuộc hoàn toàn vào chất lượng prompt. Một prompt tốt cần: rõ ràng, cụ thể, có ngữ cảnh.',
                        'examples' => [
                            ['label' => 'Prompt kém', 'prompt' => 'Viết bài về marketing', 'note' => 'Quá chung chung, AI không biết viết cho ai, về gì'],
                            ['label' => 'Prompt tốt', 'prompt' => 'Viết bài blog 500 từ về 5 xu hướng digital marketing 2026 cho startup SaaS B2B, giọng văn chuyên nghiệp nhưng dễ hiểu', 'note' => 'Cụ thể: độ dài, chủ đề, đối tượng, giọng văn'],
                        ],
                        'tips' => ['Luôn xác định rõ OUTPUT mong muốn', 'Cho AI biết bạn là ai và viết cho ai', 'Càng cụ thể càng tốt'],
                        'exercises' => [
                            ['title' => 'Cải thiện prompt', 'instruction' => 'Biến prompt kém "Viết email" thành prompt tốt, bao gồm: mục đích, người nhận, giọng văn, độ dài', 'difficulty' => 'easy', 'sample_prompt' => 'Viết email chuyên nghiệp 200 từ gửi khách hàng B2B giới thiệu dịch vụ CRM mới, nhấn mạnh tiết kiệm 30% thời gian, kèm CTA đặt lịch demo', 'expected_output' => 'Email có mở đầu chuyên nghiệp, nội dung giá trị rõ ràng, CTA cụ thể'],
                        ],
                    ],
                    [
                        'title' => 'Cấu trúc prompt cơ bản', 'content' => 'Công thức R-T-F: Role (Vai trò) + Task (Nhiệm vụ) + Format (Định dạng). Đây là nền tảng của mọi prompt hiệu quả.',
                        'examples' => [
                            ['label' => 'RTF Pattern', 'prompt' => 'Bạn là chuyên gia SEO (Role). Phân tích website example.com và đề xuất 10 cải thiện (Task). Trình bày dạng bảng với cột: Vấn đề | Mức độ | Giải pháp (Format)', 'note' => 'Rõ ràng vai trò, nhiệm vụ, và format output'],
                        ],
                        'tips' => ['Role giúp AI "nhập vai" chuyên gia phù hợp', 'Task phải có mục tiêu đo lường được', 'Format giúp output dễ sử dụng ngay'],
                        'exercises' => [
                            ['title' => 'Áp dụng RTF', 'instruction' => 'Viết prompt theo công thức R-T-F để yêu cầu AI tạo kế hoạch content marketing 1 tháng', 'difficulty' => 'easy', 'sample_prompt' => 'Bạn là Content Marketing Manager 10 năm kinh nghiệm. Lập kế hoạch content marketing 30 ngày cho startup fintech, target: CEO SME. Format: Bảng với cột Ngày | Kênh | Chủ đề | Loại content | KPI'],
                        ],
                    ],
                    [
                        'title' => 'Cho ngữ cảnh (Context)', 'content' => 'Context = background info giúp AI hiểu tình huống. Thiếu context → output generic. Context tốt gồm: ngành, đối tượng, mục tiêu, ràng buộc.',
                        'examples' => [
                            ['label' => 'Thêm context', 'prompt' => 'Context: Công ty BED CRM, startup SaaS 2 năm, 500 users, target SME Việt Nam. Đang chuẩn bị gọi vốn Series A.\n\nViết pitch deck outline 10 slides, highlight traction và market opportunity Đông Nam Á.', 'note' => 'Context cung cấp info quan trọng để AI customize output'],
                        ],
                        'tips' => ['Cung cấp số liệu cụ thể khi có', 'Nêu constraints: budget, timeline, resources', 'Cho biết output sẽ dùng cho mục đích gì'],
                        'exercises' => [
                            ['title' => 'Context crafting', 'instruction' => 'Viết prompt có context đầy đủ để AI tạo job description cho vị trí Full-stack Developer', 'difficulty' => 'easy'],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Trung cấp — Kỹ thuật nâng cao', 'level' => 'intermediate', 'icon' => 'pi pi-cog', 'color' => '#3b82f6',
                'description' => 'Few-shot learning, chain-of-thought, output control, và kỹ thuật prompt engineering chuyên sâu',
                'lessons' => [
                    [
                        'title' => 'Few-shot Prompting', 'content' => 'Cho AI 2-3 ví dụ mẫu để AI hiểu pattern bạn muốn. Đây là kỹ thuật mạnh nhất để control output quality và style.',
                        'examples' => [
                            ['label' => 'Few-shot example', 'prompt' => "Viết tagline cho sản phẩm theo style sau:\n\nSản phẩm: Nike → Tagline: Just Do It\nSản phẩm: Apple → Tagline: Think Different\nSản phẩm: BED CRM → Tagline:", 'note' => 'AI hiểu pattern ngắn gọn, mạnh mẽ từ ví dụ'],
                        ],
                        'tips' => ['2-3 examples là đủ, quá nhiều gây confusion', 'Examples phải consistent về style', 'Đặt examples gần nhau để AI thấy pattern'],
                        'exercises' => [
                            ['title' => 'Few-shot practice', 'instruction' => 'Dùng kỹ thuật few-shot để AI viết mô tả sản phẩm theo style nhất quán cho 3 sản phẩm khác nhau', 'difficulty' => 'medium'],
                        ],
                    ],
                    [
                        'title' => 'Chain-of-Thought (CoT)', 'content' => 'Yêu cầu AI "suy nghĩ từng bước" trước khi đưa ra kết luận. Rất hiệu quả cho bài toán phức tạp cần logic, phân tích, hoặc ra quyết định.',
                        'examples' => [
                            ['label' => 'CoT Prompting', 'prompt' => 'Phân tích xem công ty nên mở rộng sang thị trường Thái Lan hay Indonesia.\n\nHãy suy nghĩ từng bước:\n1. So sánh quy mô thị trường\n2. Phân tích đối thủ cạnh tranh\n3. Đánh giá rào cản gia nhập\n4. Xem xét cultural fit\n5. Đưa ra khuyến nghị với lý do', 'note' => 'Chia nhỏ vấn đề → AI suy luận tốt hơn'],
                        ],
                        'tips' => ['Dùng "Hãy suy nghĩ từng bước" hoặc "Let\'s think step by step"', 'Liệt kê các bước cụ thể nếu biết', 'CoT đặc biệt tốt cho math, logic, analysis'],
                        'exercises' => [
                            ['title' => 'CoT Analysis', 'instruction' => 'Dùng CoT prompting để phân tích nên chọn tech stack nào cho dự án mobile app mới', 'difficulty' => 'medium'],
                        ],
                    ],
                    [
                        'title' => 'Kiểm soát Output', 'content' => 'Kỹ thuật kiểm soát chính xác format, tone, length, và structure của output. Bao gồm: delimiters, output schema, negative prompting.',
                        'examples' => [
                            ['label' => 'Output Schema', 'prompt' => "Phân tích competitor và trả về kết quả CHÍNH XÁC theo JSON format sau:\n```json\n{\n  \"company\": \"tên\",\n  \"strengths\": [\"...\"],\n  \"weaknesses\": [\"...\"],\n  \"threat_level\": \"low|medium|high\",\n  \"key_differentiator\": \"...\"\n}\n```\nKHÔNG thêm text ngoài JSON.", 'note' => 'Schema + negative prompt = output chính xác'],
                        ],
                        'tips' => ['Dùng markdown format: bảng, bullet, heading', 'Negative prompt: "KHÔNG làm X, KHÔNG bao gồm Y"', 'Chỉ định language output nếu cần'],
                        'exercises' => [
                            ['title' => 'Output control', 'instruction' => 'Viết prompt yêu cầu AI tạo SWOT analysis với output là structured markdown table', 'difficulty' => 'medium'],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Nâng cao — Prompt Chuyên gia', 'level' => 'advanced', 'icon' => 'pi pi-star', 'color' => '#f59e0b',
                'description' => 'System prompts, multi-turn strategies, persona design, và advanced prompt patterns',
                'lessons' => [
                    [
                        'title' => 'System Prompt Design', 'content' => 'System prompt định nghĩa "nhân cách" và rules cho AI. Đây là nền tảng cho mọi AI assistant, chatbot, và automation.',
                        'examples' => [
                            ['label' => 'System Prompt', 'prompt' => "System: Bạn là Sales Coach AI của công ty BED CRM.\n\nRules:\n- Luôn trả lời bằng tiếng Việt\n- Focus vào B2B SaaS sales\n- Sử dụng MEDDPICC framework\n- Khi không chắc chắn, hỏi lại thay vì đoán\n- Mỗi câu trả lời kèm 1 actionable tip\n\nTone: Professional, supportive, data-driven", 'note' => 'System prompt = personality + rules + constraints'],
                        ],
                        'tips' => ['Định nghĩa rõ DO và DON\'T', 'Cho examples về tone/style mong muốn', 'Test system prompt với edge cases'],
                        'exercises' => [
                            ['title' => 'System Prompt', 'instruction' => 'Thiết kế system prompt cho AI Customer Support Agent của công ty SaaS', 'difficulty' => 'hard', 'sample_prompt' => 'System: Bạn là Support Agent AI...\nRules: ...\nPersonality: ...\nEscalation: ...'],
                        ],
                    ],
                    [
                        'title' => 'Meta-Prompting', 'content' => 'Yêu cầu AI tự viết prompt tốt hơn, hoặc đánh giá và cải thiện prompt của bạn. "Prompt to improve prompts".',
                        'examples' => [
                            ['label' => 'Meta prompt', 'prompt' => 'Tôi có prompt sau: "Viết content marketing cho công ty tech"\n\nHãy:\n1. Chỉ ra 5 điểm yếu của prompt này\n2. Đề xuất phiên bản cải thiện\n3. Giải thích tại sao phiên bản mới tốt hơn\n4. Cho thêm 3 biến thể cho các use case khác nhau', 'note' => 'AI đánh giá và cải thiện prompt của bạn'],
                        ],
                        'tips' => ['Hỏi AI "prompt này thiếu gì?"', 'Yêu cầu AI tạo variations cho A/B testing', 'Dùng AI review AI — meta-evaluation'],
                        'exercises' => [
                            ['title' => 'Meta-prompting', 'instruction' => 'Cho AI một prompt cơ bản và yêu cầu AI cải thiện qua 3 iterations', 'difficulty' => 'hard'],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Expert — Ứng dụng thực tế', 'level' => 'expert', 'icon' => 'pi pi-trophy', 'color' => '#ef4444',
                'description' => 'Prompt cho business automation, AI workflows, data analysis, và code generation',
                'lessons' => [
                    [
                        'title' => 'Prompt cho Business Automation', 'content' => 'Thiết kế prompt chains cho quy trình business: lead qualification → email sequence → follow-up → closing. Mỗi prompt là 1 step trong workflow.',
                        'examples' => [
                            ['label' => 'Workflow prompt', 'prompt' => "Step 1 - Lead Qualification:\nPhân tích thông tin lead sau và đánh giá theo BANT:\n{lead_info}\n\nOutput: JSON {qualified: bool, score: 1-10, reason: string, next_action: string}\n\nStep 2 - Nếu qualified:\nViết email personalized giới thiệu sản phẩm, reference pain point từ Step 1.", 'note' => 'Prompt chain: output step 1 → input step 2'],
                        ],
                        'tips' => ['Thiết kế prompt chain như flowchart', 'Mỗi step có input/output rõ ràng', 'Handle edge cases: "Nếu X thì Y"'],
                        'exercises' => [
                            ['title' => 'Business automation', 'instruction' => 'Thiết kế chuỗi 3 prompt cho quy trình: Phân tích feedback khách hàng → Phân loại → Tạo action items', 'difficulty' => 'hard'],
                        ],
                    ],
                    [
                        'title' => 'Prompt cho Code Generation', 'content' => 'Kỹ thuật prompt để AI sinh code chất lượng cao: specifications, test cases, architecture constraints, code style guides.',
                        'examples' => [
                            ['label' => 'Code generation', 'prompt' => "Tech Stack: Laravel 11 + Vue 3 + Inertia.js\nPattern: Follow existing controller pattern in the project\n\nTask: Tạo CRUD controller cho Resource 'Products' với:\n- Validation rules cụ thể\n- Inertia responses\n- Flash messages tiếng Việt\n- Soft delete support\n\nConstraints:\n- Không dùng API resources\n- Follow PSR-12\n- Mỗi method max 20 lines", 'note' => 'Constraints cụ thể → code quality cao hơn'],
                        ],
                        'tips' => ['Luôn specify tech stack và version', 'Cho code examples từ project hiện tại', 'Yêu cầu tests cùng với implementation', 'Dùng constraints để control code quality'],
                        'exercises' => [
                            ['title' => 'Code prompt', 'instruction' => 'Viết prompt để AI generate một Vue.js component với đầy đủ props, events, slots, và unit tests', 'difficulty' => 'hard'],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($data as $ci => $catData) {
            $lessons = $catData['lessons'];
            unset($catData['lessons']);
            $catData['account_id'] = $accountId;
            $catData['sort_order'] = $ci;
            $cat = PromptCategory::create($catData);

            foreach ($lessons as $li => $lessonData) {
                $exercises = $lessonData['exercises'] ?? [];
                unset($lessonData['exercises']);
                $lessonData['category_id'] = $cat->id;
                $lessonData['sort_order'] = $li;
                $lesson = PromptLesson::create($lessonData);

                foreach ($exercises as $ei => $exData) {
                    $exData['lesson_id'] = $lesson->id;
                    $exData['sort_order'] = $ei;
                    PromptExercise::create($exData);
                }
            }
        }

        return back()->with('success', 'Đã tạo dữ liệu mẫu Học Prompts AI.');
    }
}
