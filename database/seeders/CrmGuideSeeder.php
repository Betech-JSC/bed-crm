<?php

namespace Database\Seeders;

use App\Models\CrmGuide;
use Illuminate\Database\Seeder;

class CrmGuideSeeder extends Seeder
{
    public function run(): void
    {
        $accountId = 1;
        $userId = 1;
        $order = 0;

        $guides = [
            // ══════════════════════════════════════════
            // BẮT ĐẦU SỬ DỤNG
            // ══════════════════════════════════════════
            [
                'category' => 'Bắt đầu sử dụng',
                'icon' => 'pi pi-play',
                'title' => 'Tổng quan hệ thống BED CRM',
                'summary' => 'Hiểu toàn bộ kiến trúc và các module chính của hệ thống CRM.',
                'content' => '<h2>BED CRM là gì?</h2>
<p>BED CRM là hệ thống <strong>quản lý doanh nghiệp toàn diện</strong> được phát triển bởi BETECH JSC. Hệ thống tích hợp hơn <strong>70+ module</strong> phục vụ mọi hoạt động:</p>

<h3>📊 Nhóm chức năng chính</h3>
<ul>
  <li><strong>CRM & Sales</strong> — Quản lý leads, deals, pipeline, contacts, organizations</li>
  <li><strong>Kinh doanh</strong> — Sản phẩm, báo giá, hợp đồng, phê duyệt, dropship</li>
  <li><strong>Loyalty & CSKH</strong> — Khách hàng, tickets hỗ trợ</li>
  <li><strong>Quản lý Dự án</strong> — Projects, meetings</li>
  <li><strong>Marketing</strong> — Web forms, landing pages, SEO, content calendar, email marketing, social media, AI content</li>
  <li><strong>Tổ chức</strong> — Cơ cấu tổ chức, HR, KPI, OKR, văn hóa</li>
  <li><strong>Chiến lược & Tài chính</strong> — Strategy, finance, BI, reports, CEO roadmap</li>
  <li><strong>Công cụ</strong> — AI Chat, content studio, wiki, files, notifications, system logs</li>
</ul>

<h3>🔐 Phân quyền</h3>
<p>Hệ thống sử dụng <strong>Role-Based Access Control (RBAC)</strong>. Mỗi user được gán Role, mỗi Role có tập Permissions cụ thể. Admin có thể tùy chỉnh quyền cho từng vai trò.</p>

<h3>🏠 Dashboard</h3>
<p>Sau khi đăng nhập, bạn sẽ thấy Dashboard với:</p>
<ul>
  <li>Thống kê tổng quan (leads, deals, revenue)</li>
  <li>Biểu đồ pipeline</li>
  <li>Tasks cần xử lý hôm nay</li>
  <li>Thông báo mới</li>
</ul>',
            ],
            [
                'category' => 'Bắt đầu sử dụng',
                'icon' => 'pi pi-user',
                'title' => 'Đăng nhập & Quản lý tài khoản',
                'summary' => 'Cách đăng nhập, đổi mật khẩu và cập nhật thông tin cá nhân.',
                'content' => '<h2>Đăng nhập hệ thống</h2>
<p>Truy cập URL hệ thống và nhập <strong>email + mật khẩu</strong> được cấp. Hệ thống hỗ trợ ghi nhớ đăng nhập (Remember me).</p>

<h3>🔒 Đổi mật khẩu</h3>
<ol>
  <li>Vào <strong>Settings → Account Settings</strong></li>
  <li>Tìm mục <strong>"Đổi mật khẩu"</strong></li>
  <li>Nhập mật khẩu cũ → mật khẩu mới → xác nhận</li>
  <li>Bấm <strong>Lưu</strong></li>
</ol>

<h3>👤 Cập nhật thông tin cá nhân</h3>
<p>Tại <strong>Account Settings</strong> bạn có thể thay đổi:</p>
<ul>
  <li>Tên hiển thị, avatar</li>
  <li>Email, số điện thoại</li>
  <li>Ngôn ngữ hiển thị (Tiếng Việt / English)</li>
  <li>Múi giờ</li>
</ul>

<h3>📧 Cấu hình SMTP</h3>
<p>Vào <strong>SMTP Settings</strong> để thiết lập email gửi đi (SMTP host, port, username, password). Bấm <strong>"Test"</strong> để kiểm tra kết nối trước khi lưu.</p>',
            ],

            // ══════════════════════════════════════════
            // CRM & SALES
            // ══════════════════════════════════════════
            [
                'category' => 'CRM & Sales',
                'icon' => 'pi pi-bullseye',
                'title' => 'Quản lý Leads (Khách hàng tiềm năng)',
                'summary' => 'Cách tạo, phân loại, chấm điểm và chuyển đổi leads thành deals.',
                'content' => '<h2>Lead là gì?</h2>
<p>Lead là <strong>khách hàng tiềm năng</strong> — người đã thể hiện sự quan tâm đến sản phẩm/dịch vụ nhưng chưa trở thành khách hàng thực sự.</p>

<h3>📋 Tạo Lead mới</h3>
<ol>
  <li>Vào <strong>Leads → Bấm "Thêm Lead"</strong></li>
  <li>Điền thông tin: họ tên, email, SĐT, công ty, nguồn lead</li>
  <li>Gán <strong>Owner</strong> (nhân viên phụ trách)</li>
  <li>Chọn <strong>Status</strong>: New, Contacted, Qualified, Unqualified</li>
  <li>Bấm <strong>Lưu</strong></li>
</ol>

<h3>📥 Nguồn Lead tự động</h3>
<p>Leads được tự động tạo từ:</p>
<ul>
  <li><strong>Web Forms</strong> — Khi khách điền form trên website</li>
  <li><strong>Landing Pages</strong> — Form trên landing page</li>
  <li><strong>Chat Widget</strong> — Khách chat trên website</li>
  <li><strong>UTM Tracking</strong> — Leads được gắn nguồn traffic</li>
  <li><strong>Chatbot Flows</strong> — Bot thu thập thông tin tự động</li>
</ul>

<h3>⭐ Lead Scoring</h3>
<p>Hệ thống tự động <strong>chấm điểm lead</strong> dựa trên:</p>
<ul>
  <li>Thông tin đầy đủ (+10 điểm)</li>
  <li>Mở email (+5 điểm)</li>
  <li>Truy cập website (+3 điểm)</li>
  <li>Yêu cầu báo giá (+20 điểm)</li>
</ul>
<p>Lead &gt; 50 điểm = <strong>Hot Lead</strong>, ưu tiên liên hệ.</p>

<h3>🔄 Chuyển Lead → Deal</h3>
<p>Khi lead đã qualified, bấm <strong>"Convert to Deal"</strong>. Hệ thống sẽ tự động tạo Deal + Contact + Organization từ thông tin lead.</p>',
            ],
            [
                'category' => 'CRM & Sales',
                'icon' => 'pi pi-briefcase',
                'title' => 'Quản lý Deals (Cơ hội kinh doanh)',
                'summary' => 'Tạo, theo dõi và quản lý deals qua pipeline bán hàng.',
                'content' => '<h2>Deal là gì?</h2>
<p>Deal là <strong>cơ hội kinh doanh</strong> cụ thể — khi lead đã qualified và có nhu cầu mua hàng/sử dụng dịch vụ.</p>

<h3>📋 Tạo Deal</h3>
<ol>
  <li>Vào <strong>Deals → "Thêm Deal"</strong></li>
  <li>Đặt tên deal (VD: "Website ABC Corp")</li>
  <li>Chọn <strong>Pipeline stage</strong> (Qualification → Proposal → Negotiation → Won/Lost)</li>
  <li>Nhập <strong>giá trị deal</strong> và xác suất thắng</li>
  <li>Gán contact, organization và owner</li>
</ol>

<h3>📊 Pipeline View (Kanban)</h3>
<p>Trang <strong>Sales Pipeline</strong> hiển thị deals dạng Kanban — kéo thả deal giữa các cột (stages). Mỗi cột hiển thị tổng giá trị deals.</p>

<h3>💰 Các trạng thái Deal</h3>
<ul>
  <li><strong>Qualification</strong> — Đang tìm hiểu nhu cầu</li>
  <li><strong>Proposal</strong> — Đã gửi báo giá</li>
  <li><strong>Negotiation</strong> — Đang đàm phán</li>
  <li><strong>Won</strong> — Thắng deal ✅</li>
  <li><strong>Lost</strong> — Mất deal ❌ (ghi lý do)</li>
</ul>

<h3>📝 Activities trên Deal</h3>
<p>Mỗi deal có thể ghi nhận: cuộc gọi, meeting, email, note, task. Giúp theo dõi toàn bộ lịch sử tương tác.</p>',
            ],
            [
                'category' => 'CRM & Sales',
                'icon' => 'pi pi-send',
                'title' => 'Outbound Sales',
                'summary' => 'Cold outreach, email sequences và quản lý danh sách prospects.',
                'content' => '<h2>Outbound Sales là gì?</h2>
<p>Module giúp đội sales chủ động <strong>tiếp cận khách hàng mới</strong> (cold outreach) thay vì chờ inbound leads.</p>

<h3>📋 Quy trình</h3>
<ol>
  <li><strong>Import prospects</strong> — Upload danh sách Excel/CSV</li>
  <li><strong>Phân loại</strong> — Theo ngành, quy mô, khu vực</li>
  <li><strong>Tạo campaign</strong> — Thiết kế email sequence</li>
  <li><strong>Gửi outreach</strong> — Email tự động hoặc thủ công</li>
  <li><strong>Follow-up</strong> — Theo dõi open/click, gửi lại</li>
  <li><strong>Convert</strong> — Chuyển prospect thành lead khi quan tâm</li>
</ol>

<h3>📧 Email Sequences</h3>
<p>Tạo chuỗi email tự động (3-5 email, cách nhau 2-3 ngày). Hệ thống tự dừng nếu prospect reply.</p>',
            ],
            [
                'category' => 'CRM & Sales',
                'icon' => 'pi pi-id-card',
                'title' => 'Contacts & Organizations',
                'summary' => 'Quản lý danh bạ liên hệ và tổ chức khách hàng.',
                'content' => '<h2>Contacts</h2>
<p>Lưu trữ thông tin <strong>người liên hệ</strong>: họ tên, chức vụ, email, SĐT, ghi chú. Mỗi contact có thể liên kết với 1 Organization và nhiều Deals.</p>

<h3>🏢 Organizations</h3>
<p>Thông tin <strong>công ty/tổ chức</strong>: tên, ngành nghề, quy mô, website, địa chỉ. Một organization có nhiều contacts và nhiều deals.</p>

<h3>🔗 Liên kết dữ liệu</h3>
<ul>
  <li>Contact → Organization (1:1)</li>
  <li>Contact → nhiều Deals</li>
  <li>Organization → nhiều Contacts + nhiều Deals</li>
</ul>

<h3>📥 Import/Export</h3>
<p>Hỗ trợ import từ Excel/CSV và export danh sách. Hệ thống kiểm tra trùng lặp email trước khi import.</p>',
            ],

            // ══════════════════════════════════════════
            // KINH DOANH
            // ══════════════════════════════════════════
            [
                'category' => 'Kinh doanh',
                'icon' => 'pi pi-box',
                'title' => 'Sản phẩm & Dịch vụ',
                'summary' => 'Quản lý catalog sản phẩm, dịch vụ, giá bán và danh mục.',
                'content' => '<h2>Quản lý Sản phẩm & Dịch vụ</h2>
<p>Tạo và quản lý <strong>catalog</strong> sản phẩm/dịch vụ để sử dụng trong Báo giá và Hợp đồng.</p>

<h3>📋 Tạo sản phẩm</h3>
<ol>
  <li>Vào <strong>Sản phẩm & DV → "Thêm"</strong></li>
  <li>Điền: tên, mã SKU, danh mục, mô tả</li>
  <li>Thiết lập: giá bán, đơn vị, thuế VAT</li>
  <li>Upload hình ảnh (nếu có)</li>
</ol>

<h3>📂 Danh mục</h3>
<p>Phân nhóm sản phẩm: <em>Thiết kế website, SEO, Marketing, Phần mềm, Hosting</em>...</p>

<h3>💡 Ứng dụng</h3>
<ul>
  <li>Chọn nhanh khi <strong>tạo báo giá</strong></li>
  <li>Tự động tính tổng tiền + thuế</li>
  <li>Hiển thị trong <strong>hợp đồng</strong></li>
</ul>',
            ],
            [
                'category' => 'Kinh doanh',
                'icon' => 'pi pi-file-edit',
                'title' => 'Đáng giá (Quotations)',
                'summary' => 'Tạo, gửi và quản lý báo giá chuyên nghiệp cho khách hàng.',
                'content' => '<h2>Quy trình Báo giá</h2>

<h3>📋 Tạo Báo giá</h3>
<ol>
  <li>Vào <strong>Báo giá → "Tạo mới"</strong></li>
  <li>Chọn <strong>khách hàng</strong> (contact/organization)</li>
  <li>Liên kết với <strong>Deal</strong> (tùy chọn)</li>
  <li>Thêm <strong>dòng sản phẩm</strong>: chọn từ catalog, nhập SL, đơn giá, chiết khấu</li>
  <li>Hệ thống tự tính: thành tiền, thuế VAT, tổng cộng</li>
  <li>Thêm <strong>điều khoản</strong>, thời hạn hiệu lực</li>
</ol>

<h3>📊 Trạng thái</h3>
<ul>
  <li><strong>Draft</strong> — Đang soạn</li>
  <li><strong>Sent</strong> — Đã gửi khách</li>
  <li><strong>Accepted</strong> — Khách đồng ý ✅</li>
  <li><strong>Rejected</strong> — Khách từ chối</li>
  <li><strong>Expired</strong> — Hết hạn</li>
</ul>

<h3>📤 Gửi báo giá</h3>
<p>Xuất PDF chuyên nghiệp và gửi qua email trực tiếp từ hệ thống.</p>

<h3>🔄 Chuyển thành Hợp đồng</h3>
<p>Khi báo giá được chấp nhận, bấm <strong>"Tạo Hợp đồng"</strong> — dữ liệu tự động copy sang.</p>',
            ],
            [
                'category' => 'Kinh doanh',
                'icon' => 'pi pi-file-check',
                'title' => 'Hợp đồng (Contracts)',
                'summary' => 'Quản lý hợp đồng, gia hạn và theo dõi giá trị hợp đồng.',
                'content' => '<h2>Quản lý Hợp đồng</h2>

<h3>📋 Tạo Hợp đồng</h3>
<ol>
  <li>Tạo từ <strong>báo giá đã duyệt</strong> hoặc tạo mới</li>
  <li>Điền: số hợp đồng, ngày bắt đầu/kết thúc, giá trị</li>
  <li>Đính kèm file hợp đồng scan (PDF)</li>
  <li>Gán người ký, người phụ trách</li>
</ol>

<h3>📊 Trạng thái</h3>
<ul>
  <li><strong>Draft</strong> → <strong>Pending Approval</strong> → <strong>Active</strong> → <strong>Completed</strong></li>
  <li><strong>Cancelled</strong> / <strong>Expired</strong></li>
</ul>

<h3>⏰ Cảnh báo hết hạn</h3>
<p>Hệ thống tự gửi thông báo trước khi hợp đồng hết hạn (30, 15, 7 ngày).</p>',
            ],
            [
                'category' => 'Kinh doanh',
                'icon' => 'pi pi-verified',
                'title' => 'Phê duyệt (Approvals)',
                'summary' => 'Quy trình phê duyệt đa cấp cho báo giá, hợp đồng, chi tiêu.',
                'content' => '<h2>Hệ thống Phê duyệt</h2>
<p>Thiết lập quy trình phê duyệt <strong>đa cấp</strong> cho các hoạt động kinh doanh.</p>

<h3>📋 Cách sử dụng</h3>
<ol>
  <li>Khi tạo báo giá/hợp đồng/chi tiêu vượt hạn mức → tự động gửi phê duyệt</li>
  <li>Người phê duyệt nhận <strong>thông báo</strong></li>
  <li>Bấm <strong>Approve</strong> hoặc <strong>Reject</strong> (kèm lý do)</li>
  <li>Nếu reject → trả về người tạo để chỉnh sửa</li>
</ol>

<h3>⚙️ Thiết lập</h3>
<p>Admin có thể cấu hình: hạn mức phê duyệt, chuỗi phê duyệt (manager → director → CEO), timeout tự động.</p>',
            ],

            // ══════════════════════════════════════════
            // MARKETING
            // ══════════════════════════════════════════
            [
                'category' => 'Marketing',
                'icon' => 'pi pi-file-edit',
                'title' => 'Web Forms — Thu thập Leads',
                'summary' => 'Tạo form embed vào website để thu lead tự động.',
                'content' => '<h2>Web Forms</h2>
<p>Tạo form thu lead và embed vào bất kỳ website nào.</p>

<h3>📋 Tạo Form</h3>
<ol>
  <li>Vào <strong>Web Forms → "Tạo Form"</strong></li>
  <li>Đặt tên, chọn <strong>form type</strong> (inline, modal, slide-in, floating bar)</li>
  <li>Thiết kế fields: Name, Email, Phone, Company, Custom fields</li>
  <li>Cài đặt: thank-you message, redirect URL, email notification</li>
  <li>Bấm <strong>"Lấy mã embed"</strong> → copy script tag</li>
</ol>

<h3>🔗 Embed</h3>
<p>Dán <code>&lt;script&gt;</code> tag vào website. Form sẽ hiển thị tại vị trí embed hoặc popup.</p>

<h3>📊 Theo dõi</h3>
<p>Xem submissions, conversion rate, và dữ liệu lead trong trang Web Forms.</p>',
            ],
            [
                'category' => 'Marketing',
                'icon' => 'pi pi-desktop',
                'title' => 'Landing Pages — Tạo trang thu Lead',
                'summary' => 'Block-based landing page builder, không cần code.',
                'content' => '<h2>Landing Pages</h2>
<p>Tạo landing page <strong>kéo thả block</strong>, không cần developer.</p>

<h3>🧱 Các Block Types</h3>
<ul>
  <li><strong>Hero</strong> — Tiêu đề chính + CTA button</li>
  <li><strong>Features</strong> — Danh sách tính năng (icon + text)</li>
  <li><strong>Testimonials</strong> — Đánh giá khách hàng</li>
  <li><strong>CTA</strong> — Nút hành động nổi bật</li>
  <li><strong>FAQ</strong> — Câu hỏi thường gặp (accordion)</li>
  <li><strong>Pricing</strong> — Bảng giá so sánh</li>
  <li><strong>Form</strong> — Form thu lead inline</li>
  <li><strong>Content</strong> — Text/image tự do</li>
</ul>

<h3>📊 Analytics</h3>
<p>Mỗi landing page tracked: lượt truy cập, submissions, conversion rate.</p>

<h3>🌐 URL công khai</h3>
<p>Landing page có URL dạng <code>/lp/slug</code>, chia sẻ trên mọi kênh.</p>',
            ],
            [
                'category' => 'Marketing',
                'icon' => 'pi pi-chart-line',
                'title' => 'UTM Tracking — Theo dõi nguồn Traffic',
                'summary' => 'Tạo UTM link và theo dõi lead đến từ đâu.',
                'content' => '<h2>UTM Tracking</h2>
<p>Biết chính xác lead đến từ kênh nào: Google, Facebook, Email, Referral...</p>

<h3>📋 Tạo UTM Link</h3>
<ol>
  <li>Vào <strong>UTM Tracking → "Tạo link"</strong></li>
  <li>Nhập: URL đích, Source (google, facebook...), Medium (cpc, social, email), Campaign</li>
  <li>Hệ thống tạo link UTM và short link tự động</li>
</ol>

<h3>📊 Attribution</h3>
<p>Khi lead submit form có UTM params, hệ thống tự động gắn nguồn. Xem báo cáo: channel nào → nhiều lead nhất → nhiều deal nhất.</p>',
            ],
            [
                'category' => 'Marketing',
                'icon' => 'pi pi-search',
                'title' => 'SEO Dashboard',
                'summary' => 'Theo dõi rankings, site audit và organic performance.',
                'content' => '<h2>SEO Dashboard</h2>

<h3>🔍 Keyword Tracking</h3>
<p>Thêm keywords cần theo dõi. Hệ thống ghi nhận: <strong>vị trí ranking, search volume, difficulty, URL ranking</strong>. Xem biểu đồ ranking 7 ngày.</p>

<h3>🩺 Site Audit</h3>
<p>Kiểm tra issues trên website:</p>
<ul>
  <li><strong>Critical</strong> — Broken links, lỗi 404, trang không index</li>
  <li><strong>Warning</strong> — Meta thiếu, title quá dài, hình ảnh thiếu alt</li>
  <li><strong>Info</strong> — Suggestions tối ưu thêm</li>
</ul>
<p>Đánh dấu <strong>"Fixed"</strong> khi đã sửa.</p>

<h3>📊 Rank Distribution</h3>
<p>Biểu đồ phân bổ: bao nhiêu keyword Top 3, Top 10, Top 30, Top 50, &gt;50.</p>',
            ],
            [
                'category' => 'Marketing',
                'icon' => 'pi pi-calendar',
                'title' => 'Content Calendar — Lịch Nội dung',
                'summary' => 'Lên lịch và quản lý nội dung đa kênh.',
                'content' => '<h2>Content Calendar</h2>

<h3>📅 3 View Modes</h3>
<ul>
  <li><strong>List View</strong> — Danh sách với filter và sort</li>
  <li><strong>Board View</strong> — Kanban theo status (Idea → Draft → Review → Scheduled → Published)</li>
  <li><strong>Calendar View</strong> — Lịch tháng, xem nội dung theo ngày</li>
</ul>

<h3>📝 Tạo Content Plan</h3>
<ol>
  <li>Đặt tiêu đề, chon loại (Blog, Social Post, Video, Newsletter, Podcast, Infographic)</li>
  <li>Chọn kênh (Website, Facebook, Instagram, TikTok, YouTube, LinkedIn, Zalo)</li>
  <li>Gán người thực hiện, deadline</li>
  <li>Theo dõi performance: views, clicks, shares, leads</li>
</ol>',
            ],
            [
                'category' => 'Marketing',
                'icon' => 'pi pi-envelope',
                'title' => 'Email Marketing',
                'summary' => 'Templates, lists, campaigns, automations — email marketing toàn diện.',
                'content' => '<h2>Email Marketing</h2>

<h3>📧 4 Module con</h3>

<h4>1. Email Templates</h4>
<p>Tạo template email với editor WYSIWYG. Hỗ trợ variables: <code>{name}</code>, <code>{company}</code>, <code>{deal_name}</code>.</p>

<h4>2. Email Lists</h4>
<p>Quản lý danh sách subscribers. Import từ CSV, quản lý unsubscribe, phân segment.</p>

<h4>3. Email Campaigns</h4>
<p>Tạo campaign: chọn template → chọn list → schedule → gửi. Theo dõi: open rate, click rate, bounce, unsubscribe.</p>

<h4>4. Email Automations</h4>
<p>Chuỗi email tự động khi trigger:</p>
<ul>
  <li>Lead mới → Welcome email</li>
  <li>Sau 3 ngày → Follow-up</li>
  <li>Sau 7 ngày → Case study</li>
  <li>Sau 14 ngày → Gửi offer</li>
</ul>',
            ],
            [
                'category' => 'Marketing',
                'icon' => 'pi pi-star',
                'title' => 'Customer Reviews — Đánh giá Khách hàng',
                'summary' => 'Thu thập, quản lý và hiển thị testimonials từ khách hàng.',
                'content' => '<h2>Customer Reviews</h2>

<h3>📋 Quản lý Reviews</h3>
<p>Thu thập đánh giá từ nhiều nền tảng: <strong>Google, Facebook, Trustpilot, Zalo, Direct</strong>.</p>
<ul>
  <li>⭐ Rating 1-5 sao</li>
  <li>Phân loại theo dịch vụ (CRM, Web Design, SEO, Marketing...)</li>
  <li>Quản lý status: Pending → Approved → Featured</li>
  <li>Filter theo platform, rating, status</li>
</ul>

<h3>🌟 Featured Reviews</h3>
<p>Đánh dấu review tốt nhất làm "Featured" để hiển thị nổi bật trên website.</p>',
            ],
            [
                'category' => 'Marketing',
                'icon' => 'pi pi-users',
                'title' => 'Referral Program — Chương trình Giới thiệu',
                'summary' => 'Tạo mã giới thiệu, theo dõi conversions và quản lý rewards.',
                'content' => '<h2>Referral Program</h2>

<h3>📋 Tạo Mã Giới thiệu</h3>
<ol>
  <li>Vào <strong>Referral Program → "Thêm mã"</strong></li>
  <li>Nhập: tên người giới thiệu, email, mã code</li>
  <li>Chọn loại reward: <strong>Discount (%), Credit (đ), Commission (%)</strong></li>
  <li>Cài giới hạn sử dụng (nếu có)</li>
</ol>

<h3>📊 Tracking</h3>
<ul>
  <li>Mỗi mã có tracking: số lần dùng, conversions</li>
  <li>Conversion status: Pending → Qualified → Converted</li>
  <li>Tự động tính commission dựa trên deal value</li>
</ul>

<h3>💰 Dashboard</h3>
<p>Tổng quan: active codes, total conversions, tổng revenue từ referral, commission đã trả.</p>',
            ],
            [
                'category' => 'Marketing',
                'icon' => 'pi pi-link',
                'title' => 'Link-in-Bio — Trang link cá nhân/công ty',
                'summary' => 'Tạo trang link đẹp với 5 theme, chia sẻ trên MXH.',
                'content' => '<h2>Link-in-Bio</h2>
<p>Tạo trang <strong>link-in-bio</strong> như Linktree — chia sẻ mọi thứ với 1 URL.</p>

<h3>🎨 5 Themes</h3>
<ul>
  <li><strong>Classic</strong> — Nền trắng, text đen</li>
  <li><strong>Dark Mode</strong> — Nền tối, text sáng</li>
  <li><strong>Gradient</strong> — Background gradient tím</li>
  <li><strong>Minimal</strong> — Tối giản</li>
  <li><strong>Neon</strong> — Nền đen, text xanh neon</li>
</ul>

<h3>📋 Cách tạo</h3>
<ol>
  <li>Đặt <strong>username</strong> (URL: /bio/username)</li>
  <li>Nhập display name, bio, avatar</li>
  <li>Thêm links (title + URL)</li>
  <li>Chọn theme</li>
</ol>

<h3>📊 Tracking</h3>
<p>Views, clicks, CTR% cho mỗi bio page.</p>',
            ],
            [
                'category' => 'Marketing',
                'icon' => 'pi pi-qrcode',
                'title' => 'QR Codes — Mã QR thông minh',
                'summary' => 'Tạo QR code trackable cho URL, vCard, WiFi.',
                'content' => '<h2>QR Codes</h2>

<h3>📱 4 Loại QR</h3>
<ul>
  <li><strong>URL</strong> — Chuyển đến website</li>
  <li><strong>vCard</strong> — Thẻ danh thiếp điện tử</li>
  <li><strong>WiFi</strong> — Kết nối WiFi tự động</li>
  <li><strong>Text</strong> — Hiển thị text</li>
</ul>

<h3>🎨 Tùy chỉnh</h3>
<p>Chọn màu foreground/background. Mỗi QR có <strong>tracking URL</strong> riêng (<code>/qr/code</code>).</p>

<h3>📊 Scan Analytics</h3>
<p>Theo dõi: tổng scans, unique scans. Hữu ích cho offline → online conversion.</p>',
            ],
            [
                'category' => 'Marketing',
                'icon' => 'pi pi-map-marker',
                'title' => 'Google My Business',
                'summary' => 'Quản lý listing GMB, reviews và posts từ CRM.',
                'content' => '<h2>Google My Business</h2>

<h3>📍 Quản lý Locations</h3>
<p>Thêm thông tin: tên, địa chỉ, SĐT, website, danh mục. Theo dõi insights: views, searches, actions.</p>

<h3>⭐ Reviews</h3>
<p>Xem tất cả reviews GMB, trả lời inline trực tiếp từ CRM. Theo dõi avg rating.</p>

<h3>📢 GMB Posts</h3>
<p>Tạo bài đăng trên GMB:</p>
<ul>
  <li><strong>Update</strong> — Tin tức, thông báo</li>
  <li><strong>Event</strong> — Sự kiện sắp tới</li>
  <li><strong>Offer</strong> — Ưu đãi, khuyến mãi</li>
</ul>
<p>Mỗi bài có thể gắn CTA: Learn More, Book, Order, Call.</p>',
            ],
            [
                'category' => 'Marketing',
                'icon' => 'pi pi-sparkles',
                'title' => 'AI Content Generator',
                'summary' => 'Dùng AI tạo nội dung blog, social, email, ad copy tự động.',
                'content' => '<h2>AI Content Generator</h2>

<h3>✨ Tạo nội dung</h3>
<ol>
  <li>Nhập <strong>tiêu đề</strong> (keyword chính)</li>
  <li>Chọn loại: Blog SEO, Social Caption, Email Sequence, Ad Copy, Mô tả sản phẩm, Landing Page</li>
  <li>Chọn <strong>tone</strong>: Chuyên nghiệp, Thân thiện, Thoải mái, Trang trọng, Sáng tạo</li>
  <li>Chọn <strong>độ dài</strong>: Ngắn (~200 từ), Trung bình (~500 từ), Dài (~1000+ từ)</li>
  <li>Bấm <strong>"Tạo nội dung AI"</strong></li>
</ol>

<h3>📑 Templates</h3>
<p>Tạo AI template riêng với System Prompt + User Prompt Template. Dùng variables <code>{keyword}</code>, <code>{tone}</code>, <code>{length}</code> để tái sử dụng.</p>

<h3>📊 SEO Analysis</h3>
<p>Mỗi nội dung tạo kèm SEO suggestions: meta title, meta description, focus keyword, readability score.</p>

<h3>📋 Lịch sử</h3>
<p>Tất cả nội dung AI đã tạo được lưu lại. Copy, chỉnh sửa hoặc publish.</p>',
            ],
            [
                'category' => 'Marketing',
                'icon' => 'pi pi-comments',
                'title' => 'Chatbot Flows — Bot tự động',
                'summary' => 'Xây chatbot qualification leads với node builder visual.',
                'content' => '<h2>Chatbot Flows</h2>

<h3>🤖 Chatbot Builder</h3>
<p>Xây dựng flow chatbot với <strong>7 loại node</strong>:</p>
<ul>
  <li><strong>Tin nhắn</strong> — Bot gửi text</li>
  <li><strong>Câu hỏi</strong> — Hỏi và chờ trả lời</li>
  <li><strong>Lựa chọn</strong> — Hiện buttons cho user chọn</li>
  <li><strong>Thu thập TT</strong> — Form fields (name, email, phone, company)</li>
  <li><strong>Điều kiện</strong> — Rẽ nhánh theo câu trả lời</li>
  <li><strong>Hành động</strong> — Tạo lead, gán nhân viên, gửi email</li>
  <li><strong>Kết thúc</strong> — Message kết</li>
</ul>

<h3>⚡ Triggers</h3>
<p>Bot kích hoạt khi:</p>
<ul>
  <li>Tải trang (page_load)</li>
  <li>Click button (button_click)</li>
  <li>Sau N giây (time_delay)</li>
  <li>Rời trang (exit_intent)</li>
  <li>Scroll % (scroll_percent)</li>
</ul>

<h3>📊 Performance</h3>
<p>Theo dõi: conversations, leads captured, conversion rate. Toggle Active/Paused.</p>',
            ],

            // ══════════════════════════════════════════
            // LOYALTY & CSKH
            // ══════════════════════════════════════════
            [
                'category' => 'Loyalty & CSKH',
                'icon' => 'pi pi-heart',
                'title' => 'Quản lý Khách hàng',
                'summary' => 'Theo dõi khách hàng, health score, upsell/cross-sell.',
                'content' => '<h2>Customer Success</h2>
<p>Sau khi deal Won, contact trở thành <strong>Customer</strong>. Module này giúp:</p>
<ul>
  <li>Theo dõi <strong>health score</strong> (xanh/vàng/đỏ)</li>
  <li>Ghi nhận <strong>sử dụng sản phẩm</strong></li>
  <li>Đề xuất <strong>upsell/cross-sell</strong></li>
  <li>Theo dõi <strong>hợp đồng</strong> sắp hết hạn</li>
  <li>Lên lịch <strong>check-in</strong> định kỳ</li>
</ul>',
            ],
            [
                'category' => 'Loyalty & CSKH',
                'icon' => 'pi pi-ticket',
                'title' => 'Tickets Hỗ trợ',
                'summary' => 'Hệ thống ticket support cho khách hàng.',
                'content' => '<h2>Support Tickets</h2>

<h3>📋 Tạo Ticket</h3>
<ol>
  <li>Khách gửi yêu cầu hỗ trợ (qua email/form/chat)</li>
  <li>Hệ thống tạo ticket tự động</li>
  <li>Gán cho nhân viên support</li>
  <li>Phân loại: Bug, Feature Request, Question, Complaint</li>
  <li>Priority: Low, Medium, High, Urgent</li>
</ol>

<h3>📊 Workflow</h3>
<p><strong>Open → In Progress → Waiting → Resolved → Closed</strong></p>

<h3>⏱ SLA</h3>
<p>Thiết lập SLA response time theo priority. Cảnh báo khi sắp vi phạm SLA.</p>',
            ],

            // ══════════════════════════════════════════
            // QUẢN LÝ DỰ ÁN
            // ══════════════════════════════════════════
            [
                'category' => 'Quản lý Dự án',
                'icon' => 'pi pi-folder',
                'title' => 'Projects — Quản lý Dự án',
                'summary' => 'Tạo dự án, phân công tasks, theo dõi tiến độ.',
                'content' => '<h2>Quản lý Dự án</h2>

<h3>📋 Tạo Dự án</h3>
<ol>
  <li>Vào <strong>Projects → "Tạo mới"</strong></li>
  <li>Nhập: tên, mô tả, ngày bắt đầu/kết thúc</li>
  <li>Liên kết với <strong>Deal/Hợp đồng</strong> (tùy chọn)</li>
  <li>Gán team members</li>
</ol>

<h3>📊 Theo dõi</h3>
<ul>
  <li>Tasks trong dự án (to-do, in progress, done)</li>
  <li>Tiến độ % hoàn thành</li>
  <li>Milestones quan trọng</li>
  <li>Files đính kèm</li>
</ul>',
            ],
            [
                'category' => 'Quản lý Dự án',
                'icon' => 'pi pi-video',
                'title' => 'Meetings — Quản lý Cuộc họp',
                'summary' => 'Lên lịch, recording và AI meeting recap.',
                'content' => '<h2>Meetings</h2>

<h3>📋 Tạo Meeting</h3>
<ol>
  <li>Đặt tiêu đề, chọn ngày giờ, thời lượng</li>
  <li>Mời participants (team + khách)</li>
  <li>Chọn loại: Internal, Client, Sales, Interview</li>
  <li>Tạo agenda (các mục thảo luận)</li>
</ol>

<h3>🎥 Tính năng</h3>
<ul>
  <li><strong>Video call</strong> — Meeting link tự động</li>
  <li><strong>Recording</strong> — Ghi hình cuộc họp</li>
  <li><strong>AI Recap</strong> — AI tổng hợp nội dung + action items</li>
  <li><strong>Notes</strong> — Biên bản cuộc họp</li>
</ul>',
            ],

            // ══════════════════════════════════════════
            // TỔ CHỨC
            // ══════════════════════════════════════════
            [
                'category' => 'Tổ chức & HR',
                'icon' => 'pi pi-sitemap',
                'title' => 'Cơ cấu Tổ chức & HR',
                'summary' => 'Sơ đồ tổ chức, quản lý nhân sự, KPI, OKR.',
                'content' => '<h2>Tổ chức & HR</h2>

<h3>🏢 Org Structure</h3>
<p>Sơ đồ cây tổ chức: Phòng ban → Team → Nhân viên. Hiển thị dạng tree/chart.</p>

<h3>👥 Employees</h3>
<p>Quản lý hồ sơ nhân sự: thông tin cá nhân, chức vụ, phòng ban, lương, ngày vào. GPS chấm công (nếu có).</p>

<h3>🎯 KPI Definitions</h3>
<p>Thiết lập chỉ tiêu KPI cho từng vị trí/phòng ban. Theo dõi thực hiện vs mục tiêu.</p>

<h3>🎯 OKR (Org Objectives)</h3>
<p>Thiết lập mục tiêu theo phương pháp OKR: Objective → Key Results. Liên kết từ cấp công ty → phòng ban → cá nhân.</p>

<h3>💖 Văn hóa Doanh nghiệp</h3>
<p>Module chia sẻ giá trị cốt lõi, tầm nhìn, sứ mệnh, core values. Xây dựng văn hóa nội bộ.</p>',
            ],

            // ══════════════════════════════════════════
            // CHIẾN LƯỢC & TÀI CHÍNH
            // ══════════════════════════════════════════
            [
                'category' => 'Chiến lược & Tài chính',
                'icon' => 'pi pi-compass',
                'title' => 'Strategy & Finance',
                'summary' => 'Chiến lược kinh doanh, tài chính, BI, reports.',
                'content' => '<h2>Strategy & Finance</h2>

<h3>📊 Strategy Board</h3>
<p>Bảng chiến lược kinh doanh: SWOT analysis, competitive landscape, growth targets, quarterly goals.</p>

<h3>💰 Finance</h3>
<p>Theo dõi: revenue, expenses, profit. So sánh actual vs budget. Biểu đồ cash flow.</p>

<h3>💳 Transactions</h3>
<p>Ghi nhận mọi giao dịch thu/chi. Phân loại theo danh mục. Export báo cáo thuế.</p>

<h3>🧠 Business Intelligence</h3>
<p>Dashboard AI phân tích: customer insights, sales prediction, market trends, revenue forecast.</p>

<h3>📈 Reports</h3>
<p>Báo cáo tổng hợp: Sales Performance, Marketing ROI, Team Productivity, Customer Retention.</p>

<h3>🗺 CEO Roadmap</h3>
<p>Lộ trình phát triển công ty 1-3-5 năm. Milestones, KRs, budget allocation.</p>',
            ],

            // ══════════════════════════════════════════
            // CÔNG CỤ
            // ══════════════════════════════════════════
            [
                'category' => 'Công cụ',
                'icon' => 'pi pi-check-square',
                'title' => 'Công việc Cá nhân (My Tasks)',
                'summary' => 'Quản lý to-do list, tasks cá nhân theo Kanban.',
                'content' => '<h2>My Tasks</h2>
<p>Quản lý công việc cá nhân độc lập với projects.</p>

<h3>📋 Views</h3>
<ul>
  <li><strong>List</strong> — Danh sách có filter/sort</li>
  <li><strong>Board</strong> — Kanban (To Do → In Progress → Done)</li>
</ul>

<h3>⚡ Tính năng</h3>
<ul>
  <li>Priority: Low/Medium/High/Urgent</li>
  <li>Due date + reminders</li>
  <li>Categories/Tags</li>
  <li>Subtasks</li>
  <li>Notes đính kèm</li>
</ul>',
            ],
            [
                'category' => 'Công cụ',
                'icon' => 'pi pi-sparkles',
                'title' => 'AI Chat',
                'summary' => 'Chat trực tiếp với AI để hỗ trợ công việc.',
                'content' => '<h2>AI Chat</h2>
<p>Chat với <strong>AI assistant</strong> để:</p>
<ul>
  <li>Hỏi đáp về CRM, marketing, sales</li>
  <li>Phân tích dữ liệu</li>
  <li>Viết email, báo cáo</li>
  <li>Brainstorm ideas</li>
  <li>Dịch nội dung đa ngôn ngữ</li>
</ul>
<p>Hỗ trợ nhiều model: GPT-4, Claude, Gemini (cấu hình trong AI Providers).</p>',
            ],
            [
                'category' => 'Công cụ',
                'icon' => 'pi pi-palette',
                'title' => 'Content Studio',
                'summary' => 'Studio sáng tạo nội dung AI: social posts, blog SEO, thumbnails.',
                'content' => '<h2>Content Studio</h2>
<p>All-in-one content creation studio.</p>

<h3>📝 Social Content</h3>
<p>Tạo nội dung cho nhiều platform cùng lúc (Facebook, Instagram, LinkedIn, Twitter). AI viết theo tone + platform chuẩn.</p>

<h3>📖 Blog SEO</h3>
<p>Viết bài SEO dài: AI tạo outline → content → meta tags → schema markup. Có rich text editor. SEO analysis tự động: readability score, keyword density, heading structure.</p>

<h3>🖼 Thumbnail AI</h3>
<p>AI generate hình thumbnail cho bài blog/social. Nhiều style: Modern, Corporate, Creative, Tech, Nature, Bold.</p>

<h3>📤 Publish</h3>
<p>Lưu content và đăng lên social accounts đã kết nối. Hỗ trợ schedule cho tương lai.</p>',
            ],
            [
                'category' => 'Công cụ',
                'icon' => 'pi pi-book',
                'title' => 'Wiki Nội bộ',
                'summary' => 'Knowledge base nội bộ công ty.',
                'content' => '<h2>Wiki</h2>
<p>Xây dựng <strong>knowledge base</strong> nội bộ:</p>
<ul>
  <li>Tạo articles theo categories</li>
  <li>Rich text editor (headings, images, tables, code)</li>
  <li>Search full-text</li>
  <li>Quản lý phiên bản (version history)</li>
  <li>Phân quyền xem/sửa theo role</li>
</ul>
<p>Hữu ích cho: quy trình nội bộ, onboarding, technical docs, SOPs.</p>',
            ],
            [
                'category' => 'Công cụ',
                'icon' => 'pi pi-graduation-cap',
                'title' => 'Học Prompts AI',
                'summary' => 'Module học prompt engineering từ cơ bản đến nâng cao.',
                'content' => '<h2>Prompt Learning</h2>
<p>Học cách viết prompts hiệu quả cho AI:</p>

<h3>📚 Levels</h3>
<ul>
  <li><strong>Beginner</strong> — Cơ bản về prompts, cách đặt câu hỏi</li>
  <li><strong>Intermediate</strong> — Chain-of-thought, few-shot, role prompting</li>
  <li><strong>Advanced</strong> — System prompts, multi-step reasoning, tool use</li>
</ul>

<h3>💡 Tính năng</h3>
<ul>
  <li>Bài giảng có ví dụ thực tế</li>
  <li>Bài tập thực hành</li>
  <li>Theo dõi tiến độ học</li>
  <li>Tips & tricks hàng tuần</li>
</ul>',
            ],
            [
                'category' => 'Công cụ',
                'icon' => 'pi pi-cog',
                'title' => 'System Settings & Admin',
                'summary' => 'Users, Roles, Permissions, AI Providers, System Logs.',
                'content' => '<h2>Admin Settings</h2>

<h3>👥 Users</h3>
<p>Tạo/sửa/xóa tài khoản user. Gán roles. Reset mật khẩu. Active/Deactive.</p>

<h3>🛡 Roles & Permissions</h3>
<p>Tạo roles (Admin, Manager, Sales, Marketing...). Gán permissions chi tiết cho từng module/action (view, create, edit, delete).</p>

<h3>🤖 AI Providers</h3>
<p>Cấu hình API keys cho: OpenAI (GPT), Anthropic (Claude), Google (Gemini). Chọn model mặc định.</p>

<h3>🔗 Social Platforms</h3>
<p>Kết nối tài khoản MXH: Facebook Page, Instagram Business, LinkedIn, Twitter.</p>

<h3>📋 System Logs</h3>
<p>Xem logs hệ thống: API errors, user activities, cron jobs. Filter theo level, date, source.</p>

<h3>🕐 System History</h3>
<p>Lịch sử thay đổi: ai sửa gì, lúc nào. Audit trail đầy đủ.</p>

<h3>🗑 Thùng rác</h3>
<p>Dữ liệu đã xóa mềm. Có thể khôi phục trong 30 ngày. Xóa vĩnh viễn sau 30 ngày.</p>',
            ],
        ];

        foreach ($guides as $guide) {
            CrmGuide::create([
                'account_id' => $accountId,
                'created_by' => $userId,
                'title' => $guide['title'],
                'category' => $guide['category'],
                'icon' => $guide['icon'],
                'summary' => $guide['summary'],
                'content' => $guide['content'],
                'is_published' => true,
                'sort_order' => ++$order,
            ]);
        }
    }
}
