<template>
  <div>
    <Head title="Edit Role" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <Card>
      <template #title>Edit Role</template>
      <template #content>
        <form @submit.prevent="submit">
          <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-2">Name *</label>
                <InputText v-model="form.name" class="w-full" :class="{ 'p-invalid': form.errors.name }" />
                <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium mb-2">Slug *</label>
                <InputText v-model="form.slug" class="w-full" :class="{ 'p-invalid': form.errors.slug }" />
                <small v-if="form.errors.slug" class="p-error">{{ form.errors.slug }}</small>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">Description</label>
              <Textarea v-model="form.description" rows="3" class="w-full" />
            </div>

            <div class="flex items-center gap-2">
              <Checkbox v-model="form.is_active" inputId="is_active" :binary="true" />
              <label for="is_active" class="text-sm">Active</label>
            </div>

            <div class="border-t pt-4">
              <label class="block text-sm font-medium mb-4">Permissions</label>
              <div class="space-y-4 max-h-96 overflow-y-auto">
                <div v-for="group in permissions" :key="group.group" class="border rounded-lg p-4">
                  <h4 class="font-semibold mb-3 text-gray-700">{{ group.group }}</h4>
                  <div class="space-y-2">
                    <div v-for="permission in group.permissions" :key="permission.id" class="flex items-center gap-2">
                      <Checkbox
                        :inputId="`perm_${permission.id}`"
                        :value="permission.id"
                        v-model="form.permissions"
                        :binary="false"
                      />
                      <label :for="`perm_${permission.id}`" class="text-sm cursor-pointer">
                        {{ permission.name }}
                        <span class="text-gray-500 text-xs ml-2">({{ permission.slug }})</span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex items-center justify-end gap-2">
              <Link href="/roles">
                <Button label="Cancel" severity="secondary" outlined />
              </Link>
              <Button label="Update" type="submit" :loading="form.processing" />
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
import Textarea from 'primevue/textarea'
import Checkbox from 'primevue/checkbox'
import Button from 'primevue/button'
import Breadcrumb from 'primevue/breadcrumb'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: {
    Head,
    Link,
    Card,
    InputText,
    Textarea,
    Checkbox,
    Button,
    Breadcrumb,
  },
  layout: Layout,
  props: {
    role: Object,
    permissions: Array,
    selectedPermissions: Array,
  },
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      form: useForm({
        name: this.role?.name || '',
        slug: this.role?.slug || '',
        description: this.role?.description || '',
        is_active: this.role?.is_active ?? true,
        permissions: this.selectedPermissions || [],
      }),
      breadcrumbItems: [
        { label: 'Roles', route: '/roles' },
        { label: 'Edit' },
      ],
    }
  },
  methods: {
    submit() {
      this.form.put(`/roles/${this.role.id}`)
    },
  },
}
</script>

