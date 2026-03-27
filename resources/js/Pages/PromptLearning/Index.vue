<template>
  <div>
    <Head title="Học Prompts AI" />

    <!-- Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-icon-wrapper"><i class="pi pi-graduation-cap" /></div>
        <div>
          <h1 class="page-title">Học Prompts AI</h1>
          <p class="page-subtitle">Từ cơ bản đến nâng cao — {{ stats.overall_progress }}% hoàn thành</p>
        </div>
      </div>
      <div class="header-actions">
        <Button v-if="!categories.length" label="Tạo dữ liệu mẫu" icon="pi pi-database" severity="secondary" @click="$inertia.post('/prompt-learning/seed')" />
        <Button label="Thêm danh mục" icon="pi pi-plus" @click="openCatDialog()" />
      </div>
    </div>

    <!-- Overall Progress -->
    <div class="progress-wrap">
      <div class="progress-bar"><div class="progress-fill" :style="{ width: stats.overall_progress + '%' }"></div></div>
      <span class="progress-label">{{ stats.overall_progress }}%</span>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card sc-cat"><div class="stat-icon"><i class="pi pi-folder" /></div><div class="stat-body"><span class="stat-value">{{ stats.total_categories }}</span><span class="stat-label">Cấp độ</span></div></div>
      <div class="stat-card sc-les"><div class="stat-icon"><i class="pi pi-book" /></div><div class="stat-body"><span class="stat-value">{{ stats.total_lessons }}</span><span class="stat-label">Bài học</span></div></div>
      <div class="stat-card sc-ex"><div class="stat-icon"><i class="pi pi-pencil" /></div><div class="stat-body"><span class="stat-value">{{ stats.completed_exercises }}/{{ stats.total_exercises }}</span><span class="stat-label">Bài tập</span></div></div>
      <div class="stat-card sc-prog"><div class="stat-icon"><i class="pi pi-chart-line" /></div><div class="stat-body"><span class="stat-value">{{ stats.overall_progress }}%</span><span class="stat-label">Tiến độ</span></div></div>
    </div>

    <!-- Category Cards -->
    <div v-if="categories.length" class="categories">
      <div v-for="cat in categories" :key="cat.id" class="cat-block">
        <div class="cat-header" @click="toggleCat(cat.id)">
          <div class="cat-left">
            <div class="cat-icon" :style="{ background: cat.color + '18', color: cat.color }"><i :class="cat.icon" /></div>
            <div class="cat-info">
              <div class="cat-title-row">
                <h2 class="cat-title">{{ cat.title }}</h2>
                <span class="level-badge" :class="'lvl-' + cat.level">{{ levelLabel(cat.level) }}</span>
              </div>
              <p class="cat-desc">{{ cat.description }}</p>
            </div>
          </div>
          <div class="cat-right">
            <div class="cat-progress-wrap">
              <div class="cat-progress-bar"><div class="cat-progress-fill" :style="{ width: cat.progress + '%', background: cat.color }"></div></div>
              <span class="cat-progress-text">{{ cat.progress }}%</span>
            </div>
            <div class="cat-actions" @click.stop>
              <Button icon="pi pi-plus" text rounded size="small" v-tooltip="'Thêm bài học'" @click="openLessonDialog(null, cat.id)" />
              <Button icon="pi pi-pencil" text rounded size="small" @click="openCatDialog(cat)" />
              <Button icon="pi pi-trash" text rounded size="small" severity="danger" @click="deleteCat(cat)" />
            </div>
            <i :class="expandedCats.includes(cat.id) ? 'pi pi-chevron-up' : 'pi pi-chevron-down'" class="expand-icon" />
          </div>
        </div>

        <!-- Lessons -->
        <transition name="slide">
          <div v-if="expandedCats.includes(cat.id)" class="lessons-list">
            <div v-for="ls in cat.lessons" :key="ls.id" class="lesson-card" @click="openLessonView(ls, cat)">
              <div class="ls-left">
                <div class="ls-check" :class="{ 'ls-done': ls.completed_count === ls.exercises_count && ls.exercises_count > 0 }">
                  <i v-if="ls.completed_count === ls.exercises_count && ls.exercises_count > 0" class="pi pi-check" />
                  <i v-else class="pi pi-book" />
                </div>
                <div class="ls-info">
                  <h3 class="ls-title">{{ ls.title }}</h3>
                  <div class="ls-meta">
                    <span v-if="ls.examples.length"><i class="pi pi-code" /> {{ ls.examples.length }} ví dụ</span>
                    <span><i class="pi pi-pencil" /> {{ ls.completed_count }}/{{ ls.exercises_count }} bài tập</span>
                    <span v-if="ls.tips.length"><i class="pi pi-lightbulb" /> {{ ls.tips.length }} tips</span>
                  </div>
                </div>
              </div>
              <div class="ls-actions" @click.stop>
                <Button icon="pi pi-plus" text rounded size="small" v-tooltip="'Thêm bài tập'" @click="openExDialog(null, ls.id)" />
                <Button icon="pi pi-pencil" text rounded size="small" @click="openLessonDialog(ls)" />
                <Button icon="pi pi-trash" text rounded size="small" severity="danger" @click="deleteLesson(ls)" />
              </div>
            </div>
          </div>
        </transition>
      </div>
    </div>

    <!-- Empty -->
    <div v-else class="empty-state">
      <div class="empty-icon"><i class="pi pi-graduation-cap" /></div>
      <h3>Chưa có bài học nào</h3>
      <p>Tạo dữ liệu mẫu để bắt đầu hành trình học Prompt AI.</p>
      <Button label="Tạo dữ liệu mẫu" icon="pi pi-database" class="mt-1" @click="$inertia.post('/prompt-learning/seed')" />
    </div>

    <!-- ===== LESSON VIEWER ===== -->
    <div v-if="lessonViewDialog" class="dialog-overlay" @click.self="lessonViewDialog = false">
      <div class="lesson-viewer">
        <div class="lv-header" :style="{ borderBottomColor: activeCatColor }">
          <div class="lv-header-left">
            <div class="lv-icon" :style="{ background: activeCatColor }"><i class="pi pi-book" /></div>
            <div><h3>{{ activeLesson.title }}</h3><span class="lv-level">{{ activeCatTitle }}</span></div>
          </div>
          <button class="dialog-close" @click="lessonViewDialog = false"><i class="pi pi-times" /></button>
        </div>
        <div class="lv-body">
          <!-- Content -->
          <div v-if="activeLesson.content" class="lv-section">
            <div class="lv-section-title"><i class="pi pi-align-left" /> Nội dung</div>
            <div class="lv-content">{{ activeLesson.content }}</div>
          </div>

          <!-- Examples -->
          <div v-if="activeLesson.examples && activeLesson.examples.length" class="lv-section">
            <div class="lv-section-title"><i class="pi pi-code" /> Ví dụ Prompt</div>
            <div v-for="(ex, ei) in activeLesson.examples" :key="ei" class="prompt-example">
              <div class="pe-label">{{ ex.label }}</div>
              <div class="pe-prompt">
                <pre>{{ ex.prompt }}</pre>
                <button class="copy-btn" @click="copyText(ex.prompt)" v-tooltip="'Copy'"><i class="pi pi-copy" /></button>
              </div>
              <div v-if="ex.note" class="pe-note"><i class="pi pi-info-circle" /> {{ ex.note }}</div>
            </div>
          </div>

          <!-- Tips -->
          <div v-if="activeLesson.tips && activeLesson.tips.length" class="lv-section">
            <div class="lv-section-title"><i class="pi pi-lightbulb" /> Pro Tips</div>
            <ul class="tips-list">
              <li v-for="(tip, ti) in activeLesson.tips" :key="ti">{{ tip }}</li>
            </ul>
          </div>

          <!-- Exercises -->
          <div v-if="activeLesson.exercises && activeLesson.exercises.length" class="lv-section">
            <div class="lv-section-title"><i class="pi pi-pencil" /> Bài tập thực hành</div>
            <div v-for="ex in activeLesson.exercises" :key="ex.id" class="exercise-card" :class="{ 'ex-done': ex.completed }">
              <div class="ex-header">
                <div class="ex-title-row">
                  <span class="ex-diff" :class="'diff-' + ex.difficulty">{{ diffLabel(ex.difficulty) }}</span>
                  <h4 class="ex-title">{{ ex.title }}</h4>
                  <span v-if="ex.completed" class="ex-badge badge-done">✓ Hoàn thành</span>
                </div>
              </div>
              <p v-if="ex.instruction" class="ex-instruction">{{ ex.instruction }}</p>

              <div v-if="ex.sample_prompt" class="ex-sample">
                <div class="ex-sample-label">Prompt mẫu tham khảo:</div>
                <pre>{{ ex.sample_prompt }}</pre>
                <button class="copy-btn" @click="copyText(ex.sample_prompt)"><i class="pi pi-copy" /></button>
              </div>

              <!-- Exercise form -->
              <div class="ex-form">
                <textarea v-model="exerciseForms[ex.id]" class="ex-textarea" rows="4" placeholder="Viết prompt của bạn tại đây..." />
                <div class="ex-form-footer">
                  <div class="rating-input">
                    <span class="rating-label">Tự đánh giá:</span>
                    <button v-for="r in 5" :key="r" class="star-btn" :class="{ 'star-active': (exerciseRatings[ex.id] || 0) >= r }" @click="exerciseRatings[ex.id] = r" type="button">★</button>
                  </div>
                  <Button label="Lưu bài tập" icon="pi pi-check" size="small" @click="submitExercise(ex)" :disabled="!exerciseForms[ex.id] || !exerciseRatings[ex.id]" />
                </div>
              </div>

              <div v-if="ex.last_prompt && ex.completed" class="ex-prev">
                <div class="ex-prev-label">Bài làm trước:</div>
                <pre>{{ ex.last_prompt }}</pre>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== CATEGORY DIALOG ===== -->
    <div v-if="catDialog" class="dialog-overlay" @click.self="catDialog = false">
      <div class="dialog-card">
        <div class="dialog-header">
          <div class="dialog-header-left"><div class="dialog-icon di-violet"><i class="pi pi-folder" /></div><h3>{{ catForm.id ? 'Sửa' : 'Thêm' }} danh mục</h3></div>
          <button class="dialog-close" @click="catDialog = false"><i class="pi pi-times" /></button>
        </div>
        <form @submit.prevent="submitCat" class="dialog-body">
          <div class="form-group"><label>Tiêu đề <span class="req">*</span></label><InputText v-model="catForm.title" class="w-full" /></div>
          <div class="form-group"><label>Mô tả</label><textarea v-model="catForm.description" class="form-control" rows="2" /></div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label>Cấp độ</label>
              <Select v-model="catForm.level" :options="levelOptions" optionLabel="label" optionValue="value" class="w-full" />
            </div>
            <div class="form-group flex-1"><label>Icon</label><InputText v-model="catForm.icon" class="w-full" /></div>
            <div class="form-group" style="width:100px"><label>Màu</label><InputText v-model="catForm.color" class="w-full" /></div>
          </div>
          <div class="dialog-footer">
            <Button label="Hủy" severity="secondary" outlined @click="catDialog = false" type="button" />
            <Button :label="catForm.id ? 'Cập nhật' : 'Tạo'" icon="pi pi-check" type="submit" :loading="catForm.processing" />
          </div>
        </form>
      </div>
    </div>

    <!-- ===== LESSON DIALOG ===== -->
    <div v-if="lessonDialog" class="dialog-overlay" @click.self="lessonDialog = false">
      <div class="dialog-card dialog-wide">
        <div class="dialog-header">
          <div class="dialog-header-left"><div class="dialog-icon di-blue"><i class="pi pi-book" /></div><h3>{{ lsForm.id ? 'Sửa' : 'Thêm' }} bài học</h3></div>
          <button class="dialog-close" @click="lessonDialog = false"><i class="pi pi-times" /></button>
        </div>
        <form @submit.prevent="submitLesson" class="dialog-body">
          <div class="form-group"><label>Tiêu đề <span class="req">*</span></label><InputText v-model="lsForm.title" class="w-full" /></div>
          <div class="form-group"><label>Nội dung bài học</label><textarea v-model="lsForm.content" class="form-control" rows="4" /></div>
          <div class="form-group">
            <label>Ví dụ prompt (JSON)</label>
            <textarea v-model="examplesJson" class="form-control code-input" rows="5" placeholder='[{"label":"...","prompt":"...","note":"..."}]' />
          </div>
          <div class="form-group">
            <label>Tips (mỗi dòng 1 tip)</label>
            <textarea v-model="tipsText" class="form-control" rows="3" />
          </div>
          <div class="dialog-footer">
            <Button label="Hủy" severity="secondary" outlined @click="lessonDialog = false" type="button" />
            <Button :label="lsForm.id ? 'Cập nhật' : 'Tạo'" icon="pi pi-check" type="submit" :loading="lsForm.processing" />
          </div>
        </form>
      </div>
    </div>

    <!-- ===== EXERCISE DIALOG ===== -->
    <div v-if="exDialog" class="dialog-overlay" @click.self="exDialog = false">
      <div class="dialog-card">
        <div class="dialog-header">
          <div class="dialog-header-left"><div class="dialog-icon di-amber"><i class="pi pi-pencil" /></div><h3>{{ exForm.id ? 'Sửa' : 'Thêm' }} bài tập</h3></div>
          <button class="dialog-close" @click="exDialog = false"><i class="pi pi-times" /></button>
        </div>
        <form @submit.prevent="submitExForm" class="dialog-body">
          <div class="form-group"><label>Tiêu đề <span class="req">*</span></label><InputText v-model="exForm.title" class="w-full" /></div>
          <div class="form-group"><label>Hướng dẫn</label><textarea v-model="exForm.instruction" class="form-control" rows="2" /></div>
          <div class="form-group"><label>Prompt mẫu</label><textarea v-model="exForm.sample_prompt" class="form-control" rows="3" /></div>
          <div class="form-group"><label>Output kỳ vọng</label><textarea v-model="exForm.expected_output" class="form-control" rows="2" /></div>
          <div class="form-row">
            <div class="form-group flex-1">
              <label>Độ khó</label>
              <Select v-model="exForm.difficulty" :options="diffOptions" optionLabel="label" optionValue="value" class="w-full" />
            </div>
            <div class="form-group" style="width:80px"><label>Thứ tự</label><InputNumber v-model="exForm.sort_order" class="w-full" /></div>
          </div>
          <div class="dialog-footer">
            <Button label="Hủy" severity="secondary" outlined @click="exDialog = false" type="button" />
            <Button :label="exForm.id ? 'Cập nhật' : 'Tạo'" icon="pi pi-check" type="submit" :loading="exForm.processing" />
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
import Select from 'primevue/select'

