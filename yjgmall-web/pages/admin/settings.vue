<script setup lang="ts">
import { ElMessage } from 'element-plus'

definePageMeta({ layout: 'admin', middleware: 'admin' })
useHead({ title: '站点设置 - 运营后台' })

const { api } = useApi()
const { fetchStatus } = useAdminSecondPassword()
const busy = ref(false)
const secBusy = ref(false)
const secStatus = ref<{ has_second_password?: boolean; verified?: boolean; verified_until?: string | null; ttl_hours?: number }>({})
const secForm = reactive({
  old_password: '',
  password: '',
  password2: '',
})
const form = reactive({
  site_name: '有机硅商城',
  site_domain: 'g.aifeili.cn',
  wechat_subscribe_qr: '',
  wechat_subscribe_label: '扫一扫订阅号',
  wechat_service_qr: '',
  wechat_service_label: '扫一扫服务号',
  header_cta_text: '免费发布',
  header_cta_link: '/member',
  vip_bank_account_name: '',
  vip_bank_name: '',
  vip_bank_account: '',
  vip_pay_hint: '',
  vip_pay_expire_minutes: '30',
  home_list_carousel: '1',
  smtp_host: '',
  smtp_port: '465',
  smtp_encryption: 'ssl',
  smtp_user: '',
  smtp_pass: '',
  smtp_from: '',
  smtp_from_name: '有机硅商城',
  sms_mode: 'fixed',
  sms_fixed_code: '888888',
  sms_aliyun_key: '',
  sms_aliyun_secret: '',
  sms_aliyun_sign: '',
  sms_aliyun_template: '',
})

const { pending, refresh } = await useAsyncData('admin-site-settings', async () => {
  const res = await api('/admin/site-settings')
  const d = (res as any)?.data || {}
  Object.keys(form).forEach((k) => {
    if (d[k] != null) (form as any)[k] = d[k]
  })
  return d
}, { server: false })

async function loadSecStatus() {
  try {
    secStatus.value = await fetchStatus()
  } catch {
    secStatus.value = {}
  }
}

onMounted(() => {
  void loadSecStatus()
  if (import.meta.client && location.hash === '#second-password') {
    nextTick(() => document.getElementById('second-password')?.scrollIntoView({ behavior: 'smooth' }))
  }
})

function onCarousel(v: string | number | boolean) {
  form.home_list_carousel = v ? '1' : '0'
}

async function save() {
  busy.value = true
  try {
    await api('/admin/site-settings', { method: 'PUT', body: { ...form } })
    ElMessage.success('已保存，前台刷新后生效')
    await refresh()
  } catch (e: any) {
    ElMessage.error(e?.data?.message || '保存失败')
  } finally {
    busy.value = false
  }
}

async function saveSecondPassword() {
  if (secForm.password.length < 6) {
    ElMessage.warning('二级密码至少 6 位')
    return
  }
  if (secForm.password !== secForm.password2) {
    ElMessage.warning('两次输入的新密码不一致')
    return
  }
  if (secStatus.value.has_second_password && !secForm.old_password) {
    ElMessage.warning('修改时请填写原二级密码')
    return
  }
  secBusy.value = true
  try {
    await api('/admin/security/second-password', {
      method: 'PUT',
      body: {
        password: secForm.password,
        old_password: secForm.old_password || undefined,
      },
    })
    ElMessage.success('二级密码已保存')
    secForm.old_password = ''
    secForm.password = ''
    secForm.password2 = ''
    await loadSecStatus()
  } catch (e: any) {
    ElMessage.error(e?.data?.message || '保存失败')
  } finally {
    secBusy.value = false
  }
}
</script>

