<script setup lang="ts">
const model = defineModel<string>({ default: '' })
const props = withDefaults(defineProps<{
  label?: string
  hint?: string
  /** CSS aspect-ratio，按页面实际展示比例，例如 1 / 1、2 / 1 */
  ratio?: string
  wide?: boolean
}>(), {
  label: '图片',
  hint: 'jpg / png / gif / webp，最大 5MB',
  ratio: '1 / 1',
  wide: false,
})

const config = useRuntimeConfig()
const { api } = useApi()
const input = ref<HTMLInputElement | null>(null)
const uploading = ref(false)
const error = ref('')
const dragging = ref(false)
const broken = ref(false)

const previewSrc = computed(() => {
  const raw = String(model.value || '').trim()
  if (!raw || broken.value) return ''
  if (/^https?:\/\//i.test(raw) || raw.startsWith('data:') || raw.startsWith('blob:')) return raw
  if (raw.startsWith('/')) {
    try {
      const apiBase = String(config.public.apiBase || '')
      const origin = apiBase.replace(/\/api\/v1\/?$/, '')
      return origin ? `${origin}${raw}` : raw
    } catch {
      return raw
    }
  }
  return raw
})

const hasPreview = computed(() => !!previewSrc.value)

watch(model, () => { broken.value = false })

function pick() {
  error.value = ''
  input.value?.click()
}

async function uploadFile(file: File) {
  uploading.value = true
  error.value = ''
  broken.value = false
  try {
    const fd = new FormData()
    fd.append('file', file)
    const res = await api<{ data: { url: string } }>('/member/upload', {
      method: 'POST',
      body: fd,
    })
    model.value = res.data.url
  } catch (err: any) {
    error.value = err?.data?.message || err?.data?.errors?.file?.[0] || '上传失败'
  } finally {
    uploading.value = false
    if (input.value) input.value.value = ''
  }
}

async function onFile(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (file) await uploadFile(file)
}

function onDrop(e: DragEvent) {
  dragging.value = false
  const file = e.dataTransfer?.files?.[0]
  if (file) void uploadFile(file)
}

function clear(e?: Event) {
  e?.stopPropagation()
  model.value = ''
  error.value = ''
  broken.value = false
}

function onImgError() {
  broken.value = true
}
</script>

<template>
  <div class="iu" :class="{ wide: props.wide }">
    <div class="iu-label">{{ label }}</div>
    <div
      class="iu-box"
      :style="{ aspectRatio: props.ratio }"
      :class="{ on: hasPreview, drag: dragging, busy: uploading }"
      @click="pick"
      @dragenter.prevent="dragging = true"
      @dragover.prevent="dragging = true"
      @dragleave.prevent="dragging = false"
      @drop.prevent="onDrop"
    >
      <img v-if="hasPreview" :src="previewSrc" alt="预览" class="iu-img" @error="onImgError">
      <div v-else class="iu-empty">
        <span class="iu-plus">+</span>
        <span>{{ uploading ? '上传中…' : (model && broken ? '图片失效，点击重传' : '点击或拖拽上传') }}</span>
      </div>
      <div v-if="hasPreview" class="iu-mask" @click.stop>
        <button type="button" class="iu-btn" @click="pick">更换</button>
        <button type="button" class="iu-btn" @click="clear">删除</button>
      </div>
    </div>
    <input
      ref="input"
      type="file"
      accept="image/jpeg,image/png,image/gif,image/webp"
      class="hidden"
      @change="onFile"
    >
    <p v-if="error" class="iu-err">{{ error }}</p>
    <p v-else class="iu-hint">{{ hint }}</p>
    <p class="iu-rule">图片内容严禁带有联系方式及违反法律法规内容！</p>
  </div>
</template>

<style scoped>
.iu { min-width: 0; }
.iu-label {
  font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 8px;
}
.iu-box {
  position: relative;
  width: 100%;
  aspect-ratio: 1;
  max-width: 160px;
  border: 1px dashed #cbd5e1;
  background: #f8fafc;
  cursor: pointer;
  overflow: hidden;
  display: grid;
  place-items: center;
  transition: border-color .15s, background .15s;
}
.iu.wide .iu-box { max-width: 640px; }
.iu-box:hover, .iu-box.drag { border-color: #f97316; background: #fff7ed; }
.iu-box.busy { opacity: .7; pointer-events: none; }
.iu-box.on { border-style: solid; border-color: #e2e8f0; background: #fff; }
.iu-empty {
  display: flex; flex-direction: column; align-items: center; gap: 6px;
  color: #94a3b8; font-size: 12px; text-align: center; padding: 8px;
}
.iu-plus {
  width: 28px; height: 28px; border-radius: 50%;
  border: 1px solid #cbd5e1; display: grid; place-items: center;
  font-size: 18px; line-height: 1; color: #64748b;
}
.iu-img { width: 100%; height: 100%; object-fit: cover; display: block; }
.iu-mask {
  position: absolute; inset: auto 0 0 0;
  padding: 8px;
  background: linear-gradient(transparent, rgba(15, 23, 42, .72));
  display: none; justify-content: center; gap: 8px;
}
.iu-box.on:hover .iu-mask { display: flex; }
.iu-btn {
  border: 0; background: #fff; color: #0f172a;
  padding: 5px 10px; font: inherit; font-size: 12px; cursor: pointer;
}
.iu-btn:hover { background: #fff7ed; color: #c2410c; }
.hidden { display: none; }
.iu-hint { margin: 6px 0 0; font-size: 12px; color: #94a3b8; }
.iu-rule {
  margin: 4px 0 0;
  font-size: 12px;
  line-height: 1.45;
  color: #c9372b;
  font-weight: 600;
}
.iu-err { margin: 6px 0 0; font-size: 12px; color: #dc2626; }
</style>
