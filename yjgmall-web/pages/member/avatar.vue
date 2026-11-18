<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '头像设置' })
const { api } = useApi()
const { user, fetchMe } = useAuth()
const avatar = ref(user.value?.avatar || '')

async function save() {
  await api('/member/profile-deep', { method: 'PUT', body: { avatar: avatar.value } })
  await fetchMe(true)
  await appOk('头像已更新。', '保存成功')
}
</script>

<template>
  <div class="panel">
    <h3>
      头像设置
      <NuxtLink to="/member/profile">返回资料</NuxtLink>
    </h3>
    <div class="body">
      <form class="mf-form" @submit.prevent="save">
        <section class="mf-sec">
          <div class="mf-sec-hd">上传头像</div>
          <div class="mf-sec-bd">
            <div class="mf-pics one">
              <ImageUpload v-model="avatar" label="会员头像" hint="上传后点保存生效" />
            </div>
          </div>
        </section>
        <div class="mf-actions"><button class="btn" type="submit">保存头像</button></div>
      </form>
    </div>
  </div>
</template>
