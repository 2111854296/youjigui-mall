<script setup lang="ts">
const { state, accept, cancel } = useAppDialog()

function onKey(e: KeyboardEvent) {
  if (!state.open) return
  if (e.key === 'Escape') {
    e.preventDefault()
    cancel()
  } else if (e.key === 'Enter' && state.type !== 'prompt') {
    e.preventDefault()
    accept()
  }
}

watch(
  () => state.open,
  (v) => {
    if (!import.meta.client) return
    if (v) document.addEventListener('keydown', onKey)
    else document.removeEventListener('keydown', onKey)
  },
)

onBeforeUnmount(() => {
  if (!import.meta.client) return
  document.removeEventListener('keydown', onKey)
})
</script>

<template>
  <Teleport to="body">
    <div v-if="state.open" class="ad-mask" @click.self="cancel">
      <div
        class="ad-box"
        role="dialog"
        aria-modal="true"
        :aria-label="state.title"
        :data-tone="state.tone"
      >
        <button type="button" class="ad-x" aria-label="关闭" @click="cancel">×</button>
        <div class="ad-icon" aria-hidden="true">
          <span v-if="state.tone === 'danger'" class="ic danger">!</span>
          <span v-else-if="state.tone === 'success'" class="ic ok">✓</span>
          <span v-else-if="state.type === 'prompt'" class="ic edit">✎</span>
          <span v-else class="ic info">i</span>
        </div>
        <h3 class="ad-title">{{ state.title }}</h3>
        <p v-if="state.message" class="ad-msg">{{ state.message }}</p>
        <div v-if="state.type === 'prompt'" class="ad-field">
          <input
            v-model="state.input"
            class="ad-input"
            type="text"
            :placeholder="state.placeholder || '请输入'"
            autofocus
            @keydown.enter.prevent="accept"
          >
        </div>
        <div class="ad-acts" :class="{ single: state.type === 'alert' }">
          <button
            v-if="state.type !== 'alert'"
            type="button"
            class="ad-btn ghost"
            @click="cancel"
          >
            {{ state.cancelText }}
          </button>
          <button
            type="button"
            class="ad-btn primary"
            :class="{ danger: state.tone === 'danger' }"
            @click="accept"
          >
            {{ state.confirmText }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.ad-mask {
  position: fixed;
  inset: 0;
  z-index: 10080;
  background: rgba(15, 28, 52, 0.48);
  display: grid;
  place-items: center;
  padding: 20px;
  animation: ad-fade 0.16s ease-out;
}
.ad-box {
  position: relative;
  width: min(400px, 100%);
  background: #fff;
  border-radius: 16px;
  padding: 26px 24px 20px;
  box-shadow: 0 22px 56px rgba(15, 35, 70, 0.22);
  animation: ad-up 0.2s ease-out;
  overflow: hidden;
}
.ad-box::before {
  content: "";
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  height: 4px;
  background: linear-gradient(90deg, #1d4fd7, #3f86f9);
}
.ad-box[data-tone='danger']::before {
  background: linear-gradient(90deg, #c2410c, #ea580c);
}
.ad-box[data-tone='success']::before {
  background: linear-gradient(90deg, #047857, #10b981);
}
.ad-x {
  position: absolute;
  top: 10px;
  right: 10px;
  width: 32px;
  height: 32px;
  border: 0;
  background: transparent;
  color: #94a3b8;
  font-size: 22px;
  line-height: 1;
  cursor: pointer;
  border-radius: 8px;
  font-family: inherit;
}
.ad-x:hover {
  color: #475569;
  background: #f1f5f9;
}
.ad-icon {
  display: flex;
  justify-content: center;
  margin-bottom: 12px;
}
.ic {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  display: grid;
  place-items: center;
  font-size: 20px;
  font-weight: 800;
  color: #fff;
  background: linear-gradient(135deg, #1d4fd7, #3f86f9);
  box-shadow: 0 10px 22px rgba(36, 104, 242, 0.26);
}
.ic.danger {
  background: linear-gradient(135deg, #c2410c, #ea580c);
  box-shadow: 0 10px 22px rgba(194, 65, 12, 0.26);
}
.ic.ok {
  background: linear-gradient(135deg, #047857, #10b981);
  box-shadow: 0 10px 22px rgba(4, 120, 87, 0.26);
}
.ic.edit {
  background: linear-gradient(135deg, #0f766e, #14b8a6);
  box-shadow: 0 10px 22px rgba(15, 118, 110, 0.26);
  font-size: 18px;
}
.ad-title {
  margin: 0;
  text-align: center;
  font-size: 18px;
  font-weight: 800;
  color: #13294b;
}
.ad-msg {
  margin: 10px 0 0;
  text-align: center;
  font-size: 13.5px;
  line-height: 1.65;
  color: #5a6b85;
  white-space: pre-wrap;
}
.ad-field {
  margin-top: 14px;
}
.ad-input {
  width: 100%;
  height: 42px;
  padding: 0 12px;
  border: 1px solid #d7e0ef;
  border-radius: 10px;
  font: inherit;
  font-size: 14px;
  color: #13294b;
  outline: none;
  box-sizing: border-box;
}
.ad-input:focus {
  border-color: #2468f2;
  box-shadow: 0 0 0 3px rgba(36, 104, 242, 0.12);
}
.ad-acts {
  display: grid;
  grid-template-columns: 1fr 1.2fr;
  gap: 10px;
  margin-top: 18px;
}
.ad-acts.single {
  grid-template-columns: 1fr;
}
.ad-btn {
  height: 42px;
  border-radius: 11px;
  border: 0;
  font: inherit;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
}
.ad-btn.ghost {
  background: #f1f5f9;
  color: #5a6b85;
}
.ad-btn.ghost:hover {
  background: #e8eef6;
  color: #13294b;
}
.ad-btn.primary {
  background: linear-gradient(135deg, #1d4fd7, #3f86f9);
  color: #fff;
  box-shadow: 0 8px 18px rgba(36, 104, 242, 0.28);
}
.ad-btn.primary:hover {
  filter: brightness(1.04);
}
.ad-btn.danger {
  background: linear-gradient(135deg, #c2410c, #ea580c);
  box-shadow: 0 8px 18px rgba(194, 65, 12, 0.28);
}
@keyframes ad-fade {
  from { opacity: 0; }
  to { opacity: 1; }
}
@keyframes ad-up {
  from { opacity: 0; transform: translateY(10px) scale(0.98); }
  to { opacity: 1; transform: none; }
}
</style>
