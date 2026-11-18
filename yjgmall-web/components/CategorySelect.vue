<script setup lang="ts">
const props = withDefaults(defineProps<{
  module?: string
}>(), {
  module: 'sell',
})

const config = useRuntimeConfig()
const { data } = await useAsyncData(
  () => `categories-${props.module}`,
  () => $fetch(`${config.public.apiBase}/categories`, { query: { module: props.module } }),
  { watch: [() => props.module] },
)
const cats = computed(() => (data.value as any)?.data || [])
const model = defineModel<string | number | null>({ default: null })

// native <select> + null 在 Vue3 下容易清不掉/回显错；统一成字符串，空为 ''
const inner = computed({
  get: () => (model.value == null || model.value === '' ? '' : String(model.value)),
  set: (v: string) => {
    model.value = v === '' ? null : (/^\d+$/.test(v) ? Number(v) : v)
  },
})
</script>

<template>
  <select v-model="inner">
    <option value="">请选择分类</option>
    <optgroup v-for="c in cats" :key="c.id" :label="c.name">
      <option v-for="ch in c.children" :key="ch.id" :value="String(ch.id)">{{ ch.name }}</option>
      <option v-if="!c.children?.length" :value="String(c.id)">{{ c.name }}</option>
    </optgroup>
  </select>
</template>
