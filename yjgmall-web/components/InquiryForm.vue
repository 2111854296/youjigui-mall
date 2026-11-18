<script setup lang="ts">
const props = withDefaults(defineProps<{
  type: 'supply' | 'purchase' | 'product' | 'company'
  id: number | string
  defaultTitle?: string
  buttonText?: string
  buttonClass?: string
  hideButton?: boolean
}>(), {
  buttonClass: 'btn btn--primary',
  hideButton: false,
})

const { user } = useAuth()
const { api } = useApi()
const open = ref(false)
const msg = ref('')
const err = ref('')
const loading = ref(false)
const form = reactive({
  contact_name: user.value?.name || '',
  contact_phone: user.value?.mobile || '',
  contact_email: user.value?.email || '',
  title: props.defaultTitle ? `询价：${props.defaultTitle}` : '产品询价',
  content: '',
})

watch(() => user.value, (u) => {
  if (!u) return
  form.contact_name ||= u.name || ''
  form.contact_phone ||= u.mobile || ''
  form.contact_email ||= u.email || ''
})

watch(() => props.defaultTitle, (t) => {
  if (t && !form.content) form.title = `询价：${t}`
})

const ctaLabel = computed(() =>
  props.buttonText || (props.type === 'company' ? '在线留言' : '在线询盘'),
)

function openModal() {
  msg.value = ''
  err.value = ''
  open.value = true
  if (import.meta.client) document.body.style.overflow = 'hidden'
}

function closeModal() {
  open.value = false
  if (import.meta.client) document.body.style.overflow = ''
}

defineExpose({ open: openModal, close: closeModal })

const route = useRoute()
onMounted(() => {
  if (route.query.inquiry === '1' || route.query.inquiry === 'true') openModal()
})
watch(() => route.query.inquiry, (v) => {
  if (v === '1' || v === 'true') openModal()
})

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape' && open.value) closeModal()
}
onMounted(() => {
  if (import.meta.client) window.addEventListener('keydown', onKeydown)
})
onBeforeUnmount(() => {
  if (import.meta.client) {
    window.removeEventListener('keydown', onKeydown)
    document.body.style.overflow = ''
  }
})

async function submit() {
  err.value = ''
  msg.value = ''
  loading.value = true
  try {
    await api('/inquiries', {
      method: 'POST',
      body: {
        type: props.type,
        id: Number(props.id),
        ...form,
      },
      auth: !!user.value,
    })
    msg.value = '询盘已提交，平台审核后将转达企业'
    form.content = ''
    setTimeout(() => closeModal(), 1000)
  } catch (e: any) {
    const errors = e?.data?.errors
    err.value = errors ? Object.values(errors).flat().join('；') : (e?.data?.message || '发送失败')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="inq-wrap">
    <button
      v-if="!hideButton"
      :class="buttonClass"
      type="button"
      @click="openModal"
    >
      {{ ctaLabel }}
    </button>
    <div v-if="msg && !open" class="alert alert-success" style="margin-top:8px">{{ msg }}</div>

    <Teleport to="body">
      <div
        v-if="open"
        class="inq-overlay"
        role="dialog"
        aria-modal="true"
        @click.self="closeModal"
      >
        <div class="inq-modal">
          <div class="inq-hd">
            <h3>{{ type === 'company' ? '在线留言' : '发送询盘' }}</h3>
            <button type="button" class="inq-x" aria-label="关闭" @click="closeModal">×</button>
          </div>
          <div v-if="msg" class="alert alert-success">{{ msg }}</div>
          <div v-if="err" class="alert alert-error">{{ err }}</div>
          <form class="inq-form" @submit.prevent="submit">
            <div class="form-row"><label>联系人 <em>*</em></label><input v-model="form.contact_name" required></div>
            <div class="form-row"><label>手机 <em>*</em></label><input v-model="form.contact_phone" required></div>
            <div class="form-row"><label>邮箱</label><input v-model="form.contact_email" type="email"></div>
            <div class="form-row"><label>标题 <em>*</em></label><input v-model="form.title" required></div>
            <div class="form-row"><label>内容 <em>*</em></label><textarea v-model="form.content" required rows="4" /></div>
            <div class="inq-actions">
              <button type="button" class="btn btn--ghost" @click="closeModal">取消</button>
              <button class="btn btn--primary" type="submit" :disabled="loading">
                {{ loading ? '发送中…' : '提交询盘' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.inq-overlay {
  position: fixed;
  inset: 0;
  z-index: 1200;
  background: rgba(15, 28, 52, .48);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px 16px;
  box-sizing: border-box;
}
.inq-modal {
  width: min(520px, 100%);
  max-height: min(90vh, 720px);
  overflow: auto;
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 24px 64px rgba(15, 35, 70, .22);
  padding: 20px 22px 22px;
}
.inq-hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}
.inq-hd h3 {
  margin: 0;
  font-size: 17px;
  font-weight: 800;
  color: var(--ink);
  padding-left: 10px;
  border-left: 3px solid var(--blue);
  line-height: 1.2;
}
.inq-x {
  border: 0;
  background: transparent;
  font-size: 26px;
  line-height: 1;
  color: var(--ink-3);
  cursor: pointer;
  padding: 0 4px;
}
.inq-form .form-row { margin-bottom: 12px; }
.inq-form label {
  display: block;
  font-size: 13px;
  color: var(--ink-3);
  margin-bottom: 4px;
}
.inq-form label em { color: #c2410c; font-style: normal; }
.inq-form input,
.inq-form textarea {
  width: 100%;
  box-sizing: border-box;
  padding: 8px 10px;
  border: 1px solid var(--line);
  border-radius: 8px;
  font: inherit;
}
.inq-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 4px;
}
.btn--ghost {
  background: #fff;
  color: var(--ink-2);
  border: 1px solid var(--line);
  padding: 10px 18px;
  border-radius: 10px;
  font: inherit;
  font-weight: 600;
  cursor: pointer;
}
</style>
