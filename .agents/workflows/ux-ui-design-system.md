---
name: BED CRM - UX/UI Design System
description: Design system, component patterns, and UX/UI guidelines for the BED CRM project. Ensures consistent styling, layout structure, and interaction patterns across all modules.
---

# BED CRM — UX/UI Design System Skill

Tài liệu này là **nguồn sự thật duy nhất** (single source of truth) về design system, component patterns, và UX/UI conventions cho dự án BED CRM. **Tất cả các page và component mới PHẢI tuân thủ** tài liệu này.

---

## 1. Tech Stack & Frameworks

| Layer | Technology |
|-------|-----------|
| CSS Framework | **TailwindCSS** (custom config) + **Vanilla CSS** (scoped) |
| UI Components | **PrimeVue v4** |
| Icons | **PrimeIcons** (`pi pi-*`) |
| Font | `Cerebri Sans` (fallback: system sans-serif) |
| JS Framework | **Vue 3** (Options API) + **Inertia.js** |
| Drag & Drop | `vuedraggable` |

> **QUAN TRỌNG:** PrimeVue components (`Button`, `Dialog`, `DataTable`, `Select`, `InputText`, `Message`, `Paginator`, etc.) là ưu tiên số 1 cho UI controls. Chỉ dùng native HTML khi PrimeVue không có component phù hợp.

---

## 2. Color Palette

### Brand Colors (Tailwind config)

```
primary-50:  #fef5f0     primary-500: #ef6820  (brand orange)
primary-100: #fde8dc     primary-600: #e04f0f
primary-200: #fbd0b8     primary-700: #ba3d0f
primary-300: #f8b089     primary-800: #943214
primary-400: #f48554     primary-900: #782b13
```

### Semantic Colors (used in scoped CSS)

| Purpose | Color | Usage |
|---------|-------|-------|
| **Accent/Interactive** | `#6366f1` (indigo-500) | Focus rings, active tabs, link hover, sidebar active icons |
| **Accent Light** | `#eef2ff` / `#e0e7ff` | Active tab bg, tag bg, category highlight |
| **Success** | `#10b981` / `#ecfdf5` | Won deals, published status, positive scores |
| **Warning** | `#f59e0b` / `#fffbeb` | Warm priority, draft status, pinned icons |
| **Danger** | `#ef4444` / `#fef2f2` | Hot priority, errors, lost deals, delete actions |
| **Info** | `#3b82f6` / `#eff6ff` | Cold priority, new status, info badges |
| **Neutral Dark** | `#0f172a` / `#1e293b` | Page titles, card titles |
| **Neutral Mid** | `#334155` / `#475569` | Body text, form labels |
| **Neutral Light** | `#64748b` / `#94a3b8` | Subtitles, meta text, timestamps |
| **Neutral Faint** | `#cbd5e1` / `#e2e8f0` | Borders, placeholders, disabled |
| **Bg Light** | `#f1f5f9` / `#f8fafc` | Card backgrounds, column bodies, audit groups |
| **Page Bg** | `#f0f2f5` | Main layout background |
| **Card Bg** | `white` | Cards, modals, filter bars |

### Sidebar Colors

| Element | Color |
|---------|-------|
| Sidebar bg | `linear-gradient(180deg, #111827, #0f172a, #111827)` |
| Menu text | `rgba(255,255,255, 0.5)` → hover `0.85` |
| Active item bg | `rgba(239,104,32, 0.1)` |
| Active icon | `#f48554` |
| Active label | `#f8b089` |
| Section title | `rgba(255,255,255, 0.3)` |
| Separator | `rgba(255,255,255, 0.06)` |
| User avatar bg | `linear-gradient(135deg, #ef6820, #e04f0f)` |

---

## 3. Typography Scale

