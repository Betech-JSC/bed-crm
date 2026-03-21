<?php

namespace App\Services;

use App\Services\AI\AiGateway;
use Illuminate\Support\Facades\Log;

/**
 * AiAuditService
 * ───────────────
 * Uses AI Gateway to analyze audit data from Sales Pipeline
 * and generate intelligent reports, proposals, and recommendations.
 *
 * This service focuses on BUSINESS LOGIC (prompts, parsing, fallback).
 * All AI communication goes through AiGateway.
 */
class AiAuditService
{
    public function __construct(
        private AiGateway $ai
    ) {}

    /**
     * Analyze audit data and generate comprehensive report.
     */
    public function analyzeAudit(array $auditData, array $context = []): array
    {
        $prompt = $this->buildAuditPrompt($auditData, $context);

        try {
            $result = $this->ai
                ->withFallback(['gemini', 'openai', 'claude'])
                ->chat($prompt, [
                    'system_prompt' => 'Bạn là chuyên gia tư vấn Digital Marketing và Kinh doanh trực tuyến tại Việt Nam. Luôn trả lời bằng tiếng Việt.',
                    'temperature' => 0.7,
                    'max_tokens' => 4096,
                    'response_format' => 'json',
                ]);

            return [
                'success' => true,
                'analysis' => $result['content'],
                'provider' => $result['metadata']['provider'] ?? 'unknown',
                'generated_at' => now()->toISOString(),
            ];
        } catch (\Exception $e) {
            Log::warning('AiAuditService: AI analysis failed, using fallback', [
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackAnalysis($auditData, $context);
        }
    }

    /**
     * Generate proposal suggestions from audit data.
     */
    public function generateProposal(array $auditData, array $context = []): array
    {
        $prompt = $this->buildProposalPrompt($auditData, $context);

        try {
            $result = $this->ai
                ->withFallback(['gemini', 'openai', 'claude'])
                ->chat($prompt, [
                    'system_prompt' => 'Bạn là chuyên gia kinh doanh dịch vụ Digital Marketing tại Việt Nam. Viết đề xuất chuyên nghiệp bằng tiếng Việt.',
                    'temperature' => 0.7,
                    'max_tokens' => 4096,
                ]);

            return [
                'success' => true,
                'proposal' => $result['content'],
                'provider' => $result['metadata']['provider'] ?? 'unknown',
                'generated_at' => now()->toISOString(),
            ];
        } catch (\Exception $e) {
            Log::warning('AiAuditService: AI proposal failed, using fallback', [
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackProposal($auditData, $context);
        }
    }

    /**
     * Build the audit analysis prompt.
     */
    private function buildAuditPrompt(array $auditData, array $context): string
    {
        $website = $auditData['website'] ?? [];
        $marketing = $auditData['marketing'] ?? [];
        $business = $auditData['business'] ?? [];

        $companyName = $context['company_name'] ?? 'Khách hàng';
        $industry = $business['industry'] ?? 'chưa xác định';
        $websiteUrl = $context['website_url'] ?? ($website['url'] ?? 'không có');

        return <<<PROMPT
Hãy phân tích dữ liệu audit sau và đưa ra đánh giá chi tiết.

## THÔNG TIN DOANH NGHIỆP
- Tên công ty: {$companyName}
- Ngành: {$industry}
- Website: {$websiteUrl}
- Quy mô: {$this->val($business, 'company_size')}
- Doanh thu ước tính: {$this->val($business, 'estimated_revenue')}
- Đối thủ cạnh tranh: {$this->val($business, 'competitors')}
- Pain points: {$this->val($business, 'pain_points')}

## DỮ LIỆU AUDIT WEBSITE
- Có website: {$this->bool($website, 'has_website')}
- SSL: {$this->bool($website, 'has_ssl')}
- Responsive: {$this->bool($website, 'is_responsive')}
- Tốc độ (PageSpeed): {$this->val($website, 'speed_score')}/100
- Điểm SEO: {$this->val($website, 'seo_score')}/100
- Ghi chú website: {$this->val($website, 'notes')}

## DỮ LIỆU AUDIT MARKETING
- Đang chạy quảng cáo: {$this->bool($marketing, 'has_ads')}
- Có Fanpage: {$this->bool($marketing, 'has_fanpage')}
- Đang làm SEO: {$this->bool($marketing, 'has_seo')}
- Có chiến lược nội dung: {$this->bool($marketing, 'has_content')}
- Link fanpage: {$this->val($marketing, 'fanpage_url')}
- Ghi chú marketing: {$this->val($marketing, 'notes')}

## YÊU CẦU
Trả về JSON format:
{
  "overall_score": <số từ 0-100>,
  "overall_rating": "<Yếu|Trung bình|Khá|Tốt|Xuất sắc>",
  "summary": "<tóm tắt 2-3 câu>",
  "website_analysis": {
    "score": <0-100>,
    "strengths": ["<điểm mạnh>"],
    "weaknesses": ["<điểm yếu>"],
    "recommendations": ["<đề xuất>"]
  },
  "marketing_analysis": {
    "score": <0-100>,
    "strengths": ["..."],
    "weaknesses": ["..."],
    "recommendations": ["..."]
  },
  "business_analysis": {
    "score": <0-100>,
    "opportunities": ["<cơ hội>"],
    "threats": ["<thách thức>"]
  },
  "priority_actions": [
    {"action": "<hành động>", "impact": "<cao|trung bình|thấp>", "effort": "<dễ|trung bình|khó>", "timeline": "<ngay|1-2 tuần|1 tháng|3 tháng>"}
  ],
  "estimated_budget_range": "<khoảng ngân sách>",
  "potential_roi": "<dự đoán ROI>"
}
PROMPT;
    }

    /**
     * Build the proposal generation prompt.
     */
    private function buildProposalPrompt(array $auditData, array $context): string
    {
        $website = $auditData['website'] ?? [];
        $marketing = $auditData['marketing'] ?? [];
        $business = $auditData['business'] ?? [];

        $companyName = $context['company_name'] ?? 'Khách hàng';

        return <<<PROMPT
Dựa trên dữ liệu audit dưới đây, viết ĐỀ XUẤT GIẢI PHÁP chuyên nghiệp:

Công ty: {$companyName}, Ngành: {$this->val($business, 'industry')}, Quy mô: {$this->val($business, 'company_size')}
Website: {$this->bool($website, 'has_website')}, SSL: {$this->bool($website, 'has_ssl')}, Responsive: {$this->bool($website, 'is_responsive')}
Tốc độ: {$this->val($website, 'speed_score')}/100, SEO: {$this->val($website, 'seo_score')}/100
Quảng cáo: {$this->bool($marketing, 'has_ads')}, Fanpage: {$this->bool($marketing, 'has_fanpage')}
SEO: {$this->bool($marketing, 'has_seo')}, Nội dung: {$this->bool($marketing, 'has_content')}
Pain points: {$this->val($business, 'pain_points')}
Đối thủ: {$this->val($business, 'competitors')}

Viết đề xuất gồm:
1. Đánh giá tình trạng hiện tại
2. Giải pháp đề xuất (gói dịch vụ cụ thể)
3. Lộ trình triển khai (theo tháng)
4. Lợi ích kỳ vọng
5. Kết luận

Viết bằng tiếng Việt, chuyên nghiệp, text thuần. Các section cách nhau bằng ---.
PROMPT;
    }

    /**
     * Fallback analysis when all AI providers are unavailable.
     */
    private function fallbackAnalysis(array $auditData, array $context): array
    {
        $website = $auditData['website'] ?? [];
        $marketing = $auditData['marketing'] ?? [];

        $websiteScore = $this->calcWebsiteScore($website);
        $marketingScore = $this->calcMarketingScore($marketing);
        $overallScore = round(($websiteScore + $marketingScore) / 2);

        $strengths = [];
        $weaknesses = [];
        $actions = [];

        if (!empty($website['has_website'])) $strengths[] = 'Đã có website';
        else { $weaknesses[] = 'Chưa có website'; $actions[] = ['action' => 'Thiết kế website mới', 'impact' => 'cao', 'effort' => 'trung bình', 'timeline' => '1 tháng']; }

        if (!empty($website['has_ssl'])) $strengths[] = 'Có SSL';
        else $weaknesses[] = 'Chưa có SSL';

        if (!empty($website['is_responsive'])) $strengths[] = 'Website responsive';
        else $weaknesses[] = 'Website chưa responsive';

        if (!empty($marketing['has_ads'])) $strengths[] = 'Đang chạy quảng cáo';
        else $actions[] = ['action' => 'Triển khai Google/Facebook Ads', 'impact' => 'cao', 'effort' => 'trung bình', 'timeline' => '1-2 tuần'];

        if (!empty($marketing['has_seo'])) $strengths[] = 'Đang làm SEO';
        else $actions[] = ['action' => 'Triển khai SEO tổng thể', 'impact' => 'cao', 'effort' => 'khó', 'timeline' => '3 tháng'];

        $rating = match(true) {
            $overallScore >= 80 => 'Tốt',
            $overallScore >= 60 => 'Khá',
            $overallScore >= 40 => 'Trung bình',
            default => 'Yếu',
        };

        return [
            'success' => true,
            'analysis' => json_encode([
                'overall_score' => $overallScore,
                'overall_rating' => $rating,
                'summary' => "Đánh giá tổng quan: {$rating} ({$overallScore}/100). Có " . count($weaknesses) . " điểm cần cải thiện.",
                'website_analysis' => ['score' => $websiteScore, 'strengths' => array_values($strengths), 'weaknesses' => array_values($weaknesses), 'recommendations' => []],
                'marketing_analysis' => ['score' => $marketingScore, 'strengths' => [], 'weaknesses' => [], 'recommendations' => []],
                'business_analysis' => ['score' => 50, 'opportunities' => ['Mở rộng kênh online'], 'threats' => ['Cạnh tranh cao']],
                'priority_actions' => array_slice($actions, 0, 5),
                'estimated_budget_range' => $overallScore < 40 ? '15-30 triệu/tháng' : '5-15 triệu/tháng',
                'potential_roi' => $overallScore < 40 ? '200-400%' : '100-200%',
            ]),
            'provider' => 'fallback',
            'generated_at' => now()->toISOString(),
            'is_fallback' => true,
        ];
    }

    /**
     * Fallback proposal when all AI providers are unavailable.
     */
    private function fallbackProposal(array $auditData, array $context): array
    {
        $companyName = $context['company_name'] ?? 'Quý khách hàng';
        $website = $auditData['website'] ?? [];
        $marketing = $auditData['marketing'] ?? [];

        $services = [];
        if (empty($website['has_website'])) $services[] = '• Thiết kế website chuyên nghiệp, chuẩn SEO, responsive';
        if (empty($marketing['has_ads'])) $services[] = '• Triển khai quảng cáo Google Ads / Facebook Ads';
        if (empty($marketing['has_seo'])) $services[] = '• Dịch vụ SEO tổng thể';
        if (empty($marketing['has_content'])) $services[] = '• Xây dựng kế hoạch content marketing';

        $servicesList = !empty($services) ? implode("\n", $services) : '• Tư vấn chiến lược marketing tổng thể';

        return [
            'success' => true,
            'proposal' => "ĐỀ XUẤT GIẢI PHÁP DIGITAL MARKETING\nDành cho: {$companyName}\n\n---\n\n1. ĐÁNH GIÁ HIỆN TẠI\n\nQua audit, chúng tôi nhận thấy cần cải thiện hiện diện số.\n\n---\n\n2. GIẢI PHÁP\n\n{$servicesList}\n\n---\n\n3. LỘ TRÌNH\n\nTháng 1: Nền tảng\nTháng 2: SEO & Content\nTháng 3: Quảng cáo\n\n---\n\nLiên hệ để trao đổi chi tiết!",
            'provider' => 'fallback',
            'generated_at' => now()->toISOString(),
            'is_fallback' => true,
        ];
    }

    // ── Helpers ──

    private function val(array $data, string $key): string
    {
        return !empty($data[$key]) ? (string) $data[$key] : 'không có';
    }

    private function bool(array $data, string $key): string
    {
        return !empty($data[$key]) ? 'Có' : 'Không';
    }

    private function calcWebsiteScore(array $w): int
    {
        $s = 0; $t = 5;
        if (!empty($w['has_website'])) $s++;
        if (!empty($w['has_ssl'])) $s++;
        if (!empty($w['is_responsive'])) $s++;
        if (($w['speed_score'] ?? 0) >= 50) $s++;
        if (($w['seo_score'] ?? 0) >= 50) $s++;
        return round(($s / $t) * 100);
    }

    private function calcMarketingScore(array $m): int
    {
        $s = 0; $t = 4;
        if (!empty($m['has_ads'])) $s++;
        if (!empty($m['has_fanpage'])) $s++;
        if (!empty($m['has_seo'])) $s++;
        if (!empty($m['has_content'])) $s++;
        return round(($s / $t) * 100);
    }
}
