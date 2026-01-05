<template>
  <div>
    <Head :title="`Edit: ${slaSetting.name}`" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <Card>
      <template #title>Edit SLA Setting</template>
      <template #content>
        <form @submit.prevent="update" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Name <span class="text-red-500">*</span></label>
              <InputText v-model="form.name" :class="{ 'p-invalid': form.errors.name }" />
              <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Description</label>
              <Textarea v-model="form.description" rows="3" :class="{ 'p-invalid': form.errors.description }" />
              <small v-if="form.errors.description" class="p-error">{{ form.errors.description }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">First Response Threshold (minutes) <span class="text-red-500">*</span></label>
              <InputNumber
                v-model="form.first_response_threshold"
                :min="1"
                :max="1440"
                suffix=" min"
                :class="{ 'p-invalid': form.errors.first_response_threshold }"
              />
              <small v-if="form.errors.first_response_threshold" class="p-error">{{ form.errors.first_response_threshold }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Warning Threshold (minutes) <span class="text-red-500">*</span></label>
              <InputNumber
                v-model="form.warning_threshold"
                :min="1"
                :max="1440"
                suffix=" min"
                :class="{ 'p-invalid': form.errors.warning_threshold }"
              />
              <small v-if="form.errors.warning_threshold" class="p-error">{{ form.errors.warning_threshold }}</small>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Critical Threshold (minutes)</label>
              <InputNumber
                v-model="form.critical_threshold"
                :min="1"
                :max="1440"
                suffix=" min"
                :class="{ 'p-invalid': form.errors.critical_threshold }"
              />
              <small v-if="form.errors.critical_threshold" class="p-error">{{ form.errors.critical_threshold }}</small>
            </div>

            <div class="flex flex-col md:col-span-2">
              <h3 class="text-lg font-semibold mb-4">Notification Settings</h3>
              <div class="space-y-3">
                <div class="flex items-center">
                  <Checkbox v-model="form.notify_assigned_user" inputId="notify_assigned" :binary="true" />
                  <label for="notify_assigned" class="ml-2">Notify assigned user</label>
                </div>
                <div class="flex items-center">
                  <Checkbox v-model="form.notify_managers" inputId="notify_managers" :binary="true" />
                  <label for="notify_managers" class="ml-2">Notify managers</label>
                </div>
              </div>
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Status</label>
              <Select
                v-model="form.is_active"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
              />
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Default Setting</label>
              <Select
                v-model="form.is_default"
                :options="defaultOptions"
                optionLabel="label"
                optionValue="value"
              />
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 pt-4 border-t">
            <Link href="/sla-settings">
              <Button label="Cancel" severity="secondary" outlined />
            </Link>
            <Button label="Update SLA Setting" icon="pi pi-check" :loading="form.processing" type="submit" />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import Button from 'primevue/button'
import Breadcrumb from 'primevue/breadcrumb'

export default {
  components: {
    Head,
    Link,
    Card,
    InputText,
    InputNumber,
    Textarea,
    Select,
    Checkbox,
    Button,
    Breadcrumb,
  },
  layout: Layout,
  props: {
    slaSetting: Object,
  },
  data() {
    return {
      form: this.$inertia.form({
        name: this.slaSetting.name,
        description: this.slaSetting.description || '',
        first_response_threshold: this.slaSetting.first_response_threshold,
        warning_threshold: this.slaSetting.warning_threshold,
        critical_threshold: this.slaSetting.critical_threshold,
        notify_assigned_user: this.slaSetting.notify_assigned_user ?? true,
        notify_managers: this.slaSetting.notify_managers ?? true,
        notify_user_ids: this.slaSetting.notify_user_ids || [],
        is_active: this.slaSetting.is_active !== undefined ? this.slaSetting.is_active : true,
        is_default: this.slaSetting.is_default ?? false,
      }),
      statusOptions: [
        { label: 'Active', value: true },
        { label: 'Inactive', value: false },
      ],
      defaultOptions: [
        { label: 'No', value: false },
        { label: 'Yes', value: true },
      ],
      breadcrumbItems: [
        { label: 'SLA Settings', route: '/sla-settings' },
        { label: this.slaSetting.name },
        { label: 'Edit' },
      ],
    }
  },
  methods: {
    update() {
      this.form.put(`/sla-settings/${this.slaSetting.id}`)
    },
  },
}
</script>



