<template>
  <div>
    <Head title="Account Settings" />
    <div class="mb-6">
      <h1 class="text-3xl font-bold">Account Settings</h1>
      <p class="mt-1 text-gray-600">Manage your account information and branding</p>
    </div>

    <Card>
      <template #title>Account Information</template>
      <template #content>
        <form @submit.prevent="update" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Company Name <span class="text-red-500">*</span></label>
              <InputText v-model="form.name" :class="{ 'p-invalid': form.errors.name }" />
              <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Logo</label>
              <div class="flex items-start gap-4">
                <div v-if="account.logo || logoPreview" class="flex-shrink-0">
                  <img
                    :src="logoPreview || account.logo"
                    alt="Logo"
                    class="w-32 h-32 object-contain border border-gray-200 rounded-lg p-2 bg-white"
                  />
                </div>
                <div class="flex-1">
                  <div class="relative">
                    <input
                      type="file"
                      ref="logoInput"
                      @change="onLogoChange"
                      accept="image/*"
                      class="hidden"
                    />
                    <Button
                      type="button"
                      :label="account.logo || logoPreview ? 'Change Logo' : 'Upload Logo'"
                      icon="pi pi-upload"
                      @click="$refs.logoInput.click()"
                    />
                    <Button
                      v-if="account.logo || logoPreview"
                      type="button"
                      label="Remove"
                      icon="pi pi-times"
                      severity="danger"
                      outlined
                      class="ml-2"
                      @click="removeLogo"
                    />
                  </div>
                  <small class="text-gray-500 mt-1 block">Recommended size: 200x200px. Max file size: 2MB</small>
                  <small v-if="form.errors.logo" class="p-error block">{{ form.errors.logo }}</small>
                </div>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 pt-4 border-t">
            <Button label="Save Changes" icon="pi pi-check" :loading="form.processing" type="submit" />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script>
import { Head, useForm } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'

export default {
  components: {
    Head,
    Card,
    InputText,
    Button,
  },
  layout: Layout,
  props: {
    account: Object,
  },
  data() {
    return {
      form: useForm({
        name: this.account?.name || '',
        logo: null,
      }),
      logoPreview: null,
    }
  },
  watch: {
    account: {
      immediate: true,
      handler(newAccount) {
        if (newAccount && newAccount.name) {
          this.form.name = newAccount.name
        }
      },
    },
  },
  methods: {
    onLogoChange(event) {
      const file = event.target.files[0]
      if (file) {
        // Validate file size (2MB)
        if (file.size > 2048 * 1024) {
          alert('File size exceeds 2MB limit')
          event.target.value = ''
          return
        }

        // Validate file type
        if (!file.type.startsWith('image/')) {
          alert('Please select an image file')
          event.target.value = ''
          return
        }

        this.form.logo = file

        // Create preview
        const reader = new FileReader()
        reader.onload = (e) => {
          this.logoPreview = e.target.result
        }
        reader.readAsDataURL(file)
      }
    },
    removeLogo() {
      this.form.logo = null
      this.logoPreview = null
      if (this.$refs.logoInput) {
        this.$refs.logoInput.value = ''
      }
    },
    update() {
      // Ensure name is included
      if (!this.form.name || this.form.name.trim() === '') {
        this.form.setError('name', 'Company name is required')
        return
      }

      // Submit form - Inertia will handle FormData automatically when logo is present
      this.form.put('/account-settings', {
        forceFormData: true,
        preserveScroll: true,
        onError: (errors) => {
          console.log('Form errors:', errors)
        },
      })
    },
  },
}
</script>

