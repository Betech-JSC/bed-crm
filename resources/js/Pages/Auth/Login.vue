<template>
  <Head title="Login" />
  <div class="login-page">
    <!-- Animated background -->
    <div class="login-bg">
      <img src="/images/login-bg.png" alt="" class="login-bg-image" />
      <div class="login-bg-overlay" />
    </div>

    <!-- Floating particles effect -->
    <div class="particles">
      <div v-for="n in 20" :key="n" class="particle" :style="particleStyle(n)" />
    </div>

    <div class="login-container">
      <!-- Left side - Branding -->
      <div class="login-branding">
        <div class="branding-content">
          <div class="brand-logo-wrapper">
            <div class="brand-logo-glow" />
            <logo class="brand-logo" height="40" />
          </div>
          <h2 class="brand-tagline">Quản lý doanh nghiệp<br />thông minh & hiệu quả</h2>
          <p class="brand-description">
            Nền tảng CRM toàn diện giúp bạn quản lý khách hàng, đơn hàng và vận hành doanh nghiệp một cách chuyên nghiệp.
          </p>

          <div class="brand-features">
            <div class="feature-item" v-for="(feature, index) in features" :key="index">
              <div class="feature-icon">
                <i :class="feature.icon" />
              </div>
              <div class="feature-text">
                <span class="feature-title">{{ feature.title }}</span>
                <span class="feature-desc">{{ feature.desc }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right side - Login Form -->
      <div class="login-form-section">
        <form class="login-form" @submit.prevent="login">
          <div class="form-header">
            <div class="welcome-icon">
              <i class="pi pi-lock" />
            </div>
            <h1 class="form-title">Chào mừng trở lại!</h1>
            <p class="form-subtitle">Đăng nhập để tiếp tục công việc của bạn</p>
          </div>

          <div class="form-body">
            <!-- Email field -->
            <div class="input-group" :class="{ 'has-error': form.errors.email, 'is-focused': emailFocused }">
              <label for="login-email" class="input-label">
                <i class="pi pi-envelope" />
                Email
              </label>
              <div class="input-wrapper">
                <input
                  id="login-email"
                  v-model="form.email"
                  type="email"
                  class="login-input"
                  placeholder="Nhập email của bạn"
                  autocomplete="email"
                  autofocus
                  autocapitalize="off"
                  @focus="emailFocused = true"
                  @blur="emailFocused = false"
                />
              </div>
              <Transition name="slide-fade">
                <span v-if="form.errors.email" class="input-error">
                  <i class="pi pi-exclamation-circle" />
                  {{ form.errors.email }}
                </span>
              </Transition>
            </div>

            <!-- Password field -->
            <div class="input-group" :class="{ 'has-error': form.errors.password, 'is-focused': passwordFocused }">
              <label for="login-password" class="input-label">
                <i class="pi pi-key" />
                Mật khẩu
              </label>
              <div class="input-wrapper">
                <input
                  id="login-password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  class="login-input"
                  placeholder="Nhập mật khẩu"
                  autocomplete="current-password"
                  @focus="passwordFocused = true"
                  @blur="passwordFocused = false"
                />
                <button type="button" class="password-toggle" @click="showPassword = !showPassword" tabindex="-1">
                  <i :class="showPassword ? 'pi pi-eye-slash' : 'pi pi-eye'" />
                </button>
              </div>
              <Transition name="slide-fade">
                <span v-if="form.errors.password" class="input-error">
                  <i class="pi pi-exclamation-circle" />
                  {{ form.errors.password }}
                </span>
              </Transition>
            </div>

            <!-- Remember me -->
            <div class="form-options">
              <label class="remember-checkbox" for="remember">
                <input id="remember" v-model="form.remember" type="checkbox" />
                <span class="checkbox-custom">
                  <i class="pi pi-check" />
                </span>
                <span class="checkbox-label">Ghi nhớ đăng nhập</span>
              </label>
            </div>

            <!-- Submit button -->
            <button
              type="submit"
              class="login-button"
              :class="{ 'is-loading': form.processing }"
              :disabled="form.processing"
            >
              <span v-if="form.processing" class="button-spinner" />
              <span class="button-text">
                <i v-if="!form.processing" class="pi pi-sign-in" />
                {{ form.processing ? 'Đang đăng nhập...' : 'Đăng nhập' }}
              </span>
            </button>
          </div>

          <div class="form-footer">
            <p class="footer-text">
              <i class="pi pi-shield" />
              Kết nối được bảo mật bằng SSL
            </p>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import Logo from '@/Shared/Logo.vue'

export default {
  components: {
    Head,
    Logo,
  },
  data() {
    return {
      form: this.$inertia.form({
        email: '',
        password: '',
        remember: false,
      }),
      showPassword: false,
      emailFocused: false,
      passwordFocused: false,
      features: [
        {
          icon: 'pi pi-users',
          title: 'Quản lý khách hàng',
          desc: 'Theo dõi và chăm sóc khách hàng hiệu quả',
        },
        {
          icon: 'pi pi-chart-line',
          title: 'Phân tích dữ liệu',
          desc: 'Báo cáo trực quan, hỗ trợ ra quyết định',
        },
        {
          icon: 'pi pi-bolt',
          title: 'Tự động hóa',
          desc: 'Tối ưu quy trình, tiết kiệm thời gian',
        },
      ],
    }
  },
  methods: {
    login() {
      this.form.post('/login')
    },
    particleStyle(n) {
      const size = Math.random() * 4 + 2
      const left = Math.random() * 100
      const animDelay = Math.random() * 20
      const animDuration = Math.random() * 15 + 10
      const opacity = Math.random() * 0.3 + 0.1
      return {
        width: `${size}px`,
        height: `${size}px`,
        left: `${left}%`,
        animationDelay: `${animDelay}s`,
        animationDuration: `${animDuration}s`,
        opacity: opacity,
      }
    },
  },
}
</script>

<style scoped>
/* ===== Page Layout ===== */
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
  background: #0a0e1a;
  padding: 1rem;
}