export default {
  components: { Head, Button, InputText, InputNumber, Select },
  layout: Layout,
  props: { categories: Array, stats: Object },
  data() {
    return {
      expandedCats: this.categories.map(c => c.id),
      // Lesson viewer
      lessonViewDialog: false, activeLesson: null, activeCatColor: '#8b5cf6', activeCatTitle: '',
      exerciseForms: {}, exerciseRatings: {},
      // Category dialog
      catDialog: false, catForm: this.emptyCatForm(),
      levelOptions: [
        { label: 'Cơ bản', value: 'beginner' }, { label: 'Trung cấp', value: 'intermediate' },
        { label: 'Nâng cao', value: 'advanced' }, { label: 'Expert', value: 'expert' },
      ],
      // Lesson dialog
      lessonDialog: false, lsForm: this.emptyLsForm(), examplesJson: '[]', tipsText: '',
      // Exercise dialog
      exDialog: false, exForm: this.emptyExForm(),
      diffOptions: [
        { label: 'Dễ', value: 'easy' }, { label: 'Trung bình', value: 'medium' }, { label: 'Khó', value: 'hard' },
      ],
    }
  },
  methods: {
    toggleCat(id) { const i = this.expandedCats.indexOf(id); if (i > -1) this.expandedCats.splice(i, 1); else this.expandedCats.push(id) },

    levelLabel(l) { return { beginner: 'Cơ bản', intermediate: 'Trung cấp', advanced: 'Nâng cao', expert: 'Expert' }[l] || l },
    diffLabel(d) { return { easy: 'Dễ', medium: 'Trung bình', hard: 'Khó' }[d] || d },

    copyText(t) { navigator.clipboard.writeText(t); this.$page.props.flash = { success: 'Đã copy!' } },

    // Lesson viewer
    openLessonView(ls, cat) {
      this.activeLesson = ls
      this.activeCatColor = cat.color
      this.activeCatTitle = cat.title
      this.exerciseForms = {}
      this.exerciseRatings = {}
      ls.exercises.forEach(ex => {
        this.exerciseForms[ex.id] = ex.last_prompt || ''
        this.exerciseRatings[ex.id] = ex.best_rating || 0
      })
      this.lessonViewDialog = true
    },
    submitExercise(ex) {
      this.$inertia.post(`/prompt-learning/exercises/${ex.id}/submit`, {
        user_prompt: this.exerciseForms[ex.id],
        rating: this.exerciseRatings[ex.id],
      }, { preserveScroll: true, onSuccess: () => { ex.completed = this.exerciseRatings[ex.id] >= 3 } })
    },

    // Category CRUD
    emptyCatForm() { return this.$inertia.form({ id: null, title: '', description: '', level: 'beginner', icon: 'pi pi-book', color: '#8b5cf6', sort_order: 0 }) },
    openCatDialog(c = null) {
      this.catForm = c ? this.$inertia.form({ id: c.id, title: c.title, description: c.description || '', level: c.level, icon: c.icon, color: c.color, sort_order: c.sort_order }) : this.emptyCatForm()
      this.catDialog = true
    },
    submitCat() {
      if (this.catForm.id) this.catForm.put(`/prompt-learning/categories/${this.catForm.id}`, { preserveScroll: true, onSuccess: () => { this.catDialog = false } })
      else this.catForm.post('/prompt-learning/categories', { preserveScroll: true, onSuccess: () => { this.catDialog = false } })
    },
    deleteCat(c) { if (confirm(`Xóa "${c.title}"?`)) this.$inertia.delete(`/prompt-learning/categories/${c.id}`, { preserveScroll: true }) },

    // Lesson CRUD
    emptyLsForm() { return this.$inertia.form({ id: null, category_id: null, title: '', content: '', examples: [], tips: [], sort_order: 0 }) },
    openLessonDialog(ls = null, catId = null) {
      if (ls) {
        this.lsForm = this.$inertia.form({ id: ls.id, category_id: ls.category_id, title: ls.title, content: ls.content || '', examples: ls.examples || [], tips: ls.tips || [], sort_order: ls.sort_order })
        this.examplesJson = JSON.stringify(ls.examples || [], null, 2)
        this.tipsText = (ls.tips || []).join('\n')
      } else { this.lsForm = this.emptyLsForm(); this.lsForm.category_id = catId; this.examplesJson = '[]'; this.tipsText = '' }
      this.lessonDialog = true
    },
    submitLesson() {
      try { this.lsForm.examples = JSON.parse(this.examplesJson) } catch { this.lsForm.examples = [] }
      this.lsForm.tips = this.tipsText.split('\n').map(s => s.trim()).filter(Boolean)
      if (this.lsForm.id) this.lsForm.put(`/prompt-learning/lessons/${this.lsForm.id}`, { preserveScroll: true, onSuccess: () => { this.lessonDialog = false } })
      else this.lsForm.post('/prompt-learning/lessons', { preserveScroll: true, onSuccess: () => { this.lessonDialog = false } })
    },
    deleteLesson(ls) { if (confirm(`Xóa "${ls.title}"?`)) this.$inertia.delete(`/prompt-learning/lessons/${ls.id}`, { preserveScroll: true }) },

    // Exercise CRUD
    emptyExForm() { return this.$inertia.form({ id: null, lesson_id: null, title: '', instruction: '', sample_prompt: '', expected_output: '', difficulty: 'easy', sort_order: 0 }) },
    openExDialog(ex = null, lsId = null) {
      if (ex) { this.exForm = this.$inertia.form({ id: ex.id, lesson_id: null, title: ex.title, instruction: ex.instruction || '', sample_prompt: ex.sample_prompt || '', expected_output: ex.expected_output || '', difficulty: ex.difficulty, sort_order: ex.sort_order }) }
      else { this.exForm = this.emptyExForm(); this.exForm.lesson_id = lsId }
      this.exDialog = true
    },
    submitExForm() {
      if (this.exForm.id) this.exForm.put(`/prompt-learning/exercises/${this.exForm.id}`, { preserveScroll: true, onSuccess: () => { this.exDialog = false } })
      else this.exForm.post('/prompt-learning/exercises', { preserveScroll: true, onSuccess: () => { this.exDialog = false } })
    },
  },
}
</script>