| Element | CSS | Usage |
|---------|-----|-------|
| **Page title** | `font-size: 1.5rem; font-weight: 700; color: #0f172a; letter-spacing: -0.02em` | H1 trên mỗi trang |
| **Page subtitle** | `font-size: 0.82rem; color: #94a3b8` | Đếm kết quả, mô tả |
| **Section title** | `font-size: 1rem; font-weight: 600; color: #1e293b` | Heading trong form sections |
| **Card title** | `font-size: 0.85rem-0.92rem; font-weight: 600; color: #1e293b` | Tên trong card |
| **Body text** | `font-size: 0.85rem; color: #334155` | Nội dung chính |
| **Small/Meta** | `font-size: 0.78rem; color: #64748b` | Meta, excerpt, contact info |
| **Tiny/Badge** | `font-size: 0.65rem-0.72rem; font-weight: 600-700` | Badges, counts, labels |
| **Menu item** | `font-size: 0.78rem; font-weight: 450` | Sidebar items |
| **Section label** | `font-size: 0.65rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06-0.08em` | Menu section headers |

---

## 4. Spacing & Border Radius

### Border Radius

| Size | Value | Usage |
|------|-------|-------|
| `sm` | `4px-6px` | Badges, tags, small chips |
| `md` | `8px` | Form inputs, buttons, menu items |
| `lg` | `10px-12px` | Cards, sections, sidebar, modals |
| `xl` | `14px` | Data table card wrappers |
| `pill` | `20px` | Status badges |
| `round` | `50%` | User avatars, dots |

### Standard Spacing

| Context | Value |
|---------|-------|
| Page content padding | `1.5rem 2rem` (mobile: `1rem`) |
| Card padding | `1rem-1.5rem` |
| Form group margin-bottom | `0.75rem` |
| Section margin-bottom | `1rem-1.25rem` |
| Kanban column gap | `0.75rem` |
| Card gap (in list) | `0.5rem` |
| Inline icon gap | `0.35rem-0.5rem` |

---

## 5. Layout Patterns

### 5.1 Admin Layout (`Layout.vue`)

```
┌─────────────────────────────────────────┐
│ Sidebar (260px fixed) │ Top Bar (60px)  │
│ ┌──────────────┐      │ ┌────────────┐ │
│ │ Logo         │      │ │ Breadcrumb │ │
│ │ Menu sections│      │ │   User btn │ │
│ │ ...          │      │ └────────────┘ │
│ │ User card    │      │ Main Content   │
│ └──────────────┘      │ (slot)         │
└─────────────────────────────────────────┘
```

- Sidebar: `position: fixed`, `z-index: 50`
- Top bar: `position: sticky`, `z-index: 40`
- Main content: `margin-left: 260px`
- Mobile: sidebar becomes overlay

### 5.2 Page Header Pattern

Mọi trang đều có header dạng:
```html
<div class="page-header">
  <div>
    <h1 class="page-title">Title</h1>
    <p class="page-subtitle">Subtitle / count</p>
  </div>
  <Link href="/module/create">
    <Button label="Tạo mới" icon="pi pi-plus" />
  </Link>
</div>
```

CSS:
```css
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.25rem;
}
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; letter-spacing: -0.02em; margin: 0; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0.15rem 0 0; }
```

### 5.3 Data Table Page (ví dụ: Leads)

```
[Page Header: title + create button]
[Filter Bar: search + select filters + reset]
[Data Card:
  DataTable (PrimeVue)
  Pagination
]
```

### 5.4 Kanban Board Page (ví dụ: Deals, Sales Pipeline)

```
[Page Header: title + stats badges + create button]
[Kanban Board (flex, horizontal scroll):
  [Column: header + draggable cards + empty state] × N
]
```

### 5.5 Edit Page with Tabs (ví dụ: Sales Pipeline Edit)

```
[Page Header: back + title + priority badge + action buttons]
[Stage Progress Bar]
[Tab Container:
  Tab Nav (pill buttons)
  Tab Content (forms/timeline)
]
```

