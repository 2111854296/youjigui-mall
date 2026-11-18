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
        0 => '普通',
        1 => 'VIP',
        2 => '高级VIP',
        3 => '企业VIP',
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
      'label' => '用户',
      'type' => 'relation',
      'relation' => 'user',
      'relation_label' => 'name',
      'required' => true,
    ),
    1 => 
    array (
      'name' => 'name',
      'label' => '企业名称',
      'type' => 'text',
      'required' => true,
    ),
    2 => 
    array (
      'name' => 'logo',
      'label' => '企业Logo',
      'type' => 'image',
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
      'name' => 'template',
      'label' => '旺铺模板',
      'type' => 'select',
      'options' => 
      array (
        'default' => '经典橙',
        'blue' => '商务蓝',
        'orange' => '活力橙深',
      ),
    ),
    5 => 
    array (
      'name' => 'contact',
      'label' => '联系人',
      'type' => 'text',
    ),
    6 => 
    array (
      'name' => 'phone',
      'label' => '电话',
      'type' => 'text',
    ),
    7 => 
    array (
      'name' => 'website',
      'label' => '网站',
      'type' => 'text',
    ),
    8 => 
    array (
      'name' => 'address',
      'label' => '地址',
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
      'name' => 'business',
      'label' => '经营范围',
      'type' => 'textarea',
    ),
    11 => 
    array (
      'name' => 'intro',
      'label' => '简介',
      'type' => 'textarea',
    ),
    12 => 
    array (
      'name' => 'content',
      'label' => '详情',
      'type' => 'richtext',
    ),
    13 => 
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
    14 => 
    array (
      'name' => 'is_featured',
      'label' => '推荐',
      'type' => 'toggle',
    ),
    15 => 
    array (
      'name' => 'is_certified',
      'label' => '认证',
      'type' => 'toggle',
    ),
  ),
  'articles' => 
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
      'label' => '标题图片',
      'type' => 'image',
    ),
    2 => 
    array (
      'name' => 'category_id',
      'label' => '分类',
      'type' => 'category',
    ),
    3 => 
    array (
      'name' => 'author',
      'label' => '作者',
      'type' => 'text',
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
    7 => 
    array (
      'name' => 'is_top',
      'label' => '置顶',
      'type' => 'toggle',
    ),
    8 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'draft' => '草稿',
        'published' => '已发布',
        'offline' => '已下架',
      ),
      'required' => true,
    ),
    9 => 
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
      'type' => 'text',
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
        'home_banner' => '首页横幅',
        'home_right' => '首页右侧',
        'sell_right' => '供应右侧',
        'buy_right' => '求购右侧',
        'mall_top' => '商城顶部',
        'news_top' => '资讯顶部',
        'footer' => '页脚',
      ),
      'required' => true,
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
        'code' => '代码',
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
      'label' => '链接',
      'type' => 'text',
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
      'type' => 'textarea',
    ),
    2 => 
    array (
      'name' => 'link',
      'label' => '链接',
      'type' => 'text',
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
      'type' => 'number',
      'hint' => '父分类 ID，空为顶级',
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
      'name' => 'slug',
      'label' => 'Slug',
      'type' => 'text',
      'required' => true,
    ),
    3 => 
    array (
      'name' => 'module',
      'label' => '模块',
      'type' => 'text',
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
    0 => 
    array (
      'name' => 'contact_name',
      'label' => '联系人',
      'type' => 'text',
    ),
    1 => 
    array (
      'name' => 'contact_phone',
      'label' => '联系电话',
      'type' => 'text',
    ),
    2 => 
    array (
      'name' => 'contact_email',
      'label' => '联系邮箱',
      'type' => 'email',
    ),
    3 => 
    array (
      'name' => 'title',
      'label' => '标题',
      'type' => 'text',
      'required' => true,
    ),
    4 => 
    array (
      'name' => 'content',
      'label' => '内容',
      'type' => 'textarea',
    ),
    5 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'new' => '新询盘',
        'processing' => '处理中',
        'done' => '已完成',
      ),
      'required' => true,
    ),
    6 => 
    array (
      'name' => 'to_user_id',
      'label' => '接收用户',
      'type' => 'relation',
      'relation' => 'toUser',
      'relation_label' => 'name',
    ),
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
    11 => 
    array (
      'name' => 'status',
      'label' => '状态',
      'type' => 'select',
      'options' => 
      array (
        'approved' => '已通过',
        'pending' => '待审核',
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
);
