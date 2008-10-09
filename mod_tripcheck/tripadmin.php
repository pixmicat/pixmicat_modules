<?php
include_once('./config.php');
define("TRIPFILE", 'board.trip'); // トリップ記錄檔檔名
define("ALLOW_TRIP_REG", '1'); // 容許一般使用者註冊トリップ (是：1 否：0)

$action='';$viewstart=0;$viewend=100;
extract($_POST);
extract($_GET);

//	ファイル全体読み込み
function FileRead($szFileName) {
	if(!file_exists("$szFileName")) SysError("<B>$szFileName</B> をオープンできません");
	return @file("$szFileName");
}
//	ファイル書き込み
function FileWrite($szFileName, &$writedata, $bMode = "w") {
	if($bMode != "a" && $bMode != "w"){ $bMode = "w"; }
	
	//	Windowsシステムのためバイナリモードを付加しておく
	if(substr($bMode, -1) != "b"){ $bMode .= "b"; }
	if(!($fp = @fopen("$szFileName", $bMode))){ SysError("<B>$szFileName</B> をオープンできません"); }
	flock($fp, 2);
	fwrite($fp, $writedata);
	fclose($fp);
}
//	ファイル消去
function FileFlush($szFileName) {
	if(!($fp = @fopen("$szFileName", "wb"))){ SysError("<B>$szFileName</B> をオープンできません"); }
	flock($fp, 2);
	fwrite($fp, "");
	fclose($fp);
}
//	不要文字列除去($str：文字列)
function CleanStr(&$szStr) {
	if(get_magic_quotes_gpc()){ $szStr = stripslashes($szStr); }
	$szStr = htmlspecialchars($szStr);
	$szStr = str_replace(",", "&#44;", $szStr);
	return chop(str_replace("&amp;", "&", $szStr));
}
//	システムエラー
function SysError($text) {
	echo '<HTML>
<HEAD>
<META http-equiv="Content-Type" content="text/html; charset=UTF-8">
<META http-equiv="Content-Style-Type" content="text/css">
<TITLE>ERROR！</TITLE>
</HEAD>
<body>
<FONT size="+1" color="#FF0000"><B>ERROR：'.$text.'</B></FONT>
<BR><BR>
';
	HtmlFooter(1);
	exit();
}

function HtmlHeader() {
	//	HTMLヘッダー
	echo '<HTML>
<HEAD>
<META http-equiv="Content-Type" content="text/html; charset=UTF-8">
<META http-equiv="Content-Style-Type" content="text/css">
<TITLE>トリップ事務所</TITLE>
</HEAD>
<body>
<H2><FONT face="Arial">トリップ事務所 for futaba</FONT></H2>
<HR noshade size="1">
';
}

function HtmlFooter($self=0) {
	$PHPSELF=$self?$_SERVER['PHP_SELF']:PHP_SELF2;
	//	HTMLフッター
	echo <<<HTML
<HR noshade size="1">
【 <A href="$PHPSELF">Return</A> 】
<HR noshade size="1">
<div align="right"><FONT size="1">tripadmin.php(adopted from ThreadBBS)</FONT></div>
</BODY>
</HTML>
HTML;
	exit();
}

function check_login($forcelogin=1) {
	if (!isset($_SESSION['username']) || !isset($_SESSION['password'])) {
		$logged_in=0;
		if($forcelogin) SysError("ユーザーの認証が必要です。<br /><form action=\"".$_SERVER['PHP_SELF']."?action=login\" method=\"POST\">ユーザー：<INPUT type=\"text\" name=\"username\"><br />パスワード：<INPUT type=\"password\" name=\"password\"><INPUT type=\"submit\" value=\"認証\"></form>");
		else return $logged_in;
	} else {
		if($_SESSION['username']!=CAP_NAME || $_SESSION['password']!=CAP_PASS) {
			$logged_in=0;
			unset($_SESSION['username']);
			unset($_SESSION['password']);
			// kill incorrect session variables.
			if($forcelogin) SysError("ユーザーの認証が必要です。<br /><form action=\"".$_SERVER['PHP_SELF']."?action=login\" method=\"POST\">ユーザー：<INPUT type=\"text\" name=\"username\"><br />パスワード：<INPUT type=\"password\" name=\"password\"><INPUT type=\"submit\" value=\"認証\"></form>");
			else return $logged_in;
		}else { 
			// valid password for username
			$logged_in = 1;		// they have correct info
			return $logged_in;	// in session variables.
		}
	}
}