<template>
  <div class="settings-page">
    <div class="page-hd">
      <div>
        <h2>站点设置</h2>
        <p>顶栏微信二维码、站点名称与运营入口</p>
      </div>
      <el-button type="primary" :loading="busy" class="save-btn" @click="save">保存设置</el-button>
    </div>

    <el-skeleton v-if="pending" :rows="8" animated />
    <div v-else class="panels">
      <section id="second-password" class="panel">
        <h3>二级密码</h3>
        <p class="sec-desc">
          仅「删除 / 彻底删除」需要验证二级密码；上架、下架、还原只需确认提示。验证通过后
          {{ secStatus.ttl_hours || 24 }} 小时内删除无需再输入。
          <template v-if="secStatus.has_second_password">
            当前已设置
            <span v-if="secStatus.verified && secStatus.verified_until">，免验至 {{ secStatus.verified_until }}</span>
            <span v-else>，本次会话尚未验证</span>。
          </template>
          <template v-else>当前尚未设置，删除功能将不可用。</template>
        </p>
        <el-form label-width="120px" class="form" @submit.prevent="saveSecondPassword">
          <el-form-item v-if="secStatus.has_second_password" label="原二级密码">
            <el-input v-model="secForm.old_password" type="password" show-password maxlength="64" style="max-width:320px" />
          </el-form-item>
          <el-form-item :label="secStatus.has_second_password ? '新二级密码' : '二级密码'">
            <el-input v-model="secForm.password" type="password" show-password maxlength="64" placeholder="至少 6 位" style="max-width:320px" />
          </el-form-item>
          <el-form-item label="再输入一次">
            <el-input v-model="secForm.password2" type="password" show-password maxlength="64" style="max-width:320px" />
          </el-form-item>
          <el-form-item>
            <el-button type="primary" :loading="secBusy" class="save-btn" @click="saveSecondPassword">
              {{ secStatus.has_second_password ? '修改二级密码' : '设置二级密码' }}
            </el-button>
          </el-form-item>
        </el-form>
      </section>

      <section class="panel">
        <h3>基本信息</h3>
        <el-form label-width="120px" class="form" @submit.prevent="save">
          <el-form-item label="站点名称">
            <el-input v-model="form.site_name" maxlength="40" />
          </el-form-item>
          <el-form-item label="域名文案">
            <el-input v-model="form.site_domain" maxlength="80" placeholder="g.aifeili.cn" />
          </el-form-item>
          <el-form-item label="顶栏按钮文字">
            <el-input v-model="form.header_cta_text" maxlength="20" />
          </el-form-item>
          <el-form-item label="顶栏按钮链接">
            <el-input v-model="form.header_cta_link" maxlength="120" placeholder="/member" />
          </el-form-item>
          <el-form-item label="首页列表轮播">
            <el-switch
              :model-value="form.home_list_carousel === '1'"
              @change="onCarousel"
            />
            <span class="hint">打开后，首页最新供应、最新求购、行业资讯、资讯排行每 10 秒切换一页。关闭后只显示第一页。</span>
          </el-form-item>
        </el-form>
      </section>

      <section class="panel">
        <h3>微信二维码（顶栏）</h3>
        <el-form label-width="120px" class="form">
          <el-form-item label="订阅号图片">
            <ImageUpload v-model="form.wechat_subscribe_qr" label="订阅号二维码" />
          </el-form-item>
          <el-form-item label="订阅号文案">
            <el-input v-model="form.wechat_subscribe_label" maxlength="20" />
          </el-form-item>
          <el-form-item label="服务号图片">
            <ImageUpload v-model="form.wechat_service_qr" label="服务号二维码" />
          </el-form-item>
          <el-form-item label="服务号文案">
            <el-input v-model="form.wechat_service_label" maxlength="20" />
          </el-form-item>
        </el-form>
      </section>

      <section class="panel">
        <h3>VIP 对公收款账户</h3>
        <el-form label-width="120px" class="form">
          <el-form-item label="收款户名">
            <el-input v-model="form.vip_bank_account_name" maxlength="80" placeholder="公司全称" />
          </el-form-item>
          <el-form-item label="开户行">
            <el-input v-model="form.vip_bank_name" maxlength="120" placeholder="如：中国银行某某支行" />
          </el-form-item>
          <el-form-item label="对公账号">
            <el-input v-model="form.vip_bank_account" maxlength="40" placeholder="银行账号" />
          </el-form-item>
          <el-form-item label="支付时限(分)">
            <el-input v-model="form.vip_pay_expire_minutes" maxlength="3" placeholder="30" style="max-width:120px" />
          </el-form-item>
          <el-form-item label="付款说明">
            <el-input
              v-model="form.vip_pay_hint"
              type="textarea"
              :rows="3"
              maxlength="300"
              placeholder="转账备注、开票说明等"
            />
          </el-form-item>
        </el-form>
      </section>

      <section class="panel">
        <h3>订阅邮件</h3>
        <el-form label-width="120px" class="form">
          <el-form-item label="SMTP 服务器">
            <el-input v-model="form.smtp_host" maxlength="120" placeholder="如 smtp.exmail.qq.com" />
          </el-form-item>
          <el-form-item label="端口">
            <el-input v-model="form.smtp_port" maxlength="5" placeholder="465" style="max-width:120px" />
          </el-form-item>
          <el-form-item label="加密">
            <el-select v-model="form.smtp_encryption" style="width:160px">
              <el-option label="SSL（465）" value="ssl" />
              <el-option label="TLS（587）" value="tls" />
            </el-select>
          </el-form-item>
          <el-form-item label="账号">
            <el-input v-model="form.smtp_user" maxlength="120" placeholder="发信邮箱" />
          </el-form-item>
          <el-form-item label="密码">
            <el-input v-model="form.smtp_pass" type="password" show-password maxlength="120" placeholder="邮箱密码或授权码" />
          </el-form-item>
          <el-form-item label="发件地址">
            <el-input v-model="form.smtp_from" maxlength="120" placeholder="留空则使用账号" />
          </el-form-item>
          <el-form-item label="发件名称">
            <el-input v-model="form.smtp_from_name" maxlength="40" />
          </el-form-item>
          <p class="hint" style="margin:0 0 12px 120px">保存后，每 15 分钟给已订阅用户发送新增供应、采购、资讯和行情。未填写服务器时不会发信。</p>
        </el-form>
      </section>

      <section class="panel">
        <h3>手机验证码</h3>
        <el-form label-width="120px" class="form">
          <el-form-item label="验证方式">
            <el-select v-model="form.sms_mode" style="width:220px">
              <el-option label="固定验证码" value="fixed" />
              <el-option label="真实短信" value="gateway" />
            </el-select>
          </el-form-item>
          <el-form-item v-if="form.sms_mode !== 'gateway'" label="固定验证码">
            <el-input v-model="form.sms_fixed_code" maxlength="8" placeholder="888888" style="max-width:200px" />
          </el-form-item>
          <template v-if="form.sms_mode === 'gateway'">
            <el-form-item label="AccessKey ID">
              <el-input v-model="form.sms_aliyun_key" maxlength="80" />
            </el-form-item>
            <el-form-item label="AccessKey Secret">
              <el-input v-model="form.sms_aliyun_secret" maxlength="120" show-password />
            </el-form-item>
            <el-form-item label="短信签名">
              <el-input v-model="form.sms_aliyun_sign" maxlength="40" placeholder="已审核通过的签名" />
            </el-form-item>
            <el-form-item label="模板编号">
              <el-input v-model="form.sms_aliyun_template" maxlength="40" placeholder="SMS_ 开头，变量名 code" />
            </el-form-item>
          </template>
          <p v-if="form.sms_mode !== 'gateway'" class="hint" style="margin:0 0 12px 120px">前期会员先点获取验证码，再输入这里的数字，即视为短信验证成功，然后进入认证审核。</p>
          <p v-else class="hint" style="margin:0 0 12px 120px">保存后，会员认证和电脑端找回密码会通过阿里云发送 6 位随机验证码。模板变量名用 code。会员页面不用改。</p>
        </el-form>
      </section>
    </div>
  </div>
</template>

<style scoped>
.settings-page { width: 100%; }
.page-hd {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
}
.page-hd h2 {
  margin: 0 0 4px;
  font-size: 20px;
  color: #1a2332;
}
.page-hd p { margin: 0; color: #8b93a0; font-size: 13px; }
.save-btn {
  background: #2468f2 !important;
  border-color: #2468f2 !important;
  font-weight: 700;
}
.panels { display: grid; gap: 14px; }
.panel {
  background: #fff;
  border: 1px solid #e8ecf0;
  border-radius: 12px;
  padding: 18px 20px 8px;
}
.panel h3 {
  margin: 0 0 14px;
  font-size: 15px;
  color: #1a2332;
  padding-bottom: 10px;
  border-bottom: 1px solid #f0f2f5;
}
.form { max-width: 760px; }
.hint { margin-left: 10px; color: #8b93a0; font-size: 12px; line-height: 1.5; }
.sec-desc { margin: -4px 0 14px; color: #5b6573; font-size: 13px; line-height: 1.6; }
</style>
