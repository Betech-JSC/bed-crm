<template>
  <div>
    <Head title="Create Workflow" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <Card>
      <template #title>Create New Workflow</template>
      <template #content>
        <form @submit.prevent="store" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Name <span class="text-red-500">*</span></label>
              <InputText v-model="form.name" :class="{ 'p-invalid': form.errors.name }" />
              <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
            </div>

            <div class="flex flex-col md:col-span-2">
              <label class="mb-2 text-sm font-medium">Description</label>
              <Textarea v-model="form.description" rows="3" :class="{ 'p-invalid': form.errors.description }" />
            </div>

            <div class="flex flex-col">
              <label class="mb-2 text-sm font-medium">Trigger Event <span class="text-red-500">*</span></label>
              <Select
                v-model="form.trigger.event"
                :options="triggerEventOptions"
                optionLabel="label"
                optionValue="value"
                :class="{ 'p-invalid': form.errors['trigger.event'] }"
                @change="onTriggerChange"
              />
              <small v-if="form.errors['trigger.event']" class="p-error">{{ form.errors['trigger.event'] }}</small>
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
          </div>

          <Divider />

          <h3 class="text-lg font-semibold mb-4">Actions</h3>
          <div v-for="(action, index) in form.actions" :key="index" class="p-4 border border-gray-200 rounded-lg mb-4">
            <div class="flex items-center justify-between mb-4">
              <h4 class="font-semibold">Action {{ index + 1 }}</h4>
              <Button
                icon="pi pi-trash"
                severity="danger"
                text
                rounded
                @click="removeAction(index)"
              />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex flex-col">
                <label class="mb-2 text-sm font-medium">Action Type <span class="text-red-500">*</span></label>
                <Select
                  v-model="action.type"
                  :options="actionTypeOptions"
                  optionLabel="label"
                  optionValue="value"
                  @change="onActionTypeChange(index, action.type)"
                />
              </div>
              <div v-if="action.type === 'assign_user'" class="flex flex-col">
                <label class="mb-2 text-sm font-medium">User</label>
                <Select
                  v-model="action.user_id"
                  :options="userOptions"
                  optionLabel="label"
                  optionValue="value"
                />
              </div>
              <div v-if="action.type === 'create_task'" class="flex flex-col">
                <label class="mb-2 text-sm font-medium">Title</label>
                <InputText v-model="action.title" />
              </div>
              <div v-if="action.type === 'create_task'" class="flex flex-col">
                <label class="mb-2 text-sm font-medium">Activity Type</label>
                <Select
                  v-model="action.activity_type"
                  :options="activityTypeOptions"
                  optionLabel="label"
                  optionValue="value"
                />
              </div>
              <div v-if="action.type === 'update_field'" class="flex flex-col">
                <label class="mb-2 text-sm font-medium">Field</label>
                <InputText v-model="action.field" placeholder="e.g., status" />
              </div>
              <div v-if="action.type === 'update_field'" class="flex flex-col">
                <label class="mb-2 text-sm font-medium">Value</label>
                <InputText v-model="action.value" />
              </div>
              <div v-if="action.type === 'add_tag'" class="flex flex-col">
                <label class="mb-2 text-sm font-medium">Tag</label>
                <InputText v-model="action.tag" />
              </div>
            </div>
          </div>

          <Button
            label="Add Action"
            icon="pi pi-plus"
            severity="secondary"
            outlined
            @click="addAction"
          />

          <div class="flex items-center justify-end gap-2 pt-4 border-t">
            <Link href="/workflows">
              <Button label="Cancel" severity="secondary" outlined />
            </Link>
            <Button label="Create Workflow" icon="pi pi-check" :loading="form.processing" type="submit" />
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
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Breadcrumb from 'primevue/breadcrumb'
import Divider from 'primevue/divider'
import { useTranslation } from '@/composables/useTranslation'

export default {
  components: {
    Head,
    Link,
    Card,
    InputText,
    Textarea,
    Select,
    Button,
    Breadcrumb,
    Divider,
  },
  layout: Layout,
  props: {
    triggerEvents: Object,
    actionTypes: Object,
    salesUsers: Array,
  },
  remember: 'form',
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      form: this.$inertia.form({
        name: '',
        description: '',
        trigger: {
          event: null,
          conditions: null,
        },
        actions: [
          {
            type: 'assign_user',
            user_id: null,
          },
        ],
        is_active: true,
      }),
      statusOptions: [
        { label: 'Active', value: true },
        { label: 'Inactive', value: false },
      ],
      activityTypeOptions: [
        { label: 'Call', value: 'call' },
        { label: 'Email', value: 'email' },
        { label: 'Meeting', value: 'meeting' },
        { label: 'Note', value: 'note' },
      ],
      breadcrumbItems: [
        { label: 'Workflows', route: '/workflows' },
        { label: 'Create' },
      ],
    }
  },
  computed: {
    triggerEventOptions() {
      return Object.entries(this.triggerEvents).map(([value, label]) => ({ label, value }))
    },
    actionTypeOptions() {
      return Object.entries(this.actionTypes).map(([value, label]) => ({ label, value }))
    },
    userOptions() {
      return [
        { label: 'Select user', value: null },
        ...this.salesUsers.map(user => ({ label: user.name, value: user.id })),
      ]
    },
  },
  methods: {
    onTriggerChange() {
      // Reset conditions when trigger changes
      this.form.trigger.conditions = null
    },
    addAction() {
      this.form.actions.push({
        type: 'assign_user',
        user_id: null,
      })
    },
    removeAction(index) {
      this.form.actions.splice(index, 1)
    },
    onActionTypeChange(index, type) {
      // Reset action-specific fields when type changes
      const action = this.form.actions[index]
      Object.keys(action).forEach(key => {
        if (key !== 'type') {
          delete action[key]
        }
      })
    },
    store() {
      this.form.post('/workflows')
    },
  },
}
</script>