### 5.6 Wiki-style Layout (sidebar + content)

```
[wiki-layout (flex):
  [wiki-sidebar (280px, sticky):
    search, categories, drafts
  ]
  [wiki-main (flex: 1):
    content area
  ]
]
```

---

## 6. Component Patterns

### 6.1 Form Controls

**Dùng PrimeVue components** khi có trong danh sách (list) page (ví dụ `InputText`, `Select`). **Dùng native HTML** khi trong form tạo/sửa (ví dụ `<input>`, `<select>`, `<textarea>`).

Native form controls CSS:
```css
.form-control {
  width: 100%;
  padding: 0.55rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.85rem;
  color: #1e293b;
  background: white;
  transition: all 0.2s;
  outline: none;
  font-family: inherit;
}
.form-control:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}
```

Labels:
```css
.form-group label {
  display: block;
  font-size: 0.78rem;
  font-weight: 500;
  color: #475569;
  margin-bottom: 0.35rem;
}
.required { color: #ef4444; }
.optional { color: #94a3b8; font-weight: 400; }
```

### 6.2 Buttons

- Dùng PrimeVue `<Button>` cho tất cả actions
- Props phổ biến: `label`, `icon`, `severity`, `size="small"`, `text`, `rounded`
- Severity mapping:
  - Primary action → default (không severity)
  - Secondary → `severity="secondary"` + `text`
  - Danger → `severity="danger"` + `text`
  - Success → `severity="success"`

### 6.3 Status/Priority Badges

Pattern chung:
```css
.badge {
  font-size: 0.65rem-0.72rem;
  font-weight: 600-700;
  padding: 0.15rem-0.25rem 0.45rem-0.6rem;
  border-radius: 6px hoặc 20px (pill);
  text-transform: uppercase (cho priority);
  letter-spacing: 0.04em;
}
```

Color mapping:
```css
/* Priority */
.priority-hot  { background: #fef2f2; color: #ef4444; }
.priority-warm { background: #fffbeb; color: #f59e0b; }
.priority-cold { background: #eff6ff; color: #3b82f6; }

/* Status (pill format) */
.status-new       { background: #eff6ff; color: #3b82f6; }
.status-contacted { background: #fef3c7; color: #d97706; }
.status-qualified { background: #d1fae5; color: #059669; }
.status-won       { background: #d1fae5; color: #059669; }
.status-lost      { background: #fee2e2; color: #dc2626; }
```

### 6.4 Cards

```css
.card {
  background: white;
  border-radius: 10px-12px;
  padding: 0.85rem-1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05-0.06);
  border: 1px solid #f1f5f9;
  transition: all 0.2s;
}
.card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.06-0.08);
  border-color: #e2e8f0;
  transform: translateY(-1px); /* subtle lift */
}
```

### 6.5 Filter Bar

```css
.filter-bar {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.85rem 1rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  border: 1px solid #f1f5f9;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}
```

### 6.6 Data Table Wrapper

```css
.data-card {
  background: white;
  border-radius: 14px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
  border: 1px solid #f1f5f9;
  overflow: hidden;
}
```

### 6.7 Dialogs/Modals

- Dùng PrimeVue `<Dialog>` hoặc **Custom Popup Overlay** (xem 6.11)
- Props: `v-model:visible`, `header`, `:modal="true"`, `:style="{ width: '420px-450px' }"`
- Footer slot: `<Button label="Hủy" severity="secondary" text />` + action button

### 6.8 Empty State

```css
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 3rem;
  color: #94a3b8;
}
.empty-state i { font-size: 2rem-2.5rem; color: #cbd5e1; }
.empty-state h3 { font-size: 1.1rem; color: #475569; }
.empty-state p { font-size: 0.82rem; }
```

### 6.9 User/Assigned Avatars

