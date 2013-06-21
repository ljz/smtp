<?
include("smtp.php");
$smtpHost ="smtp.sina.com";
$port = 25;
$From =  "964697423@qq.com";
$to = "ljz964697423@gmail.com";
$authuser = "smtp主机认证用户名";
$authpass ="smtp主机认证用户密码";
$subject = "主题：SMTP测试";
$body = "<font color = red >欢迎使用SMTP 类发送电子邮件</font>";
$type = "HTML";
$smtp = new smtp($smtpHost,$port,$authuser,$authpass);
$smtp->sendmail($to,$From,$subject,$body,$type);
?>
