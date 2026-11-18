<script setup lang="ts">
import { reactive, ref } from "vue";
import { onLoad, onShow } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";
import ImageUpload from "@/components/ImageUpload/ImageUpload.vue";

const loading = ref(false);
const editId = ref<number | null>(null);
const catLabel = ref("请选择分类");
const form = reactive({
  title: "",
  thumb: "",
  budget: "",
  quantity: "",
  unit: "t",
  area: "",
  days: "",
  is_urgent: false,
  summary: "",
  content: "",
  category_id: null as number | null,
});

onLoad(async (q: any) => {
  if (!ensureLogin()) return;
  const id = q?.id ? Number(q.id) : null;
  if (!id) return;
  editId.value = id;
  uni.setNavigationBarTitle({ title: "编辑采购" });
  loading.value = true;
  try {
    const res = await api<any>(`/member/purchases/${id}`);
    const s = res?.data || {};
    Object.assign(form, {
      title: s.title || "",
      thumb: s.thumb || "",
      budget: s.budget || "",
      quantity: s.quantity || "",
      unit: s.unit || "t",
      area: s.area || "",
      days: s.days || "",
      is_urgent: !!s.is_urgent,
      summary: s.summary || "",
      content: s.content || "",
      category_id: s.category_id || null,
    });
    if (s.category?.name) catLabel.value = s.category.name;
    else if (s.category_id) catLabel.value = `分类 #${s.category_id}`;
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
  }
});

onShow(() => {
  if (!ensureLogin()) return;
  const sc = uni.getStorageSync("pick_category");
  if (sc) {
    try {
      const o = typeof sc === "string" ? JSON.parse(sc) : sc;
      if (o?.target === "publish_purchase" && o?.id != null) {
        form.category_id = Number(o.id);
        catLabel.value = o.name || String(o.id);
      }
    } catch { /* ignore */ }
    uni.removeStorageSync("pick_category");
  }
});

function openCatPage() {
  uni.navigateTo({ url: "/pages/category/index?module=buy&target=publish_purchase" });
}

async function submit() {
  if (!ensureLogin()) return;
  if (!form.title.trim()) {
    uni.showToast({ title: "请填写标题", icon: "none" });
    return;
  }
  loading.value = true;
  try {
    const body = {
      ...form,
      budget: form.budget || null,
      days: form.days ? Number(form.days) : null,
      category_id: form.category_id || null,
      thumb: form.thumb || null,
    };
    if (editId.value) {
      await api(`/member/purchases/${editId.value}`, { method: "PUT", body });
    } else {
      await api("/member/purchases", { method: "POST", body });
    }
    uni.showToast({ title: editId.value ? "已保存" : "已提交", icon: "success" });
    setTimeout(() => uni.redirectTo({ url: "/pages/member/purchases" }), 400);
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "提交失败"), icon: "none" });
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <view class="page">
    <view class="card form">
      <view class="sec-title">基本信息</view>
      <view class="row" @click="openCatPage">
        <text class="label">分类</text>
        <text class="val">{{ catLabel }}</text>
        <u-icon name="arrow-right" color="#ccc" size="14" />
      </view>
      <view class="gap" />
      <u-input v-model="form.title" placeholder="标题（必填）如：急需 D4 现货" border="surround" />
      <view class="gap" />
      <view class="urgent" @click="form.is_urgent = !form.is_urgent">
        <view class="box" :class="{ on: form.is_urgent }" />
        <text>标记为紧急采购</text>
      </view>

      <view class="sec-title">封面图</view>
      <view class="pics one">
        <view class="pic"><ImageUpload v-model="form.thumb" label="标题图片" /></view>
      </view>

      <view class="sec-title">需求与预算</view>
      <u-input v-model="form.quantity" placeholder="采购数量" border="surround" />
      <view class="gap" />
      <u-input v-model="form.unit" placeholder="单位，如：吨 / kg" border="surround" />
      <view class="gap" />
      <u-input v-model="form.budget" placeholder="预算" border="surround" type="digit" />
      <view class="gap" />
      <u-input v-model="form.area" placeholder="地区" border="surround" />
      <view class="gap" />
      <u-input v-model="form.days" placeholder="交期（天）" border="surround" type="number" />

      <view class="sec-title">详细说明</view>
      <u-input v-model="form.summary" placeholder="一句话简介" border="surround" />
      <view class="gap" />
      <u-textarea v-model="form.content" placeholder="详细需求" height="180" border="surround" />
      <view class="gap" />
      <u-button type="primary" color="#2769dc" :text="editId ? '保存修改' : '提交发布'" :loading="loading" @click="submit" />
    </view>
  </view>
</template>

<style lang="scss" scoped>
.form { margin: 20rpx; padding: 28rpx; }
.gap { height: 18rpx; }
.sec-title {
  margin: 28rpx 0 16rpx;
  font-size: 28rpx;
  font-weight: 700;
  color: #1a1a1a;
  padding-left: 14rpx;
  border-left: 6rpx solid #2769dc;
}
.sec-title:first-child { margin-top: 0; }
.row {
  display: flex; align-items: center; gap: 12rpx;
  padding: 20rpx 16rpx; border: 1rpx solid #dadbde; border-radius: 8rpx;
}
.label { color: #666; width: 100rpx; }
.val { flex: 1; color: #333; }
.urgent { display: flex; align-items: center; gap: 12rpx; font-size: 28rpx; }
.box {
  width: 36rpx; height: 36rpx; border: 2rpx solid #c8c9cc; border-radius: 6rpx;
}
.box.on { background: #2769dc; border-color: #2769dc; }
.pics { display: flex; gap: 16rpx; }
.pics.one .pic { width: 220rpx; }
.pic { flex: 1; min-width: 0; }
</style>