/* ===== Background ===== */
.login-bg {
  position: absolute;
  inset: 0;
  z-index: 0;
}

.login-bg-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  opacity: 0.4;
  filter: blur(1px);
}

.login-bg-overlay {
  position: absolute;
  inset: 0;
  background: radial-gradient(ellipse at 30% 50%, rgba(239, 104, 32, 0.08) 0%, transparent 60%),
              radial-gradient(ellipse at 70% 50%, rgba(99, 102, 241, 0.06) 0%, transparent 60%),
              linear-gradient(180deg, rgba(10, 14, 26, 0.3) 0%, rgba(10, 14, 26, 0.7) 100%);
}

/* ===== Particles ===== */
.particles {
  position: absolute;
  inset: 0;
  z-index: 1;
  pointer-events: none;
}

.particle {
  position: absolute;
  bottom: -10px;
  background: rgba(239, 104, 32, 0.6);
  border-radius: 50%;
  animation: float-up linear infinite;
}

@keyframes float-up {
  0% {
    transform: translateY(0) scale(1);
    opacity: 0;
  }
  10% {
    opacity: 1;
  }
  90% {
    opacity: 1;
  }
  100% {
    transform: translateY(-100vh) scale(0.5);
    opacity: 0;
  }
}

/* ===== Main Container ===== */
.login-container {
  position: relative;
  z-index: 2;
  display: flex;
  width: 100%;
  max-width: 1000px;
  min-height: 580px;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5),
              0 0 80px rgba(239, 104, 32, 0.08),
              inset 0 1px 0 rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  animation: container-enter 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes container-enter {
  from {
    opacity: 0;
    transform: translateY(30px) scale(0.96);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* ===== Left Branding ===== */
.login-branding {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 3rem;
  background: linear-gradient(135deg, rgba(239, 104, 32, 0.15) 0%, rgba(15, 20, 40, 0.95) 50%, rgba(99, 102, 241, 0.1) 100%);
  position: relative;
  overflow: hidden;
}

.login-branding::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle at 30% 40%, rgba(239, 104, 32, 0.12) 0%, transparent 50%);
  animation: pulse-glow 8s ease-in-out infinite alternate;
}

@keyframes pulse-glow {
  0% { opacity: 0.5; transform: scale(1); }
  100% { opacity: 1; transform: scale(1.1); }
}

.branding-content {
  position: relative;
  z-index: 1;
}

