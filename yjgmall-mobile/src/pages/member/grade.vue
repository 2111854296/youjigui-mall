<script setup lang="ts">
import { computed, reactive, ref } from "vue";
import { onShow } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";
import { useAuth } from "@/composables/useAuth";

const { fetchMe } = useAuth();
const grades = ref<any[]>([]);
const entitlement = ref<any>(null);
const loading = ref(false);
const submitting = ref(false);
const showApply = ref(false);
const form = reactive({
  vip_grade_id: null as number | null,
  company_name: "",
  contact_name: "",
  phone: "",
  mobile: "",
  email: "",
  qq: "",
  remark: "",
});

const paidGrades = computed(() => grades.value.filter((x) => Number(x.price_year) > 0));
const selectedGrade = computed(() => grades.value.find((x) => x.id === form.vip_grade_id) || null);

function perkList(g: any): string[] {
  const n = Number(g?.contact_quota_month || 0);
  const seats = Number(g?.seat_count || 1);
  const days = Number(g?.quote_history_days || 7);
  if (Number(g?.price_year) <= 0) {
    return ["基础行情与资讯", "认证后可获赠解锁次数"];
  }
  const items = [
    n > 0 ? `联系方式 ${n} 次/月` : "联系方式额度",
    days > 7 ? `行情回溯近${days <= 366 ? "1年" : days <= 800 ? "2年" : "5年"}` : "行情近7天",
  ];
  if (seats > 1) items.push(`企业席位 ${seats} 人`);
  if (g?.badge) items.push(`${g.badge} 官方标识`);
  return items.slice(0, 4);
}

onShow(async () => {
  if (!ensureLogin()) return;
  loading.value = true;
  try {
    const [gRes, me, vipMe] = await Promise.all([
      api<any>("/vip/grades", { auth: false }),
      fetchMe(true),
      api<any>("/vip/me").catch(() => null),
    ]);
    grades.value = gRes?.data || [];
    entitlement.value = vipMe?.data || vipMe || (me as any)?.vip_entitlement || null;
    form.company_name = me?.company_name || "";
    form.contact_name = me?.name || "";
    form.mobile = me?.mobile || "";
    form.email = (me as any)?.email || "";
    form.qq = (me as any)?.qq || "";
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    loading.value = false;
  }
});

function goVip(path: string) {
  uni.navigateTo({ url: path });
}

function openApply(id: number) {
  form.vip_grade_id = id;
  showApply.value = true;
}

function closeApply() {
  showApply.value = false;
}

function onPickGrade(e: any) {
  const list = paidGrades.value;
  form.vip_grade_id = list[Number(e.detail.value)]?.id || null;
}

function gradeLabel() {
  const g = selectedGrade.value;
  return g ? `${g.name}（¥${g.price_year}/年）` : "请选择";
}

