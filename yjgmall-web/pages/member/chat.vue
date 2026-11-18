<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '站内交谈' })
</script>

<template>
  <div class="panel chat-page">
    <h3>站内交谈</h3>
    <div class="body">
      <p class="chat-off">站内交谈已关闭。</p>
      <p class="muted">如需联系企业，请通过供应/商品/旺铺页提交<strong>询盘留言</strong>，由官方后台审核后再决定是否转达企业。</p>
      <p style="margin-top:16px">
        <NuxtLink class="btn" to="/member/inquiries">我的询盘</NuxtLink>
        <NuxtLink class="btn-soft-link" to="/member" style="margin-left:10px">返回会员中心</NuxtLink>
      </p>
    </div>
  </div>
</template>

<style scoped>
.chat-off {
  font-size: 15px;
  font-weight: 700;
  color: var(--ink);
  margin: 0 0 8px;
}
.muted { color: var(--ink-3); font-size: 13.5px; line-height: 1.6; }
</style>
