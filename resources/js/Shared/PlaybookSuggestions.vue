<template>
  <Card>
    <template #title>
      <div class="flex items-center justify-between">
        <span>Sales Playbook Suggestions</span>
        <Button
          icon="pi pi-refresh"
          text
          rounded
          size="small"
          @click="refreshSuggestions"
        />
      </div>
    </template>
    <template #content>
      <div v-if="playbooks && playbooks.length > 0" class="space-y-4">
        <div
          v-for="playbook in playbooks"
          :key="playbook.id"
          class="p-4 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg border border-blue-200"
        >
          <div class="flex items-start justify-between mb-3">
            <div>
              <h4 class="font-semibold text-gray-800">{{ playbook.name }}</h4>
              <p v-if="playbook.description" class="text-sm text-gray-600 mt-1">{{ playbook.description }}</p>
            </div>
            <Button
              icon="pi pi-chevron-down"
              text
              rounded
              size="small"
              @click="togglePlaybook(playbook.id)"
            />
          </div>

          <div v-if="expandedPlaybooks.includes(playbook.id)" class="mt-4 space-y-4">
            <!-- Talking Points -->
            <div v-if="playbook.talking_points">
              <h5 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                <i class="pi pi-comments text-primary-500" />
                Talking Points
              </h5>
              <div class="p-3 bg-white rounded border border-gray-200">
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ playbook.talking_points }}</p>
              </div>
            </div>

            <!-- Email Template -->
            <div v-if="playbook.email_template_subject || playbook.email_template_body">
              <h5 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                <i class="pi pi-envelope text-primary-500" />
                Email Template
              </h5>
              <div class="p-3 bg-white rounded border border-gray-200 space-y-2">
                <div v-if="playbook.email_template_subject">
                  <p class="text-xs text-gray-500">Subject:</p>
                  <p class="text-sm font-medium">{{ playbook.email_template_subject }}</p>
                </div>
                <div v-if="playbook.email_template_body">
                  <p class="text-xs text-gray-500">Body:</p>
                  <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ playbook.email_template_body }}</p>
                </div>
                <Button
                  label="Copy Email"
                  icon="pi pi-copy"
                  size="small"
                  severity="secondary"
                  outlined
                  @click="copyEmail(playbook)"
                />
              </div>
            </div>

            <!-- Recommended Documents -->
            <div v-if="playbook.recommended_documents && playbook.recommended_documents.length > 0">
              <h5 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                <i class="pi pi-file text-primary-500" />
                Recommended Documents
              </h5>
              <div class="space-y-2">
                <div
                  v-for="(doc, index) in playbook.recommended_documents"
                  :key="index"
                  class="flex items-center gap-2 p-2 bg-white rounded border border-gray-200"
                >
                  <i class="pi pi-file text-gray-400" />
                  <span class="text-sm">{{ doc }}</span>
                </div>
              </div>
            </div>

            <!-- Objections Handling -->
            <div v-if="playbook.objections_handling">
              <h5 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                <i class="pi pi-exclamation-triangle text-primary-500" />
                Objections Handling
              </h5>
              <div class="p-3 bg-white rounded border border-gray-200">
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ playbook.objections_handling }}</p>
              </div>
            </div>

            <!-- Next Steps -->
            <div v-if="playbook.next_steps">
              <h5 class="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                <i class="pi pi-arrow-right text-primary-500" />
                Next Steps
              </h5>
              <div class="p-3 bg-white rounded border border-gray-200">
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ playbook.next_steps }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="py-8 text-center text-gray-400 text-sm">
        No playbook suggestions available. Create playbooks to get personalized recommendations.
      </div>
    </template>
  </Card>
</template>

<script>
import { router } from '@inertiajs/vue3'
import Card from 'primevue/card'
import Button from 'primevue/button'

export default {
  components: {
    Card,
    Button,
  },
  props: {
    playbooks: {
      type: Array,
      default: () => [],
    },
    subjectType: {
      type: String,
      required: true, // 'deal' or 'lead'
    },
    subjectId: {
      type: Number,
      required: true,
    },
  },
  data() {
    return {
      expandedPlaybooks: [],
    }
  },
  methods: {
    togglePlaybook(playbookId) {
      const index = this.expandedPlaybooks.indexOf(playbookId)
      if (index > -1) {
        this.expandedPlaybooks.splice(index, 1)
      } else {
        this.expandedPlaybooks.push(playbookId)
      }
    },
    refreshSuggestions() {
      const route = this.subjectType === 'deal' ? 'deals' : 'leads'
      router.reload({ only: ['playbookSuggestions'] })
    },
    copyEmail(playbook) {
      const email = {
        subject: playbook.email_template_subject || '',
        body: playbook.email_template_body || '',
      }
      const emailText = `Subject: ${email.subject}\n\n${email.body}`
      navigator.clipboard.writeText(emailText).then(() => {
        // Show toast notification (you can use PrimeVue Toast here)
        alert('Email template copied to clipboard!')
      })
    },
  },
}
</script>