```css
/* Letter avatar */
.avatar {
  width: 26px-34px;
  height: same;
  border-radius: 50% (round) or 8px-10px (square);
  background: #e0e7ff;      /* or gradient for user: linear-gradient(135deg, #6366f1, #8b5cf6) */
  color: #4f46e5;           /* or white for gradient */
  font-size: 0.55rem-0.7rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  text-transform: uppercase;
}
```

### 6.10 Score/Progress Bar

```css
.bar-track {
  flex: 1; height: 4px-6px;
  background: #f1f5f9;
  border-radius: 2px-3px;
  overflow: hidden;
}
.bar-fill {
  height: 100%;
  border-radius: same;
  transition: width 0.3s;
}
/* Color classes: */
.bar-good    { background: #10b981; }
.bar-warning { background: #f59e0b; }
.bar-low     { background: #ef4444; }
.bar-neutral { background: #94a3b8; }
```

### 6.11 Popup/Dialog CRUD Pattern ⭐ (Preferred for new modules)

> **KHI NÀO DÙNG POPUP**: Sử dụng popup cho Create/Edit khi form **KHÔNG quá phức tạp** (< 10 fields, không có sub-forms lồng nhau như line items). Ví dụ: Biên bản, Biểu mẫu, Hướng dẫn CRM, Cài đặt đơn giản.
>
> **KHI NÀO DÙNG TRANG RIÊNG**: Khi form **phức tạp** (dynamic line items, multi-section, dependent fields). Ví dụ: Báo giá (có line items), Hợp đồng, Deals.

#### Popup Structure

```html
<!-- Overlay -->
<div v-if="dialog" class="dialog-overlay" @click.self="dialog = false">
  <div class="dialog-card">
    <!-- Header -->
    <div class="dialog-header">
      <div class="dialog-header-left">
        <div class="dialog-icon"><i class="pi pi-file" /></div>
        <h3>{{ form.id ? 'Chỉnh sửa' : 'Thêm mới' }} item</h3>
      </div>
      <button class="dialog-close" @click="dialog = false"><i class="pi pi-times" /></button>
    </div>
    <!-- Body -->
    <form @submit.prevent="submitForm" class="dialog-body">
      <!-- ...form fields... -->
      <!-- Footer -->
      <div class="dialog-footer">
        <Button label="Hủy" severity="secondary" outlined @click="dialog = false" type="button" />
        <Button :label="form.id ? 'Cập nhật' : 'Tạo'" icon="pi pi-check" type="submit" :loading="form.processing" />
      </div>
    </form>
  </div>
</div>
```

#### Popup CSS

```css
.dialog-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.45);
  display: flex; align-items: center; justify-content: center;
  z-index: 1000; backdrop-filter: blur(4px);
}
.dialog-card {
  background: white; border-radius: 18px;
  width: 620px; max-width: 92vw; max-height: 88vh;
  overflow-y: auto;
  box-shadow: 0 24px 64px rgba(0,0,0,.18);
  animation: slideUp .25s ease-out;
}
@keyframes slideUp {
  from { transform: translateY(20px); opacity: 0 }
  to { transform: translateY(0); opacity: 1 }
}
.dialog-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9;
}
.dialog-header-left { display: flex; align-items: center; gap: .6rem }
.dialog-icon {
  width: 36px; height: 36px; border-radius: 10px;
  background: linear-gradient(135deg, [THEME-COLOR-1], [THEME-COLOR-2]);
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: .85rem;
}
.dialog-header h3 { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0 }
.dialog-close {
  background: none; border: none; width: 32px; height: 32px;
  border-radius: 8px; display: flex; align-items: center; justify-content: center;
  color: #94a3b8; cursor: pointer; transition: all .2s;
}
.dialog-close:hover { background: #fef2f2; color: #ef4444 }
.dialog-body { padding: 1.25rem 1.5rem }
.dialog-footer {
  display: flex; justify-content: flex-end; gap: .5rem;
  padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9;
}
```

#### Popup JS Methods Pattern

