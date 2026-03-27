<template>
  <div>
    <Head title="Lộ trình CEO" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-icon-wrapper"><i class="pi pi-flag-fill" /></div>
        <div>
          <h1 class="page-title">Lộ trình CEO</h1>
          <p class="page-subtitle">Từng bước trở thành CEO giỏi — {{ stats.overall_progress }}% hoàn thành</p>
        </div>
      </div>
      <div class="header-actions">
        <Button v-if="!phases.length" label="Tạo dữ liệu mẫu" icon="pi pi-database" severity="secondary" @click="seedData" />
        <Button label="Thêm giai đoạn" icon="pi pi-plus" @click="openPhaseDialog()" />
      </div>
    </div>

    <!-- Overall Progress Bar -->
    <div class="overall-progress-wrap">
      <div class="overall-progress-bar">
        <div class="overall-progress-fill" :style="{ width: stats.overall_progress + '%' }"></div>
      </div>
      <span class="overall-progress-label">{{ stats.overall_progress }}%</span>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card sc-phases"><div class="stat-icon"><i class="pi pi-flag" /></div><div class="stat-body"><span class="stat-value">{{ stats.total_phases }}</span><span class="stat-label">Giai đoạn</span></div></div>
      <div class="stat-card sc-milestones"><div class="stat-icon"><i class="pi pi-map-marker" /></div><div class="stat-body"><span class="stat-value">{{ stats.completed_milestones }}/{{ stats.total_milestones }}</span><span class="stat-label">Cột mốc</span></div></div>
      <div class="stat-card sc-tests"><div class="stat-icon"><i class="pi pi-check-circle" /></div><div class="stat-body"><span class="stat-value">{{ stats.passed_tests }}/{{ stats.total_tests }}</span><span class="stat-label">Tests đạt</span></div></div>
      <div class="stat-card sc-score"><div class="stat-icon"><i class="pi pi-chart-line" /></div><div class="stat-body"><span class="stat-value">{{ stats.overall_progress }}%</span><span class="stat-label">Tiến độ</span></div></div>
    </div>

    <!-- Phase Roadmap -->
    <div v-if="phases.length" class="roadmap">
      <div v-for="(phase, pIdx) in phases" :key="phase.id" class="phase-block" :class="{ 'phase-complete': phase.progress === 100 }">
        <div class="phase-header" @click="togglePhase(pIdx)">
          <div class="phase-left">
            <div class="phase-number" :style="{ background: phase.color }">{{ pIdx + 1 }}</div>
            <div class="phase-info">
              <h2 class="phase-title">{{ phase.title }}</h2>
              <p class="phase-desc">{{ phase.description }}</p>
            </div>
          </div>
          <div class="phase-right">
            <div class="phase-progress-wrap">
              <div class="phase-progress-bar"><div class="phase-progress-fill" :style="{ width: phase.progress + '%', background: phase.color }"></div></div>
              <span class="phase-progress-text">{{ phase.completed_count }}/{{ phase.milestone_count }}</span>
            </div>
            <div class="phase-actions" @click.stop>
              <Button icon="pi pi-plus" text rounded size="small" v-tooltip="'Thêm cột mốc'" @click="openMilestoneDialog(null, phase.id)" />
              <Button icon="pi pi-pencil" text rounded size="small" v-tooltip="'Sửa'" @click="openPhaseDialog(phase)" />
              <Button icon="pi pi-trash" text rounded size="small" severity="danger" v-tooltip="'Xóa'" @click="deletePhase(phase)" />
            </div>
            <i :class="expandedPhases.includes(pIdx) ? 'pi pi-chevron-up' : 'pi pi-chevron-down'" class="expand-icon" />
          </div>
        </div>

        <!-- Milestones -->
        <transition name="slide">
          <div v-if="expandedPhases.includes(pIdx)" class="milestone-list">
            <div v-for="ms in phase.milestones" :key="ms.id" class="milestone-card" :class="{ 'ms-done': ms.completed }">
              <div class="ms-status-dot" :style="{ borderColor: phase.color, background: ms.completed ? phase.color : 'white' }">
                <i v-if="ms.completed" class="pi pi-check" style="color:white;font-size:.5rem" />
              </div>
              <div class="ms-body">
                <div class="ms-top">
                  <h3 class="ms-title">{{ ms.title }}</h3>
                  <span v-if="ms.completed" class="ms-badge badge-done">Hoàn thành</span>
                  <span v-else class="ms-badge badge-pending">Chưa xong</span>
                </div>
                <p v-if="ms.description" class="ms-desc">{{ ms.description }}</p>
                <div v-if="ms.skills && ms.skills.length" class="ms-skills">
                  <span v-for="s in ms.skills" :key="s" class="skill-tag">{{ s }}</span>
                </div>
                <!-- Tests for this milestone -->
                <div v-if="ms.tests && ms.tests.length" class="ms-tests">
                  <div v-for="test in ms.tests" :key="test.id" class="test-row">
                    <div class="test-info">
                      <i class="pi pi-file-edit" />
                      <span class="test-name">{{ test.title }}</span>
                      <span class="test-meta">{{ test.question_count }} câu · {{ test.time_limit_minutes }}p</span>
                    </div>
                    <div class="test-actions">
                      <span v-if="test.passed" class="score-badge score-pass">{{ test.best_score }}%</span>
                      <span v-else-if="test.best_score != null" class="score-badge score-fail">{{ test.best_score }}%</span>
                      <Button :label="test.passed ? 'Làm lại' : 'Làm bài'" :icon="test.passed ? 'pi pi-refresh' : 'pi pi-play'" size="small" :severity="test.passed ? 'secondary' : undefined" @click="startTest(test)" />
                      <Button icon="pi pi-pencil" text rounded size="small" @click="openTestDialog(test, ms.id)" />
                      <Button icon="pi pi-trash" text rounded size="small" severity="danger" @click="deleteTest(test)" />
                    </div>
                  </div>
                </div>
              </div>
              <div class="ms-actions" @click.stop>
                <Button icon="pi pi-plus" text rounded size="small" v-tooltip="'Thêm test'" @click="openTestDialog(null, ms.id)" />
                <Button icon="pi pi-pencil" text rounded size="small" v-tooltip="'Sửa'" @click="openMilestoneDialog(ms)" />
                <Button icon="pi pi-trash" text rounded size="small" severity="danger" v-tooltip="'Xóa'" @click="deleteMilestone(ms)" />
              </div>
            </div>
          </div>
        </transition>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <div class="empty-icon"><i class="pi pi-flag" /></div>
      <h3>Chưa có lộ trình</h3>
      <p>Tạo dữ liệu mẫu hoặc thêm giai đoạn đầu tiên để bắt đầu hành trình CEO.</p>
      <Button label="Tạo dữ liệu mẫu" icon="pi pi-database" class="mt-1" @click="seedData" />
    </div>

    <!-- ===== TEST TAKING DIALOG ===== -->
    <div v-if="testDialog" class="dialog-overlay" @click.self="testDialog = false">
      <div class="test-card">
        <div class="test-header">
          <div class="test-header-left">
            <div class="test-icon-lg"><i class="pi pi-file-edit" /></div>
            <div>
              <h3>{{ activeTest.title }}</h3>
              <span class="test-header-meta">{{ activeTest.questions.length }} câu hỏi · Cần ≥{{ activeTest.passing_score }}% để đạt</span>
            </div>
          </div>
          <button class="dialog-close" @click="testDialog = false"><i class="pi pi-times" /></button>
        </div>

        <!-- Test questions -->
        <div v-if="!testResult" class="test-body">
          <div v-for="(q, qi) in activeTest.questions" :key="qi" class="question-block">
            <div class="q-number">Câu {{ qi + 1 }}</div>
            <p class="q-text">{{ q.q }}</p>
            <div class="q-options">
              <label v-for="(opt, oi) in q.options" :key="oi" class="q-option" :class="{ 'q-selected': testAnswers[qi] === oi }">
                <input type="radio" :name="'q_' + qi" :value="oi" v-model="testAnswers[qi]" />
                <span class="q-option-letter">{{ ['A','B','C','D'][oi] }}</span>
                <span class="q-option-text">{{ opt }}</span>
              </label>
            </div>
          </div>
          <div class="test-submit-area">
            <Button label="Nộp bài" icon="pi pi-check" @click="submitTest" :disabled="Object.keys(testAnswers).length < activeTest.questions.length" />
          </div>
        </div>

        <!-- Test Result -->
        <div v-else class="test-result">
          <div class="result-hero" :class="testResult.passed ? 'result-pass' : 'result-fail'">
            <div class="result-score-circle">
              <span class="result-score-num">{{ testResult.score }}%</span>
            </div>
            <h3>{{ testResult.passed ? '🎉 Chúc mừng! Bạn đã ĐẠT!' : '😔 Chưa đạt — Hãy thử lại!' }}</h3>
            <p>Đúng {{ testResult.correct }}/{{ testResult.total }} câu</p>
          </div>
          <div class="result-review">
            <div v-for="(q, qi) in activeTest.questions" :key="qi" class="review-q" :class="testAnswers[qi] === q.correct ? 'rq-correct' : 'rq-wrong'">
              <div class="rq-header">
                <span class="rq-num">Câu {{ qi + 1 }}</span>
                <i :class="testAnswers[qi] === q.correct ? 'pi pi-check-circle' : 'pi pi-times-circle'" />
              </div>
              <p class="rq-text">{{ q.q }}</p>
              <p class="rq-answer">Bạn chọn: <strong>{{ q.options[testAnswers[qi]] || 'Không chọn' }}</strong></p>
              <p v-if="testAnswers[qi] !== q.correct" class="rq-correct-ans">Đáp án đúng: <strong>{{ q.options[q.correct] }}</strong></p>
              <p v-if="q.explanation" class="rq-explain"><i class="pi pi-info-circle" /> {{ q.explanation }}</p>
            </div>
          </div>
          <div class="test-submit-area">
            <Button label="Đóng" severity="secondary" outlined @click="testDialog = false" />
            <Button label="Làm lại" icon="pi pi-refresh" @click="retakeTest" />
          </div>
        </div>
      </div>
    </div>

    <!-- ===== PHASE DIALOG ===== -->
    <div v-if="phaseDialog" class="dialog-overlay" @click.self="phaseDialog = false">
      <div class="dialog-card">
        <div class="dialog-header">
          <div class="dialog-header-left">
            <div class="dialog-icon" style="background:linear-gradient(135deg,#10b981,#059669)"><i class="pi pi-flag" /></div>
            <h3>{{ phaseForm.id ? 'Sửa' : 'Thêm' }} giai đoạn</h3>
          </div>
          <button class="dialog-close" @click="phaseDialog = false"><i class="pi pi-times" /></button>
        </div>
        <form @submit.prevent="submitPhase" class="dialog-body">
          <div class="form-group"><label>Tên giai đoạn <span class="req">*</span></label><InputText v-model="phaseForm.title" class="w-full" placeholder="VD: Tự quản lý bản thân" /></div>
          <div class="form-group"><label>Mô tả</label><textarea v-model="phaseForm.description" class="form-control" rows="2" placeholder="Mô tả ngắn..." /></div>
          <div class="form-row">
            <div class="form-group flex-1"><label>Icon</label><InputText v-model="phaseForm.icon" class="w-full" placeholder="pi pi-star" /></div>
            <div class="form-group" style="width:100px"><label>Màu</label><InputText v-model="phaseForm.color" class="w-full" placeholder="#10b981" /></div>
            <div class="form-group" style="width:80px"><label>Thứ tự</label><InputNumber v-model="phaseForm.sort_order" class="w-full" /></div>
          </div>
          <div class="dialog-footer">
            <Button label="Hủy" severity="secondary" outlined @click="phaseDialog = false" type="button" />
            <Button :label="phaseForm.id ? 'Cập nhật' : 'Tạo'" icon="pi pi-check" type="submit" :loading="phaseForm.processing" />
          </div>
        </form>
      </div>
    </div>

    <!-- ===== MILESTONE DIALOG ===== -->
    <div v-if="milestoneDialog" class="dialog-overlay" @click.self="milestoneDialog = false">
      <div class="dialog-card">
        <div class="dialog-header">
          <div class="dialog-header-left">
            <div class="dialog-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706)"><i class="pi pi-map-marker" /></div>
            <h3>{{ msForm.id ? 'Sửa' : 'Thêm' }} cột mốc</h3>
          </div>
          <button class="dialog-close" @click="milestoneDialog = false"><i class="pi pi-times" /></button>
        </div>
        <form @submit.prevent="submitMilestone" class="dialog-body">
          <div class="form-group"><label>Tên cột mốc <span class="req">*</span></label><InputText v-model="msForm.title" class="w-full" placeholder="VD: Quản lý thời gian" /></div>
          <div class="form-group"><label>Mô tả</label><textarea v-model="msForm.description" class="form-control" rows="2" placeholder="Mô tả chi tiết..." /></div>
          <div class="form-group"><label>Kỹ năng (phân cách bằng dấu phẩy)</label><InputText v-model="msSkillsText" class="w-full" placeholder="Time-blocking, Deep work, Prioritization" /></div>
          <div class="dialog-footer">
            <Button label="Hủy" severity="secondary" outlined @click="milestoneDialog = false" type="button" />
            <Button :label="msForm.id ? 'Cập nhật' : 'Tạo'" icon="pi pi-check" type="submit" :loading="msForm.processing" />
          </div>
        </form>
      </div>
    </div>

    <!-- ===== TEST EDIT DIALOG ===== -->
    <div v-if="testEditDialog" class="dialog-overlay" @click.self="testEditDialog = false">
      <div class="dialog-card dialog-wide">
        <div class="dialog-header">
          <div class="dialog-header-left">
            <div class="dialog-icon" style="background:linear-gradient(135deg,#3b82f6,#2563eb)"><i class="pi pi-file-edit" /></div>
            <h3>{{ testForm.id ? 'Sửa' : 'Thêm' }} bài test</h3>
          </div>
          <button class="dialog-close" @click="testEditDialog = false"><i class="pi pi-times" /></button>
        </div>
        <form @submit.prevent="submitTestForm" class="dialog-body">
          <div class="form-row">
            <div class="form-group flex-1"><label>Tên bài test <span class="req">*</span></label><InputText v-model="testForm.title" class="w-full" /></div>
            <div class="form-group" style="width:100px"><label>Điểm đạt %</label><InputNumber v-model="testForm.passing_score" class="w-full" /></div>
            <div class="form-group" style="width:100px"><label>Giới hạn (phút)</label><InputNumber v-model="testForm.time_limit_minutes" class="w-full" /></div>
          </div>

          <div class="questions-editor">
            <div v-for="(q, qi) in editQuestions" :key="qi" class="edit-q">
              <div class="edit-q-header">
                <span class="edit-q-num">Câu {{ qi + 1 }}</span>
                <Button icon="pi pi-trash" text rounded size="small" severity="danger" @click="editQuestions.splice(qi, 1)" />
              </div>
              <div class="form-group"><label>Câu hỏi</label><InputText v-model="q.q" class="w-full" /></div>
              <div v-for="(opt, oi) in q.options" :key="oi" class="edit-opt-row">
                <label class="q-option-letter-sm" :class="{ 'correct-letter': q.correct === oi }">{{ ['A','B','C','D'][oi] }}</label>
                <InputText v-model="q.options[oi]" class="w-full" :placeholder="'Lựa chọn ' + ['A','B','C','D'][oi]" />
                <Button :icon="q.correct === oi ? 'pi pi-check-circle' : 'pi pi-circle'" :text="q.correct !== oi" :severity="q.correct === oi ? 'success' : 'secondary'" rounded size="small" @click="q.correct = oi" v-tooltip="'Đáp án đúng'" />
              </div>
              <div class="form-group"><label>Giải thích</label><InputText v-model="q.explanation" class="w-full" placeholder="Giải thích đáp án..." /></div>
            </div>
            <Button label="Thêm câu hỏi" icon="pi pi-plus" severity="secondary" text @click="addQuestion" />
          </div>

          <div class="dialog-footer">
            <Button label="Hủy" severity="secondary" outlined @click="testEditDialog = false" type="button" />
            <Button :label="testForm.id ? 'Cập nhật' : 'Tạo'" icon="pi pi-check" type="submit" :loading="testForm.processing" />
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'

