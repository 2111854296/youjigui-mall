<script setup lang="ts">
import { reactive, ref } from "vue";
import { onShow } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";
import ImageUpload from "@/components/ImageUpload/ImageUpload.vue";
import { statusLabel } from "@/utils/status";

const loading = ref(false);
const saving = ref(false);
const shop = ref<any>({});
const templateOptions = [
  { code: "atelier", name: "展厅·清雅" },
  { code: "forge", name: "工场·沉稳" },
  { code: "ledger", name: "名录·商务" },
];
const companyTypes = ["企业单位", "事业单位或社会团体", "个体经营", "其他"];
const businessModes = ["制造商", "贸易商", "服务商", "其他机构"];
const scales = ["1-49人", "50-99人", "100-499人", "500-999人", "1000-3000人", "3000人以上"];

const form = reactive({
  name: "",
  logo: "",
  banner: "",
  intro: "",
  content: "",
  area: "",
  business: "",
  company_type: "企业单位",
  business_mode: "制造商",
  scale: "50-99人",
  capital: "",
  website: "",
  contact: "",
  phone: "",
  template: "atelier",
});

function normalizeTpl(raw?: string) {
  const t = String(raw || "atelier");
  if (t === "forge" || t === "orange") return "forge";
  if (t === "ledger" || t === "blue") return "ledger";
  return "atelier";
}

function fill(s: any) {
  Object.assign(form, {
    name: s.name || "",
    logo: s.logo || "",
    banner: s.banner || "",
    intro: s.intro || "",
    content: s.content || "",
    area: s.area || "",
    business: s.business || "",
    company_type: s.company_type || "企业单位",
    business_mode: s.business_mode || "制造商",
    scale: s.scale || "50-99人",
    capital: s.capital || "",
    website: s.website || "",
    contact: s.contact || "",
    phone: s.phone || "",
    template: normalizeTpl(s.template),
  });
}

async function load() {
  if (!ensureLogin()) return;
  loading.value = true;
  try {
    const res = await api<any>("/member/shop");
    shop.value = res?.data || {};
    fill(shop.value);
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    loading.value = false;
  }
}

function pickTpl(e: any) {
  const list = shop.value.templates?.length ? shop.value.templates : templateOptions;
  form.template = list[Number(e.detail.value)]?.code || "atelier";
}

function pickOpt(list: string[], key: "company_type" | "business_mode" | "scale", e: any) {
  form[key] = list[Number(e.detail.value)] || "";
}

async function save() {
  if (!form.name.trim()) {
    uni.showToast({ title: "请填写公司名称", icon: "none" });
    return;
  }
  saving.value = true;
  try {
    await api("/member/shop", { method: "PUT", body: { ...form } });
    uni.showToast({ title: "设置已保存", icon: "success" });
    await load();
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "保存失败"), icon: "none" });
  } finally {
    saving.value = false;
  }
}

const tplLabel = () => {
  const list = shop.value.templates?.length ? shop.value.templates : templateOptions;
  return list.find((t: any) => t.code === form.template)?.name || form.template;
};

onShow(load);
</script>

<template>
  <view class="page">
    <view v-if="loading" class="footer-tip muted">加载中...</view>
    <view v-else class="card form">
      <text class="meta muted">状态 {{ statusLabel(shop.status) }} · /companies/{{ shop.slug || "-" }}</text>
      <view class="section">基本资料</view>
      <u-input v-model="form.name" placeholder="公司名称 *" border="surround" />
      <view class="gap" />
      <picker :range="shop.templates?.length ? shop.templates : templateOptions" range-key="name" @change="pickTpl">
        <view class="picker-row"><text class="muted">旺铺模板</text><text class="val">{{ tplLabel() }}</text></view>
      </picker>
      <view class="gap" />
      <u-input v-model="form.area" placeholder="地区，如：广东东莞" border="surround" />
      <view class="gap" />
      <picker :range="companyTypes" @change="pickOpt(companyTypes, 'company_type', $event)">
        <view class="picker-row"><text class="muted">公司类型</text><text class="val">{{ form.company_type }}</text></view>
      </picker>
      <view class="gap" />
      <picker :range="businessModes" @change="pickOpt(businessModes, 'business_mode', $event)">
        <view class="picker-row"><text class="muted">经营模式</text><text class="val">{{ form.business_mode }}</text></view>
      </picker>
      <view class="gap" />
      <picker :range="scales" @change="pickOpt(scales, 'scale', $event)">
        <view class="picker-row"><text class="muted">人员规模</text><text class="val">{{ form.scale }}</text></view>
      </picker>
      <view class="gap" />
      <u-input v-model="form.capital" placeholder="注册资本" border="surround" />
      <view class="gap" />
      <u-input v-model="form.business" placeholder="主营" border="surround" />
      <view class="gap" />
      <u-input v-model="form.website" placeholder="网站 https://" border="surround" />

      <view class="section">Logo / 横幅</view>
      <view class="pics">
        <view class="pic"><ImageUpload v-model="form.logo" label="Logo" /></view>
        <view class="pic"><ImageUpload v-model="form.banner" label="旺铺广告图" /></view>
      </view>

      <view class="section">联系方式</view>
      <u-input v-model="form.contact" placeholder="联系人" border="surround" />
      <view class="gap" />
      <u-input v-model="form.phone" placeholder="电话" border="surround" />

      <view class="section">公司介绍</view>
      <u-textarea v-model="form.intro" placeholder="简介" height="100" border="surround" />
      <view class="gap" />
      <u-textarea v-model="form.content" placeholder="详情" height="180" border="surround" />
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="保存设置" :loading="saving" @click="save" />
    </view>
  </view>
</template>

<style lang="scss" scoped>
.form { margin: 20rpx; padding: 28rpx; }
.meta { display: block; margin-bottom: 12rpx; font-size: 22rpx; }
.gap { height: 18rpx; }
.section {
  margin: 28rpx 0 16rpx; font-size: 28rpx; font-weight: 700;
  padding-left: 14rpx; border-left: 6rpx solid #2769dc;
}
.section:first-of-type { margin-top: 16rpx; }
.picker-row {
  display: flex; align-items: center; gap: 12rpx;
  padding: 20rpx 16rpx; border: 1rpx solid #dadbde; border-radius: 8rpx;
}
.val { flex: 1; }
.pics { display: flex; gap: 16rpx; }
.pic { flex: 1; min-width: 0; }
.footer-tip { text-align: center; padding: 32rpx; }
</style>