async function submit() {
  if (!form.vip_grade_id || !form.contact_name.trim()) {
    uni.showToast({ title: "请选择会员组并填写联系人", icon: "none" });
    return;
  }
  submitting.value = true;
  try {
    await api("/vip/apply", { method: "POST", body: { ...form } });
    uni.showToast({ title: "申请已提交", icon: "success" });
    setTimeout(() => closeApply(), 800);
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "提交失败"), icon: "none" });
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <view class="page">
    <view v-if="entitlement" class="card status">
      <view class="section">我的权益</view>
      <text class="status-line">
        当前档位 <text class="em">{{ entitlement.grade_name || "未开通" }}</text>
        <text v-if="entitlement.badge"> · {{ entitlement.badge }}</text>
        <text v-if="entitlement.vip_expire_at"> · 到期 {{ String(entitlement.vip_expire_at).slice(0, 10) }}</text>
      </text>
      <text class="status-line muted">
        本月联系方式：已用 {{ entitlement.contact_used_month ?? 0 }} /
        配额 {{ entitlement.contact_quota_month ?? 0 }}
        · 剩余 {{ entitlement.contact_remain_total ?? 0 }}
        <text v-if="entitlement.contact_bonus_left">（含赠送 {{ entitlement.contact_bonus_left }}）</text>
      </text>
      <text class="status-line muted">行情可回溯 {{ entitlement.quote_history_days || 7 }} 天</text>
      <view class="vip-links">
        <text class="vlink" @click="goVip('/pages/member/seats')">席位管理</text>
        <text class="dot">·</text>
        <text class="vlink" @click="goVip('/pages/member/invite')">拉新邀请</text>
        <text class="dot">·</text>
        <text class="vlink" @click="goVip('/pages/member/contact-packs')">购买次数包</text>
      </view>
    </view>

    <view class="card">
      <view class="section">选择会员档位</view>
      <view class="lead">点击对应档位「申请升级」填写资料。</view>
      <view v-if="loading" class="muted tip">加载中...</view>
      <view
        v-for="g in grades"
        :key="g.id"
        class="tier"
        :class="{ on: showApply && form.vip_grade_id === g.id }"
      >
        <view class="tier-hd">
          <text class="g-name">{{ g.name }}</text>
          <text class="g-price">{{ Number(g.price_year) > 0 ? `¥${g.price_year}/年` : "免费" }}</text>
        </view>
        <text v-for="(p, i) in perkList(g)" :key="i" class="g-perk muted">· {{ p }}</text>
        <view
          v-if="Number(g.price_year) > 0"
          class="tier-btn"
          @click="openApply(g.id)"
        >
          申请升级
        </view>
        <text v-else class="free-tip muted">当前可免费使用</text>
      </view>
    </view>

    <view v-if="showApply" class="mask" @click="closeApply">
      <view class="sheet" @click.stop>
        <view class="sheet-hd">
          <text class="sheet-title">申请升级付费会员</text>
          <text class="sheet-x" @click="closeApply">×</text>
        </view>
        <view v-if="selectedGrade" class="pick">
          申请档位：{{ selectedGrade.name }} · ¥{{ selectedGrade.price_year }}/年
        </view>
        <picker :range="paidGrades" range-key="name" @change="onPickGrade">
          <view class="picker-row"><text class="muted">升级会员组</text><text class="val">{{ gradeLabel() }}</text></view>
        </picker>
        <view class="gap" />
        <u-input v-model="form.company_name" placeholder="公司全称" border="surround" />
        <view class="gap" />
        <u-input v-model="form.contact_name" placeholder="联系人 *" border="surround" />
        <view class="gap" />
        <u-input v-model="form.mobile" placeholder="手机" border="surround" />
        <view class="gap" />
        <u-input v-model="form.phone" placeholder="电话" border="surround" />
        <view class="gap" />
        <u-input v-model="form.email" placeholder="Email" border="surround" />
        <view class="gap" />
        <u-input v-model="form.qq" placeholder="QQ" border="surround" />
        <view class="gap" />
        <u-textarea v-model="form.remark" placeholder="附言" height="100" border="surround" />
        <view class="gap" />
        <u-button type="primary" color="#2769dc" text="提交升级申请" :loading="submitting" @click="submit" />
      </view>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.card { margin: 20rpx; padding: 28rpx; background: #fff; border-radius: 16rpx; }
.section {
  font-size: 28rpx; font-weight: 700; padding-left: 14rpx;
  border-left: 6rpx solid #2769dc; margin-bottom: 12rpx;
}
.status-line { display: block; font-size: 26rpx; margin-bottom: 8rpx; line-height: 1.45; color: #374151; }
.status-line .em { font-weight: 700; color: #1f2937; }
.vip-links { display: flex; flex-wrap: wrap; align-items: center; gap: 8rpx; margin-top: 14rpx; }
.vlink { font-size: 26rpx; color: #2769dc; font-weight: 600; }
.dot { color: #9ca3af; }
.lead { font-size: 24rpx; color: #6b7280; margin-bottom: 16rpx; line-height: 1.4; }
.tip { padding: 12rpx 0; }
.tier {
  padding: 20rpx 18rpx;
  border: 2rpx solid #e8edf3;
  border-radius: 14rpx;
  margin-bottom: 16rpx;
}
.tier.on { border-color: #2769dc; background: #f5f8ff; }
.tier-hd { display: flex; justify-content: space-between; align-items: baseline; gap: 12rpx; }
.g-name { font-size: 28rpx; font-weight: 700; color: #1f2937; }
.g-price { color: #2769dc; font-size: 26rpx; font-weight: 700; }
.g-perk { display: block; font-size: 22rpx; margin-top: 6rpx; }
.tier-btn {
  margin-top: 16rpx;
  text-align: center;
  background: #2769dc;
  color: #fff;
  font-size: 26rpx;
  font-weight: 700;
  padding: 16rpx 0;
  border-radius: 12rpx;
}
.free-tip { display: block; margin-top: 12rpx; font-size: 22rpx; }
.pick { font-size: 24rpx; color: #374151; margin-bottom: 14rpx; }
.gap { height: 18rpx; }
.picker-row {
  display: flex; align-items: center; gap: 12rpx;
  padding: 20rpx 16rpx; border: 1rpx solid #dadbde; border-radius: 8rpx;
}
.val { margin-left: auto; font-weight: 600; }
.muted { color: #6b7280; }
.mask {
  position: fixed;
  inset: 0;
  z-index: 1000;
  background: rgba(15, 28, 52, 0.45);
  display: flex;
  align-items: flex-end;
  justify-content: center;
}
.sheet {
  width: 100%;
  max-height: 86vh;
  overflow-y: auto;
  background: #fff;
  border-radius: 24rpx 24rpx 0 0;
  padding: 28rpx 28rpx calc(28rpx + env(safe-area-inset-bottom));
  box-sizing: border-box;
}
.sheet-hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12rpx;
}
.sheet-title {
  font-size: 30rpx;
  font-weight: 700;
  padding-left: 14rpx;
  border-left: 6rpx solid #2769dc;
}
.sheet-x { font-size: 44rpx; color: #9ca3af; line-height: 1; padding: 0 8rpx; }
</style>
