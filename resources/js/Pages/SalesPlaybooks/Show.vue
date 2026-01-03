<template>
  <div>
    <Head :title="playbook.name" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main Content -->
      <div class="lg:col-span-2 space-y-6">
        <Card>
          <template #title>
            <div class="flex items-center justify-between">
              <span>{{ playbook.name }}</span>
              <Tag :value="playbook.is_active ? 'Active' : 'Inactive'" :severity="playbook.is_active ? 'success' : 'secondary'" />
            </div>
          </template>
          <template #content>
            <div class="space-y-6">
              <div v-if="playbook.description">
                <h4 class="font-semibold mb-2">Description</h4>
                <p class="text-gray-700">{{ playbook.description }}</p>
              </div>

              <Divider />

              <!-- Matching Criteria -->
              <div>
                <h4 class="font-semibold mb-3">Matching Criteria</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div v-if="playbook.industries && playbook.industries.length > 0">
                    <p class="text-sm text-gray-600 mb-2">Industries:</p>
                    <div class="flex flex-wrap gap-1">
                      <Tag v-for="industry in playbook.industries" :key="industry" :value="industry" severity="info" />
                    </div>
                  </div>
                  <div v-if="playbook.deal_stages && playbook.deal_stages.length > 0">
                    <p class="text-sm text-gray-600 mb-2">Deal Stages:</p>
                    <div class="flex flex-wrap gap-1">
                      <Tag v-for="stage in playbook.deal_stages" :key="stage" :value="stage" severity="secondary" />
                    </div>
                  </div>
                  <div v-if="playbook.pain_points && playbook.pain_points.length > 0" class="md:col-span-2">
                    <p class="text-sm text-gray-600 mb-2">Pain Points:</p>
                    <div class="flex flex-wrap gap-1">
                      <Tag v-for="pain in playbook.pain_points" :key="pain" :value="pain" severity="warning" />
                    </div>
                  </div>
                </div>
              </div>

              <Divider />

              <!-- Talking Points -->
              <div v-if="playbook.talking_points">
                <h4 class="font-semibold mb-2">Talking Points</h4>
                <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                  <p class="text-gray-700 whitespace-pre-wrap">{{ playbook.talking_points }}</p>
                </div>
              </div>

              <!-- Email Template -->
              <div v-if="playbook.email_template_subject || playbook.email_template_body">
                <h4 class="font-semibold mb-2">Email Template</h4>
                <div class="p-4 bg-gray-50 rounded-lg border border-gray-200 space-y-3">
                  <div v-if="playbook.email_template_subject">
                    <p class="text-sm font-medium text-gray-600">Subject:</p>
                    <p class="text-gray-800">{{ playbook.email_template_subject }}</p>
                  </div>
                  <div v-if="playbook.email_template_body">
                    <p class="text-sm font-medium text-gray-600">Body:</p>
                    <p class="text-gray-700 whitespace-pre-wrap">{{ playbook.email_template_body }}</p>
                  </div>
                </div>
              </div>

              <!-- Recommended Documents -->
              <div v-if="playbook.recommended_documents && playbook.recommended_documents.length > 0">
                <h4 class="font-semibold mb-2">Recommended Documents</h4>
                <div class="space-y-2">
                  <div
                    v-for="(doc, index) in playbook.recommended_documents"
                    :key="index"
                    class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg border border-gray-200"
                  >
                    <i class="pi pi-file text-gray-400" />
                    <span>{{ doc }}</span>
                  </div>
                </div>
              </div>

              <!-- Objections Handling -->
              <div v-if="playbook.objections_handling">
                <h4 class="font-semibold mb-2">Objections Handling</h4>
                <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                  <p class="text-gray-700 whitespace-pre-wrap">{{ playbook.objections_handling }}</p>
                </div>
              </div>

              <!-- Next Steps -->
              <div v-if="playbook.next_steps">
                <h4 class="font-semibold mb-2">Next Steps</h4>
                <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                  <p class="text-gray-700 whitespace-pre-wrap">{{ playbook.next_steps }}</p>
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Sidebar -->
      <div class="lg:col-span-1">
        <Card>
          <template #title>Actions</template>
          <template #content>
            <div class="flex flex-col gap-2">
              <Link :href="`/sales-playbooks/${playbook.id}/edit`">
                <Button label="Edit Playbook" icon="pi pi-pencil" class="w-full justify-start" />
              </Link>
              <Button
                label="Delete Playbook"
                icon="pi pi-trash"
                severity="danger"
                outlined
                class="w-full justify-start"
                @click="deletePlaybook"
              />
            </div>
          </template>
        </Card>

        <Card class="mt-6">
          <template #title>Metadata</template>
          <template #content>
            <div class="space-y-2 text-sm">
              <div>
                <span class="text-gray-600">Priority:</span>
                <Badge :value="playbook.priority" class="ml-2" />
              </div>
              <div v-if="playbook.tags && playbook.tags.length > 0">
                <span class="text-gray-600">Tags:</span>
                <div class="flex flex-wrap gap-1 mt-1">
                  <Tag v-for="tag in playbook.tags" :key="tag" :value="tag" severity="secondary" />
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
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
    Divider,
    Breadcrumb,
  },
  layout: Layout,
  props: {
    playbook: Object,
  },
  data() {
    return {
      breadcrumbItems: [
        { label: 'Sales Playbooks', route: '/sales-playbooks' },
        { label: this.playbook.name },
      ],
    }
  },
  methods: {
    deletePlaybook() {
      if (confirm('Are you sure you want to delete this playbook?')) {
        router.delete(`/sales-playbooks/${this.playbook.id}`)
      }
    },
  },
}
</script>

