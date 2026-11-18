-- Company advanced filters + shop banner
ALTER TABLE companies
  ADD COLUMN IF NOT EXISTS company_type VARCHAR(32) NULL COMMENT '企业类型' AFTER business,
  ADD COLUMN IF NOT EXISTS business_mode VARCHAR(32) NULL COMMENT '经营模式' AFTER company_type,
  ADD COLUMN IF NOT EXISTS scale VARCHAR(32) NULL COMMENT '人员规模' AFTER business_mode,
  ADD COLUMN IF NOT EXISTS capital VARCHAR(64) NULL COMMENT '注册资本' AFTER scale,
  ADD COLUMN IF NOT EXISTS vip_level TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'VIP指数0-10' AFTER is_certified,
  ADD COLUMN IF NOT EXISTS banner VARCHAR(500) NULL COMMENT '旺铺横幅' AFTER logo;

UPDATE companies
SET
  company_type = COALESCE(NULLIF(company_type, ''), '企业单位'),
  business_mode = COALESCE(NULLIF(business_mode, ''), '制造商'),
  scale = COALESCE(NULLIF(scale, ''), '50-99人'),
  capital = COALESCE(NULLIF(capital, ''), '500万人民币'),
  vip_level = IF(vip_level > 0, vip_level, IF(is_certified = 1, 5, IF(is_featured = 1, 3, 1)))
WHERE status = 'approved';
