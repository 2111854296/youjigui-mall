<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '我的评价' })
const { api } = useApi()
const { data } = useMemberData('my-reviews', () => api('/member/reviews'))
const items = computed(() => (data.value as any)?.data || [])
</script>

<template>
  <div class="panel">
    <h3>我的商品评价</h3>
    <div class="body">
      <table class="table">
        <thead><tr><th>商品</th><th>评分</th><th>内容</th><th>时间</th></tr></thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>
              <NuxtLink v-if="item.product" :to="`/mall/${item.product.id}`">{{ item.product.title }}</NuxtLink>
              <span v-else>-</span>
            </td>
            <td>{{ item.rating }}星</td>
            <td>{{ item.content }}</td>
            <td class="muted">{{ item.created_at?.slice?.(0, 16) }}</td>
          </tr>
          <tr v-if="!items.length"><td colspan="4" class="muted">暂无评价</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
