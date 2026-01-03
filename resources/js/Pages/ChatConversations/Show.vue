<template>
  <div>
    <Head :title="`Conversation: ${conversation.visitor_name || 'Anonymous'}`" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 space-y-6">
        <Card>
          <template #title>Conversation</template>
          <template #content>
            <div class="space-y-4 max-h-[600px] overflow-y-auto">
              <div
                v-for="message in conversation.messages"
                :key="message.id"
                :class="[
                  'p-4 rounded-lg',
                  message.role === 'user'
                    ? 'bg-gray-100 ml-auto max-w-[80%]'
                    : 'bg-primary-50 max-w-[80%]'
                ]"
              >
                <div class="flex items-start justify-between mb-2">
                  <span class="font-medium text-sm">
                    {{ message.role === 'user' ? (conversation.visitor_name || 'Visitor') : 'AI Assistant' }}
                  </span>
                  <span class="text-xs text-gray-500">{{ message.created_at }}</span>
                </div>
                <div class="text-sm whitespace-pre-wrap">{{ message.content }}</div>
                <div v-if="message.tokens_used" class="mt-2 text-xs text-gray-400">
                  Tokens: {{ message.tokens_used }} | Cost: ${{ message.cost?.toFixed(6) || '0.000000' }}
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <div class="space-y-6">
        <Card>
          <template #title>Visitor Information</template>
          <template #content>
            <div class="space-y-3">
              <div>
                <label class="text-sm font-medium text-gray-600">Name</label>
                <p class="mt-1">{{ conversation.visitor_name || '-' }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-600">Email</label>
                <p class="mt-1">{{ conversation.visitor_email || '-' }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-600">Phone</label>
                <p class="mt-1">{{ conversation.visitor_phone || '-' }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-600">IP Address</label>
                <p class="mt-1 text-xs">{{ conversation.visitor_ip || '-' }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-600">Page URL</label>
                <p class="mt-1 text-xs break-all">{{ conversation.page_url || '-' }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-600">Referrer</label>
                <p class="mt-1 text-xs break-all">{{ conversation.referrer_url || '-' }}</p>
              </div>
            </div>
          </template>
        </Card>

        <Card>
          <template #title>CRM Links</template>
          <template #content>
            <div class="space-y-3">
              <div v-if="conversation.lead">
                <label class="text-sm font-medium text-gray-600">Lead</label>
                <div class="mt-1">
                  <Link
                    :href="conversation.lead.url"
                    class="text-primary-600 hover:text-primary-800"
                  >
                    {{ conversation.lead.name }} →
                  </Link>
                </div>
              </div>
              <div v-if="conversation.contact">
                <label class="text-sm font-medium text-gray-600">Contact</label>
                <div class="mt-1">
                  <Link
                    :href="`/contacts/${conversation.contact.id}/edit`"
                    class="text-primary-600 hover:text-primary-800"
                  >
                    {{ conversation.contact.name }} →
                  </Link>
                </div>
              </div>
              <div v-if="!conversation.lead && !conversation.contact">
                <p class="text-sm text-gray-500">No CRM links</p>
              </div>
            </div>
          </template>
        </Card>

        <Card>
          <template #title>Statistics</template>
          <template #content>
            <div class="space-y-3">
              <div>
                <label class="text-sm font-medium text-gray-600">Total Messages</label>
                <p class="mt-1 text-lg font-bold">{{ conversation.message_count }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-600">Status</label>
                <p class="mt-1">
                  <Tag
                    :value="conversation.status"
                    :severity="conversation.status === 'active' ? 'success' : 'secondary'"
                  />
                </p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-600">Started</label>
                <p class="mt-1 text-sm">{{ conversation.created_at }}</p>
              </div>
              <div>
                <label class="text-sm font-medium text-gray-600">Last Message</label>
                <p class="mt-1 text-sm">{{ conversation.last_message_at || '-' }}</p>
              </div>
            </div>
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
import Tag from 'primevue/tag'
import Breadcrumb from 'primevue/breadcrumb'

export default {
  components: {
    Head,
    Link,
    Card,
    Tag,
    Breadcrumb,
  },
  layout: Layout,
  props: {
    conversation: Object,
  },
  data() {
    return {
      breadcrumbItems: [
        { label: 'Chat Conversations', route: '/chat-conversations' },
        { label: 'View' },
      ],
    }
  },
}
</script>

