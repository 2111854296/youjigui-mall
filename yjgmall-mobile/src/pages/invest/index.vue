<script setup lang="ts">
import { reactive, ref, computed } from "vue";
import { onLoad, onReachBottom, onPullDownRefresh } from "@dcloudio/uni-app";
import { api, getToken } from "@/utils/request";
import { errMsg } from "@/utils/auth";

const items = ref<any[]>([]);
const page = ref(1);
const totalPages = ref(1);
const loading = ref(false);
const submitting = ref(false);
const finished = computed(() => page.value >= totalPages.value);
const form = reactive({
  invest_project_id: null as number | null,
  company_name: "",
  contact_name: "",
  contact_phone: "",
  content: "",
});
const projectNames = computed(() => ["综合咨询", ...items.value.map((x) => x.title)]);
const projectIndex = ref(0);

async function fetchList(reset = false) {
  if (loading.value) return;
  if (reset) {
    page.value = 1;
    items.value = [];
  }
  loading.value = true;
  try {
    const res = await api<any>("/invest", { query: { page: page.value } });
    const list = res?.data || [];
    items.value = reset ? list : items.value.concat(list);
    totalPages.value = Number(res?.meta?.last_page || res?.meta?.pages || 1);
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

function goDetail(id: number | string) {
  uni.navigateTo({ url: `/pages/invest/detail?id=${id}` });
}

function onPickProject(e: any) {
  const i = Number(e.detail.value);
  projectIndex.value = i;
  form.invest_project_id = i === 0 ? null : items.value[i - 1]?.id ?? null;
}

async function apply() {
  if (!form.contact_name.trim() || !form.contact_phone.trim()) {
    uni.showToast({ title: "请填写联系人与电话", icon: "none" });
    return;
  }
  submitting.value = true;
  try {
    await api("/invest/apply", {
      method: "POST",
      body: { ...form },
      auth: Boolean(getToken()),
    });
    uni.showToast({ title: "招商意向已提交", icon: "success" });
    form.content = "";
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "提交失败"), icon: "none" });
  } finally {
    submitting.value = false;
  }
}

onLoad((q) => {
  if (q?.id) {
    form.invest_project_id = Number(q.id) || null;
  }
  fetchList(true);
});
onReachBottom(() => {
  if (finished.value || loading.value) return;
  page.value += 1;
  fetchList();
});
onPullDownRefresh(() => fetchList(true));
</script>

<template>
  <view class="page">
    <view
      v-for="(item, i) in items"
      :key="item.id || i"
      class="row card"
      @click="goDetail(item.id)"
    >
      <text class="title">{{ item.title }}</text>
      <text class="sub muted">{{ item.area || "地区不限" }} · {{ item.amount || "金额面议" }}</text>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无项目</text>
    </view>

    <view class="card form">
      <text class="sec-title">提交合作意向</text>
      <picker :range="projectNames" :value="projectIndex" @change="onPickProject">
        <view class="picker">意向项目：{{ projectNames[projectIndex] || "综合咨询" }}</view>
      </picker>
      <view class="gap" />
      <u-input v-model="form.company_name" placeholder="公司" border="surround" />
      <view class="gap" />
      <u-input v-model="form.contact_name" placeholder="联系人 *" border="surround" />
      <view class="gap" />
      <u-input v-model="form.contact_phone" placeholder="电话 *" border="surround" />
      <view class="gap" />
      <u-textarea v-model="form.content" placeholder="说明" height="120" border="surround" />
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="提交意向" :loading="submitting" @click="apply" />
    </view>
  </view>
</template>

<style lang="scss" scoped>
.row { padding: 24rpx 20rpx; }
.title { font-size: 30rpx; font-weight: 600; display: block; }
.sub { display: block; margin-top: 8rpx; font-size: 24rpx; }
.footer-tip { text-align: center; padding: 16rpx; font-size: 24rpx; }
.form { padding: 28rpx 24rpx; margin: 16rpx 20rpx 32rpx; }
.sec-title { font-size: 30rpx; font-weight: 700; display: block; margin-bottom: 20rpx; }
.picker {
  padding: 20rpx; background: #f7f8fa; border-radius: 8rpx; font-size: 26rpx; color: #333;
}
.gap { height: 20rpx; }
</style>
