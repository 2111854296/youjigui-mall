/** 招聘发布等：需已认证企业（与人才库门控一致） */
export default defineNuxtRouteMiddleware(async () => {
  const { user, loaded, fetchMe } = useAuth('front')
  if (!loaded.value || !user.value) {
    await fetchMe(true)
  }
  if (!user.value) {
    return navigateTo('/login?redirect=/member/jobs/create')
  }
  const u: any = user.value
  const ok = Boolean(u.is_admin || u.company_certified || (u.is_enterprise && u.is_certified))
  if (ok) return
  if (!u.is_enterprise) {
    return navigateTo('/member?need=enterprise&from=jobs')
  }
  return navigateTo('/member/shop?need=cert&from=jobs')
})
