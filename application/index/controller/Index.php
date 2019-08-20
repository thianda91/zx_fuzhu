<?php

namespace app\index\controller;

use think\Controller;
use PHPMailer\PHPMailer\PHPMailer;

class Index extends Controller {
	public function index() {
		if (in_array ( substr ( request ()->ip (), 0, 8 ), [ 
				'10.61.21',
				'0.0.0.0',
				'127.0.0.' 
		] )) {
			return $this->fetch ( 'indexs' );
		} else {
			return $this->fetch ();
		}
	}
	public function testMail() {
		$mail = new PHPMailer();
		$mail->isSMTP (); // Set mailer to use SMTP
		$mail->CharSet = "utf-8";
		$mail->SetLanguage ( 'zh_cn' );
		$mail->SMTPDebug = 1;
		$mail->Host = 'smtp.139.com'; // Specify main and backup SMTP servers
		$mail->SMTPAuth = true; // Enable SMTP authentication
		$mail->Username = "tl_excelserver@139.com"; // SMTP username
		$mail->Password = 'HUYUE6868816'; // SMTP password
		                                  // $mail->SMTPSecure = 'tls'; // Enable TLS encryption, `ssl` also accepted
		$mail->Port = 25; // TCP port to connect to
		$mail->setFrom ( 'tl_excelserver@139.com', 'Excel服务器' );
		$mail->addAddress ( '1748104738@139.com' ); // Name is optional
		                                             // $mail->addReplyTo ( 'info@example.com', 'Information' );
		                                             // $mail->addCC ( 'cc@example.com' );
		                                             // $mail->addBCC ( 'bcc@example.com' );
		                                             
		// $mail->addAttachment ( '/var/tmp/file.tar.gz' ); // Add attachments
		$mail->addAttachment ( '/aa.jpg', '附件new.jpg' ); // Optional name
		                                                 // 绝对路径从磁盘根目录算起，相对路径从public/idnex.php算起。
		$mail->isHTML ( true ); // Set email format to HTML
		
		$mail->Subject = '测试邮件标题' . date ( "Y-m-d H:i:s" );
		$mail->Body = 'This is the HTML message body <b>in bold!</b><hr>正文是Body';
		$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
		
		if (! $mail->send ()) {
			echo 'Message could not be sent.';
			echo 'Mailer Error: ' . $mail->ErrorInfo;
		} else {
			echo 'Message has been sent';
		}
		exit ( 0 );
	}
	public function _empty() {
		$dir = APP_PATH . request ()->module () . DS . "view" . DS . request ()->controller () . DS . request ()->action () . "." . config ( 'template.view_suffix' );
		if (file_exists ( $dir ))
			return $this->fetch ( request ()->action () );
		else {
			return $this->error ( "请求未找到(╯﹏╰)", null, null, 60 );
		}
	}
}
