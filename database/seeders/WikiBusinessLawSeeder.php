<?php

namespace Database\Seeders;

use App\Models\WikiArticle;
use App\Models\WikiCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class WikiBusinessLawSeeder extends Seeder
{
    public function run(): void
    {
        // Get first user for authorship
        $user = User::first();
        if (!$user) {
            $this->command->warn('No user found. Skipping WikiBusinessLawSeeder.');
            return;
        }

        $accountId = $user->account_id;

        // ── Create Parent Category: Pháp Luật Doanh Nghiệp ──
        $parentCat = WikiCategory::firstOrCreate(
            ['account_id' => $accountId, 'slug' => 'phap-luat-doanh-nghiep'],
            [
                'name' => 'Pháp Luật Doanh Nghiệp',
                'description' => 'Tổng hợp kiến thức về luật doanh nghiệp, thuế, quản lý nhà nước',
                'icon' => 'pi pi-building',
                'sort_order' => 10,
            ]
        );

        // ── Sub-categories ──
        $catLuatDN = WikiCategory::firstOrCreate(
            ['account_id' => $accountId, 'slug' => 'luat-doanh-nghiep'],
            [
                'name' => 'Luật Doanh Nghiệp',
                'parent_id' => $parentCat->id,
                'description' => 'Luật Doanh nghiệp 2020, sửa đổi 2025 và các văn bản hướng dẫn',
                'icon' => 'pi pi-file',
                'sort_order' => 1,
            ]
        );

        $catThue = WikiCategory::firstOrCreate(
            ['account_id' => $accountId, 'slug' => 'thue-doanh-nghiep'],
            [
                'name' => 'Thuế Doanh Nghiệp',
                'parent_id' => $parentCat->id,
                'description' => 'Thuế TNDN, VAT, TNCN và các chính sách thuế mới nhất',
                'icon' => 'pi pi-calculator',
                'sort_order' => 2,
            ]
        );

        $catQuanLy = WikiCategory::firstOrCreate(
            ['account_id' => $accountId, 'slug' => 'quan-ly-nha-nuoc'],
            [
                'name' => 'Quản Lý Nhà Nước',
                'parent_id' => $parentCat->id,
                'description' => 'Thủ tục hành chính, đăng ký kinh doanh, giấy phép',
                'icon' => 'pi pi-shield',
                'sort_order' => 3,
            ]
        );

        $this->command->info('✅ Created wiki categories for Pháp Luật Doanh Nghiệp');

        // ── Articles ──
        $articles = [
            // === LUẬT DOANH NGHIỆP ===
            [
                'category_id' => $catLuatDN->id,
                'title' => 'Tổng quan Luật Doanh nghiệp sửa đổi 2025 (Luật 76/2025/QH15)',
                'is_pinned' => true,
                'excerpt' => 'Luật sửa đổi, bổ sung một số điều của Luật Doanh nghiệp có hiệu lực từ 01/7/2025. Tổng hợp những thay đổi quan trọng nhất.',
                'content' => '<h2>📋 Tổng quan</h2>
<p><strong>Luật sửa đổi, bổ sung một số điều của Luật Doanh nghiệp</strong> (Luật số 76/2025/QH15) đã được Quốc hội thông qua và có <strong>hiệu lực từ ngày 01/7/2025</strong>. Luật này sửa đổi, bổ sung Luật Doanh nghiệp 2020 (số 59/2020/QH14).</p>

<h2>🔑 Những thay đổi quan trọng nhất</h2>

<h3>1. Thông tin chủ sở hữu hưởng lợi (Beneficial Owner)</h3>
<p>Đây là điểm mới <strong>nổi bật nhất</strong>. Doanh nghiệp phải:</p>
<ul>
<li>Thu thập, cập nhật, lưu giữ thông tin về chủ sở hữu hưởng lợi</li>
<li>Cung cấp cho cơ quan nhà nước có thẩm quyền khi được yêu cầu</li>
<li>Bổ sung thông tin và danh sách chủ sở hữu hưởng lợi trong hồ sơ đăng ký kinh doanh</li>
</ul>

<h3>2. Cổ tức và giá thị trường</h3>
<p>Luật làm rõ:</p>
<ul>
<li><strong>Cổ tức:</strong> Là khoản lợi nhuận sau thuế được trả cho mỗi cổ phần bằng tiền mặt hoặc tài sản khác</li>
<li><strong>Giá thị trường:</strong> Xác định cụ thể giá thị trường của phần vốn góp, cổ phần đối với từng trường hợp (niêm yết, OTC...)</li>
</ul>

<h3>3. Giấy tờ pháp lý cá nhân</h3>
<p>Bỏ quy định về <strong>Chứng minh nhân dân</strong>, thay thế bằng:</p>
<ul>
<li>Thẻ căn cước</li>
<li>Căn cước công dân gắn chip</li>
<li>Hộ chiếu</li>
</ul>

<h3>4. Quyền CB-CC-VC tham gia doanh nghiệp</h3>
<p>Bổ sung cho phép cán bộ, công chức, viên chức được:</p>
<ul>
<li>Góp vốn, mua cổ phần, mua phần vốn góp</li>
<li>Thành lập, quản lý doanh nghiệp trong lĩnh vực <strong>khoa học, công nghệ, đổi mới sáng tạo và chuyển đổi số quốc gia</strong></li>
</ul>

<h3>5. Tăng trách nhiệm người đại diện pháp luật</h3>
<p>Tăng cường trách nhiệm cá nhân đối với những thiệt hại gây ra cho doanh nghiệp do vi phạm trách nhiệm.</p>

<h3>6. Cấm kê khai giả mạo, khống vốn điều lệ</h3>
<p>Bổ sung hành vi bị nghiêm cấm:</p>
<ul>
<li>Kê khai giả mạo, không trung thực nội dung hồ sơ ĐKKD</li>
<li>Kê khai khống vốn điều lệ</li>
</ul>

<h3>7. Giảm vốn điều lệ CTCP</h3>
<ul>
<li>Có thể hoàn trả vốn góp theo tỷ lệ sở hữu nếu hoạt động trên 02 năm và đảm bảo thanh toán nợ</li>
<li>Bổ sung trường hợp hoàn lại vốn góp theo yêu cầu cổ đông sở hữu cổ phần ưu đãi hoàn lại</li>
</ul>

<h2>📅 Timeline áp dụng</h2>
<table>
<tr><th>Thời điểm</th><th>Nội dung</th></tr>
<tr><td>01/7/2025</td><td>Luật 76/2025/QH15 có hiệu lực</td></tr>
<tr><td>2026</td><td>Tiếp tục áp dụng, hoàn thiện hành lang pháp lý</td></tr>
</table>

<h2>📎 Văn bản liên quan</h2>
<ul>
<li>Luật Doanh nghiệp 2020 (59/2020/QH14) — hiệu lực 01/01/2021</li>
<li>Luật sửa đổi 2025 (76/2025/QH15) — hiệu lực 01/07/2025</li>
</ul>',
            ],

            [
                'category_id' => $catLuatDN->id,
                'title' => 'So sánh Luật Doanh nghiệp 2020 vs Sửa đổi 2025 — Điểm khác biệt chính',
                'excerpt' => 'Bảng so sánh chi tiết những điều khoản được sửa đổi, bổ sung giữa Luật DN 2020 gốc và bản sửa đổi 2025.',
                'content' => '<h2>📊 Bảng so sánh chi tiết</h2>

<table>
<tr><th>Nội dung</th><th>Luật DN 2020</th><th>Sửa đổi 2025</th></tr>
<tr><td><strong>Chủ sở hữu hưởng lợi</strong></td><td>Chưa có quy định cụ thể</td><td>Bắt buộc thu thập, lưu giữ, cung cấp thông tin</td></tr>
<tr><td><strong>Giấy tờ cá nhân</strong></td><td>CMND/CCCD/Hộ chiếu</td><td>Bỏ CMND, chỉ còn Căn cước/Hộ chiếu</td></tr>
<tr><td><strong>CB-CC-VC lập DN</strong></td><td>Cấm hoàn toàn</td><td>Cho phép trong lĩnh vực KH-CN, ĐMST, CĐS</td></tr>
<tr><td><strong>Định nghĩa cổ tức</strong></td><td>Chung chung</td><td>Cụ thể: lợi nhuận sau thuế/cổ phần</td></tr>
<tr><td><strong>Giá thị trường vốn góp</strong></td><td>Không quy định rõ</td><td>Quy định cụ thể theo từng trường hợp</td></tr>
<tr><td><strong>Kê khai khống vốn ĐL</strong></td><td>Chưa có điều cấm riêng</td><td>Bổ sung hành vi bị nghiêm cấm</td></tr>
<tr><td><strong>Trách nhiệm người ĐD PL</strong></td><td>Quy định chung</td><td>Tăng cường trách nhiệm cá nhân</td></tr>
<tr><td><strong>Giảm vốn ĐL CTCP</strong></td><td>Hạn chế</td><td>Mở rộng: hoàn trả vốn sau 2 năm + CP ưu đãi hoàn lại</td></tr>
<tr><td><strong>Chào bán trái phiếu RL</strong></td><td>Quy định cũ</td><td>Điều chỉnh cho CT chưa đại chúng</td></tr>
<tr><td><strong>Mẫu dấu DN</strong></td><td>Đã bãi bỏ thông báo mẫu dấu</td><td>Tiếp tục bãi bỏ, đơn giản hóa</td></tr>
</table>

<h2>💡 Nhận xét</h2>
<ul>
<li>Xu hướng chung: <strong>minh bạch hóa</strong>, <strong>số hóa</strong>, <strong>đơn giản hóa thủ tục</strong></li>
<li>Khuyến khích CB-CC-VC tham gia đổi mới sáng tạo</li>
<li>Nâng cao trách nhiệm người đứng đầu doanh nghiệp</li>
<li>Phù hợp với xu thế hội nhập quốc tế và phòng chống rửa tiền</li>
</ul>',
            ],

            [
                'category_id' => $catLuatDN->id,
                'title' => 'Các loại hình doanh nghiệp tại Việt Nam — Hướng dẫn chọn loại hình phù hợp',
                'excerpt' => 'Hướng dẫn chi tiết về 5 loại hình doanh nghiệp phổ biến: TNHH 1 thành viên, TNHH 2+ TV, CP, Hợp danh, DNTN.',
                'content' => '<h2>📋 5 loại hình doanh nghiệp phổ biến</h2>

<h3>1. Công ty TNHH một thành viên</h3>
<ul>
<li><strong>Chủ sở hữu:</strong> 1 tổ chức hoặc 1 cá nhân</li>
<li><strong>Trách nhiệm:</strong> Trong phạm vi vốn điều lệ</li>
<li><strong>Phát hành CP:</strong> Không được</li>
<li><strong>Phù hợp:</strong> Startup 1 người, chi nhánh cty nước ngoài</li>
</ul>

<h3>2. Công ty TNHH hai thành viên trở lên</h3>
<ul>
<li><strong>Thành viên:</strong> 2 - 50 tổ chức/cá nhân</li>
<li><strong>Trách nhiệm:</strong> Trong phạm vi vốn góp</li>
<li><strong>Phát hành CP:</strong> Không được</li>
<li><strong>Phù hợp:</strong> DN gia đình, hợp tác kinh doanh nhỏ</li>
</ul>

<h3>3. Công ty Cổ phần (CTCP)</h3>
<ul>
<li><strong>Cổ đông:</strong> Tối thiểu 3, không giới hạn tối đa</li>
<li><strong>Trách nhiệm:</strong> Trong phạm vi số cổ phần sở hữu</li>
<li><strong>Phát hành CP:</strong> Được phép</li>
<li><strong>Phù hợp:</strong> DN muốn huy động vốn, IPO, scale lớn</li>
</ul>

<h3>4. Công ty Hợp danh</h3>
<ul>
<li><strong>Thành viên:</strong> Tối thiểu 2 thành viên hợp danh</li>
<li><strong>Trách nhiệm:</strong> Thành viên HD chịu trách nhiệm vô hạn</li>
<li><strong>Phù hợp:</strong> Văn phòng luật, công ty kiểm toán, tư vấn</li>
</ul>

<h3>5. Doanh nghiệp tư nhân (DNTN)</h3>
<ul>
<li><strong>Chủ sở hữu:</strong> 1 cá nhân duy nhất</li>
<li><strong>Trách nhiệm:</strong> Chịu trách nhiệm bằng toàn bộ tài sản</li>
<li><strong>Lưu ý:</strong> Không được góp vốn thành lập DN khác</li>
<li><strong>Phù hợp:</strong> Kinh doanh cá thể nhỏ</li>
</ul>

<h2>📊 Bảng so sánh nhanh</h2>
<table>
<tr><th>Tiêu chí</th><th>TNHH 1 TV</th><th>TNHH 2+ TV</th><th>CTCP</th><th>Hợp danh</th><th>DNTN</th></tr>
<tr><td>Số chủ sở hữu</td><td>1</td><td>2-50</td><td>≥3</td><td>≥2 HD</td><td>1</td></tr>
<tr><td>Trách nhiệm</td><td>Hữu hạn</td><td>Hữu hạn</td><td>Hữu hạn</td><td>Vô hạn (HD)</td><td>Vô hạn</td></tr>
<tr><td>Phát hành CP</td><td>✗</td><td>✗</td><td>✓</td><td>✗</td><td>✗</td></tr>
<tr><td>Tư cách pháp nhân</td><td>Có</td><td>Có</td><td>Có</td><td>Có</td><td>Không</td></tr>
</table>',
            ],

            // === THUẾ DOANH NGHIỆP ===
            [
                'category_id' => $catThue->id,
                'title' => 'Thuế TNDN sửa đổi 2025 — Thuế suất linh hoạt theo doanh thu từ 01/01/2026',
                'is_pinned' => true,
                'excerpt' => 'Luật Thuế TNDN sửa đổi (67/2025/QH15) áp dụng thuế suất 15%-17%-20% linh hoạt theo quy mô doanh thu DN.',
                'content' => '<h2>📋 Tổng quan</h2>
<p><strong>Luật Thuế thu nhập doanh nghiệp sửa đổi</strong> (Luật số 67/2025/QH15) có hiệu lực từ <strong>01/10/2025</strong>, áp dụng cho kỳ tính thuế TNDN từ năm 2025 trở đi.</p>

<h2>💰 Thuế suất mới từ 01/01/2026</h2>
<table>
<tr><th>Doanh thu năm</th><th>Thuế suất</th><th>Trước đây</th></tr>
<tr><td>Dưới 3 tỷ đồng</td><td><strong>15%</strong></td><td>20%</td></tr>
<tr><td>Từ 3 tỷ đến dưới 50 tỷ</td><td><strong>17%</strong></td><td>20%</td></tr>
<tr><td>Từ 50 tỷ trở lên</td><td><strong>20%</strong></td><td>20%</td></tr>
</table>

<p>⚡ <strong>Điểm mới:</strong> Thuế suất linh hoạt theo quy mô, hỗ trợ DN nhỏ & vừa (SMEs).</p>

<h2>📝 Mở rộng chi phí được trừ</h2>
<p>Luật mới cho phép tính vào chi phí hợp lý:</p>
<ul>
<li>Chi phí đào tạo và đào tạo lại nhân sự</li>
<li>Chi phí chuyển đổi số</li>
<li>Chi phí nghiên cứu và phát triển công nghệ (R&D)</li>
<li>Chi phí hoạt động khởi nghiệp đổi mới sáng tạo</li>
</ul>

<h2>🎯 Ưu đãi thuế mới</h2>
<p>Miễn, giảm thuế TNDN cho DN hoạt động trong:</p>
<ul>
<li>Chuyển đổi số</li>
<li>Công nghệ cao</li>
<li>Đổi mới sáng tạo</li>
</ul>

<h2>📎 Văn bản hướng dẫn</h2>
<ul>
<li><strong>Nghị định 320/2025/NĐ-CP:</strong> Hướng dẫn Luật Thuế TNDN, hiệu lực 15/12/2025</li>
<li><strong>Thông tư 20/2026/TT-BTC:</strong> Hướng dẫn chi tiết hồ sơ chứng từ chi phí được trừ, hiệu lực 12/3/2026</li>
</ul>',
            ],

            [
                'category_id' => $catThue->id,
                'title' => 'Thuế VAT sửa đổi 2025 — Giảm 2% VAT đến hết 2026 & ngưỡng mới cho hộ KD',
                'excerpt' => 'Luật Thuế GTGT sửa đổi (149/2025/QH15) có hiệu lực 01/01/2026. Giảm 2% VAT kéo dài đến 31/12/2026.',
                'content' => '<h2>📋 Tổng quan</h2>
<p><strong>Luật Thuế giá trị gia tăng sửa đổi</strong> (Luật số 149/2025/QH15) có hiệu lực từ <strong>01/01/2026</strong>.</p>

<h2>💡 Điểm mới quan trọng</h2>

<h3>1. Tiếp tục giảm 2% thuế VAT</h3>
<ul>
<li>Áp dụng cho các nhóm hàng hóa, dịch vụ đang chịu thuế 10% → còn <strong>8%</strong></li>
<li>Thời hạn: <strong>01/7/2025 — 31/12/2026</strong></li>
<li><strong>Không áp dụng</strong> cho: Viễn thông, tài chính, ngân hàng, chứng khoán, bảo hiểm, BĐS</li>
</ul>

<h3>2. Tăng ngưỡng doanh thu cho hộ kinh doanh</h3>
<table>
<tr><th>Nội dung</th><th>Trước</th><th>Sau sửa đổi</th></tr>
<tr><td>Ngưỡng DT không chịu VAT</td><td>200 triệu/năm</td><td><strong>500 triệu/năm</strong></td></tr>
</table>

<h3>3. Sàn thương mại điện tử kê khai thuế thay</h3>
<p>Từ <strong>01/7/2025</strong>: Các sàn TMĐT phải kê khai và nộp thuế thay cho người bán.</p>

<h3>4. Hoàn thuế & khấu trừ</h3>
<ul>
<li>Bổ sung trường hợp hoàn thuế GTGT</li>
<li>Thay đổi điều kiện khấu trừ thuế đầu vào</li>
<li>Mục tiêu: Siết chặt quản lý, tăng minh bạch</li>
</ul>

<h2>📅 Timeline</h2>
<table>
<tr><th>Thời điểm</th><th>Nội dung</th></tr>
<tr><td>01/7/2025</td><td>Giảm 2% VAT bắt đầu, sàn TMĐT kê khai thay</td></tr>
<tr><td>01/01/2026</td><td>Luật VAT sửa đổi có hiệu lực</td></tr>
<tr><td>31/12/2026</td><td>Hết hạn giảm 2% VAT</td></tr>
</table>',
            ],

            [
                'category_id' => $catThue->id,
                'title' => 'Luật Quản lý thuế sửa đổi — Bãi bỏ thuế khoán & lệ phí môn bài từ 2026',
                'excerpt' => 'Luật Quản lý thuế sửa đổi có hiệu lực 01/7/2026 với nhiều thay đổi lớn: bỏ thuế khoán, phân nhóm NNT, quản lý theo đối tượng.',
                'content' => '<h2>📋 Tổng quan</h2>
<p><strong>Luật Quản lý thuế sửa đổi</strong> có hiệu lực từ <strong>01/7/2026</strong>, đưa ra nhiều nội dung mới về quản lý thuế.</p>

<h2>🔑 Thay đổi quan trọng</h2>

<h3>1. Phân nhóm người nộp thuế</h3>
<p>Chuyển đổi từ quản lý <strong>theo chức năng</strong> sang quản lý <strong>theo đối tượng</strong> người nộp thuế kết hợp chức năng.</p>

<h3>2. Bãi bỏ thuế khoán & lệ phí môn bài</h3>
<p>Từ <strong>01/01/2026</strong>:</p>
<ul>
<li>Bãi bỏ thuế khoán đối với hộ kinh doanh</li>
<li>Bãi bỏ lệ phí môn bài đối với hộ kinh doanh</li>
</ul>

<h3>3. Phương thức kê khai mới cho hộ KD</h3>
<ul>
<li>Hộ KD & cá nhân KD kê khai, tính thuế theo <strong>tỷ lệ % trên doanh thu</strong></li>
<li>Cơ quan thuế hỗ trợ khai thuế dựa trên dữ liệu của mình + thông tin do NNT cung cấp</li>
</ul>

<h2>📅 Timeline</h2>
<table>
<tr><th>Thời điểm</th><th>Nội dung</th></tr>
<tr><td>01/01/2026</td><td>Bãi bỏ thuế khoán & lệ phí môn bài cho hộ KD</td></tr>
<tr><td>01/7/2026</td><td>Luật QL thuế sửa đổi có hiệu lực đầy đủ</td></tr>
</table>',
            ],

            // === QUẢN LÝ NHÀ NƯỚC ===
            [
                'category_id' => $catQuanLy->id,
                'title' => 'Tổng hợp thủ tục đăng ký doanh nghiệp 2025-2026 — Hướng dẫn từ A-Z',
                'excerpt' => 'Hướng dẫn chi tiết quy trình đăng ký thành lập doanh nghiệp theo quy định mới nhất, bao gồm hồ sơ, thời hạn, phí.',
                'content' => '<h2>📋 Quy trình đăng ký doanh nghiệp</h2>

<h3>Bước 1: Chuẩn bị hồ sơ</h3>
<ul>
<li>Giấy đề nghị đăng ký doanh nghiệp (theo mẫu)</li>
<li>Điều lệ công ty (đối với TNHH, CTCP)</li>
<li>Danh sách thành viên/cổ đông sáng lập</li>
<li>Bản sao giấy tờ pháp lý cá nhân (Căn cước/Hộ chiếu — <strong>không còn CMND từ 07/2025</strong>)</li>
<li><strong>Mới 2025:</strong> Thông tin chủ sở hữu hưởng lợi (nếu có)</li>
</ul>

<h3>Bước 2: Nộp hồ sơ</h3>
<p>Có 2 cách:</p>
<ul>
<li><strong>Trực tuyến:</strong> Qua Cổng thông tin quốc gia về đăng ký doanh nghiệp (<a href="https://dangkykinhdoanh.gov.vn">dangkykinhdoanh.gov.vn</a>)</li>
<li><strong>Trực tiếp:</strong> Tại Phòng Đăng ký kinh doanh - Sở KH&ĐT tỉnh/TP</li>
</ul>

<h3>Bước 3: Nhận kết quả</h3>
<ul>
<li>Thời hạn: <strong>3 ngày làm việc</strong> kể từ ngày nhận đủ hồ sơ hợp lệ</li>
<li>Nhận Giấy chứng nhận đăng ký doanh nghiệp</li>
</ul>

<h3>Bước 4: Sau đăng ký</h3>
<ul>
<li>Khắc dấu (không cần thông báo mẫu dấu)</li>
<li>Mở tài khoản ngân hàng</li>
<li>Đăng ký chữ ký số</li>
<li>Khai thuế ban đầu tại Chi cục thuế</li>
<li>Mua và phát hành hóa đơn điện tử</li>
</ul>

<h2>💡 Lưu ý quan trọng 2025-2026</h2>
<ul>
<li>Từ 01/7/2025: Phải khai thông tin chủ sở hữu hưởng lợi</li>
<li>Không được kê khai khống vốn điều lệ (vi phạm nghiêm cấm)</li>
<li>Hộ kinh doanh: Bãi bỏ thuế khoán & lệ phí môn bài từ 01/01/2026</li>
</ul>',
            ],

            [
                'category_id' => $catQuanLy->id,
                'title' => 'Timeline pháp luật doanh nghiệp & thuế 2025-2026 — Cập nhật mới nhất',
                'is_pinned' => true,
                'excerpt' => 'Lộ trình các mốc thời gian quan trọng về luật doanh nghiệp, thuế TNDN, VAT, quản lý thuế trong năm 2025-2026.',
                'content' => '<h2>📅 Timeline tổng hợp 2025 — 2026</h2>

<h3>🗓️ Năm 2025</h3>
<table>
<tr><th>Thời điểm</th><th>Văn bản</th><th>Nội dung chính</th></tr>
<tr><td><strong>01/7/2025</strong></td><td>Luật DN sửa đổi (76/2025/QH15)</td><td>Chủ sở hữu hưởng lợi, bỏ CMND, CB-CC-VC lập DN ĐMST</td></tr>
<tr><td><strong>01/7/2025</strong></td><td>Nghị quyết giảm VAT</td><td>Giảm 2% thuế VAT (10% → 8%) cho hầu hết hàng hóa/DV</td></tr>
<tr><td><strong>01/7/2025</strong></td><td>Quy định TMĐT</td><td>Sàn TMĐT kê khai & nộp thuế thay cho người bán</td></tr>
<tr><td><strong>01/10/2025</strong></td><td>Luật Thuế TNDN sửa đổi (67/2025/QH15)</td><td>Thuế suất linh hoạt 15-17-20%, mở rộng chi phí được trừ</td></tr>
<tr><td><strong>15/12/2025</strong></td><td>Nghị định 320/2025/NĐ-CP</td><td>Hướng dẫn thi hành Luật Thuế TNDN</td></tr>
</table>

<h3>🗓️ Năm 2026</h3>
<table>
<tr><th>Thời điểm</th><th>Văn bản</th><th>Nội dung chính</th></tr>
<tr><td><strong>01/01/2026</strong></td><td>Luật Thuế VAT sửa đổi (149/2025/QH15)</td><td>Ngưỡng DT hộ KD tăng lên 500 triệu, quy định hoàn thuế mới</td></tr>
<tr><td><strong>01/01/2026</strong></td><td>Thuế suất TNDN mới</td><td>Áp dụng thuế suất 15%/17%/20% theo doanh thu</td></tr>
<tr><td><strong>01/01/2026</strong></td><td>Luật QL thuế</td><td>Bãi bỏ thuế khoán & lệ phí môn bài cho hộ KD</td></tr>
<tr><td><strong>12/3/2026</strong></td><td>Thông tư 20/2026/TT-BTC</td><td>Hướng dẫn chi tiết hồ sơ chứng từ TNDN</td></tr>
<tr><td><strong>01/7/2026</strong></td><td>Luật QL thuế sửa đổi (đầy đủ)</td><td>Phân nhóm NNT, quản lý theo đối tượng</td></tr>
<tr><td><strong>31/12/2026</strong></td><td>Hết giảm 2% VAT</td><td>Kết thúc chính sách giảm VAT (nếu không được gia hạn)</td></tr>
</table>

<h2>💡 Khuyến nghị cho doanh nghiệp</h2>
<ul>
<li>✅ Cập nhật hồ sơ ĐKKD trước 01/7/2025 (thông tin chủ sở hữu hưởng lợi)</li>
<li>✅ Rà soát vốn điều lệ — đảm bảo không kê khai khống</li>
<li>✅ Tận dụng giảm 2% VAT đến hết 2026</li>
<li>✅ SMEs chuẩn bị cho thuế suất TNDN mới (có thể thấp hơn 20%)</li>
<li>✅ Tối ưu chi phí R&D, chuyển đổi số để được trừ thuế</li>
<li>✅ Hộ KD: chuyển từ thuế khoán sang kê khai % doanh thu</li>
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

        $this->command->info('✅ Seeded ' . count($articles) . ' wiki articles about Business Law & Tax');
    }
}
