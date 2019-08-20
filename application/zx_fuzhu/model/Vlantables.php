<?php

namespace app\zx_fuzhu\model;

use think\Model;
use think\Db;
use traits\model\SoftDelete;

class Vlantables extends Model
{
	use SoftDelete;
	protected $deleteTime = 'delete_time';
	protected $autoWriteTimestamp = false;
	/**
	 * 录入/更新/删除vlan
	 *
	 * @param string $aStation        	
	 * @param string $vlan        	
	 * @param string $description        	
	 * @param unknown $infoId        	
	 */
	public static function createVlan($aStation = "", $vlan = "", $description = "", $infoId = null)
	{
		if (is_null($infoId)) {
			return; // 仅内部调用，infoId不会为空。
		}
		$vlantables = new static();
		$aStationConf = config("aStation");
		if (array_key_exists($aStation, $aStationConf)) {
			// 根据a端匹配到9312名，则保存vlan
			$data = [
				"deviceName" => $aStationConf[$aStation],
				"vlan" => $vlan == 0 ? null : $vlan,
				"description" => $description,
				"infoId" => $infoId
			];
			// 根据infoId，如果已存在则更新，否则新增。
			$dbData = $vlantables->where(["infoId" => $infoId])->find();
			if ($dbData) {
				$dbData->isUpdate(true)->save($data);
			} else {
				$vlantables->isUpdate(false)->save($data);
			}
		}
	}
	/**
	 * 自动预分配vlan，返回预分配vlan
	 *
	 * @param string $device        	
	 * @param string $cName        	
	 * @param string $manual
	 *        	0 for write to db(default), 1 for pre-generate.
	 * @return number|string
	 */
	public static function generateVlan($device = "", $cName = "", $manual = false)
	{
		$usedVlans = self::where("deviceName", $device)->distinct(true)->column("vlan");
		for ($vlan = 2111; $vlan < 3071; $vlan++) {
			if (!in_array($vlan, $usedVlans)) {
				$preVlan = $vlan;
				break;
			}
		}
		if ($manual) {
			// 手动分类，推荐空闲vlan
			$result = [
				"preVlan" => $preVlan,
				"usedVlans" => $usedVlans
			];
		} else {
			Db::name("vlantables")->insert([
				"deviceName" => $device,
				"vlan" => $preVlan,
				"description" => $cName
			]);
			$result = $preVlan;
		}
		return $result;
	}
	/**
	 * 导入更新已使用vlan
	 *
	 * @param string $deviceName
	 * @param string $str
	 * @return void|string
	 */
	public static function importUsedVlan($deviceName = "", $str = "")
	{
		preg_match_all('/vlan batch ([\S ]+)/', $str, $str); // 匹配后结果输出到$str
		$str = implode(" ", $str[1]);
		// $str = str_replace ( 'vlan batch ', '', $str );
		// $str = preg_replace ( '/[\r\n]/', '', $str );
		$array = explode(" ", $str);
		$result = [];
		$count = count($array);
		for ($i = 0; $i < $count; $i++) {
			// 遍历数组，替换to，补充连续的vlan
			if ($array[$i] == "to") {
				array_splice($array, $i, 1);
				// 补充连续的vlan
				for ($j = $array[$i - 1] + 1; $j < $array[$i]; $j++) {
					$array[] = $j;
				}
			}
		}
		sort($array, SORT_NUMERIC); // 排序
		$count = 0;
		$validation = 0;
		foreach ($array as $v) {
			if ($v > 2000 && $v < 3071) {
				// 范围之外的vlan，无操作
				$count++;
				// 已有，则放弃
				$vlanInfo = self::where([
					"deviceName" => $deviceName,
					"vlan" => $v
				])->select();
				if (!$vlanInfo) {
					// 无信息，则insert
					$vlanInfo = self::create([
						"deviceName" => $deviceName,
						"vlan" => $v,
						"description" => "手动导入-" . date("Y-m-d h:i:s", time())
					]);
					$validation++;
				}
			}
		}
		return [
			"total" => count($array),
			"count" => $count,
			"validation" => $validation
		];
	}
	/**
	 * 检查vlan是否已分配
	 *
	 * @param string $zxType
	 * @param string $aStation
	 * @param number $vlan
	 * @return array
	 */
	public static function check($zxType = "", $aStation = "", $vlan = 0)
	{
		$data = Infotables::where([
			"zxType" => $zxType,
			"aStation" => $aStation,
			"vlan" => $vlan
		])->field("id,cName")->find();
		if (!$data) {
			$data = self::where("vlan", $vlan);
			$deviceConf = config("aStation");
			if (array_key_exists($aStation, $deviceConf)) {
				$data = $data->where("deviceName", $deviceConf[$aStation]);
				$data = $data->field("id,description as cName")->find();
				if ($data) { // 修改以区别，不同于要查询的数据
					// 此时情况为：vlan 在 Infotables 中查询无冲突，在 Vlantables 中有冲突
					$data = $data->toArray();
					// 随意设置个$data["id"]，非null
					$data["id"] .= "_vlan";
					$data["code"] = 0;
				}
			} else {
				// 此时情况为：vlan存在，aStation不在预设范围内（为空）
				$data = [
					"id" => "",
					"code" => 2,
					"cName" => "请填写正确的<span style='color: red;'>A端基站</span>！"
				];
			}
		} else {
			// 此时情况为：vlan 在 Infotables 中查询有冲突
			$data["code"] = 0;
		}
		return $data;
	}
}