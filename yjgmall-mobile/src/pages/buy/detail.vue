<script setup lang="ts">
import { computed, reactive, ref } from "vue";
import { onLoad } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { memberBadgeUrl } from "@/utils/memberBadge";
import { useAuth } from "@/composables/useAuth";
import { ensureLogin, errMsg } from "@/utils/auth";
import { contentNodes } from "@/utils/content";
import { openChat } from "@/utils/chat";

const { fetchMe, token, addFavorite } = useAuth();
const item = ref<any>(null);
const badgeSrc = computed(() => memberBadgeUrl(item.value?.user?.member_grade));
const loading = ref(true);
const submitting = ref(false);
const unlockLoading = ref(false);
const unlockErr = ref("");
const unlockedContact = ref<any>(null);
const id = ref("");
const showQuote = ref(false);
const form = reactive({
  price: "",
  unit: "吨",
  quantity: "",
  content: "",
  contact_name: "",
  contact_phone: "",
});

const qtyText = computed(() => {
  const q = String(item.value?.quantity || item.value?.amount || "").trim();
  const u = String(item.value?.unit || "").trim();
  if (!q) return "面议";
  if (!u || q.includes(u)) return q;
  return `${q} ${u}`;
});

const areaText = computed(() => {
  const direct = item.value?.area || item.value?.address || item.value?.city;
  if (direct) return String(direct);
  const raw = String(item.value?.content || item.value?.introduce || "");
  const m = raw.match(/(?:交货地|交货地址|收货地|地区)[：:\s]*([^\n\r<，,；;]{2,30})/);
  if (m?.[1]) return m[1].trim();
  return "地区不限";
});

const daysText = computed(() => {
  const d = item.value?.days;
  if (d) return `${d}天内`;
  if (item.value?.delivery || item.value?.lead_time) {
    return String(item.value.delivery || item.value.lead_time);
  }
  const raw = String(item.value?.content || "");
  const m = raw.match(/(?:交期|交货期)[：:\s]*([^\n\r<，,；;]{1,20})/);
  if (m?.[1]) return m[1].trim();
  return "待定";
});

const displayMobile = computed(() => {
  if (unlockedContact.value?.mobile) return unlockedContact.value.mobile;
  if (item.value?.contact_unlocked) return item.value?.user?.mobile || "—";
  return item.value?.user?.mobile || "未解锁";
});

const needUnlock = computed(
  () => !item.value?.contact_unlocked && !unlockedContact.value,
);

const contactUnlocked = computed(
  () => !!item.value?.contact_unlocked || !!unlockedContact.value,
);

onLoad(async (q) => {
  if (!q?.id) return;
  id.value = String(q.id);
  try {
    const headers = token() ? { Authorization: `Bearer ${token()}` } : undefined;
    const res = await api<any>(`/buy/${id.value}`, { headers, auth: !!token() });
    item.value = res?.data || res;
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
  }
  if (token()) {
    const u = await fetchMe();
    if (u) {
      form.contact_name = u.name || u.username || "";
      form.contact_phone = u.mobile || "";
    }
  }
});

function openQuote() {
  if (!ensureLogin()) return;
  showQuote.value = true;
}

function closeQuote() {
  showQuote.value = false;
  unlockErr.value = "";
}

async function unlockContact() {
  if (!ensureLogin()) return;
  unlockLoading.value = true;
  unlockErr.value = "";
  try {
    const res = await api<any>("/vip/unlock-contact", {
      method: "POST",
      body: { target_type: "purchase", target_id: Number(id.value) },
    });
    unlockedContact.value = res?.data?.contact || res?.contact;
    uni.showToast({ title: "已解锁", icon: "success" });
    const r2 = await api<any>(`/buy/${id.value}`, { auth: true });
    item.value = r2?.data || r2;
  } catch (e: any) {
    unlockErr.value = errMsg(e, "解锁失败，请升级会员或检查额度");
    uni.showToast({ title: unlockErr.value, icon: "none" });
  } finally {
    unlockLoading.value = false;
  }
}

async function submitQuote() {
  if (!form.content || !form.contact_name || !form.contact_phone) {
    uni.showToast({ title: "请填写联系人、电话和报价说明", icon: "none" });
    return;
  }
  submitting.value = true;
  try {
    await api(`/buy/${id.value}/quote`, {
      method: "POST",
      body: { ...form, price: form.price || null },
      auth: !!token(),
    });
    uni.showToast({ title: "报价已提交", icon: "success" });
    closeQuote();
    form.content = "";
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "提交失败"), icon: "none" });
  } finally {
    submitting.value = false;
  }
}