$goto=0;
	session_start();

	switch($action){
	case 'logout':
		unset($_SESSION['username']);
		unset($_SESSION['password']);
		echo "<META http-equiv=\"refresh\" content=\"1;URL=$_SERVER[PHP_SELF]\">";
		echo "ログアウトしました。<br />数秒後にページが自動的に切り変わります。<br />しばらく待っても変わらない場合は、<a href=\"$_SERVER[PHP_SELF]\">こちら</a>をクリックしてください。";
		break;
	case 'login':
		if(isset($_POST['username'])&&isset($_POST['password'])) {
			$_SESSION['username']=$_POST['username'];
			$_SESSION['password']=$_POST['password'];
		}
		check_login();
		echo "<META http-equiv=\"refresh\" content=\"1;URL=$_SERVER[PHP_SELF]\">";
		echo "ログインしました。<br />数秒後にページが自動的に切り変わります。<br />しばらく待っても変わらない場合は、<a href=\"$_SERVER[PHP_SELF]\">こちら</a>をクリックしてください。";
		break;
	case 'manage':
		check_login();
		$delflag = isset($_POST["del"]); // 是否有「削除」勾選
		$actflag = isset($_POST["act"]); // 是否有「有効」勾選
		$banflag = isset($_POST["ban"]); // 是否有「禁止」勾選
		$dpflag = isset($_POST["dp"]); // 是否有「削除人」勾選

		if($delflag || $actflag || $banflag || $dpflag) {
			$aTripList = FileRead(TRIPFILE);
			
			$szTemp = "";
			while(list(, $val) = @each($aTripList)){
				$bFlag = TRUE;
				@list($szTrip,$szTime,$szIP,$szActivate,$szBan,$szDelPerm) = @explode("<>", $val);
				if($banflag) {
					reset($_POST["ban"]);
					while(list(, $tTrip) = @each($_POST["ban"])){
						if($szTrip == $tTrip){ $val = "$szTrip<>$szTime<>$szIP<>$szActivate<>".($szBan=!$szBan)."<>$szDelPerm<>\n"; break; }
					}
				}
				if($dpflag) {
					reset($_POST["dp"]);
					while(list(, $tTrip) = @each($_POST["dp"])){
						if($szTrip == $tTrip){ $val = "$szTrip<>$szTime<>$szIP<>$szActivate<>$szBan<>".($szDelPerm=!$szDelPerm)."<>\n"; break; }
					}
				}
				if($actflag) {
					reset($_POST["act"]);
					while(list(, $tTrip) = @each($_POST["act"])){
						if($szTrip == $tTrip){ $val = "$szTrip<>$szTime<>$szIP<>1<>$szBan<>$szDelPerm<>\n"; break; }
					}
				}
				if($delflag) {
					reset($_POST["del"]);
					while(list(, $tTrip) = @each($_POST["del"])){
						if($szTrip == $tTrip){ $bFlag = FALSE; break; }
					}
				}
				if($bFlag){ $szTemp .= $val; }
			}
			FileWrite(TRIPFILE, $szTemp, "w");
			HtmlHeader();
			echo "<BR>処理しました。<BR><BR>";
		}
		$goto=1;
		break;
	case 'add':
		if(preg_match("/(#|＃)(.*)/",$triptext,$regs)){
			$cap = $regs[2];
			$cap = strtr($cap,array("&amp;"=>"&","&#44;"=>","));
			$salt = substr($cap."H.",1,2);
			$salt = preg_replace("/[^\.-z]/",".",$salt);
			$salt = strtr($salt,":;<=>?@[\\]^_`","ABCDEFGabcdef");
			$trip=substr(crypt($cap,$salt),-10);
		}else{
			SysError("フォーマットは正しくない。");
		}
		
		$aTripList = FileRead(TRIPFILE);
		
		$bFlag = FALSE;
		while(list(,$val) = @each($aTripList)){
			list($szTrip,) = explode("<>", $val);
			if($trip == $szTrip){ $bFlag = TRUE; break; }
		}
		if($bFlag){ SysError("同じトリップが存在します。"); }
		
		$szTemp = "$trip<>".time()."<>".$_SERVER['REMOTE_ADDR']."<>0<>0<>0<>\n";
		FileWrite(TRIPFILE, $szTemp, "a");
		HtmlHeader();
		echo "トリップ <B>$trip</B> を追加しました。";
		$goto=1;
		break;
	case 'alldelview':
		check_login();
		HtmlHeader();
		echo <<<HTML
<P>全てのトリップを削除します。<BR>よろしいですか？</P>
<FORM method="POST" action="$_SERVER[PHP_SELF]" ENCTYPE="multipart/form-data">
<INPUT type="hidden" name="action" value="alldel">
<INPUT type="submit" value="全削除">
</FORM>
HTML;

		break;
	case 'alldel':
		check_login();
		FileFlush(TRIPFILE);
		HtmlHeader();
		echo "<BR>トリップを全削除しました。<BR><BR>";
		$goto=1;
		break;
	default:
		HtmlHeader();
		if(ALLOW_TRIP_REG||check_login(0)) echo <<<HTML
<P>トリップ追加</P>
<FORM method="POST" action="$_SERVER[PHP_SELF]" ENCTYPE="multipart/form-data">
<INPUT type="hidden" name="action" value="add">
<TABLE border="0" cellpadding="0" cellspacing="0">
<TBODY>
<TR>
<TD><dl><dt>トリップ("#"というのから始めます、例："#123")</dt>
<dd><INPUT size="20" type="text" maxlength="100" name="triptext"></dd></dl></TD>
</TR>
<TR>
<TD><INPUT type="submit" value="追加">　<INPUT type="reset" value="リセット"></TD>
</TR>
</TBODY>
</TABLE>
</FORM>
HTML;
		if(check_login(0)) {
			$aTripList = FileRead(TRIPFILE);
			echo <<<HTML
<HR size="1" noshade>
<P>トリップ全削除</P>
<FORM method="POST" action="$_SERVER[PHP_SELF]" ENCTYPE="multipart/form-data">
<INPUT type="hidden" name="action" value="alldelview">
<INPUT type="submit" value="全削除">
</FORM>
<HR size="1" noshade>
<P>トリップ管理</P>
<FORM method="POST" action="$_SERVER[PHP_SELF]" ENCTYPE="multipart/form-data">
<INPUT type="hidden" name="action" value="manage">
<table border=1>
<tr><th>削除</th><th>トリップ</th><th>日時</th><th>IP</th><th>有効</th><th>禁止</th><th>削除人</th></tr>
HTML;
			$TripCount=count($aTripList);
			if($viewstart == ""){ $vstart = 1; }
			else{ $vstart = $viewstart; }
			if($viewend == ""){ $vend = 100; }
			else{ $vend = $viewend; }
			$vend=$TripCount>$viewend?$viewend:$TripCount;
			$szNextLink = "";
			for($i=1; $i<=$TripCount; $i+=100){
				$end = $i + 99;
				$szNextLink .= " <a href=\"$_SERVER[PHP_SELF]?viewstart=$i&viewend=$end\">$i-</a>";
			}
			
			if($szNextLink != ""){ echo "$szNextLink<BR>"; }
			
			for($i=$vstart-1;$i<$vend;$i++){
				if($aTripList[$i] == ""){ break; }
				@list($szTrip,$szTime,$szIP,$szActivate,$szBan,$szDelPerm) = @explode("<>", $aTripList[$i]);
				echo "<tr><td><INPUT type=\"checkbox\" name=\"del[]\" value=\"$szTrip\"></td><td>$szTrip</td><td>".date('Y-m-d H:m:s',$szTime)."</td><td>$szIP</td><td>".(!$szActivate?"<INPUT type=\"checkbox\" name=\"act[]\" value=\"$szTrip\">":'はい')."</td><td><INPUT type=\"checkbox\" name=\"ban[]\" value=\"$szTrip\">".($szBan?'はい':'')."</td><td><INPUT type=\"checkbox\" name=\"dp[]\" value=\"$szTrip\">".($szDelPerm?'はい':'')."</td></tr>";
			}
			echo "</table>";
			if($szNextLink != ""){ echo "$szNextLink<BR>"; }
			echo <<<HTML
<INPUT type="submit" value="送信">
</FORM>
<FORM method="POST" action="$_SERVER[PHP_SELF]" ENCTYPE="multipart/form-data">
<INPUT type="hidden" name="action" value="logout">
<INPUT type="submit" value="ログアウト">
</FORM>
HTML;
		} else {
			echo "<HR size=\"1\" noshade><P>ログイン</P><form action=\"".$_SERVER['PHP_SELF']."?action=login\" method=\"POST\">ユーザー：<INPUT type=\"text\" name=\"username\"><br />パスワード：<INPUT type=\"password\" name=\"password\"><INPUT type=\"submit\" value=\"認証\"></form>";
		}
		break;
	}
	
	HtmlFooter($goto);
?>
