<template>
  <div>
    <Head title="Google My Business" />

    <div class="page-header">
      <div>
        <h1 class="page-title"><i class="pi pi-map-marker" style="color:#ea4335;" /> Google My Business</h1>
        <p class="page-subtitle">Quản lý listing, reviews và insights GMB</p>
      </div>
      <button class="btn-add" @click="showAddLoc = true"><i class="pi pi-plus" /> Thêm Location</button>
    </div>

    <div class="stats-row">
      <div class="stat-card"><div class="stat-icon si-loc"><i class="pi pi-map-marker" /></div><div class="stat-body"><span class="stat-val">{{ stats.total_locations }}</span><span class="stat-lbl">Locations</span></div></div>
      <div class="stat-card"><div class="stat-icon si-star"><i class="pi pi-star-fill" /></div><div class="stat-body"><span class="stat-val stat-gold">{{ stats.avg_rating }}</span><span class="stat-lbl">Avg Rating</span></div></div>
      <div class="stat-card"><div class="stat-icon si-reviews"><i class="pi pi-comments" /></div><div class="stat-body"><span class="stat-val">{{ stats.total_reviews }}</span><span class="stat-lbl">Reviews</span></div></div>
      <div class="stat-card"><div class="stat-icon si-views"><i class="pi pi-eye" /></div><div class="stat-body"><span class="stat-val">{{ formatNum(stats.total_views) }}</span><span class="stat-lbl">Views</span></div></div>
    </div>

    <!-- Location Selector -->
    <div v-if="locations.length" class="loc-selector">
      <button v-for="loc in locations" :key="loc.id" class="loc-chip" :class="{ active: selectedLocationId === loc.id }" @click="selectLocation(loc.id)">
        <i class="pi pi-map-marker" /> {{ loc.location_name }}
        <span class="lc-rating">⭐{{ loc.avg_rating }}</span>
      </button>
    </div>

    <!-- Tabs -->
    <div class="tabs-bar">
      <button class="tab" :class="{ active: tab === 'overview' }" @click="tab = 'overview'"><i class="pi pi-chart-bar" /> Tổng quan</button>
      <button class="tab" :class="{ active: tab === 'reviews' }" @click="tab = 'reviews'"><i class="pi pi-star" /> Reviews <span v-if="reviews?.length" class="tab-count">{{ reviews.length }}</span></button>
      <button class="tab" :class="{ active: tab === 'posts' }" @click="tab = 'posts'"><i class="pi pi-megaphone" /> Bài đăng</button>
    </div>

    <!-- Overview -->
    <div v-show="tab === 'overview'">
      <div v-if="selectedLoc" class="loc-details">
        <div class="ld-grid">
          <div class="ld-card"><div class="ld-icon"><i class="pi pi-eye" /></div><span class="ld-val">{{ formatNum(selectedLoc.total_views) }}</span><span class="ld-lbl">Views</span></div>
          <div class="ld-card"><div class="ld-icon"><i class="pi pi-search" /></div><span class="ld-val">{{ formatNum(selectedLoc.total_searches) }}</span><span class="ld-lbl">Searches</span></div>
          <div class="ld-card"><div class="ld-icon"><i class="pi pi-bolt" /></div><span class="ld-val">{{ formatNum(selectedLoc.total_actions) }}</span><span class="ld-lbl">Actions</span></div>
          <div class="ld-card"><div class="ld-icon"><i class="pi pi-star-fill" /></div><span class="ld-val ld-gold">{{ selectedLoc.avg_rating }}</span><span class="ld-lbl">{{ selectedLoc.review_count }} reviews</span></div>
        </div>
        <div class="ld-info">
          <div v-if="selectedLoc.address" class="li-row"><i class="pi pi-map-marker" /><span>{{ selectedLoc.address }}</span></div>
          <div v-if="selectedLoc.phone" class="li-row"><i class="pi pi-phone" /><span>{{ selectedLoc.phone }}</span></div>
          <div v-if="selectedLoc.website" class="li-row"><i class="pi pi-globe" /><a :href="selectedLoc.website" target="_blank">{{ selectedLoc.website }}</a></div>
          <div v-if="selectedLoc.category" class="li-row"><i class="pi pi-tag" /><span>{{ selectedLoc.category }}</span></div>
        </div>
      </div>
      <div v-else class="empty-state"><div class="empty-icon"><i class="pi pi-map-marker" /></div><h3>Chưa có location</h3></div>
    </div>

    <!-- Reviews -->
    <div v-show="tab === 'reviews'">
      <div v-if="reviews?.length" class="reviews-list">
        <div v-for="r in reviews" :key="r.id" class="rv-card">
          <div class="rv-header">
            <div class="rv-user"><strong>{{ r.reviewer_name }}</strong><span class="rv-date">{{ r.review_time }}</span></div>
            <div class="rv-stars"><span v-for="s in 5" :key="s" class="star" :class="{ filled: s <= r.rating }">★</span></div>
          </div>
          <p v-if="r.comment" class="rv-comment">{{ r.comment }}</p>
          <div v-if="r.reply" class="rv-reply"><strong>Phản hồi:</strong> {{ r.reply }}<span class="rv-reply-date">{{ r.replied_at }}</span></div>
          <div v-else class="rv-reply-form">
            <input v-model="replyTexts[r.id]" type="text" class="fm-input" placeholder="Viết phản hồi..." />
            <button class="reply-btn" :disabled="!replyTexts[r.id]" @click="replyReview(r.id)"><i class="pi pi-send" /></button>
          </div>
        </div>
      </div>
      <div v-else class="empty-state"><div class="empty-icon"><i class="pi pi-star" /></div><h3>Chưa có review</h3></div>
    </div>

    <!-- Posts -->
    <div v-show="tab === 'posts'">
      <button class="btn-add-sm" @click="showAddPost = true"><i class="pi pi-plus" /> Tạo bài đăng</button>
      <div v-if="posts?.length" class="posts-list">
        <div v-for="p in posts" :key="p.id" class="post-card">
          <span class="pc-type" :class="'pt-' + p.post_type">{{ postTypes[p.post_type]?.label }}</span>
          <p class="pc-content">{{ p.content }}</p>
          <div class="pc-footer">
            <span class="pc-status" :class="'ps-' + p.status">{{ p.status }}</span>
            <span class="pc-date">{{ p.published_at || '—' }}</span>
          </div>
        </div>
      </div>
      <div v-else class="empty-state"><div class="empty-icon"><i class="pi pi-megaphone" /></div><h3>Chưa có bài đăng</h3></div>
    </div>

    <!-- Add Location Modal -->
    <div v-if="showAddLoc" class="modal-overlay" @click.self="showAddLoc = false">
      <div class="modal-box">
        <div class="modal-head"><h3>Thêm Location</h3><button class="modal-close" @click="showAddLoc = false"><i class="pi pi-times" /></button></div>
        <div class="fm-group"><label>Tên location <span class="req">*</span></label><input v-model="locForm.location_name" type="text" class="fm-input" /></div>
        <div class="fm-group"><label>Địa chỉ</label><input v-model="locForm.address" type="text" class="fm-input" /></div>
        <div class="fm-row">
          <div class="fm-group flex-1"><label>Điện thoại</label><input v-model="locForm.phone" type="text" class="fm-input" /></div>
          <div class="fm-group flex-1"><label>Website</label><input v-model="locForm.website" type="url" class="fm-input" /></div>
        </div>
        <div class="fm-group"><label>Danh mục</label><input v-model="locForm.category" type="text" class="fm-input" placeholder="VD: Công ty thiết kế web" /></div>
        <button class="btn-save" :disabled="!locForm.location_name" @click="saveLoc"><i class="pi pi-save" /> Thêm</button>
      </div>
    </div>

    <!-- Add Post Modal -->
    <div v-if="showAddPost" class="modal-overlay" @click.self="showAddPost = false">
      <div class="modal-box">
        <div class="modal-head"><h3>Tạo bài đăng GMB</h3><button class="modal-close" @click="showAddPost = false"><i class="pi pi-times" /></button></div>
        <div class="fm-group"><label>Loại bài</label>
          <div class="type-opts"><button v-for="(info, key) in postTypes" :key="key" class="type-opt" :class="{ active: postForm.post_type === key }" @click="postForm.post_type = key"><i :class="info.icon" /> {{ info.label }}</button></div>
        </div>
        <div class="fm-group"><label>Nội dung <span class="req">*</span></label><textarea v-model="postForm.content" rows="3" class="fm-input" /></div>
        <div class="fm-row">
          <div class="fm-group flex-1"><label>CTA</label><select v-model="postForm.cta_type" class="fm-input"><option value="">—</option><option value="learn_more">Tìm hiểu</option><option value="book">Đặt lịch</option><option value="order">Đặt hàng</option><option value="call">Gọi điện</option></select></div>
          <div class="fm-group flex-1"><label>CTA URL</label><input v-model="postForm.cta_url" type="url" class="fm-input" /></div>
        </div>
        <button class="btn-save" :disabled="!postForm.content" @click="savePost"><i class="pi pi-save" /> Tạo</button>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'

