<template>
  <div>
    <Head :title="lead.name" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <Message v-if="lead.deleted_at" severity="warn" :closable="false" class="mb-4">
      This lead has been deleted.
      <Button label="Restore" size="small" severity="warning" outlined class="ml-2" @click="restore" />
    </Message>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main Form -->
      <div class="lg:col-span-2">
        <Card>
          <template #title>Edit Lead</template>
          <template #content>
            <form @submit.prevent="update" class="space-y-6">
              <Message v-if="form.errors.duplicate" severity="error" :closable="false">
                <div class="mb-2">{{ form.errors.duplicate }}</div>
                <Link v-if="form.errors.duplicate_id" :href="`/leads/${form.errors.duplicate_id}/edit`" class="text-sm underline">
                  View existing lead
                </Link>
              </Message>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="flex flex-col">
                  <label class="mb-2 text-sm font-medium">Name <span class="text-red-500">*</span></label>
                  <InputText v-model="form.name" :class="{ 'p-invalid': form.errors.name }" />
                  <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
                </div>

                <div class="flex flex-col">
                  <label class="mb-2 text-sm font-medium">Company</label>
                  <InputText v-model="form.company" :class="{ 'p-invalid': form.errors.company }" />
                  <small v-if="form.errors.company" class="p-error">{{ form.errors.company }}</small>
                </div>

                <div class="flex flex-col">
                  <label class="mb-2 text-sm font-medium">Phone</label>
                  <InputText v-model="form.phone" :class="{ 'p-invalid': form.errors.phone }" />
                  <small v-if="form.errors.phone" class="p-error">{{ form.errors.phone }}</small>
                </div>

                <div class="flex flex-col">
                  <label class="mb-2 text-sm font-medium">Email</label>
                  <InputText v-model="form.email" type="email" :class="{ 'p-invalid': form.errors.email }" />
                  <small v-if="form.errors.email" class="p-error">{{ form.errors.email }}</small>
                </div>

                <div class="flex flex-col">
                  <label class="mb-2 text-sm font-medium">Source</label>
                  <Select
                    v-model="form.source"
                    :options="sourceOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select source"
                    :class="{ 'p-invalid': form.errors.source }"
                  />
                  <small v-if="form.errors.source" class="p-error">{{ form.errors.source }}</small>
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

                <div class="flex flex-col">
                  <label class="mb-2 text-sm font-medium">Tags</label>
                  <InputText v-model="tagsInput" placeholder="e.g. hot, vip, follow-up" />
                  <small class="text-gray-500 text-xs mt-1">Separate tags with commas</small>
                </div>
              </div>

              <div class="flex flex-col">
                <label class="mb-2 text-sm font-medium">Notes</label>
                <Textarea v-model="form.notes" rows="6" :class="{ 'p-invalid': form.errors.notes }" />
                <small v-if="form.errors.notes" class="p-error">{{ form.errors.notes }}</small>
              </div>

              <!-- Lead Scoring & Enrichment -->
              <div class="p-4 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg border border-blue-200">
                <div class="flex items-center justify-between mb-4">
                  <div>
                    <p class="text-sm font-medium text-gray-700">Lead Scoring</p>
                    <div v-if="lead.score !== null && lead.score !== undefined" class="mt-2 flex items-center gap-2">
                      <Tag :value="`${lead.score}/100`" :severity="getScoreSeverity(lead.score)" />
                      <Tag :value="lead.priority_label || 'Cold'" :severity="lead.priority_severity || 'info'" />
                      <span v-if="lead.icp" class="text-xs text-gray-600">ICP: {{ lead.icp.name }}</span>
                    </div>
                    <p v-else class="text-xs text-gray-500 mt-1">Not scored yet</p>
                  </div>
                  <div class="flex gap-2">
                    <Button
                      label="Enrich"
                      icon="pi pi-search"
                      size="small"
                      severity="secondary"
                      outlined
                      :loading="enriching"
                      @click="enrichLead"
                    />
                    <Button
                      label="Score"
                      icon="pi pi-star"
                      size="small"
                      severity="secondary"
                      outlined
                      @click="scoreLead"
                    />
                  </div>
                </div>

                <!-- Scoring Breakdown -->
                <div v-if="lead.scoring_details && lead.scoring_details.details" class="mt-4 space-y-3">
                  <Divider />
                  <div class="space-y-2">
                    <h4 class="text-xs font-semibold text-gray-700 uppercase">Scoring Breakdown</h4>
                    <div v-for="(detail, key) in lead.scoring_details.details" :key="key" class="text-xs">
                      <div class="flex items-center justify-between mb-1">
                        <span class="text-gray-600 capitalize">{{ key.replace('_', ' ') }}:</span>
                        <span class="font-medium">{{ detail.score }}/100 ({{ detail.weight }}%)</span>
                      </div>
                      <div class="w-full bg-gray-200 rounded-full h-1.5 mb-2">
                        <div
                          class="bg-primary-500 h-1.5 rounded-full"
                          :style="{ width: `${detail.score}%` }"
                        />
                      </div>
                      <p class="text-gray-500 text-xs mb-2">{{ detail.explanation }}</p>
                    </div>
                  </div>
                  
                  <!-- Formula -->
                  <div v-if="lead.scoring_details.formula" class="mt-3 p-2 bg-gray-50 rounded border border-gray-200">
                    <p class="text-xs font-semibold text-gray-700 mb-1">Formula:</p>
                    <p class="text-xs text-gray-600 font-mono">{{ lead.scoring_details.formula }}</p>
                  </div>

                  <!-- Suggested Action -->
                  <div v-if="lead.scoring_details.suggested_action" class="mt-3 p-3 bg-white rounded border border-gray-200">
                    <div class="flex items-start gap-2">
                      <i :class="lead.scoring_details.suggested_action.icon || 'pi pi-info-circle'" class="text-primary-500 mt-0.5" />
                      <div class="flex-1">
                        <p class="text-xs font-semibold text-gray-700">{{ lead.scoring_details.suggested_action.label }}</p>
                        <p class="text-xs text-gray-600 mt-1">{{ lead.scoring_details.suggested_action.description }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Engagement Stats -->
                <div v-if="lead.email_opens > 0 || lead.website_visits > 0" class="mt-4 pt-4 border-t border-gray-200">
                  <h4 class="text-xs font-semibold text-gray-700 uppercase mb-2">Engagement</h4>
                  <div class="grid grid-cols-2 gap-2 text-xs">
                    <div>
                      <span class="text-gray-600">Email Opens:</span>
                      <span class="font-medium ml-1">{{ lead.email_opens || 0 }}</span>
                    </div>
                    <div>
                      <span class="text-gray-600">Email Clicks:</span>
                      <span class="font-medium ml-1">{{ lead.email_clicks || 0 }}</span>
                    </div>
                    <div>
                      <span class="text-gray-600">Website Visits:</span>
                      <span class="font-medium ml-1">{{ lead.website_visits || 0 }}</span>
                    </div>
                    <div>
                      <span class="text-gray-600">Page Views:</span>
                      <span class="font-medium ml-1">{{ lead.page_views || 0 }}</span>
                    </div>
                  </div>
                </div>

                <!-- Enrichment Data -->
                <div v-if="lead.enrichment_data" class="mt-4 pt-4 border-t border-gray-200">
                  <h4 class="text-xs font-semibold text-gray-700 uppercase mb-2">Enriched Data</h4>
                  <div class="text-xs text-gray-600 space-y-1">
                    <p v-if="lead.enrichment_data.industry"><strong>Industry:</strong> {{ lead.enrichment_data.industry }}</p>
                    <p v-if="lead.enrichment_data.employees"><strong>Employees:</strong> {{ lead.enrichment_data.employees }}</p>
                    <p v-if="lead.enrichment_data.location"><strong>Location:</strong> {{ lead.enrichment_data.location }}</p>
                    <p v-if="lead.enrichment_data.job_title"><strong>Job Title:</strong> {{ lead.enrichment_data.job_title }}</p>
                  </div>
                </div>
              </div>

              <!-- SLA Tracking -->
              <div v-if="lead.sla_setting" class="p-4 bg-gradient-to-br from-orange-50 to-red-50 rounded-lg border border-orange-200 mt-4">
                <div class="flex items-center justify-between mb-3">
                  <div>
                    <p class="text-sm font-medium text-gray-700">SLA Response Time</p>
                    <div class="mt-1 flex items-center gap-2">
                      <Tag
                        :value="getSLAStatusLabel(lead.sla_status)"
                        :severity="getSLAStatusSeverity(lead.sla_status)"
                      />
                      <span v-if="lead.response_time_minutes" class="text-xs text-gray-600">
                        Response: {{ lead.response_time_minutes }} min
                      </span>
                    </div>
                  </div>
                </div>

                <div v-if="lead.sla_started_at && !lead.first_response_at" class="space-y-2">
                  <div class="text-xs">
                    <span class="text-gray-600">Threshold:</span>
                    <span class="font-medium ml-1">{{ lead.sla_setting.first_response_threshold }} minutes</span>
                  </div>
                  <div class="text-xs">
                    <span class="text-gray-600">Elapsed:</span>
                    <span class="font-medium ml-1">{{ getElapsedMinutes(lead.sla_started_at) }} minutes</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div
                      class="h-2 rounded-full transition-all"
                      :class="getSLAProgressClass(lead.sla_status)"
                      :style="{ width: `${getSLAProgress(lead)}%` }"
                    />
                  </div>
                  <p v-if="lead.sla_status === 'breached'" class="text-xs text-red-600 font-medium">
                    ⚠️ SLA Breached! Immediate action required.
                  </p>
                  <p v-else-if="lead.sla_status === 'warning'" class="text-xs text-orange-600 font-medium">
                    ⚠️ Approaching SLA threshold.
                  </p>
                </div>

                <div v-if="lead.first_response_at" class="text-xs text-green-600">
                  ✓ First response recorded at {{ formatDateTime(lead.first_response_at) }}
                </div>
              </div>

              <div v-if="!lead.deal" class="p-4 bg-green-50 rounded-lg border border-green-200">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-medium text-gray-700">Ready to convert?</p>
                    <p class="text-xs text-gray-600 mt-1">Convert this lead to a deal to start tracking in the pipeline</p>
                  </div>
                  <Button
                    label="Convert to Deal"
                    icon="pi pi-arrow-right"
                    severity="success"
                    @click="convertToDeal"
                  />
                </div>
              </div>
              <div v-else class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-medium text-gray-700">Already converted</p>
                    <Link :href="`/deals/${lead.deal.id}/edit`" class="text-xs text-primary-600 hover:text-primary-800">
                      View Deal →
                    </Link>
                  </div>
                </div>
              </div>
              <div class="flex items-center justify-between pt-4 border-t">
                <Button
                  v-if="!lead.deleted_at"
                  label="Delete Lead"
                  icon="pi pi-trash"
                  severity="danger"
                  outlined
                  @click="destroy"
                />
                <div class="flex gap-2 ml-auto">
                  <Link href="/leads">
                    <Button label="Cancel" severity="secondary" outlined />
                  </Link>
                  <Button label="Update Lead" icon="pi pi-check" :loading="form.processing" type="submit" />
                </div>
              </div>
            </form>
          </template>
        </Card>
      </div>

      <!-- Notes Sidebar -->
      <div class="lg:col-span-1 space-y-6">
        <Card>
          <template #title>Add Note</template>
          <template #content>
            <form @submit.prevent="addNote" class="space-y-4">
              <Textarea v-model="noteForm.note" rows="4" placeholder="Enter your note here..." :class="{ 'p-invalid': noteForm.errors.note }" />
              <small v-if="noteForm.errors.note" class="p-error">{{ noteForm.errors.note }}</small>
              <Button label="Add Note" icon="pi pi-plus" :loading="noteForm.processing" type="submit" class="w-full" />
            </form>

            <Divider />

            <div v-if="formattedNotes.length > 0">
              <h3 class="mb-4 text-sm font-semibold text-gray-700">Notes History</h3>
              <div class="space-y-3 max-h-96 overflow-y-auto">
                <div v-for="(note, index) in formattedNotes" :key="index" class="p-3 bg-gray-50 rounded-lg">
                  <div class="mb-2 flex items-center justify-between">
                    <span class="text-xs font-medium text-gray-500">{{ note.date }}</span>
                  </div>
                  <div class="text-sm text-gray-800 whitespace-pre-wrap">{{ note.text }}</div>
                </div>
              </div>
            </div>
            <div v-else class="py-8 text-center text-gray-400 text-sm">No notes yet</div>
          </template>
        </Card>

        <!-- Sales Playbook Suggestions -->
        <PlaybookSuggestions
          v-if="playbookSuggestions && playbookSuggestions.length > 0"
          :playbooks="playbookSuggestions"
          subject-type="lead"
          :subject-id="lead.id"
        />

        <Card>
          <template #content>
            <ActivityTimeline
              :activities="activities"
              subject-type="App\Models\Lead"
              :subject-id="lead.id"
            />
          </template>
        </Card>
      </div>
    </div>
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
import Message from 'primevue/message'
import Breadcrumb from 'primevue/breadcrumb'
import Divider from 'primevue/divider'
import Tag from 'primevue/tag'
import ActivityTimeline from '@/Shared/ActivityTimeline.vue'
import PlaybookSuggestions from '@/Shared/PlaybookSuggestions.vue'

export default {
  components: {
    Head,
    Link,
    Card,
    InputText,
    Textarea,
    Select,
    Button,
    Message,
    Breadcrumb,
    Divider,
    Tag,
    ActivityTimeline,
    PlaybookSuggestions,
  },
  layout: Layout,
  props: {
    lead: Object,
    activities: Array,
    statuses: Object,
    sources: Object,
    salesUsers: Array,
    playbookSuggestions: Array,
  },
  data() {
    return {
      enriching: false,
    }
  },
  remember: 'form',
  data() {
    return {
      tagsInput: this.lead.tags ? this.lead.tags.join(', ') : '',
      form: this.$inertia.form({
        name: this.lead.name,
        phone: this.lead.phone,
        email: this.lead.email,
        company: this.lead.company,
        source: this.lead.source,
        status: this.lead.status,
        assigned_to: this.lead.assigned_to,
        notes: this.lead.notes,
        tags: this.lead.tags || [],
      }),
      noteForm: this.$inertia.form({
        note: '',
      }),
      enriching: false,
      breadcrumbItems: [
        { label: 'Leads', route: '/leads' },
        { label: this.lead.name },
      ],
    }
  },
  computed: {
    statusOptions() {
      return Object.entries(this.statuses).map(([value, label]) => ({ label, value }))
    },
    sourceOptions() {
      return [{ label: 'Select source', value: null }, ...Object.entries(this.sources).map(([value, label]) => ({ label, value }))]
    },
    assignedOptions() {
      return [{ label: 'Unassigned', value: null }, ...this.salesUsers.map(user => ({ label: user.name, value: user.id }))]
    },
    hasDeal() {
      return this.lead.deal !== null && this.lead.deal !== undefined
    },
    formattedNotes() {
      if (!this.lead.notes) return []
      const notes = this.lead.notes.split('\n\n').filter(n => n.trim())
      return notes.map(note => {
        const match = note.match(/^\[(.+?)\]\s*(.+)$/s)
        if (match) {
          return { date: match[1], text: match[2].trim() }
        }
        return { date: '', text: note.trim() }
      }).reverse()
    },
  },
  watch: {
    tagsInput(newVal) {
      this.form.tags = newVal.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0)
    },
  },
  methods: {
    update() {
      this.form.put(`/leads/${this.lead.id}`)
    },
    destroy() {
      if (confirm('Are you sure you want to delete this lead?')) {
        this.$inertia.delete(`/leads/${this.lead.id}`)
      }
    },
    restore() {
      if (confirm('Are you sure you want to restore this lead?')) {
        this.$inertia.put(`/leads/${this.lead.id}/restore`)
      }
    },
    addNote() {
      this.noteForm.post(`/leads/${this.lead.id}/notes`, {
        preserveScroll: true,
        onSuccess: () => {
          this.noteForm.reset()
          // Refresh lead data
          this.$inertia.reload({ only: ['lead'] })
        },
      })
    },
    convertToDeal() {
      this.$inertia.post(`/leads/${this.lead.id}/convert`, {}, {
        preserveScroll: true,
      })
    },
    async enrichLead() {
      this.enriching = true
      try {
        const response = await fetch(`/leads/${this.lead.id}/enrich`, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Accept': 'application/json',
          },
        })
        const data = await response.json()
        if (data.success) {
          this.$inertia.reload({ only: ['lead'] })
        }
      } catch (error) {
        console.error('Enrichment failed:', error)
      } finally {
        this.enriching = false
      }
    },
    scoreLead() {
      this.$inertia.post(`/leads/${this.lead.id}/score`, {}, {
        preserveScroll: true,
      })
    },
    getScoreSeverity(score) {
      if (score >= 80) return 'success'
      if (score >= 60) return 'warning'
      if (score >= 40) return 'info'
      return 'danger'
    },
    getSLAStatusLabel(status) {
      const labels = {
        pending: 'Pending',
        on_time: 'On Time',
        warning: 'Warning',
        breached: 'Breached',
        resolved: 'Resolved',
      }
      return labels[status] || status
    },
    getSLAStatusSeverity(status) {
      const severities = {
        pending: 'info',
        on_time: 'success',
        warning: 'warning',
        breached: 'danger',
        resolved: 'success',
      }
      return severities[status] || 'secondary'
    },
    getElapsedMinutes(startedAt) {
      if (!startedAt) return 0
      const start = new Date(startedAt)
      const now = new Date()
      return Math.floor((now - start) / (1000 * 60))
    },
    getSLAProgress(lead) {
      if (!lead.sla_setting || !lead.sla_started_at || lead.first_response_at) {
        return 0
      }
      const elapsed = this.getElapsedMinutes(lead.sla_started_at)
      const threshold = lead.sla_setting.first_response_threshold
      return Math.min(100, (elapsed / threshold) * 100)
    },
    getSLAProgressClass(status) {
      if (status === 'breached') return 'bg-red-500'
      if (status === 'warning') return 'bg-orange-500'
      return 'bg-blue-500'
    },
    formatDateTime(dateTimeString) {
      if (!dateTimeString) return ''
      const date = new Date(dateTimeString)
      return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
    },
  },
}
</script>
