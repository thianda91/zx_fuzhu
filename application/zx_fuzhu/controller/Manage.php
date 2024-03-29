<?php

namespace app\zx_fuzhu\controller;

use think\Db;
use app\zx_fuzhu\model\Vlantables;
use app\zx_fuzhu\model\Infotables;
use app\zx_fuzhu\model\Iptables;

class Manage extends Index
{

	protected $beforeActionList = [
		'checkAuth'
	];

	protected function checkAuth()
	{
		if (session("user.role") != "manage") {
			return $this->redirect("index/query");
		}
	}

	public function index()
	{
		return $this->redirect("todo");
	}

	/**
	 * 待办
	 *
	 * @param boolean $ignoreCheck
	 * @return void
	 */
	public function todo($ignoreCheck = false)
	{
		if (request()->isGet()) {
			$this->assign("list", $this->refleshTodoList());
			return $this->fetch();
		}
		if (request()->isPost()) {
			$req = input("get.req");
			// $infotables = new Infotables ();
			// 无论是什么请求，先获取数据库的 info
			$info = Infotables::get(input("post.id")); // 获取器数据
			$data = input("post.");
			if ($info) {
				// 设置不需要输出的字段
				$detail = $info->hidden([
					'aDate',
					'tags',
					'ipMask',
					'ipBMask',
					'create_time',
					'update_time',
					'delete_time'
				])->toArray();
			} else {
				return $this->success("这条待办找不到啦！");
			}
			$extra = $detail["extra"];
			if (is_array($extra)) {
				foreach ($extra as $k => $v) {
					$detail[$k] = $v;
				}
			}
			unset($detail["extra"]);
			$detail["ip"] = $info->ip; // 更正ip为 str 形式
			$detail["ipB"] = $info->ipB; // 更正ipB
			if ($req == "getDetail") {
				// 返回单条数据及同客户名的信息摘要
				$cName = mb_substr($info->cName, 2, 10, "utf-8");
				$field = "id,instanceId,cName,create_time,neFactory,vlan,aStation,ip,aPerson,aEmail,delete_time";
				$relative = collection(Infotables::withTrashed()->where("cName", "like", "%" . $cName . "%")->where("id", "<>", $info->id)->field($field)->select())->toArray();
				$result = [
					"related" => $relative,
					"detail" => $detail,
					"string" => $cName
				];
				return json($result);
			} else if ($req == "auto_pre") {
				// 若已有ip则不再分配ip
				if ($detail["ip"] == "") {
					$genIp = Iptables::generateIP($data["zxType"]);
				} else {
					$genIp = $detail["ip"];
				}
				// 若无A端基站则不分配vlan
				if ($data["aStation"] == "") {
					return [
						"genIp" => $genIp,
						"device" => null
					];
				} else {
					$device = config("aStation")[$data["aStation"]];
					$genVlan = Vlantables::generateVlan($device, null, 1);
					return [
						"genIp" => $genIp,
						"preVlan" => $genVlan["preVlan"],
						"usedVlans" => $genVlan["usedVlans"],
						"device" => $device
					];
				}
			} else if ($req == "distribution") {
				// 判断是否有变化
				$ifChanged = false;
				foreach ($detail as $k => $v) {
					if ($v != $data[$k]) {
						$ifChanged = true;
						break;
					}
				}
				$logKey = "分配IP";
				if ($ifChanged) {
					// 为以下操作约定 return 值：$msg 表示 提示信息（区分是ip问题还是vlan问题），$data 表示具体数据（如重复的客户名） 
					$this->checkInstanceID($info, $data);
					$_ip = $data["ip"];
					$data = $this->checkAndSetIp($info, $data);
					$data = $this->checkAndSetVlan($data, $ignoreCheck);
					$this->updateInfo($data);
					// vlan 不为空，且 status 为 0 或者 4，则 status +1
					if (isset($data["vlan"]) && ($info->status == 0 || $info->status == 4)) {
						Infotables::where("id", $data["id"])->setInc("status");
					}
					$logValue = [
						"status" => "success",
						"name" => session("user.name"),
						"email" => session("user.email"),
						"msg" => "操作成功",
						"Infoid" => $data["id"],
						"cName" => $data["cName"],
						"ip" => $_ip,
						"vlan" => $data["vlan"],
					];
					$this->log($logKey, $logValue);
					return $this->result($this->refleshTodoList(), 1, "操作成功。<br>是否发送邮件通知客户经理填写备案信息？");
				} else {
					$msg = "本次提交无修改";
					$logValue = [
						"status" => "failed",
						"name" => session("user.name"),
						"email" => session("user.email"),
						"msg" => $msg,
					];
					$this->log($logKey, $logValue);
					return $this->result(null, 2, $msg);
				}
			}
		}
	}

