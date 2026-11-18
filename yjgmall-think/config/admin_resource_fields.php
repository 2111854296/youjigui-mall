<?php

/** Auto-extracted from Filament Resources — admin form schemas */
return array (
  'users' => 
  array (
    0 => 
    array (
      'name' => 'name',
      'label' => '姓名',
      'type' => 'text',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'avatar',
      'label' => '头像',
      'type' => 'image',
    ),
    2 => 
    array (
      'name' => 'username',
      'label' => '用户名',
      'type' => 'text',
      'required' => true,
    ),
    3 => 
    array (
      'name' => 'email',
      'label' => '邮箱',
      'type' => 'email',
    ),
    4 => 
    array (
      'name' => 'mobile',
      'label' => '手机',
      'type' => 'text',
    ),
    5 => 
    array (
      'name' => 'password',
      'label' => '密码',
      'type' => 'password',
      'required' => true,
      'create_only' => false,
    ),
    6 => 
    array (
      'name' => 'company_name',
      'label' => '公司名称',
      'type' => 'text',
    ),
    7 => 
    array (
      'name' => 'level',
      'label' => '等级',
      'type' => 'select',
      'options' => 
      array (
        0 => '普通会员',
        1 => '白银会员',
        2 => '黄金会员',
        3 => '星钻会员',
      ),
      'required' => true,
    ),
    8 => 
    array (
      'name' => 'vip_expire_at',
      'label' => 'VIP到期时间',
      'type' => 'datetime',
    ),
    9 => 
    array (
      'name' => 'is_certified',
      'label' => '认证',
      'type' => 'toggle',
    ),
    10 => 
    array (
      'name' => 'member_type',
      'label' => '会员类型',
      'type' => 'select',
      'options' => 
      array (
        'personal' => '个人会员',
        'company' => '企业会员',
      ),
      'required' => true,
    ),
    11 => 
    array (
      'name' => 'group_code',
      'label' => '会员组',
      'type' => 'select',
      'options' => 
      array (
        'admin' => '管理员',
        'banned' => '禁止访问',
        'guest' => '游客',
        'pending' => '待审核会员',
        'personal' => '个人会员',
        'enterprise' => '企业会员',
        'vip' => 'VIP渠道会员',
      ),
      'required' => true,
    ),
    12 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'pending' => '待审核',
        'active' => '正常',
        'banned' => '禁用',
      ),
      'required' => true,
    ),
    13 => 
    array (
      'name' => 'is_admin',
      'label' => '管理员',
      'type' => 'toggle',
    ),
    14 => 
    array (
      'name' => 'points',
      'label' => '积分',
      'type' => 'number',
    ),
    15 => 
    array (
      'name' => 'balance',
      'label' => '余额',
      'type' => 'number',
    ),
    16 => 
    array (
      'name' => 'deposit',
      'label' => '保证金',
      'type' => 'number',
    ),
    17 => 
    array (
      'name' => 'mobile_verified',
      'label' => '手机认证',
      'type' => 'toggle',
    ),
    18 => 
    array (
      'name' => 'bank_verified',
      'label' => '银行认证',
      'type' => 'toggle',
    ),
    19 => 
    array (
      'name' => 'truename_verified',
      'label' => '实名认证',
      'type' => 'toggle',
    ),
    20 => 
    array (
      'name' => 'truename',
      'label' => '真实姓名',
      'type' => 'text',
    ),
    21 => 
    array (
      'name' => 'online_status',
      'label' => '在线状态',
      'type' => 'select',
      'options' => 
      array (
        'online' => '在线',
        'invisible' => '隐身',
        'offline' => '离线',
      ),
    ),
  ),
  'supplies' => 
  array (
    0 => 
    array (
      'name' => 'user_id',
      'label' => '用户',
      'type' => 'relation',
      'relation' => 'user',
      'relation_label' => 'name',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'category_id',
      'label' => '分类',
      'type' => 'category',
      'required' => true,
    ),
    2 => 
    array (
      'name' => 'title',
      'label' => '标题',
      'type' => 'text',
      'required' => true,
    ),
    3 => 
    array (
      'name' => 'thumb',
      'label' => '标题图片',
      'type' => 'image',
    ),
    4 => 
    array (
      'name' => 'thumb1',
      'label' => '产品图1',
      'type' => 'image',
    ),
    5 => 
    array (
      'name' => 'thumb2',
      'label' => '产品图2',
      'type' => 'image',
    ),
    6 => 
    array (
      'name' => 'price',
      'label' => '价格',
      'type' => 'number',
    ),
    7 => 
    array (
      'name' => 'unit',
      'label' => '单位',
      'type' => 'text',
    ),
    8 => 
    array (
      'name' => 'brand',
      'label' => '品牌',
      'type' => 'text',
    ),
    9 => 
    array (
      'name' => 'summary',
      'label' => '摘要',
      'type' => 'textarea',
    ),
    10 => 
    array (
      'name' => 'content',
      'label' => '详情',
      'type' => 'richtext',
    ),
    11 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'pending' => '待审核',
        'approved' => '已通过',
        'rejected' => '已拒绝',
        'offline' => '已下架',
      ),
      'required' => true,
    ),
    12 => 
    array (
      'name' => 'is_hot',
      'label' => '热门标记',
      'type' => 'toggle',
      'hint' => '开启后首页供求列表标题旁显示「热」',
    ),
    13 => 
    array (
      'name' => 'weight',
      'label' => '排序权重',
      'type' => 'number',
      'hint' => '默认先按会员等级。同等级按询盘、收藏、资料完整度、最近更新。这个数字只做微调，越大越靠前',
    ),
    14 => 
    array (
      'name' => 'refreshed_at',
      'label' => '刷新时间',
      'type' => 'datetime',
    ),
  ),
  'purchases' => 
  array (
    0 => 
    array (
      'name' => 'user_id',
      'label' => '用户',
      'type' => 'relation',
      'relation' => 'user',
      'relation_label' => 'name',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'category_id',
      'label' => '分类',
      'type' => 'category',
      'required' => true,
    ),
    2 => 
    array (
      'name' => 'title',
      'label' => '标题',
      'type' => 'text',
      'required' => true,
    ),
    3 => 
    array (
      'name' => 'thumb',
      'label' => '标题图片',
      'type' => 'image',
    ),
    4 => 
    array (
      'name' => 'budget',
      'label' => '预算',
      'type' => 'number',
    ),
    5 => 
    array (
      'name' => 'quantity',
      'label' => '数量',
      'type' => 'text',
    ),
    6 => 
    array (
      'name' => 'summary',
      'label' => '摘要',
      'type' => 'textarea',
    ),
    7 => 
    array (
      'name' => 'content',
      'label' => '详情',
      'type' => 'richtext',
    ),
    8 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'pending' => '待审核',
        'approved' => '已通过',
        'rejected' => '已拒绝',
        'offline' => '已下架',
      ),
      'required' => true,
    ),
    9 => 
    array (
      'name' => 'is_hot',
      'label' => '热门标记',
      'type' => 'toggle',
      'hint' => '开启后首页供求列表标题旁显示「热」',
    ),
    10 => 
    array (
      'name' => 'refreshed_at',
      'label' => '刷新时间',
      'type' => 'datetime',
    ),
  ),
  'products' => 
  array (
    0 => 
    array (
      'name' => 'user_id',
      'label' => '用户',
      'type' => 'relation',
      'relation' => 'user',
      'relation_label' => 'name',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'category_id',
      'label' => '分类',
      'type' => 'category',
      'required' => true,
    ),
    2 => 
    array (
      'name' => 'title',
      'label' => '标题',
      'type' => 'text',
      'required' => true,
    ),
    3 => 
    array (
      'name' => 'thumb',
      'label' => '商品图片',
      'type' => 'image',
    ),
    4 => 
    array (
      'name' => 'price',
      'label' => '价格',
      'type' => 'number',
      'required' => true,
    ),
    5 => 
    array (
      'name' => 'stock',
      'label' => '库存',
      'type' => 'number',
    ),
    6 => 
    array (
      'name' => 'unit',
      'label' => '单位',
      'type' => 'text',
    ),
    7 => 
    array (
      'name' => 'summary',
      'label' => '摘要',
      'type' => 'textarea',
    ),
    8 => 
    array (
      'name' => 'content',
      'label' => '详情',
      'type' => 'richtext',
    ),
    9 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'pending' => '待审核',
        'approved' => '已通过',
        'rejected' => '已拒绝',
        'offline' => '已下架',
      ),
      'required' => true,
    ),
  ),
  'companies' => 
  array (
    0 => 
    array (
      'name' => 'user_id',
      'label' => '关联用户',
      'type' => 'relation',
      'relation' => 'user',
      'relation_label' => 'name',
      'required' => true,
      'section' => 'basic',
    ),
    1 => 
    array (
      'name' => 'name',
      'label' => '公司名称',
      'type' => 'text',
      'required' => true,
      'section' => 'basic',
    ),
    2 => 
    array (
      'name' => 'category_id',
      'label' => '主营行业',
      'type' => 'category',
      'module' => 'company',
      'section' => 'basic',
    ),
    3 => 
    array (
      'name' => 'logo',
      'label' => '企业Logo',
      'type' => 'image',
      'section' => 'basic',
    ),
    4 => 
    array (
      'name' => 'banner',
      'label' => '旺铺横幅',
      'type' => 'image',
      'section' => 'basic',
    ),
    5 => 
    array (
      'name' => 'env_image1',
      'label' => '厂房环境图 1',
      'type' => 'image',
      'hint' => '首页展示 4 格可滑动，最多 8 张',
      'section' => 'env',
    ),
    6 => 
    array (
      'name' => 'env_image2',
      'label' => '厂房环境图 2',
      'type' => 'image',
      'section' => 'env',
    ),
    7 => 
    array (
      'name' => 'env_image3',
      'label' => '厂房环境图 3',
      'type' => 'image',
      'section' => 'env',
    ),
    8 => 
    array (
      'name' => 'env_image4',
      'label' => '厂房环境图 4',
      'type' => 'image',
      'section' => 'env',
    ),
    9 => 
    array (
      'name' => 'env_image5',
      'label' => '厂房环境图 5',
      'type' => 'image',
      'section' => 'env',
    ),
    10 => 
    array (
      'name' => 'env_image6',
      'label' => '厂房环境图 6',
      'type' => 'image',
      'section' => 'env',
    ),
    11 => 
    array (
      'name' => 'env_image7',
      'label' => '厂房环境图 7',
      'type' => 'image',
      'section' => 'env',
    ),
    12 => 
    array (
      'name' => 'env_image8',
      'label' => '厂房环境图 8',
      'type' => 'image',
      'section' => 'env',
    ),
    13 => 
    array (
      'name' => 'slug',
      'label' => '公司主页',
      'type' => 'text',
      'required' => true,
      'section' => 'basic',
    ),
    14 => 
    array (
      'name' => 'company_type',
      'label' => '公司类型',
      'type' => 'select',
      'options' => 
      array (
        '企业单位' => '企业单位',
        '个体经营' => '个体经营',
        '事业单位' => '事业单位',
        '其他组织' => '其他组织',
      ),
      'section' => 'basic',
    ),
    15 => 
    array (
      'name' => 'business_mode',
      'label' => '经营模式',
      'type' => 'multiselect',
      'hint' => '最多选择两项',
      'max' => 2,
      'options' => 
      array (
        '制造商' => '制造商',
        '贸易商' => '贸易商',
        '服务商' => '服务商',
        '其他组织' => '其他组织',
      ),
      'section' => 'basic',
    ),
    16 => 
    array (
      'name' => 'scale',
      'label' => '公司规模',
      'type' => 'select',
      'options' => 
      array (
        '1-19人' => '1-19人',
        '20-49人' => '20-49人',
        '50-99人' => '50-99人',
        '100-499人' => '100-499人',
        '500人以上' => '500人以上',
      ),
      'section' => 'basic',
    ),
    17 => 
    array (
      'name' => 'capital',
      'label' => '注册资本',
      'type' => 'text',
      'section' => 'basic',
    ),
    18 => 
    array (
      'name' => 'capital_unit',
      'label' => '资本单位',
      'type' => 'select',
      'options' => 
      array (
        '人民币' => '人民币',
        '美元' => '美元',
        '欧元' => '欧元',
        '港币' => '港币',
        '其他' => '其他',
      ),
      'section' => 'basic',
    ),
    19 => 
    array (
      'name' => 'reg_year',
      'label' => '公司成立年份',
      'type' => 'number',
      'section' => 'basic',
    ),
    20 => 
    array (
      'name' => 'area',
      'label' => '所在地区',
      'type' => 'text',
      'section' => 'basic',
    ),
    21 => 
    array (
      'name' => 'address',
      'label' => '公司地址',
      'type' => 'text',
      'section' => 'basic',
    ),
    22 => 
    array (
      'name' => 'zip',
      'label' => '邮政编码',
      'type' => 'text',
      'section' => 'basic',
    ),
    23 => 
    array (
      'name' => 'contact',
      'label' => '联系人',
      'type' => 'text',
      'section' => 'basic',
    ),
    24 => 
    array (
      'name' => 'phone',
      'label' => '电话',
      'type' => 'text',
      'section' => 'basic',
    ),
    25 => 
    array (
      'name' => 'fax',
      'label' => '传真',
      'type' => 'text',
      'section' => 'basic',
    ),
    26 => 
    array (
      'name' => 'email',
      'label' => '电子邮件',
      'type' => 'text',
      'section' => 'basic',
    ),
    27 => 
    array (
      'name' => 'website',
      'label' => '公司网址',
      'type' => 'text',
      'section' => 'basic',
    ),
    28 => 
    array (
      'name' => 'sell_products',
      'label' => '销售产品',
      'type' => 'text',
      'hint' => '多个产品用 | 分隔',
      'section' => 'basic',
    ),
    29 => 
    array (
      'name' => 'buy_products',
      'label' => '采购产品',
      'type' => 'text',
      'hint' => '多个产品用 | 分隔',
      'section' => 'basic',
    ),
    30 => 
    array (
      'name' => 'business',
      'label' => '经营范围',
      'type' => 'textarea',
      'section' => 'basic',
    ),
    31 => 
    array (
      'name' => 'tags',
      'label' => '名企标签',
      'type' => 'text',
      'hint' => '首页名企卡片展示，最多4个，用逗号分隔，如：广东,硫化剂,食品级,生产销售',
      'section' => 'basic',
    ),
    32 => 
    array (
      'name' => 'intro',
      'label' => '公司介绍',
      'type' => 'textarea',
      'section' => 'basic',
    ),
    33 => 
    array (
      'name' => 'content',
      'label' => '详细介绍',
      'type' => 'richtext',
      'section' => 'basic',
    ),
    34 => 
    array (
      'name' => 'is_certified',
      'label' => '公司认证',
      'type' => 'toggle',
      'section' => 'cert',
    ),
    35 => 
    array (
      'name' => 'user_mobile_verified',
      'label' => '手机认证',
      'type' => 'toggle',
      'section' => 'cert',
      'virtual' => true,
    ),
    36 => 
    array (
      'name' => 'user_bank_verified',
      'label' => '银行认证',
      'type' => 'toggle',
      'section' => 'cert',
      'virtual' => true,
    ),
    37 => 
    array (
      'name' => 'user_truename_verified',
      'label' => '实名认证',
      'type' => 'toggle',
      'section' => 'cert',
      'virtual' => true,
    ),
    38 => 
    array (
      'name' => 'template',
      'label' => '旺铺模板',
      'type' => 'select',
      'options' => 
      array (
        'atelier' => '展厅·清雅',
        'forge' => '工场·沉稳',
        'ledger' => '名录·商务',
      ),
      'section' => 'advanced',
    ),
    39 => 
    array (
      'name' => 'vip_theory',
      'label' => 'VIP理论值',
      'type' => 'number',
      'hint' => '理论指数与修正值之和为 VIP 指数',
      'section' => 'advanced',
    ),
    40 => 
    array (
      'name' => 'vip_correct',
      'label' => 'VIP修正值',
      'type' => 'number',
      'section' => 'advanced',
    ),
    41 => 
    array (
      'name' => 'vip_level',
      'label' => 'VIP指数',
      'type' => 'number',
      'hint' => '0-10，由理论值+修正值自动回写，也可手工调整',
      'section' => 'advanced',
    ),
    42 => 
    array (
      'name' => 'vip_from',
      'label' => 'VIP开通时间',
      'type' => 'datetime',
      'section' => 'advanced',
    ),
    43 => 
    array (
      'name' => 'views',
      'label' => '人气',
      'type' => 'number',
      'section' => 'advanced',
    ),
    44 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'pending' => '待审核',
        'approved' => '已通过',
        'rejected' => '已拒绝',
        'offline' => '已下架',
      ),
      'required' => true,
      'section' => 'advanced',
    ),
    45 => 
    array (
      'name' => 'is_featured',
      'label' => '推荐',
      'type' => 'toggle',
      'section' => 'advanced',
    ),
  ),
  'articles' => 
  array (
    0 => 
    array (
      'name' => 'category_id',
      'label' => '所属分类',
      'type' => 'category',
      'module' => 'article',
      'hint' => '栏目子集：选子类即可挂到对应 Tab（网评 › 日评/周评；产能产量 › 产能/开工率）。价格行情资讯另绑「行情品种」',
    ),
    1 => 
    array (
      'name' => 'title',
      'label' => '资讯标题',
      'type' => 'text',
      'required' => true,
    ),
    2 => 
    array (
      'name' => 'level',
      'label' => '信息级别',
      'type' => 'number',
      'hint' => '数字越大越靠前，0 为普通',
    ),
    3 => 
    array (
      'name' => 'thumb',
      'label' => '标题图片',
      'type' => 'image',
    ),
    4 => 
    array (
      'name' => 'content',
      'label' => '资讯内容',
      'type' => 'richtext',
    ),
    5 => 
    array (
      'name' => 'summary',
      'label' => '资讯简介',
      'type' => 'textarea',
    ),
    6 => 
    array (
      'name' => 'author',
      'label' => '资讯作者',
      'type' => 'text',
    ),
    7 => 
    array (
      'name' => 'source',
      'label' => '资讯来源',
      'type' => 'text',
    ),
    8 => 
    array (
      'name' => 'source_url',
      'label' => '来源链接',
      'type' => 'text',
    ),
    9 => 
    array (
      'name' => 'type',
      'label' => '类型',
      'type' => 'select',
      'options' => 
      array (
        'news' => '资讯',
        'quote' => '行情',
      ),
      'required' => true,
    ),
    10 => 
    array (
      'name' => 'quote_product_id',
      'label' => '行情品种',
      'type' => 'relation',
      'relation' => 'quoteProduct',
      'hint' => '用于「价格行情资讯」：点下方标签快捷选择，或在框内搜索品种名。其它栏目子集请在「所属分类」选子类。',
    ),
    11 => 
    array (
      'name' => 'is_top',
      'label' => '置顶',
      'type' => 'toggle',
    ),
    12 => 
    array (
      'name' => 'status',
      'label' => '资讯状态',
      'type' => 'select',
      'options' => 
      array (
        'published' => '已上架',
        'pending' => '待审',
        'rejected' => '已拒绝',
        'offline' => '已下架',
        'draft' => '草稿',
      ),
      'required' => true,
    ),
    13 => 
    array (
      'name' => 'area',
      'label' => '所在地区',
      'type' => 'text',
    ),
    14 => 
    array (
      'name' => 'views',
      'label' => '真实浏览量',
      'type' => 'number',
      'hint' => '用户真实访问累计，一般勿改；前台显示 = 真实 + 增加',
    ),
    15 => 
    array (
      'name' => 'view_boost',
      'label' => '增加浏览量',
      'type' => 'number',
      'hint' => '在真实浏览量基础上额外增加的数量，例如填 10000 则前台多显示 1 万',
    ),
    16 => 
    array (
      'name' => 'sort',
      'label' => '排序',
      'type' => 'number',
    ),
    17 => 
    array (
      'name' => 'published_at',
      'label' => '发布时间',
      'type' => 'datetime',
    ),
  ),
  'article-topics' => 
  array (
    0 => 
    array (
      'name' => 'name',
      'label' => '名称',
      'type' => 'text',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'slug',
      'label' => 'Slug',
      'type' => 'text',
      'required' => true,
    ),
    2 => 
    array (
      'name' => 'summary',
      'label' => '摘要',
      'type' => 'textarea',
    ),
    3 => 
    array (
      'name' => 'sort',
      'label' => '排序',
      'type' => 'number',
    ),
    4 => 
    array (
      'name' => 'is_active',
      'label' => '启用',
      'type' => 'toggle',
    ),
  ),
  'banners' => 
  array (
    0 => 
    array (
      'name' => 'title',
      'label' => '标题',
      'type' => 'text',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'image',
      'label' => '图片',
      'type' => 'image',
      'required' => true,
    ),
    2 => 
    array (
      'name' => 'link',
      'label' => '链接',
      'type' => 'text',
    ),
    3 => 
    array (
      'name' => 'position',
      'label' => '位置',
      'type' => 'select',
      'options' => 
      array (
        'home' => '首页轮播',
        'mall_top' => '商城轮播',
        'company_top' => '企业库轮播',
        'news_top' => '资讯轮播',
      ),
      'hint' => '频道顶部轮播建议改在「广告」里配，首页也可直接用广告-首页轮播',
    ),
    4 => 
    array (
      'name' => 'sort',
      'label' => '排序',
      'type' => 'number',
    ),
    5 => 
    array (
      'name' => 'is_active',
      'label' => '启用',
      'type' => 'toggle',
    ),
  ),
  'ads' => 
  array (
    0 => 
    array (
      'name' => 'title',
      'label' => '标题',
      'type' => 'text',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'position',
      'label' => '位置',
      'type' => 'select',
      'options' => 
      array (
        'home_banner' => '首页轮播',
        'home_portal' => '首页分类展位',
        'sell_elite_1' => '产品分类·推荐供应·第1张',
        'sell_elite_2' => '产品分类·推荐供应·第2张',
        'sell_elite_3' => '产品分类·推荐供应·第3张',
        'sell_elite_4' => '产品分类·推荐供应·第4张',
        'buy_right' => '求购右侧',
        'mall_top' => '商城轮播',
        'sample_top' => '样品专区轮播',
        'company_top' => '企业库轮播',
        'news_top' => '资讯轮播',
        'rank_sell' => '供应第1页首条',
        'rank_sell_2' => '供应第2页首条',
        'rank_sell_3' => '供应第3页首条',
        'floor_mono' => '首页·有机硅单体推广（排序1-5）',
        'floor_oil' => '首页·硅油推广（排序1-5）',
        'floor_rubber' => '首页·硅橡胶推广（排序1-5）',
        'floor_addon' => '首页·助剂填料推广（排序1-5）',
        'floor_equip' => '首页·机械设备推广（排序1-5）',
        'floor_silane' => '首页·硅烷推广（排序1-5）',
        'floor_glue' => '首页·胶粘剂推广（排序1-5）',
        'floor_resin' => '首页·硅树脂推广（排序1-5）',
      ),
      'required' => true,
      'hint' => '首页/商城/企业库/资讯顶部轮播都在这里配置，图片+跳转链接即可',
    ),
    2 => 
    array (
      'name' => 'type',
      'label' => '类型',
      'type' => 'select',
      'options' => 
      array (
        'image' => '图片',
        'text' => '文字',
      ),
      'required' => true,
    ),
    3 => 
    array (
      'name' => 'image',
      'label' => '图片',
      'type' => 'image',
    ),
    4 => 
    array (
      'name' => 'link',
      'label' => '跳转链接',
      'type' => 'text',
      'hint' => '站内填 /companies/旺铺名 或 /mall/123，外链填完整 http(s) 地址',
    ),
    5 => 
    array (
      'name' => 'content',
      'label' => '内容',
      'type' => 'textarea',
    ),
    6 => 
    array (
      'name' => 'sort',
      'label' => '排序',
      'type' => 'number',
    ),
    7 => 
    array (
      'name' => 'is_active',
      'label' => '启用',
      'type' => 'toggle',
    ),
    8 => 
    array (
      'name' => 'starts_at',
      'label' => '开始时间',
      'type' => 'datetime',
    ),
    9 => 
    array (
      'name' => 'ends_at',
      'label' => '结束时间',
      'type' => 'datetime',
    ),
    10 => 
    array (
      'name' => 'price',
      'label' => '价格',
      'type' => 'number',
    ),
  ),
  'ad-bookings' => 
  array (
    0 => 
    array (
      'name' => 'user_id',
      'label' => '用户',
      'type' => 'relation',
      'relation' => 'user',
      'relation_label' => 'name',
      'readonly' => true,
    ),
    1 => 
    array (
      'name' => 'place_code',
      'label' => '广告位',
      'type' => 'text',
      'readonly' => true,
    ),
    2 => 
    array (
      'name' => 'title',
      'label' => '标题',
      'type' => 'text',
      'readonly' => true,
    ),
    3 => 
    array (
      'name' => 'image',
      'label' => '物料图片',
      'type' => 'image',
      'readonly' => true,
    ),
    4 => 
    array (
      'name' => 'contact_name',
      'label' => '联系人',
      'type' => 'text',
      'readonly' => true,
    ),
    5 => 
    array (
      'name' => 'contact_phone',
      'label' => '联系电话',
      'type' => 'text',
      'readonly' => true,
    ),
    6 => 
    array (
      'name' => 'remark',
      'label' => '备注',
      'type' => 'textarea',
      'readonly' => true,
    ),
    7 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'pending' => '待审核',
        'approved' => '已通过',
        'rejected' => '已拒绝',
      ),
      'required' => true,
    ),
  ),
  'spreads' => 
  array (
    0 => 
    array (
      'name' => 'user_id',
      'label' => '用户',
      'type' => 'relation',
      'relation' => 'user',
      'relation_label' => 'name',
      'readonly' => true,
    ),
    1 => 
    array (
      'name' => 'module',
      'label' => '模块',
      'type' => 'select',
      'options' => 
      array (
        'sell' => '供应',
        'buy' => '求购',
        'company' => '企业',
        'mall' => '商城',
      ),
      'readonly' => true,
    ),
    2 => 
    array (
      'name' => 'keyword',
      'label' => '关键词',
      'type' => 'text',
      'readonly' => true,
    ),
    3 => 
    array (
      'name' => 'bid',
      'label' => '出价',
      'type' => 'number',
      'readonly' => true,
    ),
    4 => 
    array (
      'name' => 'days',
      'label' => '天数',
      'type' => 'number',
      'readonly' => true,
    ),
    5 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'pending' => '待审核',
        'active' => '已生效',
        'rejected' => '已拒绝',
      ),
      'required' => true,
    ),
    6 => 
    array (
      'name' => 'starts_at',
      'label' => '开始时间',
      'type' => 'datetime',
    ),
    7 => 
    array (
      'name' => 'ends_at',
      'label' => '结束时间',
      'type' => 'datetime',
    ),
  ),
  'announcements' => 
  array (
    0 => 
    array (
      'name' => 'title',
      'label' => '标题',
      'type' => 'text',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'content',
      'label' => '内容',
      'type' => 'richtext',
      'placeholder' => '请输入公告内容，支持标题、列表、链接等排版',
    ),
    2 => 
    array (
      'name' => 'link',
      'label' => '外链（可选）',
      'type' => 'text',
      'hint' => '仅填 http(s) 外链时，首页点击会直接打开外链；站内路径请留空，点击会进入本条公告详情',
    ),
    3 => 
    array (
      'name' => 'sort',
      'label' => '排序',
      'type' => 'number',
    ),
    4 => 
    array (
      'name' => 'is_active',
      'label' => '启用',
      'type' => 'toggle',
    ),
  ),
  'hotwords' => 
  array (
    0 => 
    array (
      'name' => 'word',
      'label' => '热词',
      'type' => 'text',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'module',
      'label' => '模块',
      'type' => 'select',
      'options' => 
      array (
        'sell' => '供应',
        'buy' => '求购',
        'company' => '企业',
        'news' => '资讯',
        'mall' => '商城',
      ),
      'required' => true,
    ),
    2 => 
    array (
      'name' => 'sort',
      'label' => '排序',
      'type' => 'number',
    ),
    3 => 
    array (
      'name' => 'is_active',
      'label' => '启用',
      'type' => 'toggle',
    ),
  ),
  'friend-links' => 
  array (
    0 => 
    array (
      'name' => 'name',
      'label' => '名称',
      'type' => 'text',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'url',
      'label' => '链接',
      'type' => 'text',
      'required' => true,
    ),
    2 => 
    array (
      'name' => 'logo',
      'label' => 'Logo',
      'type' => 'image',
    ),
    3 => 
    array (
      'name' => 'sort',
      'label' => '排序',
      'type' => 'number',
    ),
    4 => 
    array (
      'name' => 'is_active',
      'label' => '启用',
      'type' => 'toggle',
    ),
  ),
  'pages' => 
  array (
    0 => 
    array (
      'name' => 'slug',
      'label' => 'Slug',
      'type' => 'text',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'title',
      'label' => '标题',
      'type' => 'text',
      'required' => true,
    ),
    2 => 
    array (
      'name' => 'content',
      'label' => '内容',
      'type' => 'richtext',
    ),
  ),
  'categories' => 
  array (
    0 => 
    array (
      'name' => 'parent_id',
      'label' => '父级分类',
      'type' => 'category_parent',
      'hint' => '点开选择父分类，不选就是顶级',
    ),
    1 => 
    array (
      'name' => 'icon',
      'label' => '侧栏图标',
      'type' => 'category_icon',
      'hint' => '父级分类在首页左侧名称前面的图标',
    ),
    2 => 
    array (
      'name' => 'name',
      'label' => '名称',
      'type' => 'text',
      'required' => true,
    ),
    3 => 
    array (
      'name' => 'slug',
      'label' => 'Slug',
      'type' => 'text',
      'required' => true,
    ),
    4 => 
    array (
      'name' => 'module',
      'label' => '模块',
      'type' => 'select',
      'required' => true,
      'options' => 
      array (
        'sell' => '供应',
        'buy' => '采购',
        'mall' => '商城',
        'company' => '企业库',
        'news' => '资讯',
      ),
    ),
    5 => 
    array (
      'name' => 'sort',
      'label' => '排序',
      'type' => 'number',
    ),
    6 => 
    array (
      'name' => 'is_active',
      'label' => '启用',
      'type' => 'toggle',
    ),
  ),
  'orders' => 
  array (
    0 => 
    array (
      'name' => 'order_no',
      'label' => '订单号',
      'type' => 'text',
      'readonly' => true,
    ),
    1 => 
    array (
      'name' => 'user_id',
      'label' => '用户',
      'type' => 'relation',
      'relation' => 'user',
      'relation_label' => 'name',
      'readonly' => true,
    ),
    2 => 
    array (
      'name' => 'amount',
      'label' => '金额',
      'type' => 'number',
      'readonly' => true,
    ),
    3 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'pending' => '待付款',
        'paid' => '已付款',
        'shipped' => '已发货',
        'completed' => '已完成',
        'cancelled' => '已取消',
        'refunded' => '已退款',
      ),
      'required' => true,
    ),
    4 => 
    array (
      'name' => 'payment_method',
      'label' => '支付方式',
      'type' => 'text',
      'readonly' => true,
    ),
    5 => 
    array (
      'name' => 'receiver_name',
      'label' => '收货人',
      'type' => 'text',
    ),
    6 => 
    array (
      'name' => 'receiver_phone',
      'label' => '收货电话',
      'type' => 'text',
    ),
    7 => 
    array (
      'name' => 'receiver_address',
      'label' => '收货地址',
      'type' => 'textarea',
    ),
    8 => 
    array (
      'name' => 'remark',
      'label' => '备注',
      'type' => 'textarea',
    ),
  ),
  'inquiries' => 
  array (
  ),
  'reviews' => 
  array (
    0 => 
    array (
      'name' => 'product_id',
      'label' => '商品',
      'type' => 'relation',
      'relation' => 'product',
      'relation_label' => 'title',
      'readonly' => true,
    ),
    1 => 
    array (
      'name' => 'user_id',
      'label' => '用户',
      'type' => 'relation',
      'relation' => 'user',
      'relation_label' => 'name',
      'readonly' => true,
    ),
    2 => 
    array (
      'name' => 'rating',
      'label' => '评分',
      'type' => 'number',
      'readonly' => true,
    ),
    3 => 
    array (
      'name' => 'content',
      'label' => '评价内容',
      'type' => 'textarea',
      'readonly' => true,
    ),
    4 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'published' => '已发布',
        'hidden' => '已隐藏',
      ),
      'required' => true,
    ),
  ),
  'withdraws' => 
  array (
    0 => 
    array (
      'name' => 'user_id',
      'label' => '会员',
      'type' => 'relation',
      'relation' => 'user',
      'relation_label' => 'username',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'amount',
      'label' => '金额',
      'type' => 'number',
      'required' => true,
    ),
    2 => 
    array (
      'name' => 'bank_name',
      'label' => '开户行',
      'type' => 'text',
    ),
    3 => 
    array (
      'name' => 'bank_account',
      'label' => '账号',
      'type' => 'text',
    ),
    4 => 
    array (
      'name' => 'account_name',
      'label' => '户名',
      'type' => 'text',
    ),
    5 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'pending' => '待审核',
        'approved' => '已通过',
        'rejected' => '已拒绝',
      ),
      'required' => true,
    ),
    6 => 
    array (
      'name' => 'remark',
      'label' => '备注',
      'type' => 'textarea',
    ),
  ),
  'vip-applications' => 
  array (
    0 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'pending' => '待审核',
        'approved' => '已通过',
        'rejected' => '已拒绝',
      ),
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'user_id',
      'label' => '用户',
      'type' => 'relation',
      'relation' => 'user',
      'relation_label' => 'name',
      'readonly' => true,
    ),
    2 => 
    array (
      'name' => 'vip_grade_id',
      'label' => 'VIP等级',
      'type' => 'relation',
      'relation' => 'grade',
      'relation_label' => 'name',
      'readonly' => true,
    ),
    3 => 
    array (
      'name' => 'company_name',
      'label' => '公司名称',
      'type' => 'text',
      'readonly' => true,
    ),
    4 => 
    array (
      'name' => 'contact_name',
      'label' => '联系人',
      'type' => 'text',
      'readonly' => true,
    ),
    5 => 
    array (
      'name' => 'phone',
      'label' => '电话',
      'type' => 'text',
      'readonly' => true,
    ),
    6 => 
    array (
      'name' => 'mobile',
      'label' => '手机',
      'type' => 'text',
      'readonly' => true,
    ),
    7 => 
    array (
      'name' => 'email',
      'label' => '邮箱',
      'type' => 'text',
      'readonly' => true,
    ),
    8 => 
    array (
      'name' => 'qq',
      'label' => 'QQ',
      'type' => 'text',
      'readonly' => true,
    ),
    9 => 
    array (
      'name' => 'remark',
      'label' => '备注',
      'type' => 'textarea',
      'readonly' => true,
    ),
  ),
  'vip-pay-orders' => 
  array (
    0 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'pending_pay' => '待付款',
        'pending_review' => '待审核',
        'approved' => '已开通',
        'rejected' => '已驳回',
        'expired' => '已超时',
        'cancelled' => '已取消',
      ),
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'order_no',
      'label' => '订单号',
      'type' => 'text',
      'readonly' => true,
    ),
    2 => 
    array (
      'name' => 'user_id',
      'label' => '用户',
      'type' => 'relation',
      'relation' => 'user',
      'relation_label' => 'name',
      'readonly' => true,
    ),
    3 => 
    array (
      'name' => 'vip_grade_id',
      'label' => 'VIP档位',
      'type' => 'relation',
      'relation' => 'grade',
      'relation_label' => 'name',
      'readonly' => true,
    ),
    4 => 
    array (
      'name' => 'amount',
      'label' => '应付金额',
      'type' => 'text',
      'readonly' => true,
    ),
    5 => 
    array (
      'name' => 'company_name',
      'label' => '公司名称',
      'type' => 'text',
      'readonly' => true,
    ),
    6 => 
    array (
      'name' => 'contact_name',
      'label' => '联系人',
      'type' => 'text',
      'readonly' => true,
    ),
    7 => 
    array (
      'name' => 'mobile',
      'label' => '手机',
      'type' => 'text',
      'readonly' => true,
    ),
    8 => 
    array (
      'name' => 'transfer_note',
      'label' => '转账备注(订单号)',
      'type' => 'text',
      'readonly' => true,
    ),
    9 => 
    array (
      'name' => 'bank_account_name',
      'label' => '收款户名',
      'type' => 'text',
      'readonly' => true,
    ),
    10 => 
    array (
      'name' => 'bank_name',
      'label' => '开户行',
      'type' => 'text',
      'readonly' => true,
    ),
    11 => 
    array (
      'name' => 'bank_account',
      'label' => '收款账号',
      'type' => 'text',
      'readonly' => true,
    ),
    12 => 
    array (
      'name' => 'voucher_url',
      'label' => '付款凭证',
      'type' => 'image',
      'readonly' => true,
      'hint' => '点击大图可打开原图核对金额、账号与时间',
    ),
    13 => 
    array (
      'name' => 'voucher_uploaded_at',
      'label' => '传凭证时间',
      'type' => 'text',
      'readonly' => true,
    ),
    14 => 
    array (
      'name' => 'expire_at',
      'label' => '支付截止',
      'type' => 'text',
      'readonly' => true,
    ),
    15 => 
    array (
      'name' => 'remark',
      'label' => '用户备注/开票',
      'type' => 'textarea',
      'readonly' => true,
    ),
    16 => 
    array (
      'name' => 'admin_remark',
      'label' => '审核备注（驳回时必填，用户可见）',
      'type' => 'textarea',
      'hint' => '拒绝时必须填写原因；通过时可留空或写内部说明',
    ),
    17 => 
    array (
      'name' => 'reviewed_at',
      'label' => '审核时间',
      'type' => 'text',
      'readonly' => true,
    ),
    18 => 
    array (
      'name' => 'reviewed_by',
      'label' => '审核人ID',
      'type' => 'text',
      'readonly' => true,
    ),
  ),
  'fund-pay-orders' =>
  array (
    0 =>
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' =>
      array (
        'pending_pay' => '待付款',
        'pending_review' => '待审核',
        'approved' => '已通过',
        'rejected' => '已驳回',
        'expired' => '已超时',
        'cancelled' => '已取消',
      ),
      'required' => true,
    ),
    1 =>
    array (
      'name' => 'order_no',
      'label' => '订单号',
      'type' => 'text',
      'readonly' => true,
    ),
    2 =>
    array (
      'name' => 'user_id',
      'label' => '用户',
      'type' => 'relation',
      'relation' => 'user',
      'relation_label' => 'name',
      'readonly' => true,
    ),
    3 =>
    array (
      'name' => 'title',
      'label' => '类型',
      'type' => 'text',
      'readonly' => true,
    ),
    4 =>
    array (
      'name' => 'amount',
      'label' => '应付金额',
      'type' => 'text',
      'readonly' => true,
    ),
    5 =>
    array (
      'name' => 'transfer_note',
      'label' => '转账备注(订单号)',
      'type' => 'text',
      'readonly' => true,
    ),
    6 =>
    array (
      'name' => 'bank_account_name',
      'label' => '收款户名',
      'type' => 'text',
      'readonly' => true,
    ),
    7 =>
    array (
      'name' => 'bank_name',
      'label' => '开户行',
      'type' => 'text',
      'readonly' => true,
    ),
    8 =>
    array (
      'name' => 'bank_account',
      'label' => '收款账号',
      'type' => 'text',
      'readonly' => true,
    ),
    9 =>
    array (
      'name' => 'voucher_url',
      'label' => '付款凭证',
      'type' => 'image',
      'readonly' => true,
      'hint' => '点击大图可打开原图核对金额、账号与时间',
    ),
    10 =>
    array (
      'name' => 'voucher_uploaded_at',
      'label' => '传凭证时间',
      'type' => 'text',
      'readonly' => true,
    ),
    11 =>
    array (
      'name' => 'expire_at',
      'label' => '支付截止',
      'type' => 'text',
      'readonly' => true,
    ),
    12 =>
    array (
      'name' => 'remark',
      'label' => '用户备注',
      'type' => 'textarea',
      'readonly' => true,
    ),
    13 =>
    array (
      'name' => 'admin_remark',
      'label' => '审核备注（驳回时必填，用户可见）',
      'type' => 'textarea',
      'hint' => '拒绝时必须填写原因；通过时可留空或写内部说明',
    ),
    14 =>
    array (
      'name' => 'reviewed_at',
      'label' => '审核时间',
      'type' => 'text',
      'readonly' => true,
    ),
    15 =>
    array (
      'name' => 'reviewed_by',
      'label' => '审核人ID',
      'type' => 'text',
      'readonly' => true,
    ),
  ),
  'ask-tickets' => 
  array (
    0 => 
    array (
      'name' => 'user_id',
      'label' => '会员',
      'type' => 'relation',
      'relation' => 'user',
      'relation_label' => 'username',
    ),
    1 => 
    array (
      'name' => 'title',
      'label' => '标题',
      'type' => 'text',
      'required' => true,
    ),
    2 => 
    array (
      'name' => 'content',
      'label' => '内容',
      'type' => 'textarea',
    ),
    3 => 
    array (
      'name' => 'contact_name',
      'label' => '联系人',
      'type' => 'text',
    ),
    4 => 
    array (
      'name' => 'contact_phone',
      'label' => '电话',
      'type' => 'text',
    ),
    5 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'new' => '新工单',
        'replied' => '已回复',
        'closed' => '已关闭',
      ),
      'required' => true,
    ),
    6 => 
    array (
      'name' => 'reply',
      'label' => '回复',
      'type' => 'textarea',
    ),
  ),
  'jobs' => 
  array (
    0 => 
    array (
      'name' => 'title',
      'label' => '标题',
      'type' => 'text',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'company_name',
      'label' => '公司名称',
      'type' => 'text',
    ),
    2 => 
    array (
      'name' => 'area',
      'label' => '地区',
      'type' => 'text',
    ),
    3 => 
    array (
      'name' => 'salary',
      'label' => '薪资',
      'type' => 'text',
    ),
    4 => 
    array (
      'name' => 'education',
      'label' => '学历',
      'type' => 'text',
    ),
    5 => 
    array (
      'name' => 'experience',
      'label' => '经验',
      'type' => 'text',
    ),
    6 => 
    array (
      'name' => 'headcount',
      'label' => '人数',
      'type' => 'number',
    ),
    7 => 
    array (
      'name' => 'summary',
      'label' => '摘要',
      'type' => 'textarea',
    ),
    8 => 
    array (
      'name' => 'content',
      'label' => '内容',
      'type' => 'richtext',
    ),
    9 => 
    array (
      'name' => 'contact_name',
      'label' => '联系人',
      'type' => 'text',
    ),
    10 => 
    array (
      'name' => 'contact_phone',
      'label' => '联系电话',
      'type' => 'text',
    ),
    12 =>
    array (
      'name' => 'job_role',
      'label' => '职位类别',
      'type' => 'select',
      'options' =>
      array (
        '管理' => '管理',
        '研发' => '研发',
        '销售' => '销售',
        '文员' => '文员',
        '生产' => '生产',
        '品控' => '品控',
        '外贸' => '外贸',
        '行政' => '行政',
        '前台' => '前台',
        '其他' => '其他',
      ),
    ),
    13 =>
    array (
      'name' => 'job_category',
      'label' => '领域分类',
      'type' => 'text',
    ),
    11 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'approved' => '已通过',
        'pending' => '待审核',
        'rejected' => '已拒绝',
        'offline' => '已下架',
      ),
      'required' => true,
    ),
    12 => 
    array (
      'name' => 'views',
      'label' => '浏览',
      'type' => 'number',
    ),
  ),
  'resumes' => 
  array (
    0 => 
    array (
      'name' => 'title',
      'label' => '标题',
      'type' => 'text',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'truename',
      'label' => '姓名',
      'type' => 'text',
    ),
    2 => 
    array (
      'name' => 'gender',
      'label' => '性别',
      'type' => 'text',
    ),
    3 => 
    array (
      'name' => 'education',
      'label' => '学历',
      'type' => 'text',
    ),
    4 => 
    array (
      'name' => 'experience',
      'label' => '经验',
      'type' => 'text',
    ),
    5 => 
    array (
      'name' => 'minsalary',
      'label' => '期望薪资下限',
      'type' => 'number',
    ),
    6 => 
    array (
      'name' => 'maxsalary',
      'label' => '期望薪资上限',
      'type' => 'number',
    ),
    7 => 
    array (
      'name' => 'mobile',
      'label' => '手机',
      'type' => 'text',
    ),
    8 => 
    array (
      'name' => 'email',
      'label' => '邮箱',
      'type' => 'text',
    ),
    9 => 
    array (
      'name' => 'area',
      'label' => '地区',
      'type' => 'text',
    ),
    10 => 
    array (
      'name' => 'open',
      'label' => '公开',
      'type' => 'select',
      'options' => 
      array (
        'public' => '公开（人才库）',
        'private' => '仅自己',
      ),
    ),
    11 => 
    array (
      'name' => 'summary',
      'label' => '摘要',
      'type' => 'textarea',
    ),
    12 => 
    array (
      'name' => 'content',
      'label' => '详细经历',
      'type' => 'richtext',
    ),
    13 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'approved' => '已通过',
        'pending' => '待审核',
        'rejected' => '已拒绝',
        'offline' => '已下架',
      ),
      'required' => true,
    ),
  ),
  'know' => 
  array (
    0 => 
    array (
      'name' => 'title',
      'label' => '标题',
      'type' => 'text',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'content',
      'label' => '内容',
      'type' => 'richtext',
    ),
    2 => 
    array (
      'name' => 'reward',
      'label' => '悬赏',
      'type' => 'number',
    ),
    3 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'open' => '开放',
        'closed' => '已关闭',
      ),
      'required' => true,
    ),
    4 => 
    array (
      'name' => 'views',
      'label' => '浏览',
      'type' => 'number',
    ),
    5 => 
    array (
      'name' => 'answers_count',
      'label' => '回答数',
      'type' => 'number',
      'readonly' => true,
    ),
  ),
  'club' => 
  array (
    0 => 
    array (
      'name' => 'title',
      'label' => '标题',
      'type' => 'text',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'circle',
      'label' => '圈子',
      'type' => 'text',
    ),
    2 => 
    array (
      'name' => 'content',
      'label' => '内容',
      'type' => 'richtext',
    ),
    3 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'pending' => '待审核',
        'approved' => '已通过',
        'offline' => '已下架',
      ),
      'required' => true,
    ),
    4 => 
    array (
      'name' => 'views',
      'label' => '浏览',
      'type' => 'number',
    ),
  ),
  'brands' => 
  array (
    0 => 
    array (
      'name' => 'name',
      'label' => '名称',
      'type' => 'text',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'slug',
      'label' => 'Slug',
      'type' => 'text',
      'required' => true,
    ),
    2 => 
    array (
      'name' => 'logo',
      'label' => 'Logo',
      'type' => 'image',
    ),
    3 => 
    array (
      'name' => 'website',
      'label' => '官网',
      'type' => 'text',
    ),
    4 => 
    array (
      'name' => 'country',
      'label' => '国家',
      'type' => 'text',
    ),
    5 => 
    array (
      'name' => 'intro',
      'label' => '简介',
      'type' => 'textarea',
    ),
    6 => 
    array (
      'name' => 'content',
      'label' => '内容',
      'type' => 'richtext',
    ),
    7 => 
    array (
      'name' => 'sort',
      'label' => '排序',
      'type' => 'number',
    ),
    8 => 
    array (
      'name' => 'is_featured',
      'label' => '推荐',
      'type' => 'toggle',
    ),
    9 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'published' => '已发布',
        'offline' => '已下架',
      ),
      'required' => true,
    ),
  ),
  'downloads' => 
  array (
    0 => 
    array (
      'name' => 'title',
      'label' => '标题',
      'type' => 'text',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'category_id',
      'label' => '分类',
      'type' => 'category',
    ),
    2 => 
    array (
      'name' => 'file_url',
      'label' => '文件地址',
      'type' => 'text',
      'required' => true,
    ),
    3 => 
    array (
      'name' => 'file_type',
      'label' => '文件类型',
      'type' => 'text',
    ),
    4 => 
    array (
      'name' => 'file_size',
      'label' => '文件大小',
      'type' => 'text',
    ),
    5 => 
    array (
      'name' => 'summary',
      'label' => '摘要',
      'type' => 'textarea',
    ),
    6 => 
    array (
      'name' => 'sort',
      'label' => '排序',
      'type' => 'number',
    ),
    7 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'published' => '已发布',
        'offline' => '已下架',
      ),
      'required' => true,
    ),
    8 => 
    array (
      'name' => 'downloads',
      'label' => '下载次数',
      'type' => 'number',
    ),
    8 =>
    array (
      'name' => 'points_cost',
      'label' => '积分消耗',
      'type' => 'number',
    ),
    9 =>
    array (
      'name' => 'require_login',
      'label' => '需登录',
      'type' => 'toggle',
    ),
    10 =>
    array (
      'name' => 'min_group',
      'label' => '最低会员组',
      'type' => 'select',
      'options' =>
      array (
        'guest' => '游客',
        'personal' => '个人会员',
        'enterprise' => '企业会员',
        'vip' => 'VIP会员',
      ),
    ),
  ),
  'exhibits' => 
  array (
    0 => 
    array (
      'name' => 'title',
      'label' => '标题',
      'type' => 'text',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'area',
      'label' => '地区',
      'type' => 'text',
    ),
    2 => 
    array (
      'name' => 'venue',
      'label' => '场馆',
      'type' => 'text',
    ),
    3 => 
    array (
      'name' => 'starts_on',
      'label' => '开始日期',
      'type' => 'date',
    ),
    4 => 
    array (
      'name' => 'ends_on',
      'label' => '结束日期',
      'type' => 'date',
    ),
    5 => 
    array (
      'name' => 'organizer',
      'label' => '主办方',
      'type' => 'text',
    ),
    6 => 
    array (
      'name' => 'summary',
      'label' => '摘要',
      'type' => 'textarea',
    ),
    7 => 
    array (
      'name' => 'content',
      'label' => '内容',
      'type' => 'richtext',
    ),
    8 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'published' => '已发布',
        'offline' => '已下架',
      ),
      'required' => true,
    ),
    9 => 
    array (
      'name' => 'views',
      'label' => '浏览',
      'type' => 'number',
    ),
  ),
  'gifts' => 
  array (
    0 => 
    array (
      'name' => 'title',
      'label' => '标题',
      'type' => 'text',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'thumb',
      'label' => '缩略图',
      'type' => 'image',
    ),
    2 => 
    array (
      'name' => 'points',
      'label' => '积分',
      'type' => 'number',
      'required' => true,
    ),
    3 => 
    array (
      'name' => 'stock',
      'label' => '库存',
      'type' => 'number',
    ),
    4 => 
    array (
      'name' => 'summary',
      'label' => '摘要',
      'type' => 'textarea',
    ),
    5 => 
    array (
      'name' => 'content',
      'label' => '内容',
      'type' => 'richtext',
    ),
    6 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'published' => '已发布',
        'offline' => '已下架',
      ),
      'required' => true,
    ),
  ),
  'group-deals' => 
  array (
    0 => 
    array (
      'name' => 'title',
      'label' => '标题',
      'type' => 'text',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'thumb',
      'label' => '缩略图',
      'type' => 'image',
    ),
    2 => 
    array (
      'name' => 'price',
      'label' => '团购价',
      'type' => 'number',
    ),
    3 => 
    array (
      'name' => 'market_price',
      'label' => '市场价',
      'type' => 'number',
    ),
    4 => 
    array (
      'name' => 'stock',
      'label' => '库存',
      'type' => 'number',
    ),
    5 => 
    array (
      'name' => 'sold',
      'label' => '已售',
      'type' => 'number',
    ),
    6 => 
    array (
      'name' => 'ends_at',
      'label' => '结束时间',
      'type' => 'datetime',
    ),
    7 => 
    array (
      'name' => 'summary',
      'label' => '摘要',
      'type' => 'textarea',
    ),
    8 => 
    array (
      'name' => 'content',
      'label' => '内容',
      'type' => 'richtext',
    ),
    9 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'published' => '已发布',
        'offline' => '已下架',
      ),
      'required' => true,
    ),
    10 => 
    array (
      'name' => 'views',
      'label' => '浏览',
      'type' => 'number',
    ),
  ),
  'photos' => 
  array (
    0 => 
    array (
      'name' => 'title',
      'label' => '标题',
      'type' => 'text',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'thumb',
      'label' => '缩略图',
      'type' => 'image',
    ),
    2 => 
    array (
      'name' => 'summary',
      'label' => '摘要',
      'type' => 'textarea',
    ),
    3 => 
    array (
      'name' => 'content',
      'label' => '内容',
      'type' => 'richtext',
    ),
    4 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'published' => '已发布',
        'offline' => '已下架',
      ),
      'required' => true,
    ),
    5 => 
    array (
      'name' => 'views',
      'label' => '浏览',
      'type' => 'number',
    ),
  ),
  'videos' => 
  array (
    0 => 
    array (
      'name' => 'title',
      'label' => '标题',
      'type' => 'text',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'thumb',
      'label' => '缩略图',
      'type' => 'image',
    ),
    2 => 
    array (
      'name' => 'video_url',
      'label' => '视频地址',
      'type' => 'text',
    ),
    3 => 
    array (
      'name' => 'summary',
      'label' => '摘要',
      'type' => 'textarea',
    ),
    4 => 
    array (
      'name' => 'content',
      'label' => '内容',
      'type' => 'richtext',
    ),
    5 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'published' => '已发布',
        'offline' => '已下架',
      ),
      'required' => true,
    ),
    6 => 
    array (
      'name' => 'views',
      'label' => '浏览',
      'type' => 'number',
    ),
  ),
  'specials' => 
  array (
    0 => 
    array (
      'name' => 'name',
      'label' => '名称',
      'type' => 'text',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'slug',
      'label' => 'Slug',
      'type' => 'text',
      'required' => true,
    ),
    2 => 
    array (
      'name' => 'thumb',
      'label' => '缩略图',
      'type' => 'image',
    ),
    3 => 
    array (
      'name' => 'summary',
      'label' => '摘要',
      'type' => 'textarea',
    ),
    4 => 
    array (
      'name' => 'content',
      'label' => '内容',
      'type' => 'richtext',
    ),
    5 => 
    array (
      'name' => 'sort',
      'label' => '排序',
      'type' => 'number',
    ),
    6 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'published' => '已发布',
        'offline' => '已下架',
      ),
      'required' => true,
    ),
    7 => 
    array (
      'name' => 'views',
      'label' => '浏览',
      'type' => 'number',
    ),
  ),
  'special-items' =>
  array (
    0 =>
    array (
      'name' => 'special_id',
      'label' => '所属专题',
      'type' => 'relation',
      'relation' => 'special',
      'relation_label' => 'name',
      'required' => true,
    ),
    1 =>
    array (
      'name' => 'title',
      'label' => '标题',
      'type' => 'text',
      'required' => true,
    ),
    2 =>
    array (
      'name' => 'item_type',
      'label' => '类型',
      'type' => 'select',
      'options' =>
      array (
        'article' => '资讯',
        'product' => '商品',
        'supply' => '供应',
        'company' => '企业',
        'download' => '下载',
        'custom' => '自定义链接',
      ),
    ),
    3 =>
    array (
      'name' => 'item_id',
      'label' => '关联ID',
      'type' => 'number',
    ),
    4 =>
    array (
      'name' => 'thumb',
      'label' => '缩略图',
      'type' => 'image',
    ),
    5 =>
    array (
      'name' => 'link',
      'label' => '链接',
      'type' => 'text',
    ),
    6 =>
    array (
      'name' => 'summary',
      'label' => '摘要',
      'type' => 'textarea',
    ),
    7 =>
    array (
      'name' => 'sort',
      'label' => '排序',
      'type' => 'number',
    ),
    8 =>
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' =>
      array (
        'published' => '已发布',
        'offline' => '已下架',
      ),
      'required' => true,
    ),
  ),
  'quote-products' => 
  array (
    0 => 
    array (
      'name' => 'name',
      'label' => '产品名称',
      'type' => 'text',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'unit',
      'label' => '单位',
      'type' => 'text',
    ),
    2 => 
    array (
      'name' => 'price_min',
      'label' => '最低价',
      'type' => 'number',
    ),
    3 => 
    array (
      'name' => 'price_max',
      'label' => '最高价',
      'type' => 'number',
    ),
    4 => 
    array (
      'name' => 'price_avg',
      'label' => '均价',
      'type' => 'number',
    ),
    5 => 
    array (
      'name' => 'market',
      'label' => '市场',
      'type' => 'text',
    ),
    6 => 
    array (
      'name' => 'quoted_on',
      'label' => '报价日期',
      'type' => 'date',
    ),
    7 => 
    array (
      'name' => 'sort',
      'label' => '排序',
      'type' => 'number',
    ),
    8 => 
    array (
      'name' => 'is_active',
      'label' => '启用',
      'type' => 'toggle',
    ),
  ),
  'invest-projects' => 
  array (
    0 => 
    array (
      'name' => 'title',
      'label' => '标题',
      'type' => 'text',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'area',
      'label' => '区域',
      'type' => 'text',
    ),
    2 => 
    array (
      'name' => 'amount',
      'label' => '金额',
      'type' => 'text',
    ),
    3 => 
    array (
      'name' => 'summary',
      'label' => '摘要',
      'type' => 'textarea',
    ),
    4 => 
    array (
      'name' => 'content',
      'label' => '内容',
      'type' => 'richtext',
    ),
    5 => 
    array (
      'name' => 'contact_name',
      'label' => '联系人',
      'type' => 'text',
    ),
    6 => 
    array (
      'name' => 'contact_phone',
      'label' => '联系电话',
      'type' => 'text',
    ),
    7 => 
    array (
      'name' => 'sort',
      'label' => '排序',
      'type' => 'number',
    ),
    8 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'published' => '已发布',
        'offline' => '已下架',
      ),
      'required' => true,
    ),
  ),
  'invest-leads' => 
  array (
    0 => 
    array (
      'name' => 'invest_project_id',
      'label' => '招商项目',
      'type' => 'relation',
      'relation' => 'project',
      'relation_label' => 'title',
      'readonly' => true,
    ),
    1 => 
    array (
      'name' => 'user_id',
      'label' => '用户',
      'type' => 'relation',
      'relation' => 'user',
      'relation_label' => 'name',
      'readonly' => true,
    ),
    2 => 
    array (
      'name' => 'company_name',
      'label' => '公司名称',
      'type' => 'text',
      'readonly' => true,
    ),
    3 => 
    array (
      'name' => 'contact_name',
      'label' => '联系人',
      'type' => 'text',
      'readonly' => true,
    ),
    4 => 
    array (
      'name' => 'contact_phone',
      'label' => '联系电话',
      'type' => 'text',
      'readonly' => true,
    ),
    5 => 
    array (
      'name' => 'content',
      'label' => '留言',
      'type' => 'textarea',
      'readonly' => true,
    ),
    6 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'new' => '新线索',
        'done' => '已处理',
        'rejected' => '已拒绝',
      ),
      'required' => true,
    ),
  ),
  'company-news' =>
  array (
    0 =>
    array (
      'name' => 'company_id',
      'label' => '企业',
      'type' => 'relation',
      'relation' => 'company',
      'relation_label' => 'name',
      'required' => true,
    ),
    1 =>
    array (
      'name' => 'title',
      'label' => '标题',
      'type' => 'text',
      'required' => true,
    ),
    2 =>
    array (
      'name' => 'content',
      'label' => '内容',
      'type' => 'richtext',
    ),
    3 =>
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' =>
      array (
        'published' => '发布',
        'pending' => '待审',
        'offline' => '下架',
      ),
      'required' => true,
    ),
  ),
  'company-honors' =>
  array (
    0 =>
    array (
      'name' => 'company_id',
      'label' => '企业',
      'type' => 'relation',
      'relation' => 'company',
      'relation_label' => 'name',
      'required' => true,
    ),
    1 =>
    array (
      'name' => 'title',
      'label' => '证书名称',
      'type' => 'text',
      'required' => true,
    ),
    2 =>
    array (
      'name' => 'image',
      'label' => '证书图片',
      'type' => 'image',
    ),
    3 =>
    array (
      'name' => 'authority',
      'label' => '发证机构',
      'type' => 'text',
    ),
    4 =>
    array (
      'name' => 'issued_at',
      'label' => '发证日期',
      'type' => 'date',
    ),
    5 =>
    array (
      'name' => 'expires_at',
      'label' => '到期日期',
      'type' => 'date',
      'hint' => '留空表示永久有效',
    ),
    6 =>
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' =>
      array (
        'pending' => '待审',
        'approved' => '通过',
        'rejected' => '未通过',
        'expired' => '过期',
        'offline' => '回收站',
      ),
    ),
  ),
  'company-pages' =>
  array (
    0 =>
    array (
      'name' => 'company_id',
      'label' => '企业',
      'type' => 'relation',
      'relation' => 'company',
      'relation_label' => 'name',
      'required' => true,
    ),
    1 =>
    array (
      'name' => 'title',
      'label' => '标题',
      'type' => 'text',
      'required' => true,
    ),
    2 =>
    array (
      'name' => 'slug',
      'label' => '别名',
      'type' => 'text',
      'required' => true,
    ),
    3 =>
    array (
      'name' => 'content',
      'label' => '内容',
      'type' => 'richtext',
    ),
    4 =>
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' =>
      array (
        'published' => '发布',
        'offline' => '下架',
      ),
      'required' => true,
    ),
    5 =>
    array (
      'name' => 'sort',
      'label' => '排序',
      'type' => 'number',
    ),
  ),
  'company-links' =>
  array (
    0 =>
    array (
      'name' => 'company_id',
      'label' => '企业',
      'type' => 'relation',
      'relation' => 'company',
      'relation_label' => 'name',
      'required' => true,
    ),
    1 =>
    array (
      'name' => 'name',
      'label' => '名称',
      'type' => 'text',
      'required' => true,
    ),
    2 =>
    array (
      'name' => 'url',
      'label' => '链接',
      'type' => 'text',
      'required' => true,
    ),
    3 =>
    array (
      'name' => 'sort',
      'label' => '排序',
      'type' => 'number',
    ),
  ),
  'member-verifications' =>
  array (
    0 =>
    array (
      'name' => 'type',
      'label' => '类型',
      'type' => 'select',
      'readonly' => true,
      'options' =>
      array (
        'mobile' => '手机',
        'truename' => '实名',
        'bank' => '银行',
      ),
    ),
    1 =>
    array (
      'name' => 'mobile',
      'label' => '手机号',
      'type' => 'text',
      'readonly' => true,
    ),
    2 =>
    array (
      'name' => 'truename',
      'label' => '真实姓名',
      'type' => 'text',
      'readonly' => true,
    ),
    3 =>
    array (
      'name' => 'bank_name',
      'label' => '开户行',
      'type' => 'text',
      'readonly' => true,
    ),
    4 =>
    array (
      'name' => 'bank_account',
      'label' => '银行账号',
      'type' => 'text',
      'readonly' => true,
    ),
    5 =>
    array (
      'name' => 'status',
      'label' => '审核',
      'type' => 'select',
      'required' => true,
      'options' =>
      array (
        'pending' => '待审核',
        'approved' => '通过',
        'rejected' => '拒绝',
      ),
    ),
    6 =>
    array (
      'name' => 'admin_remark',
      'label' => '驳回说明',
      'type' => 'textarea',
    ),
  ),
);