async function fav() {
  if (!ensureLogin()) return;
  try {
    await addFavorite("purchase", id.value);
    uni.showToast({ title: "已收藏", icon: "success" });
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  }
}

function chat() {
  if (!ensureLogin()) return;
  openChat(item.value?.user?.id);
}

function goGrade() {
  uni.navigateTo({ url: "/pages/member/grade" });
}
</script>

<template>
  <view class="page">
    <view v-if="loading" class="center"><u-loading-icon color="#2769dc" /></view>
    <template v-else-if="item">
      <view class="card head">
        <text class="title">
          {{ item.title }}
          <text v-if="item.is_urgent || item.urgent" class="urgent">急</text>
        </text>
        <view class="sub muted">
          <text v-if="item.category?.name">{{ item.category.name }}</text>
          <text v-if="item.category?.name"> · </text>
          <text>浏览 {{ item.views ?? item.hits ?? 0 }}</text>
          <text> · 报价 {{ item.quotes_count ?? 0 }}</text>
        </view>
        <text class="budget" :class="{ ask: !item.budget }">
          {{ item.budget ? `预算 ¥${item.budget}` : "预算面议" }}
        </text>
        <view class="kv">
          <view class="kv-row">
            <text class="k muted">数量</text>
            <text class="v">{{ qtyText }}</text>
          </view>
          <view class="kv-row">
            <text class="k muted">地区</text>
            <text class="v">{{ areaText }}</text>
          </view>
          <view class="kv-row">
            <text class="k muted">交期</text>
            <text class="v">{{ daysText }}</text>
          </view>
        </view>
      </view>

      <view class="card seller">
        <view class="section-hd" style="padding-left:0;padding-right:0;padding-top:0">
          <text class="title">采购商信息</text>
        </view>
        <view class="kv">
          <view class="kv-row">
            <text class="k muted">公司</text>
            <view class="v co-line">
              <text>{{ item.user?.company_name || item.user?.name || "—" }}</text>
              <image v-if="badgeSrc" class="seller-badge" :src="badgeSrc" mode="heightFix" />
            </view>
          </view>
          <view class="kv-row">
            <text class="k muted">联系人</text>
            <text class="v">{{ item.user?.name || "—" }}</text>
          </view>
          <view class="kv-row">
            <text class="k muted">手机</text>
            <text class="v" :class="{ blurred: needUnlock }">{{ displayMobile }}</text>
          </view>
        </view>

        <view v-if="needUnlock" class="unlock">
          <text class="hint muted">完整号码按会员档位计次解锁，同一采购不重复扣次</text>
          <text v-if="unlockErr" class="err">{{ unlockErr }}</text>
          <view class="unlock-btn" :class="{ disabled: unlockLoading }" @click="unlockContact">
            {{ unlockLoading ? "解锁中…" : "解锁联系方式" }}
          </view>
          <text class="grade-link" @click="goGrade">查看会员权益 ›</text>
        </view>
        <view v-else-if="contactUnlocked" class="unlocked-tip muted">已解锁完整联系方式</view>
      </view>

      <view class="card">
        <view class="section-hd" style="padding-left:0;padding-right:0;padding-top:0">
          <text class="title">采购说明</text>
        </view>
        <rich-text class="content" :nodes="contentNodes(item.content || item.introduce || '暂无详细说明')" />
      </view>

      <view class="tip-card card">
        <text class="tip-title">温馨提示</text>
        <text class="tip-line muted">· 报价请如实填写交期与货源</text>
        <text class="tip-line muted">· 优先通过平台沟通更安全</text>
        <text class="tip-line muted">· 警惕脱离平台的私下转账</text>
      </view>

      <view class="bottom-bar">
        <view class="bar-icon" @click="fav">
          <u-icon name="star" color="#2769dc" size="22" />
          <text>收藏</text>
        </view>
        <u-button
          type="primary"
          color="#2769dc"
          text="我要报价"
          customStyle="flex:1;height:76rpx;margin-left:12rpx"
          @click="openQuote"
        />
      </view>

      <view v-if="showQuote" class="quote-mask" @click="closeQuote">
        <view class="sheet" @click.stop>
          <view class="sheet-hd">
            <text class="sheet-title">提交报价</text>
            <text class="sheet-x" @click="closeQuote">×</text>
          </view>
          <u-input v-model="form.price" placeholder="报价金额（选填）" border="surround" />
          <view class="gap" />
          <u-input v-model="form.unit" placeholder="单位，如：吨" border="surround" />
          <view class="gap" />
          <u-input v-model="form.quantity" placeholder="可供数量" border="surround" />
          <view class="gap" />
          <u-input v-model="form.contact_name" placeholder="联系人 *" border="surround" />
          <view class="gap" />
          <u-input v-model="form.contact_phone" placeholder="电话 *" border="surround" />
          <view class="gap" />
          <u-textarea v-model="form.content" placeholder="请说明货源、交期等 *" height="120" border="surround" />
          <view class="gap" />
          <u-button type="primary" color="#2769dc" text="确认报价" :loading="submitting" @click="submitQuote" />
        </view>
      </view>
    </template>
    <view v-else class="center muted">信息不存在或已下架</view>
  </view>
