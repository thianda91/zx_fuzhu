<?php

namespace app\common\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\Session;
use PHPMailer\PHPMailer\PHPMailer;

class Common extends Controller
{

	/**
	 * 判断是否已登录--(初始化函数 _initialize 优先于 $beforeActionList 配置)
	 */
	public function _initialize()
	{
		$request = Request::instance();
		if ($request->controller() == 'common') {
			return $this->error("非法访问！你很6啊。然而我会带你回去。");
		}
		// url : index.php/【MODULE】/【CONTROLLER】/【ACTION】.html
		$permitModule = [];
		$permitController = [ // "Tool"
		];
		$permitActions = [
			"index",
			"main",
			"getvcode"
		];
		// 判断是否从localhost 访问， url 是否允许 未登录访问。否则跳转
		if (substr($request->domain(), -9) == "localhost" || in_array($request->module(), $permitModule) || in_array($request->controller(), $permitController) || in_array($request->action(), $permitActions) || input('session.user/a')) {
			$this->assign("version", config("version"));
		} else {
			// session 保存 to_url
			session("to_url", request()->baseUrl());
			return $this->error('您未登录或登录超时，请先登录！', 'index/index#' . $request->controller() . "/" . $request->action());
		}
	}
	public function index()
	{
		return "当前执行的是：common/index";
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
		return $this->success("已注销登录", "index#logout", "", 1);
	}
	/**
	 * php 数组 转成 Grid 组件需要的 json 格式
	 *
	 * @param array $array        	
	 * @param string $header        	
	 * @return string
	 */
	public static function array_to_json($array = [], $header = '')
	{
		$data_arr = [];
		if ($header == '') {
			foreach ($array as $val) {
				$id = null;
				$theData = [];
				foreach ($val as $k => $v) {
					if ($id == null || $k == 'id')
						// 让 id 为 第一个值，或者为id值
						$id = $v;
					$theData[] = $v;
				}
				$data_arr[] = [
					"id" => $theData[0],
					"data" => $theData
				];
			}
		} else { }
		return json_encode([
			"rows" => $data_arr
		], JSON_UNESCAPED_UNICODE);
	}
	/**
	 * php 数组根据 header 转成 csv 字符串
	 *
	 * @param array $array        	
	 * @param string $header        	
	 * @return string
	 */
	public static function array_to_csv($array = [], $header = '')
	{
		$csvstr = '';
		if ($header == '') {
			foreach ($array as $val) {
				$i = 1;
				foreach ($val as $v) {
					if ($i < count($val))
						$csvstr .= $v . ',';
					else
						$csvstr .= $v . "\n";
					$i++;
				}
			}
		} else {
			$headers = explode(",", $header);
			foreach ($array as $val) {
				for ($i = 0; $i < count($headers); $i++) {
					if ($i < count($headers))
						$csvstr .= $val[$headers[$i]] . ',';
					else
						$csvstr .= $val[$headers[$i]] . "\n";
				}
			}
		}
		$csvstr = substr($csvstr, 0, strlen($csvstr) - 1);
		return $csvstr;
	}
	/**
	 * csv 转 php数组
	 *
	 * @param array $header        	
	 * @param string $csvstr        	
	 * @return array
	 */
	public static function csv_to_array($header = [], $csvstr = '')
	{
		trim($csvstr);
		$data = explode("\n", $csvstr);
		$result = []; // 初始化结果数据
		for ($i = 0; $i < count($data); $i++) { // 将多条原始数据分别分割为数组
			$result[] = array_combine($header, explode(",", trim($data[$i])));
		}
		return $result;
	}
	/**
	 * 根据列名、表名查询非重复数据
	 *
	 * @param string $table
	 * @param array $field
	 * @param array $where
	 * @param string $distinct
	 * @param string $order
	 * @return string|\think\Collection|\think\db\false|PDOStatement|string
	 */
	public static function get_combo_options($table = '', $field = [], $where = [], $distinct = true, $order = "id")
	{
		$table = input("param.table");
		$field = input("param.field/a");
		$where = input("param.where/a");
		$distinct = input("?param.distinct") ? input("param.distinct/b") : true;
		$order = input("param.order");
		if ($table == '')
			return "传值为空";
		foreach ($field as $k => $f) {
			$field_arr[] = $f . " as " . $k;
		}
		$field_str = implode(",", $field_arr);
		$result = Db::name($table)->distinct($distinct)->where($where)->field($field_str)->order($order)->select();
		return $result; // 自动处理成 json()
		// return json_encode($result, 256);//Content-Type:text/html
		// return json ( $result ); // 返回 Content-Type : application/json
	}
	public static function get_combo_options2($column = '', $table = '')
	{
		$table = input("param.table");
		$column = input("param.column");
		if ($table == '' || $column == '')
			return "传值为空,需要_table和_column参数";
		$result = Db::name($table)->distinct(true)->field($column . " as value")->select();
		for ($i = 0; $i < count($result); $i++) {
			$result[$i]['text'] = $result[$i]['value'];
		}
		$result = [
			'options' => $result
		];
		return $result;
	}
	/**
	 * 获取邮件验证码
	 *
	 * @param string $e
	 * @param number $ttl
	 */
	public function getvcode($e = '', $ttl = '120')
	{
		if (preg_match('/[^A-Za-z0-9.@_]+/', $e)) {
			return $this->error('非法邮箱地址哦');
		} else {
			$e = strtolower($e);
		}
		// 限制系统每2小时最多发送12个验证码。（超2小时code可重复）
		$data = Db::table("phpweb_check")->whereTime('time', '-2 hours')->select();
		if (count($data) > 12) {
			return $this->success("单位时间发送验证码过多。请根据页面下方信息与管理员联系。");
		}
		// 检查$ttl分钟内是否已申请
		// $data = Db::table ( "phpweb_check" )->whereTime ( 'time', '-31 min' )->where ( "loginName", $e )->order ( "time desc" )->find ();
		$codes = [];
		foreach ($data as $v) {
			if ($v['email'] == $e) {
				if (time() - strtotime($v['time']) < $ttl * 60) {
					return $this->success("距离上一次申请间隔小于" . $ttl . "分钟，请勿重复操作。<br><br>如未收到请稍等3分钟并检查是否在垃圾邮件中。");
				}
			}
			$codes[] = $v['code'];
		}
		// 检查是否与生效中的其他用户的相同
		do {
			$vcode = rand(0, 9999);
		} while (in_array($vcode, $codes));
		$address = $e;
		$subject = '[' . config('moduleName') . ']验证码：' . sprintf("%04s", $vcode) . '，可在30分钟内使用。';
		$body = '<p style="color:#088bff;">请确认是您申请了邮箱登录的验证码。若非本人操作，请忽略本邮件。</p><hr /><br /><br /><br /><br />
				<div style="width:500px;padding:30px;background-color:#000;color:#bbb;"><p>Powered by 
				<a style="color:#eee;font-weight:bold;" href="' . config('domain_name') . '")">' . config('copyright') . '</a></p>
				<p>Connect me: <a style="color:#eee;font-weight:bold;" href="mailto:' . config('contact') . '">' . config('contact') . '</a></p>
				<p>Visit <a style="color:#eee;font-weight:bold;" href="https://github.com/' . config('github_repo') . '">here</a> to find more</p></div>';
		$sendEmail = $this->sendEmail($address, $subject, $body);
		// $sendEmail = true; // 测试用例
		if (is_bool($sendEmail)) {
			$msg = '验证码已通过邮件发送，请到邮箱内查收主题包含[' . config('moduleName') . ']的邮件。';
			$log_k = "获取验证码";
			// 新用户，通知管理员
			if (Db::table("phpweb_check")->where("email", $e)->find()) { } else {
				$log_k = "首次获取验证码";
				$title = "[新用户]" . $e;
				$msg = "来自IP： " . request()->ip() . "，首次获取验证码。";
				$address = config("manageEmails");
				foreach ($address as $k => $v) {
					$address[$k] = $v . "@ln.chinamobile.com";
				}
				$address['BCC1'] = config('contact');
				$this->noticeManage($title, $msg . "<hr> 访问地址 =>" . input('param.url'), $address);
			}
			// 存入数据库
			$insertData = [
				'code' => $vcode,
				'email' => $e,
				'name' => input("param.name"),
				'module' => request()->module()
			];
			Db::table("phpweb_check")->insert($insertData);
			$this->log($log_k, [
				"status" => "success",
				"name" => input("param.name"),
				"email" => $e,
				"msg" => $msg
			]);
			return $this->success($msg, null, 2 * $vcode);
		} else {
			$msg = "邮件发送未成功：" . $sendEmail;
			$this->log("获取验证码", [
				"status" => "failed",
				"name" => input("param.name"),
				"email" => $e,
				"msg" => $msg
			]);
			return $this->error($msg);
		}
	}
	/**
	 * 获取参数(unused)
	 *
	 * @param string $table
	 * @param array $where
	 * @return string 参数值
	 */
	public static function getSysInfo($label = '')
	{
		return Db::table("phpweb_sysinfo")->where("label", $label)->value("value");
	}
	public function bugReport()
	{
		if (Request::instance()->isGet()) {
			return $this->display("<h3>??????</h3>");
		} else {
			$data = input("post.");
			// return dump($data);
			return Db::name('bugreport')->insert($data);
			// return $this->success("");
		}
	}
	/**
	 * 通知管理员
	 *
	 * @param unknown $title
	 * @param unknown $msg
	 * @param array $address     	
	 */
	protected function noticeManage($title = "", $msg = "", $address = [])
	{
		$number = 20;
		$logs = Db::table("phpweb_log")->field("id,k,v,ip,module,time")->order("time desc")->limit($number)->select();
		$tableStr = '<table class="_xda-resultTable" style="font-size:14px;border-collapse:collapse;border:none;">';
		$tableStr .= '<tr bgcolor="#dddddd" style="font-size:18px;">';
		$tableStr .= '<th>编号</th><th>键</th><th>值</th><th>ip</th><th>模块</th><th>时间</th>';
		$tableStr .= '</tr>';
		foreach ($logs as $key => $value) {
			$tableStr .= '<tr><td style="width:50px;">' . $value["id"] . '</td>';
			$tableStr .= '<td style="width:90px;">' . $value["k"] . '</td>';
			$v = str_replace(",", "\n", $value["v"]);
			$v = str_replace("{", "<pre style='width:500px;overflow:auto;'>", $v);
			$v = str_replace("}", "</pre>", $v);
			$v = str_replace("\":\"", ":\t\t", $v);
			$v = str_replace("\"", "", $v);
			$tableStr .= '<td style="width:500px;">' . $v . '</td>';
			$tableStr .= '<td style="width:130px;">' . long2ip($value['ip']) . '</td>';
			$tableStr .= '<td style="width:65px;">' . $value["module"] . '</td>';
			$tableStr .= '<td style="width:150px;">' . $value["time"] . '</td></tr>';
		}
		$tableStr .= '</table>';
		$tableStr .= '<style>._xda-resultTable th, ._xda-resultTable td{border:solid #000 1px;}</style>';
		// 下面是旧版php的字符串拼接变量的方法。
		$msg = "<p>{$msg}</p><hr><p>以下是最近" . $number . "条系统log日志：</p>{$tableStr}";
		// return $msg;
		$result = $this->sendEmail($address, $title, $msg);
		return $result;
	}
	protected function noticeAdmin($title = "", $msg = "")
	{
		$this->noticeManage($title, $msg, config('contact'));
	}
	/**
	 * 记录系统log
	 *
	 * @param unknown $k        	
	 * @param unknown $v        	
	 * @param unknown $time        	
	 */
	protected function log($k = "", $v = [])
	{
		Db::table("phpweb_log")->insert([
			"k" => $k,
			"v" => json_encode($v, JSON_UNESCAPED_UNICODE),
			"module" => Request::instance()->module(),
			"ip" => ip2long(request()->ip())
		]);
	}
	protected function sendEmail($address = [], $subject = '', $body = '', $attachments = null)
	{
		try {
			$mail = new PHPMailer();
			$mail->isSMTP(); // Set mailer to use SMTP
			$mail->CharSet = "utf-8";
			$mail->SetLanguage('zh_cn');
			// $mail->SMTPDebug = 1;
			$account = config("email");
			$myAddress = config("email.username");
			$mail->Host = $account['SMTP']; // Specify main and backup SMTP servers
			$mail->SMTPAuth = true; // Enable SMTP authentication
			$mail->Username = $myAddress; // SMTP username
			$mail->Password = config("email.password"); // SMTP password
			$mail->SMTPSecure = 'ssl'; // Enable TLS encryption, `ssl` also accepted
			$mail->Port = $account['Port']; // TCP port to connect to
			$mail->setFrom($myAddress, config("email.nickname"));
			if (is_string($address)) {
				$mail->addAddress($address);
			} else {
				foreach ($address as $key => $addr) {
					if (substr($key, 0, 3) === 'BCC') {
						$mail->addBCC($addr);
					} else if (substr($key, 0, 2) === 'CC') {
						$mail->addCC($addr);
					} else {
						$mail->addAddress($addr);
					}
				}
			}
			// Name is optional
			// $mail->addReplyTo ( 'info@example.com', 'Information' );
			// $mail->addCC ( 'cc@example.com' );
			// $mail->addBCC ( 'bcc@example.com' );
			if (!is_null($attachments)) {
				if (is_array($attachments)) {
					foreach ($attachments as $attachment) {
						$mail->addAttachment($attachment);
					}
				} else {
					$mail->addAttachment($attachments);
				}
			}
			// $mail->addAttachment ( '/var/tmp/file.tar.gz' ); // Add attachments
			// $mail->addAttachment ( '/aa.jpg', '附件new.jpg' ); // Optional name
			// 绝对路径从磁盘根目录算起，相对路径从public/index.php算起。
			$mail->isHTML(true); // Set email format to HTML
			$mail->Subject = $subject;
			//$body .= '<br><br><br><p style="float:right;color:red;font-size:10px;">'+config('version')+'</p>';  
			$mail->Body = $body;
			$mail->AltBody = '您的邮件客户端不支持查看HTML格式的邮件正文。';

			if ($mail->send()) {
				return true;
			} else {
				return $mail->ErrorInfo;
			}
		} catch (Exception $e) {
			return $mail->ErrorInfo;
		}
	}
	public function _empty()
	{
		$request = Request::instance();
		$dir = APP_PATH . $request->module() . DS . "view" . DS . strtolower($request->controller()) . DS . $request->action() . "." . config('template.view_suffix');
		if (file_exists($dir))
			return $this->fetch($request->action());
		else {
			return $this->error("请求未找到，将返回上一页...(common/controller/Common.php->_empty())");
		}
	}
}
