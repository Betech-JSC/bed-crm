<template>
  <div>
    <Head title="SMTP Settings" />
    <div class="mb-6">
      <h1 class="text-3xl font-bold">{{ t('common.smtp_settings') }}</h1>
      <p class="mt-1 text-gray-600">Configure your email server settings for sending emails</p>
    </div>

    <Card>
      <template #title>SMTP Configuration</template>
      <template #content>
        <form @submit.prevent="store" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">SMTP Host <span class="text-red-500">*</span></label>
              <InputText v-model="form.host" placeholder="smtp.gmail.com" :class="{ 'p-invalid': form.errors.host }" />
              <small v-if="form.errors.host" class="p-error">{{ form.errors.host }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Port <span class="text-red-500">*</span></label>
              <InputNumber
                v-model="form.port"
                :min="1"
                :max="65535"
                placeholder="587"
                :class="{ 'p-invalid': form.errors.port }"
              />
              <small v-if="form.errors.port" class="p-error">{{ form.errors.port }}</small>
              <small class="text-gray-500 mt-1">Common ports: 587 (TLS), 465 (SSL), 25</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Username <span class="text-red-500">*</span></label>
              <InputText v-model="form.username" placeholder="your-email@gmail.com" :class="{ 'p-invalid': form.errors.username }" />
              <small v-if="form.errors.username" class="p-error">{{ form.errors.username }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Password <span class="text-red-500">*</span></label>
              <Password
                v-model="form.password"
                :feedback="false"
                toggleMask
                placeholder="Enter password"
                :class="{ 'p-invalid': form.errors.password }"
              />
              <small v-if="form.errors.password" class="p-error">{{ form.errors.password }}</small>
              <small v-if="smtpSetting && smtpSetting.password" class="text-gray-500 mt-1">Leave blank to keep current password</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Encryption</label>
              <Select
                v-model="form.encryption"
                :options="encryptionOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select encryption"
                :class="{ 'p-invalid': form.errors.encryption }"
              />
              <small v-if="form.errors.encryption" class="p-error">{{ form.errors.encryption }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">From Email Address <span class="text-red-500">*</span></label>
              <InputText v-model="form.from_address" type="email" placeholder="noreply@example.com" :class="{ 'p-invalid': form.errors.from_address }" />
              <small v-if="form.errors.from_address" class="p-error">{{ form.errors.from_address }}</small>
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">From Name</label>
              <InputText v-model="form.from_name" placeholder="Your Company Name" :class="{ 'p-invalid': form.errors.from_name }" />
              <small v-if="form.errors.from_name" class="p-error">{{ form.errors.from_name }}</small>
            </div>

            <div class="flex items-center md:col-span-2">
              <Checkbox v-model="form.is_active" inputId="is_active" :binary="true" />
              <label for="is_active" class="ml-2">Activate SMTP settings</label>
            </div>
          </div>

          <div class="flex items-center justify-between pt-4 border-t">
            <div>
              <InputText
                v-model="testEmail"
                placeholder="test@example.com"
                class="w-64"
              />
              <Button
                label="Send Test Email"
                icon="pi pi-send"
                severity="secondary"
                outlined
                class="ml-2"
                @click="sendTestEmail"
                :disabled="!testEmail || !form.is_active"
              />
            </div>
            <div class="flex items-center gap-2">
              <Button label="Save Settings" icon="pi pi-check" :loading="form.processing" type="submit" />
            </div>
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script>
import { Head, Link, useForm } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Password from 'primevue/password'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import Button from 'primevue/button'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: {
    Head,
    Link,
    Card,
    InputText,
    InputNumber,
    Password,
    Select,
    Checkbox,
    Button,
  },
  layout: Layout,
  props: {
    smtpSetting: Object,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      form: useForm({
        host: this.smtpSetting?.host || '',
        port: this.smtpSetting?.port || 587,
        username: this.smtpSetting?.username || '',
        password: '',
        encryption: this.smtpSetting?.encryption || 'tls',
        from_address: this.smtpSetting?.from_address || '',
        from_name: this.smtpSetting?.from_name || '',
        is_active: this.smtpSetting?.is_active ?? true,
      }),
      testEmail: '',
      encryptionOptions: [
        { label: 'TLS', value: 'tls' },
        { label: 'SSL', value: 'ssl' },
        { label: 'None', value: null },
      ],
    }
  },
  methods: {
    store() {
      // If password is masked, don't send it
      if (this.smtpSetting && this.form.password === '***') {
        this.form.password = ''
      }
      
      this.form.post('/smtp-settings', {
        forceFormData: true,
      })
    },
    sendTestEmail() {
      if (!this.testEmail) {
        return
      }

      this.$inertia.post('/smtp-settings/test', {
        test_email: this.testEmail,
      }, {
        preserveState: true,
        preserveScroll: true,
      })
    },
  },
}
</script>

