/** 站内直连交谈已关闭：请走询盘，由平台审核后转达企业 */
export async function openChat(_peerId?: number | string | null) {
  uni.showToast({
    title: "站内交谈已关闭，请通过询盘留言",
    icon: "none",
  });
}
