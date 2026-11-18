<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '修改资料' })
import { isEmail, isMobile, isRealName } from '~/utils/formCheck'
const { api } = useApi()
const { fetchMe } = useAuth()

const msg = ref('')
const error = ref('')
const loading = ref(false)

const { data, refresh } = useMemberData('profile-deep', () => api('/member/profile-deep'))
const form = reactive({
  name: '',
  gender: 'male',
  email: '',
  mobile: '',
  qq: '',
  province: '',
  city: '',
  district: '',
  area: '',
  company_name: '',
  company_type: '',
  sound: 1,
  online_status: 'online',
})

watch(data, (v: any) => {
  const u = v?.data
  if (!u) return
  Object.assign(form, {
    name: u.name || '',
    gender: u.gender || 'male',
    email: u.email || '',
    mobile: u.mobile || '',
    qq: u.qq || '',
    province: u.province || '',
    city: u.city || '',
    district: u.district || '',
    area: u.area || '',
    company_name: u.company_name || '',
    company_type: u.company_type || '',
    sound: u.sound ?? 1,
    online_status: u.online_status || 'online',
  })
}, { immediate: true })

const groupLabel = computed(() => (data.value as any)?.group_label || '')
const myId = computed(() => (data.value as any)?.data?.id || '')

async function onSubmit() {
  msg.value = ''
  error.value = ''
  if (form.name.trim() && !isRealName(form.name)) {
    await appFail('请用真实姓名', '保存失败')
    return
  }
  if (form.mobile.trim() && !isMobile(form.mobile)) {
    await appFail('请填写11位手机号', '保存失败')
    return
  }
  if (form.email.trim() && !isEmail(form.email)) {
    await appFail('请填写正确的邮箱', '保存失败')
    return
  }
  loading.value = true
  try {
    await api('/member/profile-deep', { method: 'PUT', body: { ...form } })
    await refresh()
    await fetchMe(true)
    await appOk('资料已更新。', '保存成功')
  } catch (e: any) {
    await appFail(apiErrorText(e, '保存失败'), '保存失败')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="panel">
    <h3>
      修改资料
      <NuxtLink to="/member/avatar">更换头像</NuxtLink>
    </h3>
    <div class="body">
      <div class="mf-meta">
        <span>用户ID <b>{{ myId || '-' }}</b></span>
        <span>会员组 <b>{{ groupLabel || '-' }}</b></span>
      </div>
      <div v-if="msg" class="alert alert-success">{{ msg }}</div>
      <div v-if="error" class="alert alert-error">{{ error }}</div>
      <form class="mf-form" @submit.prevent="onSubmit">
        <section class="mf-sec">
          <div class="mf-sec-hd">联系信息</div>
          <div class="mf-sec-bd">
            <div class="mf-grid-2">
              <div class="form-row"><label>联系人 <em>*</em></label><input v-model="form.name" required></div>
              <div class="form-row">
                <label>性别</label>
                <select v-model="form.gender">
                  <option value="male">先生</option>
                  <option value="female">女士</option>
                </select>
              </div>
              <div class="form-row"><label>手机</label><input v-model="form.mobile"></div>
              <div class="form-row"><label>邮箱</label><input v-model="form.email" type="email"></div>
              <div class="form-row"><label>QQ</label><input v-model="form.qq"></div>
            </div>
          </div>
        </section>
        <section class="mf-sec">
          <div class="mf-sec-hd">地区与公司</div>
          <div class="mf-sec-bd">
            <div class="mf-grid-3">
              <div class="form-row"><label>省</label><input v-model="form.province" placeholder="如：广东省"></div>
              <div class="form-row"><label>市</label><input v-model="form.city" placeholder="如：广州市"></div>
              <div class="form-row"><label>区/县</label><input v-model="form.district"></div>
            </div>
            <div class="form-row"><label>地区备注</label><input v-model="form.area"></div>
            <div class="mf-grid-2">
              <div class="form-row"><label>公司</label><input v-model="form.company_name"></div>
              <div class="form-row"><label>公司类型</label><input v-model="form.company_type"></div>
            </div>
          </div>
        </section>
        <section class="mf-sec">
          <div class="mf-sec-hd">偏好设置</div>
          <div class="mf-sec-bd">
            <div class="mf-grid-2">
              <div class="form-row">
                <label>提示音</label>
                <select v-model.number="form.sound">
                  <option :value="0">关闭</option>
                  <option :value="1">默认</option>
                  <option :value="2">提示音2</option>
                  <option :value="3">提示音3</option>
                </select>
              </div>
              <div class="form-row">
                <label>在线状态</label>
                <select v-model="form.online_status">
                  <option value="online">在线</option>
                  <option value="invisible">隐身</option>
                  <option value="offline">离线</option>
                </select>
              </div>
            </div>
          </div>
        </section>
        <div class="mf-actions">
          <button class="btn" type="submit" :disabled="loading">{{ loading ? '保存中…' : '保存资料' }}</button>
        </div>
      </form>
    </div>
  </div>
</template>
