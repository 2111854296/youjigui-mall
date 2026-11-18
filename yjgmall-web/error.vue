<script setup lang="ts">
const props = defineProps<{ error: { statusCode?: number; statusMessage?: string; message?: string } }>()
const code = computed(() => Number(props.error?.statusCode || 404))
const title = computed(() => (code.value === 404 ? '页面不存在' : '出了点问题'))
const tip = computed(() =>
  code.value === 404
    ? '您访问的页面可能已下线或地址有误，试试回首页或搜索。'
    : (props.error?.statusMessage || props.error?.message || '请稍后再试，或返回首页继续浏览。'),
)

useHead(() => ({ title: `${code.value} · ${title.value}` }))

function goHome() {
  clearError({ redirect: '/' })
}
</script>

<template>
  <div class="err-page">
    <div class="err-card">
      <p class="brand">有机硅商城</p>
      <div class="code">{{ code }}</div>
      <h1>{{ title }}</h1>
      <p class="tip">{{ tip }}</p>
      <div class="acts">
        <button type="button" class="btn primary" @click="goHome">返回首页</button>
        <NuxtLink class="btn ghost" to="/search">去搜索</NuxtLink>
        <NuxtLink class="btn ghost" to="/quotes">价格行情</NuxtLink>
      </div>
    </div>
  </div>
</template>

<style scoped>
.err-page {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 32px 16px;
  background:
    radial-gradient(900px 420px at 12% -10%, rgba(36, 104, 242, 0.16), transparent 60%),
    linear-gradient(180deg, #f5f8ff 0%, #eef3fb 100%);
  color: #13294b;
  font-family: "Noto Sans SC", "PingFang SC", "Microsoft YaHei", sans-serif;
}
.err-card {
  width: min(520px, 100%);
  background: #fff;
  border: 1px solid #e4ebf7;
  border-radius: 18px;
  box-shadow: 0 18px 40px rgba(19, 41, 75, 0.08);
  padding: 36px 28px 28px;
  text-align: center;
}
.brand {
  margin: 0 0 8px;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: #2468f2;
}
.code {
  font-size: 64px;
  font-weight: 800;
  line-height: 1;
  color: #2468f2;
  letter-spacing: -0.04em;
}
h1 {
  margin: 10px 0 8px;
  font-size: 22px;
  font-weight: 800;
}
.tip {
  margin: 0 auto 22px;
  max-width: 28em;
  font-size: 14px;
  line-height: 1.6;
  color: #5a6b85;
}
.acts {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  justify-content: center;
}
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 40px;
  padding: 0 16px;
  border-radius: 999px;
  font-size: 14px;
  font-weight: 700;
  text-decoration: none;
  border: 1px solid transparent;
  cursor: pointer;
  font-family: inherit;
}
.btn.primary {
  background: #2468f2;
  color: #fff;
}
.btn.ghost {
  background: #f6f9fe;
  color: #2468f2;
  border-color: #d7e4fb;
}
</style>
