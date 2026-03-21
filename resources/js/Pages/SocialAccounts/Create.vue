<template>
  <div>
    <Head title="Kết nối tài khoản" />

    <!-- Breadcrumb -->
    <div class="breadcrumb-bar">
      <Link href="/social-accounts" class="breadcrumb-link">
        <i class="pi pi-arrow-left" /> Tài khoản
      </Link>
      <span class="breadcrumb-sep">/</span>
      <span class="breadcrumb-current">Kết nối mới</span>
    </div>

    <!-- Page Header -->
    <div class="page-header-center">
      <div class="header-icon"><i class="pi pi-link" /></div>
      <h1>Kết nối mạng xã hội</h1>
      <p>Chọn nền tảng bạn muốn kết nối để bắt đầu tự động đăng bài</p>
    </div>

    <!-- Platform Cards -->
    <div class="platform-cards">
      <div
        v-for="(config, key) in platformConfig"
        :key="key"
        class="platform-card"
        @click="connectPlatform(key)"
      >
        <div class="platform-card-top">
          <div class="pcard-icon" :style="{ background: config.gradient }">
            <i :class="config.icon" />
          </div>
          <div class="pcard-arrow"><i class="pi pi-arrow-right" /></div>
        </div>
        <h3 class="pcard-name">{{ config.label }}</h3>
        <p class="pcard-desc">{{ config.description }}</p>
        <div class="pcard-features">
          <span v-for="(feat, i) in config.features" :key="i" class="feature-chip">
            <i class="pi pi-check" /> {{ feat }}
          </span>
        </div>
        <div class="pcard-footer">
          <span class="connect-label">Kết nối ngay →</span>
        </div>
      </div>
    </div>

    <!-- How it Works -->
    <div class="how-card">
      <h2><i class="pi pi-question-circle" /> Cách kết nối hoạt động</h2>
      <div class="how-steps">
        <div class="how-step">
          <div class="how-num">1</div>
          <div class="how-body">
            <h4>Chọn nền tảng</h4>
            <p>Bấm vào nền tảng bạn muốn kết nối ở phía trên</p>
          </div>
        </div>
        <div class="how-connector"><i class="pi pi-arrow-right" /></div>
        <div class="how-step">
          <div class="how-num">2</div>
          <div class="how-body">
            <h4>Đăng nhập & cấp quyền</h4>
            <p>Đăng nhập tài khoản social và cấp quyền cho ứng dụng</p>
          </div>
        </div>
        <div class="how-connector"><i class="pi pi-arrow-right" /></div>
        <div class="how-step">
          <div class="how-num">3</div>
          <div class="how-body">
            <h4>Sẵn sàng!</h4>
            <p>Tài khoản đã kết nối, bạn có thể bắt đầu tạo & đăng bài</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Security Note -->
    <div class="security-note">
      <i class="pi pi-shield" />
      <div>
        <h4>Bảo mật & an toàn</h4>
        <p>Tokens được mã hóa trước khi lưu. Bạn có thể ngắt kết nối bất kỳ lúc nào. Chúng tôi chỉ yêu cầu quyền cần thiết để đăng bài.</p>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'

export default {
  components: { Head, Link },
  layout: Layout,
  props: { platforms: Object },
  computed: {
    platformConfig() {
      return {
        facebook: {
          label: 'Facebook',
          icon: 'pi pi-facebook',
          gradient: 'linear-gradient(135deg, #1877F2, #0d65d9)',
          description: 'Đăng bài lên trang Facebook và nhóm',
          features: ['Đăng bài', 'Quản lý Page', 'Xem thống kê'],
        },
        instagram: {
          label: 'Instagram',
          icon: 'pi pi-instagram',
          gradient: 'linear-gradient(135deg, #E4405F, #fd1d1d, #F77737)',
          description: 'Chia sẻ nội dung lên feed Instagram',
          features: ['Đăng ảnh', 'Story', 'Reels'],
        },
        linkedin: {
          label: 'LinkedIn',
          icon: 'pi pi-linkedin',
          gradient: 'linear-gradient(135deg, #0A66C2, #004182)',
          description: 'Chia sẻ nội dung chuyên nghiệp',
          features: ['Bài viết', 'Trang công ty', 'Networking'],
        },
        twitter: {
          label: 'Twitter / X',
          icon: 'pi pi-twitter',
          gradient: 'linear-gradient(135deg, #1DA1F2, #0d8ecf)',
          description: 'Đăng tweet và tương tác',
          features: ['Tweet', 'Thread', 'Hashtags'],
        },
      }
    },
  },
  methods: {
    async connectPlatform(platform) {
      try {
        const res = await fetch(`/api/social-platforms/${platform}/auth-url`, {
          headers: { 'Accept': 'application/json' },
        })
        const data = await res.json()
        if (data.success && data.auth_url) {
          window.location.href = data.auth_url
        } else {
          if (confirm(`${this.platformConfig[platform]?.label} chưa được cấu hình OAuth.\n\nĐi đến trang cấu hình?`)) {
            router.visit('/social-platforms')
          }
        }
      } catch (e) {
        if (confirm('Nền tảng chưa được cấu hình. Đi đến trang cấu hình Social Platforms?')) {
          router.visit('/social-platforms')
        }
      }
    },
  },
}
</script>

