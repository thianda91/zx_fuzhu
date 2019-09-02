<?php

namespace app\zx_fuzhu\controller;

use think\Db;
use think\Cache;
use app\zx_fuzhu\model\Infotables;
use app\zx_fuzhu\model\Vlantables;


class Index extends Common
{
	public function ch2arr($str)
	// 未使用
	{
		$length = mb_strlen($str, 'utf-8');
		$array = [];
		for ($i = 0; $i < $length; $i++)
			$array[] = mb_substr($str, $i, 1, 'utf-8');
		return $array;
	}

	/**
	 * 首页-登录
	 *
	 * @return mixed|string|void
	 */
	public function index()
	{
		if (request()->isGet()) {
			if (request()->pathinfo() != request()->module() . "/index/index.html") {
				return $this->redirect("index");
			}
			return $this->fetch();
		}
		if (request()->isPost()) {
			// post请求 验证登陆
			$user = Db::table("phpweb_check")->where('email', input("post.email"))->order("time desc")->find();
			$msg = "";
			if (!$user) {
				return $this->result(["code" => 0], 0, "该邮箱还未申请验证码");
			}
			if ($user["code"] != input("post.code")) {
				$msg = "验证码错误";
			} else {
				// 验证码正确，继续验证申请人姓名
				if ($user["name"] != input("post.name")) {
					$msg = "申请人姓名与申请时不一致<br />申请时为：<b>" . $user["name"] . "</b><br />申请时间：" . $user["time"];
				}
			}
			if ($msg) {
				$this->writeLog("登陆", "failed", $msg);
				return $this->error($msg, null, input("post."));
			} else if (time() - strtotime($user["time"]) > 3600 * 24 * 15) { // 15天内可直接登陆
				$msg = "登陆超时，请重新获取验证码。";
				$this->writeLog("登陆", "failed", $msg);
				unset($user["code"]);
				return $this->error($msg, "index", $user);
			} else {
				$e = explode("@", $user["email"]);
				// 附加role。
				if ($e[1] == "ln.chinamobile.com" && in_array($e[0], config("manageEmails"))) {
					$user["role"] = "manage";
				} else {
					$user["role"] = "index";
				}
				session("user", $user);
				$msg = "欢迎回来，" . $user["name"] . "。";
				$this->writeLog("登陆", "success", $msg);
				$hash = input("post.hash");
				$hashToURL = request()->pathinfo() == request()->module() . "/index/index.html" ? $hash != null ? $hash != "logout" ? "/" . request()->module() . "/" . $hash . ".html" : null : null : null;
				$to_url = session("to_url") ? session("to_url") : session("user.role") . "/query";
				// 跳转优先级：1. location.hash、2. session("to_url")、3. session("user.role") . "/query"
				$url =  $hashToURL ? $hashToURL : $to_url;
				session("to_url", null);
				return $this->success($msg, $url, $user);
			}
		}
	}

	protected function writeLog($k = '', $status = '', $msg = '')
	{
		$this->log($k, [
			"status" => $status,
			"name" => input("post.name"),
			"email" => input("post.email"),
			"msg" => strip_tags($msg)
		]);
	}

	/**
	 * 数据专线申请开通
	 */
	public function apply()
	{
		if (request()->isGet()) {
			return $this->fetch("index/apply");
		} else if (request()->isPost()) {
			$data = input("post.");
			$this->checkInstanceID(null, $data["instanceId"]); // 检查instanceId
			// 工程申请时不再填写idc/isp备案信息，下面的转换不再需要。
			// $extraHeader = config("extraInfo");
			// foreach ($extraHeader as $k => $v) {
			// 	$data["extra"][$v] = $data[$v];
			// 	unset($data[$v]);
			// }
			$result = Infotables::createInfo($data, "apply");
			// 发邮件通知 给客户经理，抄送 IP 地址管理员、当前申请人
			$subject = "[待办]ip申请-" . ($data["ifOnu"] ? "onu" : "9312") . "-" . $data["cName"] . $data["instanceId"];
			$body = $this->todo_link_str();
			$this->sendManageNotice($subject, $body, true);
			$v = [
				"username" => session("user.name"),
				"email" => session("user.email"),
				"cName" => $data["cName"],
				"instanceId" => $data["instanceId"]
			];
			$this->log("提交申请", $v);
			$redirectUrl = "../" . session("user.role") . "/query.html";
			return $this->result(null, $result, $redirectUrl);
		}
	}

