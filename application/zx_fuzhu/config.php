<?php
return [
	'version' => "V0.11.2",
	// 额外信息，用于IP备案
	'extraInfo' => [
		"单位性质" => "unitProperty",
		"单位分类" => "unitCategory",
		"行业分类" => "industryCategory",
		"单位证件类型" => "credential",
		"单位证件号" => "credentialnum",
		"所在省" => "province",
		"所在市" => "city",
		"所在县区" => "county",
		"应用服务类型" => "appServType",
		"单位属性" => "unitAttribute",
		"网络安全责任人" => "securityPerson",
		"责任人身份证号" => "securityPersonID",
		"责任人电话" => "securityPhone",
		"责任人邮箱" => "securityEmail",
		"邮政编码" => "zipCode",
	],
	// 列宽度
	'colWidth' => [
		0 => 98,
		1 => 96,
		2 => 65,
		3 => 50,
		4 => 65,
		5 => 100,
		6 => 240,
		7 => 220,
		8 => 450,
		9 => 50,
		10 => 116,
		11 => 116,
		12 => 107,
		13 => 107,
		14 => 164,
		15 => 93,
		16 => 96,
		17 => 164,
		18 => 200,
		19 => 75,
		20 => 200,
		21 => 60,
		22 => 107,
		23 => 164,
		24 => 98,
		25 => 98,
		26 => 50,
		27 => 80,
		28 => 0,
		29 => 76,
		30 => 76,
		31 => 76,
		32 => 132,
		33 => 107,
		34 => 62,
		35 => 62,
		36 => 76,
		37 => 104,
		38 => 90,
		39 => 90,
		40 => 107,
		41 => 96,
		42 => 164,
		43 => 62,
	],
	/* 生成IDC备案信息使用（与 index/ipbeian.html 里定义的一致）*/
	// 单位性质
	'unitProperty' => [
		1 => '军队',
		2 => '政府机关',
		3 => '事业单位',
		4 => '企业',
		5 => '个人',
		6 => '社会团体',
		9 => '民办非企业',
		10 => '基金会',
		11 => '律师事务所',
		12 => '外国文化中心',
		13 => '群团组织',
		14 => '司法鉴定所',
	],
	// 单位分类
	'unitCategory' => [
		11 => '电信业务经营者',
		12 => '公益性互联网络单位',
		13 => '其他',
		15 => '前置单位',
	],
	// 行业分类
	'industryCategory' => [
		1 => '农、林、牧、渔业',
		2 => '采矿业',
		3 => '制造业',
		4 => '电力、燃气及水的生产和供应业',
		5 => '建筑业',
		6 => '交通运输、仓储和邮政业',
		7 => '信息传输、计算机服务和软件业',
		8 => '批发和零售业',
		9 => '住宿和餐饮业',
		10 => '金融业',
		11 => '房地产业',
		12 => '租赁和商务服务业',
		13 => '科学研究、技术服务和地质勘查业',
		14 => '水利、环境和公共设施管理业',
		15 => '居民服务和其他服务业',
		16 => '教育',
		17 => '卫生、社会保障和社会福利业',
		18 => '文化、体育和娱乐业',
		19 => '公共管理与社会组织',
		20 => '国际组织',
	],
	// 使用单位证件类型
	'credential' => [
		1 => '工商营业执照',
		2 => '身份证',
		3 => '组织机构代码证书',
		4 => '事业法人证书',
		5 => '军队代号',
		6 => '社团法人证书',
		7 => '护照',
		8 => '军官证',
		9 => '组织机构代码证书',
		10 => '组织机构代码证书',
		11 => '台胞证',
		12 => '组织机构代码证书',
		13 => '统一社会信用代码',
		14 => '港澳居民来往内地通行证',
		16 => '组织机构代码证书',
		17 => '民办非企业单位登记证书',
		18 => '组织机构代码证书',
		19 => '基金会法人登记证书',
		20 => '组织机构代码证书',
		21 => '律师事务所执业许可证',
		22 => '统一社会信用代码',
		23 => '有偿服务许可证',
		24 => '统一社会信用代码',
		25 => '宗教活动场所登记证',
		27 => '外国企业常驻代表机构登记证',
		28 => '统一社会信用代码',
		30 => '外国人永久居留身份证',
		31 => '博物馆登记证',
		32 => '博物馆登记证',
		33 => '企业集团登记证',
	],
	// 应用服务类型
	'appServType' => [
		28 => '政务生产类',
		29 => '生活消费类',
	],
];
