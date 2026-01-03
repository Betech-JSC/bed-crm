<template>
  <div>
    <Head :title="`Knowledge Base: ${widget?.name || 'Loading...'}`" />
    <div class="mb-6">
      <Breadcrumb :model="breadcrumbItems" />
    </div>

    <Card>
      <template #title>
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-2xl font-bold">Knowledge Base</h2>
            <p class="text-sm text-gray-600 mt-1">
              Upload documents and scripts for the AI to learn from. The chatbot will use this information to answer questions accurately.
            </p>
          </div>
          <Link v-if="widget" :href="`/chat-widgets/${widget.id}/edit`">
            <Button label="Back to Widget" icon="pi pi-arrow-left" severity="secondary" outlined />
          </Link>
        </div>
      </template>
      <template #content>
        <div class="space-y-6">
          <!-- Upload Form -->
          <Card>
            <template #title>Upload Document</template>
            <template #content>
              <form @submit.prevent="uploadDocument" class="space-y-4">
                <div class="flex flex-col">
                  <label class="mb-2 text-sm font-medium">Document Name <span class="text-red-500">*</span></label>
                  <InputText
                    v-model="uploadForm.name"
                    placeholder="e.g., Product Manual, FAQ, Company Policies"
                    :class="{ 'p-invalid': uploadForm.errors.name }"
                  />
                  <small v-if="uploadForm.errors.name" class="p-error">{{ uploadForm.errors.name }}</small>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="flex flex-col">
                    <label class="mb-2 text-sm font-medium">Upload File</label>
                    <input
                      ref="fileInput"
                      type="file"
                      accept=".txt,.pdf,.doc,.docx"
                      class="hidden"
                      @change="onFileSelect"
                    />
                    <div class="flex items-center gap-2">
                      <Button
                        type="button"
                        :label="selectedFile ? 'Change File' : 'Choose File'"
                        icon="pi pi-upload"
                        severity="secondary"
                        outlined
                        @click="triggerFileInput"
                      />
                      <Button
                        v-if="selectedFile"
                        type="button"
                        icon="pi pi-times"
                        severity="danger"
                        text
                        rounded
                        @click="removeFile"
                      />
                    </div>
                    <small v-if="selectedFile" class="text-gray-500 mt-1">
                      {{ selectedFile.name }} ({{ formatFileSize(selectedFile.size) }})
                    </small>
                    <small class="text-gray-500 mt-1">Supported: TXT, PDF, DOC, DOCX (max 10MB)</small>
                  </div>

                  <div class="flex flex-col">
                    <label class="mb-2 text-sm font-medium">Or Paste Text</label>
                    <Textarea
                      v-model="uploadForm.content"
                      rows="4"
                      placeholder="Paste your document content here..."
                      :class="{ 'p-invalid': uploadForm.errors.content }"
                    />
                    <small v-if="uploadForm.errors.content" class="p-error">{{ uploadForm.errors.content }}</small>
                  </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                  <div class="flex items-start gap-3">
                    <i class="pi pi-info-circle text-blue-600 mt-1"></i>
                    <div class="flex-1 text-sm text-blue-700">
                      <p class="font-semibold mb-1">How RAG Works:</p>
                      <ul class="list-disc list-inside space-y-1 ml-2">
                        <li>Documents are automatically split into chunks</li>
                        <li>Each chunk is converted to embeddings (vector representations)</li>
                        <li>When users ask questions, the AI searches for relevant chunks</li>
                        <li>The AI uses this context to provide accurate, document-based answers</li>
                      </ul>
                    </div>
                  </div>
                </div>

                <div class="flex items-center justify-end">
                  <Button
                    label="Upload & Process"
                    icon="pi pi-upload"
                    :loading="uploadForm.processing"
                    type="submit"
                  />
                </div>
              </form>
            </template>
          </Card>

          <!-- Documents List -->
          <div>
            <h3 class="text-lg font-semibold mb-4">Uploaded Documents</h3>
            <DataTable
              :value="documents"
              :paginator="false"
              stripedRows
              responsiveLayout="scroll"
              class="p-datatable-sm"
            >
              <template #empty>
                <div class="py-8 text-center text-gray-500">
                  No documents uploaded yet. Upload your first document to enable RAG (Retrieval-Augmented Generation).
                </div>
              </template>

              <Column field="name" header="Document Name" sortable>
                <template #body="{ data }">
                  <div class="flex items-center gap-2">
                    <i class="pi pi-file text-primary-600"></i>
                    <span class="font-medium">{{ data.name }}</span>
                    <Tag
                      v-if="data.chunks_count > 1"
                      :value="`${data.chunks_count} chunks`"
                      severity="info"
                    />
                  </div>
                </template>
              </Column>

              <Column field="file_type" header="Type">
                <template #body="{ data }">
                  <span class="text-sm text-gray-600 uppercase">{{ data.file_type || 'Text' }}</span>
                </template>
              </Column>

              <Column header="Tokens">
                <template #body="{ data }">
                  <Badge :value="data.total_tokens?.toLocaleString() || '0'" />
                </template>
              </Column>

              <Column field="is_active" header="Status">
                <template #body="{ data }">
                  <Tag
                    :value="data.is_active ? 'Active' : 'Inactive'"
                    :severity="data.is_active ? 'success' : 'secondary'"
                  />
                </template>
              </Column>

              <Column field="created_at" header="Uploaded" sortable>
                <template #body="{ data }">
                  <span class="text-sm text-gray-600">{{ data.created_at }}</span>
                </template>
              </Column>

              <Column>
                <template #body="{ data }">
                  <div class="flex items-center gap-2">
                    <Button
                      :icon="data.is_active ? 'pi pi-eye-slash' : 'pi pi-eye'"
                      :label="data.is_active ? 'Disable' : 'Enable'"
                      :severity="data.is_active ? 'secondary' : 'success'"
                      text
                      rounded
                      size="small"
                      @click="toggleDocument(data.id)"
                    />
                    <Button
                      icon="pi pi-trash"
                      severity="danger"
                      text
                      rounded
                      size="small"
                      @click="confirmDelete(data.id, data.name)"
                    />
                  </div>
                </template>
              </Column>
            </DataTable>
          </div>
        </div>
      </template>
    </Card>

    <!-- Delete Confirmation Dialog -->
    <Dialog
      v-model:visible="showDeleteDialog"
      modal
      header="Confirm Delete"
      :style="{ width: '400px' }"
    >
      <p>Are you sure you want to delete "{{ documentToDelete?.name }}"?</p>
      <p class="text-sm text-gray-600 mt-2">This will remove all chunks of this document from the knowledge base.</p>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="showDeleteDialog = false" />
        <Button label="Delete" severity="danger" @click="deleteDocument" :loading="deleting" />
      </template>
    </Dialog>
  </div>