	/**
	 * 修改提交
	 */
	public function re_apply()
	{
		if (request()->isGet()) {
			return $this->error("Nothing here. Do not try again!");
		}
		$data = input("post.");
		$oldData = Infotables::get(input("param.old"))->toArray();
		$updates = []; // 记录有更新的字段
		$change = '';
		/* 不验证实例标识重复与否 */
		$extraHeader = config("extraInfo");
		foreach ($extraHeader as $k => $v) {
			$data["extra"][$v] = $data[$v];
			unset($data[$v]);
			if ($data["extra"][$v] != $oldData["extra"][$v]) {
				$change .= $k . " => [" . $oldData["extra"][$v] . "] 改为 [" . $data["extra"][$v] . "]\r\n";
				$updates[$k] = "[" . $oldData["extra"][$v] . "]=>[" . $data["extra"][$v] . "]";
			}
		}
		// 不验证 instanceID
		$result = Infotables::createInfo($data, "apply");
		$this->queryDelete(["id" => [input("param.old")]]);
		// 发邮件通知
		$subject = "[待办]修改申请-" . $data["aPerson"] . "-" . $data["cName"];
		$oldInfo = "<pre style='color:#088'>A端基站： " . $oldData["aStation"] . "\r\nip: " . $oldData["ip"] . "\r\nvlan: " . $oldData["vlan"] . "</pre>";
		$body = "<p>原分配信息：</p>" . $oldInfo;
		$body .= "<p>修改内容：</p><pre style='color:#088bff'>";
		foreach ($data as $k => $v) {
			if ($k = 'extra') {
				continue;
			}
			// last change from v0.7.17
			if ($v != $oldData[$k]) {
				$change .= $k . ' => [' . $oldData[$k] . '] 改为 [' . $v . "]\r\n";
				$updates[$k] = '[' . $oldData[$k] . ']=>[' . $v . ']';
			}
		}
		$change == '' && $change = "无";
		$body .= $change;
		$body .= $this->todo_link_str();
		$this->sendManageNotice($subject, $body, true);
		$v = [
			"username" => session("user.name"),
			"email" => session("user.email"),
			"cName" => $data["cName"],
			"instanceId" => $data["instanceId"],
			"oldInfo" => $oldInfo,
			"updates" => $updates
		];
		$this->log("修改申请", $v);
		$redirectUrl = "../" . session("user.role") . "/query.html";
		return $this->result(null, $result, $redirectUrl);
	}

	/**
	 * IP 备案信息填写
	 *
	 * @return void
	 */
	public function ipbeian()
	{
		if (request()->isGet()) {
			return $this->fetch();
		}
		if (request()->isPost()) {
			if (!input("?post.id")) {
				return $this->result("没有传参", 0);;
			}
			// 提交时更新 Infotables，并发送idc备案邮件
			$data = input('post.');
			$extraHeader = config("extraInfo");
			foreach ($extraHeader as $k => $v) {
				$data["extra"][$v] = $data[$v];
				unset($data[$v]);
			}
			$infotables = new Infotables();
			$extraHeader = config("extraInfo");
			foreach ($extraHeader as $e => $exH) {
				if (isset($v[$exH])) { // extra 部分有更新，需要先获取完整的
					$v["extra"][$exH] = $v[$exH];
					unset($v[$exH]);
				}
			}
			$result = $infotables->isUpdate(true)->allowField(true)->save($v, ["id" => $data['cName']]);
			// 自动发IDC备案信息给厂家
			$this->sendBeiAnResultEmail(input("post.id"));

			$address = config('idc_contact');
			$subject =
				$attachment = Generator::generateIDCinfoFiles(input("post.id"));
			$result = $this->sendEmail($address, $subject, $body);
			// 抄送 IP 地址管理员，申请人、客户经理
			$subject = "[待办]ip申请-" . ($data["ifOnu"] ? "onu" : "9312") . "-" . $data["cName"] . $data["instanceId"];
			$body = $this->todo_link_str();
			$this->sendManageNotice($subject, $body, true);
		}
		if (request()->isPut()) {
			if (input("param.r") == "get_cnames") {
				$result = Infotables::where('mEmail', session('user.email'))->where('status', '<', 2)->column('id,cName');
				return $result;
			}
		}
	}

