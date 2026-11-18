<script setup lang="ts">
import { reactive, ref, watch } from "vue";
import { onShow, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureEnterprise, errMsg } from "@/utils/auth";
import ImageUpload from "@/components/ImageUpload/ImageUpload.vue";
import { useAuth } from "@/composables/useAuth";

const { user, fetchMe } = useAuth();
const positions = ref<any[]>([]);
const bookings = ref<any[]>([]);
const loading = ref(false);
const submitting = ref(false);
const form = reactive({
  place_code: "",
  title: "",
  image: "",
  contact_name: "",
  contact_phone: "",
  remark: "",
});

watch(
  () => user(),
  (u) => {
    if (!u) return;
    if (!form.contact_name) form.contact_name = u.name || "";
    if (!form.contact_phone) form.contact_phone = u.mobile || "";
  },
  { immediate: true },
);

async function load() {
  if (!(await ensureEnterprise({ tip: "广告预定仅对企业会员开放，请先升级为企业会员" }))) return;
  loading.value = true;
  try {
    await fetchMe();
    const [posRes, bookRes] = await Promise.all([
      api<any>("/ad-positions"),
      api<any>("/member/ad-bookings"),
    ]);
    positions.value = posRes?.data || [];
    bookings.value = bookRes?.data || [];
    if (!form.place_code && positions.value.length) {
      form.place_code = positions.value[0].code;
    }
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

async function submit() {
  if (!(await ensureEnterprise({ tip: "广告预定仅对企业会员开放，请先升级为企业会员" }))) return;
  if (!form.place_code || !form.title.trim()) {
    uni.showToast({ title: "请填写广告位和标题", icon: "none" });
    return;
  }
  if (!form.contact_name.trim() || !form.contact_phone.trim()) {
    uni.showToast({ title: "请填写联系人与电话", icon: "none" });
    return;
  }
  submitting.value = true;
  try {
    await api("/member/ad-bookings", { method: "POST", body: { ...form } });
    uni.showToast({ title: "预定已提交", icon: "success" });
    form.title = "";
    form.image = "";
    form.remark = "";
    await load();
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "提交失败"), icon: "none" });
  } finally {
    submitting.value = false;
  }
}

onShow(load);
onPullDownRefresh(load);
</script>

<template>
  <view class="page">
    <view class="card sec">
      <text class="sec-title">广告位一览</text>
      <view v-for="p in positions" :key="p.code" class="pos">
        <text class="name">{{ p.name }}</text>
        <text class="meta muted">{{ p.code }} · {{ p.size || "—" }} · 在投 {{ p.active_count ?? 0 }}</text>
      </view>
      <text v-if="!loading && !positions.length" class="muted tip">暂无广告位</text>
    </view>

    <view class="card sec">
      <text class="sec-title">广告预定</text>
      <view class="gap" />
      <picker
        :range="positions"
        range-key="name"
        @change="(e: any) => { const i = Number(e.detail.value); form.place_code = positions[i]?.code || '' }"
      >
        <view class="row">
          <text class="label">广告位</text>
          <text class="val">{{ positions.find((p) => p.code === form.place_code)?.name || "请选择" }}</text>
          <u-icon name="arrow-right" color="#ccc" size="14" />
        </view>
      </picker>
      <view class="gap" />
      <u-input v-model="form.title" placeholder="投放标题（必填）" border="surround" />
      <view class="gap" />
      <ImageUpload v-model="form.image" label="物料图片" hint="上传广告物料图，方便审核投放" />
      <view class="gap" />
      <u-input v-model="form.contact_name" placeholder="联系人（必填）" border="surround" />
      <view class="gap" />
      <u-input v-model="form.contact_phone" placeholder="电话（必填）" border="surround" />
      <view class="gap" />
      <u-textarea v-model="form.remark" placeholder="备注" height="120" border="surround" />
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="提交预定" :loading="submitting" @click="submit" />
    </view>

    <view v-if="bookings.length" class="card sec">
      <text class="sec-title">我的预定</text>
      <view v-for="b in bookings" :key="b.id" class="pos">
        <text class="name">{{ b.title }}</text>
        <text class="meta muted">{{ b.place_code }} · {{ b.status }}</text>
      </view>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.sec { margin: 20rpx; padding: 24rpx; }
.sec-title {
  display: block;
  font-size: 28rpx;
  font-weight: 700;
  color: #1a1a1a;
  padding-left: 14rpx;
  border-left: 6rpx solid #2769dc;
  margin-bottom: 16rpx;
}
.pos { padding: 12rpx 0; border-bottom: 1rpx solid #f0f0f0; }
.pos:last-child { border-bottom: 0; }
.name { display: block; font-size: 28rpx; color: #222; }
.meta { display: block; font-size: 22rpx; margin-top: 6rpx; }
.gap { height: 18rpx; }
.tip { font-size: 24rpx; }
.row {
  display: flex; align-items: center; gap: 12rpx;
  padding: 20rpx 16rpx; border: 1rpx solid #dadbde; border-radius: 8rpx;
}
.label { color: #666; width: 120rpx; }
.val { flex: 1; color: #333; }
</style>
