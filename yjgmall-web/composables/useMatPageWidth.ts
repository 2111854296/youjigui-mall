/** 每一页的宽度和滑动距离共用测到的同一个像素，避免百分比取整后切到卡片。 */
export function bindMatPageWidth(root: HTMLElement | null | undefined) {
  if (!root || typeof ResizeObserver === 'undefined') return () => {}
  const apply = () => {
    const nodes = root.classList.contains('mat-viewport')
      ? [root]
      : [...root.querySelectorAll<HTMLElement>('.mat-viewport')]
    nodes.forEach((el) => {
      const cs = getComputedStyle(el)
      const w = el.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight)
      if (w > 0) el.style.setProperty('--page-w', `${w}px`)
    })
  }
  apply()
  const ro = new ResizeObserver(apply)
  ro.observe(root)
  root.querySelectorAll('.mat-viewport').forEach((el) => ro.observe(el))
  const mo = new MutationObserver(() => {
    root.querySelectorAll('.mat-viewport').forEach((el) => ro.observe(el))
    apply()
  })
  mo.observe(root, { childList: true, subtree: true })
  window.addEventListener('resize', apply)
  return () => {
    ro.disconnect()
    mo.disconnect()
    window.removeEventListener('resize', apply)
  }
}
