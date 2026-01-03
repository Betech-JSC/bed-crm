<template>
  <div>
    <div class="mb-4 flex items-center justify-between">
      <h3 class="text-lg font-semibold">Activity Timeline</h3>
      <Button
        v-if="showAddButton"
        label="Add Activity"
        icon="pi pi-plus"
        size="small"
        @click="showAddDialog = true"
      />
    </div>

    <!-- Add Activity Dialog -->
    <Dialog
      v-model:visible="showAddDialog"
      modal
      header="Add Activity"
      :style="{ width: '500px' }"
    >
      <form @submit.prevent="store" class="space-y-4">
        <div class="flex flex-col">
          <label class="mb-2 text-sm font-medium">Type <span class="text-red-500">*</span></label>
          <Select
            v-model="form.type"
            :options="typeOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Select activity type"
            :class="{ 'p-invalid': form.errors.type }"
          />
          <small v-if="form.errors.type" class="p-error">{{ form.errors.type }}</small>
        </div>

        <div class="flex flex-col">
          <label class="mb-2 text-sm font-medium">Title</label>
          <InputText v-model="form.title" placeholder="Activity title (optional)" :class="{ 'p-invalid': form.errors.title }" />
          <small v-if="form.errors.title" class="p-error">{{ form.errors.title }}</small>
        </div>

        <div class="flex flex-col">
          <label class="mb-2 text-sm font-medium">Date & Time <span class="text-red-500">*</span></label>
          <Calendar
            v-model="form.date"
            showTime
            hourFormat="12"
            dateFormat="yy-mm-dd"
            :class="{ 'p-invalid': form.errors.date }"
          />
          <small v-if="form.errors.date" class="p-error">{{ form.errors.date }}</small>
        </div>

        <div class="flex flex-col">
          <label class="mb-2 text-sm font-medium">Description</label>
          <Textarea
            v-model="form.description"
            rows="4"
            placeholder="Enter activity description..."
            :class="{ 'p-invalid': form.errors.description }"
          />
          <small v-if="form.errors.description" class="p-error">{{ form.errors.description }}</small>
        </div>

        <div class="flex justify-end gap-2">
          <Button label="Cancel" severity="secondary" outlined @click="showAddDialog = false" />
          <Button label="Add Activity" icon="pi pi-check" :loading="form.processing" type="submit" />
        </div>
      </form>
    </Dialog>

    <!-- Timeline -->
    <div v-if="activities.length > 0" class="space-y-4">
      <div
        v-for="activity in activities"
        :key="activity.id"
        class="relative flex gap-4 pb-4 border-l-2 border-gray-200 pl-4 last:border-l-0 last:pb-0"
      >
        <!-- Icon -->
        <div
          class="flex-shrink-0 mt-1 w-10 h-10 rounded-full flex items-center justify-center"
          :class="getActivityColorClass(activity.type)"
        >
          <i :class="`pi ${getActivityIcon(activity.type)} text-white`" />
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-1">
                <span class="font-semibold text-gray-900">{{ activity.title || getActivityTypeLabel(activity.type) }}</span>
                <Tag :value="getActivityTypeLabel(activity.type)" :severity="getActivitySeverity(activity.type)" />
              </div>
              <p v-if="activity.description" class="text-sm text-gray-600 whitespace-pre-wrap mb-2">
                {{ activity.description }}
              </p>
              <div class="flex items-center gap-4 text-xs text-gray-500">
                <span>
                  <i class="pi pi-calendar mr-1" />
                  {{ formatDateTime(activity.date) }}
                </span>
                <span v-if="activity.user">
                  <i class="pi pi-user mr-1" />
                  {{ activity.user.name }}
                </span>
              </div>
            </div>
            <div v-if="canEdit" class="flex gap-1">
              <Button
                icon="pi pi-pencil"
                size="small"
                severity="secondary"
                text
                rounded
                @click="editActivity(activity)"
              />
              <Button
                icon="pi pi-trash"
                size="small"
                severity="danger"
                text
                rounded
                @click="deleteActivity(activity)"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="py-8 text-center text-gray-400 text-sm">
      No activities yet. Click "Add Activity" to get started.
    </div>

    <!-- Edit Activity Dialog -->
    <Dialog
      v-model:visible="showEditDialog"
      modal
      header="Edit Activity"
      :style="{ width: '500px' }"
    >
      <form v-if="editingActivity" @submit.prevent="update" class="space-y-4">
        <div class="flex flex-col">
          <label class="mb-2 text-sm font-medium">Type <span class="text-red-500">*</span></label>
          <Select
            v-model="editForm.type"
            :options="typeOptions"
            optionLabel="label"
            optionValue="value"
            :class="{ 'p-invalid': editForm.errors.type }"
          />
          <small v-if="editForm.errors.type" class="p-error">{{ editForm.errors.type }}</small>
        </div>

        <div class="flex flex-col">
          <label class="mb-2 text-sm font-medium">Title</label>
          <InputText v-model="editForm.title" :class="{ 'p-invalid': editForm.errors.title }" />
          <small v-if="editForm.errors.title" class="p-error">{{ editForm.errors.title }}</small>
        </div>

        <div class="flex flex-col">
          <label class="mb-2 text-sm font-medium">Date & Time <span class="text-red-500">*</span></label>
          <Calendar
            v-model="editForm.date"
            showTime
            hourFormat="12"
            dateFormat="yy-mm-dd"
            :class="{ 'p-invalid': editForm.errors.date }"
          />
          <small v-if="editForm.errors.date" class="p-error">{{ editForm.errors.date }}</small>
        </div>

        <div class="flex flex-col">
          <label class="mb-2 text-sm font-medium">Description</label>
          <Textarea
            v-model="editForm.description"
            rows="4"
            :class="{ 'p-invalid': editForm.errors.description }"
          />
          <small v-if="editForm.errors.description" class="p-error">{{ editForm.errors.description }}</small>
        </div>

        <div class="flex justify-end gap-2">
          <Button label="Cancel" severity="secondary" outlined @click="showEditDialog = false" />
          <Button label="Update Activity" icon="pi pi-check" :loading="editForm.processing" type="submit" />
        </div>
      </form>
    </Dialog>
  </div>
