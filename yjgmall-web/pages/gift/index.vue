<script setup lang="ts">
definePageMeta({ layout: 'channel' })
useHead({ title: '积分换礼' })
const config = useRuntimeConfig()
const { data } = await useAsyncData('gift', () => $fetch(`${config.public.apiBase}/gift`))
const items = computed(() => (data.value as any)?.data || [])
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <span class="on">积分换礼</span>
          <NuxtLink to="/member">我的积分</NuxtLink>
        </div>
      </div>
    </div>

    <div class="tip-bar">
      <div class="container">
        <b>兑换提示：</b>
        积分礼品库存有限，兑换成功后请留意收货信息。
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div class="list-panel">
          <div class="lp-hd"><h1>积分礼品</h1></div>
          <div class="goods-grid">
            <NuxtLink v-for="item in items" :key="item.id" class="g-card" :to="`/gift/${item.id}`">
              <div class="g-name">{{ item.title }}</div>
              <div class="g-price">{{ item.points }} <small>积分</small></div>
              <div class="g-meta"><span>库存 {{ item.stock }}</span></div>
            </NuxtLink>
          </div>
          <p v-if="!items.length" class="empty">暂无礼品</p>
        </div>
      </div>
    </div>
  </div>
</template>
