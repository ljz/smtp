<?
class smtp
{
	//定义公共变量
	var $smtp_port;
	var $time_out = 30;
	var $host_name ="localhost";
	var $log_file = "";
	var $smtp_host;
	var $debug;
	var $user;
	var $pass;
	var $sock = false;
	var $auth =true;


	//使用构造函数初始化相关参数
	function smtp( $smtp_host="",$smtp_port=25,$user,$pass) 
	{
		$this->$debug = FALSE;
		$this->$smtp_host = $smtp_host;
		$this->$smtp_port = $smtp_port;
		$this->$user = $user;
		$this->$pass = $pass; 
	}


	//发送邮件函数
	function sendmail($to,$from,$subject = "",$body="",$mailtype,$cc="",$bcc="",$mail_headers="")
	{
		//获取发件者地址
		$mail_from = $this->get_address($this->strip_comment($from));
		$body = ereg_replace("(^|(\r\n))(\.)"."\1. \3",$body);//处理邮件内容
		$header = "MIME-VERSION：1.0\r\n";  			//构建邮件头信息
		//如果邮件类型是HTML，添加HTML头信息
		if($mailtype="HTML")
		{
			$header .= "Content-Type:text/html \r\n"; 
		}
		$header .= "TO:".$to ."\r\n";  			//处理接受者邮件
		if( $cc != "")                                  //处理抄送者邮件
		{
			$header .= "Cc:".$cc .">\r\n";
		}
		$header .= "From:$from <".$from .">\r\n";//构建头文件中发送者的内容
		$header .= "Subject:".$subject .">\r\n";//构建头文件中标题内容
		$header .= $mail_headers;
		$header.= "Date:".date("r")."\r\n";   //构建头信息中的时间
		$header.="X-Mailer:By Redhat(PHP/".phpversion().")\r\n";
		list($msec,$sec) = explode("",microtime());
		$header .= "Message -ID:<".date("YmdHis",$sec).".".($msec*1000000).".".$mail_from.">\r\n";
		//取得接收邮件地址
		$TO = explode(",",$this->strip_comment($to));		//使用explode切割接收者邮件地址
		if($cc != "")
		{
			$TO = array_merge($TO,explode(",",$this->strip_comment($cc)));
		}
		if($bcc !="")
		{
			$TO = array_merge($TO,explode(",",$this->strip_comment($bcc)));
		}
		$sent = TRUE;
		foreach($TO as $rcpt_to){
			$rcpt_to= $this->get_address($rcpt_to);//用get_adderss 取得收件人的邮件地址
		}
		if(!$this->smtp_sockopen($rcpt_to))   //使用smtpp_sockopen（）函数打开sock
		{
			$this->alert("打开SMTP服务器失败".$rcpt_to."<br>");
			$sent = FALSE;
			continue;
		}

		//使用smtp_send()方法向邮件服务器发送命令
		if($this->smtp_send($this->$host_name,$mail_from,$rcpt_to,$header,$body))
		{
			$this->alert("发送邮件到<".$recpt_to.">成功<br>");
		}
		else
		{
			$this->alert("发送邮件到<".$tecpt_to.">失败<br>");
			$sent = FALSE;
		}

		fclose($this->sock);
		$this->alert("与远程主机断开连接");

		return $sent;
	}





