<script setup lang="ts">
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import Placeholder from '@tiptap/extension-placeholder'
import Image from '@tiptap/extension-image'

const model = defineModel<string>({ default: '' })
const props = withDefaults(defineProps<{
  placeholder?: string
  minHeight?: string
  maxImages?: number
}>(), {
  placeholder: '请输入正文…',
  minHeight: '180px',
  maxImages: 20,
})

const { api } = useApi()
const rteEl = ref<HTMLElement | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)
const dragging = ref(false)
const imgErr = ref('')
const uploading = ref(false)

const extensions = [
  StarterKit.configure({
    heading: { levels: [2, 3] },
  }),
  Link.configure({
    openOnClick: false,
    HTMLAttributes: { rel: 'noopener noreferrer', target: '_blank' },
  }),
  Placeholder.configure({ placeholder: props.placeholder }),
]
if (props.maxImages > 0) {
  extensions.push(Image.configure({ allowBase64: false }))
}

const editor = useEditor({
  extensions,
  content: model.value || '',
  onUpdate: ({ editor: ed }) => {
    const html = ed.getHTML()
    model.value = html === '<p></p>' ? '' : html
    if (props.maxImages && (html.match(/<img\b/gi) || []).length < props.maxImages && imgErr.value.startsWith('最多')) {
      imgErr.value = ''
    }
  },
})

watch(model, (val) => {
  if (!editor.value) return
  const cur = editor.value.getHTML()
  const next = val || ''
  if (next !== cur && next !== (cur === '<p></p>' ? '' : cur)) {
    editor.value.commands.setContent(next || '', { emitUpdate: false })
  }
})

onBeforeUnmount(() => {
  editor.value?.destroy()
})

function run(cmd: string, attrs?: Record<string, unknown>) {
  if (!editor.value) return
  const chain = editor.value.chain().focus()
  switch (cmd) {
    case 'bold': chain.toggleBold().run(); break
    case 'italic': chain.toggleItalic().run(); break
    case 'strike': chain.toggleStrike().run(); break
    case 'h2': chain.toggleHeading({ level: 2 }).run(); break
    case 'h3': chain.toggleHeading({ level: 3 }).run(); break
    case 'bullet': chain.toggleBulletList().run(); break
    case 'ordered': chain.toggleOrderedList().run(); break
    case 'quote': chain.toggleBlockquote().run(); break
    case 'hr': chain.setHorizontalRule().run(); break
    case 'link': {
      void (async () => {
        const prev = editor.value?.getAttributes('link').href as string | undefined
        const { prompt: appPrompt } = useAppDialog()
        const url = await appPrompt({
          title: '插入链接',
          message: '请输入链接地址',
          defaultValue: prev || 'https://',
          placeholder: 'https://',
          allowEmpty: true,
        })
        if (url === null || !editor.value) return
        const c = editor.value.chain().focus()
        if (url === '') {
          c.extendMarkRange('link').unsetLink().run()
        } else {
          c.extendMarkRange('link').setLink({ href: url }).run()
        }
      })()
      break
    }
    case 'clear': chain.clearNodes().unsetAllMarks().run(); break
    default:
      if (attrs) chain.run()
  }
}

function active(name: string, attrs?: Record<string, unknown>) {
  return editor.value?.isActive(name, attrs) || false
}

function imageFiles(list: FileList | File[] | undefined | null) {
  return [...(list || [])].filter((f) => f.type.startsWith('image/'))
}

function imageCount() {
  const html = editor.value?.getHTML() || model.value || ''
  return (html.match(/<img\b/gi) || []).length
}

function dragHasImage(e: DragEvent) {
  const items = [...(e.dataTransfer?.items || [])]
  if (items.some((i) => i.kind === 'file' && (!i.type || i.type.startsWith('image/')))) return true
  return imageFiles(e.dataTransfer?.files).length > 0
}

function onDragOver(e: DragEvent) {
  if (!props.maxImages || !dragHasImage(e)) return
  e.preventDefault()
  dragging.value = true
}

