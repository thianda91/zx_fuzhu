<?php

namespace app\zx_fuzhu\controller;

use app\common\controller\Common as CCommon;
use think\Session;
use app\zx_fuzhu\model\Infotables;

class Common extends CCommon
{

	/**
	 * 判断是否已登录--(初始化函数 _initialize 优先于 $beforeActionList 配置)
	 */
	public function _initialize()
	{
		parent::_initialize();
		$this->assign("title", config('webTitle'));
	}
	/**
	 * 退出登录
	 */
	public function loginout()
	{
		$this->log("注销登陆", [
			"stauts" => "success",
			"name" => session("user.name")
		]);
		Session::delete("user");
		return $this->success("已注销登录", "index/index#logout", "", 1);
	}
	public function _empty()
	{
		$dir = APP_PATH . request()->module() . DS . "view" . DS . request()->controller() . DS . request()->action() . "." . config('template.view_suffix');
		if (file_exists($dir))
			return $this->fetch(request()->action());
		else {
			return $this->error("页面不在了哦~你猜我给它弄到哪去了？→_→", null, null, 30);
		}
	}
	public function tt()
	{
		$data = null;
		return dump($data);
	}
}
