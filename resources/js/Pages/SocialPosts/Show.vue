<template>
  <div>
    <Head title="Social Post Details" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 space-y-6">
        <Card>
          <template #title>Post Content</template>
          <template #content>
            <div class="space-y-4">
              <div class="flex items-center gap-2 mb-4">
                <Tag :value="post.platform.toUpperCase()" :severity="getPlatformSeverity(post.platform)" />
                <Tag :value="post.status" :severity="getStatusSeverity(post.status)" />
              </div>

              <div class="prose max-w-none">
                <p class="whitespace-pre-wrap text-gray-700">{{ post.content }}</p>
              </div>

              <div v-if="post.media_urls && post.media_urls.length" class="mt-4">
                <h4 class="font-semibold mb-2">Media</h4>
                <div class="grid grid-cols-2 gap-2">
                  <img
                    v-for="(url, index) in post.media_urls"
                    :key="index"
                    :src="url"
                    alt="Post media"
                    class="rounded-lg w-full h-32 object-cover"
                  />
                </div>
              </div>

              <div v-if="post.error_message" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                <h4 class="font-semibold text-red-800 mb-1">Error</h4>
                <p class="text-sm text-red-700">{{ post.error_message }}</p>
              </div>
            </div>
          </template>
        </Card>

        <Card v-if="post.analytics">
          <template #title>Analytics</template>
          <template #content>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div v-if="post.analytics.likes !== undefined" class="text-center">
                <div class="text-2xl font-bold text-primary-600">👍</div>
                <div class="text-sm text-gray-600 mt-1">Likes</div>
                <div class="text-lg font-semibold">{{ post.analytics.likes }}</div>
              </div>
              <div v-if="post.analytics.comments !== undefined" class="text-center">
                <div class="text-2xl font-bold text-primary-600">💬</div>
                <div class="text-sm text-gray-600 mt-1">Comments</div>
                <div class="text-lg font-semibold">{{ post.analytics.comments }}</div>
              </div>
              <div v-if="post.analytics.shares !== undefined" class="text-center">
                <div class="text-2xl font-bold text-primary-600">🔁</div>
                <div class="text-sm text-gray-600 mt-1">Shares</div>
                <div class="text-lg font-semibold">{{ post.analytics.shares }}</div>
              </div>
              <div v-if="post.analytics.views !== undefined" class="text-center">
                <div class="text-2xl font-bold text-primary-600">👁️</div>
                <div class="text-sm text-gray-600 mt-1">Views</div>
                <div class="text-lg font-semibold">{{ post.analytics.views }}</div>
              </div>
            </div>
            <div v-if="post.analytics_synced_at" class="mt-4 text-sm text-gray-500 text-center">
              Last synced: {{ post.analytics_synced_at }}
            </div>
          </template>
        </Card>
      </div>

      <div class="space-y-6">
        <Card>
          <template #title>Actions</template>
          <template #content>
            <div class="space-y-3">
              <Button
                v-if="post.status === 'failed'"
                label="Retry Post"
                icon="pi pi-refresh"
                class="w-full"
                @click="retry"
              />

              <Button
                label="Sync Analytics"
                icon="pi pi-chart-bar"
                class="w-full"
                severity="secondary"
                outlined
                @click="syncAnalytics"
              />

              <Button
                label="Delete Post"
                icon="pi pi-trash"
                class="w-full"
                severity="danger"
                outlined
                @click="deletePost"
              />
            </div>
          </template>
        </Card>

        <Card>
          <template #title>Information</template>
          <template #content>
            <div class="space-y-2 text-sm">
              <div>
                <span class="text-gray-600">Platform:</span>
                <span class="ml-2 font-medium">{{ post.platform.toUpperCase() }}</span>
              </div>
              <div>
                <span class="text-gray-600">Account:</span>
                <span class="ml-2 font-medium">{{ post.social_account?.name || '-' }}</span>
              </div>
              <div>
                <span class="text-gray-600">Content Item:</span>
                <Link v-if="post.content_item" :href="`/content-items/${post.content_item.id}`" class="ml-2 font-medium text-primary-600">
                  {{ post.content_item.title }}
                </Link>
                <span v-else class="ml-2">-</span>
              </div>
              <div>
                <span class="text-gray-600">Scheduled:</span>
                <span class="ml-2 font-medium">{{ post.scheduled_at || '-' }}</span>
              </div>
              <div>
                <span class="text-gray-600">Posted:</span>
                <span class="ml-2 font-medium">{{ post.posted_at || '-' }}</span>
              </div>
              <div v-if="post.platform_post_id">
                <span class="text-gray-600">Platform Post ID:</span>
                <span class="ml-2 font-medium text-xs">{{ post.platform_post_id }}</span>
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
import Button from 'primevue/button'
import Breadcrumb from 'primevue/breadcrumb'

export default {
  components: {
    Head,
    Link,
    Card,
    Tag,
    Button,
    Breadcrumb,
  },
  layout: Layout,
  props: {
    post: Object,
  },
  data() {
    return {
      breadcrumbItems: [
        { label: 'Social Posts', route: '/social-posts' },
        { label: 'Post Details' },
      ],
    }
  },
  methods: {
    getPlatformSeverity(platform) {
      const severities = {
        linkedin: 'info',
        twitter: 'info',
        facebook: 'primary',
        instagram: 'warning',
      }
      return severities[platform] || 'secondary'
    },
    getStatusSeverity(status) {
      const severities = {
        draft: 'secondary',
        scheduled: 'warning',
        posting: 'info',
        published: 'success',
        failed: 'danger',
      }
      return severities[status] || 'secondary'
    },
    retry() {
      router.post(`/social-posts/${this.post.id}/retry`)
    },
    syncAnalytics() {
      router.post(`/social-posts/${this.post.id}/sync-analytics`)
    },
    deletePost() {
      if (confirm('Are you sure you want to delete this post?')) {
        router.delete(`/social-posts/${this.post.id}`)
      }
    },
  },
}
</script>