function onDragLeave(e: DragEvent) {
  const next = e.relatedTarget as Node | null
  if (next && rteEl.value?.contains(next)) return
  dragging.value = false
}

async function uploadImage(file: File) {
  const fd = new FormData()
  fd.append('file', file)
  const res = await api<{ data: { url: string } }>('/member/upload', { method: 'POST', body: fd })
  return res.data.url
}

async function insertImages(files: File[]) {
  if (!editor.value || !props.maxImages) return
  const images = imageFiles(files)
  if (!images.length) return
  const room = props.maxImages - imageCount()
  if (room <= 0) {
    imgErr.value = `最多插入 ${props.maxImages} 张图片`
    return
  }
  const batch = images.slice(0, room)
  imgErr.value = images.length > room ? `最多插入 ${props.maxImages} 张图片` : ''
  uploading.value = true
  try {
    for (const file of batch) {
      if (file.size > 5 * 1024 * 1024) {
        imgErr.value = '图片大小需在 5MB 以内'
        continue
      }
      const url = await uploadImage(file)
      if (!url || !editor.value) continue
      editor.value.chain().focus().setImage({ src: url }).run()
    }
  } catch (err: any) {
    imgErr.value = err?.data?.message || '图片上传失败'
  } finally {
    uploading.value = false
  }
}

function pickImages() {
  if (!props.maxImages) return
  imgErr.value = ''
  fileInput.value?.click()
}

function onPick(e: Event) {
  const input = e.target as HTMLInputElement
  const files = imageFiles(input.files)
  input.value = ''
  void insertImages(files)
}

function onPaste(e: ClipboardEvent) {
  if (!props.maxImages) return
  const files = imageFiles(e.clipboardData?.files)
  if (!files.length) return
  e.preventDefault()
  e.stopPropagation()
  void insertImages(files)
}

function onDrop(e: DragEvent) {
  const files = imageFiles(e.dataTransfer?.files)
  if (!props.maxImages || !files.length) return
  e.preventDefault()
  e.stopPropagation()
  dragging.value = false
  void insertImages(files)
}
</script>

<template>
  <ClientOnly>
    <div
      ref="rteEl"
      class="rte"
      :class="{ drag: dragging }"
      :style="{ '--rte-min': minHeight }"
      @dragover.capture="onDragOver"
      @dragleave="onDragLeave"
      @drop.capture="onDrop"
      @paste.capture="onPaste"
    >
      <div class="rte-bar" v-if="editor">
        <button type="button" :class="{ on: active('bold') }" title="粗体" @click="run('bold')">B</button>
        <button type="button" :class="{ on: active('italic') }" title="斜体" @click="run('italic')"><i>I</i></button>
        <button type="button" :class="{ on: active('strike') }" title="删除线" @click="run('strike')"><s>S</s></button>
        <span class="sep" />
        <button type="button" :class="{ on: active('heading', { level: 2 }) }" @click="run('h2')">H2</button>
        <button type="button" :class="{ on: active('heading', { level: 3 }) }" @click="run('h3')">H3</button>
        <span class="sep" />
        <button type="button" :class="{ on: active('bulletList') }" title="无序列表" @click="run('bullet')">• 列表</button>
        <button type="button" :class="{ on: active('orderedList') }" title="有序列表" @click="run('ordered')">1. 列表</button>
        <button type="button" :class="{ on: active('blockquote') }" @click="run('quote')">引用</button>
        <span class="sep" />
        <button type="button" :class="{ on: active('link') }" @click="run('link')">链接</button>
        <button v-if="maxImages > 0" type="button" title="插入图片" @click="pickImages">插入图片</button>
        <input
          ref="fileInput"
          class="rte-file"
          type="file"
          accept="image/jpeg,image/png,image/gif,image/webp,image/bmp"
          multiple
          @change="onPick"
        >
        <button type="button" @click="run('hr')">分隔线</button>
        <button type="button" @click="run('clear')">清除格式</button>
      </div>
      <EditorContent :editor="editor" class="rte-body" />
      <p v-if="dragging" class="rte-drop">松开即可插入图片</p>
      <p v-if="imgErr" class="rte-err">{{ imgErr }}</p>
      <p v-else-if="uploading" class="rte-err">图片上传中…</p>
      <p v-if="maxImages > 0" class="rte-rule">图片内容严禁带有联系方式及违反法律法规内容！</p>
    </div>
    <template #fallback>
      <textarea class="rte-fallback" :value="model" :placeholder="placeholder" rows="8" @input="model = ($event.target as HTMLTextAreaElement).value" />
    </template>
  </ClientOnly>