</template>

<script>
import { router } from '@inertiajs/vue3'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Calendar from 'primevue/calendar'
import Tag from 'primevue/tag'

export default {
  components: {
    Button,
    Dialog,
    InputText,
    Textarea,
    Select,
    Calendar,
    Tag,
  },
  props: {
    activities: {
      type: Array,
      default: () => [],
    },
    subjectType: {
      type: String,
      required: true,
    },
    subjectId: {
      type: Number,
      required: true,
    },
    showAddButton: {
      type: Boolean,
      default: true,
    },
    canEdit: {
      type: Boolean,
      default: true,
    },
  },
  data() {
    return {
      showAddDialog: false,
      showEditDialog: false,
      editingActivity: null,
      form: this.$inertia.form({
        subject_type: this.subjectType,
        subject_id: this.subjectId,
        type: 'note',
        title: '',
        description: '',
        date: new Date(),
      }),
      editForm: this.$inertia.form({
        type: '',
        title: '',
        description: '',
        date: null,
      }),
      typeOptions: [
        { label: 'Call', value: 'call' },
        { label: 'Email', value: 'email' },
        { label: 'Meeting', value: 'meeting' },
        { label: 'Note', value: 'note' },
      ],
    }
  },
  methods: {
    store() {
      this.form.post('/activities', {
        preserveScroll: true,
        onSuccess: () => {
          this.showAddDialog = false
          this.form.reset()
          this.form.date = new Date()
        },
      })
    },
    editActivity(activity) {
      this.editingActivity = activity
      this.editForm.type = activity.type
      this.editForm.title = activity.title || ''
      this.editForm.description = activity.description || ''
      this.editForm.date = new Date(activity.date)
      this.showEditDialog = true
    },
    update() {
      this.editForm.put(`/activities/${this.editingActivity.id}`, {
        preserveScroll: true,
        onSuccess: () => {
          this.showEditDialog = false
          this.editingActivity = null
          this.editForm.reset()
        },
      })
    },
    deleteActivity(activity) {
      if (confirm('Are you sure you want to delete this activity?')) {
        router.delete(`/activities/${activity.id}`, {
          preserveScroll: true,
        })
      }
    },
    getActivityIcon(type) {
      const icons = {
        call: 'pi-phone',
        email: 'pi-envelope',
        meeting: 'pi-calendar',
        note: 'pi-file-edit',
      }
      return icons[type] || 'pi-circle'
    },
    getActivityTypeLabel(type) {
      const labels = {
        call: 'Call',
        email: 'Email',
        meeting: 'Meeting',
        note: 'Note',
      }
      return labels[type] || type
    },
    getActivitySeverity(type) {
      const severities = {
        call: 'info',
        email: 'success',
        meeting: 'warning',
        note: 'secondary',
      }
      return severities[type] || 'secondary'
    },
    getActivityColorClass(type) {
      const colors = {
        call: 'bg-blue-500',
        email: 'bg-green-500',
        meeting: 'bg-purple-500',
        note: 'bg-gray-500',
      }
      return colors[type] || 'bg-gray-500'
    },
    formatDateTime(dateString) {
      const date = new Date(dateString)
      return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
      })
    },
  },
}
</script>

<style scoped>
.relative:not(:last-child)::before {
  content: '';
  position: absolute;
  left: -2px;
  top: 3rem;
  bottom: -1rem;
  width: 2px;
  background: #e5e7eb;
}
</style>

