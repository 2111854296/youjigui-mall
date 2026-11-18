/** 中文姓名，允许少数民族姓名中的间隔号。 */
export function isRealName(raw: string) {
  const name = raw.trim()
  return /^[\u4e00-\u9fa5]{2,20}(·[\u4e00-\u9fa5]{1,20})?$/.test(name)
}

/** 18 位身份证，末位可为 X。 */
export function isIdCard(raw: string) {
  const s = raw.trim().toUpperCase()
  if (!/^[1-9]\d{16}[\dX]$/.test(s)) return false
  const weight = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2]
  const check = '10X98765432'
  let sum = 0
  for (let i = 0; i < 17; i++) sum += Number(s[i]) * weight[i]
  return check[sum % 11] === s[17]
}

export function isMobile(raw: string) {
  return /^1\d{10}$/.test(raw.trim())
}

export function isBankAccount(raw: string) {
  return /^\d{8,30}$/.test(raw.trim())
}

export function isEmail(raw: string) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(raw.trim())
}