export default {
  components: { Head },
  layout: Layout,
  props: { locations: Array, reviews: [Array, Object], posts: [Array, Object], stats: Object, postTypes: Object, selectedLocationId: Number },
  data() {
    return {
      tab: 'overview', showAddLoc: false, showAddPost: false, replyTexts: {},
      locForm: { location_name: '', address: '', phone: '', website: '', category: '' },
      postForm: { post_type: 'update', content: '', cta_type: '', cta_url: '' },
    }
  },
  computed: {
    selectedLoc() { return this.locations.find(l => l.id === this.selectedLocationId) },
  },
  methods: {
    formatNum(n) { return n ? n.toLocaleString() : '0' },
    selectLocation(id) { router.get('/gmb-dashboard', { location_id: id }, { preserveState: true, replace: true }) },
    saveLoc() { router.post('/gmb-dashboard/locations', this.locForm, { onSuccess: () => { this.locForm = { location_name: '', address: '', phone: '', website: '', category: '' }; this.showAddLoc = false } }) },
    replyReview(id) { router.post(`/gmb-dashboard/reviews/${id}/reply`, { reply: this.replyTexts[id] }) },
    savePost() { router.post('/gmb-dashboard/posts', { ...this.postForm, gmb_location_id: this.selectedLocationId }, { onSuccess: () => { this.postForm = { post_type: 'update', content: '', cta_type: '', cta_url: '' }; this.showAddPost = false } }) },
  },
}
</script>

