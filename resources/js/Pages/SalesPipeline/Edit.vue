<template>
  <div>
    <Head :title="`${pipeline.company_name} - Quy trình`" />

    <!-- Header -->
    <div class="page-header">
      <div>
        <div class="header-top">
          <Link href="/sales-pipeline" class="back-link">
            <i class="pi pi-arrow-left" />
          </Link>
          <h1 class="page-title">{{ pipeline.company_name }}</h1>
          <span
            class="priority-badge"
            :class="`priority-${pipeline.priority}`"
          >{{ pipeline.priority }}</span>
        </div>
        <p class="page-subtitle">{{ pipeline.contact_name }} · {{ stageLabel }}</p>
      </div>
      <div class="header-actions">
        <Button
          v-if="pipeline.is_open && pipeline.stage === 'quote'"
          label="Chốt thành công"
          icon="pi pi-check-circle"
          severity="success"
          size="small"
          @click="showCloseWonDialog = true"
        />
        <Button
          v-if="pipeline.is_open"
          label="Đóng thất bại"
          icon="pi pi-times-circle"
          severity="danger"
          size="small"
          text
          @click="showCloseLostDialog = true"
        />
        <Button
          v-if="pipeline.deleted_at"
          label="Khôi phục"
          icon="pi pi-replay"
          severity="warning"
          size="small"
          @click="restore"
        />
        <Button
          v-else
          label="Xóa"
          icon="pi pi-trash"
          severity="danger"
          size="small"
          text
          @click="destroy"
        />
      </div>
    </div>

    <!-- Stage Progress Bar -->
    <div class="stage-progress">
      <div
        v-for="(label, key) in openStages"
        :key="key"
        class="stage-step"
        :class="{
          'step-active': key === pipeline.stage,
          'step-done': stageIndex(key) < stageIndex(pipeline.stage),
          'step-closed-won': pipeline.stage === 'closed_won',
          'step-closed-lost': pipeline.stage === 'closed_lost',
        }"
      >
        <div class="step-dot">
          <i v-if="stageIndex(key) < stageIndex(pipeline.stage) || pipeline.stage === 'closed_won'" class="pi pi-check" />
          <span v-else>{{ stageIndex(key) + 1 }}</span>
        </div>
        <span class="step-label">{{ label }}</span>
      </div>
      <div
        v-if="pipeline.stage === 'closed_won' || pipeline.stage === 'closed_lost'"
        class="stage-step"
        :class="{ 'step-active': true, 'step-won': pipeline.stage === 'closed_won', 'step-lost': pipeline.stage === 'closed_lost' }"
      >
        <div class="step-dot">
          <i :class="pipeline.stage === 'closed_won' ? 'pi pi-star-fill' : 'pi pi-times'" />
        </div>
        <span class="step-label">{{ pipeline.stage === 'closed_won' ? 'Thành công' : 'Thất bại' }}</span>
      </div>
    </div>

    <!-- Trashed message -->
    <div v-if="pipeline.deleted_at" class="trashed-banner">
      <i class="pi pi-exclamation-triangle" />
      <span>Quy trình này đã bị xóa.</span>
    </div>

    <!-- TabView -->
    <div class="tabs-container">
      <div class="tab-nav">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          class="tab-btn"
          :class="{ 'tab-active': activeTab === tab.key }"
          @click="activeTab = tab.key"
        >
          <i :class="tab.icon" />
          {{ tab.label }}
        </button>
      </div>

      <div class="tab-content">
        <!-- TAB: Overview -->
        <div v-if="activeTab === 'overview'" class="tab-panel">
          <form @submit.prevent="submitOverview">
            <div class="form-grid">
              <div class="form-group">
                <label>Liên kết Lead</label>
                <select v-model="overviewForm.lead_id" class="form-control">
                  <option :value="null">-- Không liên kết --</option>
                  <option v-for="lead in leads" :key="lead.id" :value="lead.id">{{ lead.name }} {{ lead.company ? `(${lead.company})` : '' }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Tên công ty <span class="required">*</span></label>
                <input v-model="overviewForm.company_name" type="text" class="form-control" />
              </div>
              <div class="form-group">
                <label>Người liên hệ <span class="required">*</span></label>
                <input v-model="overviewForm.contact_name" type="text" class="form-control" />
              </div>
              <div class="form-group">
                <label>Số điện thoại</label>
                <input v-model="overviewForm.phone" type="text" class="form-control" />
              </div>
              <div class="form-group">
                <label>Email</label>
                <input v-model="overviewForm.email" type="email" class="form-control" />
              </div>
              <div class="form-group">
                <label>Website</label>
                <input v-model="overviewForm.website_url" type="url" class="form-control" />
              </div>
              <div class="form-group">
                <label>Người phụ trách</label>
                <select v-model="overviewForm.assigned_to" class="form-control">
                  <option :value="null">-- Chọn --</option>
                  <option v-for="user in salesUsers" :key="user.id" :value="user.id">{{ user.name }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Mức ưu tiên</label>
                <div class="priority-selector">
                  <button v-for="(label, key) in priorities" :key="key" type="button"
                    class="priority-btn" :class="{ active: overviewForm.priority === key, [`btn-${key}`]: true }"
                    @click="overviewForm.priority = key">{{ label }}</button>
                </div>
              </div>
              <div class="form-group">
                <label>Kênh social</label>
                <select v-model="overviewForm.social_channel" class="form-control">
                  <option :value="null">-- Chọn --</option>
                  <option v-for="(label, key) in socialChannels" :key="key" :value="key">{{ label }}</option>
                </select>
              </div>
              <div class="form-group">
                <label>Tài khoản social</label>
                <input v-model="overviewForm.social_account" type="text" class="form-control" />
              </div>
            </div>
            <div class="form-group">
              <label>Ghi chú</label>
              <textarea v-model="overviewForm.notes" class="form-control" rows="3" />
            </div>
            <div class="tab-actions">
              <Button type="submit" label="Lưu thông tin" icon="pi pi-save" size="small" :loading="overviewForm.processing" />
            </div>
          </form>
        </div>

        <!-- TAB: Audit -->
        <div v-if="activeTab === 'audit'" class="tab-panel">
          <form @submit.prevent="submitAudit">
            <!-- Website -->
            <div class="audit-group">
              <h3 class="audit-group-title"><i class="pi pi-globe" /> Website</h3>
              <div class="audit-checklist">
                <label class="check-item"><input type="checkbox" v-model="auditForm.audit_data.website.has_website" /><span>Có website</span></label>
                <label class="check-item"><input type="checkbox" v-model="auditForm.audit_data.website.has_ssl" /><span>SSL</span></label>
                <label class="check-item"><input type="checkbox" v-model="auditForm.audit_data.website.is_responsive" /><span>Responsive</span></label>
              </div>
              <div class="form-grid form-grid-small">
                <div class="form-group"><label>Tốc độ</label><input v-model.number="auditForm.audit_data.website.speed_score" type="number" min="0" max="100" class="form-control" /></div>
                <div class="form-group"><label>SEO</label><input v-model.number="auditForm.audit_data.website.seo_score" type="number" min="0" max="100" class="form-control" /></div>
              </div>
              <div class="form-group"><label>Ghi chú</label><textarea v-model="auditForm.audit_data.website.notes" class="form-control" rows="2" /></div>
            </div>
            <!-- Marketing -->
            <div class="audit-group">
              <h3 class="audit-group-title"><i class="pi pi-megaphone" /> Marketing</h3>
              <div class="audit-checklist">
                <label class="check-item"><input type="checkbox" v-model="auditForm.audit_data.marketing.has_ads" /><span>Quảng cáo</span></label>
                <label class="check-item"><input type="checkbox" v-model="auditForm.audit_data.marketing.has_fanpage" /><span>Fanpage</span></label>
                <label class="check-item"><input type="checkbox" v-model="auditForm.audit_data.marketing.has_seo" /><span>SEO</span></label>
                <label class="check-item"><input type="checkbox" v-model="auditForm.audit_data.marketing.has_content" /><span>Nội dung</span></label>
              </div>
              <div class="form-group"><label>Link fanpage</label><input v-model="auditForm.audit_data.marketing.fanpage_url" type="url" class="form-control" /></div>
              <div class="form-group"><label>Ghi chú</label><textarea v-model="auditForm.audit_data.marketing.notes" class="form-control" rows="2" /></div>
            </div>
            <!-- Business -->
            <div class="audit-group">
              <h3 class="audit-group-title"><i class="pi pi-chart-bar" /> Kinh doanh</h3>
              <div class="form-grid">
                <div class="form-group"><label>Quy mô</label>
                  <select v-model="auditForm.audit_data.business.company_size" class="form-control">
                    <option value="">--</option><option value="1-10">1-10</option><option value="11-50">11-50</option>
                    <option value="51-200">51-200</option><option value="201-500">201-500</option><option value="500+">500+</option>
                  </select>
                </div>
                <div class="form-group"><label>Ngành</label><input v-model="auditForm.audit_data.business.industry" type="text" class="form-control" /></div>
                <div class="form-group"><label>Doanh thu</label><input v-model="auditForm.audit_data.business.estimated_revenue" type="text" class="form-control" /></div>
                <div class="form-group"><label>Đối thủ</label><input v-model="auditForm.audit_data.business.competitors" type="text" class="form-control" /></div>
              </div>
              <div class="form-group"><label>Pain points</label><textarea v-model="auditForm.audit_data.business.pain_points" class="form-control" rows="3" /></div>
              <div class="form-group"><label>Ghi chú</label><textarea v-model="auditForm.audit_data.business.notes" class="form-control" rows="2" /></div>
            </div>
            <div class="tab-actions">
              <span class="audit-score-label">Hoàn thành: {{ pipeline.audit_score }}%</span>
              <Button type="submit" label="Lưu Audit" icon="pi pi-save" size="small" :loading="auditForm.processing" />
            </div>
          </form>

          <!-- AI Analysis Section -->
          <div class="ai-section">
            <div class="ai-section-header">
              <div class="ai-header-left">
                <div class="ai-icon-wrapper">
                  <i class="pi pi-sparkles" />
                </div>
                <div>
                  <h3 class="ai-title">AI Phân tích Audit</h3>
                  <p class="ai-subtitle">Gemini AI phân tích dữ liệu audit và đưa ra đánh giá chi tiết</p>
                </div>
              </div>
              <div class="ai-header-actions">
                <Button
                  label="Phân tích bằng AI"
                  icon="pi pi-sparkles"
                  :loading="aiAnalyzing"
                  size="small"
                  class="ai-analyze-btn"
                  @click="runAiAnalysis"
                />
                <Button
                  label="AI tạo đề xuất"
                  icon="pi pi-file-edit"
                  :loading="aiGeneratingProposal"
                  size="small"
                  severity="secondary"
                  @click="runAiProposal"
                />
              </div>
            </div>

            <!-- Loading State -->
            <div v-if="aiAnalyzing" class="ai-loading">
              <div class="ai-loading-animation">
                <div class="ai-dot" />
                <div class="ai-dot" />
                <div class="ai-dot" />
              </div>
              <p class="ai-loading-text">AI đang phân tích dữ liệu audit...</p>
            </div>

            <!-- Analysis Result -->
            <div v-if="aiAnalysis && !aiAnalyzing" class="ai-result">
              <!-- Overall Score -->
              <div class="ai-score-overview">
                <div class="ai-score-circle" :class="getAiScoreClass(aiAnalysis.overall_score)">
                  <span class="ai-score-number">{{ aiAnalysis.overall_score }}</span>
                  <span class="ai-score-total">/100</span>
                </div>
                <div class="ai-score-info">
                  <span class="ai-rating-badge" :class="getAiScoreClass(aiAnalysis.overall_score)">
                    {{ aiAnalysis.overall_rating }}
                  </span>
                  <p class="ai-summary">{{ aiAnalysis.summary }}</p>
                </div>
              </div>

              <!-- Breakdown Cards -->
              <div class="ai-breakdown-grid">
                <!-- Website -->
                <div class="ai-breakdown-card">
                  <div class="ai-card-header">
                    <i class="pi pi-globe" />
                    <span>Website</span>
                    <span class="ai-card-score" :class="getAiScoreClass(aiAnalysis.website_analysis?.score)">
                      {{ aiAnalysis.website_analysis?.score || 0 }}/100
                    </span>
                  </div>
                  <div v-if="aiAnalysis.website_analysis?.strengths?.length" class="ai-list-section">
                    <span class="ai-list-label good">Điểm mạnh</span>
                    <ul class="ai-list">
                      <li v-for="(s, i) in aiAnalysis.website_analysis.strengths" :key="'ws'+i">
                        <i class="pi pi-check-circle" /> {{ s }}
                      </li>
                    </ul>
                  </div>
                  <div v-if="aiAnalysis.website_analysis?.weaknesses?.length" class="ai-list-section">
                    <span class="ai-list-label bad">Điểm yếu</span>
                    <ul class="ai-list">
                      <li v-for="(w, i) in aiAnalysis.website_analysis.weaknesses" :key="'ww'+i">
                        <i class="pi pi-exclamation-circle" /> {{ w }}
                      </li>
                    </ul>
                  </div>
                  <div v-if="aiAnalysis.website_analysis?.recommendations?.length" class="ai-list-section">
                    <span class="ai-list-label neutral">Đề xuất</span>
                    <ul class="ai-list">
                      <li v-for="(r, i) in aiAnalysis.website_analysis.recommendations" :key="'wr'+i">
                        <i class="pi pi-arrow-right" /> {{ r }}
                      </li>
                    </ul>
                  </div>
                </div>

                <!-- Marketing -->
                <div class="ai-breakdown-card">
                  <div class="ai-card-header">
                    <i class="pi pi-megaphone" />
                    <span>Marketing</span>
                    <span class="ai-card-score" :class="getAiScoreClass(aiAnalysis.marketing_analysis?.score)">
                      {{ aiAnalysis.marketing_analysis?.score || 0 }}/100
                    </span>
                  </div>
                  <div v-if="aiAnalysis.marketing_analysis?.strengths?.length" class="ai-list-section">
                    <span class="ai-list-label good">Điểm mạnh</span>
                    <ul class="ai-list">
                      <li v-for="(s, i) in aiAnalysis.marketing_analysis.strengths" :key="'ms'+i">
                        <i class="pi pi-check-circle" /> {{ s }}
                      </li>
                    </ul>
                  </div>
                  <div v-if="aiAnalysis.marketing_analysis?.weaknesses?.length" class="ai-list-section">
                    <span class="ai-list-label bad">Điểm yếu</span>
                    <ul class="ai-list">
                      <li v-for="(w, i) in aiAnalysis.marketing_analysis.weaknesses" :key="'mw'+i">
                        <i class="pi pi-exclamation-circle" /> {{ w }}
                      </li>
                    </ul>
                  </div>
                  <div v-if="aiAnalysis.marketing_analysis?.recommendations?.length" class="ai-list-section">
                    <span class="ai-list-label neutral">Đề xuất</span>
                    <ul class="ai-list">
                      <li v-for="(r, i) in aiAnalysis.marketing_analysis.recommendations" :key="'mr'+i">
                        <i class="pi pi-arrow-right" /> {{ r }}
                      </li>
                    </ul>
                  </div>
                </div>

                <!-- Business -->
                <div class="ai-breakdown-card">
                  <div class="ai-card-header">
                    <i class="pi pi-chart-bar" />
                    <span>Kinh doanh</span>
                    <span class="ai-card-score" :class="getAiScoreClass(aiAnalysis.business_analysis?.score)">
                      {{ aiAnalysis.business_analysis?.score || 0 }}/100
                    </span>
                  </div>
                  <div v-if="aiAnalysis.business_analysis?.opportunities?.length" class="ai-list-section">
                    <span class="ai-list-label good">Cơ hội</span>
                    <ul class="ai-list">
                      <li v-for="(o, i) in aiAnalysis.business_analysis.opportunities" :key="'bo'+i">
                        <i class="pi pi-check-circle" /> {{ o }}
                      </li>
                    </ul>
                  </div>
                  <div v-if="aiAnalysis.business_analysis?.threats?.length" class="ai-list-section">
                    <span class="ai-list-label bad">Thách thức</span>
                    <ul class="ai-list">
                      <li v-for="(t, i) in aiAnalysis.business_analysis.threats" :key="'bt'+i">
                        <i class="pi pi-exclamation-triangle" /> {{ t }}
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Priority Actions -->
              <div v-if="aiAnalysis.priority_actions?.length" class="ai-actions-table">
                <h4 class="ai-actions-title"><i class="pi pi-bolt" /> Hành động ưu tiên</h4>
                <table class="ai-table">
                  <thead>
                    <tr>
                      <th>Hành động</th>
                      <th>Tác động</th>
                      <th>Độ khó</th>
                      <th>Thời gian</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(action, i) in aiAnalysis.priority_actions" :key="'action'+i">
                      <td>{{ action.action }}</td>
                      <td><span class="impact-badge" :class="`impact-${action.impact}`">{{ action.impact }}</span></td>
                      <td><span class="effort-tag">{{ action.effort }}</span></td>
                      <td class="timeline-cell">{{ action.timeline }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Budget & ROI -->
              <div v-if="aiAnalysis.estimated_budget_range || aiAnalysis.potential_roi" class="ai-footer-info">
                <div v-if="aiAnalysis.estimated_budget_range" class="ai-footer-item">
                  <i class="pi pi-wallet" />
                  <div>
                    <span class="ai-footer-label">Ngân sách đề xuất</span>
                    <span class="ai-footer-value">{{ aiAnalysis.estimated_budget_range }}</span>
                  </div>
                </div>
                <div v-if="aiAnalysis.potential_roi" class="ai-footer-item">
                  <i class="pi pi-chart-line" />
                  <div>
                    <span class="ai-footer-label">ROI kỳ vọng</span>
                    <span class="ai-footer-value">{{ aiAnalysis.potential_roi }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- AI Proposal Result -->
            <div v-if="aiProposalText && !aiGeneratingProposal" class="ai-proposal-result">
              <div class="ai-proposal-header">
                <h4><i class="pi pi-file-edit" /> Đề xuất giải pháp (AI Generated)</h4>
                <Button label="Dùng làm Proposal" icon="pi pi-arrow-right" size="small" severity="success" @click="useAiProposal" />
              </div>
              <div class="ai-proposal-content">
                <pre>{{ aiProposalText }}</pre>
              </div>
            </div>

            <!-- Proposal Loading -->
            <div v-if="aiGeneratingProposal" class="ai-loading">
              <div class="ai-loading-animation">
                <div class="ai-dot" /><div class="ai-dot" /><div class="ai-dot" />
              </div>
              <p class="ai-loading-text">AI đang tạo đề xuất giải pháp...</p>
            </div>
          </div>
        </div>

        <!-- TAB: Proposal -->
        <div v-if="activeTab === 'propose'" class="tab-panel">
          <form @submit.prevent="submitProposal">
            <div class="form-group">
              <label>Giải pháp đề xuất</label>
              <textarea v-model="proposeForm.proposal_summary" class="form-control" rows="8" placeholder="Mô tả giải pháp bạn đề xuất cho khách hàng..." />
            </div>
            <div class="form-group">
              <label>Ghi chú trao đổi</label>
              <textarea v-model="proposeForm.discussion_notes" class="form-control" rows="5" placeholder="Nội dung trao đổi với khách hàng..." />
            </div>
            <div class="tab-actions">
              <Button type="submit" label="Lưu giải pháp" icon="pi pi-save" size="small" :loading="proposeForm.processing" />
            </div>
          </form>
        </div>

        <!-- TAB: Quote -->
        <div v-if="activeTab === 'quote'" class="tab-panel">
          <form @submit.prevent="submitQuote">
            <div class="form-grid">
              <div class="form-group">
                <label>Số tiền báo giá <span class="required">*</span></label>
                <input v-model.number="quoteForm.quote_amount" type="number" min="0" class="form-control" placeholder="0" />
              </div>
              <div class="form-group">
                <label>Hiệu lực đến</label>
                <input v-model="quoteForm.quote_valid_until" type="date" class="form-control" />
              </div>
            </div>
            <div class="form-group">
              <label>Ghi chú báo giá</label>
              <textarea v-model="quoteForm.quote_notes" class="form-control" rows="4" placeholder="Chi tiết báo giá, hạng mục..." />
            </div>
            <div v-if="pipeline.quote_amount" class="quote-summary">
              <div class="quote-amount">{{ formatCurrency(pipeline.quote_amount) }}</div>
              <div v-if="pipeline.quote_valid_until" class="quote-valid">Hiệu lực đến: {{ pipeline.quote_valid_until }}</div>
            </div>
            <div class="tab-actions">
              <Button type="submit" label="Lưu báo giá" icon="pi pi-save" size="small" :loading="quoteForm.processing" />
            </div>
          </form>
        </div>

        <!-- TAB: History -->
        <div v-if="activeTab === 'history'" class="tab-panel">
          <!-- Add activity form -->
          <form @submit.prevent="submitActivity" class="activity-form">
            <div class="form-grid">
              <div class="form-group">
                <select v-model="activityForm.type" class="form-control">
                  <option value="call">📞 Gọi điện</option>
                  <option value="email">✉️ Email</option>
                  <option value="meeting">📅 Họp</option>
                  <option value="note">📝 Ghi chú</option>
                </select>
              </div>
              <div class="form-group">
                <input v-model="activityForm.title" type="text" class="form-control" placeholder="Tiêu đề..." />
              </div>
            </div>
            <div class="form-group">
              <textarea v-model="activityForm.description" class="form-control" rows="2" placeholder="Mô tả..." />
            </div>
            <div class="tab-actions">
              <Button type="submit" label="Thêm hoạt động" icon="pi pi-plus" size="small" :loading="activityForm.processing" />
            </div>
          </form>

          <!-- Activity timeline -->
          <div class="timeline">
            <div v-for="act in activities" :key="act.id" class="timeline-item">
              <div class="timeline-dot" :class="`dot-${act.type}`">
                <i :class="getActivityIcon(act.type)" />
              </div>
              <div class="timeline-content">
                <div class="timeline-header">
                  <span class="timeline-title">{{ act.title }}</span>
                  <span class="timeline-date">{{ formatDate(act.date) }}</span>
                </div>
                <p v-if="act.description" class="timeline-desc">{{ act.description }}</p>
                <span v-if="act.user" class="timeline-user">{{ act.user.name }}</span>
              </div>
            </div>
            <div v-if="activities.length === 0" class="timeline-empty">
              <i class="pi pi-clock" />
              <span>Chưa có hoạt động nào</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Close Won Dialog -->
    <Dialog v-model:visible="showCloseWonDialog" header="Chốt deal thành công" :modal="true" :style="{ width: '450px' }">
      <div class="form-group">
        <label>Ghi chú</label>
        <textarea v-model="closeWonForm.close_notes" class="form-control" rows="3" placeholder="Ghi chú chốt deal..." />
      </div>
      <template #footer>
        <Button label="Hủy" severity="secondary" text @click="showCloseWonDialog = false" />
        <Button label="Chốt thành công" icon="pi pi-check" severity="success" :loading="closeWonForm.processing" @click="submitCloseWon" />
      </template>
    </Dialog>

    <!-- Close Lost Dialog -->
    <Dialog v-model:visible="showCloseLostDialog" header="Đóng deal thất bại" :modal="true" :style="{ width: '450px' }">
      <div class="form-group">
        <label>Lý do thất bại <span class="required">*</span></label>
        <textarea v-model="closeLostForm.lost_reason" class="form-control" rows="2" placeholder="Lý do..." />
      </div>
      <div class="form-group">
        <label>Ghi chú</label>
        <textarea v-model="closeLostForm.close_notes" class="form-control" rows="2" />
      </div>
      <template #footer>
        <Button label="Hủy" severity="secondary" text @click="showCloseLostDialog = false" />
        <Button label="Đóng thất bại" icon="pi pi-times" severity="danger" :loading="closeLostForm.processing" @click="submitCloseLost" />
      </template>
    </Dialog>
  </div>
</template>

<script>
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'

export default {
  components: { Head, Link, Button, Dialog },
  layout: Layout,
  props: {
    pipeline: Object,
    activities: Array,
    stages: Object,
    allStages: Object,
    priorities: Object,
    socialChannels: Object,
    salesUsers: Array,
    leads: Array,
  },
  data() {
    return {
      activeTab: 'overview',
      showCloseWonDialog: false,
      showCloseLostDialog: false,
      aiAnalyzing: false,
      aiAnalysis: this.pipeline.audit_data?.ai_analysis ? this.parseAiAnalysis(this.pipeline.audit_data.ai_analysis) : null,
      aiGeneratingProposal: false,
      aiProposalText: null,
    }
  },
  computed: {
    tabs() {
      return [
        { key: 'overview', label: 'Tổng quan', icon: 'pi pi-info-circle' },
        { key: 'audit', label: 'Audit', icon: 'pi pi-search' },
        { key: 'propose', label: 'Giải pháp', icon: 'pi pi-file-edit' },
        { key: 'quote', label: 'Báo giá', icon: 'pi pi-dollar' },
        { key: 'history', label: 'Lịch sử', icon: 'pi pi-clock' },
      ]
    },
    openStages() {
      const stages = { ...this.allStages }
      delete stages['closed_won']
      delete stages['closed_lost']
      return stages
    },
    stageLabel() {
      return this.allStages[this.pipeline.stage] || this.pipeline.stage
    },
  },
  setup(props) {
    const overviewForm = useForm({
      lead_id: props.pipeline.lead_id,
      company_name: props.pipeline.company_name,
      contact_name: props.pipeline.contact_name,
      phone: props.pipeline.phone || '',
      email: props.pipeline.email || '',
      website_url: props.pipeline.website_url || '',
      assigned_to: props.pipeline.assigned_to,
      social_channel: props.pipeline.social_channel,
      social_account: props.pipeline.social_account || '',
      priority: props.pipeline.priority || 'warm',
      notes: props.pipeline.notes || '',
    })

    const auditForm = useForm({
      audit_data: JSON.parse(JSON.stringify(props.pipeline.audit_data)),
    })

    const proposeForm = useForm({
      proposal_summary: props.pipeline.proposal_summary || '',
      discussion_notes: props.pipeline.discussion_notes || '',
    })

    const quoteForm = useForm({
      quote_amount: props.pipeline.quote_amount || null,
      quote_valid_until: props.pipeline.quote_valid_until || '',
      quote_notes: props.pipeline.quote_notes || '',
    })

    const activityForm = useForm({
      type: 'note',
      title: '',
      description: '',
      subject_type: 'App\\Models\\SalesPipeline',
      subject_id: props.pipeline.id,
      date: new Date().toISOString().substr(0, 10),
    })

    const closeWonForm = useForm({
      close_notes: '',
    })

    const closeLostForm = useForm({
      lost_reason: '',
      close_notes: '',
    })

    return { overviewForm, auditForm, proposeForm, quoteForm, activityForm, closeWonForm, closeLostForm }
  },
  methods: {
    stageIndex(stageKey) {
      const keys = Object.keys(this.openStages)
      return keys.indexOf(stageKey)
    },
    formatCurrency(value) {
      return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 }).format(value)
    },
    formatDate(d) {
      return new Date(d).toLocaleDateString('vi-VN', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
    },
    getActivityIcon(type) {
      return { call: 'pi pi-phone', email: 'pi pi-envelope', meeting: 'pi pi-calendar', note: 'pi pi-file-edit' }[type] || 'pi pi-circle'
    },
    submitOverview() {
      this.overviewForm.put(`/sales-pipeline/${this.pipeline.id}`)
    },
    submitAudit() {
      this.auditForm.post(`/sales-pipeline/${this.pipeline.id}/audit`)
    },
    submitProposal() {
      this.proposeForm.put(`/sales-pipeline/${this.pipeline.id}`, {
        preserveScroll: true,
      })
    },
    submitQuote() {
      this.quoteForm.post(`/sales-pipeline/${this.pipeline.id}/quote`)
    },
    submitActivity() {
      this.activityForm.post('/activities', {
        preserveScroll: true,
        onSuccess: () => {
          this.activityForm.reset('title', 'description')
        },
      })
    },
    submitCloseWon() {
      this.closeWonForm.post(`/sales-pipeline/${this.pipeline.id}/close-won`)
    },
    submitCloseLost() {
      this.closeLostForm.post(`/sales-pipeline/${this.pipeline.id}/close-lost`)
    },
    destroy() {
      if (confirm('Bạn có chắc muốn xóa quy trình này?')) {
        router.delete(`/sales-pipeline/${this.pipeline.id}`)
      }
    },
    restore() {
      router.put(`/sales-pipeline/${this.pipeline.id}/restore`)
    },
    // ── AI Methods ──
    async runAiAnalysis() {
      this.aiAnalyzing = true
      this.aiAnalysis = null
      try {
        const response = await fetch(`/sales-pipeline/${this.pipeline.id}/ai-analyze`, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
        })
        const data = await response.json()
        if (data.success && data.analysis) {
          this.aiAnalysis = this.parseAiAnalysis(data)
        }
      } catch (error) {
        console.error('AI analysis failed:', error)
      } finally {
        this.aiAnalyzing = false
      }
    },
    async runAiProposal() {
      this.aiGeneratingProposal = true
      this.aiProposalText = null
      try {
        const response = await fetch(`/sales-pipeline/${this.pipeline.id}/ai-proposal`, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
        })
        const data = await response.json()
        if (data.success && data.proposal) {
          this.aiProposalText = data.proposal
        }
      } catch (error) {
        console.error('AI proposal failed:', error)
      } finally {
        this.aiGeneratingProposal = false
      }
    },
    useAiProposal() {
      if (this.aiProposalText) {
        this.proposeForm.proposal_summary = this.aiProposalText
        this.activeTab = 'propose'
      }
    },
    parseAiAnalysis(data) {
      if (!data) return null
      let analysis = data.analysis || data
      // If analysis is a string (JSON from Gemini), parse it
      if (typeof analysis === 'string') {
        try {
          // Gemini sometimes wraps JSON in ```json ... ```
          const cleaned = analysis.replace(/```json\n?/g, '').replace(/```\n?/g, '').trim()
          analysis = JSON.parse(cleaned)
        } catch (e) {
          console.warn('Could not parse AI analysis JSON:', e)
          return null
        }
      }
      return analysis
    },
    getAiScoreClass(score) {
      if (!score && score !== 0) return 'score-unknown'
      if (score >= 80) return 'score-excellent'
      if (score >= 60) return 'score-good'
      if (score >= 40) return 'score-average'
      return 'score-weak'
    },
  },
}
</script>

<style scoped>
.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 1rem;
}
.header-top { display: flex; align-items: center; gap: 0.75rem; }
.back-link {
  width: 32px; height: 32px; border-radius: 8px; background: #f1f5f9;
  display: flex; align-items: center; justify-content: center;
  color: #64748b; text-decoration: none; transition: all 0.2s;
}
.back-link:hover { background: #e2e8f0; color: #1e293b; }
.page-title { font-size: 1.35rem; font-weight: 700; color: #0f172a; margin: 0; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0.15rem 0 0 2.5rem; }
.header-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }

.priority-badge {
  font-size: 0.6rem; font-weight: 700; text-transform: uppercase;
  padding: 0.15rem 0.45rem; border-radius: 6px; letter-spacing: 0.04em;
}
.priority-hot { background: #fef2f2; color: #ef4444; }
.priority-warm { background: #fffbeb; color: #f59e0b; }
.priority-cold { background: #eff6ff; color: #3b82f6; }

/* Stage Progress */
.stage-progress {
  display: flex;
  align-items: center;
  gap: 0;
  margin-bottom: 1.25rem;
  background: white;
  border-radius: 12px;
  padding: 0.75rem 1rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  border: 1px solid #f1f5f9;
  overflow-x: auto;
}
.stage-step {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  flex: 1;
  position: relative;
  padding-right: 1rem;
}
.stage-step::after {
  content: '';
  position: absolute;
  right: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 20px;
  height: 2px;
  background: #e2e8f0;
}
.stage-step:last-child::after { display: none; }
.step-dot {
  width: 26px; height: 26px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.65rem; font-weight: 700; flex-shrink: 0;
  background: #f1f5f9; color: #94a3b8; border: 2px solid #e2e8f0;
  transition: all 0.3s;
}
.step-dot i { font-size: 0.6rem; }
.step-label { font-size: 0.7rem; color: #94a3b8; white-space: nowrap; }

.step-done .step-dot { background: #ecfdf5; color: #10b981; border-color: #10b981; }
.step-done .step-label { color: #10b981; }
.step-active .step-dot { background: #6366f1; color: white; border-color: #6366f1; transform: scale(1.1); }
.step-active .step-label { color: #6366f1; font-weight: 600; }
.step-won .step-dot { background: #10b981; color: white; border-color: #10b981; }
.step-won .step-label { color: #10b981; font-weight: 600; }
.step-lost .step-dot { background: #ef4444; color: white; border-color: #ef4444; }
.step-lost .step-label { color: #ef4444; font-weight: 600; }

.trashed-banner {
  background: #fef2f2; border: 1px solid #fecaca; color: #dc2626;
  padding: 0.75rem 1rem; border-radius: 10px; margin-bottom: 1rem;
  display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem;
}

/* Tabs */
.tabs-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  border: 1px solid #f1f5f9;
  overflow: hidden;
}
.tab-nav {
  display: flex;
  border-bottom: 1px solid #f1f5f9;
  overflow-x: auto;
}
.tab-btn {
  padding: 0.75rem 1.25rem;
  font-size: 0.82rem;
  font-weight: 500;
  color: #64748b;
  background: none;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  border-bottom: 2px solid transparent;
  transition: all 0.2s;
  white-space: nowrap;
}
.tab-btn:hover { color: #1e293b; background: #f8fafc; }
.tab-btn.tab-active {
  color: #6366f1;
  border-bottom-color: #6366f1;
  font-weight: 600;
}
.tab-btn i { font-size: 0.85rem; }
.tab-content { padding: 1.5rem; }

/* Form styling reuse */
.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
.form-grid-small { grid-template-columns: repeat(2, 1fr); gap: 0.75rem; margin-bottom: 0.75rem; }
.form-group { margin-bottom: 0.75rem; }
.form-group label { display: block; font-size: 0.78rem; font-weight: 500; color: #475569; margin-bottom: 0.35rem; }
.required { color: #ef4444; }

.form-control {
  width: 100%; padding: 0.55rem 0.75rem;
  border: 1px solid #e2e8f0; border-radius: 8px;
  font-size: 0.85rem; color: #1e293b; background: white;
  transition: all 0.2s; outline: none; font-family: inherit;
}
.form-control:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
select.form-control { cursor: pointer; }
textarea.form-control { resize: vertical; min-height: 60px; }

.tab-actions { display: flex; justify-content: flex-end; align-items: center; gap: 0.75rem; padding-top: 0.75rem; }

/* Priority selector */
.priority-selector { display: flex; gap: 0.5rem; }
.priority-btn {
  flex: 1; padding: 0.4rem 0.5rem; border-radius: 8px;
  font-size: 0.75rem; font-weight: 600; border: 2px solid #e2e8f0;
  background: white; cursor: pointer; transition: all 0.2s;
}
.priority-btn:hover { border-color: #cbd5e1; }
.priority-btn.active.btn-hot { border-color: #ef4444; background: #fef2f2; color: #ef4444; }
.priority-btn.active.btn-warm { border-color: #f59e0b; background: #fffbeb; color: #f59e0b; }
.priority-btn.active.btn-cold { border-color: #3b82f6; background: #eff6ff; color: #3b82f6; }

/* Audit */
.audit-group {
  background: #f8fafc; border-radius: 10px;
  padding: 1.25rem; margin-bottom: 1rem; border: 1px solid #f1f5f9;
}
.audit-group-title {
  font-size: 0.88rem; font-weight: 600; color: #334155;
  margin: 0 0 0.75rem; display: flex; align-items: center; gap: 0.4rem;
}
.audit-group-title i { color: #6366f1; }
.audit-checklist { display: flex; flex-wrap: wrap; gap: 0.6rem; margin-bottom: 0.75rem; }
.check-item {
  display: flex; align-items: center; gap: 0.4rem;
  font-size: 0.78rem; color: #475569; cursor: pointer;
  padding: 0.3rem 0.6rem; border-radius: 8px;
  background: white; border: 1px solid #e2e8f0; transition: all 0.15s;
}
.check-item:hover { border-color: #6366f1; }
.check-item input[type="checkbox"] { accent-color: #6366f1; cursor: pointer; }
.audit-score-label { font-size: 0.78rem; color: #64748b; font-weight: 500; }

/* Quote summary */
.quote-summary {
  background: #ecfdf5; border-radius: 10px; padding: 1rem;
  margin-bottom: 0.75rem; border: 1px solid #d1fae5;
}
.quote-amount { font-size: 1.5rem; font-weight: 700; color: #10b981; }
.quote-valid { font-size: 0.78rem; color: #6b7280; margin-top: 0.25rem; }

/* Activity */
.activity-form {
  background: #f8fafc; border-radius: 10px; padding: 1rem;
  margin-bottom: 1.5rem; border: 1px solid #f1f5f9;
}

/* Timeline */
.timeline { position: relative; padding-left: 2rem; }
.timeline::before {
  content: ''; position: absolute; left: 12px; top: 0; bottom: 0;
  width: 2px; background: #f1f5f9;
}
.timeline-item {
  position: relative; padding-bottom: 1.25rem; display: flex; gap: 0.75rem;
}
.timeline-dot {
  width: 26px; height: 26px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; position: absolute; left: -2rem;
  background: white; border: 2px solid #e2e8f0;
}
.timeline-dot i { font-size: 0.6rem; }
.dot-call { border-color: #3b82f6; color: #3b82f6; }
.dot-email { border-color: #10b981; color: #10b981; }
.dot-meeting { border-color: #8b5cf6; color: #8b5cf6; }
.dot-note { border-color: #94a3b8; color: #94a3b8; }

.timeline-content { flex: 1; }
.timeline-header { display: flex; justify-content: space-between; align-items: center; }
.timeline-title { font-size: 0.85rem; font-weight: 600; color: #1e293b; }
.timeline-date { font-size: 0.7rem; color: #94a3b8; }
.timeline-desc { font-size: 0.78rem; color: #64748b; margin: 0.25rem 0 0; }
.timeline-user { font-size: 0.7rem; color: #94a3b8; }
.timeline-empty {
  display: flex; flex-direction: column; align-items: center;
  gap: 0.5rem; color: #cbd5e1; padding: 2rem; font-size: 0.85rem;
}
.timeline-empty i { font-size: 1.5rem; }

@media (max-width: 640px) {
  .form-grid { grid-template-columns: 1fr; }
  .header-actions { flex-direction: column; }
  .stage-progress { gap: 0.25rem; }
  .tab-btn { padding: 0.6rem 0.8rem; font-size: 0.75rem; }
  .ai-breakdown-grid { grid-template-columns: 1fr; }
  .ai-score-overview { flex-direction: column; text-align: center; }
  .ai-section-header { flex-direction: column; gap: 0.75rem; }
  .ai-header-actions { width: 100%; }
  .ai-footer-info { flex-direction: column; }
}

/* ===== AI Section ===== */
.ai-section {
  margin-top: 1.5rem;
  background: linear-gradient(135deg, #f5f3ff 0%, #eef2ff 50%, #faf5ff 100%);
  border-radius: 14px;
  border: 1px solid #e0e7ff;
  overflow: hidden;
}
.ai-section-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1rem 1.25rem;
  background: linear-gradient(135deg, rgba(99,102,241,0.06), rgba(139,92,246,0.06));
  border-bottom: 1px solid #e0e7ff;
}
.ai-header-left { display: flex; align-items: center; gap: 0.75rem; }
.ai-icon-wrapper {
  width: 40px; height: 40px; border-radius: 12px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1rem;
  animation: aiPulse 2s ease-in-out infinite;
}
@keyframes aiPulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(99,102,241,0.3); }
  50% { box-shadow: 0 0 0 8px rgba(99,102,241,0); }
}
.ai-title { font-size: 0.92rem; font-weight: 700; color: #1e293b; margin: 0; }
.ai-subtitle { font-size: 0.72rem; color: #6366f1; margin: 0.1rem 0 0; }
.ai-header-actions { display: flex; gap: 0.5rem; }
.ai-analyze-btn {
  background: linear-gradient(135deg, #6366f1, #8b5cf6) !important;
  border: none !important;
}

/* AI Loading */
.ai-loading {
  display: flex; flex-direction: column; align-items: center; gap: 0.75rem;
  padding: 2.5rem; text-align: center;
}
.ai-loading-animation { display: flex; gap: 0.4rem; }
.ai-dot {
  width: 10px; height: 10px; border-radius: 50%;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  animation: aiBounce 1.4s ease-in-out infinite;
}
.ai-dot:nth-child(2) { animation-delay: 0.2s; }
.ai-dot:nth-child(3) { animation-delay: 0.4s; }
@keyframes aiBounce {
  0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
  40% { transform: scale(1); opacity: 1; }
}
.ai-loading-text { font-size: 0.82rem; color: #6366f1; font-weight: 500; }

/* AI Result */
.ai-result { padding: 1.25rem; }

/* Score Overview */
.ai-score-overview {
  display: flex; align-items: center; gap: 1.25rem;
  padding: 1.25rem; background: white; border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 1.25rem;
}
.ai-score-circle {
  width: 80px; height: 80px; border-radius: 50%;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  border: 4px solid; flex-shrink: 0; transition: all 0.3s;
}
.ai-score-circle.score-excellent { border-color: #10b981; background: #ecfdf5; }
.ai-score-circle.score-good { border-color: #3b82f6; background: #eff6ff; }
.ai-score-circle.score-average { border-color: #f59e0b; background: #fffbeb; }
.ai-score-circle.score-weak { border-color: #ef4444; background: #fef2f2; }
.ai-score-number { font-size: 1.5rem; font-weight: 800; line-height: 1; color: #1e293b; }
.ai-score-total { font-size: 0.6rem; color: #94a3b8; }
.ai-score-info { flex: 1; }
.ai-rating-badge {
  display: inline-block; font-size: 0.7rem; font-weight: 700;
  padding: 0.15rem 0.5rem; border-radius: 6px; text-transform: uppercase;
  letter-spacing: 0.04em;
}
.ai-rating-badge.score-excellent { background: #d1fae5; color: #059669; }
.ai-rating-badge.score-good { background: #dbeafe; color: #2563eb; }
.ai-rating-badge.score-average { background: #fef3c7; color: #d97706; }
.ai-rating-badge.score-weak { background: #fee2e2; color: #dc2626; }
.ai-summary { font-size: 0.82rem; color: #475569; margin: 0.5rem 0 0; line-height: 1.5; }

/* Breakdown Grid */
.ai-breakdown-grid {
  display: grid; grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem; margin-bottom: 1.25rem;
}
.ai-breakdown-card {
  background: white; border-radius: 10px;
  box-shadow: 0 1px 2px rgba(0,0,0,0.04);
  overflow: hidden; border: 1px solid #f1f5f9;
  transition: transform 0.2s, box-shadow 0.2s;
}
.ai-breakdown-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
.ai-card-header {
  display: flex; align-items: center; gap: 0.35rem;
  padding: 0.7rem 0.85rem; background: #fafbfc;
  border-bottom: 1px solid #f1f5f9;
  font-size: 0.78rem; font-weight: 600; color: #334155;
}
.ai-card-header i { color: #6366f1; font-size: 0.75rem; }
.ai-card-score { margin-left: auto; font-size: 0.65rem; font-weight: 700; padding: 0.1rem 0.3rem; border-radius: 4px; }
.ai-card-score.score-excellent { background: #d1fae5; color: #059669; }
.ai-card-score.score-good { background: #dbeafe; color: #2563eb; }
.ai-card-score.score-average { background: #fef3c7; color: #d97706; }
.ai-card-score.score-weak { background: #fee2e2; color: #dc2626; }

.ai-list-section { padding: 0.6rem 0.85rem; }
.ai-list-section + .ai-list-section { border-top: 1px solid #f8fafc; }
.ai-list-label {
  font-size: 0.6rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: 0.06em; margin-bottom: 0.35rem; display: block;
}
.ai-list-label.good { color: #10b981; }
.ai-list-label.bad { color: #ef4444; }
.ai-list-label.neutral { color: #6366f1; }

.ai-list { list-style: none; padding: 0; margin: 0; }
.ai-list li {
  font-size: 0.75rem; color: #475569; line-height: 1.5;
  display: flex; align-items: flex-start; gap: 0.3rem;
  padding: 0.15rem 0;
}
.ai-list li i { font-size: 0.6rem; margin-top: 0.25rem; flex-shrink: 0; }
.ai-list li .pi-check-circle { color: #10b981; }
.ai-list li .pi-exclamation-circle { color: #ef4444; }
.ai-list li .pi-exclamation-triangle { color: #f59e0b; }
.ai-list li .pi-arrow-right { color: #6366f1; }

/* Actions Table */
.ai-actions-table {
  background: white; border-radius: 10px;
  padding: 1rem; margin-bottom: 1rem;
  box-shadow: 0 1px 2px rgba(0,0,0,0.04);
  border: 1px solid #f1f5f9;
}
.ai-actions-title {
  font-size: 0.78rem; font-weight: 700; color: #1e293b;
  margin: 0 0 0.75rem; display: flex; align-items: center; gap: 0.3rem;
}
.ai-actions-title i { color: #f59e0b; }
.ai-table { width: 100%; border-collapse: collapse; }
.ai-table th {
  font-size: 0.65rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: 0.06em; color: #94a3b8;
  padding: 0.4rem 0.6rem; text-align: left;
  border-bottom: 1px solid #f1f5f9;
}
.ai-table td {
  font-size: 0.78rem; color: #334155; padding: 0.55rem 0.6rem;
  border-bottom: 1px solid #f8fafc;
}
.ai-table tr:last-child td { border: none; }
.impact-badge {
  font-size: 0.6rem; font-weight: 700; padding: 0.1rem 0.35rem;
  border-radius: 4px; text-transform: capitalize;
}
.impact-cao, .impact-high { background: #fef2f2; color: #ef4444; }
.impact-trung, .impact-medium { background: #fef3c7; color: #d97706; }
.impact-thấp, .impact-low { background: #dbeafe; color: #3b82f6; }
.effort-tag { font-size: 0.72rem; color: #64748b; font-style: italic; }
.timeline-cell { font-size: 0.72rem; color: #6366f1; font-weight: 500; white-space: nowrap; }

/* Footer Info */
.ai-footer-info {
  display: flex; gap: 1rem; padding: 1rem;
  background: white; border-radius: 10px;
  box-shadow: 0 1px 2px rgba(0,0,0,0.04);
  border: 1px solid #f1f5f9;
}
.ai-footer-item {
  flex: 1; display: flex; align-items: center; gap: 0.65rem;
  padding: 0.75rem; background: #f8fafc; border-radius: 8px;
}
.ai-footer-item i { font-size: 1.1rem; color: #6366f1; }
.ai-footer-label { font-size: 0.65rem; color: #94a3b8; display: block; }
.ai-footer-value { font-size: 0.92rem; font-weight: 700; color: #1e293b; }

/* AI Proposal */
.ai-proposal-result {
  margin-top: 1rem; background: white; border-radius: 10px;
  overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.04);
  border: 1px solid #d1fae5;
}
.ai-proposal-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.75rem 1rem; background: #ecfdf5; border-bottom: 1px solid #d1fae5;
}
.ai-proposal-header h4 {
  font-size: 0.82rem; font-weight: 600; color: #059669; margin: 0;
  display: flex; align-items: center; gap: 0.3rem;
}
.ai-proposal-header h4 i { font-size: 0.82rem; }
.ai-proposal-content {
  padding: 1rem; max-height: 400px; overflow-y: auto;
}
.ai-proposal-content pre {
  font-family: inherit; white-space: pre-wrap;
  font-size: 0.82rem; color: #334155; line-height: 1.6; margin: 0;
}
</style>