<style scoped>
/* ===== Breadcrumb ===== */
.breadcrumb-bar { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; font-size: 0.78rem; }
.breadcrumb-link { color: #6366f1; text-decoration: none; display: flex; align-items: center; gap: 0.3rem; }
.breadcrumb-link:hover { opacity: 0.7; }
.breadcrumb-link i { font-size: 0.68rem; }
.breadcrumb-sep { color: #cbd5e1; }
.breadcrumb-current { color: #64748b; font-weight: 500; }

/* ===== Header Center ===== */
.page-header-center { text-align: center; margin-bottom: 1.5rem; }
.header-icon {
  width: 64px; height: 64px; border-radius: 20px; margin: 0 auto 0.75rem;
  background: linear-gradient(135deg, #eef2ff, #e0e7ff);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.5rem; color: #6366f1;
}
.page-header-center h1 { font-size: 1.35rem; font-weight: 700; color: #0f172a; margin: 0 0 0.3rem; }
.page-header-center p { font-size: 0.85rem; color: #94a3b8; margin: 0; }

/* ===== Platform Cards ===== */
.platform-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 0.85rem; margin-bottom: 1.5rem; }

.platform-card {
  background: white; border-radius: 16px; border: 1.5px solid #f1f5f9;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04); padding: 1.25rem;
  cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.platform-card:hover {
  border-color: #6366f1; box-shadow: 0 12px 32px rgba(99,102,241,0.12);
  transform: translateY(-4px);
}

.platform-card-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.85rem; }
.pcard-icon {
  width: 48px; height: 48px; border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.25rem; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.pcard-arrow { color: #cbd5e1; transition: all 0.25s; }
.platform-card:hover .pcard-arrow { color: #6366f1; transform: translateX(4px); }

.pcard-name { font-size: 1.05rem; font-weight: 700; color: #0f172a; margin: 0 0 0.25rem; }
.pcard-desc { font-size: 0.78rem; color: #64748b; margin: 0 0 0.75rem; line-height: 1.4; }

.pcard-features { display: flex; flex-wrap: wrap; gap: 0.3rem; margin-bottom: 0.85rem; }
.feature-chip {
  display: flex; align-items: center; gap: 0.2rem;
  font-size: 0.62rem; color: #10b981; background: #ecfdf5;
  padding: 0.15rem 0.4rem; border-radius: 5px;
}
.feature-chip i { font-size: 0.5rem; }

.pcard-footer { border-top: 1px solid #f8fafc; padding-top: 0.65rem; }
.connect-label { font-size: 0.72rem; font-weight: 600; color: #6366f1; }

/* ===== How it Works ===== */
.how-card {
  background: white; border-radius: 16px; border: 1px solid #f1f5f9;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04); padding: 1.5rem;
  margin-bottom: 1rem;
}
.how-card h2 {
  font-size: 0.95rem; font-weight: 600; color: #1e293b; margin: 0 0 1.25rem;
  display: flex; align-items: center; gap: 0.4rem;
}
.how-card h2 i { color: #6366f1; font-size: 0.88rem; }

.how-steps { display: flex; align-items: flex-start; justify-content: center; gap: 0; }
.how-step { display: flex; gap: 0.65rem; flex: 1; }
.how-num {
  width: 36px; height: 36px; border-radius: 12px; flex-shrink: 0;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white; font-size: 0.82rem; font-weight: 700;
  display: flex; align-items: center; justify-content: center;
}
.how-body h4 { font-size: 0.82rem; font-weight: 600; color: #1e293b; margin: 0 0 0.15rem; }
.how-body p { font-size: 0.72rem; color: #64748b; margin: 0; line-height: 1.4; }
.how-connector {
  display: flex; align-items: center; padding: 0 0.75rem; color: #cbd5e1;
  margin-top: 0.4rem;
}

/* ===== Security ===== */
.security-note {
  display: flex; gap: 0.75rem; padding: 1rem 1.25rem;
  background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px;
}
.security-note > i { color: #10b981; font-size: 1.1rem; margin-top: 0.15rem; }
.security-note h4 { font-size: 0.78rem; font-weight: 600; color: #166534; margin: 0 0 0.15rem; }
.security-note p { font-size: 0.72rem; color: #15803d; margin: 0; line-height: 1.45; }

/* ===== Responsive ===== */
@media (max-width: 768px) {
  .platform-cards { grid-template-columns: 1fr; }
  .how-steps { flex-direction: column; gap: 0.75rem; }
  .how-connector { transform: rotate(90deg); padding: 0; align-self: center; }
}
</style>