</template>

<style lang="scss" scoped>
.center { padding: 80rpx; text-align: center; }
.head { padding: 28rpx 24rpx 20rpx; }
.title {
  font-size: 34rpx; font-weight: 700; display: block; line-height: 1.4; margin-bottom: 10rpx;
}
.urgent {
  display: inline-block; margin-left: 10rpx; padding: 2rpx 10rpx;
  font-size: 20rpx; font-weight: 700; color: #fff; background: #e11d48; border-radius: 6rpx;
  vertical-align: middle;
}
.sub { font-size: 22rpx; margin-bottom: 14rpx; }
.budget {
  display: block; font-size: 36rpx; font-weight: 800; color: #c2410c; margin-bottom: 18rpx;
}
.budget.ask { color: #2769dc; font-size: 32rpx; }
.kv { display: flex; flex-direction: column; gap: 12rpx; }
.kv-row { display: flex; align-items: flex-start; gap: 16rpx; }
.kv-row .k { width: 96rpx; flex-shrink: 0; font-size: 26rpx; }
.kv-row .v { flex: 1; font-size: 26rpx; color: #1f2937; line-height: 1.4; word-break: break-all; }
.co-line { display: flex; align-items: center; flex-wrap: wrap; gap: 8rpx; }
.seller-badge { height: 32rpx; width: 104rpx; }
.kv-row .v.blurred { color: #6b7280; letter-spacing: 1rpx; }

.seller { padding: 24rpx; }
.unlock {
  margin-top: 20rpx; padding-top: 18rpx; border-top: 1rpx solid #f1f4f7;
  display: flex; flex-direction: column; align-items: stretch; gap: 14rpx;
}
.hint { font-size: 22rpx; line-height: 1.45; }
.err { font-size: 24rpx; color: #dc2626; }
.unlock-btn {
  text-align: center; background: #2769dc; color: #fff;
  font-size: 28rpx; font-weight: 700; padding: 20rpx 0; border-radius: 12rpx;
}
.unlock-btn.disabled { opacity: 0.65; }
.grade-link { font-size: 24rpx; color: #2769dc; font-weight: 600; align-self: flex-start; }
.unlocked-tip { display: block; margin-top: 12rpx; font-size: 22rpx; }

.content { font-size: 28rpx; line-height: 1.65; color: #374151; display: block; padding: 0 4rpx 8rpx; }
.tip-card { padding: 24rpx; }
.tip-title { display: block; font-size: 26rpx; font-weight: 700; margin-bottom: 10rpx; }
.tip-line { display: block; font-size: 24rpx; line-height: 1.55; margin-top: 4rpx; }

.bottom-bar {
  position: fixed; left: 0; right: 0; bottom: 0; z-index: 100;
  display: flex; align-items: center;
  padding: 12rpx 20rpx calc(12rpx + env(safe-area-inset-bottom));
  background: #fff;
  box-shadow: 0 -8rpx 28rpx rgba(31, 56, 100, 0.08);
  border-top: 1rpx solid #e8edf3;
}
.bar-icon {
  width: 100rpx; display: flex; flex-direction: column; align-items: center;
  gap: 4rpx; font-size: 20rpx; color: #64748b;
}
.gap { height: 16rpx; }
.page { padding-bottom: 160rpx; }
.quote-mask {
  position: fixed; inset: 0; z-index: 1100;
  background: rgba(15, 28, 52, 0.48);
  display: flex; align-items: flex-end; justify-content: center;
}
.sheet {
  width: 100%; max-height: 86vh; overflow-y: auto; background: #fff;
  border-radius: 28rpx 28rpx 0 0;
  padding: 32rpx 28rpx calc(28rpx + env(safe-area-inset-bottom));
  box-sizing: border-box;
  box-shadow: 0 -12rpx 40rpx rgba(15, 28, 52, 0.12);
}
.sheet-hd { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20rpx; }
.sheet-title {
  font-size: 32rpx; font-weight: 700; padding-left: 16rpx; border-left: 6rpx solid #2769dc; color: #1f2937;
}
.sheet-x { font-size: 44rpx; color: #94a3b8; line-height: 1; padding: 0 8rpx; }
.muted { color: #64748b; }
</style>
