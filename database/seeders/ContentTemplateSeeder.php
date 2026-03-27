<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\ContentTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class ContentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $account = Account::first();
        $user = User::where('account_id', $account->id)->first();

        $templates = [
            // Blog
            [
                'name' => 'SEO Blog Article',
                'description' => 'Tạo bài blog chuẩn SEO tối ưu cho Google ranking',
                'category' => 'blog',
                'prompt_template' => "Viết bài blog SEO về chủ đề: {{topic}}\nCông ty: {{company}}\nTừ khóa chính: {{keyword}}\nĐộ dài: 1500 từ\nBao gồm: H2 headings, meta description, internal links suggestion",
                'variables' => ['topic', 'company', 'keyword'],
                'settings' => ['tone' => 'professional', 'length' => 1500],
                'is_active' => true,
            ],
            [
                'name' => 'Blog Listicle',
                'description' => 'Bài viết dạng danh sách "Top X điều..."',
                'category' => 'blog',
                'prompt_template' => "Viết bài listicle: Top {{count}} {{topic}}\nĐối tượng: {{audience}}\nPhong cách: Dễ hiểu, có ví dụ thực tế",
                'variables' => ['count', 'topic', 'audience'],
                'settings' => ['tone' => 'casual', 'length' => 1200],
                'is_active' => true,
            ],
            [
                'name' => 'Case Study Blog',
                'description' => 'Viết case study cho khách hàng thành công',
                'category' => 'blog',
                'prompt_template' => "Viết case study:\nKhách hàng: {{client_name}}\nNgành: {{industry}}\nVấn đề: {{problem}}\nGiải pháp: {{solution}}\nKết quả: {{results}}",
                'variables' => ['client_name', 'industry', 'problem', 'solution', 'results'],
                'settings' => ['tone' => 'professional', 'length' => 2000],
                'is_active' => true,
            ],

            // Social Media
            [
                'name' => 'Facebook Post',
                'description' => 'Bài đăng Facebook thu hút tương tác',
                'category' => 'social',
                'prompt_template' => "Viết bài Facebook về: {{topic}}\nMục tiêu: Tăng {{goal}}\nCông ty: {{company}}\nPhong cách: Thân thiện, kèm emoji\nĐộ dài: 150-300 từ",
                'variables' => ['topic', 'goal', 'company'],
                'settings' => ['platform' => 'facebook', 'max_length' => 300],
                'is_active' => true,
            ],
            [
                'name' => 'Instagram Caption',
                'description' => 'Caption Instagram kèm hashtags',
                'category' => 'social',
                'prompt_template' => "Viết caption Instagram:\nSản phẩm/Chủ đề: {{topic}}\nBrand voice: {{brand_voice}}\nKèm 10-15 hashtags liên quan\nCTA: {{cta}}",
                'variables' => ['topic', 'brand_voice', 'cta'],
                'settings' => ['platform' => 'instagram', 'max_length' => 200],
                'is_active' => true,
            ],
            [
                'name' => 'LinkedIn Thought Leadership',
                'description' => 'Bài viết chuyên gia trên LinkedIn',
                'category' => 'social',
                'prompt_template' => "Viết bài LinkedIn thought leadership:\nChủ đề: {{topic}}\nGóc nhìn: {{perspective}}\nKinh nghiệm: {{experience}}\nBài học: {{lesson}}\nPhong cách: Chuyên nghiệp, sâu sắc",
                'variables' => ['topic', 'perspective', 'experience', 'lesson'],
                'settings' => ['platform' => 'linkedin', 'max_length' => 600],
                'is_active' => true,
            ],

            // Email
            [
                'name' => 'Welcome Email',
                'description' => 'Email chào mừng khách hàng mới',
                'category' => 'email',
                'prompt_template' => "Viết email chào mừng:\nKhách hàng: {{customer_name}}\nCông ty: {{company}}\nSản phẩm: {{product}}\nĐiểm nổi bật: 3 tính năng chính\nCTA: Bắt đầu dùng thử",
                'variables' => ['customer_name', 'company', 'product'],
                'settings' => ['type' => 'welcome', 'format' => 'html'],
                'is_active' => true,
            ],
            [
                'name' => 'Newsletter Weekly',
                'description' => 'Bản tin hàng tuần cho subscriber',
                'category' => 'email',
                'prompt_template' => "Viết newsletter tuần {{week_number}}:\nChủ đề chính: {{main_topic}}\nTin tức: {{news_items}}\nTips: 2-3 mẹo hữu ích\nCTA: {{cta}}",
                'variables' => ['week_number', 'main_topic', 'news_items', 'cta'],
                'settings' => ['type' => 'newsletter', 'format' => 'html'],
                'is_active' => true,
            ],
            [
                'name' => 'Cold Outreach Email',
                'description' => 'Email tiếp cận khách hàng lạnh',
                'category' => 'email',
                'prompt_template' => "Viết cold email:\nCông ty mục tiêu: {{target_company}}\nNgành: {{industry}}\nDịch vụ: {{service}}\nGiá trị: {{value_prop}}\nPhong cách: Ngắn gọn, chuyên nghiệp, dưới 150 từ",
                'variables' => ['target_company', 'industry', 'service', 'value_prop'],
                'settings' => ['type' => 'outreach', 'format' => 'text'],
                'is_active' => true,
            ],

            // Ad Copy
            [
                'name' => 'Google Ads Copy',
                'description' => 'Viết nội dung quảng cáo Google Ads',
                'category' => 'ad',
                'prompt_template' => "Viết Google Ads:\nSản phẩm: {{product}}\nTừ khóa: {{keyword}}\nUSP: {{usp}}\nHeadline 1 (30 ký tự): \nHeadline 2 (30 ký tự): \nDescription (90 ký tự): \nCTA: {{cta}}",
                'variables' => ['product', 'keyword', 'usp', 'cta'],
                'settings' => ['platform' => 'google_ads'],
                'is_active' => true,
            ],
            [
                'name' => 'Facebook Ads Copy',
                'description' => 'Bài quảng cáo Facebook hấp dẫn',
                'category' => 'ad',
                'prompt_template' => "Viết Facebook Ads:\nSản phẩm: {{product}}\nĐối tượng: {{audience}}\nPain point: {{pain}}\nGiải pháp: {{solution}}\nOffer: {{offer}}\nTạo 3 phiên bản A/B test",
                'variables' => ['product', 'audience', 'pain', 'solution', 'offer'],
                'settings' => ['platform' => 'facebook_ads'],
                'is_active' => true,
            ],

            // Other
            [
                'name' => 'Product Description',
                'description' => 'Mô tả sản phẩm hấp dẫn cho e-commerce',
                'category' => 'other',
                'prompt_template' => "Viết mô tả sản phẩm:\nTên: {{product_name}}\nDanh mục: {{category}}\nTính năng: {{features}}\nLợi ích: {{benefits}}\nGiá: {{price}}\nPhong cách: Thuyết phục, kèm bullet points",
                'variables' => ['product_name', 'category', 'features', 'benefits', 'price'],
                'settings' => ['tone' => 'persuasive'],
                'is_active' => true,
            ],
        ];

        foreach ($templates as $tpl) {
            ContentTemplate::create(array_merge($tpl, [
                'account_id' => $account->id,
                'usage_count' => rand(0, 150),
            ]));
        }

        $this->command->info('Created ' . count($templates) . ' content templates.');
    }
}