	public function generateVlan()
	{
		$aStationConf = config("aStation");
		if (array_key_exists(input("get.d"), $aStationConf)) {
			$device = $aStationConf[input("get.d")];
			return Vlantables::generateVlan($device, "预分配", 1);
		}
	}

	protected function updateInfo($data)
	{
		$extraHeader = config("extraInfo");
		foreach ($extraHeader as $k => $v) {
			if (isset($data[$v])) {
				$data["extra"][$v] = $data[$v];
				unset($data[$v]);
			}
		}
		// unset ( $data ["delete_time"] );
		$infotables = new Infotables();
		// 更新单条数据
		$result = $infotables->isUpdate(true)->save($data, [
			"id" => $data["id"]
		]);
		// 更新最后分配的IP
		isset($data["ip"]) && Iptables::setLastIp($data["ip"]);
		$infotables->find($data["id"]);
		return $result;
	}

	protected function refleshTodoList()
	{
		$where = ["neFactory" => 2, "vlan" => null];
		$whereor = ["status" => 0];
		$field = "id,cName,create_time,aPerson,ifOnu,instanceId,zxType,aStation";
		// 显示 ONU 业务 vlan 为空的，和状态为 0 的
		$data = Infotables::where($where)->whereOr($whereor)->field($field)->order("create_time desc")->select();
		return json_encode($data, 256);
	}

