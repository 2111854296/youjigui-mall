<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '我投递的职位' })
const { api } = useApi()
const { data } = useMemberData('job-apps', () => api('/member/job-applications'))
const items = computed(() => (data.value as any)?.data || [])
const statusMap: Record<string, string> = { pending: '待处理', viewed: '已查看', accepted: '已通过', rejected: '未通过' }
</script>

<template>
  <div class="panel">
    <h3>我投递的职位 <NuxtLink to="/member/resumes">简历管理</NuxtLink></h3>
    <div class="body">
      <table class="table">
        <thead><tr><th>职位</th><th>被投公司</th><th>简历</th><th>状态</th><th>时间</th></tr></thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>
              <NuxtLink v-if="item.job" :to="`/job/${item.job_post_id}`">{{ item.job.title }}</NuxtLink>
              <span v-else>#{{ item.job_post_id }}</span>
            </td>
            <td>{{ item.company_name || '-' }}</td>
            <td>{{ item.resume?.title || item.resume_id }}</td>
            <td>{{ statusMap[item.status] || item.status }}</td>
            <td>{{ item.created_at }}</td>
          </tr>
          <tr v-if="!items.length"><td colspan="5" class="muted">暂无投递记录</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