.brand-logo-wrapper {
  position: relative;
  display: inline-flex;
  margin-bottom: 2rem;
}

.brand-logo-glow {
  position: absolute;
  inset: -10px;
  background: radial-gradient(circle, rgba(239, 104, 32, 0.3) 0%, transparent 70%);
  filter: blur(15px);
  border-radius: 50%;
}

.brand-logo {
  width: 180px;
  fill: white;
  filter: drop-shadow(0 2px 8px rgba(239, 104, 32, 0.3));
  position: relative;
  z-index: 1;
}

.brand-tagline {
  font-size: 1.6rem;
  font-weight: 700;
  color: white;
  line-height: 1.3;
  margin-bottom: 1rem;
  background: linear-gradient(135deg, #ffffff 0%, #f8b089 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.brand-description {
  font-size: 0.9rem;
  color: rgba(255, 255, 255, 0.6);
  line-height: 1.6;
  margin-bottom: 2rem;
}

/* ===== Features ===== */
.brand-features {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.feature-item {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.75rem;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.06);
  transition: all 0.3s ease;
  animation: feature-enter 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.feature-item:nth-child(1) { animation-delay: 0.3s; }
.feature-item:nth-child(2) { animation-delay: 0.45s; }
.feature-item:nth-child(3) { animation-delay: 0.6s; }

@keyframes feature-enter {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.feature-item:hover {
  background: rgba(239, 104, 32, 0.08);
  border-color: rgba(239, 104, 32, 0.2);
  transform: translateX(4px);
}

.feature-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(239, 104, 32, 0.2), rgba(239, 104, 32, 0.1));
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: #f48554;
  font-size: 0.9rem;
}

.feature-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.feature-title {
  font-size: 0.85rem;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.9);
}

.feature-desc {
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.45);
}

/* ===== Right Form Section ===== */
.login-form-section {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  background: linear-gradient(180deg, rgba(15, 20, 40, 0.98) 0%, rgba(10, 14, 26, 0.99) 100%);
}

.login-form {
  width: 100%;
  max-width: 380px;
}

/* ===== Form Header ===== */
.form-header {
  text-align: center;
  margin-bottom: 2rem;
  animation: fade-in-down 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.2s both;
}

