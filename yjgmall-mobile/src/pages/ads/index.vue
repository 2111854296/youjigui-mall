<script setup lang="ts">
import { reactive, ref, watch, computed } from "vue";
import { onLoad, onPullDownRefresh } from "@dcloudio/uni-app";
import { api, getToken } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";
import ImageUpload from "@/components/ImageUpload/ImageUpload.vue";

const positions = ref<any[]>([]);
const bookings = ref<any[]>([]);
const loading = ref(false);
const submitting = ref(false);
const loggedIn = computed(() => Boolean(getToken()));
const form = reactive({
  place_code: "",
  title: "",
  image: "",
  contact_name: "",
  contact_phone: "",
  remark: "",
});
const placeIndex = ref(0);
const placeNames = ref<string[]>([]);

watch(positions, (list) => {
  placeNames.value = list.map((p) => p.name);
  if (!form.place_code && list.length) {
    form.place_code = list[0].code;
    placeIndex.value = 0;
  }
});

async function load() {
  loading.value = true;
  try {
    const posRes = await api<any>("/ad-positions");
    positions.value = posRes?.data || [];
    if (getToken()) {
      try {
        const bookRes = await api<any>("/member/ad-bookings");
        bookings.value = bookRes?.data || [];
      } catch {
        bookings.value = [];
      }
    }
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

function onPickPlace(e: any) {
  const i = Number(e.detail.value);
  placeIndex.value = i;
  form.place_code = positions.value[i]?.code || "";
}

async function submit() {
  if (!ensureLogin()) return;
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

onLoad(load);
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
      <text v-if="!loggedIn" class="login-tip" @click="ensureLogin()">请先登录后再预定广告 ›</text>
      <picker :range="placeNames" :value="placeIndex" @change="onPickPlace">
        <view class="picker">广告位：{{ placeNames[placeIndex] || "请选择" }}</view>
      </picker>
      <view class="gap" />
      <u-input v-model="form.title" placeholder="投放标题 *" border="surround" />
      <view class="gap" />
      <ImageUpload v-model="form.image" label="物料图片" hint="上传广告物料图" />
      <view class="gap" />
      <u-input v-model="form.contact_name" placeholder="联系人 *" border="surround" />
      <view class="gap" />
      <u-input v-model="form.contact_phone" placeholder="电话 *" border="surround" />
      <view class="gap" />
      <u-textarea v-model="form.remark" placeholder="备注" height="100" border="surround" />
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="提交预定" :loading="submitting" @click="submit" />
    </view>

    <view v-if="bookings.length" class="card sec">
      <text class="sec-title">我的预定</text>
      <view v-for="b in bookings" :key="b.id" class="book">
        <text class="name">{{ b.title }}</text>
        <text class="meta muted">{{ b.place_code }} · {{ b.status }}</text>
      </view>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.sec { margin: 16rpx 20rpx; padding: 24rpx; }
.sec-title { font-size: 30rpx; font-weight: 700; display: block; margin-bottom: 16rpx; }
.pos { padding: 14rpx 0; border-bottom: 1rpx solid #f1f4f7; }
.pos:last-child { border-bottom: none; }
.name { display: block; font-size: 28rpx; font-weight: 600; }
.meta { display: block; margin-top: 6rpx; font-size: 22rpx; }
.tip { display: block; padding: 12rpx 0; font-size: 24rpx; }
.login-tip { display: block; color: #2769dc; font-size: 26rpx; margin-bottom: 16rpx; }
.picker {
  padding: 20rpx; background: #f7f8fa; border-radius: 8rpx; font-size: 26rpx;
}
.gap { height: 20rpx; }
.book { padding: 14rpx 0; border-bottom: 1rpx solid #f1f4f7; }
</style>
