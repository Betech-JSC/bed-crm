<template>
  <div>
    <Head :title="deal.title" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <Message v-if="deal.deleted_at" severity="warn" :closable="false" class="mb-4">
      This deal has been deleted.
      <Button label="Restore" size="small" severity="warning" outlined class="ml-2" @click="restore" />
    </Message>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main Form -->
      <div class="lg:col-span-2">
        <Card>
          <template #title>Edit Deal</template>
          <template #content>
            <form @submit.prevent="update" class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex flex-col">
                  <label class="mb-2 text-sm font-medium">Title <span class="text-red-500">*</span></label>
                  <InputText v-model="form.title" :class="{ 'p-invalid': form.errors.title }" />
                  <small v-if="form.errors.title" class="p-error">{{ form.errors.title }}</small>
                </div>

                <div class="flex flex-col">
                  <label class="mb-2 text-sm font-medium">Stage <span class="text-red-500">*</span></label>
                  <Select
                    v-model="form.stage"
                    :options="stageOptions"
                    optionLabel="label"
                    optionValue="value"
                    :class="{ 'p-invalid': form.errors.stage }"
                  />
                  <small v-if="form.errors.stage" class="p-error">{{ form.errors.stage }}</small>
                </div>

                <div class="flex flex-col">
                  <label class="mb-2 text-sm font-medium">Value</label>
                  <InputNumber
                    v-model="form.value"
                    mode="currency"
                    currency="USD"
                    locale="en-US"
                    :class="{ 'p-invalid': form.errors.value }"
                  />
                  <small v-if="form.errors.value" class="p-error">{{ form.errors.value }}</small>
                </div>

                <div class="flex flex-col">
                  <label class="mb-2 text-sm font-medium">Expected Close Date</label>
                  <Calendar
                    v-model="form.expected_close_date"
                    dateFormat="yy-mm-dd"
                    :class="{ 'p-invalid': form.errors.expected_close_date }"
                  />
                  <small v-if="form.errors.expected_close_date" class="p-error">{{ form.errors.expected_close_date }}</small>
                </div>

                <div class="flex flex-col">
                  <label class="mb-2 text-sm font-medium">Status <span class="text-red-500">*</span></label>
                  <Select
                    v-model="form.status"
                    :options="statusOptions"
                    optionLabel="label"
                    optionValue="value"
                    :class="{ 'p-invalid': form.errors.status }"
                  />
                  <small v-if="form.errors.status" class="p-error">{{ form.errors.status }}</small>
                </div>

                <div class="flex flex-col">
                  <label class="mb-2 text-sm font-medium">Assign To</label>
                  <Select
                    v-model="form.assigned_to"
                    :options="assignedOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Unassigned"
                    :class="{ 'p-invalid': form.errors.assigned_to }"
                  />
                  <small v-if="form.errors.assigned_to" class="p-error">{{ form.errors.assigned_to }}</small>
                </div>
              </div>

              <div v-if="form.status === 'lost'" class="flex flex-col">
                <label class="mb-2 text-sm font-medium">Lost Reason <span class="text-red-500">*</span></label>
                <Textarea v-model="form.lost_reason" rows="3" placeholder="Why was this deal lost?" :class="{ 'p-invalid': form.errors.lost_reason }" />
                <small v-if="form.errors.lost_reason" class="p-error">{{ form.errors.lost_reason }}</small>
              </div>

              <div class="flex flex-col">
                <label class="mb-2 text-sm font-medium">Notes</label>
                <Textarea v-model="form.notes" rows="6" :class="{ 'p-invalid': form.errors.notes }" />
                <small v-if="form.errors.notes" class="p-error">{{ form.errors.notes }}</small>
              </div>

              <div v-if="deal.lead" class="p-4 bg-blue-50 rounded-lg">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-medium text-gray-700">Related Lead</p>
                    <Link :href="`/leads/${deal.lead.id}/edit`" class="text-primary-600 hover:text-primary-800">
                      {{ deal.lead.name }}
                      <span v-if="deal.lead.company"> - {{ deal.lead.company }}</span>
                    </Link>
                  </div>
                </div>
              </div>

              <div class="flex items-center justify-between pt-4 border-t">
                <div class="flex gap-2">
                  <Button
                    v-if="form.status === 'open'"
                    label="Mark as Won"
                    icon="pi pi-check"
                    severity="success"
                    outlined
                    @click="markWon"
                  />
                  <Button
                    v-if="form.status === 'open'"
                    label="Mark as Lost"
                    icon="pi pi-times"
                    severity="danger"
                    outlined
                    @click="showLostDialog = true"
                  />
                  <Button
                    v-if="!deal.deleted_at"
                    label="Delete Deal"
                    icon="pi pi-trash"
                    severity="danger"
                    outlined
                    @click="destroy"
                  />
                </div>
                <div class="flex gap-2">
                  <Link href="/deals">
                    <Button label="Cancel" severity="secondary" outlined />
                  </Link>
                  <Button label="Update Deal" icon="pi pi-check" :loading="form.processing" type="submit" />
                </div>
              </div>
            </form>
          </template>
        </Card>
      </div>

      <!-- Actions Sidebar -->
      <div class="lg:col-span-1 space-y-6">
        <Card>
          <template #title>Quick Actions</template>
          <template #content>
            <div class="grid grid-cols-2 gap-2">
              <Button
                v-if="form.status === 'open'"
                label="Won"
                icon="pi pi-check"
                severity="success"
                size="small"
                class="w-full"
                @click="markWon"
              />
              <Button
                v-if="form.status === 'open'"
                label="Lost"
                icon="pi pi-times"
                severity="danger"
                size="small"
                class="w-full"
                @click="showLostDialog = true"
              />
            </div>
          </template>
        </Card>



        <Card>
          <template #content>
            <ActivityTimeline
              :activities="activities"
              subject-type="App\Models\Deal"
              :subject-id="deal.id"
            />
          </template>
        </Card>
      </div>
    </div>

    <!-- Lost Reason Dialog -->
    <Dialog v-model:visible="showLostDialog" modal header="Mark Deal as Lost" :style="{ width: '500px' }">
      <form @submit.prevent="markLost" class="space-y-4">
        <div class="flex flex-col">
          <label class="mb-2 text-sm font-medium">Lost Reason <span class="text-red-500">*</span></label>
          <Textarea v-model="lostForm.lost_reason" rows="4" placeholder="Why was this deal lost?" :class="{ 'p-invalid': lostForm.errors.lost_reason }" />
          <small v-if="lostForm.errors.lost_reason" class="p-error">{{ lostForm.errors.lost_reason }}</small>
        </div>
        <div class="flex justify-end gap-2">
          <Button label="Cancel" severity="secondary" outlined @click="showLostDialog = false" />
          <Button label="Mark as Lost" icon="pi pi-times" severity="danger" :loading="lostForm.processing" type="submit" />
        </div>
      </form>
    </Dialog>
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
import Calendar from 'primevue/calendar'
import Button from 'primevue/button'
import Message from 'primevue/message'
import Breadcrumb from 'primevue/breadcrumb'
import Dialog from 'primevue/dialog'
import ActivityTimeline from '@/Shared/ActivityTimeline.vue'