</template>

<style scoped>
.rte.drag { border-color: #2468f2; box-shadow: inset 0 0 0 1px #2468f2; }
.rte-drop {
  margin: 0;
  padding: 6px 12px 8px;
  font-size: 12px;
  color: #1443b8;
}
.rte-err { margin: 0; padding: 0 12px 8px; font-size: 12px; color: #c2410c; }
.rte-rule {
  margin: 0;
  padding: 0 12px 8px;
  font-size: 12px;
  line-height: 1.45;
  color: #c9372b;
  font-weight: 600;
}
.rte-file { display: none; }
.rte-body :deep(.ProseMirror img) {
  display: block;
  max-width: 100%;
  height: auto;
  margin: 8px 0;
}
.rte {
  border: 1px solid #c5d0db;
  background: #fff;
  border-radius: 2px;
  position: relative;
  display: flex;
  flex-direction: column;
  /* 长文只滚正文，工具栏始终在编辑器顶部 */
  max-height: min(72vh, 680px);
}
.rte-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  padding: 6px 8px;
  border-bottom: 1px solid #e3e8ef;
  background: linear-gradient(180deg, #f7f9fc, #f0f3f7);
  flex: 0 0 auto;
  position: sticky;
  top: 0;
  z-index: 2;
}
.rte-bar button {
  appearance: none;
  border: 1px solid transparent;
  background: transparent;
  font: inherit;
  font-size: 12px;
  padding: 3px 8px;
  cursor: pointer;
  color: #334;
  border-radius: 2px;
  line-height: 1.4;
}
.rte-bar button:hover { background: #fff; border-color: #c5d0db; }
.rte-bar button.on { background: #e8f0fa; border-color: #255d9a; color: #255d9a; font-weight: 600; }
.rte-bar .sep { width: 1px; align-self: stretch; background: #dde3ea; margin: 2px 4px; }
.rte-body {
  flex: 1 1 auto;
  min-height: var(--rte-min, 180px);
  overflow: auto;
  overscroll-behavior: contain;
  -webkit-overflow-scrolling: touch;
}
.rte-body :deep(.tiptap),
.rte-body :deep(.ProseMirror) {
  min-height: var(--rte-min, 180px);
  padding: 10px 12px;
  outline: none;
  font-size: 14px;
  line-height: 1.7;
  color: #222;
}
.rte-body :deep(.ProseMirror p) { margin: 0 0 .6em; }
.rte-body :deep(.ProseMirror h2) { font-size: 1.25em; margin: .8em 0 .4em; }
.rte-body :deep(.ProseMirror h3) { font-size: 1.1em; margin: .7em 0 .35em; }
.rte-body :deep(.ProseMirror ul),
.rte-body :deep(.ProseMirror ol) { padding-left: 1.4em; margin: 0 0 .6em; }
.rte-body :deep(.ProseMirror blockquote) {
  margin: 0 0 .6em;
  padding: 4px 12px;
  border-left: 3px solid #255d9a;
  color: #556;
  background: #f7f9fc;
}
.rte-body :deep(.ProseMirror a) { color: #255d9a; text-decoration: underline; }
.rte-body :deep(.ProseMirror p.is-editor-empty:first-child::before) {
  content: attr(data-placeholder);
  float: left;
  color: #99a;
  pointer-events: none;
  height: 0;
}
.rte-fallback {
  width: 100%;
  min-height: 160px;
  padding: 8px 10px;
  border: 1px solid #c5d0db;
  font: inherit;
}
</style>