@keyframes fade-in-down {
  from {
    opacity: 0;
    transform: translateY(-15px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.welcome-icon {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  background: linear-gradient(135deg, #ef6820, #f48554);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.25rem;
  box-shadow: 0 8px 24px rgba(239, 104, 32, 0.3);
  animation: icon-pulse 3s ease-in-out infinite;
}

@keyframes icon-pulse {
  0%, 100% { box-shadow: 0 8px 24px rgba(239, 104, 32, 0.3); }
  50% { box-shadow: 0 8px 32px rgba(239, 104, 32, 0.5); }
}

.welcome-icon i {
  font-size: 1.3rem;
  color: white;
}

.form-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: white;
  margin-bottom: 0.5rem;
}

.form-subtitle {
  font-size: 0.85rem;
  color: rgba(255, 255, 255, 0.45);
}

/* ===== Form Body ===== */
.form-body {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  animation: fade-in-up 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.4s both;
}

@keyframes fade-in-up {
  from {
    opacity: 0;
    transform: translateY(15px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* ===== Input Groups ===== */
.input-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.input-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8rem;
  font-weight: 500;
  color: rgba(255, 255, 255, 0.5);
  transition: color 0.3s ease;
}

.input-label i {
  font-size: 0.75rem;
}

.input-group.is-focused .input-label {
  color: #f48554;
}

.input-wrapper {
  position: relative;
}

.login-input {
  width: 100%;
  padding: 0.85rem 1rem;
  background: rgba(255, 255, 255, 0.04);
  border: 1.5px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  color: white;
  font-size: 0.9rem;
  outline: none;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.login-input::placeholder {
  color: rgba(255, 255, 255, 0.2);
}

.login-input:focus {
  border-color: rgba(239, 104, 32, 0.5);
  background: rgba(239, 104, 32, 0.04);
  box-shadow: 0 0 0 3px rgba(239, 104, 32, 0.1),
              0 4px 12px rgba(0, 0, 0, 0.2);
}

.input-group.has-error .login-input {
  border-color: rgba(239, 68, 68, 0.5);
  background: rgba(239, 68, 68, 0.04);
}

.input-group.has-error .login-input:focus {
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

/* ===== Password Toggle ===== */
.password-toggle {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: rgba(255, 255, 255, 0.3);
  cursor: pointer;
  padding: 4px;
  transition: color 0.2s ease;
  font-size: 0.9rem;
}

.password-toggle:hover {
  color: rgba(255, 255, 255, 0.6);
}

/* ===== Error Message ===== */
.input-error {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.75rem;
  color: #f87171;
}

.input-error i {
  font-size: 0.7rem;
}

.slide-fade-enter-active {
  transition: all 0.3s ease;
}
.slide-fade-leave-active {
  transition: all 0.2s ease;
}
.slide-fade-enter-from,
.slide-fade-leave-to {
  opacity: 0;
  transform: translateY(-5px);
}

/* ===== Remember Checkbox ===== */
.form-options {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.remember-checkbox {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  cursor: pointer;
  user-select: none;
}

.remember-checkbox input[type="checkbox"] {
  display: none;
}

.checkbox-custom {
  width: 20px;
  height: 20px;
  border-radius: 6px;
  border: 1.5px solid rgba(255, 255, 255, 0.15);
  background: rgba(255, 255, 255, 0.04);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  flex-shrink: 0;
}

.checkbox-custom i {
  font-size: 0.6rem;
  color: white;
  opacity: 0;
  transform: scale(0.5);
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.remember-checkbox input:checked + .checkbox-custom {
  background: linear-gradient(135deg, #ef6820, #e04f0f);
  border-color: transparent;
  box-shadow: 0 2px 8px rgba(239, 104, 32, 0.3);
}

.remember-checkbox input:checked + .checkbox-custom i {
  opacity: 1;
  transform: scale(1);
}

.checkbox-label {
  font-size: 0.82rem;
  color: rgba(255, 255, 255, 0.5);
  transition: color 0.2s ease;
}

.remember-checkbox:hover .checkbox-label {
  color: rgba(255, 255, 255, 0.7);
}

/* ===== Login Button ===== */
.login-button {
  width: 100%;
  padding: 0.9rem;
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, #ef6820 0%, #e04f0f 100%);
  color: white;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  margin-top: 0.5rem;
}

.login-button::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.15) 0%, transparent 50%);
  opacity: 0;
  transition: opacity 0.3s ease;
}

.login-button:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(239, 104, 32, 0.4),
              0 4px 12px rgba(239, 104, 32, 0.2);
}

.login-button:hover::before {
  opacity: 1;
}

.login-button:active:not(:disabled) {
  transform: translateY(0);
}

.login-button:disabled {
  cursor: not-allowed;
  opacity: 0.7;
}

.button-text {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  position: relative;
  z-index: 1;
}

.button-text i {
  font-size: 0.9rem;
}

.button-spinner {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
}

@keyframes spin {
  to { transform: translateY(-50%) rotate(360deg); }
}

/* ===== Form Footer ===== */
.form-footer {
  margin-top: 2rem;
  text-align: center;
  animation: fade-in-up 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.6s both;
}

.footer-text {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  font-size: 0.72rem;
  color: rgba(255, 255, 255, 0.25);
}

.footer-text i {
  font-size: 0.7rem;
  color: rgba(74, 222, 128, 0.6);
}

/* ===== Responsive ===== */
@media (max-width: 768px) {
  .login-container {
    flex-direction: column;
    max-width: 420px;
    min-height: auto;
  }

  .login-branding {
    padding: 2rem;
  }

  .brand-tagline {
    font-size: 1.2rem;
  }

  .brand-features {
    display: none;
  }

  .login-form-section {
    padding: 1.5rem;
  }
}

@media (max-width: 480px) {
  .login-page {
    padding: 0;
    align-items: stretch;
  }

  .login-container {
    border-radius: 0;
    min-height: 100vh;
    max-width: 100%;
    box-shadow: none;
    border: none;
  }

  .login-branding {
    padding: 1.5rem;
  }

  .brand-logo {
    width: 140px;
  }

  .brand-tagline {
    font-size: 1.1rem;
  }

  .brand-description {
    display: none;
  }
}
</style>