	/**
	 * 发送 IDC 备案信息给厂家联系人
	 */
	public function sendBeiAnResultEmail($id = '')
	{
		$order = "1,6,10,15,17,23,28,19";
		// instanceId,cName,ip,mPerson,mEmail,aEmail,extra,ifOnu
		$field = $this->getHeader("zx_apply-new-rb", $order, true);
		$items = explode(",", $this->getHeader("zx_apply-new-rb", $order));
		array_pop($items); // 去掉 ifOnu
		$db = Infotables::field($field)->find($id);
		// 转换 extra 信息
		$extraHeader = config("extraInfo");
		foreach ($extraHeader as $k => $v) {
			if (array_key_exists($v, $db["extra"])) {
				$db[$v] = $db["extra"][$v];
				array_push($items, $k);
			}
			unset($db["extra"]);
		}
		$values = array_values($db->toArray());
		$title = config('idc.title_city') . 'IDC.ISP-' . $db->ip . "-" . $db->cName;
		$contact = config('idc_contact');
		$contact_str = implode(',', $contact);
		$body = "--本邮件用来idc备案--";
		$body .= "<br><table style='border-collapse:collapse;border:none;'>";
		for ($i = 0; $i < count($items); $i++) {
			$body .= "<tr>";
			$body .= "<th style='width:200px;border:solid #666 1px;'>" . $items[$i] . "</th><td style='width:500px;border:solid #666 1px;'>" . $values[$i] . "</td>";
			$body .= "</tr>";
		}
		$body .= "</table>";
		$body .= "<p style='color:#000;background-color:#ccc;font-size:12px;'>备案信息由客户经理<span style='color:blue'>" . $db->mPerson . "(" . $db->mEmail;
		$body .= ")</span>负责填写，并确保其完整性、准确性。<br>本邮件由辅助平台自动发送给厂家联系人：" . $contact_str . "，并抄送给 IP 地址管理员。</p>";
		$body .= "如备案信息有误，请厂家联系人联系客户经理。由客户经理负责登陆辅助平台进行更正重新提交，或直接回复给厂家联系人。";
		$address = $contact;
		$address['CCm'] = $db->mEmail;
		$address['CCa'] = $db->aEmail;
		foreach (config("manageEmails") as $k => $v) {
			$address['CC' . $k] = $v . "@ln.chinamobile.com";
		}
		$result = $this->sendEmail($address, $title, $body);
		return $this->result($result, is_bool($result) ? 1 : 0);
	}

	/**
	 * 根据label、order 获取表格的 header!
	 * $v为false，获取option(default)；为ture，获取value
	 *
	 * @param String $label            
	 * @param String $order            
	 * @param boolean $v            
	 * @return string
	 */
	public function getHeader($label = "label", $order = "order", $v = false)
	{
		if ($label === "label" || $order === "order") {
			return "{msg:\"你要搞什么？\"}"; // 未输入参数label或order
		}
		$_headerData = Db::table("phpweb_sysinfo")->field("value,option")->where(["label" => $label])->order("id")->select();
		$orderArr = explode(",", $order);
		$headerArr = [];
		$sub = $v ? "value" : "option";
		foreach ($orderArr as $o) {
			$headerArr[] = $_headerData[$o][$sub];
		}
		return implode(",", $headerArr);
	}

	/**
	 * 根据order获取handsontable组件的colWidth
	 *
	 * @param unknown $order            
	 * @return string|void
	 */
	protected function getColWidths($order = null)
	{
		if (!is_null($order)) {
			$orderArr = explode(",", $order);
			$result = [];
			foreach ($orderArr as $v) {
				$result[] = config("colWidth")[$v];
			}
			return implode(",", $result);
		}
		return $this->result(null, 0, "~缺参数~");
	}