<style scoped>
/* ===== Header ===== */
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem; flex-wrap:wrap; gap:.75rem }
.header-content { display:flex; align-items:center; gap:.85rem }
.header-icon-wrapper { width:48px; height:48px; border-radius:14px; background:linear-gradient(135deg,#8b5cf6,#7c3aed); display:flex; align-items:center; justify-content:center; color:white; font-size:1.25rem; box-shadow:0 4px 14px rgba(139,92,246,.3) }
.page-title { font-size:1.5rem; font-weight:800; color:#0f172a; margin:0; letter-spacing:-.02em }
.page-subtitle { font-size:.82rem; color:#64748b; margin:.15rem 0 0 }
.header-actions { display:flex; gap:.5rem }

/* ===== Progress ===== */
.progress-wrap { display:flex; align-items:center; gap:.75rem; margin-bottom:1.25rem }
.progress-bar { flex:1; height:10px; background:#e2e8f0; border-radius:5px; overflow:hidden }
.progress-fill { height:100%; border-radius:5px; background:linear-gradient(90deg,#8b5cf6,#7c3aed); transition:width .6s cubic-bezier(.4,0,.2,1) }
.progress-label { font-size:.82rem; font-weight:700; color:#7c3aed; min-width:40px }

/* ===== Stats ===== */
.stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:.75rem; margin-bottom:1.5rem }
.stat-card { display:flex; align-items:center; gap:.75rem; padding:.85rem 1rem; background:white; border-radius:14px; border:1.5px solid #f1f5f9; box-shadow:0 1px 3px rgba(0,0,0,.04); transition:all .2s }
.stat-card:hover { transform:translateY(-2px); box-shadow:0 4px 14px rgba(0,0,0,.06) }
.stat-icon { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:.92rem }
.sc-cat .stat-icon { background:#f5f3ff; color:#7c3aed }
.sc-les .stat-icon { background:#dbeafe; color:#2563eb }
.sc-ex .stat-icon { background:#fef3c7; color:#d97706 }
.sc-prog .stat-icon { background:#f0fdf4; color:#16a34a }
.stat-body { display:flex; flex-direction:column } .stat-value { font-size:1.15rem; font-weight:800; color:#0f172a } .stat-label { font-size:.68rem; color:#94a3b8 }

/* ===== Categories ===== */
.categories { display:flex; flex-direction:column; gap:.75rem }
.cat-block { background:white; border-radius:16px; border:1.5px solid #f1f5f9; box-shadow:0 1px 3px rgba(0,0,0,.04); overflow:hidden; transition:all .2s }
.cat-header { display:flex; align-items:center; justify-content:space-between; padding:1rem 1.25rem; cursor:pointer; transition:background .15s; gap:1rem }
.cat-header:hover { background:#fafbfc }
.cat-left { display:flex; align-items:center; gap:.85rem; flex:1; min-width:0 }
.cat-icon { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0 }
.cat-info { min-width:0 }
.cat-title-row { display:flex; align-items:center; gap:.45rem }
.cat-title { font-size:.95rem; font-weight:700; color:#1e293b; margin:0 }
.level-badge { font-size:.5rem; font-weight:700; padding:.1rem .4rem; border-radius:5px; text-transform:uppercase; letter-spacing:.04em }
.lvl-beginner { background:#d1fae5; color:#059669 }
.lvl-intermediate { background:#dbeafe; color:#2563eb }
.lvl-advanced { background:#fef3c7; color:#d97706 }
.lvl-expert { background:#fef2f2; color:#ef4444 }
.cat-desc { font-size:.72rem; color:#94a3b8; margin:.1rem 0 0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis }
.cat-right { display:flex; align-items:center; gap:.75rem; flex-shrink:0 }
.cat-progress-wrap { display:flex; align-items:center; gap:.45rem }
.cat-progress-bar { width:80px; height:6px; background:#f1f5f9; border-radius:3px; overflow:hidden }
.cat-progress-fill { height:100%; border-radius:3px; transition:width .4s }
.cat-progress-text { font-size:.68rem; font-weight:600; color:#64748b }
.cat-actions { display:flex; gap:.125rem }
.expand-icon { font-size:.65rem; color:#94a3b8 }

/* ===== Lessons List ===== */
.lessons-list { padding:0 1.25rem 1rem; display:flex; flex-direction:column; gap:.4rem }
.lesson-card { display:flex; align-items:center; justify-content:space-between; padding:.7rem .85rem; background:#fafbfc; border-radius:10px; border:1px solid #f1f5f9; cursor:pointer; transition:all .2s; gap:.5rem }
.lesson-card:hover { background:#f5f3ff; border-color:#e9e5ff }
.ls-left { display:flex; align-items:center; gap:.65rem; flex:1; min-width:0 }
.ls-check { width:28px; height:28px; border-radius:8px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; font-size:.65rem; color:#94a3b8; flex-shrink:0 }
.ls-done { background:#d1fae5; color:#059669 }
.ls-info { min-width:0 }
.ls-title { font-size:.78rem; font-weight:600; color:#1e293b; margin:0 }
.ls-meta { display:flex; gap:.5rem; margin-top:.15rem }
.ls-meta span { font-size:.58rem; color:#94a3b8; display:flex; align-items:center; gap:.15rem }
.ls-meta i { font-size:.5rem }
.ls-actions { display:flex; gap:.125rem; flex-shrink:0 }

/* ===== Lesson Viewer ===== */
.lesson-viewer { background:white; border-radius:18px; width:900px; max-width:95vw; max-height:90vh; display:flex; flex-direction:column; box-shadow:0 24px 64px rgba(0,0,0,.18); animation:slideUp .25s ease-out }
.lv-header { display:flex; align-items:center; justify-content:space-between; padding:1.25rem 1.5rem; border-bottom:3px solid; flex-shrink:0 }
.lv-header-left { display:flex; align-items:center; gap:.65rem }
.lv-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; color:white; font-size:.9rem }
.lv-header h3 { font-size:1.05rem; font-weight:700; color:#1e293b; margin:0 }
.lv-level { font-size:.65rem; color:#94a3b8 }
.lv-body { padding:1.5rem; overflow-y:auto; flex:1 }
.lv-section { margin-bottom:1.5rem }
.lv-section-title { font-size:.68rem; font-weight:700; color:#8b5cf6; text-transform:uppercase; letter-spacing:.05em; margin-bottom:.65rem; display:flex; align-items:center; gap:.3rem }
.lv-section-title i { font-size:.6rem }
.lv-content { font-size:.85rem; color:#334155; line-height:1.7; white-space:pre-line }

/* Prompt Examples */
.prompt-example { margin-bottom:.85rem; border-radius:10px; border:1px solid #e9e5ff; overflow:hidden }
.pe-label { font-size:.62rem; font-weight:700; color:#7c3aed; padding:.35rem .75rem; background:#f5f3ff; text-transform:uppercase; letter-spacing:.04em }
.pe-prompt { position:relative; padding:.75rem; background:#1e1b4b; color:#e0e7ff }
.pe-prompt pre { margin:0; white-space:pre-wrap; word-wrap:break-word; font-size:.78rem; font-family:'JetBrains Mono','Fira Code',monospace; line-height:1.6 }
.copy-btn { position:absolute; top:.5rem; right:.5rem; background:rgba(255,255,255,.1); border:none; color:#a5b4fc; width:28px; height:28px; border-radius:6px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all .2s }
.copy-btn:hover { background:rgba(255,255,255,.2); color:white }
.pe-note { font-size:.68rem; color:#6366f1; padding:.4rem .75rem; background:#f5f3ff; display:flex; align-items:center; gap:.25rem }
.pe-note i { font-size:.55rem }

/* Tips */
.tips-list { margin:0; padding-left:1.25rem; list-style:none }
.tips-list li { font-size:.78rem; color:#334155; line-height:1.7; padding:.15rem 0 }
.tips-list li::before { content:'💡'; margin-right:.35rem }

/* Exercises */
.exercise-card { padding:1rem; background:#fafbfc; border-radius:12px; border:1.5px solid #f1f5f9; margin-bottom:.75rem; transition:all .2s }
.ex-done { background:#f0fdf4; border-color:#d1fae5 }
.ex-header { margin-bottom:.35rem }
.ex-title-row { display:flex; align-items:center; gap:.35rem; flex-wrap:wrap }
.ex-diff { font-size:.5rem; font-weight:700; padding:.1rem .35rem; border-radius:4px; text-transform:uppercase }
.diff-easy { background:#d1fae5; color:#059669 }
.diff-medium { background:#fef3c7; color:#d97706 }
.diff-hard { background:#fef2f2; color:#ef4444 }
.ex-title { font-size:.82rem; font-weight:700; color:#1e293b; margin:0 }
.ex-badge { font-size:.52rem; font-weight:700; padding:.1rem .35rem; border-radius:4px }
.badge-done { background:#d1fae5; color:#059669 }
.ex-instruction { font-size:.78rem; color:#475569; margin:.2rem 0 .5rem; line-height:1.5 }
.ex-sample { position:relative; border-radius:8px; overflow:hidden; margin-bottom:.65rem; border:1px solid #e9e5ff }
.ex-sample-label { font-size:.58rem; font-weight:700; color:#7c3aed; padding:.25rem .65rem; background:#f5f3ff; text-transform:uppercase }
.ex-sample pre { margin:0; padding:.65rem; background:#1e1b4b; color:#e0e7ff; font-size:.72rem; font-family:'JetBrains Mono','Fira Code',monospace; white-space:pre-wrap; word-wrap:break-word; line-height:1.5 }
.ex-form { margin-top:.5rem }
.ex-textarea { width:100%; padding:.65rem .85rem; border:1.5px solid #e2e8f0; border-radius:10px; font-size:.82rem; color:#1e293b; font-family:inherit; resize:vertical; outline:none; transition:all .2s }
.ex-textarea:focus { border-color:#8b5cf6; box-shadow:0 0 0 3px rgba(139,92,246,.1) }
.ex-form-footer { display:flex; justify-content:space-between; align-items:center; margin-top:.5rem }
.rating-input { display:flex; align-items:center; gap:.2rem }
.rating-label { font-size:.68rem; font-weight:600; color:#64748b; margin-right:.25rem }
.star-btn { background:none; border:none; font-size:1.15rem; color:#d4d4d8; cursor:pointer; padding:0; transition:color .15s }
.star-active { color:#f59e0b }
.ex-prev { margin-top:.5rem; padding:.5rem .65rem; background:#f8fafc; border-radius:6px; border:1px solid #f1f5f9 }
.ex-prev-label { font-size:.55rem; font-weight:700; color:#94a3b8; text-transform:uppercase; margin-bottom:.2rem }
.ex-prev pre { margin:0; font-size:.72rem; color:#475569; font-family:'JetBrains Mono','Fira Code',monospace; white-space:pre-wrap; word-wrap:break-word }

/* ===== Dialogs ===== */
.dialog-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:1000; backdrop-filter:blur(4px); padding:1.5rem }
.dialog-card { background:white; border-radius:18px; width:620px; max-width:100%; max-height:calc(100vh - 3rem); display:flex; flex-direction:column; box-shadow:0 24px 64px rgba(0,0,0,.18); animation:slideUp .25s ease-out }
.dialog-wide { width:780px }
@keyframes slideUp { from{transform:translateY(20px);opacity:0} to{transform:translateY(0);opacity:1} }
.dialog-header { display:flex; align-items:center; justify-content:space-between; padding:1.25rem 1.5rem; border-bottom:1px solid #f1f5f9; flex-shrink:0 }
.dialog-header-left { display:flex; align-items:center; gap:.6rem }
.dialog-icon { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; color:white; font-size:.85rem }
.di-violet { background:linear-gradient(135deg,#8b5cf6,#7c3aed) }
.di-blue { background:linear-gradient(135deg,#3b82f6,#2563eb) }
.di-amber { background:linear-gradient(135deg,#f59e0b,#d97706) }
.dialog-header h3 { font-size:1rem; font-weight:700; color:#1e293b; margin:0 }
.dialog-close { background:none; border:none; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#94a3b8; cursor:pointer; transition:all .2s; flex-shrink:0 }
.dialog-close:hover { background:#fef2f2; color:#ef4444 }
.dialog-body { padding:1.25rem 1.5rem; overflow-y:auto; flex:1; min-height:0 }
.dialog-footer { display:flex; justify-content:flex-end; gap:.5rem; padding:1rem 1.5rem; border-top:1px solid #f1f5f9; flex-shrink:0 }

/* ===== Forms ===== */
.form-row { display:flex; gap:.75rem; flex-wrap:wrap }
.form-group { margin-bottom:.85rem; min-width:0 }
.flex-1 { flex:1; min-width:120px }
.w-full { width:100% }
.form-group label { display:block; font-size:.72rem; font-weight:600; color:#475569; margin-bottom:.35rem }
.req { color:#ef4444 }
.form-control { width:100%; padding:.55rem .75rem; border:1px solid #e2e8f0; border-radius:8px; font-size:.85rem; color:#1e293b; background:white; transition:all .2s; outline:none; font-family:inherit; resize:vertical }
.form-control:focus { border-color:#8b5cf6; box-shadow:0 0 0 3px rgba(139,92,246,.1) }
.code-input { font-family:'JetBrains Mono','Fira Code',monospace; font-size:.78rem }

/* ===== Transitions ===== */
.slide-enter-active, .slide-leave-active { transition:all .3s ease }
.slide-enter-from, .slide-leave-to { opacity:0; max-height:0; overflow:hidden }
.slide-enter-to, .slide-leave-from { opacity:1; max-height:3000px }

/* ===== Empty ===== */
.empty-state { text-align:center; padding:3rem 2rem; background:white; border-radius:16px; border:2px dashed #e2e8f0 }
.empty-icon { width:64px; height:64px; border-radius:16px; background:linear-gradient(135deg,#f5f3ff,#ede9fe); display:flex; align-items:center; justify-content:center; margin:0 auto 1rem; font-size:1.5rem; color:#7c3aed }
.empty-state h3 { font-size:1rem; font-weight:700; color:#1e293b; margin:0 0 .35rem }
.empty-state p { font-size:.82rem; color:#94a3b8; margin:0 }
.mt-1 { margin-top:.75rem }

/* ===== Responsive ===== */
@media (max-width:768px) {
  .page-header { flex-direction:column; align-items:flex-start }
  .stats-grid { grid-template-columns:repeat(2,1fr) }
  .cat-header { flex-direction:column; align-items:flex-start }
  .cat-right { width:100%; justify-content:space-between }
  .form-row { flex-direction:column }
  .dialog-overlay { padding:.75rem }
  .lesson-viewer { max-height:95vh }
  .lesson-card { flex-direction:column; align-items:flex-start }
}
</style>
