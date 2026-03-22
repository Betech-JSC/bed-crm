<?php

namespace Database\Seeders;

use App\Models\WikiArticle;
use App\Models\WikiCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class WikiLaw2026Seeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (!$user) {
            $this->command->warn('No user found.');
            return;
        }

        $accountId = $user->account_id;

        // Get or create parent category
        $parentCat = WikiCategory::where('account_id', $accountId)
            ->where('slug', 'phap-luat-doanh-nghiep')->first();

        if (!$parentCat) {
            $parentCat = WikiCategory::create([
                'account_id' => $accountId,
                'name' => 'Pháp Luật Doanh Nghiệp',
                'slug' => 'phap-luat-doanh-nghiep',
                'icon' => 'pi pi-building',
                'sort_order' => 10,
            ]);
        }

        // Create sub-category for 2026 laws
        $catLuat2026 = WikiCategory::firstOrCreate(
            ['account_id' => $accountId, 'slug' => 'luat-moi-2026'],
            [
                'name' => 'Luật mới có hiệu lực 2026',
                'parent_id' => $parentCat->id,
                'description' => 'Tất cả các luật mới có hiệu lực trong năm 2026 liên quan đến doanh nghiệp',
                'icon' => 'pi pi-calendar',
                'sort_order' => 0,
            ]
        );

        $catCongNghe = WikiCategory::firstOrCreate(
            ['account_id' => $accountId, 'slug' => 'luat-cong-nghe-so'],
            [
                'name' => 'Luật Công nghệ & Số',
                'parent_id' => $parentCat->id,
                'description' => 'Luật AI, Bảo vệ dữ liệu cá nhân, Chuyển đổi số, Công nghiệp CN số',
                'icon' => 'pi pi-microchip-ai',
                'sort_order' => 4,
            ]
        );

        $catLaoDong = WikiCategory::firstOrCreate(
            ['account_id' => $accountId, 'slug' => 'luat-lao-dong-nhan-su'],
            [
                'name' => 'Lao Động & Nhân Sự',
                'parent_id' => $parentCat->id,
                'description' => 'Thuế TNCN, Luật Việc làm, Luật Nhà giáo, BHXH',
                'icon' => 'pi pi-users',
                'sort_order' => 5,
            ]
        );

        $this->command->info('✅ Created 2026 law categories');

        $articles = [
            // ══════════════════════════════════
            // TỔNG HỢP - Pinned
            // ══════════════════════════════════
            [
                'category_id' => $catLuat2026->id,
                'title' => 'Tổng hợp tất cả các Luật mới có hiệu lực năm 2026 — Doanh nghiệp cần biết',
                'is_pinned' => true,
                'excerpt' => 'Danh sách đầy đủ 20+ luật mới có hiệu lực từ 01/01/2026 đến 01/7/2026, chia theo mốc thời gian. Cập nhật tháng 03/2026.',
                'content' => '<h2>📋 Tổng quan tình hình pháp luật 2026</h2>
<p>Năm 2026 là năm có <strong>nhiều luật mới có hiệu lực nhất</strong> trong lịch sử lập pháp Việt Nam, với hàng loạt đạo luật quan trọng đã được Quốc hội thông qua trong năm 2025. Dưới đây là <strong>toàn cảnh</strong> các luật ảnh hưởng trực tiếp đến doanh nghiệp.</p>

<h2>🗓️ Có hiệu lực từ 01/01/2026</h2>
<table>
<tr><th>#</th><th>Luật</th><th>Tác động đến DN</th></tr>
<tr><td>1</td><td><strong>Luật Thuế VAT sửa đổi (149/2025/QH15)</strong></td><td>Ngưỡng DT hộ KD tăng 500 triệu, mở rộng hàng hóa không chịu VAT</td></tr>
<tr><td>2</td><td><strong>Thuế suất TNDN mới (15-17-20%)</strong></td><td>Thuế suất linh hoạt theo doanh thu, SMEs được giảm</td></tr>
<tr><td>3</td><td><strong>Luật Bảo vệ dữ liệu cá nhân (91/2025/QH15)</strong></td><td>Cấm mua bán DLCN, siết thu thập/xử lý dữ liệu</td></tr>
<tr><td>4</td><td><strong>Luật Công nghiệp công nghệ số (71/2025/QH15)</strong></td><td>Khung pháp lý cho AI và tài sản số</td></tr>
<tr><td>5</td><td><strong>Luật Thuế TTĐB sửa đổi (66/2025/QH15)</strong></td><td>Thêm nước giải khát đường cao, điều hòa nhiệt độ</td></tr>
<tr><td>6</td><td><strong>Luật Việc làm 2025 (74/2025/QH15)</strong></td><td>Mở rộng BHTN, quản lý lao động linh hoạt</td></tr>
<tr><td>7</td><td><strong>Luật Nhà giáo 2025 (73/2025/QH15)</strong></td><td>Lương cao nhất hệ thống hành chính sự nghiệp</td></tr>
<tr><td>8</td><td><strong>Luật sửa đổi Luật Quảng cáo (75/2025/QH15)</strong></td><td>Quản lý quảng cáo mạng, minh bạch hóa</td></tr>
<tr><td>9</td><td><strong>Luật sửa đổi Luật KD Bảo hiểm</strong></td><td>Cắt giảm điều kiện KD, khuyến khích kinh tế tư nhân</td></tr>
<tr><td>10</td><td><strong>Luật Hóa chất 2025 (69/2025/QH15)</strong></td><td>Quản lý chặt toàn vòng đời hóa chất</td></tr>
<tr><td>11</td><td><strong>Bãi bỏ thuế khoán & lệ phí môn bài</strong></td><td>Hộ KD chuyển sang kê khai % doanh thu</td></tr>
<tr><td>12</td><td><strong>Luật Năng lượng nguyên tử 2025</strong></td><td>Giám sát an toàn nhà máy điện hạt nhân</td></tr>
<tr><td>13</td><td><strong>Luật Tư pháp người chưa thành niên</strong></td><td>Giảm mức phạt tù, chính sách nhân văn</td></tr>
<tr><td>14</td><td><strong>Luật Giáo dục đại học sửa đổi</strong></td><td>Tự chủ đại học, GD đại học số</td></tr>
</table>

<h2>🗓️ Có hiệu lực từ 01/03/2026</h2>
<table>
<tr><th>#</th><th>Luật</th><th>Tác động đến DN</th></tr>
<tr><td>15</td><td><strong>Luật Trí tuệ nhân tạo (134/2025/QH15)</strong></td><td>Khung pháp lý AI, cấm lạm dụng AI, yêu cầu AI có trách nhiệm</td></tr>
<tr><td>16</td><td><strong>Luật Phục hồi, phá sản 2025</strong></td><td>Ưu tiên phục hồi DN trước phá sản</td></tr>
<tr><td>17</td><td><strong>Luật Đầu tư 2025</strong></td><td>Cắt giảm 39 ngành có điều kiện, tiền kiểm → hậu kiểm</td></tr>
<tr><td>18</td><td><strong>Luật Quy hoạch 2025</strong></td><td>Phân cấp, đơn giản hóa quy trình</td></tr>
</table>

<h2>🗓️ Có hiệu lực từ 01/05/2026</h2>
<table>
<tr><th>#</th><th>Luật</th><th>Tác động</th></tr>
<tr><td>19</td><td><strong>Luật Bảo hiểm tiền gửi</strong></td><td>Ổn định hệ thống tín dụng</td></tr>
</table>

<h2>🗓️ Có hiệu lực từ 01/07/2026</h2>
<table>
<tr><th>#</th><th>Luật</th><th>Tác động đến DN</th></tr>
<tr><td>20</td><td><strong>Luật Thuế TNCN sửa đổi</strong></td><td>Giảm trừ gia cảnh 15,5 triệu/tháng, ngưỡng KD 500 triệu</td></tr>
<tr><td>21</td><td><strong>Luật Quản lý thuế sửa đổi</strong></td><td>Phân nhóm NNT, quản lý theo đối tượng</td></tr>
<tr><td>22</td><td><strong>Luật Chuyển đổi số</strong></td><td>Khung pháp lý quốc gia số</td></tr>
</table>

<h2>⚡ Top 5 luật DN phải nắm ngay (Tháng 03/2026)</h2>
<ol>
<li><strong>Luật Trí tuệ nhân tạo</strong> — Đã có hiệu lực 01/03/2026, DN dùng AI phải tuân thủ</li>
<li><strong>Luật Bảo vệ dữ liệu cá nhân</strong> — Đang áp dụng, phạt nặng nếu vi phạm</li>
<li><strong>Thuế suất TNDN mới 15-17-20%</strong> — Đang áp dụng, ảnh hưởng trực tiếp đến lợi nhuận</li>
<li><strong>Luật Đầu tư 2025</strong> — Có hiệu lực 01/03/2026, cắt giảm điều kiện KD</li>
<li><strong>Luật Thuế TNCN sửa đổi</strong> — Sắp có hiệu lực 01/07/2026, ảnh hưởng tính lương</li>
</ol>',
            ],

            // ══════════════════════════════════
            // LUẬT AI
            // ══════════════════════════════════
            [
                'category_id' => $catCongNghe->id,
                'title' => 'Luật Trí tuệ nhân tạo 2025 (134/2025/QH15) — Hiệu lực 01/03/2026',
                'is_pinned' => true,
                'excerpt' => 'Luật AI đầu tiên của Việt Nam, thiết lập khung pháp lý cho phát triển và ứng dụng AI an toàn, có trách nhiệm.',
                'content' => '<h2>📋 Tổng quan</h2>
<p><strong>Luật Trí tuệ nhân tạo 2025</strong> (Luật số 134/2025/QH15) là <strong>đạo luật đầu tiên</strong> tại Việt Nam quy định chuyên biệt về AI, được Quốc hội thông qua tháng 12/2025, <strong>có hiệu lực từ 01/03/2026</strong>.</p>

<h2>🔑 Nội dung chính</h2>

<h3>1. Mục tiêu</h3>
<ul>
<li>Thiết lập <strong>khung pháp lý thống nhất</strong> cho AI</li>
<li>Thúc đẩy phát triển và ứng dụng AI <strong>an toàn, có trách nhiệm</strong></li>
<li>Bảo vệ quyền con người trong thời đại AI</li>
</ul>

<h3>2. Các hành vi bị cấm</h3>
<ul>
<li>❌ Lợi dụng AI để <strong>vi phạm pháp luật</strong></li>
<li>❌ Sử dụng AI để <strong>lừa dối hoặc thao túng</strong> con người</li>
<li>❌ Tạo deepfake nhằm mục đích xấu</li>
<li>❌ Thu thập dữ liệu trái phép thông qua AI</li>
</ul>

<h3>3. Nghĩa vụ của doanh nghiệp sử dụng AI</h3>
<ul>
<li>Phải <strong>đánh giá rủi ro</strong> trước khi triển khai hệ thống AI</li>
<li>Đảm bảo <strong>tính minh bạch</strong> — người dùng phải biết đang tương tác với AI</li>
<li>Chịu trách nhiệm về <strong>kết quả đầu ra</strong> của AI</li>
<li>Lưu giữ log và dữ liệu để <strong>kiểm soát, giám sát</strong></li>
</ul>

<h3>4. Phân loại rủi ro AI</h3>
<table>
<tr><th>Mức độ</th><th>Ví dụ</th><th>Yêu cầu</th></tr>
<tr><td><strong>Rủi ro cao</strong></td><td>Y tế, tài chính, tư pháp, tuyển dụng</td><td>Đánh giá bắt buộc, giám sát liên tục</td></tr>
<tr><td><strong>Rủi ro trung bình</strong></td><td>Chatbot, nội dung tự động</td><td>Gắn nhãn AI, thông báo cho người dùng</td></tr>
<tr><td><strong>Rủi ro thấp</strong></td><td>Spam filter, tìm kiếm</td><td>Tuân thủ chung</td></tr>
</table>

<h2>💡 Tác động đến doanh nghiệp</h2>
<ul>
<li>Startup AI: Có khung pháp lý rõ ràng, giảm rủi ro pháp lý</li>
<li>DN dùng AI trong marketing/sales: Phải gắn nhãn nội dung AI</li>
<li>DN fintech/healthtech: Phải đánh giá rủi ro AI bắt buộc</li>
<li>DN nước ngoài cung cấp dịch vụ AI tại VN: Phải tuân thủ luật VN</li>
</ul>',
            ],

            // ══════════════════════════════════
            // LUẬT BẢO VỆ DỮ LIỆU CÁ NHÂN
            // ══════════════════════════════════
            [
                'category_id' => $catCongNghe->id,
                'title' => 'Luật Bảo vệ dữ liệu cá nhân (91/2025/QH15) — Hiệu lực 01/01/2026',
                'excerpt' => 'Đạo luật đầu tiên của VN về thu thập, lưu trữ, xử lý và bảo vệ dữ liệu cá nhân. Cấm mua bán DLCN.',
                'content' => '<h2>📋 Tổng quan</h2>
<p><strong>Luật Bảo vệ dữ liệu cá nhân</strong> (Luật số 91/2025/QH15) là đạo luật <strong>đầu tiên</strong> tại Việt Nam điều chỉnh toàn diện hoạt động thu thập, lưu trữ, xử lý và bảo vệ dữ liệu cá nhân. <strong>Hiệu lực từ 01/01/2026</strong>.</p>

<h2>🔑 Nội dung chính</h2>

<h3>1. Nghiêm cấm</h3>
<ul>
<li>❌ <strong>Mua bán dữ liệu cá nhân</strong> dưới mọi hình thức</li>
<li>❌ Thu thập, xử lý, chia sẻ dữ liệu trái phép</li>
<li>❌ Thu thập dữ liệu nhạy cảm mà không có đồng ý rõ ràng</li>
</ul>

<h3>2. Quyền của chủ thể dữ liệu</h3>
<ul>
<li>Quyền biết về việc xử lý dữ liệu của mình</li>
<li>Quyền <strong>đồng ý hoặc từ chối</strong> cho xử lý</li>
<li>Quyền <strong>yêu cầu xóa</strong> dữ liệu cá nhân</li>
<li>Quyền <strong>chuyển dữ liệu</strong> sang tổ chức khác</li>
<li>Quyền <strong>khiếu nại</strong> khi bị vi phạm</li>
</ul>

<h3>3. Nghĩa vụ của DN xử lý dữ liệu</h3>
<ul>
<li>Bổ nhiệm <strong>Nhân viên bảo vệ dữ liệu</strong> (DPO)</li>
<li>Lập <strong>hồ sơ đánh giá tác động</strong> xử lý dữ liệu</li>
<li>Thông báo khi xảy ra <strong>vi phạm dữ liệu</strong> trong 72 giờ</li>
<li>Đảm bảo <strong>dữ liệu quan trọng được lưu trữ tại VN</strong></li>
</ul>

<h2>⚠️ Mức xử phạt</h2>
<ul>
<li>Phạt hành chính: Lên đến <strong>5% doanh thu</strong> trong trường hợp nghiêm trọng</li>
<li>Truy cứu trách nhiệm hình sự đối với cá nhân vi phạm</li>
</ul>

<h2>💡 DN cần làm ngay</h2>
<ol>
<li>Rà soát toàn bộ quy trình thu thập dữ liệu khách hàng</li>
<li>Cập nhật <strong>Chính sách Bảo mật</strong> trên website/app</li>
<li>Bổ sung <strong>form đồng ý rõ ràng</strong> (consent form)</li>
<li>Bổ nhiệm DPO hoặc phân công người phụ trách</li>
<li>Xây dựng quy trình xử lý vi phạm dữ liệu (data breach)</li>
</ol>',
            ],

            // ══════════════════════════════════
            // LUẬT ĐẦU TƯ 2025
            // ══════════════════════════════════
            [
                'category_id' => $catLuat2026->id,
                'title' => 'Luật Đầu tư 2025 — Cắt giảm 39 ngành nghề có điều kiện, hiệu lực 01/03/2026',
                'excerpt' => 'Luật Đầu tư mới cắt giảm 39 ngành nghề đầu tư KD có điều kiện, chuyển từ tiền kiểm sang hậu kiểm.',
                'content' => '<h2>📋 Tổng quan</h2>
<p><strong>Luật Đầu tư 2025</strong> có hiệu lực từ <strong>01/03/2026</strong>, thay thế Luật Đầu tư 2020. Đây là bước đột phá trong cải cách môi trường đầu tư kinh doanh.</p>

<h2>🔑 Thay đổi quan trọng</h2>

<h3>1. Cắt giảm 39 ngành nghề có điều kiện</h3>
<p>Rà soát và <strong>cắt bỏ 39 ngành, nghề</strong> trong danh mục đầu tư kinh doanh có điều kiện. Sửa đổi phạm vi của <strong>20 ngành</strong> khác.</p>

<h3>2. Chuyển từ "tiền kiểm" sang "hậu kiểm"</h3>
<ul>
<li><strong>Trước:</strong> DN phải xin giấy phép trước khi kinh doanh</li>
<li><strong>Sau:</strong> DN được kinh doanh trước, cơ quan quản lý kiểm tra sau</li>
<li>Giảm chi phí tuân thủ và thời gian chờ đợi</li>
</ul>

<h3>3. Đảm bảo quyền tự do đầu tư</h3>
<ul>
<li>Mở rộng quyền tự do lựa chọn ngành nghề kinh doanh</li>
<li>Giảm rào cản gia nhập thị trường</li>
<li>Thu hút FDI mạnh mẽ hơn</li>
</ul>

<h2>💡 Tác động</h2>
<ul>
<li>🟢 Dễ gia nhập thị trường hơn cho startup</li>
<li>🟢 Giảm thủ tục hành chính cho DN</li>
<li>🟢 Thu hút thêm đầu tư nước ngoài</li>
<li>🟡 Cần tự chịu trách nhiệm cao hơn khi hậu kiểm</li>
</ul>',
            ],

            // ══════════════════════════════════
            // LUẬT PHỤC HỒI PHÁ SẢN
            // ══════════════════════════════════
            [
                'category_id' => $catLuat2026->id,
                'title' => 'Luật Phục hồi, Phá sản 2025 — Ưu tiên cứu doanh nghiệp, hiệu lực 01/03/2026',
                'excerpt' => 'Luật mới thay đổi cách tiếp cận: không chỉ phá sản mà ưu tiên cơ chế phục hồi doanh nghiệp gặp khó khăn tài chính.',
                'content' => '<h2>📋 Tổng quan</h2>
<p><strong>Luật Phục hồi, Phá sản 2025</strong> có hiệu lực từ <strong>01/03/2026</strong>, thay thế Luật Phá sản 2014. Thay đổi lớn nhất: <strong>đặt phục hồi lên trước phá sản</strong>.</p>

<h2>🔑 Thay đổi đáng chú ý</h2>

<h3>1. Ưu tiên phục hồi doanh nghiệp</h3>
<ul>
<li>Tên luật thay đổi: từ "Phá sản" → "<strong>Phục hồi, Phá sản</strong>"</li>
<li>Tạo cơ chế để DN gặp khó khăn tài chính có cơ hội phục hồi trước khi bị phá sản</li>
<li>Bảo vệ việc làm cho người lao động</li>
</ul>

<h3>2. Quy trình phục hồi</h3>
<ol>
<li>DN nộp đơn xin phục hồi hoặc chủ nợ đề nghị</li>
<li>Tòa án chỉ định quản tài viên</li>
<li>Lập kế hoạch phục hồi (tối đa 3 năm)</li>
<li>Chủ nợ biểu quyết phương án phục hồi</li>
<li>Thực hiện theo giám sát</li>
</ol>

<h3>3. Nếu phục hồi thất bại</h3>
<p>Mới chuyển sang thủ tục <strong>phá sản và thanh lý</strong> tài sản.</p>

<h2>💡 Tác động tích cực</h2>
<ul>
<li>DN gặp khó không phải "chết" ngay mà có cơ hội sống</li>
<li>Giảm tổn thất cho chủ nợ, nhà đầu tư, người lao động</li>
<li>Phù hợp với thông lệ quốc tế (Chapter 11 - Mỹ)</li>
</ul>',
            ],

            // ══════════════════════════════════
            // LUẬT TNCN SỬA ĐỔI
            // ══════════════════════════════════
            [
                'category_id' => $catLaoDong->id,
                'title' => 'Thuế TNCN sửa đổi 2025 — Giảm trừ gia cảnh 15,5 triệu/tháng, hiệu lực 01/07/2026',
                'is_pinned' => true,
                'excerpt' => 'Luật Thuế TNCN sửa đổi nâng giảm trừ gia cảnh lên 15,5 triệu/tháng cho bản thân, 6,2 triệu cho người phụ thuộc. Ngưỡng KD 500 triệu.',
                'content' => '<h2>📋 Tổng quan</h2>
<p><strong>Luật Thuế thu nhập cá nhân sửa đổi</strong> có hiệu lực từ <strong>01/07/2026</strong>, thay đổi đáng kể mức giảm trừ gia cảnh và ngưỡng doanh thu cá nhân kinh doanh.</p>

<h2>💰 Thay đổi mức giảm trừ gia cảnh</h2>
<table>
<tr><th>Đối tượng</th><th>Trước sửa đổi</th><th>Sau sửa đổi (01/7/2026)</th></tr>
<tr><td><strong>Bản thân người nộp thuế</strong></td><td>11 triệu/tháng</td><td><strong>15,5 triệu/tháng</strong></td></tr>
<tr><td><strong>Mỗi người phụ thuộc</strong></td><td>4,4 triệu/tháng</td><td><strong>6,2 triệu/tháng</strong></td></tr>
</table>

<h2>📊 Ví dụ tính thuế TNCN</h2>
<p>Giả sử nhân viên lương 30 triệu/tháng, 1 người phụ thuộc:</p>
<ul>
<li><strong>Trước:</strong> Thu nhập chịu thuế = 30 - 11 - 4.4 = 14.6 triệu</li>
<li><strong>Sau 01/7/2026:</strong> Thu nhập chịu thuế = 30 - 15.5 - 6.2 = <strong>8.3 triệu</strong></li>
<li>→ <strong>Giảm đáng kể</strong> số thuế phải nộp</li>
</ul>

<h2>🏪 Ngưỡng doanh thu cá nhân kinh doanh</h2>
<table>
<tr><th>Nội dung</th><th>Trước</th><th>Sau 01/7/2026</th></tr>
<tr><td>Ngưỡng DT không nộp thuế TNCN</td><td>200 triệu/năm</td><td><strong>500 triệu/năm</strong></td></tr>
</table>

<h2>💡 DN cần chuẩn bị</h2>
<ol>
<li>Cập nhật phần mềm tính lương từ 01/07/2026</li>
<li>Thông báo cho nhân viên về thay đổi giảm trừ gia cảnh</li>
<li>Rà soát hồ sơ người phụ thuộc</li>
<li>Điều chỉnh kế hoạch chi phí nhân sự</li>
</ol>',
            ],

            // ══════════════════════════════════
            // LUẬT CHUYỂN ĐỔI SỐ
            // ══════════════════════════════════
            [
                'category_id' => $catCongNghe->id,
                'title' => 'Luật Chuyển đổi số — Khung pháp lý quốc gia số, hiệu lực 01/07/2026',
                'excerpt' => 'Luật Chuyển đổi số thiết lập khung pháp lý thống nhất cho quốc gia số, giải quyết điểm nghẽn và tạo động lực đột phá.',
                'content' => '<h2>📋 Tổng quan</h2>
<p><strong>Luật Chuyển đổi số</strong> có hiệu lực từ <strong>01/07/2026</strong>, thiết lập khung pháp lý thống nhất để hình thành <strong>quốc gia số</strong>.</p>

<h2>🔑 Nội dung chính</h2>
<ul>
<li>Giải quyết các <strong>điểm nghẽn</strong> trong chuyển đổi số quốc gia</li>
<li>Tạo <strong>động lực đột phá</strong> cho CĐS trong khu vực công và tư nhân</li>
<li>Quy định về <strong>hạ tầng số</strong>, <strong>dữ liệu số</strong>, <strong>nền tảng số</strong></li>
<li>Chính sách phát triển <strong>kinh tế số</strong>, <strong>xã hội số</strong></li>
<li>Khuyến khích DN <strong>đầu tư vào CĐS</strong> với ưu đãi thuế</li>
</ul>

<h2>💡 Tác động đến DN</h2>
<ul>
<li>Có cơ sở pháp lý rõ ràng để triển khai CĐS</li>
<li>Ưu đãi thuế TNDN cho chi phí CĐS (kết hợp Luật TNDN sửa đổi)</li>
<li>Yêu cầu chia sẻ dữ liệu với cơ quan nhà nước khi cần</li>
</ul>',
            ],
        ];

        foreach ($articles as $data) {
            WikiArticle::firstOrCreate(
                ['account_id' => $accountId, 'title' => $data['title']],
                array_merge($data, [
                    'account_id' => $accountId,
                    'status' => 'published',
                    'is_pinned' => $data['is_pinned'] ?? false,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    'published_at' => now(),
                ])
            );
        }

        $this->command->info('✅ Seeded ' . count($articles) . ' wiki articles about 2026 Law updates');
    }
}