	/**
	 * 信息查询
	 */
	public function query()
	{
		if (request()->isGet()) {
			// 访问
			$aStation = array_keys(config("aStation"));
			$zxTitle = [
				"label" => "zx_apply-new-rb",
				"order" => "24,1,2,3,4,5,6,7,8,9,10,12,13,14,15,16,17,18,19,20,29,30,31,32,33,34,35,36,37,38,39,40,41,42,22,23,26"
			];
			$this->assign([
				"aStationData" => implode(",", $aStation),
				"colHeaderData" => $this->getHeader($zxTitle["label"], $zxTitle["order"]),
				"colWidthsData" => $this->getColWidths($zxTitle["order"]),
				"data" => $this->getInfoData()->toJson()
			]);
			return $this->fetch();
		}
		if (request()->isPost()) {
			// 获取台账
			input("post.r") == "info" && $data = $this->getInfoData(input("post.zxType"))->toArray();
			input("post.r") == "search" && $data = $this->querySearch(input("post."));
			input("post.r") == "brief" && $data = $this->querySearchBrief(input("post."));
			input("get.r") == "update" && $data = $this->queryUpdateInfo(input("post."));
			return $data;
		}
		if (request()->isPut()) {
			// 相关操作
			input("post.r") == "copy_one_more" && $data = $this->queryCopyOne(input("post."));
			input("post.r") == "export" && $data = $this->queryExport();
			return $data;
		}
	}

	/**
	 * 获取台账信息
	 *
	 * @param string $zxType            
	 * @param number $limit            
	 * @return \think\model\Collection|\think\Collection
	 */
	protected function getInfoData($zxType = "互联网", $limit = 100)
	{
		$where = "";
		if (session("user.role") != "manage") {
			$where = ["aEmail" => session("user.email")];
		}
		return collection(Infotables::where("zxType", $zxType)->where($where)->order("status,ip desc,create_time desc")->limit($limit)->select());
	}

	/**
	 * 全局查询
	 *
	 * @param unknown $data            
	 * @return array
	 */
	protected function querySearch($data)
	{
		$where = "";
		if (session("user.role") != "manage") {
			$where = ["aEmail" => session("user.email")];
		}
		return collection(Infotables::where("zxType", $data["zxType"])->where($where)->where($data["where"][0], "like", "%" . $data["where"][2] . "%")->order("ip desc")->select())->toArray();
	}

	/**
	 * 基本信息查询
	 *
	 * @param unknown $data            
	 * @return array
	 */
	private function querySearchBrief($data)
	{
		if (!isset($data["where"][2]) || $data["where"][2] == "") {
			return;
		}
		$field = "create_time,instanceId,cName,cAddress,vlan,ip,aPerson,aEmail";
		$result = collection(Infotables::where("zxType", $data["zxType"])->where($data["where"][0], "like", "%" . $data["where"][2] . "%")->field($field)->order("ip desc")->select())->toArray();
		$logValue  = $data;
		$logValue["resultLen"] = count($result);
		$logValue["user"] = session("user.name");
		$logValue["url"] = request()->url(true);
		$this->log("基本信息查询", $logValue);
		if (Cache::get('querySearchBriefTimes')) {
			Cache::inc('querySearchBriefTimes');
		} else {
			Cache::set('querySearchBriefTimes', 1, 600);
		}
		$querySearchBriefTimes = Cache::get('querySearchBriefTimes');
		if ($querySearchBriefTimes > 12) {
			$address = config("manageEmails");
			foreach ($address as $k => $v) {
				$address[$k] = $v . "@ln.chinamobile.com";
			}
			if ($querySearchBriefTimes > 14) {
				$msg = session("user.name") . "已被系统强制登出，原因：查询频繁，单位时间内累计" . $querySearchBriefTimes . "次";
				$this->log("强制登出", $msg);
				$this->noticeManage("[频繁查询]" . session("user.name") . "-10分钟内：" . $querySearchBriefTimes, null, $address);
				session(null);
				return $this->error("已退出登陆，请勿频繁查询！", "index");
			}
			return $querySearchBriefTimes;
		}
		return $result;
	}

