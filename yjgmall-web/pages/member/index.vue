<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '商务中心' })

const route = useRoute()
const { api } = useApi()
const { user, isEnterprise, caps } = useAuth()

const { data, refresh } = useMemberData('member-dashboard', () => api('/member/dashboard'))
const dash = computed(() => (data.value as any)?.data?.stats || {})
const me = computed(() => (data.value as any)?.data?.user || user.value || {})

const note = ref('')
onMounted(() => {
  note.value = localStorage.getItem('yjgmall_biz_note') || ''
})
async function saveNote() {
  localStorage.setItem('yjgmall_biz_note', note.value)
  await appOk('便笺已保存。')
}

const pending = computed(() => me.value?.status === 'pending' || me.value?.group_code === 'pending')
const needEnterprise = computed(() => String(route.query.need || '') === 'enterprise')
const publishTo = computed(() => (caps.value.supply ? '/member/supplies/create' : '/member/purchases/create'))
</script>

<template>
  <div>
    <div v-if="pending" class="mc-warn">
      尊敬的会员，您的帐号<span style="color:#c00">正在审核中</span>，审核通过之前部分功能可能受限。
    </div>
    <div v-if="needEnterprise" class="mc-warn">
      供应发布、商品、旺铺、卖出订单等仅对企业会员开放。个人会员可发采购、下单询盘。需要卖家能力请
      <NuxtLink to="/member/grade">升级为企业会员</NuxtLink>。
    </div>

    <div class="mc-card">
      <div class="mc-profile">
        <div class="mc-avatar">
          <img v-if="me.avatar" :src="me.avatar" alt="">
          <span v-else>头</span>
        </div>
        <div class="mc-profile-main">
          <div class="mc-hello">
            欢迎，{{ me.name || me.username }}
            <small>（{{ me.username }}）</small>
          </div>
          <div class="mc-id">用户ID <b>{{ me.id || '-' }}</b><small>注册后自动分配，加好友时发给对方</small></div>
          <div class="mc-meta">
            <span class="tag">{{ isEnterprise ? '企业会员' : '个人会员' }} · {{ me.group_label || '会员' }}</span>
            <span class="v" :class="{ on: me.mobile_verified }">手机</span>
            <span class="v" :class="{ on: me.bank_verified }">银行</span>
            <span class="v" :class="{ on: me.truename_verified }">实名</span>
            <NuxtLink class="btn-up" to="/member/grade">会员升级</NuxtLink>
          </div>
          <div class="mc-money">
            账户余额 <b>¥{{ dash.balance ?? me.balance ?? '0.00' }}</b>
            <NuxtLink to="/member/finance">流水</NuxtLink>
            <NuxtLink to="/member/cash">提现</NuxtLink>
            <NuxtLink class="btn-charge" to="/member/finance">充值</NuxtLink>
          </div>
        </div>
      </div>

      <div class="mc-quick">
        <NuxtLink class="q" to="/member/cart"><b>{{ dash.cart ?? 0 }}</b><span>购物车</span></NuxtLink>
        <NuxtLink class="q" to="/member/messages"><b>{{ dash.messages ?? 0 }}</b><span>新通知</span></NuxtLink>
        <NuxtLink class="q" to="/member/deposit"><b>{{ dash.deposit ?? '0.00' }}</b><span>保证金</span></NuxtLink>
        <NuxtLink class="q" to="/member/credit"><b>{{ dash.points ?? me.points ?? 0 }}</b><span>积分</span></NuxtLink>
        <NuxtLink class="q action" :to="publishTo"><b>+</b><span>{{ isEnterprise ? '发布供应' : '发布采购' }}</span></NuxtLink>
        <NuxtLink class="q action" to="/member/orders"><b>☰</b><span>我的订单</span></NuxtLink>
        <NuxtLink v-if="isEnterprise" class="q action" to="/member/shop"><b>⌂</b><span>商铺管理</span></NuxtLink>
        <NuxtLink v-else class="q action" to="/member/grade"><b>↑</b><span>升级企业</span></NuxtLink>
      </div>
    </div>

    <div class="mc-card" style="margin-top:12px">
      <div class="mc-sec-title">系统消息</div>
      <div class="muted" style="padding:12px 16px">暂无消息</div>
    </div>

    <div class="mc-card" style="margin-top:12px">
      <div class="mc-sec-title">商务便笺</div>
      <div style="padding:12px 16px">
        <textarea v-model="note" maxlength="5000" rows="5" style="width:100%;padding:8px;border:1px solid #dde3ea;font:inherit" placeholder="个人记事本，仅自己可见" />
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px">
          <span class="muted">{{ note.length }}/5000</span>
          <div>
            <button class="btn" type="button" @click="saveNote">更新</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.mc-warn {
  background: #fff8dc;
  border: 1px solid #f0e0a0;
  color: #666;
  padding: 10px 14px;
  margin-bottom: 10px;
  font-size: 14px;
}
.mc-card {
  background: #fff;
  border: 1px solid #cfd8e3;
}
.mc-profile {
  display: flex;
  gap: 18px;
  padding: 18px 20px;
  border-bottom: 1px solid #f0f0f0;
}
.mc-id { margin-top: 6px; font-size: 13px; color: #5a6b85; }
.mc-id b { color: #123c8f; font-size: 16px; margin: 0 6px; }
.mc-id small { color: #98a5bc; }
.mc-avatar {
  width: 78px;
  height: 78px;
  border-radius: 4px;
  background: #eef2f7;
  border: 1px solid #dde3ea;
  display: grid;
  place-items: center;
  color: #99a;
  overflow: hidden;
  flex: none;
  font-size: 16px;
}
.mc-avatar img { width: 100%; height: 100%; object-fit: cover; }
.mc-hello { font-size: 18px; font-weight: 700; }
.mc-hello small { color: #888; font-weight: 400; font-size: 14px; }
.mc-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  margin-top: 10px;
  font-size: 13px;
}
.tag {
  background: #eef4ff;
  color: #2468f2;
  padding: 3px 9px;
  border-radius: 6px;
}
.v {
  padding: 2px 7px;
  border: 1px solid #ddd;
  color: #999;
  border-radius: 2px;
}
.v.on { border-color: #52c41a; color: #389e0d; background: #f6ffed; }
.btn-up {
  background: #2468f2;
  color: #fff !important;
  padding: 4px 12px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
}
.mc-money {
  margin-top: 12px;
  font-size: 14px;
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: center;
}
.mc-money b { color: #c00; font-size: 22px; }
.mc-money a { color: #2468f2; font-size: 14px; }
.btn-charge {
  background: #2468f2 !important;
  color: #fff !important;
  padding: 5px 14px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
}
.mc-quick {
  display: grid;
  grid-template-columns: repeat(8, minmax(0, 1fr));
  gap: 0;
}
.q {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 16px 8px;
  border-right: 1px solid #f0f0f0;
  text-align: center;
  min-height: 86px;
}
.q:last-child { border-right: 0; }
.q b { font-size: 20px; color: #2468f2; line-height: 1.2; }
.q span { font-size: 13px; color: #555; }
.q.action b { font-size: 24px; color: #2468f2; }
.mc-sec-title {
  padding: 12px 16px;
  border-bottom: 1px solid #f0f0f0;
  font-weight: 700;
  font-size: 15px;
  color: #333;
  background: #fafbfc;
}
@media (max-width: 960px) {
  .mc-quick { grid-template-columns: repeat(4, 1fr); }
  .q:nth-child(4n) { border-right: 0; }
}
</style>