export default {
  components: { Head, Button, InputText, InputNumber },
  layout: Layout,
  props: { phases: Array, stats: Object },
  data() {
    return {
      expandedPhases: this.phases.map((_, i) => i),
      // Phase dialog
      phaseDialog: false,
      phaseForm: this.emptyPhaseForm(),
      // Milestone dialog
      milestoneDialog: false,
      msForm: this.emptyMsForm(),
      msSkillsText: '',
      // Test edit dialog
      testEditDialog: false,
      testForm: this.emptyTestForm(),
      editQuestions: [],
      // Test taking
      testDialog: false,
      activeTest: null,
      testAnswers: {},
      testResult: null,
    }
  },
  methods: {
    togglePhase(idx) {
      const i = this.expandedPhases.indexOf(idx)
      if (i > -1) this.expandedPhases.splice(i, 1)
      else this.expandedPhases.push(idx)
    },
    seedData() {
      this.$inertia.post('/ceo-roadmap/seed', {}, { preserveScroll: true })
    },

    // ── Phase ──
    emptyPhaseForm() { return this.$inertia.form({ id: null, title: '', description: '', icon: 'pi pi-star', color: '#10b981', sort_order: 0 }) },
    openPhaseDialog(p = null) {
      this.phaseForm = p ? this.$inertia.form({ id: p.id, title: p.title, description: p.description || '', icon: p.icon, color: p.color, sort_order: p.sort_order }) : this.emptyPhaseForm()
      this.phaseDialog = true
    },
    submitPhase() {
      if (this.phaseForm.id) this.phaseForm.put(`/ceo-roadmap/phases/${this.phaseForm.id}`, { preserveScroll: true, onSuccess: () => { this.phaseDialog = false } })
      else this.phaseForm.post('/ceo-roadmap/phases', { preserveScroll: true, onSuccess: () => { this.phaseDialog = false } })
    },
    deletePhase(p) { if (confirm(`Xóa giai đoạn "${p.title}"?`)) this.$inertia.delete(`/ceo-roadmap/phases/${p.id}`, { preserveScroll: true }) },

    // ── Milestone ──
    emptyMsForm() { return this.$inertia.form({ id: null, phase_id: null, title: '', description: '', skills: [], sort_order: 0 }) },
    openMilestoneDialog(ms = null, phaseId = null) {
      if (ms) {
        this.msForm = this.$inertia.form({ id: ms.id, phase_id: ms.phase_id, title: ms.title, description: ms.description || '', skills: ms.skills || [], sort_order: ms.sort_order })
        this.msSkillsText = (ms.skills || []).join(', ')
      } else {
        this.msForm = this.emptyMsForm()
        this.msForm.phase_id = phaseId
        this.msSkillsText = ''
      }
      this.milestoneDialog = true
    },
    submitMilestone() {
      this.msForm.skills = this.msSkillsText.split(',').map(s => s.trim()).filter(Boolean)
      if (this.msForm.id) this.msForm.put(`/ceo-roadmap/milestones/${this.msForm.id}`, { preserveScroll: true, onSuccess: () => { this.milestoneDialog = false } })
      else this.msForm.post('/ceo-roadmap/milestones', { preserveScroll: true, onSuccess: () => { this.milestoneDialog = false } })
    },
    deleteMilestone(ms) { if (confirm(`Xóa cột mốc "${ms.title}"?`)) this.$inertia.delete(`/ceo-roadmap/milestones/${ms.id}`, { preserveScroll: true }) },

    // ── Test Edit ──
    emptyTestForm() { return this.$inertia.form({ id: null, milestone_id: null, title: '', description: '', questions: [], passing_score: 70, time_limit_minutes: 10 }) },
    openTestDialog(test = null, msId = null) {
      if (test) {
        this.testForm = this.$inertia.form({ id: test.id, milestone_id: null, title: test.title, description: test.description || '', questions: test.questions || [], passing_score: test.passing_score, time_limit_minutes: test.time_limit_minutes })
        this.editQuestions = JSON.parse(JSON.stringify(test.questions || []))
      } else {
        this.testForm = this.emptyTestForm()
        this.testForm.milestone_id = msId
        this.editQuestions = []
      }
      this.testEditDialog = true
    },
    addQuestion() { this.editQuestions.push({ q: '', options: ['', '', '', ''], correct: 0, explanation: '' }) },
    submitTestForm() {
      this.testForm.questions = this.editQuestions
      if (this.testForm.id) this.testForm.put(`/ceo-roadmap/tests/${this.testForm.id}`, { preserveScroll: true, onSuccess: () => { this.testEditDialog = false } })
      else this.testForm.post('/ceo-roadmap/tests', { preserveScroll: true, onSuccess: () => { this.testEditDialog = false } })
    },
    deleteTest(test) { if (confirm(`Xóa bài test "${test.title}"?`)) this.$inertia.delete(`/ceo-roadmap/tests/${test.id}`, { preserveScroll: true }) },

    // ── Test Taking ──
    startTest(test) {
      this.activeTest = test
      this.testAnswers = {}
      this.testResult = null
      this.testDialog = true
    },
    submitTest() {
      const questions = this.activeTest.questions
      let correct = 0
      questions.forEach((q, i) => { if (this.testAnswers[i] === q.correct) correct++ })
      const score = Math.round(correct / questions.length * 100)
      this.testResult = { score, correct, total: questions.length, passed: score >= this.activeTest.passing_score }
      // Save to server
      this.$inertia.post(`/ceo-roadmap/tests/${this.activeTest.id}/submit`, { answers: this.testAnswers }, { preserveScroll: true, preserveState: true })
    },
    retakeTest() { this.testAnswers = {}; this.testResult = null },
  },
}
</script>