	/**
	 * 从query.html更新台账
	 *
	 * @param unknown $updateData            
	 * @return number|\think\false
	 */
	protected function queryUpdateInfo($updateData)
	{
		$result = 0;
		$dbNew = [];
		foreach ($updateData as $k => $v) {
			$infotables = new Infotables();
			$line_and_id = explode("-", $k);
			$extraHeader = config("extraInfo");
			foreach ($extraHeader as $e => $exH) {
				if (isset($v[$exH])) { // extra 部分有更新，需要先获取完整的
					$v["extra"][$exH] = $v[$exH];
					unset($v[$exH]);
				}
			}
			$result += $infotables->isUpdate(true)->allowField(true)->save($v, ["id" => $line_and_id[1]]);
			// 反查询刚才修改后的数据库里的值，用于前后端数据的一致性
			$data = $infotables->where("id", $line_and_id[1])->find();
			foreach ($v as $kk => $vv) {
				$dbNew[$k][$kk] = $data->$kk;
			}
			$infotables = null;
		}
		$logValue = [
			"sqlResult" => $result,
			"name" => session("user.name"),
			"email" => session("user.email"),
			"updateData" => $updateData,
		];
		$this->log("台账更新", $logValue);
		return $this->result($dbNew, 1, $result);
	}

	/**
	 * 从query.html删除台账条目
	 *
	 * @param unknown $input            
	 */
	protected function queryDelete($input)
	{
		$result = Infotables::destroy($input["id"]);
		// 操作人记录到备注里
		Infotables::where('id', 2684)->exp('marks', 'concat(marks,\'' . session("user.name") . "已删;" . "')")->update();
		// 同步删除vlantables
		foreach ($input["id"] as $id) {
			Vlantables::destroy(["infoId" => $id]);
		}
		$logValue = [
			"sqlResult" => $result,
			"name" => session("user.name"),
			"email" => session("user.email"),
			"deleteData" => $input,
		];
		$this->log("台账更新", $logValue);
		return $result;
	}
	/**
	 * 额外再申请 IP 操作
	 *
	 * @return void
	 */
	protected function queryCopyOne($input)
	{
		if (request()->isGet()) {
			return $this->error("Nothing here. Do not try again!");
		}
		$id = $input["copyOne"];
		/* 不验证实例标识重复与否 */
		$data = Infotables::get($id)->getData();
		// json str 转 object
		$data['extra'] = json_decode($data['extra']);
		// 获取原始数据，去除 id，直接保存
		unset($data["id"]);
		$result = Infotables::createInfo($data, "apply");
		// 发邮件通知
		$subject = "[待办]额外申请-" . $data["aPerson"] . "-" . $data["cName"];
		$oldInfo = "<pre>A端基站： " . $data["aStation"] . "\r\nip: " . long2ip($data["ip"]) . "\r\nvlan: " . $data["vlan"] . "</pre>";
		$body = "<p>原分配信息：</p>" . $oldInfo;
		$body .= "<p>现申请额外的IP，需要您核实。";
		$body .= $this->todo_link_str();
		$this->sendManageNotice($subject, $body);
		$logValue = [
			"username" => session("user.name"),
			"email" => session("user.email"),
			"cName" => $data["cName"],
			"instanceId" => $data["instanceId"],
			"oldInfo" => $oldInfo
		];
		$this->log("额外申请", $logValue);
		$redirectUrl = "../" . session("user.role") . "/query.html";
		return $this->result(null, $result, $redirectUrl);
	}
	/**
	 * 导出全量台账-基于专线类型
	 *
	 * @param string $zxType            
	 * @return string[]|array[]
	 */
	protected function queryExport($zxType = "互联网")
	{
		if ($zxType == "互联网") {
			$colHeader = "申请时间,产品实例标识,带宽,网元厂家,A端基站,客户名称,单位详细地址,客户需求说明(选填),VLAN,IP,联系人姓名(客户侧),联系电话(客户侧),联系人邮箱(客户侧)*,负责人姓名(移动侧)*,负责人电话(移动侧)*,负责人邮箱(移动侧)*,备注,是否ONU带\n(默认为否),单位性质*,单位分类*,行业分类*,使用单位证件类型*,使用单位证件号码*,单位所在省*,单位所在市*,单位所在县*,应用服务类型*,单位属性,网络安全责任人,责任人身份证号,责任人电话,责任人邮箱";
			$colName = "create_time,instanceId,bandWidth,neFactory,aStation,cName,cAddress,cNeeds,vlan,ip,cPerson,cPhone,cEmail,mPerson,mPhone,mEmail,marks,ifOnu,extra.unitProperty,extra.unitCategory,extra.industryCategory,extra.credential,extra.credentialnum,extra.province,extra.city,extra.county,extra.appServType,extra.unitAttribute,extra.securityPerson,extra.securityPersonID,extra.securityPhone,extra.securityEmail";
			$field = "";
		} else if ($zxType == "营业厅") {
			$colHeader = "申请时间,产品实例标识,网元厂家,A端基站,客户名称,单位详细地址,VLAN,互联IP,业务IP,联系人姓名(客户侧),联系电话(客户侧)";
			$colName = "create_time,instanceId,neFactory,aStation,cName,cAddress,,vlan,ip,ipB,cPerson,cPhone";
			$field = $colName;
		} else if ($zxType == "卫生网") {
			$colHeader = "申请时间,产品实例标识,网元厂家,A端基站,客户名称,单位详细地址,VLAN,互联IP,业务IP,联系人姓名(客户侧),联系电话(客户侧)";
			$colName = "create_time,instanceId,neFactory,aStation,cName,cAddress,vlan,ip,ipB,cPerson,cPhone";
			$field = $colName;
		} else if ($zxType == "平安校园") {
			$colHeader = "申请时间,产品实例标识,客户名称,单位详细地址,VLAN,监控IP";
			$colName = "create_time,instanceId,cName,cAddress,vlan,ip";
			$field = $colName;
		}
		if (session("user.role") == "manage") {
			$data = collection(Infotables::field($field)->where("zxType", $zxType)->order("ip")->select())->toArray();
		} else {
			$data = collection(Infotables::field($field)->where("aPerson", session("user.name"))->order("ip")->select())->toArray();
		}
		$logValue = [
			"dataNum" => count($data),
			"zxType" => $zxType,
			"username" => session("user.name"),
			"email" => session("user.email")
		];
		$this->log("导出全量数据", $logValue);
		return [
			"data" => $data,
			"colHeader" => $colHeader,
			"colName" => $colName
		];
	}