	/**
	 * 提醒客户经理发送 IDC 备案信息给厂家联系人
	 *
	 * @return void
	 */
	public function sendBeiAnNotice()
	{
		if (request()->isGet() || !input("?post.id")) {
			return $this->result("Wrong Req", 0);
		}
		// 忽略送来的参数，从数据库获取
		$order = "1,3,6,4,5,9,10,15,22,17,23,19";
		// instanceId,bandWidth,cName,neFactory,aStation,vlan,ip,mPerson,aPerson,mEmail,aEmail,ifOnu
		$field = $this->getHeader("zx_apply-new-rb", $order, true);
		$items = explode(",", $this->getHeader("zx_apply-new-rb", $order));
		array_pop($items); // 去掉最后 3 个参数 mEmail,aEmail,ifOnu
		array_pop($items);
		array_pop($items);
		$db = Infotables::field($field)->find(input("post.id"));
		$values = array_values($db->toArray());
		$address = [$db->mEmail]; // 发给客户经理
		$title = '[ip已分配]请填写IDC/ISP备案信息-' . $db->cName . "-" . $db->ip;
		// $title = config('idc.title_city') . 'IDC.ISP-' . $db->ip . "-" . $db->cName;
		$contact = config('idc_contact');
		$contact_str = implode(',', $contact);
		$body = "<p>请 <b>" . $db->mPerson . "</b> 及时填写 IDC/ISP 备案信息：</p>" . $this->todo_link_str('index/ipbeian');
		$body .= "<p>登陆时使用当前邮箱登陆： <b>" . $db->mEmail . "</b> 。别填错了哦，否则无法填写备案信息！</p>";
		$body .= "<hr><p>请 <b>" . $db->aPerson . "</b> 阅知。</p>";
		$body .= "<br><table style='border-collapse:collapse;border:none;'>";
		for ($i = 0; $i < count($items); $i++) {
			$body .= "<tr>";
			$body .= "<th style='width:200px;border:solid #666 1px;'>" . $items[$i] . "</th><td style='width:500px;border:solid #666 1px;'>" . $values[$i] . "</td>";
			$body .= "</tr>";
		}
		$body .= "</table>";
		$body .= "<p style='color:#000;background-color:#ccc;font-size:12px;'>请<span style='color:blue'>客户经理</span>尽快完成备案信息的填写，并确保其完整性、准确性。";
		$body .= "<br>在辅助平台提交后会自动发送给厂家联系人：<b>" . $contact_str . "</b>，并自动抄送给 IP 地址管理员。</p>";
		$body .= "如备案信息有误，在收到邮件后，登陆辅助平台进行更正，重新提交。";
		$address['CCa'] = $db->aEmail; // 抄送申请人
		if ($db->ifOnu) {
			$CCs = config("manageEmails");
			foreach ($CCs as $k => $v) {
				$address['CC' . $k] = $v . "@ln.chinamobile.com";
			}
		} else {
			$address['CCu'] = session("user.email"); // 抄送当前用户（IP地址管理员）
		}
		$result = $this->sendEmail($address, $title, $body);
		return $this->result($result, is_bool($result) ? 1 : 0);
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
				"order" => "24,1,4,5,6,9,10,19,22,23,26"
			];
			$this->assign([
				"aStationData" => implode(",", $aStation),
				"colHeaderData" => $this->getHeader($zxTitle["label"], $zxTitle["order"]),
				"colWidthsData" => $this->getColWidths($zxTitle["order"]),
				"data" => addslashes($this->getInfoData()->toJson()),
			]);
			return $this->fetch();
		}
		if (request()->isPost()) {
			// 获取台账
			// return $this->getInfoData();
			input("post.r") == "info" && $data = $this->getInfoData(input("post.zxType"))->toArray();
			input("post.r") == "detail" && $data = Infotables::get(input("post.id"))->toJson();
			input("post.r") == "search" && $data = $this->querySearch(input("post."));
			input("get.r") == "update" && $data = $this->queryUpdateInfo(input("post."));
			input("post.r") == "delete" && $data = $this->queryDelete(input("post."));
			return $data;
		}
		if (request()->isPut()) {
			// 相关操作
			input("post.r") == "script" && $data = Generator::generateScript(input("post.id/a")[0]);
			input("post.r") == "export_idc_isp" && $data = Generator::generateIDCinfoFiles(input("post.id"), 'httpResponse');
			input("post.r") == "export_zg" && $data = Generator::generateZgWorkflow(input("post.id"));
			input("post.r") == "export_jtip" && $data = Generator::generateJtIp(explode(",", input("post.id")));
			input("post.r") == "export_gxbip" && $data = Generator::generateGxbIp(explode(",", input("post.id")));
			input("post.r") == "export" && $data = $this->queryExport(input("post.zxType"));
			return $data;
		}
	}


	public function _get_device9312_info()
	{
		return base64_decode(config("device9312"));
	}

	/**
	 * get: 加载数据到 handsontable 并验证,
	 * post: 上传,后台处理入库
	 * 1. 默认 post 为新申请,移除 ip、vlan 信息再保存,
	 * 严格验证，不合规不许提交，标记status：0
	 * 2. 带 post 参数 type=import,视为旧信息导入,
	 * 生成 ip 表（全）和 vlan 表信息（不全）。直接入库，并标记 tags: 导入
	 * 3. 为了新增字段不修改数据库，将新增字段用 json 保存到一列。
	 * 在 csv_to_array 时，需要获取额外的字段
	 *
	 * @return void|string|mixed|string
	 */
	public function _ht_apply()
	{
		$zxInfoTitle = [
			"label" => "zx_apply-new-rb",
			"order" => "0,1,3,4,5,6,7,8,9,10,12,13,14,15,16,17,18,19,29,30,31,32,33,34,35,36,37"
		];
		if (request()->isPost()) {
			$postData = input("post.data");
			$type = input("post.type");
			$dataHeader = $this->getHeader($zxInfoTitle["label"], $zxInfoTitle["order"], true);
			// 获取数据库的列名
			$dataHeader = explode(",", $dataHeader);
			// 根据列名和数据转成php数组
			// $postData = substr ( $postData, 3 ); // 莫名奇妙的前三个字节是垃圾数据。3天才研究出来，只能这样解决！！！(目前已在前端解决)
			$data = $this->csv_to_array($dataHeader, $postData);
			// return dump($data);
			// 获取额外的字段
			$extraHeader = config("extraInfo");
			foreach ($data as $k => $v) {
				// 清除空元素
				$data[$k] = array_filter($v);
				$temp = [];
				foreach ($extraHeader as $kk => $vv) {
					if (isset($data[$k][$vv])) {
						$data[$k]["extra"][$vv] = $data[$k][$vv];
						unset($data[$k][$vv]);
					}
				}
				if (isset($v["aStation"]) && $v["aStation"] == "柴河局") {
					$data[$k]["neFactory"] = isset($data[$k]["neFactory"]) ? $data[$k]["neFactory"] : "";
					$data[$k]["aStation"] .= "-" . $data[$k]["neFactory"];
				}
				$data[$k]["aDate"] = isset($data[$k]["aDate"]) ? $data[$k]["aDate"] : "";
				if ($this->checkDateIsValid($data[$k]["aDate"])) {
					// 申请时间转存到 create_time
					$data[$k]["create_time"] = strtotime($data[$k]["aDate"]);
				}
			}
			// 若导入，ip/vlan信息要单独存储。
			$result = Infotables::createInfo($data, $type);
			// $result = $info->save($data[0]);
			return dump($result);
		}
		if (request()->isGet()) {
			if (input('?get.t')) {
				$aStation = array_keys(config("aStation"));
				$this->assign([
					"aStation" => implode(",", $aStation),
					'colHeaderData' => $this->getHeader($zxInfoTitle["label"], $zxInfoTitle["order"]),
					"colWidthsData" => $this->getColWidths($zxInfoTitle["order"])
				]);
				return $this->fetch("_ht_apply");
			}
		}
	}

	/**
	 * ip、vlan申请
	 *
	 * @return mixed|string
	 */
	public function import()
	{
		// post数据传给 _ht_apply()
		return $this->fetch();
	}

	/**
	 * 系统设置
	 *
	 * @return mixed|string
	 */
	public function settings()
	{
		if (request()->isGet()) {
			if (!strpos(request()->header("referer"), request()->action())) {
				session("settings_back_url", request()->header("referer"));
			}
			$lastIp = Iptables::ip_export(Db::table("phpweb_sysinfo")->where("label", "zx_apply-lastIP")->value("value"));
			$this->assign([
				"lastIp" => $lastIp
			]);
			return $this->fetch();
		} else if (request()->isPost()) {
			if (input("post.exec") == "ok_ip") {
				return Iptables::setLastIp(input("post.lastIpStr"));
			}
			if (input("post.exec") == "cal_ip") {
				$unusedIps = [];
				// todo
				return $this->result($unusedIps, 1);
			}
			if (input("post.exec") == "ok_vlan") {
				return Vlantables::importUsedVlan(input("post.device"), input("post.vlanImport"));
			}
		}
	}

	/**
	 * todo预分配时检查获取的数据与数据库中的ip/ipB是否重复
	 *
	 * @param unknown $info            
	 * @param unknown $data            
	 * @return void|NULL|unknown
	 */
	protected function checkAndSetIp($info, $data)
	{
		if ($info["ip"] != $data["ip"]) { // 获取的ip有变化，则检查是否冲突
			$ip = Iptables::check($data["ip"], "ip", $data["zxType"]);
			if ($ip)
				return $this->error("互联 ip 冲突，", null, $ip["cName"]);
		}
		// 设置ipMask
		$ip_array = Iptables::ip_parse($data["ip"]);
		$data["ip"] = $ip_array[2];
		$data["ipMask"] = $ip_array[1];
		if ($data["ipB"] == "") {
			// 设置 ipB为null
			$data["ipB"] = null;
			$data["ipBMask"] = null;
			return $data;
		}
		if ($info["ipB"] != $data["ipB"]) {
			$ipB = Iptables::check($data["ipB"], "ipB", $data["zxType"]);
			if ($ipB)
				return $this->error("业务 ip 冲突，", null, $ipB["cName"]);
		}
		// 设置ipBMask
		$ipB_array = Iptables::ip_parse($data["ipB"]);
		/* 若未提供ipBMask，默认强制设置ipBMask为-8 */
		$ipB_array[1] == -1 && $ipB_array = Iptables::ip_parse(Iptables::ip_export($ipB_array[0], -8));
		/* 修正ip为ip_start */
		$data["ipB"] = $ipB_array[2];
		$data["ipBMask"] = $ipB_array[1];
		return $data;
	}

	/**
	 * todo预分配时检查获取的vlan是否已分配，并记录/更新
	 *
	 * @param Infotables $data
	 * @param boolean $check
	 * @return Infotables $data
	 */
	protected function checkAndSetVlan($data, $ignoreCheck)
	{
		if ($data["vlan"] == "") {
			$data["vlan"] = null;
			Vlantables::createVlan($data["aStation"], $data["vlan"], $data["cName"], $data["id"]);
			return $data;
		}
		if ($ignoreCheck == false) {
			// default : $ignoreCheck = false
			$vlan = Vlantables::check($data["zxType"], $data["aStation"], $data["vlan"]);
			if ($vlan && $vlan["id"] != $data["id"]) {
				if ($vlan["code"]) {
					// (code = 2) vlan 存在但 aStation 不在预设范围内(为空)
					return $this->result($vlan["cName"], $vlan["code"], '无法关联 vlan');
				} else {
					// (code = 0) 找到 vlan 且 vlan 的 id 与自己的id不同
					return $this->result($vlan["cName"], $vlan["code"], "是否强制分配 vlan？");
				}
			} else {
				// vlan 不存在或 vlan id 与自己的 id 相同，则返回执行 infotables 的更新
			}
		} else {
			// 基于 Id 更新,若更新 0 条，则新增
		}
		Vlantables::createVlan($data["aStation"], $data["vlan"], $data["cName"], $data["id"]);
		return $data;
	}

	/**
	 * 校验日期格式是否正确
	 *
	 * @param string $date
	 *            日期
	 * @param string $formats
	 *            需要检验的格式数组
	 * @return boolean
	 */
	protected function checkDateIsValid($date, $formats = array("Y-m-d", "Y/m/d"))
	{
		$unixTime = strtotime($date);
		if (!$unixTime) { // strtotime转换不对，日期格式显然不对。
			return false;
		}
		// 校验日期的有效性，只要满足其中一个格式就OK
		foreach ($formats as $format) {
			if (date($format, $unixTime) == $date) {
				return true;
			}
		}
		return false;
	}
}
