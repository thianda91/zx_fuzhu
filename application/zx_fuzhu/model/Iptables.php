<?php

namespace app\zx_fuzhu\model;

use think\Model;
use think\Db;

class Iptables extends Model
{

	/**
	 * 自动预分配ip，并确保未使用
	 *
	 * @param string $zxType        	
	 * @return number|string
	 */
	public static function generateIP($zxType = "互联网")
	{
		// $lastIp = Db::name ( "infotables" )->where ( "zxType", $zxType )->where ( "ip", "NOT NULL" )->order ( "create_time desc" )->limit ( 1 )->value ( "ip" );
		$lastIp = Db::table("phpweb_sysinfo")->where("label", "zx_apply-lastIP")->value("value");
		if ($lastIp) {
			// lastIp检测是否已使用，已使用则自增，直至返回未使用的ip，并记录
			do {
				$nextIpStr = self::ip_export($lastIp++);
				$valid = self::check($nextIpStr, "ip", $zxType);
			} while ($valid); // 有返回则表示冲突或不可用
			self::setLastIp($nextIpStr);
			return $nextIpStr;
		} else {
			return "暂无参考，请在系统设置中设置或手动分配";
		}
	}
	/**
	 * 设置最后分配的ip
	 *
	 * @param string $ipStr        	
	 * @return number
	 */
	public static function setLastIp($ipStr = "")
	{
		if (is_numeric($ipStr)) {
			$ip = $ipStr;
		} else {
			$ip = self::ip_parse($ipStr)[2];
		}
		if (!strlen($ip)) {
			return;
		}
		// 已存在则更新，不存在则新增
		if (Db::table("phpweb_sysinfo")->where("label", "zx_apply-lastIP")->find()) {
			$result = Db::table("phpweb_sysinfo")->where("label", "zx_apply-lastIP")->setField("value", $ip);
		} else {
			$data = [
				"label" => "zx_apply-lastIP",
				"value" => $ip
			];
			$result = Db::table("phpweb_sysinfo")->insert($data);
		}
		return $result;
	}
	/**
	 * 是否已分配
	 *
	 * @param unknown $zxType        	
	 * @param string $ip_str        	
	 * @param string $filed
	 *        	ip (defalut) or ipB
	 * @return array|\think\db\false|PDOStatement|string|\think\Model
	 */
	public static function check($ip_str = "", $filed = "ip", $zxType = "互联网")
	{
		if ($ip_str == "") {
			return;
		}
		list($ip, $mask, $ip_start, $ip_end) = self::ip_parse($ip_str);
		if (!self::ifCanUse($zxType, $ip_start)) {
			return "no";
		}
		$infotables = new Infotables();
		$data = $infotables->where([
			"zxType" => $zxType,
			$filed => $ip_start
			// $filed . "Mask" => $mask
		])->field("id,cName")->find();
		return $data;
	}
	/**
	 * 判断ip是否可用
	 *
	 * @param unknown $zxType        	
	 * @param unknown $long        	
	 * @param unknown $mask        	
	 * @return boolean
	 */
	public static function ifCanUse($zxType = '互联网', $long, $mask = -1)
	{
		if ($long < 0) {
			// 2^31-(-x) 设计负数如何转化并计算。
			$array = explode(",", "-255,0,-1");
		} else {
			$array = explode(",", "0,1,255");
		}
		return !in_array($long % 256, $array);
	}
	/**
	 * ip转换
	 * ip字符串10.10.10.10/32转成ip2long形式的数组:ip/mask/ip_start/ip_end
	 *
	 * @param unknown $ip_str        	
	 * @return boolean[]|number[]
	 */
	public static function ip_parse($ip_str = "")
	{
		if ($ip_str == "") {
			return [
				null,
				null,
				null,
				null
			];
		}
		$mask_len = 32;
		if (strpos($ip_str, "/") > 0) {
			list($ip_str, $mask_len) = explode("/", $ip_str);
		}
		$ip = ip2long($ip_str);
		$mask = 0xFFFFFFFF << (32 - $mask_len) & 0xFFFFFFFF;
		$ip_start = $ip & $mask;
		$ip_end = $ip | (~$mask) & 0xFFFFFFFF;
		return array(
			$ip,
			$mask,
			$ip_start,
			$ip_end
		);
	}
	/**
	 * ip、掩码转换成ip字符串10.10.10.10/32
	 *
	 * @param long $long        	
	 * @param long $subnet_mask        	
	 * @return string
	 */
	public static function ip_export($long, $subnet_mask = "")
	{
		if (!$long) {
			return null;
		}
		if (in_array($subnet_mask, [-1, 0xFFFFFFFF, ""])) {
			return long2ip($long);
		} else {
			$suffix = strlen(preg_replace("/0/", "", decbin($subnet_mask))); // 十进制转二进制 统计32-bits的二进制中1的个数
			return long2ip($long) . "/" . $suffix;
		}
	}
}