```javascript
methods: {
  // Empty form factory
  emptyForm() {
    return this.$inertia.form({
      id: null, title: '', /* ...other fields... */
    })
  },
  // Open dialog (create or edit)
  openDialog(item = null) {
    if (item) {
      this.form = this.$inertia.form({
        id: item.id, title: item.title, /* ...populate fields... */
      })
    } else {
      this.form = this.emptyForm()
    }
    this.dialog = true
  },
  // Submit (auto-detect create vs update)
  submitForm() {
    if (this.form.id) {
      this.form.put(`/items/${this.form.id}`, {
        preserveScroll: true, onSuccess: () => { this.dialog = false }
      })
    } else {
      this.form.post('/items', {
        preserveScroll: true, onSuccess: () => { this.dialog = false }
      })
    }
  },
  // Delete with confirm
  deleteItem(item) {
    if (confirm(`Xóa "${item.title}"?`)) {
      this.$inertia.delete(`/items/${item.id}`, { preserveScroll: true })
    }
  },
}
```

#### Backend: Popup Pattern Controller

```php
// store() and update() đều return back() thay vì redirect
public function store(Request $request) {
    // validate + create
    return back()->with('success', 'Đã tạo.');
}
public function update(Request $request, Model $model) {
    // validate + update
    return back()->with('success', 'Đã cập nhật.');
}
// Không cần create() hoặc edit() method (không có trang riêng)
```

#### Routes: Popup Pattern

```php
// Không cần create/edit routes (không dùng resource)
Route::get('items', [Controller::class, 'index'])->name('items.index');
Route::post('items', [Controller::class, 'store'])->name('items.store');
Route::put('items/{item}', [Controller::class, 'update'])->name('items.update');
Route::delete('items/{item}', [Controller::class, 'destroy'])->name('items.destroy');
```

### 6.12 Module Color Themes

Mỗi module mới cần **1 accent color riêng** để phân biệt. Sử dụng cho:
- Header icon gradient
- Search focus ring
- Card hover border
- Stat chips
- Dialog icon gradient

| Module | Primary | Gradient | Usage |
|--------|---------|----------|-------|
| Sales/Leads | `#6366f1` | `#6366f1 → #8b5cf6` | Indigo |
| Products | `#f59e0b` | `#f59e0b → #d97706` | Amber |
| Quotations | `#3b82f6` | `#3b82f6 → #2563eb` | Blue |
| Contracts | `#059669` | `#059669 → #047857` | Green |
| Approvals | `#8b5cf6` | `#8b5cf6 → #7c3aed` | Purple |
| Documents | `#ec4899` | `#ec4899 → #db2777` | Pink |
| CRM Guides | `#0ea5e9` | `#0ea5e9 → #0284c7` | Sky |
| System/Tools | `#64748b` | `#64748b → #475569` | Slate |

### 6.13 Toast Notification System

> **File:** `resources/js/Shared/FlashMessages.vue`
> **Vị trí:** Global — mount trong `Layout.vue`, Teleport to `body`
> **Trigger:** Tự động bắt `$page.props.flash` từ backend

#### Khi nào dùng

| Trường hợp | Type |
|------------|------|
| CRUD thành công | `success` |
| Validation error | `error` |
| Cảnh báo (delete, irreversible) | `warning` |
| Thông tin chung | `info` |

#### UX Specifications

| Property | Value |
|----------|-------|
| Position | Top-right, fixed |
| Max width | 400px |
| Auto-dismiss | 4500ms |
| Animation in | `slideIn` from right, 0.35s cubic-bezier |
| Animation out | `slideOut` to right, 0.25s ease-in |
| Progress bar | 3px, gradient, shrinks over duration |
| Border-left | 4px colored by severity |
| Icon | 32×32px rounded-10px gradient background |
| Z-index | 9999 |
| Click | Click toast to dismiss |
| Close button | ✕ icon, top-right |

#### Severity Colors

