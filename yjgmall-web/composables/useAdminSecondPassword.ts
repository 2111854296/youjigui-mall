import { ElMessage, ElMessageBox } from 'element-plus'

/** 后台删除：二级密码设置校验 + 24 小时内免再输 */
export function useAdminSecondPassword() {
  const { api } = useApi('admin')

  async function fetchStatus() {
    const res = await api<any>('/admin/security')
    return (res as any)?.data || res
  }

  async function promptVerify(title = '验证二级密码') {
    const { value } = await ElMessageBox.prompt('删除操作需验证二级密码，验证后 24 小时内无需再输入', title, {
      inputType: 'password',
      inputPlaceholder: '请输入二级密码',
      confirmButtonText: '验证',
      cancelButtonText: '取消',
      inputPattern: /\S{6,}/,
      inputErrorMessage: '二级密码至少 6 位',
    })
    const plain = String(value || '').trim()
    const res = await api<any>('/admin/security/verify', { method: 'POST', body: { password: plain } })
    ElMessage.success((res as any)?.data?.message || (res as any)?.message || '验证成功')
    return res
  }

  /** 删除前调用：未设置则引导去站点设置；未验证则弹窗输入 */
  async function ensureDeleteAuth(): Promise<boolean> {
    let status: any
    try {
      status = await fetchStatus()
    } catch (e: any) {
      ElMessage.error(e?.data?.message || '无法读取安全状态')
      return false
    }
    if (!status?.has_second_password) {
      ElMessage.warning('请先在「站点设置」中设置二级密码')
      await navigateTo('/admin/settings#second-password')
      return false
    }
    if (status?.verified) return true
    try {
      await promptVerify()
      return true
    } catch (e: any) {
      if (e === 'cancel' || e === 'close') return false
      ElMessage.error(e?.data?.message || '二级密码验证失败')
      return false
    }
  }

  /** 包装删除请求：遇 423/428 时引导设置或再验一次后重试 */
  async function runWithSecondPassword<T>(fn: () => Promise<T>): Promise<T | undefined> {
    const ok = await ensureDeleteAuth()
    if (!ok) return undefined
    try {
      return await fn()
    } catch (e: any) {
      const code = e?.statusCode || e?.response?.status || e?.data?.code
      if (code === 423) {
        ElMessage.warning('请先在「站点设置」中设置二级密码')
        await navigateTo('/admin/settings#second-password')
        return undefined
      }
      if (code === 428) {
        try {
          await promptVerify()
          return await fn()
        } catch (e2: any) {
          if (e2 === 'cancel' || e2 === 'close') return undefined
          ElMessage.error(e2?.data?.message || '二级密码验证失败')
          return undefined
        }
      }
      throw e
    }
  }

  return { fetchStatus, promptVerify, ensureDeleteAuth, runWithSecondPassword }
}
