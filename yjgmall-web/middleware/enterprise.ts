export default defineNuxtRouteMiddleware(async () => {
  const { user, loaded, fetchMe, caps } = useAuth('front')
  if (!loaded.value || !user.value?.capabilities) {
    await fetchMe(true)
  }
  if (!user.value) {
    return navigateTo('/login')
  }
  if (!caps.value.shop && !caps.value.supply) {
    return navigateTo('/member?need=enterprise')
  }
})
