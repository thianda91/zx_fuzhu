<?php

namespace app\zx_fuzhu\controller;

use app\zx_fuzhu\model\Infotables;
use app\zx_fuzhu\model\Iptables;
use Overtrue\Pinyin\Pinyin;


class Generator extends Common
{
/* 各系统的【单位属性】默认固定为“企业” */
/* 应该根据字段 unitProperty 来填写 */
/* 应删除字段 unitAttribute，或在 apply.html 中隐藏该字段，已精简页面显示 */
	public static function generateIDCinfoFiles($ids = null, $type = 2)
	{
		if (!is_array($ids)) {
			$ids = explode(",", $ids);
		}
		$row = 3;
		$cellValues = [];
		$sheets = [
			2 => ["表4", "表5", "表8", "表10", "表11"]
		];
		$colNames = "A B C D E F G H I J K L M N O P Q R S T U V W X Y Z";
		$theCompany = base64_decode("5Lit5Zu956e75Yqo6YCa5L+h6ZuG5Zui6L695a6B5pyJ6ZmQ5YWs5Y+4");
		$engineRoom = base64_decode("6L695a6B56e75Yqo6ZOB5bKt5biCSURD5LiT57q/5py65oi/");
		$structure = [
			"表4" => [
				"title" => "IP地址段信息",
				"header" => "起始IP地址,终止IP地址,IP地址使用方式,使用人,使用人的证件类型,对应证件号码,来源单位,分配单位,分配使用时间,机房名称",
				"default" => [
					"C" => "专线",
					"G" => $theCompany,
					"H" => $theCompany,
					"J" => $engineRoom,
				],
			],
			"表5" => [
				"title" => "用户信息",
				"header" => "用户属性,单位名称,单位属性,证件类型,证件号码,单位地址,邮政编码,服务开通时间,注册时间,网络安全责任人(名称),网络安全责任人(证件号码)",
				"default" => [
					"A" => "其他用户",
					//"G" => 112000,
				]
			],
			"表8" => [
				"title" => "占用机房信息(其他用户)",
				"header" => "机房名称,资源分配时间,网络带宽(Mbps),用户信息(单位名称)",
				"default" => [
					"A" => $engineRoom,
				]
			],
			"表10" => [
				"title" => "其他用户IP地址段信息",
				"header" => "机房名称,资源分配时间,网络带宽,用户信息(单位名称),起始IP地址,终止IP地址",
				"default" => [
					"A" => $engineRoom,
				]
			],
			"表11" => [
				"title" => "网络安全责任人",
				"header" => "姓名,证件类型,证件号码,固定电话,移动电话,email地址",
				"default" => [
					"B" => "身份证",
					"D" => "无",
				]
			],
		];
		/* start creating cellValues */
		foreach ($sheets[$type] as $sheet) {
			/* build headers */
			$header = [];
			$keys = explode(" ", $colNames);
			$values = explode(",", $structure[$sheet]["header"]);
			foreach ($values as $k => $v) {
				$header[$keys[$k] . 2] = $v;
			}
			$cellValues[$sheet] = array_merge(["A1" => $structure[$sheet]["title"]], $header);
			/* add default values */
			foreach ($structure[$sheet]["default"] as $k => $v) {
				$cellValues[$sheet][$k . $row] = $v;
			}
		}
		function getArrayValue($arr = [], $k = "0")
		{
			return array_key_exists($k, $arr) ? $arr[$k] : null;
		}
		foreach ($ids as $id) {
			/* add dynamic values */
			$data = Infotables::get($id)->toArray();
			$extra = getArrayValue($data, "extra");
			$nullVal = null;
			$credential = $extra ? getArrayValue($extra, "credential") ? config("credential")[getArrayValue($extra, "credential")] : $nullVal : $nullVal;
			$unitProperty = $extra ? getArrayValue($extra, "unitProperty") ? config("unitProperty")[getArrayValue($extra, "unitProperty")] : $nullVal : $nullVal;
			if (in_array("表4", $sheets[$type])) {
				$cellValues["表4"]["A" . $row] = $data["ip"]; // ip
				$cellValues["表4"]["B" . $row] = $data["ip"]; // ip
				$cellValues["表4"]["D" . $row] = $data["cName"]; // 客户名
				$cellValues["表4"]["E" . $row] = $credential; // 证件类型
				$cellValues["表4"]["F" . $row] = getArrayValue($extra, "credentialnum"); // 证件号码
				$cellValues["表4"]["I" . $row] = $data["create_time"]; // 分配时间
			}
			if (in_array("表5", $sheets[$type])) {
				$cellValues["表5"]["B" . $row] = $data["cName"]; // 客户名
				$cellValues["表5"]["C" . $row] = $unitProperty; // 单位属性
				$cellValues["表5"]["D" . $row] = $credential; // 证件类型
				$cellValues["表5"]["E" . $row] = getArrayValue($extra, "credentialnum"); // 证件号码
				$cellValues["表5"]["F" . $row] = $data["cAddress"]; // 客户地址
				$cellValues["表5"]["G" . $row] = $data["extra"]["zipCode"]; // 邮政编码
				$cellValues["表5"]["H" . $row] = $data["create_time"]; // 服务开通时间
				$cellValues["表5"]["I" . $row] = $data["create_time"]; // 注册时间
				$cellValues["表5"]["J" . $row] = getArrayValue($extra, "securityPerson"); // 网络安全责任人
				$cellValues["表5"]["K" . $row] = getArrayValue($extra, "securityPersonID"); // 责任人身份证号
			}
			if (in_array("表8", $sheets[$type])) {
				$cellValues["表8"]["B" . $row] = $data["create_time"]; // 分配时间
				$cellValues["表8"]["C" . $row] = $data["bandWidth"] + 0; // 带宽
				$cellValues["表8"]["D" . $row] = $data["cName"]; // 客户名
			}
			if (in_array("表10", $sheets[$type])) {
				$cellValues["表10"]["B" . $row] = $data["create_time"]; // 分配时间
				$cellValues["表10"]["C" . $row] = $data["bandWidth"] + 0; // 带宽
				$cellValues["表10"]["D" . $row] = $data["cName"]; // 客户名
				$cellValues["表10"]["E" . $row] = $data["ip"]; // ip
				$cellValues["表10"]["F" . $row] = $data["ip"]; // ip
			}
			if (in_array("表11", $sheets[$type])) {
				$cellValues["表11"]["A" . $row] = getArrayValue($extra, "securityPerson");
				$cellValues["表11"]["C" . $row] = getArrayValue($extra, "securityPersonID"); // 责任人身份证号
				$cellValues["表11"]["E" . $row] = $data["cPhone"] + 0; // 客户电话
				$cellValues["表11"]["F" . $row] = $data["cEmail"]; // 客户邮箱
				$row++;
			}
		}
		//return $cellValues;
		$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$index = 1;
        // 编辑worksheet
		foreach ($cellValues as $ws => $_sheet) {
			$worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $ws);
			$spreadsheet->addSheet($worksheet, $index++);
			foreach ($_sheet as $k => $v) {
				$worksheet->setCellValue($k, $v);
				// $worksheet->getCell ( $k )->setValue ( $v );
			}
		}
		$spreadsheet->removeSheetByIndex(0);
		$spreadsheet->getProperties()->setCreator("X.Da");
		$spreadsheet->getProperties()->setLastModifiedBy('X.Da');
		$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
		// header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); // 告诉浏览器数据excel07文件
		// header('Content-Disposition: attachment;filename="' . $fileName . '"'); // 告诉浏览器将输出文件的名称
		// header('Cache-Control: max-age=0'); // 禁止缓存
		// $writer->save("php://output");
		$dir = "../runtime/temp/";
		$fileName = '铁岭IDC.ISP-' . $data["ip"] . "-" . $data["cName"] . '.xlsx';
		// $tmp = time(). '.xlsx';
		// $writer->save($dir . $tmp);
		$writer->save($dir . $fileName);
		$spreadsheet->disconnectWorksheets();
		unset($spreadsheet);
		unset($writer);
		// if (file_exists($dir . $fileName) || !file_exists($dir . $tmp)) {
		// 	return -1;	// idc.isp附件生成失败
		// } else {
		// 	return rename($dir . $fileName, $dir . $tmp) ? $dir . $fileName : 0;
		// }
		return $dir . $fileName;
	}
	public static function generateZgWorkflow($ids = null)
	{
		if (is_string($ids)) {
			$ids = explode(",", $ids);
		}
		$row = 5;
		$cellValues = [];
		$principal = base64_decode("5Y2c546J");
		$tel = base64_decode("MTg4NDEwNTA4MTU=") + 0;
		$dept = base64_decode("5a6i5oi35ZON5bqU5Lit5b+D");
		$email = base64_decode("YnV5dS50bEBsbi5jaGluYW1vYmlsZS5jb20");
		$engineRoom = base64_decode("6ZOB5bKt5p+05rKz6KGX5bGAMeWPt+alvDLlsYIyMTDnu7zlkIjmnLrmiL8");
		$default = [
			"B" => "铁岭",
			"D" => "占用",
			"E" => "223.100.96.0/20",
			"F" => "集客专线",
			"G" => "互联网专线",
			"H" => "LNTIL-MA-CMNET-BAS02-YZME60X",
			"L" => $principal,
			"M" => $tel,
			"N" => "Auto import! --X.Da",
			"O" => "铁岭",
			"P" => $principal,
			"R" => "其他",
			//"S" => "企业",
			"T" => "辽宁",
			"W" => $dept,
			"X" => $email,
			"AD" => "静态",
			"AH" => "已启用",
			"AI" => $engineRoom,
		];
		foreach ($ids as $id) {
			foreach ($default as $k => $v) {
				$cellValues[$k . $row] = $v;
			}
			$data = Infotables::get($id)->toArray();
			$cellValues["C" . $row] = $data["ip"] . "/32"; // ip
			$cellValues["K" . $row] = $data["create_time"]; // 分配时间
			$cellValues["Q" . $row] = $data["cName"]; // 客户名
			$cellValues["S" . $row] = $data["extra"]["unitAttribute"]; // 企业属性
            /* 以下为选填 */
			$cellValues["I" . $row] = $data["cPerson"]; // 客户联系人
			$cellValues["J" . $row] = $data["cPhone"] + 0; // 客户电话
			$cellValues["U" . $row] = $data["cAddress"]; // 客户地址
			$cellValues["V" . $row] = $data["cEmail"]; // 客户邮箱
			$cellValues["AG" . $row] = $data["instanceId"] + 0; // 政企专线计费代号
			$row++;
		}
		$pFilename = './static/sampleData/zg_import.xls';
		return self::exportExcelFile($pFilename, 0, $cellValues, 'Xls', '资管流程-' . $data["cName"] . '.xls');
	}

	public static function generateJtIp($ids = null)
	{
		$row = 4;
		$cellValues = [];
		$dept = base64_decode("6ZOB5bKt56e75Yqo5a6i5oi35ZON5bqU5Lit5b+D");
		$principal = base64_decode("5Y2c546J");
		$tel = base64_decode("MTg4NDEwNTA4MTU=") + 0;
		$email = base64_decode("YnV5dS50bEBsbi5jaGluYW1vYmlsZS5jb20");
		$default = [
			"D" => "其他",
			//"F" => "企业",
			"G" => "辽宁",
			"H" => "铁岭",
			"Q" => "静态",
			"T" => "互联网专线",
			"U" => "占用",
			"V" => "已启用",
			"AA" => $dept,
			"AB" => $principal,
			"AC" => $tel,
			"AD" => $email
		];
		foreach ($ids as $id) {
			foreach ($default as $k => $v) {
				$cellValues[$k . $row] = $v;
			}
			$data = Infotables::get($id)->toArray();
			$cellValues["B" . $row] = $data["ip"] . "/32"; // ip
			$cellValues["C" . $row] = $data["cName"]; // 客户名
			$cellValues["F" . $row] = $data["extra"]["unitAttribute"]; // 企业属性
			$cellValues["L" . $row] = $data["cAddress"]; // 客户地址
			$cellValues["M" . $row] = $data["cPerson"]; // 客户联系人
			$cellValues["N" . $row] = $data["cPhone"] + 0; // 客户电话
			$cellValues["O" . $row] = $data["cEmail"]; // 客户邮箱
			$cellValues["R" . $row] = $data["create_time"]; // 分配时间
			$row++;
		}
		$pFilename = './static/sampleData/ip_jt.xlsx';
		return self::exportExcelFile($pFilename, 0, $cellValues, 'Xlsx', '集团IP备案-' . $data["cName"] . '.xlsx');
	}

	public static function generateGxbIp($ids = null)
	{
		$row = 2;
		$cellValues = [];
		$default = [
			"D" => "其他",
			//"F" => "企业",
			"Q" => "静态"
		];
		foreach ($ids as $id) {
			foreach ($default as $k => $v) {
				$cellValues[$k . $row] = $v;
			}
			$data = Infotables::get($id)->toArray();
			$cellValues["A" . $row] = $data["ip"]; // ip
			$cellValues["B" . $row] = $data["ip"]; // ip
			$cellValues["C" . $row] = $data["cName"]; // 客户名
			$cellValues["F" . $row] = $data["extra"]["unitAttribute"]; // 企业属性
			$cellValues["L" . $row] = $data["cAddress"]; // 客户地址
			$cellValues["M" . $row] = $data["cPerson"]; // 客户联系人
			$cellValues["N" . $row] = $data["cPhone"] + 0; // 客户电话
			$cellValues["O" . $row] = $data["cEmail"]; // 客户邮箱
			$cellValues["R" . $row] = $data["create_time"]; // 分配时间
			$cellValues["F" . $row] = "企业";
			$cellValues["G" . $row] = isset($data["extra"]["province"]) ? $data["extra"]["province"] : "";
			$cellValues["H" . $row] = isset($data["extra"]["city"]) ? $data["extra"]["city"] : "";
			$row++;
		}
		$pFilename = './static/sampleData/ip_gxb.xls';
		return self::exportExcelFile($pFilename, 4, $cellValues, 'Xls', '工信部IP备案-' . $data["cName"] . '.xls');
	}

	/**
	 * 导出到excel
	 *
	 * @param unknown $pFilename 模板文件地址
	 * @param unknown $workSheetIndex 工作表索引
	 * @param unknown $cellValues  数据
	 * @param unknown $writerType 输出类型 Xls or Xlsx
	 * @param unknown $fileName 输出文件名
	 * @param String $writerMethod 写入$cellValues使用的phpsSpreadsheet 方法名
	 * @param boolval $unusePHP 是否用 PHP 导出 excel 文件
	 */
	private static function exportExcelFile($pFilename, $workSheetIndex, $cellValues, $writerType, $fileName, $writerMethod = "setCellValue")
	{
		if (!input("post.php/b")) {
			return [
				"fileName" => $fileName,
				"cellValues" => $cellValues
			];
		}
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($pFilename);
		$worksheet = $spreadsheet->getSheet($workSheetIndex);
        // 编辑worksheet
		foreach ($cellValues as $d => $v) {
			$worksheet->$writerMethod($d, $v);
            // $worksheet->getCell ( $d )->setValue ( $v );
		}
        // 定义spreadsheet参数并输出到浏览器
		$spreadsheet->getProperties()->setCreator("X.Da");
		$spreadsheet->getProperties()->setLastModifiedBy('X.Da');
		$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, $writerType);
		if ($writerType == "Xls") {
			header('Content-Type: application/vnd.ms-excel'); // 告诉浏览器将要输出excel03文件
		} else {
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); // 告诉浏览器数据excel07文件
		}
		header('Content-Disposition: attachment;filename="' . $fileName . '"'); // 告诉浏览器将输出文件的名称
		header('Cache-Control: max-age=0'); // 禁止缓存
		$writer->save("php://output");
		$spreadsheet->disconnectWorksheets();
		unset($spreadsheet);
		unset($writer);
	}
	public static function generateScript($id = null)
	{
		$data = Infotables::get($id);
		if ($data["zxType"] == "互联网") {
			if ($data['neFactory'] == 'ONU') {
				return self::error("ONU业务暂不支持数据制作脚本");
			}
			return self::generateScriptNet($data);
		}
		if ($data["zxType"] == "卫生网") {
			return self::generateScriptWsw($data);
		}
	}

	/**
	 * 数据制作脚本生成-互联网
	 *
	 * @param unknown $data            
	 * @return string|NULL[]|string[]
	 */
	private static function generateScriptNet($data)
	{
		$data["domain"] = 'tlyd-rb'; // 1. domain
		$aStation = config("aStation");
		$data["sw93"] = $aStation[$data["aStation"]]; // 2. 9312名
		$pinyin = new Pinyin();
		$desc = substr($data["sw93"], 0, stripos($data["sw93"], "-") + 1);
		$desc = str_replace("CHJ", "TL", $desc);
		$_desc = $pinyin->convert(preg_replace("/[^\x{4e00}-\x{9fa5}A-Za-z0-9-]/u", "", $data["cName"]));
		foreach ($_desc as $v) {
			$desc .= ucfirst($v);
		}
		$data["desc"] = $desc . "_NET"; // 3. 描述
		$device9312 = json_decode(base64_decode(config("device9312")), true)[$data["sw93"]];
		function bas($bas, $device9312, $data)
		{
			$trunk = $device9312['bas' . $bas . '_down_port'];
			$rbp = $device9312['rbp_name'];
			$_bas_name = [
				"01" => "01-CHJ",
				"02" => "02-YZL"
			];
			if (strlen($data["domain"]) > 4) { // tlyd-rb
				$rbp = "\n remote-backup-profile " . $rbp;
			} else {
				$rbp = null;
			}
			$script = "interface " . $trunk . "." . $data["vlan"];
			$script .= "\ndis th\n ";
			$script .= "\n description [LNTIL-MA-CMNET-BAS" . $_bas_name[$bas] . "ME60X]" . $trunk . "." . $data["vlan"] . "-[" . $data["desc"] . "]";
			$script .= "\n user-vlan " . $data["vlan"] . $rbp;
			$script .= "\n bas\n #\n access-type layer2-subscriber default-domain authentication " . $data["domain"];
			$script .= "\n authentication-method bind\n";
			$script .= "static-user " . $data["ip"] . " " . $data["ip"] . " gateway " . substr($data["ip"], 0, strripos($data["ip"], ".") + 1) . "1 " . "interface " . $trunk . "." . $data["vlan"] . " vlan " . $data["vlan"] . " domain-name " . $data["domain"] . " detect\r\n";
			return $script;
		}
		function the93($device9312, $data)
		{
			if ($data["neFactory"]) {
				$data["neFactory"] === '华为' && $down = "port_hw";
				$data["neFactory"] === '中兴' && $down = "port_zte";
			} else {
				return "网元厂家未定义";
			}
			$script = "vlan " . $data["vlan"] . "\ndis th\n \n";
			$script .= "description to-[" . $data["desc"] . "]\nq";
			$script .= "\ninterface " . $device9312["up_port_yz"]; // 上行银州 bas02
			$script .= "\nport trunk allow-pass vlan " . $data["vlan"];
			if (strlen($data["domain"]) > 4) { // 上行柴河 bas01
				$script .= "\ninterface " . $device9312["up_port_ch"] . "\nport trunk allow-pass vlan " . $data["vlan"];
			}
			$script .= "\ninterface " . $device9312[$down]; // 下行
			$script .= "\nport trunk allow-pass vlan " . $data["vlan"];
			$script .= "\nq\r\n";
			return $script;
		}
		$result = [
			"bas01" => bas("01", $device9312, $data),
			"bas02" => bas("02", $device9312, $data),
			"the93" => [
				$data["sw93"],
				the93($device9312, $data)
			]
		];
		return $result;
	}

	/**
	 * 数据制作脚本生成-卫生网
	 *
	 * @param unknown $data            
	 * @return string|NULL[]|string[]
	 */
	private static function generateScriptWsw($data)
	{
		$aStation = config("aStation");
		$data["sw93"] = $aStation[$data["aStation"]]; // 2. 9312名
		$pinyin = new Pinyin();
		$desc = substr($data["sw93"], 0, stripos($data["sw93"], "-") + 1); // 3. 描述
		$desc = str_replace("CHJ", "TL", $desc);
		$_desc = $pinyin->convert(preg_replace("/[^\x{4e00}-\x{9fa5}A-Za-z0-9-]/u", "", $data["cName"]));
		foreach ($_desc as $v) {
			$desc .= ucfirst($v);
		}
		$device9312 = json_decode(config("device9312"), true)[$data["sw93"]];
		$trunk = $device9312['bas02_down_port'];
		$ip = Iptables::ip_parse($data["ip"]);
		$ipB = Iptables::ip_parse($data["ipB"]);
		$script = "interface " . $trunk . "." . $data["vlan"] . "\ndis th\n";
		$script .= "\n vlan-type dot1q " . $data["vlan"];
		$script .= "\n description [LNTIL-MA-CMNET-BAS02-YZLME60X]" . $trunk . "." . $data["vlan"] . "-[" . $desc . "]";
		$script .= "\n ip binding vpn-instance tlwsw";
		$script .= "\n ip address " . long2ip($ipB[2] + 1) . " " . long2ip($ipB[1]);
		$script .= "\n traffic-policy remarkdscp inbound";
		$script .= "\n statistic enable";
		$script .= "\nip route-static vpn-instance tlwsw " . long2ip($ip[2]) . " " . long2ip($ip[1]) . " " . long2ip($ipB[2] + 2) . "\r\n";
        /* the9312 */
		if ($data["neFactory"]) {
			$data["neFactory"] === '华为' && $down = "port_hw";
			$data["neFactory"] === '中兴' && $down = "port_zte";
		} else {
			return [
				"the93" => [
					"网元厂家未定义",
					""
				]
			];
		}
		$the9312 = "vlan " . $data["vlan"] . "\ndis th\n\n";
		$the9312 .= "description to-[" . $desc . "]\nq";
		$the9312 .= "\ninterface " . $device9312["up_port_yz"] . "\nport trunk allow-pass vlan " . $data["vlan"];
		$the9312 .= "\ninterface " . $device9312[$down] . "\nport trunk allow-pass vlan " . $data["vlan"] . "\nq\r\n";
		return [
			"bas02" => $script,
			"the93" => [
				$data["sw93"],
				$the9312
			]
		];
	}
	/**
	 * 修复历史数据新增 6 个 extra 字段为空的问题
	 *
	 * @return void
	 */
	public function fix6newExtraFields()
	{
		$data = Infotables::all();
		foreach ($data as $k => $v) {
			$unitProperty = config("unitProperty");
			$extra = $v->extra;
			if (is_null($extra)) {
				continue;
			}
			if (!isset($extra['unitAttribute'])) $extra['unitAttribute'] = is_null($extra['unitProperty']) ? "" : $unitProperty[$extra['unitProperty']];
			if (!isset($extra['securityPerson'])) $extra['securityPerson'] = is_null($v->cPerson) ? "" : $v->cPerson;
			if (!isset($extra['securityPersonID'])) $extra['securityPersonID'] = '0';
			if (!isset($extra['securityPhone'])) $extra['securityPhone'] = is_null($v->cPhone) ? "" : $v->cPhone;
			if (!isset($extra['securityEmail'])) $extra['securityEmail'] = is_null($v->cEmail) ? "" : $v->cEmail;
			if (!isset($extra['zipCode'])) $extra['zipCode'] = "112000";
			$data[$k]->extra = $extra;
			$data[$k]->save();
		}
		return dump("ok");
	}

	private function cacheSettings()
	{
		$client = new \Redis();
		$client->connect('127.0.0.1', 6379);
		$pool = new \Cache\Adapter\Redis\RedisCachePool($client);
		$simpleCache = new \Cache\Bridge\SimpleCache\SimpleCacheBridge($pool);
		\PhpOffice\PhpSpreadsheet\Settings::setCache($simpleCache);
	}
}