| Type | Left Border | Icon BG | Title Color |
|------|-------------|---------|-------------|
| `success` | `#10b981` | `#ecfdf5 → #d1fae5` | `#059669` |
| `error` | `#ef4444` | `#fef2f2 → #fecaca` | `#dc2626` |
| `warning` | `#f59e0b` | `#fffbeb → #fde68a` | `#d97706` |
| `info` | `#3b82f6` | `#eff6ff → #dbeafe` | `#2563eb` |

#### Backend Integration

```php
// Controller — trigger toast từ backend
return back()->with('success', 'Đã tạo thành công.');
return back()->with('error', 'Không thể xóa mục này.');
return back()->with('warning', 'Hành động này không thể hoàn tác.');
return back()->with('info', 'Dữ liệu đã được cập nhật.');
```

#### Component Structure

```html
<!-- Toast Container (Teleport to body) -->
<div class="toast-container">
  <div class="toast-item toast-{type}">
    <div class="toast-icon-wrap"><i class="pi pi-check-circle" /></div>
    <div class="toast-body">
      <span class="toast-title">Thành công</span>
      <p class="toast-message">Đã tạo sản phẩm.</p>
    </div>
    <button class="toast-close"><i class="pi pi-times" /></button>
    <div class="toast-progress">
      <div class="toast-progress-bar bar-success" />
    </div>
  </div>
</div>
```

#### Key CSS

```css
.toast-container {
  position: fixed; top: 1.25rem; right: 1.25rem;
  z-index: 9999; max-width: 400px;
}
.toast-item {
  border-radius: 14px; background: white;
  box-shadow: 0 8px 32px rgba(0,0,0,.12);
  border-left: 4px solid; /* color by type */
  overflow: hidden;
}
.toast-progress-bar {
  animation: progressShrink linear forwards;
}
@keyframes progressShrink { from{width:100%} to{width:0%} }
```

---

## 7. Kanban Board Pattern

### Column Structure

```css
.kanban-board {
  display: flex;
  gap: 0.75rem;
  overflow-x: auto;
  padding-bottom: 1rem;
  min-height: calc(100vh - 180px);
}
.kanban-column {
  flex-shrink: 0;
  width: 280px;
}
.column-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.65rem 0.75rem;
  background: white;
  border-radius: 10px;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
  border: 1px solid #f1f5f9;
  margin-bottom: 0.5rem;
}
.column-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  min-height: 200px;
  padding: 0.25rem;
  border-radius: 10px;
  background: rgba(241, 245, 249, 0.5);
}
```

### Drag States

```css
.ghost-card {
  opacity: 0.4;
  background: #f1f5f9 !important;
  border: 2px dashed #94a3b8 !important;
}
.chosen-card {
  cursor: grabbing !important;
  box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
  transform: rotate(1deg);
}
```

### Stage Dots (colored indicators)

Mỗi stage cần 1 dot color riêng, ví dụ:
```css
.dot-prospecting { background: #6366f1; }
.dot-qualification { background: #06b6d4; }
.dot-proposal { background: #8b5cf6; }
/* ... */
```

---

## 8. Transitions & Animations

### Standard Transition

```css
transition: all 0.2s;          /* general elements */
transition: all 0.15s ease;    /* hover effects, small interactive */
transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);  /* sidebar, layout shifts */
```

### Micro-animations

- **Card hover lift**: `transform: translateY(-1px)` + box-shadow increase
- **Drag rotate**: `transform: rotate(1deg)` on chosen-card
- **Focus ring**: `box-shadow: 0 0 0 3px rgba(99,102,241, 0.1)` (indigo glow)
- **Scale on active**: `transform: scale(1.1)` for stage step dots
- **Slide-up animation** (dropdown panels, popup dialogs):
  ```css
  @keyframes slideUp {
    from { opacity: 0; transform: translateY(10px-20px); }
    to { opacity: 1; transform: translateY(0); }
  }
  ```