import { useTranslation } from '@/composables/useTranslation'

export default {
  components: {
    Head,
    Link,
    Card,
    InputText,
    InputNumber,
    Textarea,
    Select,
    Calendar,
    Button,
    Message,
    Breadcrumb,
    Dialog,
    ActivityTimeline,

  },
  layout: Layout,
  props: {
    deal: Object,
    activities: Array,
    stages: Object,
    statuses: Object,
    salesUsers: Array,
    proposals: Array,
  },
  remember: 'form',
  setup() {
    const { t } = useTranslation()
    return { t }
  },
  data() {
    return {
      showLostDialog: false,
      form: this.$inertia.form({
        title: this.deal.title,
        stage: this.deal.stage,
        value: this.deal.value,
        expected_close_date: this.deal.expected_close_date,
        status: this.deal.status,
        lost_reason: this.deal.lost_reason,
        assigned_to: this.deal.assigned_to,
        notes: this.deal.notes,
      }),
      lostForm: this.$inertia.form({
        lost_reason: '',
      }),
      breadcrumbItems: [
        { label: 'Deals', route: '/deals' },
        { label: this.deal.title },
      ],
    }
  },
  computed: {
    stageOptions() {
      return Object.entries(this.stages).map(([value, label]) => ({ label, value }))
    },
    statusOptions() {
      return Object.entries(this.statuses).map(([value, label]) => ({ label, value }))
    },
    assignedOptions() {
      return [
        { label: 'Unassigned', value: null },
        ...this.salesUsers.map(user => ({ label: user.name, value: user.id })),
      ]
    },
  },
  methods: {
    update() {
      this.form.put(`/deals/${this.deal.id}`)
    },
    destroy() {
      if (confirm('Are you sure you want to delete this deal?')) {
        this.$inertia.delete(`/deals/${this.deal.id}`)
      }
    },
    restore() {
      if (confirm('Are you sure you want to restore this deal?')) {
        this.$inertia.put(`/deals/${this.deal.id}/restore`)
      }
    },
    markWon() {
      this.$inertia.post(`/deals/${this.deal.id}/won`, {}, {
        preserveScroll: true,
        onSuccess: () => {
          this.form.status = 'won'
        },
      })
    },
    markLost() {
      this.lostForm.post(`/deals/${this.deal.id}/lost`, {
        preserveScroll: true,
        onSuccess: () => {
          this.showLostDialog = false
          this.form.status = 'lost'
          this.form.lost_reason = this.lostForm.lost_reason
          this.lostForm.reset()
        },
      })
    },
  },
}
</script>