	/**
	 * 检查 instanceId 是否重复
	 * 可输入$data数组或instanceId
	 *
	 * @param unknown $info            
	 * @param unknown $data            
	 */
	protected function checkInstanceID($info, $data)
	{
		if (null == $info) {
			$instanceId = $data;
		} else if ($info["id"] != $data["id"]) {
			$instanceId = $data["instanceId"];
		} else {
			return;
		}
		$info = Infotables::get([
			"instanceId" => $instanceId
		]);
		if ($info) {
			return $this->error("实例标识重复，请重试", null, "该实例标识对应客户名为：<br>" . $info["cName"] . "<br>申请人：" . $info["aPerson"]);
		}
	}

	/**
	 * 给管理员发送通知, 可选：抄送给当前用户
	 *
	 * @param string $subject
	 * @param string $body
	 * @param boolean $addCurrentUser
	 */
	protected function sendManageNotice($subject = '', $body = '', $addCurrentUser = false)
	{
		$address = config("manageEmails");
		foreach ($address as $k => $v) {
			$address[$k] = $v . "@ln.chinamobile.com";
		}
		$addCurrentUser && $address["CC1"] = session("user.email");
		$this->sendEmail($address, $subject, $body);
	}

	/**
	 * 生成发送代办邮件时的访问链接
	 *
	 * @param string $hash
	 */
	private function todo_link_str($hash = 'manage/todo')
	{
		return "<p>请登陆系统及时处理：</p><br> 内网： <a href='http://" . config('address_local') . "/" . config('moduleName') . "/index/index.html#" . $hash . "'>http://" . config('address_local') . "/" . config('moduleName') . "/index/index.html#" . $hash . "</a><br>外网： <a href='http://" . config('address_wide') . "/" . config('moduleName') . "/index/index.html#" . $hash . "'>http://" . config('address_wide') . "/" . config('moduleName') . "/index/index.html#" . $hash . "</a>";
	}
}
