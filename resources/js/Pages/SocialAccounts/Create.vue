<template>
  <div>
    <Head title="Connect Social Account" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <Card>
      <template #title>Connect Social Media Account</template>
      <template #content>
        <div class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="platform in platforms"
              :key="platform.value"
              class="border rounded-lg p-4 hover:border-primary-500 cursor-pointer transition-colors"
              @click="connectPlatform(platform.value)"
            >
              <div class="flex items-center gap-3">
                <div class="text-2xl font-bold">{{ platform.label }}</div>
                <div class="ml-auto">
                  <Button icon="pi pi-arrow-right" text rounded />
                </div>
              </div>
              <p class="text-sm text-gray-600 mt-2">Click to connect your {{ platform.label }} account</p>
            </div>
          </div>

          <div class="p-4 bg-blue-50 rounded-lg">
            <h4 class="font-semibold mb-2">How it works:</h4>
            <ol class="list-decimal list-inside space-y-1 text-sm text-gray-700">
              <li>Click on the platform you want to connect</li>
              <li>You'll be redirected to authorize the application</li>
              <li>After authorization, you'll be redirected back</li>
              <li>Your account will be connected and ready to use</li>
            </ol>
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script>
import { Head, router } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Breadcrumb from 'primevue/breadcrumb'

export default {
  components: {
    Head,
    Card,
    Button,
    Breadcrumb,
  },
  layout: Layout,
  props: {
    platforms: Object,
  },
  data() {
    return {
      breadcrumbItems: [
        { label: 'Social Accounts', route: '/social-accounts' },
        { label: 'Connect' },
      ],
    }
  },
  methods: {
    connectPlatform(platform) {
      // In production, this would redirect to OAuth flow
      // For now, we'll show a message
      alert(`To connect ${platform}, you need to implement OAuth flow. This would redirect to the platform's authorization page.`)
      
      // Example OAuth flow (commented out):
      // const redirectUri = encodeURIComponent(window.location.origin + '/social-accounts/callback')
      // const authUrl = this.getAuthUrl(platform, redirectUri)
      // window.location.href = authUrl
    },
    getAuthUrl(platform, redirectUri) {
      const urls = {
        linkedin: `https://www.linkedin.com/oauth/v2/authorization?response_type=code&client_id=${process.env.LINKEDIN_CLIENT_ID}&redirect_uri=${redirectUri}&state=linkedin&scope=r_liteprofile%20r_emailaddress%20w_member_social`,
        twitter: `https://twitter.com/i/oauth2/authorize?response_type=code&client_id=${process.env.TWITTER_CLIENT_ID}&redirect_uri=${redirectUri}&scope=tweet.read%20tweet.write%20users.read&state=twitter&code_challenge=challenge&code_challenge_method=plain`,
        facebook: `https://www.facebook.com/v18.0/dialog/oauth?client_id=${process.env.FACEBOOK_CLIENT_ID}&redirect_uri=${redirectUri}&state=facebook&scope=pages_manage_posts,pages_read_engagement`,
        instagram: `https://api.instagram.com/oauth/authorize?client_id=${process.env.INSTAGRAM_CLIENT_ID}&redirect_uri=${redirectUri}&scope=user_profile,user_media&response_type=code&state=instagram`,
      }
      return urls[platform] || '#'
    },
  },
}
</script>