### Hover Patterns

- **Links**: color shift to `#6366f1` (indigo) hoặc `#4f46e5`
- **Cards**: box-shadow increase + subtle border-color change
- **Buttons**: dùng PrimeVue's built-in hover
- **Menu items**: bg `rgba(255,255,255,0.06)` + text lighten

---

## 9. Responsive Breakpoints

| Breakpoint | Value | Behavior |
|-----------|-------|----------|
| Mobile | `max-width: 640px` | Stack grids, hide labels |
| Tablet | `max-width: 768px` | Sidebar overlay, single-column forms |
| Desktop | > 768px | Full layout |

### Key Responsive Rules

```css
@media (max-width: 768px) {
  .sidebar { transform: translateX(-100%); }
  .main-wrapper { margin-left: 0; }
  .mobile-menu-btn { display: flex; }
  .form-grid { grid-template-columns: 1fr; }
  /* Wiki layout: column direction */
  .wiki-layout { flex-direction: column; }
  .wiki-sidebar { width: 100%; position: static; }
  /* Dialog responsive */
  .dialog-card { max-width: 95vw; max-height: 92vh; }
}
```

---

## 10. Page Script Conventions

### Options API Pattern

```vue
<script>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'

export default {
  components: { Head, Link, Button },
  layout: Layout,
  props: { /* server data */ },
  setup(props) {
    const form = useForm({ /* fields */ })
    return { form }
  },
  data() { return { /* local state */ } },
  computed: { /* derived */ },
  methods: {
    submit() { this.form.post('/endpoint') },
  },
}
</script>
```

### Standard Methods

```javascript
// Format currency (VND)
formatCurrency(value) {
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency', currency: 'VND', maximumFractionDigits: 0,
  }).format(value)
}

// Format date
formatDate(d) {
  return new Date(d).toLocaleDateString('vi-VN', {
    day: 'numeric', month: 'short', year: 'numeric'
  })
}

// Initials from name
initials(name) {
  if (!name) return '?'
  return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
}
```

---

## 11. Navigation & Routing

### Menu Item Pattern (`MenuItem.vue`)

```html
<MenuItem href="/route" icon="pi pi-icon" :active="isUrl('route')">Label</MenuItem>
```

### Inertia Navigation

```javascript
// Preserve state navigation
router.get('/url', params, { preserveState: true, replace: true })

// Form submission
form.post('/url')     // create
form.put('/url')      // update
form.delete('/url')   // delete

// Stage update (PATCH)
router.patch('/url', { stage: value }, { preserveScroll: true, preserveState: true })
```

---

## 12. Checklist: Tạo Page Mới

Khi tạo page mới, kiểm tra:

- [ ] Dùng `<Head title="..." />` cho page title
- [ ] Dùng `layout: Layout` trong component
- [ ] Page header: flex, space-between, title + action button
- [ ] Cards: `background: white; border-radius: 12px; box-shadow: 0 1px 3px; border: 1px solid #f1f5f9`
- [ ] Form controls: `.form-control` class, focus ring `#6366f1`
- [ ] Badges dùng đúng color mapping (không tự chọn màu)
- [ ] Empty states cho danh sách rỗng
- [ ] Mobile responsive: `@media (max-width: 768px)`
- [ ] PrimeVue `Button` cho tất cả buttons (không dùng native `<button>` cho actions)
- [ ] Flash messages tự động qua `FlashMessages.vue` trong Layout
- [ ] Vietnamese labels cho tất cả text hiển thị
- [ ] Scoped CSS — KHÔNG dùng global styles
- [ ] **Popup vs Page**: Form đơn giản → dùng popup dialog (6.11). Form phức tạp → dùng trang riêng
- [ ] **Module color**: Chọn 1 accent color riêng cho module (xem 6.12)
- [ ] **Slide-up animation**: Dialog phải có `animation: slideUp .25s ease-out`
