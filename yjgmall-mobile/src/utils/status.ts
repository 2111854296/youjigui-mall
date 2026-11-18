const map: Record<string, string> = {
  pending: "待审",
  approved: "已通过",
  rejected: "已拒绝",
  published: "已发布",
  offline: "已下架",
  draft: "草稿",
  new: "待处理",
  replied: "已回复",
  closed: "已关闭",
  paid: "已付款",
  unfinished: "进行中",
  finished: "已完成",
  accepted: "已采纳",
  done: "已完成",
  shipped: "已发货",
  completed: "已完成",
  cancelled: "已取消",
  processing: "处理中",
  reply_pending: "回复审核中",
  success: "成功",
  failed: "失败",
};

export function statusLabel(s?: string | number | null) {
  if (s == null || s === "") return "—";
  const k = String(s);
  return map[k] || k;
}