<style scoped>
/* ===== Header ===== */
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem; flex-wrap:wrap; gap:.75rem }
.header-content { display:flex; align-items:center; gap:.85rem }
.header-icon-wrapper { width:48px; height:48px; border-radius:14px; background:linear-gradient(135deg,#10b981,#059669); display:flex; align-items:center; justify-content:center; color:white; font-size:1.25rem; box-shadow:0 4px 14px rgba(16,185,129,.3) }
.page-title { font-size:1.5rem; font-weight:800; color:#0f172a; margin:0; letter-spacing:-.02em }
.page-subtitle { font-size:.82rem; color:#64748b; margin:.15rem 0 0 }
.header-actions { display:flex; gap:.5rem }

/* ===== Overall Progress ===== */
.overall-progress-wrap { display:flex; align-items:center; gap:.75rem; margin-bottom:1.25rem }
.overall-progress-bar { flex:1; height:10px; background:#e2e8f0; border-radius:5px; overflow:hidden }
.overall-progress-fill { height:100%; border-radius:5px; background:linear-gradient(90deg,#10b981,#059669); transition:width .6s cubic-bezier(.4,0,.2,1) }
.overall-progress-label { font-size:.82rem; font-weight:700; color:#059669; min-width:40px }

/* ===== Stats ===== */
.stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:.75rem; margin-bottom:1.5rem }
.stat-card { display:flex; align-items:center; gap:.75rem; padding:.85rem 1rem; background:white; border-radius:14px; border:1.5px solid #f1f5f9; box-shadow:0 1px 3px rgba(0,0,0,.04); transition:all .2s }
.stat-card:hover { transform:translateY(-2px); box-shadow:0 4px 14px rgba(0,0,0,.06) }
.stat-icon { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:.92rem }
.sc-phases .stat-icon { background:#ecfdf5; color:#059669 }
.sc-milestones .stat-icon { background:#fef3c7; color:#d97706 }
.sc-tests .stat-icon { background:#eff6ff; color:#3b82f6 }
.sc-score .stat-icon { background:#f0fdf4; color:#16a34a }
.stat-body { display:flex; flex-direction:column }
.stat-value { font-size:1.15rem; font-weight:800; color:#0f172a }
.stat-label { font-size:.68rem; color:#94a3b8 }

/* ===== Roadmap Phases ===== */
.roadmap { display:flex; flex-direction:column; gap:.75rem }
.phase-block { background:white; border-radius:16px; border:1.5px solid #f1f5f9; box-shadow:0 1px 3px rgba(0,0,0,.04); overflow:hidden; transition:all .2s }
.phase-block:hover { border-color:#e2e8f0 }
.phase-complete { border-color:#d1fae5 }
.phase-header { display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; cursor:pointer; transition:background .15s; gap:1rem }
.phase-header:hover { background:#fafbfc }
.phase-left { display:flex; align-items:center; gap:.85rem; flex:1; min-width:0 }
.phase-number { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; color:white; font-size:.85rem; font-weight:800; flex-shrink:0 }
.phase-info { min-width:0 }
.phase-title { font-size:.95rem; font-weight:700; color:#1e293b; margin:0 }
.phase-desc { font-size:.72rem; color:#94a3b8; margin:.1rem 0 0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis }
.phase-right { display:flex; align-items:center; gap:.75rem; flex-shrink:0 }
.phase-progress-wrap { display:flex; align-items:center; gap:.45rem }
.phase-progress-bar { width:80px; height:6px; background:#f1f5f9; border-radius:3px; overflow:hidden }
.phase-progress-fill { height:100%; border-radius:3px; transition:width .4s }
.phase-progress-text { font-size:.68rem; font-weight:600; color:#64748b }
.phase-actions { display:flex; gap:.125rem }
.expand-icon { font-size:.65rem; color:#94a3b8; transition:transform .2s }

/* ===== Milestones ===== */
.milestone-list { padding:0 1.25rem 1rem; display:flex; flex-direction:column; gap:.5rem }
.milestone-card { display:flex; gap:.75rem; padding:.85rem 1rem; background:#fafbfc; border-radius:12px; border:1px solid #f1f5f9; transition:all .2s }
.milestone-card:hover { background:#f8fafc; border-color:#e2e8f0 }
.ms-done { background:#f0fdf4; border-color:#d1fae5 }
.ms-status-dot { width:20px; height:20px; border-radius:50%; border:2.5px solid #cbd5e1; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:.1rem; transition:all .2s }
.ms-body { flex:1; min-width:0 }
.ms-top { display:flex; align-items:center; gap:.45rem; flex-wrap:wrap }
.ms-title { font-size:.82rem; font-weight:700; color:#1e293b; margin:0 }
.ms-badge { font-size:.55rem; font-weight:700; padding:.1rem .4rem; border-radius:6px; text-transform:uppercase }
.badge-done { background:#d1fae5; color:#059669 }
.badge-pending { background:#f1f5f9; color:#94a3b8 }
.ms-desc { font-size:.72rem; color:#64748b; margin:.2rem 0 0 }
.ms-skills { display:flex; gap:.3rem; flex-wrap:wrap; margin-top:.4rem }
.skill-tag { font-size:.58rem; font-weight:600; padding:.12rem .4rem; border-radius:5px; background:#eef2ff; color:#6366f1 }
.ms-actions { display:flex; flex-direction:column; gap:.125rem; flex-shrink:0 }

/* ===== Test Row ===== */
.ms-tests { margin-top:.5rem; display:flex; flex-direction:column; gap:.35rem }
.test-row { display:flex; align-items:center; justify-content:space-between; padding:.45rem .65rem; background:white; border-radius:8px; border:1px solid #f1f5f9 }
.test-info { display:flex; align-items:center; gap:.4rem }
.test-info i { font-size:.7rem; color:#94a3b8 }
.test-name { font-size:.72rem; font-weight:600; color:#334155 }
.test-meta { font-size:.58rem; color:#94a3b8 }
.test-actions { display:flex; align-items:center; gap:.3rem }
.score-badge { font-size:.6rem; font-weight:700; padding:.12rem .4rem; border-radius:6px }
.score-pass { background:#d1fae5; color:#059669 }
.score-fail { background:#fef2f2; color:#ef4444 }

/* ===== Test Taking Dialog ===== */
.test-card { background:white; border-radius:18px; width:780px; max-width:95vw; max-height:90vh; display:flex; flex-direction:column; box-shadow:0 24px 64px rgba(0,0,0,.18); animation:slideUp .25s ease-out }
.test-header { display:flex; align-items:center; justify-content:space-between; padding:1.25rem 1.5rem; border-bottom:1px solid #f1f5f9; flex-shrink:0 }
.test-header-left { display:flex; align-items:center; gap:.65rem }
.test-icon-lg { width:42px; height:42px; border-radius:12px; background:linear-gradient(135deg,#10b981,#059669); display:flex; align-items:center; justify-content:center; color:white; font-size:1rem }
.test-header h3 { font-size:1rem; font-weight:700; color:#1e293b; margin:0 }
.test-header-meta { font-size:.68rem; color:#94a3b8 }
.test-body { padding:1.25rem 1.5rem; overflow-y:auto; flex:1 }
.question-block { margin-bottom:1.5rem; padding-bottom:1.25rem; border-bottom:1px solid #f1f5f9 }
.question-block:last-child { border-bottom:none }
.q-number { font-size:.6rem; font-weight:700; color:#10b981; text-transform:uppercase; letter-spacing:.05em; margin-bottom:.3rem }
.q-text { font-size:.88rem; font-weight:600; color:#1e293b; margin:0 0 .65rem; line-height:1.5 }
.q-options { display:flex; flex-direction:column; gap:.4rem }
.q-option { display:flex; align-items:center; gap:.55rem; padding:.55rem .75rem; border:1.5px solid #e2e8f0; border-radius:10px; cursor:pointer; transition:all .2s; font-size:.82rem; color:#334155 }
.q-option:hover { border-color:#10b981; background:#f0fdf4 }
.q-option input { display:none }
.q-selected { border-color:#10b981; background:#ecfdf5; color:#059669; font-weight:600 }
.q-option-letter { width:24px; height:24px; border-radius:7px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; font-size:.65rem; font-weight:700; color:#64748b; flex-shrink:0; transition:all .2s }
.q-selected .q-option-letter { background:#10b981; color:white }
.q-option-text { flex:1 }
.test-submit-area { display:flex; justify-content:flex-end; gap:.5rem; padding-top:1rem }

/* ===== Test Result ===== */
.test-result { overflow-y:auto; flex:1 }
.result-hero { text-align:center; padding:2rem 1.5rem; border-bottom:1px solid #f1f5f9 }
.result-pass { background:linear-gradient(180deg,#f0fdf4,white) }
.result-fail { background:linear-gradient(180deg,#fef2f2,white) }
.result-score-circle { width:90px; height:90px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem; box-shadow:0 4px 20px rgba(0,0,0,.08) }
.result-pass .result-score-circle { background:linear-gradient(135deg,#10b981,#059669) }
.result-fail .result-score-circle { background:linear-gradient(135deg,#ef4444,#dc2626) }
.result-score-num { font-size:1.4rem; font-weight:800; color:white }
.result-hero h3 { font-size:1.1rem; font-weight:700; color:#1e293b; margin:0 }
.result-hero p { font-size:.82rem; color:#64748b; margin:.3rem 0 0 }
.result-review { padding:1.25rem 1.5rem }
.review-q { padding:.75rem; border-radius:10px; margin-bottom:.5rem; border:1px solid #f1f5f9 }
.rq-correct { background:#f0fdf4; border-color:#d1fae5 }
.rq-wrong { background:#fef2f2; border-color:#fecaca }
.rq-header { display:flex; align-items:center; justify-content:space-between }
.rq-num { font-size:.6rem; font-weight:700; text-transform:uppercase; color:#64748b }
.rq-correct .rq-header i { color:#10b981 }
.rq-wrong .rq-header i { color:#ef4444 }
.rq-text { font-size:.78rem; font-weight:600; color:#1e293b; margin:.3rem 0 }
.rq-answer { font-size:.72rem; color:#475569; margin:.15rem 0 }
.rq-correct-ans { font-size:.72rem; color:#059669; margin:.1rem 0 }
.rq-explain { font-size:.68rem; color:#64748b; margin:.2rem 0 0; font-style:italic; display:flex; align-items:flex-start; gap:.3rem }
.rq-explain i { font-size:.6rem; margin-top:.15rem; flex-shrink:0 }

/* ===== Dialogs (shared) ===== */
.dialog-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:1000; backdrop-filter:blur(4px); padding:1.5rem }
.dialog-card { background:white; border-radius:18px; width:620px; max-width:100%; max-height:calc(100vh - 3rem); display:flex; flex-direction:column; box-shadow:0 24px 64px rgba(0,0,0,.18); animation:slideUp .25s ease-out }
.dialog-wide { width:780px }
@keyframes slideUp { from { transform:translateY(20px); opacity:0 } to { transform:translateY(0); opacity:1 } }
.dialog-header { display:flex; align-items:center; justify-content:space-between; padding:1.25rem 1.5rem; border-bottom:1px solid #f1f5f9; flex-shrink:0 }
.dialog-header-left { display:flex; align-items:center; gap:.6rem }
.dialog-icon { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; color:white; font-size:.85rem; flex-shrink:0 }
.dialog-header h3 { font-size:1rem; font-weight:700; color:#1e293b; margin:0 }
.dialog-close { background:none; border:none; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#94a3b8; cursor:pointer; transition:all .2s; flex-shrink:0 }
.dialog-close:hover { background:#fef2f2; color:#ef4444 }
.dialog-body { padding:1.25rem 1.5rem; overflow-y:auto; flex:1; min-height:0 }
.dialog-footer { display:flex; justify-content:flex-end; gap:.5rem; padding:1rem 1.5rem; border-top:1px solid #f1f5f9; flex-shrink:0 }

/* ===== Form ===== */
.form-row { display:flex; gap:.75rem; flex-wrap:wrap }
.form-group { margin-bottom:.85rem; min-width:0 }
.flex-1 { flex:1; min-width:120px }
.w-full { width:100% }
.form-group label { display:block; font-size:.72rem; font-weight:600; color:#475569; margin-bottom:.35rem }
.req { color:#ef4444 }
.form-control { width:100%; padding:.55rem .75rem; border:1px solid #e2e8f0; border-radius:8px; font-size:.85rem; color:#1e293b; background:white; transition:all .2s; outline:none; font-family:inherit; resize:vertical }
.form-control:focus { border-color:#10b981; box-shadow:0 0 0 3px rgba(16,185,129,.1) }

/* ===== Questions Editor ===== */
.questions-editor { margin-top:.5rem }
.edit-q { padding:.85rem; background:#fafbfc; border-radius:10px; border:1px solid #f1f5f9; margin-bottom:.65rem }
.edit-q-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:.5rem }
.edit-q-num { font-size:.65rem; font-weight:700; color:#10b981; text-transform:uppercase }
.edit-opt-row { display:flex; align-items:center; gap:.4rem; margin-bottom:.35rem }
.q-option-letter-sm { width:24px; height:24px; border-radius:6px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; font-size:.6rem; font-weight:700; color:#64748b; flex-shrink:0 }
.correct-letter { background:#d1fae5; color:#059669 }

/* ===== Transitions ===== */
.slide-enter-active, .slide-leave-active { transition:all .3s ease }
.slide-enter-from, .slide-leave-to { opacity:0; max-height:0; overflow:hidden }
.slide-enter-to, .slide-leave-from { opacity:1; max-height:2000px }

/* ===== Empty ===== */
.empty-state { text-align:center; padding:3rem 2rem; background:white; border-radius:16px; border:2px dashed #e2e8f0 }
.empty-icon { width:64px; height:64px; border-radius:16px; background:linear-gradient(135deg,#ecfdf5,#d1fae5); display:flex; align-items:center; justify-content:center; margin:0 auto 1rem; font-size:1.5rem; color:#059669 }
.empty-state h3 { font-size:1rem; font-weight:700; color:#1e293b; margin:0 0 .35rem }
.empty-state p { font-size:.82rem; color:#94a3b8; margin:0 }
.mt-1 { margin-top:.75rem }

/* ===== Responsive ===== */
@media (max-width:768px) {
  .page-header { flex-direction:column; align-items:flex-start }
  .stats-grid { grid-template-columns:repeat(2,1fr) }
  .phase-header { flex-direction:column; align-items:flex-start }
  .phase-right { width:100%; justify-content:space-between }
  .form-row { flex-direction:column }
  .dialog-overlay { padding:.75rem }
  .test-card { max-height:95vh }
  .milestone-card { flex-direction:column }
  .test-row { flex-direction:column; align-items:flex-start; gap:.35rem }
}
</style>
