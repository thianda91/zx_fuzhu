<?php

namespace app\zx_fuzhu\model;

use think\Model;
use traits\model\SoftDelete;

class Infotables extends Model
{
	use SoftDelete;
	protected $deleteTime = 'delete_time';
	protected $autoWriteTimestamp = true;
	protected $dateFormat = 'Y-m-d';
	protected $type = [
		// "aDate" => "date",
		"extra" => "array"
	];
	protected $insert = ['zxType']; 

	public function setIpAttr($value)
	{
		if (is_int($value)) {
			return $value;
		} else {
			return Iptables::ip_parse($value)[2];
		}
	}
	public function getIpAttr($value, $data)
	{
		return Iptables::ip_export($value, isset($data["ipMask"]) ? $data["ipMask"] : -1);
		return $value ? long2ip($value) : null;
	}
	public function setIpBAttr($value)
	{
		if (is_int($value) || "" == $value) {
			return $value;
		} else {
			return Iptables::ip_parse($value)[2];
		}
	}
	public function getIpBAttr($value, $data)
	{
		return Iptables::ip_export($value, isset($data["ipBMask"]) ? $data["ipBMask"] : -4);
	}
	public function setNeFactoryAttr($value)
	{
		// if (preg_match_all ( "/[0-9]/", $tt ) == strlen ( $tt )) {
		if (is_numeric($value) && floor($value) == $value) {
			// 是数字，且是整数
			return $value;
		} else {
			$ne = array_search($value, [
				"华为",
				"中兴",
				"ONU"
			]);
			return is_int($ne) ? $ne : null;
		}
	}
	public function getNeFactoryAttr($value)
	{
		$zx_nefactory = [
			0 => "华为",
			1 => "中兴",
			2 => "ONU",
			3 => null
		];
		return is_null($value) ? null : $zx_nefactory[$value];
	}
	public function setZxTypeAttr($value)
	{
		if ($value == '' or $value == null) { 
			return "互联网";
		}else{
			return $value;
		}
	}


	// public function getStatusAttr($value) {
	// $statusArr = [
	// 0 => "申请",
	// 1 => "预分配",
	// 2 => "已IDC待流程",
	// 3 => "已流程已备ip待IDC",
	// 4 => "待做数据",
	// 5 => "已做数据",
	// 9 => "历史导入"
	// ];
	// return array_key_exists ( $value, $statusArr ) ? $statusArr [$value] : "";
	// }
	/**
	 * 新增Info，type可选导入、申请
	 *
	 * @param string $data        	
	 * @param string $type        	
	 * @return number[]|\think\false[]
	 */
	public static function createInfo($data = "", $type = "")
	{
		$result = [];
		if ($type == "import") {
			foreach ($data as $k => $d) {
				$infotables = new static();
				$data[$k] = array_merge([
					"tags" => "导入",
					"status" => 9
				], $data[$k]);
				// 清除空元素
				$data[$k] = array_filter($data[$k]);
				$infotables->isUpdate(false)->allowField(true)->save($data[$k], []);
				$result[$k] = $infotables->id;
				if (isset($data[$k]["vlan"]) && isset($data[$k]["aStation"])) {
					// 如果vlan不为空，则记录vlan表
					Vlantables::createVlan($data[$k]["aStation"], $data[$k]["vlan"], $data[$k]["cName"], $result[$k]);
				}
			}
		}
		if ($type == "apply") {
			$infotables = new static();
			$data["tags"] = "申请";
			$data["status"] = 0;
			$result = $infotables->isUpdate(false)->allowField(true)->save($data, []);
		}
		return $result;
	}
}
