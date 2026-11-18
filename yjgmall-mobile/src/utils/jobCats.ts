/** 与 PC / 企业发职位同一套，勿单独写死另一套 */
export const FIELD_CATS = [
  "有机硅单体/中间体",
  "硅油",
  "硅橡胶",
  "有机硅助剂/填料",
  "机械设备",
  "硅烷",
  "胶粘剂",
  "硅树脂",
  "其他",
] as const;

export const JOB_ROLE_CATS = [
  "管理",
  "研发",
  "销售",
  "文员",
  "生产",
  "品控",
  "外贸",
  "行政",
  "前台",
  "其他",
] as const;

export const JOB_EXP_OPTS = ["不限", "应届生", "1-3年", "3-5年", "5-10年", "10年以上"] as const;
export const JOB_EDU_OPTS = ["不限", "高中", "大专", "本科", "硕士", "博士"] as const;
export const JOB_NATURE_OPTS = ["全职", "兼职", "实习"] as const;
