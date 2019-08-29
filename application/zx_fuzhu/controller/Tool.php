<?php

namespace app\zx_fuzhu\controller;

class Tool extends Common
{
	public function _initialize()
	{
		parent::_initialize();
	}
	public function index()
	{
		// 暂未配置登录页面
		return $this->redirect("main");
	}
}
