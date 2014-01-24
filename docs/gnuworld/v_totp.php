<?
require("../../php_includes/cmaster.inc");
/* $Id: login.php,v 1.29 2005/12/13 11:49:32 nighty Exp $ */
if($loadavg5 >= CRIT_LOADAVG) {
  	header("Location: highload.php");
  	exit;
}
$totp_pin=$_POST['pin'];
   	if ($totp_pin!="" && !preg_match(NON_BOGUS_TOTP,trim($totp_pin))) {
	echo "<h2>Bogus token PIN</h2><br><a href=\"v_totp.php\">Try again</a>";
	die;
	}
$dummy='n/a';
SetCookie("totp",$dummy,time()+7200,"/",COOKIE_DOMAIN);
header("Pragma: no-cache");
$current_page='v_totp.php';
$user_id = std_security_chk($auth);
$cTheme = get_theme_info();
if ($user_id > 0 )
{
$web_cookie=explode(":", $auth);
$user_name=$web_cookie[0];
	if (!ip_check($user_name,0)) {
		echo "<META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">\n";
		std_theme_styles(1); std_theme_body();
        	echo "<h1>Error<br>\n";
        	echo "Too many failed login attempts for this username.</h1><br>\n";
		echo "</body>\n";
		echo "</html>\n\n";
		$ENABLE_COOKIE_TABLE = 1;
		pg_safe_exec(CLEAR_COOKIES_QUERY);
		pg_safe_exec("delete from webcookies where user_id=" . (int)$user_id);
		if (COOKIE_DOMAIN!="") {
			SetCookie("auth","",0,"/",COOKIE_DOMAIN);
			SetCookie("totp","",0,"/",COOKIE_DOMAIN);
		} else {
			SetCookie("auth","",0,"/");
			SetCookie("totp","",0,"/");
		}
		$ENABLE_COOKIE_TABLE = 0;
		die;
        }
$totp_key=has_totp($user_id);
$expire=time()+get_custom_session($user_id);
$totp_web_help="https://cservice.undernet.org/live/totp";
if(!$totp_key)
	{
	header("Location main.php?sba=1");
	}
	else
	{
	$TimeStamp = Google2FA::get_timestamp();		
	$secretkey = Google2FA::base32_decode($totp_key);
	//$otp       = Google2FA::oath_hotp($secretkey, $TimeStamp);
	$result = Google2FA::verify_key($totp_key, $totp_pin);
	if ($result)
		{
		$temp_totp_hash=gen_totp_cookie($totp_key);
		
		if (COOKIE_DOMAIN!="") 
		{
		SetCookie("csess",$expire."",$expire, "/",COOKIE_DOMAIN) or die ("Can not set cookie");
		SetCookie("totp",$temp_totp_hash,$expire,"/",COOKIE_DOMAIN);
		}
		else
		{
		SetCookie("csess", $expire."", $expire, "/") or die ("Can not set cookie");
		SetCookie("totp",$temp_totp_hash,$expire,"/");
		}
		$fmm="UPDATE webcookies SET totp_cookie='".$temp_totp_hash."' WHERE user_id='" . (int)$user_id . "'";
		pg_exec($fmm);
		$fmm="DELETE from ips where ipnum='" . cl_ip() . "' AND lower(user_name)='" . strtolower($user_name) . "'";
		pg_exec($fmm);
		$admin=std_admin();
		if ($admin>0) { local_seclog("Login"); log_webrelay("authenticated with TOTP at level " . $admin); }
		header ("Location: main.php?sba=1");
		die;
		}
		else
		{
		$msg = "<h3>Invalid TOTP token!</h3>";	
		  ip_check($user_name,1);
		//	totp_ip_add( cl_ip(),  $user_name);
		
			//pg_exec("INSERT INTO ips (ipnum,user_name,expiration,hit_counts,set_on) VALUES ('" . cl_ip() . "','" . $user_name . "',0,1,now()::abstime::int4)");
		}	
	}


echo "<META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">\n";
//echo "<META HTTP-EQUIV=\"Expires\" CONTENT=\"0\">\n";
?>
<html>
<head>
<title>CService Login</title>
<? std_theme_styles(1); ?>
</head>
<?php
std_theme_body("","document.forms[0].pin.focus();");
echo "<center>\n";
echo "<font size=+2><b>Welcome to CService</b></font>\n";
echo "<br>\n";
echo "<table width=\"400\" bgcolor=#" . $cTheme->main_textcolor . ">\n";
echo "<tr><td>\n";
echo "<table cellpadding=5 bgcolor=#" . $cTheme->table_bgcolor . " width=\"100%\">\n";
echo "<tr><td><center>\n";
echo "<font color=#" . $cTheme->main_textcolor . ">\n";
echo "<font size=+2><b>CService Login - TOTP verification</b></font>\n";
if (($totp_pin != '') && ($msg !=''))
	{
	echo $msg;
	local_seclog("Failed login (WRONG TOTP for `" . N_get_pure_string($user_name) . "`)");
	log_webrelay("failed TOTP token.");

	}
echo "<form method=post action=v_totp.php>\n";
echo "<table border=0><tr><td><font color=\"#" . $cTheme->main_textcolor . "\">Enter your token</td><td><input type=text name=pin ></td></tr>\n";
echo "<tr><td colspan=\"2\" align=\"center\"><input type=submit value=Submit></td></tr>";
echo "</center>";
echo "</form>";
?>
</td></tr>
<?php
echo "<tr><td valign=top bgcolor=#ff9999 colspan=\"2\">";
	echo "<font style=\"font-size: 13px; color: #000000; font-weight: bold;\">";
	echo 'If you can not login, consult the online manual at <a href="'.$totp_web_help.'" target="_blank">'.$totp_web_help.'</a> or contact #usernames';
	?>
</table>
</td>
</tr>
</table>

</center>
</body>
</html>
<?
}
else
{
echo "<META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">\n";
//echo "<META HTTP-EQUIV=\"Expires\" CONTENT=\"0\">\n";
?>
<html>
<head>
<title>CService Login</title>
<? std_theme_styles(); ?>
</head>
<?php
echo "<center>\n";
echo "<font size=+2><b>Welcome to CService</b></font>\n";
echo "<br>\n";
echo "<table width=\"400\" bgcolor=#" . $cTheme->main_textcolor . ">\n";
echo "<tr><td>\n";
echo "<table cellpadding=5 bgcolor=#" . $cTheme->table_bgcolor . " width=\"100%\">\n";
echo "<tr><td><center>\n";
echo "<font color=#" . $cTheme->main_textcolor . ">\n";
echo "<font size=+2><b>CService Login - TOTP verification</b></font>\n";
   	echo "<h3>Your username and password were not checked. </h3><br>Click<a href=\"login.php\"> here</a> to login.</td></tr></table></td></tr></table></center></body></html>\n\n";
	die;
}
?>
