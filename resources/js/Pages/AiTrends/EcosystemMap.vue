<template>
  <div>
    <Head title="AI Ecosystem Map" />

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <i class="pi pi-sitemap" style="color: #6366f1; margin-right: 0.5rem;" />
          AI Ecosystem Map
        </h1>
        <p class="page-subtitle">
          Toàn cảnh 13 layer của hệ sinh thái AI — {{ totalTools }} công cụ nổi bật đáng theo dõi
        </p>
      </div>
      <div class="header-actions">
        <Link href="/ai-trends">
          <Button label="Trends Feed" icon="pi pi-bolt" severity="secondary" text size="small" />
        </Link>
      </div>
    </div>

    <!-- Criteria Banner -->
    <div class="criteria-banner">
      <div class="criteria-title">
        <i class="pi pi-verified" />
        <span>Tiêu chí chọn lọc</span>
      </div>
      <div class="criteria-list">
        <span class="criteria-chip"><i class="pi pi-star-fill" /> GitHub Stars > 5K</span>
        <span class="criteria-chip"><i class="pi pi-code" /> Hệ sinh thái AI Developer</span>
        <span class="criteria-chip"><i class="pi pi-book" /> Có docs & cộng đồng</span>
        <span class="criteria-chip"><i class="pi pi-sparkles" /> Có nét riêng biệt</span>
        <span class="criteria-chip"><i class="pi pi-th-large" /> Thuộc layer cụ thể</span>
      </div>
    </div>

    <!-- Search & Filter -->
    <div class="eco-filter-bar">
      <div class="search-wrapper">
        <i class="pi pi-search search-icon" />
        <input
          v-model="searchQuery"
          class="form-control search-input"
          placeholder="Tìm kiếm công cụ, nền tảng..."
        />
      </div>
      <div class="view-toggle">
        <button class="view-btn" :class="{ active: viewMode === 'grid' }" @click="viewMode = 'grid'">
          <i class="pi pi-th-large" />
        </button>
        <button class="view-btn" :class="{ active: viewMode === 'list' }" @click="viewMode = 'list'">
          <i class="pi pi-list" />
        </button>
      </div>
    </div>

    <!-- Layers -->
    <div class="layers-container">
      <div
        v-for="(layer, index) in filteredLayers"
        :key="layer.id"
        class="layer-section"
        :style="{ '--layer-color': layer.color, '--layer-bg': layer.bgColor }"
      >
        <!-- Layer Header -->
        <div class="layer-header" @click="toggleLayer(layer.id)">
          <div class="layer-left">
            <div class="layer-number">{{ layer.order }}</div>
            <div class="layer-icon-wrapper" :style="{ background: layer.color }">
              <i :class="layer.icon" />
            </div>
            <div class="layer-info">
              <h2 class="layer-title">{{ layer.name }}</h2>
              <p class="layer-desc">{{ layer.description }}</p>
            </div>
          </div>
          <div class="layer-right">
            <span class="layer-count">{{ layer.tools.length }} tools</span>
            <i class="pi" :class="expandedLayers.includes(layer.id) ? 'pi-chevron-up' : 'pi-chevron-down'" />
          </div>
        </div>

        <!-- Layer Tools -->
        <transition name="slide">
          <div v-if="expandedLayers.includes(layer.id)" class="layer-body">
            <div :class="viewMode === 'grid' ? 'tools-grid' : 'tools-list'">
              <div
                v-for="tool in layer.tools"
                :key="tool.name"
                class="tool-card"
                :class="{ 'tool-card--list': viewMode === 'list' }"
                @click="openTool(tool)"
              >
                <div class="tool-logo-wrapper">
                  <span class="tool-logo-text">{{ tool.name.substring(0, 2).toUpperCase() }}</span>
                </div>
                <div class="tool-content">
                  <div class="tool-head">
                    <h3 class="tool-name">{{ tool.name }}</h3>
                    <a v-if="tool.url" :href="tool.url" target="_blank" class="tool-link" @click.stop>
                      <i class="pi pi-external-link" />
                    </a>
                  </div>
                  <p class="tool-desc">{{ tool.description }}</p>
                  <div class="tool-meta">
                    <span v-if="tool.stars" class="tool-stars">
                      <i class="pi pi-star-fill" /> {{ formatStars(tool.stars) }}
                    </span>
                    <span v-if="tool.license" class="tool-license">{{ tool.license }}</span>
                    <span v-for="tag in (tool.tags || []).slice(0, 3)" :key="tag" class="tool-tag">{{ tag }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </transition>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="eco-footer">
      <div class="eco-stat" v-for="stat in summaryStats" :key="stat.label">
        <span class="eco-stat-value">{{ stat.value }}</span>
        <span class="eco-stat-label">{{ stat.label }}</span>
      </div>
    </div>
  </div>
</template>

<script>
import { Head, Link } from '@inertiajs/vue3'
import Layout from '@/Shared/Layout.vue'
import Button from 'primevue/button'

export default {
  components: { Head, Link, Button },
  layout: Layout,
  data() {
    return {
      searchQuery: '',
      viewMode: 'grid',
      expandedLayers: ['foundation-models'],
      layers: [
        {
          id: 'foundation-models',
          order: 1,
          name: 'Foundation Models',
          description: 'Các mô hình nền tảng lớn — LLM, VLM, multimodal',
          icon: 'pi pi-box',
          color: '#6366f1',
          bgColor: '#eef2ff',
          tools: [
            { name: 'LLaMA', description: 'Meta\'s open-source LLM family (LLaMA 2/3), đa kích thước, dẫn đầu open-weight', stars: 70000, url: 'https://github.com/meta-llama/llama', license: 'Custom', tags: ['LLM', 'Meta', 'Open-weight'] },
            { name: 'Mistral', description: 'LLM hiệu suất cao từ Mistral AI, nhỏ gọn nhưng cực mạnh', stars: 32000, url: 'https://github.com/mistralai/mistral-src', license: 'Apache 2.0', tags: ['LLM', 'Efficient', 'EU'] },
            { name: 'Qwen', description: 'Alibaba\'s multilingual LLM series, mạnh tiếng Trung & code', stars: 20000, url: 'https://github.com/QwenLM/Qwen', license: 'Custom', tags: ['LLM', 'Multilingual', 'Alibaba'] },
            { name: 'Gemma', description: 'Google DeepMind\'s lightweight open models, tối ưu on-device', stars: 15000, url: 'https://github.com/google-deepmind/gemma', license: 'Custom', tags: ['LLM', 'Google', 'Lightweight'] },
            { name: 'Stable Diffusion', description: 'Text-to-image diffusion model, nền tảng cho hệ sinh thái GenAI hình ảnh', stars: 68000, url: 'https://github.com/CompVis/stable-diffusion', license: 'MIT', tags: ['Image', 'Diffusion', 'Open'] },
            { name: 'Whisper', description: 'OpenAI\'s speech-to-text model, đa ngôn ngữ, accuracy cao', stars: 72000, url: 'https://github.com/openai/whisper', license: 'MIT', tags: ['Speech', 'STT', 'OpenAI'] },
          ]
        },
        {
          id: 'model-serving',
          order: 2,
          name: 'Model Serving & Inference',
          description: 'Triển khai, serve và tối ưu inference cho AI models',
          icon: 'pi pi-server',
          color: '#0ea5e9',
          bgColor: '#f0f9ff',
          tools: [
            { name: 'vLLM', description: 'Inference engine tốc độ cao cho LLM, PagedAttention, throughput vượt trội', stars: 42000, url: 'https://github.com/vllm-project/vllm', license: 'Apache 2.0', tags: ['Inference', 'Fast', 'Production'] },
            { name: 'Ollama', description: 'Chạy LLM local cực đơn giản, một lệnh duy nhất', stars: 110000, url: 'https://github.com/ollama/ollama', license: 'MIT', tags: ['Local', 'Easy', 'Desktop'] },
            { name: 'TGI', description: 'Hugging Face\'s Text Generation Inference, production-grade', stars: 10000, url: 'https://github.com/huggingface/text-generation-inference', license: 'Apache 2.0', tags: ['HuggingFace', 'Production', 'gRPC'] },
            { name: 'llama.cpp', description: 'Chạy LLM trên CPU thuần, quantization GGUF, siêu nhẹ', stars: 75000, url: 'https://github.com/ggerganov/llama.cpp', license: 'MIT', tags: ['CPU', 'GGUF', 'C++'] },
            { name: 'TensorRT-LLM', description: 'NVIDIA\'s optimized inference cho GPU, tận dụng tối đa CUDA', stars: 12000, url: 'https://github.com/NVIDIA/TensorRT-LLM', license: 'Apache 2.0', tags: ['NVIDIA', 'GPU', 'Optimized'] },
          ]
        },
        {
          id: 'orchestration',
          order: 3,
          name: 'Orchestration & Frameworks',
          description: 'Framework xây dựng ứng dụng AI — chain, pipeline, RAG',
          icon: 'pi pi-sitemap',
          color: '#8b5cf6',
          bgColor: '#f5f3ff',
          tools: [
            { name: 'LangChain', description: 'Framework phổ biến nhất để build LLM apps, chains & agents', stars: 100000, url: 'https://github.com/langchain-ai/langchain', license: 'MIT', tags: ['Framework', 'Chains', 'Popular'] },
            { name: 'LlamaIndex', description: 'Data framework cho LLM — kết nối dữ liệu riêng với AI', stars: 38000, url: 'https://github.com/run-llama/llama_index', license: 'MIT', tags: ['RAG', 'Data', 'Index'] },
            { name: 'Haystack', description: 'Framework end-to-end cho NLP/LLM pipelines by deepset', stars: 18000, url: 'https://github.com/deepset-ai/haystack', license: 'Apache 2.0', tags: ['Pipeline', 'NLP', 'Search'] },
            { name: 'Semantic Kernel', description: 'Microsoft\'s SDK tích hợp AI vào apps, hỗ trợ .NET/Python/Java', stars: 22000, url: 'https://github.com/microsoft/semantic-kernel', license: 'MIT', tags: ['Microsoft', 'SDK', 'Enterprise'] },
            { name: 'DSPy', description: 'Programming (not prompting) LMs — tự động tối ưu prompt', stars: 20000, url: 'https://github.com/stanfordnlp/dspy', license: 'MIT', tags: ['Stanford', 'Prompting', 'Optimize'] },
          ]
        },
        {
          id: 'agents',
          order: 4,
          name: 'AI Agents & Autonomous Systems',
          description: 'Hệ thống AI tự chủ, multi-agent, tool-use',
          icon: 'pi pi-android',
          color: '#f43f5e',
          bgColor: '#fff1f2',
          tools: [
            { name: 'AutoGPT', description: 'Autonomous AI agent tiên phong, tự lên kế hoạch & thực thi', stars: 170000, url: 'https://github.com/Significant-Gravitas/AutoGPT', license: 'MIT', tags: ['Autonomous', 'Pioneer', 'GPT'] },
            { name: 'CrewAI', description: 'Framework multi-agent collaboration, role-based AI teams', stars: 25000, url: 'https://github.com/crewAIInc/crewAI', license: 'MIT', tags: ['Multi-Agent', 'Roles', 'Teams'] },
            { name: 'AutoGen', description: 'Microsoft\'s multi-agent conversation framework', stars: 38000, url: 'https://github.com/microsoft/autogen', license: 'MIT', tags: ['Microsoft', 'Conversation', 'Multi-Agent'] },
            { name: 'OpenDevin', description: 'Open-source AI software engineer, code autonomously', stars: 40000, url: 'https://github.com/OpenDevin/OpenDevin', license: 'MIT', tags: ['Coding', 'SWE', 'Autonomous'] },
            { name: 'MetaGPT', description: 'Multi-agent framework mô phỏng software company', stars: 46000, url: 'https://github.com/geekan/MetaGPT', license: 'MIT', tags: ['SWE', 'Company', 'Agents'] },
          ]
        },
        {
          id: 'vector-db',
          order: 5,
          name: 'Vector Databases & Retrieval',
          description: 'Cơ sở dữ liệu vector, embedding search, RAG storage',
          icon: 'pi pi-database',
          color: '#10b981',
          bgColor: '#ecfdf5',
          tools: [
            { name: 'ChromaDB', description: 'AI-native embedding database, đơn giản, developer-friendly', stars: 16000, url: 'https://github.com/chroma-core/chroma', license: 'Apache 2.0', tags: ['Embedding', 'Simple', 'Python'] },
            { name: 'Milvus', description: 'Vector database scale lớn, production-grade by Zilliz', stars: 32000, url: 'https://github.com/milvus-io/milvus', license: 'Apache 2.0', tags: ['Scale', 'Production', 'Cloud'] },
            { name: 'Weaviate', description: 'AI-native vector DB với built-in ML modules', stars: 12000, url: 'https://github.com/weaviate/weaviate', license: 'BSD-3', tags: ['GraphQL', 'Modules', 'Hybrid'] },
            { name: 'Qdrant', description: 'Vector search engine viết bằng Rust, hiệu suất cao', stars: 22000, url: 'https://github.com/qdrant/qdrant', license: 'Apache 2.0', tags: ['Rust', 'Fast', 'Filtering'] },
            { name: 'Pinecone', description: 'Managed vector DB, serverless, enterprise-ready (SaaS)', stars: 0, url: 'https://www.pinecone.io', license: 'SaaS', tags: ['Managed', 'Serverless', 'Enterprise'] },
          ]
        },
        {
          id: 'fine-tuning',
          order: 6,
          name: 'Fine-tuning & Training',
          description: 'Công cụ fine-tune, RLHF, PEFT, training frameworks',
          icon: 'pi pi-sliders-h',
          color: '#f59e0b',
          bgColor: '#fffbeb',
          tools: [
            { name: 'Unsloth', description: 'Fine-tune LLM nhanh gấp 2x, tiết kiệm 80% VRAM', stars: 22000, url: 'https://github.com/unslothai/unsloth', license: 'Apache 2.0', tags: ['Fast', 'Efficient', 'LoRA'] },
            { name: 'Axolotl', description: 'Công cụ fine-tune đa phương pháp — LoRA, QLoRA, full', stars: 8000, url: 'https://github.com/OpenAccess-AI-Collective/axolotl', license: 'Apache 2.0', tags: ['LoRA', 'QLoRA', 'Flexible'] },
            { name: 'PEFT', description: 'HuggingFace\'s Parameter-Efficient Fine-Tuning library', stars: 17000, url: 'https://github.com/huggingface/peft', license: 'Apache 2.0', tags: ['HuggingFace', 'LoRA', 'Adapter'] },
            { name: 'TRL', description: 'Transformer Reinforcement Learning — RLHF, DPO, PPO', stars: 10000, url: 'https://github.com/huggingface/trl', license: 'Apache 2.0', tags: ['RLHF', 'DPO', 'Alignment'] },
            { name: 'LitGPT', description: 'Lightning AI\'s hackable LLM implementation for pretraining & finetuning', stars: 11000, url: 'https://github.com/Lightning-AI/litgpt', license: 'Apache 2.0', tags: ['Lightning', 'Pretrain', 'Hack'] },
          ]
        },
        {
          id: 'data-eval',
          order: 7,
          name: 'Data, Eval & Monitoring',
          description: 'Đánh giá model, quản lý data, observability cho AI',
          icon: 'pi pi-chart-bar',
          color: '#06b6d4',
          bgColor: '#ecfeff',
          tools: [
            { name: 'LangSmith', description: 'LangChain\'s platform debug, test, monitor LLM apps', stars: 0, url: 'https://smith.langchain.com', license: 'SaaS', tags: ['Debug', 'Trace', 'LangChain'] },
            { name: 'Weights & Biases', description: 'ML experiment tracking, model registry, data versioning', stars: 9000, url: 'https://github.com/wandb/wandb', license: 'MIT', tags: ['Tracking', 'Experiments', 'MLOps'] },
            { name: 'Phoenix (Arize)', description: 'AI observability — traces, evals, datasets cho LLM', stars: 8000, url: 'https://github.com/Arize-ai/phoenix', license: 'Custom', tags: ['Observability', 'Traces', 'Evals'] },
            { name: 'Ragas', description: 'Framework đánh giá RAG pipelines — faithfulness, relevancy', stars: 8000, url: 'https://github.com/explodinggradients/ragas', license: 'Apache 2.0', tags: ['RAG', 'Eval', 'Metrics'] },
            { name: 'Promptfoo', description: 'Test & evaluate LLM outputs, CI/CD cho prompts', stars: 6000, url: 'https://github.com/promptfoo/promptfoo', license: 'MIT', tags: ['Testing', 'CI/CD', 'Prompts'] },
          ]
        },
        {
          id: 'dev-tools',
          order: 8,
          name: 'AI Developer Tools',
          description: 'Code assistants, IDE plugins, AI-powered dev tools',
          icon: 'pi pi-wrench',
          color: '#ec4899',
          bgColor: '#fdf2f8',
          tools: [
            { name: 'Cursor', description: 'AI-first code editor, fork VS Code, tích hợp AI sâu', stars: 0, url: 'https://cursor.sh', license: 'SaaS', tags: ['Editor', 'AI-native', 'Popular'] },
            { name: 'Continue', description: 'Open-source AI code assistant cho VS Code & JetBrains', stars: 22000, url: 'https://github.com/continuedev/continue', license: 'Apache 2.0', tags: ['IDE', 'Open', 'Assistant'] },
            { name: 'Aider', description: 'AI pair programming trong terminal, edit code trực tiếp', stars: 25000, url: 'https://github.com/paul-gauthier/aider', license: 'Apache 2.0', tags: ['Terminal', 'Pair', 'CLI'] },
            { name: 'Open Interpreter', description: 'Code interpreter chạy local, tương tác tự nhiên', stars: 56000, url: 'https://github.com/OpenInterpreter/open-interpreter', license: 'AGPL', tags: ['Interpreter', 'Local', 'Natural'] },
            { name: 'Cline', description: 'Autonomous coding agent trong VS Code, plan & execute', stars: 18000, url: 'https://github.com/cline/cline', license: 'Apache 2.0', tags: ['Agent', 'VS Code', 'Autonomous'] },
          ]
        },
        {
          id: 'ui-frontend',
          order: 9,
          name: 'AI UI & Frontend',
          description: 'Giao diện chat, UI components, frontend cho AI apps',
          icon: 'pi pi-palette',
          color: '#a855f7',
          bgColor: '#faf5ff',
          tools: [
            { name: 'Vercel AI SDK', description: 'SDK build AI-powered UIs, streaming, React/Next.js', stars: 12000, url: 'https://github.com/vercel/ai', license: 'Apache 2.0', tags: ['React', 'Streaming', 'Vercel'] },
            { name: 'Gradio', description: 'Build ML demos & UIs cực nhanh bằng Python', stars: 35000, url: 'https://github.com/gradio-app/gradio', license: 'Apache 2.0', tags: ['Demo', 'Python', 'Fast'] },
            { name: 'Streamlit', description: 'Framework tạo data apps & AI dashboards bằng Python', stars: 36000, url: 'https://github.com/streamlit/streamlit', license: 'Apache 2.0', tags: ['Dashboard', 'Python', 'Data'] },
            { name: 'Open WebUI', description: 'Self-hosted UI cho LLMs, thay thế ChatGPT UI', stars: 60000, url: 'https://github.com/open-webui/open-webui', license: 'MIT', tags: ['Chat', 'Self-hosted', 'UI'] },
            { name: 'Chainlit', description: 'Build conversational AI UIs in Python, production-ready', stars: 8000, url: 'https://github.com/Chainlit/chainlit', license: 'Apache 2.0', tags: ['Chat', 'Python', 'Production'] },
          ]
        },
        {
          id: 'mlops',
          order: 10,
          name: 'MLOps & Infrastructure',
          description: 'Quản lý vòng đời ML — deploy, scale, monitor',
          icon: 'pi pi-cog',
          color: '#64748b',
          bgColor: '#f8fafc',
          tools: [
            { name: 'MLflow', description: 'Platform quản lý ML lifecycle — track, package, deploy', stars: 19000, url: 'https://github.com/mlflow/mlflow', license: 'Apache 2.0', tags: ['Lifecycle', 'Tracking', 'Deploy'] },
            { name: 'Ray', description: 'Distributed computing framework cho AI/ML workloads', stars: 35000, url: 'https://github.com/ray-project/ray', license: 'Apache 2.0', tags: ['Distributed', 'Scale', 'Training'] },
            { name: 'BentoML', description: 'Build, ship AI applications — model serving made easy', stars: 7500, url: 'https://github.com/bentoml/BentoML', license: 'Apache 2.0', tags: ['Serving', 'Deploy', 'Package'] },
            { name: 'Kubeflow', description: 'ML toolkit cho Kubernetes — pipelines, training, serving', stars: 14000, url: 'https://github.com/kubeflow/kubeflow', license: 'Apache 2.0', tags: ['K8s', 'Pipelines', 'Google'] },
            { name: 'DVC', description: 'Data Version Control — Git cho ML data & experiments', stars: 14000, url: 'https://github.com/iterative/dvc', license: 'Apache 2.0', tags: ['Versioning', 'Data', 'Git'] },
          ]
        },
        {
          id: 'multimodal',
          order: 11,
          name: 'Multimodal & Generative',
          description: 'Image, video, audio, 3D generation — AI sáng tạo',
          icon: 'pi pi-images',
          color: '#e11d48',
          bgColor: '#fff1f2',
          tools: [
            { name: 'ComfyUI', description: 'Node-based UI cho Stable Diffusion, workflow linh hoạt', stars: 65000, url: 'https://github.com/comfyanonymous/ComfyUI', license: 'GPL', tags: ['UI', 'Nodes', 'Workflow'] },
            { name: 'Fooocus', description: 'Image generation đơn giản, focus vào prompting', stars: 42000, url: 'https://github.com/lllyasviel/Fooocus', license: 'GPL', tags: ['Simple', 'Prompting', 'Image'] },
            { name: 'Bark', description: 'Text-to-speech generative model by Suno, realistic voices', stars: 36000, url: 'https://github.com/suno-ai/bark', license: 'MIT', tags: ['TTS', 'Voice', 'Suno'] },
            { name: 'AudioCraft', description: 'Meta\'s audio generation — music, sound effects, compression', stars: 22000, url: 'https://github.com/facebookresearch/audiocraft', license: 'MIT', tags: ['Audio', 'Music', 'Meta'] },
            { name: 'InstantMesh', description: '3D mesh generation from single image, fast & high quality', stars: 6000, url: 'https://github.com/TencentARC/InstantMesh', license: 'Apache 2.0', tags: ['3D', 'Mesh', 'Tencent'] },
          ]
        },
        {
          id: 'security-safety',
          order: 12,
          name: 'AI Safety & Security',
          description: 'Bảo mật AI, guardrails, content moderation, alignment',
          icon: 'pi pi-shield',
          color: '#dc2626',
          bgColor: '#fef2f2',
          tools: [
            { name: 'Guardrails AI', description: 'Validate & structure LLM outputs, prevent hallucination', stars: 5500, url: 'https://github.com/guardrails-ai/guardrails', license: 'Apache 2.0', tags: ['Validation', 'Safety', 'Output'] },
            { name: 'NeMo Guardrails', description: 'NVIDIA\'s toolkit thêm guardrails cho LLM conversations', stars: 4500, url: 'https://github.com/NVIDIA/NeMo-Guardrails', license: 'Apache 2.0', tags: ['NVIDIA', 'Dialogue', 'Rails'] },
            { name: 'LLM Guard', description: 'Security toolkit cho LLM interactions — sanitize I/O', stars: 4000, url: 'https://github.com/protectai/llm-guard', license: 'MIT', tags: ['Security', 'Sanitize', 'Protection'] },
            { name: 'Garak', description: 'LLM vulnerability scanner — probe for weaknesses', stars: 3000, url: 'https://github.com/leondz/garak', license: 'Apache 2.0', tags: ['Scanner', 'Vulnerability', 'Red-team'] },
            { name: 'Rebuff', description: 'Self-hardening prompt injection detector', stars: 1500, url: 'https://github.com/protectai/rebuff', license: 'MIT', tags: ['Injection', 'Detection', 'Prompt'] },
          ]
        },
        {
          id: 'platforms-hubs',
          order: 13,
          name: 'Platforms & Hubs',
          description: 'Nền tảng tổng hợp, model hubs, cộng đồng AI',
          icon: 'pi pi-globe',
          color: '#0891b2',
          bgColor: '#ecfeff',
          tools: [
            { name: 'Hugging Face', description: 'The GitHub of AI — model hub, datasets, spaces, community', stars: 135000, url: 'https://github.com/huggingface/transformers', license: 'Apache 2.0', tags: ['Hub', 'Models', 'Community'] },
            { name: 'Replicate', description: 'Run AI models with API, pay-per-use, cloud inference', stars: 0, url: 'https://replicate.com', license: 'SaaS', tags: ['API', 'Cloud', 'Easy'] },
            { name: 'Together AI', description: 'Fast inference platform cho open models, cheapest GPUs', stars: 0, url: 'https://www.together.ai', license: 'SaaS', tags: ['Inference', 'Cheap', 'Fast'] },
            { name: 'Kaggle', description: 'Data science competition & notebook platform by Google', stars: 0, url: 'https://www.kaggle.com', license: 'SaaS', tags: ['Competition', 'Notebooks', 'Google'] },
            { name: 'Papers With Code', description: 'ML papers kèm code implementations, SOTA benchmarks', stars: 0, url: 'https://paperswithcode.com', license: 'Free', tags: ['Papers', 'Research', 'SOTA'] },
          ]
        },
      ],
    }
  },
  computed: {
    totalTools() {
      return this.layers.reduce((sum, l) => sum + l.tools.length, 0)
    },
    filteredLayers() {
      if (!this.searchQuery.trim()) return this.layers
      const q = this.searchQuery.toLowerCase()
      return this.layers
        .map(layer => ({
          ...layer,
          tools: layer.tools.filter(t =>
            t.name.toLowerCase().includes(q) ||
            t.description.toLowerCase().includes(q) ||
            (t.tags || []).some(tag => tag.toLowerCase().includes(q))
          )
        }))
        .filter(layer => layer.tools.length > 0)
    },
    summaryStats() {
      const allTools = this.layers.flatMap(l => l.tools)
      const openSource = allTools.filter(t => t.stars > 0).length
      const totalStars = allTools.reduce((s, t) => s + (t.stars || 0), 0)
      return [
        { value: this.layers.length, label: 'Layers' },
        { value: this.totalTools, label: 'Công cụ' },
        { value: openSource, label: 'Open Source' },
        { value: this.formatStars(totalStars), label: 'Tổng GitHub Stars' },
      ]
    },
  },
  methods: {
    toggleLayer(id) {
      const idx = this.expandedLayers.indexOf(id)
      if (idx >= 0) this.expandedLayers.splice(idx, 1)
      else this.expandedLayers.push(id)
    },
    openTool(tool) {
      if (tool.url) window.open(tool.url, '_blank')
    },
    formatStars(num) {
      if (!num) return ''
      if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'
      if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
      return num.toString()
    },
  },
  mounted() {
    // Auto-expand all on search
    this.$watch('searchQuery', (val) => {
      if (val.trim()) {
        this.expandedLayers = this.layers.map(l => l.id)
      }
    })
  },
}
</script>

<style scoped>
/* ===== Page Header ===== */
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
.page-title { font-size: 1.5rem; font-weight: 700; color: #0f172a; letter-spacing: -0.02em; margin: 0; display: flex; align-items: center; }
.page-subtitle { font-size: 0.82rem; color: #94a3b8; margin: 0.15rem 0 0; }
.header-actions { display: flex; align-items: center; gap: 0.5rem; }

/* ===== Criteria Banner ===== */
.criteria-banner {
  display: flex; align-items: center; gap: 1rem; padding: 0.85rem 1.25rem;
  background: linear-gradient(135deg, #eef2ff, #f0f9ff); border-radius: 12px;
  border: 1px solid #e0e7ff; margin-bottom: 1rem;
}
.criteria-title { display: flex; align-items: center; gap: 0.4rem; font-size: 0.78rem; font-weight: 600; color: #4f46e5; white-space: nowrap; }
.criteria-title i { font-size: 0.85rem; }
.criteria-list { display: flex; flex-wrap: wrap; gap: 0.4rem; }
.criteria-chip {
  display: flex; align-items: center; gap: 0.25rem; padding: 0.2rem 0.55rem;
  border-radius: 6px; background: white; border: 1px solid #e0e7ff;
  font-size: 0.68rem; font-weight: 500; color: #6366f1;
}
.criteria-chip i { font-size: 0.6rem; }

/* ===== Filter Bar ===== */
.eco-filter-bar {
  display: flex; align-items: center; gap: 0.75rem; padding: 0.65rem 1rem;
  background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  border: 1px solid #f1f5f9; margin-bottom: 1.25rem;
}
.search-wrapper { position: relative; flex: 1; min-width: 200px; }
.search-icon { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.85rem; }
.search-input { width: 100%; padding-left: 2.2rem !important; font-size: 0.82rem; border-radius: 8px; }
.view-toggle { display: flex; gap: 0.25rem; }
.view-btn {
  width: 32px; height: 32px; border-radius: 8px; border: 1px solid #e2e8f0;
  background: white; color: #94a3b8; cursor: pointer; display: flex;
  align-items: center; justify-content: center; transition: all 0.2s; font-size: 0.82rem;
}
.view-btn:hover { border-color: #6366f1; color: #6366f1; }
.view-btn.active { background: #eef2ff; border-color: #6366f1; color: #6366f1; }

/* ===== Layer Sections ===== */
.layers-container { display: flex; flex-direction: column; gap: 0.75rem; }

.layer-section {
  background: white; border-radius: 14px; border: 1px solid #f1f5f9;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden; transition: all 0.2s;
}
.layer-section:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.06); }

.layer-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1rem 1.25rem; cursor: pointer; transition: background 0.2s;
}
.layer-header:hover { background: #fafbfc; }

.layer-left { display: flex; align-items: center; gap: 0.85rem; }
.layer-number {
  width: 28px; height: 28px; border-radius: 8px; background: #f1f5f9;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.72rem; font-weight: 700; color: #64748b; flex-shrink: 0;
}
.layer-icon-wrapper {
  width: 38px; height: 38px; border-radius: 10px; display: flex;
  align-items: center; justify-content: center; flex-shrink: 0;
}
.layer-icon-wrapper i { font-size: 1rem; color: white; }

.layer-info { min-width: 0; }
.layer-title { font-size: 0.95rem; font-weight: 650; color: #1e293b; margin: 0; }
.layer-desc { font-size: 0.75rem; color: #94a3b8; margin: 0.1rem 0 0; }

.layer-right { display: flex; align-items: center; gap: 0.75rem; }
.layer-count {
  font-size: 0.68rem; font-weight: 600; padding: 0.15rem 0.5rem;
  border-radius: 6px; background: var(--layer-bg); color: var(--layer-color);
}
.layer-right .pi { font-size: 0.72rem; color: #94a3b8; transition: transform 0.2s; }

/* ===== Layer Body ===== */
.layer-body { padding: 0 1.25rem 1.25rem; }

.tools-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 0.65rem; }
.tools-list { display: flex; flex-direction: column; gap: 0.5rem; }

/* ===== Tool Card ===== */
.tool-card {
  display: flex; gap: 0.75rem; padding: 0.85rem 1rem; border-radius: 10px;
  border: 1px solid #f1f5f9; background: #fafbfc; cursor: pointer;
  transition: all 0.2s ease;
}
.tool-card:hover {
  border-color: var(--layer-color, #e2e8f0); background: white;
  box-shadow: 0 4px 12px rgba(0,0,0,0.06); transform: translateY(-1px);
}
.tool-card--list { flex-direction: row; align-items: center; }

.tool-logo-wrapper {
  width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
  background: linear-gradient(135deg, var(--layer-color, #6366f1), var(--layer-color, #8b5cf6));
  display: flex; align-items: center; justify-content: center; opacity: 0.85;
}
.tool-logo-text { font-size: 0.72rem; font-weight: 700; color: white; letter-spacing: 0.02em; }

.tool-content { flex: 1; min-width: 0; }
.tool-head { display: flex; align-items: center; justify-content: space-between; gap: 0.35rem; margin-bottom: 0.2rem; }
.tool-name { font-size: 0.82rem; font-weight: 600; color: #1e293b; margin: 0; }
.tool-link {
  width: 24px; height: 24px; border-radius: 6px; display: flex;
  align-items: center; justify-content: center; color: #94a3b8;
  transition: all 0.15s; flex-shrink: 0;
}
.tool-link:hover { background: #eef2ff; color: #6366f1; }
.tool-link i { font-size: 0.65rem; }

.tool-desc { font-size: 0.72rem; color: #64748b; margin: 0 0 0.35rem; line-height: 1.45; }

.tool-meta { display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap; }
.tool-stars {
  display: flex; align-items: center; gap: 0.2rem;
  font-size: 0.65rem; font-weight: 600; color: #f59e0b;
}
.tool-stars i { font-size: 0.55rem; }
.tool-license {
  font-size: 0.6rem; font-weight: 500; padding: 0.08rem 0.35rem;
  border-radius: 4px; background: #f1f5f9; color: #64748b;
}
.tool-tag {
  font-size: 0.58rem; font-weight: 500; padding: 0.08rem 0.35rem;
  border-radius: 4px; background: var(--layer-bg, #eef2ff); color: var(--layer-color, #6366f1);
}

/* ===== Slide Transition ===== */
.slide-enter-active, .slide-leave-active { transition: all 0.25s ease; overflow: hidden; }
.slide-enter-from, .slide-leave-to { opacity: 0; max-height: 0; }
.slide-enter-to, .slide-leave-from { opacity: 1; max-height: 2000px; }

/* ===== Footer Stats ===== */
.eco-footer {
  display: flex; justify-content: center; gap: 2rem; padding: 1.25rem;
  margin-top: 1.5rem; background: linear-gradient(135deg, #0f172a, #1e293b);
  border-radius: 14px;
}
.eco-stat { text-align: center; }
.eco-stat-value { display: block; font-size: 1.25rem; font-weight: 800; color: white; }
.eco-stat-label { font-size: 0.68rem; color: rgba(255,255,255,0.6); font-weight: 450; }

/* ===== Form Control (search) ===== */
.form-control {
  width: 100%; padding: 0.55rem 0.75rem; border: 1px solid #e2e8f0;
  border-radius: 8px; font-size: 0.85rem; color: #1e293b; background: white;
  transition: all 0.2s; outline: none; font-family: inherit;
}
.form-control:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }

/* ===== Responsive ===== */
@media (max-width: 1024px) {
  .tools-grid { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); }
}
@media (max-width: 768px) {
  .criteria-banner { flex-direction: column; align-items: flex-start; }
  .tools-grid { grid-template-columns: 1fr; }
  .eco-footer { flex-wrap: wrap; gap: 1rem; }
  .page-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
}
</style>
