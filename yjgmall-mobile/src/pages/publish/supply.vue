<script setup lang="ts">
import { reactive, ref } from "vue";
import { onLoad, onShow } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureEnterprise, errMsg } from "@/utils/auth";
import ImageUpload from "@/components/ImageUpload/ImageUpload.vue";

const loading = ref(false);
const gated = ref(false);
const editId = ref<number | null>(null);
const catLabel = ref("请选择分类");
const form = reactive({
  title: "",
  thumb: "",
  thumb1: "",
  thumb2: "",
  price: "",
  unit: "t",
  min_amount: "",
  amount: "",
  days: "",
  brand: "",
  area: "",
  attr1_name: "规格",
  attr1_value: "",
  attr2_name: "粘度",
  attr2_value: "",
  attr3_name: "含量",
  attr3_value: "",
  summary: "",
  content: "",
  category_id: null as number | null,
});

onLoad(async (q: any) => {
  if (!(await ensureEnterprise({ tip: "发布供应仅对企业会员开放，请先升级为企业会员" }))) {
    gated.value = true;
    return;
  }
  const id = q?.id ? Number(q.id) : null;
  if (!id) return;
  editId.value = id;
  uni.setNavigationBarTitle({ title: "编辑供应" });
  loading.value = true;
  try {
    const res = await api<any>(`/member/supplies/${id}`);
    const s = res?.data || {};
    Object.assign(form, {
      title: s.title || "",
      thumb: s.thumb || "",
      thumb1: s.thumb1 || "",
      thumb2: s.thumb2 || "",
      price: s.price || "",
      unit: s.unit || "t",
      min_amount: s.min_amount || "",
      amount: s.amount || "",
      days: s.days || "",
      brand: s.brand || "",
      area: s.area || "",
      attr1_name: s.attr1_name || "规格",
      attr1_value: s.attr1_value || "",
      attr2_name: s.attr2_name || "粘度",
      attr2_value: s.attr2_value || "",
      attr3_name: s.attr3_name || "含量",
      attr3_value: s.attr3_value || "",
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

onShow(async () => {
  if (gated.value) return;
  if (!(await ensureEnterprise())) {
    gated.value = true;
    return;
  }
  const sc = uni.getStorageSync("pick_category");
  if (sc) {
    try {
      const o = typeof sc === "string" ? JSON.parse(sc) : sc;
      if (o?.target === "publish_supply" && o?.id != null) {
        form.category_id = Number(o.id);
        catLabel.value = o.name || String(o.id);
      }
    } catch { /* ignore */ }
    uni.removeStorageSync("pick_category");
  }
});

function openCatPage() {
  uni.navigateTo({ url: "/pages/category/index?module=sell&target=publish_supply" });
}

async function submit() {
  if (!(await ensureEnterprise())) return;
  if (!form.title.trim()) {
    uni.showToast({ title: "请填写标题", icon: "none" });
    return;
  }
  loading.value = true;
  try {
    const body = {
      ...form,
      price: form.price || null,
      min_amount: form.min_amount || null,
      days: form.days || null,
      category_id: form.category_id || null,
      thumb: form.thumb || null,
      thumb1: form.thumb1 || null,
      thumb2: form.thumb2 || null,
    };
    if (editId.value) {
      await api(`/member/supplies/${editId.value}`, { method: "PUT", body });
    } else {
      await api("/member/supplies", { method: "POST", body });
    }
    uni.showToast({ title: editId.value ? "已保存" : "已提交", icon: "success" });
    setTimeout(() => uni.redirectTo({ url: "/pages/member/supplies" }), 400);
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
      <u-input v-model="form.title" placeholder="标题（必填）如：气相白炭黑 疏水型" border="surround" />
      <view class="gap" />
      <u-input v-model="form.brand" placeholder="品牌" border="surround" />
      <view class="gap" />
      <u-input v-model="form.area" placeholder="地区，如：广东东莞" border="surround" />

      <view class="sec-title">产品图片</view>
      <view class="pics">
        <view class="pic"><ImageUpload v-model="form.thumb" label="主图" /></view>
        <view class="pic"><ImageUpload v-model="form.thumb1" label="附图 1" /></view>
        <view class="pic"><ImageUpload v-model="form.thumb2" label="附图 2" /></view>
      </view>

      <view class="sec-title">价格与供货</view>
      <u-input v-model="form.price" placeholder="价格（面议可留空）" border="surround" type="digit" />
      <view class="gap" />
      <u-input v-model="form.unit" placeholder="单位，如：吨 / kg" border="surround" />
      <view class="gap" />
      <u-input v-model="form.days" placeholder="发货天数" border="surround" type="number" />
      <view class="gap" />
      <u-input v-model="form.min_amount" placeholder="起订量" border="surround" />
      <view class="gap" />
      <u-input v-model="form.amount" placeholder="供货量" border="surround" />

      <view class="sec-title">规格参数</view>
      <u-input v-model="form.attr1_name" placeholder="参数1名称" border="surround" />
      <view class="gap" />
      <u-input v-model="form.attr1_value" :placeholder="`${form.attr1_name || '参数1'}（选填）`" border="surround" />
      <view class="gap" />
      <u-input v-model="form.attr2_name" placeholder="参数2名称" border="surround" />
      <view class="gap" />
      <u-input v-model="form.attr2_value" :placeholder="`${form.attr2_name || '参数2'}（选填）`" border="surround" />
      <view class="gap" />
      <u-input v-model="form.attr3_name" placeholder="参数3名称" border="surround" />
      <view class="gap" />
      <u-input v-model="form.attr3_value" :placeholder="`${form.attr3_name || '参数3'}（选填）`" border="surround" />

      <view class="sec-title">详细说明</view>
      <u-input v-model="form.summary" placeholder="一句话简介" border="surround" />
      <view class="gap" />
      <u-textarea v-model="form.content" placeholder="详细描述" height="180" border="surround" />
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
.pics {
  display: flex;
  gap: 16rpx;
  margin-bottom: 8rpx;
}
.pic { flex: 1; min-width: 0; min-height: 200rpx; }
</style>