</template>

<script>
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import Dialog from 'primevue/dialog'
import Breadcrumb from 'primevue/breadcrumb'

export default {
  components: {
    Head,
    Link,
    Card,
    InputText,
    Textarea,
    Button,
    DataTable,
    Column,
    Tag,
    Badge,
    Dialog,
    Breadcrumb,
  },
  layout: Layout,
  props: {
    widget: Object,
    documents: Array,
  },
  data() {
    return {
      uploadForm: useForm({
        name: '',
        content: '',
        file: null,
      }),
      selectedFile: null,
      showDeleteDialog: false,
      documentToDelete: null,
      deleting: false,
    }
  },
  computed: {
    breadcrumbItems() {
      if (!this.widget) {
        return [
          { label: 'Chat Widgets', route: '/chat-widgets' },
          { label: 'Knowledge Base' },
        ]
      }
      return [
        { label: 'Chat Widgets', route: '/chat-widgets' },
        { label: this.widget.name || 'Widget', route: `/chat-widgets/${this.widget.id}/edit` },
        { label: 'Knowledge Base' },
      ]
    },
  },
  methods: {
    triggerFileInput() {
      this.$refs.fileInput.click()
    },
    onFileSelect(event) {
      const file = event.target.files[0]
      if (file) {
        if (file.size > 10485760) { // 10MB
          alert('File size exceeds maximum allowed size (10MB).')
          this.removeFile()
          return
        }
        this.selectedFile = file
        this.uploadForm.file = file
      }
    },
    removeFile() {
      this.selectedFile = null
      this.uploadForm.file = null
      if (this.$refs.fileInput) {
        this.$refs.fileInput.value = ''
      }
    },
    uploadDocument() {
      if (!this.uploadForm.name) {
        alert('Please enter a document name.')
        return
      }

      if (!this.uploadForm.content && !this.uploadForm.file) {
        alert('Please either upload a file or paste text content.')
        return
      }

      if (!this.widget || !this.widget.id) {
        alert('Widget information is not available.')
        return
      }
      this.uploadForm.post(`/chat-widgets/${this.widget.id}/documents`, {
        preserveScroll: true,
        onSuccess: () => {
          this.uploadForm.reset()
          this.removeFile()
        },
      })
    },
    toggleDocument(documentId) {
      const document = this.documents.find(d => d.id === documentId)
      if (!document) return
      if (!this.widget || !this.widget.id) return

      router.post(`/chat-widgets/${this.widget.id}/documents/${documentId}/toggle`, {}, {
        preserveScroll: true,
      })
    },
    confirmDelete(documentId, documentName) {
      this.documentToDelete = { id: documentId, name: documentName }
      this.showDeleteDialog = true
    },
    deleteDocument() {
      if (!this.documentToDelete) return
      if (!this.widget || !this.widget.id) return

      this.deleting = true
      router.delete(`/chat-widgets/${this.widget.id}/documents/${this.documentToDelete.id}`, {
        preserveScroll: true,
        onSuccess: () => {
          this.showDeleteDialog = false
          this.documentToDelete = null
          this.deleting = false
        },
        onError: () => {
          this.deleting = false
        },
      })
    },
    formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes'
      const k = 1024
      const sizes = ['Bytes', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
    },
  },
}
</script>

