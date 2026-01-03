<template>
  <div>
    <Head :title="proposal.title" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main Content -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Proposal Details -->
        <Card>
          <template #title>
            <div class="flex items-center justify-between">
              <span>{{ proposal.title }}</span>
              <Tag :value="proposal.status_label" :severity="proposal.status_severity" />
            </div>
          </template>
          <template #content>
            <div class="space-y-4">
              <div v-if="proposal.description" class="prose max-w-none">
                <p class="text-gray-700 whitespace-pre-wrap">{{ proposal.description }}</p>
              </div>

              <Divider />

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-sm text-gray-600">Amount</p>
                  <p class="text-lg font-semibold">
                    {{ proposal.amount ? formatCurrency(proposal.amount) : '-' }}
                  </p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Valid Until</p>
                  <p class="text-lg font-semibold">
                    {{ proposal.valid_until ? formatDate(proposal.valid_until) : 'No expiry' }}
                  </p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Version</p>
                  <p class="text-lg font-semibold">v{{ proposal.version }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">Created</p>
                  <p class="text-lg font-semibold">{{ formatDateTime(proposal.created_at) }}</p>
                </div>
              </div>

              <Divider />

              <!-- File Download -->
              <div v-if="proposal.file_name" class="p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <i class="pi pi-file text-2xl text-gray-400" />
                    <div>
                      <p class="font-medium">{{ proposal.file_name }}</p>
                      <p class="text-xs text-gray-500">{{ formatFileSize(proposal.file_size) }}</p>
                    </div>
                  </div>
                  <Link :href="`/proposals/${proposal.id}/download`">
                    <Button label="Download" icon="pi pi-download" outlined />
                  </Link>
                </div>
              </div>

              <!-- Status Tracking -->
              <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                <h4 class="font-semibold mb-3">Status Tracking</h4>
                <div class="space-y-2 text-sm">
                  <div v-if="proposal.sent_at" class="flex items-center gap-2">
                    <i class="pi pi-send text-blue-500" />
                    <span>Sent: {{ formatDateTime(proposal.sent_at) }}</span>
                    <span v-if="proposal.sender" class="text-gray-500">by {{ proposal.sender.name }}</span>
                  </div>
                  <div v-if="proposal.viewed_at" class="flex items-center gap-2">
                    <i class="pi pi-eye text-purple-500" />
                    <span>Viewed: {{ formatDateTime(proposal.viewed_at) }}</span>
                    <span class="text-gray-500">({{ proposal.view_count }} times)</span>
                  </div>
                  <div v-if="proposal.accepted_at" class="flex items-center gap-2">
                    <i class="pi pi-check-circle text-green-500" />
                    <span>Accepted: {{ formatDateTime(proposal.accepted_at) }}</span>
                  </div>
                  <div v-if="proposal.rejected_at" class="flex items-center gap-2">
                    <i class="pi pi-times-circle text-red-500" />
                    <span>Rejected: {{ formatDateTime(proposal.rejected_at) }}</span>
                  </div>
                  <div v-if="proposal.rejection_reason" class="mt-2 p-2 bg-red-50 rounded">
                    <p class="text-xs font-medium text-red-700">Rejection Reason:</p>
                    <p class="text-xs text-red-600">{{ proposal.rejection_reason }}</p>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <!-- Version History -->
        <Card v-if="versions.length > 1">
          <template #title>Version History</template>
          <template #content>
            <div class="space-y-2">
              <div
                v-for="version in versions"
                :key="version.id"
                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
              >
                <div class="flex items-center gap-3">
                  <Badge :value="`v${version.version}`" />
                  <span class="text-sm">{{ formatDateTime(version.created_at) }}</span>
                  <Tag :value="getStatusLabel(version.status)" :severity="getStatusSeverity(version.status)" />
                </div>
                <Link :href="`/proposals/${version.id}`">
                  <Button icon="pi pi-arrow-right" text rounded size="small" />
                </Link>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Sidebar -->
      <div class="lg:col-span-1 space-y-6">
        <!-- Actions -->
        <Card>
          <template #title>Actions</template>
          <template #content>
            <div class="flex flex-col gap-2">
              <Button
                v-if="proposal.can_be_edited"
                label="Edit Proposal"
                icon="pi pi-pencil"
                class="w-full justify-start"
                @click="edit"
              />
              <Button
                v-if="proposal.can_be_sent"
                label="Send Proposal"
                icon="pi pi-send"
                severity="info"
                class="w-full justify-start"
                @click="sendProposal"
              />
              <Button
                label="Create New Version"
                icon="pi pi-copy"
                severity="secondary"
                outlined
                class="w-full justify-start"
                @click="createVersion"
              />
              <Button
                v-if="proposal.can_be_accepted"
                label="Mark as Accepted"
                icon="pi pi-check"
                severity="success"
                class="w-full justify-start"
                @click="acceptProposal"
              />
              <Button
                v-if="proposal.can_be_rejected"
                label="Mark as Rejected"
                icon="pi pi-times"
                severity="danger"
                outlined
                class="w-full justify-start"
                @click="showRejectDialog = true"
              />
              <Divider />
              <Button
                label="Delete Proposal"
                icon="pi pi-trash"
                severity="danger"
                outlined
                class="w-full justify-start"
                @click="deleteProposal"
              />
            </div>
          </template>
        </Card>

        <!-- Deal Link -->
        <Card v-if="proposal.deal">
          <template #title>Linked Deal</template>
          <template #content>
            <Link :href="`/deals/${proposal.deal.id}/edit`" class="text-primary-600 hover:text-primary-800">
              {{ proposal.deal.title }}
            </Link>
          </template>
        </Card>

        <!-- Creator Info -->
        <Card>
          <template #title>Created By</template>
          <template #content>
            <div class="text-sm">
              <p class="font-medium">{{ proposal.creator?.name || 'Unknown' }}</p>
              <p class="text-gray-500">{{ formatDateTime(proposal.created_at) }}</p>
            </div>
          </template>
        </Card>
      </div>
    </div>

    <!-- Reject Dialog -->
    <Dialog v-model:visible="showRejectDialog" modal header="Reject Proposal" :style="{ width: '500px' }">
      <form @submit.prevent="rejectProposal" class="space-y-4">
        <div class="flex flex-col">
          <label class="mb-2 text-sm font-medium">Rejection Reason (Optional)</label>
          <Textarea v-model="rejectionReason" rows="4" placeholder="Enter reason for rejection..." />
        </div>
        <div class="flex justify-end gap-2">
          <Button label="Cancel" severity="secondary" outlined @click="showRejectDialog = false" />
          <Button label="Reject Proposal" severity="danger" type="submit" />
        </div>
      </form>
    </Dialog>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Divider from 'primevue/divider'
import Breadcrumb from 'primevue/breadcrumb'

export default {
  components: {
    Head,
    Link,
    Card,
    Tag,
    Badge,
    Button,
    Dialog,
    Textarea,
    Divider,
    Breadcrumb,
  },
  layout: Layout,
  props: {
    proposal: Object,
    versions: Array,
  },
  data() {
    return {
      showRejectDialog: false,
      rejectionReason: '',
      breadcrumbItems: [
        { label: 'Proposals', route: '/proposals' },
        { label: this.proposal.title },
      ],
    }
  },
  methods: {
    edit() {
      router.visit(`/proposals/${this.proposal.id}/edit`)
    },
    sendProposal() {
      router.post(`/proposals/${this.proposal.id}/send`, {}, {
        preserveScroll: true,
      })
    },
    createVersion() {
      router.post(`/proposals/${this.proposal.id}/version`, {}, {
        preserveScroll: true,
      })
    },
    acceptProposal() {
      router.post(`/proposals/${this.proposal.id}/accept`, {}, {
        preserveScroll: true,
      })
    },
    rejectProposal() {
      router.post(`/proposals/${this.proposal.id}/reject`, {
        rejection_reason: this.rejectionReason,
      }, {
        preserveScroll: true,
        onSuccess: () => {
          this.showRejectDialog = false
          this.rejectionReason = ''
        },
      })
    },
    deleteProposal() {
      if (confirm('Are you sure you want to delete this proposal?')) {
        router.delete(`/proposals/${this.proposal.id}`)
      }
    },
    formatCurrency(value) {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
      }).format(value)
    },
    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
      })
    },
    formatDateTime(dateString) {
      return new Date(dateString).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
    },
    formatFileSize(bytes) {
      if (!bytes) return '0 Bytes'
      const k = 1024
      const sizes = ['Bytes', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
    },
    getStatusLabel(status) {
      const labels = {
        draft: 'Draft',
        sent: 'Sent',
        viewed: 'Viewed',
        accepted: 'Accepted',
        rejected: 'Rejected',
      }
      return labels[status] || status
    },
    getStatusSeverity(status) {
      const severities = {
        draft: 'secondary',
        sent: 'info',
        viewed: 'warning',
        accepted: 'success',
        rejected: 'danger',
      }
      return severities[status] || 'secondary'
    },
  },
}
</script>