	//SMTP主机命令发送
	function smtp_send($helo,$from,$to, $header,$body="")
	{
		//使用smtp_pubcmd()函数向邮件服务器发送命令
		if(!$this->smtp_putcmd("HELO",$helo))
		{
			return $this->smtp_error("发送HELO命令");
		}
		if($this->auth)
		{//如果smtp服务器是需要认证的
			//使用smtp_putcmd()函数发送认证信息
			if(!$this->smtp_putcmd("AUTH LOGIN",base64_encode($this->user)))
			{
				return $this->smtp_error("发送HELO命令");
			}  
			if(!$this->smtp_putcmd("AUTH LOGIN",base64_encode($this->pass)))
			{
				return $this->smtp_error("发送HELO命令");
			}
		}

		//smtp_putcmd向smtp服务器发送邮件发送者的命令
		if(!$this->smtp_putcmd("MAIL","FROM:<".$from.">"))
		{
			return $this->smtp_error("发送MAIL FROM 命令");
		}
		//smtp_putcmd向smtp服务器发送邮件接收者的命令
		if(!$this->smtp_putcmd("RCPT","TO：<".$to.">"))
		{
			return $this->smtp_error("发送MAIL FROM 命令");
		}


		if(!$this->smtp_putcmd("DATA"))
		{
			return $this->smtp_error("发送DATA 命令");
		}
		if(!$this->smtp_message($header,$body))
		{
			return $this->smtp_error("发送信息");
		}
		if(!$this->smtp_eom())
		{
			return $this->smtp_error("发送<CR><LF>.<CR><LF>[EOM]");
		}
		if(!$this->smtp_putcmd("QUIT命令"))
		{
			return $this->smtp_error("发送QUIT命令");
		}
		return TRUE;

	}



	//使用fsockopen()函数连接到SMTP主机
	function smtp_sockopen(){
		$this->alert("连接到远程主机".$this->smtp_host.":".$this->smtp_ort);
		//使用fsockopen打开smtp主机
		$this->sock =@ fsockopen($this->smtp_host,$this->smto_port,$errno,$errstr,$this->time_out);
		//如果主机链接失败就返回错误信息
		if(!($this->sock && $this->smtp_ok())){
			$this->alert("错误：打开远程主机".$this->smtp_host."失败");
			$this->alert("错误内容：".$errstr."(".$errno.")");
			return FALSE;
		}
		$this->alert("已经连接到了SMTP主机：".$this->smtp_host);
		return TRUE;
	}


	//发送邮件的头信息和内容
	function smtp_message(){
		//使用fputs函数向smtp服务器发送邮件头信息和邮件内容
		fputs($this->sock,$header."\r\n".$body);
		$this->alert(">".str_replace("\r\n","\n".">",$header."\n>".$body."\n>".$body."\n>"));
		return TRUE;
	}


	//发送邮件结束命令
	function smtp_eom(){
		fputs($this->sock,"\r\n.\r\n");
		$this->alert(".[EOM]<br>");
		return $this->smtp_ok();
	}


	//检测smtp命令是否成功
	function smtp_ok()
	{
		$reponse=str_replace("\r\n","",fgets($this->sock,512));//获取sock返回数据

		$this->alert($response."\n"); 		//显示返回数据
		if(! ereg("^[23]",$response)){
			fputs($this->sock,"QUIT\r\n");//向smtp主机发送quit命令
			fgets($this->sock,512);
			$this->alert("错误：远程主机返回\"".$reponse."\"\n");
			return FALSE;
		}
		return TRUE;
	}



	//发送smtp命令到smtp服务器
	function smtp_putcmd($cmd,$arg="")
	{
		if($arg !="")
		{
			if($cmd =="")
			{
				$cmd = $arg;
			}
			else{
				$cmd = $cmd."".$arg;
			}
		}
		fputs($this->sock,$cmd."\r\n");
		$this->alert(">".$cmd."\n");
		return $this->smtp_ok();
	}



	//显示发送smtp命令时的错误信息
	function smtp_error($string)
	{
		$this->alert("".$string.".时出现错误<br>");
		return FALSE;
	}



	//显示运行信息
	function alert($message){
		echo $message ."<br>";
		return TRUE;
	}


	//注释邮箱地址
	function strip_comment($address)
	{
		$comment = "\([^()]*\)";
		while(ereg($comment,$address))    //使用正则表达式，处理邮箱地址
		{
			$address = ereg_replace($comment,"",$address);
		}
		return $address;
	}


	//取得电子邮件地址
	function get_address($address)
	{
		$address = ereg_replace("([ \t\r\n])+","",$address);
		$address = ereg_replace("^.*<(.+)>.*$","\l",$address);
		return $address;                           //取得正则表达式处理的邮件地址
	}

}
?>