<style scoped>
.page-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:.75rem}.page-title{font-size:1.4rem;font-weight:800;color:#0f172a;margin:0;display:flex;align-items:center;gap:.5rem}.page-subtitle{font-size:.78rem;color:#94a3b8;margin:.1rem 0 0}.btn-add{display:inline-flex;align-items:center;gap:.35rem;padding:.55rem 1.1rem;border-radius:10px;background:linear-gradient(135deg,#ea4335,#c5221f);color:#fff;font-size:.78rem;font-weight:700;border:none;cursor:pointer;font-family:inherit}
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:.55rem;margin-bottom:1rem}.stat-card{display:flex;align-items:center;gap:.5rem;padding:.6rem .8rem;border-radius:12px;background:#fff;border:1.5px solid #f1f5f9}.stat-icon{width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:.78rem;flex-shrink:0}.si-loc{background:#fef2f2;color:#ea4335}.si-star{background:#fef3c7;color:#f59e0b}.si-reviews{background:#ecfdf5;color:#10b981}.si-views{background:#eef2ff;color:#6366f1}.stat-val{font-size:1.05rem;font-weight:800;color:#0f172a;display:block}.stat-gold{color:#f59e0b}.stat-lbl{font-size:.6rem;color:#94a3b8}
.loc-selector{display:flex;gap:.3rem;margin-bottom:.75rem;flex-wrap:wrap}.loc-chip{padding:.35rem .65rem;border-radius:8px;border:1.5px solid #e2e8f0;background:#fff;font-size:.68rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:.25rem;font-family:inherit}.loc-chip.active{border-color:#ea4335;background:#fef2f2;color:#ea4335}.loc-chip i{font-size:.55rem}.lc-rating{font-size:.55rem;color:#f59e0b}
.tabs-bar{display:flex;gap:0;border-bottom:1.5px solid #f1f5f9;margin-bottom:.75rem}.tab{padding:.5rem .9rem;border:none;background:0 0;font-size:.72rem;font-weight:700;color:#94a3b8;cursor:pointer;border-bottom:2px solid transparent;display:flex;align-items:center;gap:.25rem;font-family:inherit}.tab.active{color:#ea4335;border-bottom-color:#ea4335}.tab-count{padding:.05rem .3rem;border-radius:9px;background:#ea4335;color:#fff;font-size:.5rem}
.ld-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:.5rem;margin-bottom:.75rem}.ld-card{text-align:center;padding:.8rem .5rem;border-radius:12px;background:#fff;border:1.5px solid #f1f5f9}.ld-icon{margin-bottom:.3rem}.ld-icon i{font-size:.9rem;color:#ea4335}.ld-val{font-size:1.2rem;font-weight:900;color:#0f172a;display:block}.ld-gold{color:#f59e0b}.ld-lbl{font-size:.55rem;color:#94a3b8}
.ld-info{background:#fff;border-radius:12px;border:1.5px solid #f1f5f9;padding:.75rem}.li-row{display:flex;align-items:center;gap:.4rem;font-size:.72rem;color:#475569;padding:.25rem 0}.li-row i{font-size:.6rem;color:#ea4335;width:16px}
.reviews-list{display:flex;flex-direction:column;gap:.4rem}.rv-card{background:#fff;border-radius:12px;border:1.5px solid #f1f5f9;padding:.75rem}.rv-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:.3rem}.rv-user strong{font-size:.75rem;color:#0f172a}.rv-date{font-size:.55rem;color:#94a3b8;margin-left:.3rem}.rv-stars .star{font-size:.7rem;color:#e2e8f0}.rv-stars .star.filled{color:#f59e0b}.rv-comment{font-size:.75rem;color:#475569;margin:0 0 .4rem;line-height:1.5}.rv-reply{font-size:.65rem;color:#059669;background:#ecfdf5;padding:.4rem .5rem;border-radius:7px}.rv-reply-date{font-size:.5rem;color:#94a3b8;margin-left:.3rem}.rv-reply-form{display:flex;gap:.25rem}.rv-reply-form .fm-input{flex:1;font-size:.7rem;padding:.3rem .5rem}.reply-btn{width:30px;height:30px;border:none;border-radius:7px;background:#ea4335;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.6rem;flex-shrink:0}
.btn-add-sm{display:inline-flex;align-items:center;gap:.2rem;padding:.3rem .6rem;border-radius:7px;background:#f1f5f9;border:none;color:#64748b;font-size:.65rem;font-weight:600;cursor:pointer;margin-bottom:.5rem;font-family:inherit}.posts-list{display:flex;flex-direction:column;gap:.35rem}.post-card{background:#fff;border-radius:10px;border:1.5px solid #f1f5f9;padding:.6rem}.pc-type{padding:.1rem .35rem;border-radius:4px;font-size:.55rem;font-weight:700;text-transform:uppercase}.pt-update{background:#eef2ff;color:#6366f1}.pt-event{background:#fef3c7;color:#f59e0b}.pt-offer{background:#ecfdf5;color:#10b981}.pc-content{font-size:.72rem;color:#475569;margin:.3rem 0}.pc-footer{display:flex;justify-content:space-between;font-size:.55rem}.pc-status{font-weight:700;text-transform:capitalize}.ps-draft{color:#f59e0b}.ps-published{color:#10b981}.pc-date{color:#94a3b8}
.modal-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.4);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;z-index:9999}.modal-box{background:#fff;border-radius:16px;padding:1.2rem;width:95%;max-width:460px;box-shadow:0 20px 60px rgba(0,0,0,.15)}.modal-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem}.modal-head h3{font-size:.95rem;font-weight:800;margin:0}.modal-close{width:28px;height:28px;border:none;background:#f1f5f9;border-radius:7px;cursor:pointer;color:#94a3b8;display:flex;align-items:center;justify-content:center}
.fm-group{margin-bottom:.55rem}.fm-group label{display:block;font-size:.68rem;font-weight:600;color:#475569;margin-bottom:.15rem}.req{color:#ef4444}.fm-input{width:100%;padding:.42rem .65rem;border:1.5px solid #e2e8f0;border-radius:9px;font-size:.78rem;color:#1e293b;outline:none;font-family:inherit;box-sizing:border-box}.fm-row{display:flex;gap:.5rem}.flex-1{flex:1}
.type-opts{display:flex;gap:.3rem}.type-opt{padding:.3rem .6rem;border-radius:7px;border:1.5px solid #e2e8f0;background:#fff;font-size:.65rem;font-weight:600;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:.15rem}.type-opt.active{border-color:#ea4335;background:#fef2f2;color:#ea4335}
.btn-save{width:100%;padding:.5rem;border-radius:9px;background:linear-gradient(135deg,#ea4335,#c5221f);color:#fff;font-size:.78rem;font-weight:700;border:none;cursor:pointer;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:.3rem;margin-top:.5rem}.btn-save:disabled{opacity:.5}
.empty-state{text-align:center;padding:2.5rem 1rem}.empty-icon{width:48px;height:48px;border-radius:14px;background:#fef2f2;display:flex;align-items:center;justify-content:center;margin:0 auto .6rem}.empty-icon i{font-size:1.1rem;color:#ea4335}.empty-state h3{font-size:.95rem;color:#1e293b;margin:0}
@media(max-width:768px){.stats-row,.ld-grid{grid-template-columns:repeat(2,1fr)}}
</style>
