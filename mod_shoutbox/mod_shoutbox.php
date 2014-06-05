<?php
/*
mod_shoutbox.php
 */
class mod_shoutbox extends ModuleHelper {
	private $lastno;
	private $logcount;
	private $myPage1;
	private $myPage2;
	private	$MESG_LOG = './shoutbox.log'; // shoutbox紀錄檔位置
	private	$MESG_CACHE = './shoutbox.cc'; // shoutbox快取檔位置
	private	$JSON_CACHE = './shoutbox.json'; // shoutbox JSON快取檔位置
	private	$LOG_MAX = 500; // shoutbox 最大紀錄行數
	private	$MESSAGE_MAX = 50; // shoutbox 單一訊息最大長度
	private	$MES_PER_PAGE = 5; // shoutbox 一頁顯示筆數
	private	$EMOTIONS = array(	'|∀ﾟ )',
								'(´ﾟДﾟ`)',
								'(;´Д`)ﾊｧァ～',
								'|дﾟ )ﾉ',
								'(｀･ω･)',
								'|-` ).｡o０',
								'(=ﾟωﾟ)='); // 表情
	private $wordSHOUT = '喊'; //shoutbox button shown name
	private $CSS = '<style type="text/css"><!--/*--><![CDATA[/*><!--*/
#shoutboxframe {border:0px none;}
#shoutboxformdiv form {display:inline-flex;}
#shoutboxframe.show {display:block;position:absolute;top:3em;left:0; border: 2px #F0E0D6 solid; width: 65%;height: $ifheight; overflow:auto; margin:0; padding:0;}
#shoutboxframe.hide {display:none;}
#shoutboxframe {margin:0 0 0 0.4em;padding:0}
.shoutInput {padding:0;margin:0;border:1px solid #888;}
.shoutBtn {padding:0;margin:0;background-color:#ccc;border:1px solid #888;}
.shout {font-size:9pt}
.shout .e {font-weight:bold}
.shout .d {color:gray}
/*!--> ]]> */
</style>
';
 
	public function __construct($PMS) {
		parent::__construct($PMS);
		$this->myPage1 = $this->getModulePageURL(); // 基底位置
	}

	public function getModuleName(){
		return 'mod_shoutbox : 即時留言';
	}

	public function getModuleVersionInfo(){
		return '7th.Release (v140530)';
	}
	public function autoHookHead(&$dat,$isRes){
		$this->myPage2='http://'.$_SERVER['HTTP_HOST'].preg_replace('/'.basename($_SERVER['PHP_SELF']).'$/', '', $_SERVER['PHP_SELF']).str_replace('&amp;','&',$this->myPage1);
		$ifheight=($this->MES_PER_PAGE+11)."em";
		$dat .= $this->CSS;
		$dat .= '<script type="text/javascript">
//<![CDATA[
function gID(s) { return document.getElementById(s); }
/* 建立XMLHttpRequest物件 */
function JSONXMLHttpReq(){
	var objxml = false;
	/*@cc_on @*/
	/*@if (@_jscript_version >= 5)
	try{
		objxml = new ActiveXObject("Msxml2.XMLHTTP");
	}catch(e){
		try{
			objxml = new ActiveXObject("Microsoft.XMLHTTP");
		}catch(e2){ objxml = false; }
	}
	@end @*/
	if(!objxml && typeof XMLHttpRequest!="undefined") {
		objxml = new XMLHttpRequest();
		if(objxml.overrideMimeType) objxml.overrideMimeType("text/plain");
	}
	return objxml;
}
var xhttpjson=JSONXMLHttpReq();

function getLatestMessage(){
	if(xhttpjson){
		xhttpjson.open("GET","'.$this->myPage2.'&action=latest", true);
		xhttpjson.onreadystatechange = ParseLatestMessage;
		xhttpjson.send(null);
	}
}
function ParseLatestMessage(){
	if(xhttpjson.readyState==4){ // 讀取完成
		var returnObj = eval("("+xhttpjson.responseText+")");
		if(returnObj.message) {
			gID("latestshout").innerHTML="<span class=\"e\">"+returnObj.emotion+"</span>&gt; <span class=\"m\">"+returnObj.message+"</span> <span class=\"d\">("+returnObj.date+")</span>";
		}
	}
}

function ToggleShoutBox() {
	if(gID("shoutboxframe").className=="hide") {
		gID("shoutboxframe").src="'.$this->myPage2.'";
		gID("shoutboxframe").className="show";
	}else{
		gID("shoutboxframe").src="about:blank";
		gID("shoutboxframe").className="hide";
	}
}

function realsubmit() {
	if(gID("shout_mesg").value) {
		gID("real_shout_mesg").value=gID("shout_mesg").value;
		gID("real_shout_emo").value=gID("shout_emo").value;
		gID("realshoutboxform").submit();
		gID("shout_mesg").value="";
		setTimeout("getLatestMessage()",1000);
	}
	return false;
}
//--><!]]></script>';
	}

	/* 自動掛載：頂部連結列 */
	public function autoHookToplink(&$linkbar, $isReply){
		$linkbar = '<div id="mod_shoutboxdiv" style="display:inline;float:left;"><div class="shout" style="float:left;" id="latestshout"></div><form action="'.$this->myPage1.'" method="post" id="shoutboxform" target="shoutboxframe" onsubmit="return realsubmit();"><input type="hidden" name="action" value="shout"/><select name="emotion" id="shout_emo" class="shoutInput">'.$this->_getEmotionHTML().'</select>&gt;<input type="text" name="message" value="" id="shout_mesg" size="18" class="shoutInput"/><input type="submit" name="submit" value="'.$this->wordSHOUT.'" class="shoutBtn"/></form>
<script type="text/javascript">//<!--//--><![CDATA[//><!--
getLatestMessage();
setInterval("getLatestMessage()",30000);
//--><!]]></script>
<div style="display:inline"><div class="shout" style="display:table-cell;float:left;"> <iframe id="shoutboxframe" class="hide" name="shoutboxframe"></iframe></div>
<div style="display:inline"><form action="'.$this->myPage1.'" method="post" id="realshoutboxform" style="display:none" target="shoutboxframe"><input type="hidden" name="action" value="shout"/><input type="hidden" name="emotion" id="real_shout_emo" value=""/><input type="hidden" name="message" id="real_shout_mesg" value=""/></form>
    '."\n".'</div></div></div>[<a href="javascript:ToggleShoutBox();">Shoutbox</a>]'.$linkbar;
	}

	private function _getEmotionHTML() {
		$html='';$ecnt=count($this->EMOTIONS);
		for($i=0;$i<$ecnt;$i++) {
			$html.="<option value='$i'".(!$i?' selected="selected"':'').'>'.$this->EMOTIONS[$i]."</option>\n";
		}
		return $html;
	}

	private function _latestMessage() {
		if(file_exists($this->JSON_CACHE)) readfile($this->JSON_CACHE);
		else {
			if($logs=@file($this->MESG_LOG)) { // mesgno,date,emo,mesg,ip = each $logs, order desc
				if(isset($logs[0])) {
					list(,$date,$emo,$mes,)=explode(',',$logs[0]);
					echo $this->_rebuildJSON($date,$emo,$mes);
				}
			} else return '{}'; // return null object
		}
	}

	private function _loadCache() {
		if(!$this->lastno) {
			if($logs=@file($this->MESG_CACHE)) { // 有快取
				$this->lastno=trim($logs[0]);
				$this->logcount=trim($logs[1]);
				return true;
			} else { // 無快取
				return $this->_rebuildCache();
			}
		} else return true;
	}

	private function _rebuildJSON($date,$emo,$mes) {
		$json='{"emotion":"'.addslashes($emo).'","message":"'.addslashes($mes).'","date":"'.gmdate('Y-m-d H:i:s',$date+TIME_ZONE*3600).'"}';
		$this->_write($this->JSON_CACHE,$json);
		return $json;
	}

	private function _rebuildCache() {
		if($logs=@file($this->MESG_LOG)) { // mesgno,date,emo,mesg,ip = each $logs, order desc
			if(!$this->lastno) if(isset($logs[0])) $this->lastno = intval(substr($logs[0],strpos($logs[0],',')));
			$this->logcount = count($logs);
			$this->_writeCache();
			return true;
		} else {
			$this->_writeCache();
			return false;
		}
	}

	private function _writeCache() {
		$this->_write($this->MESG_CACHE,intval($this->lastno)."\n".intval($this->logcount)."\n");
	}

	private function _write($file,$data) {
		$rp = fopen($file, "w");
		flock($rp, LOCK_EX); // 鎖定檔案
		@fputs($rp,$data);
		flock($rp, LOCK_UN); // 解鎖
		fclose($rp);
		chmod($file,0666);
	}

	private function _post() {
		$emo=isset($_POST['emotion'])?intval($_POST['emotion']):0;
		$mesg=isset($_POST['message'])?$_POST['message']:'';
		$mesg=CleanStr($mesg);
		if(!$mesg) error("請填入內文");
		if(preg_match("/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/",$mesg)) error('eMail禁止');
		if(strlen($mesg)>$this->MESSAGE_MAX) error(_T('regist_commenttoolong'));
		$mesg = str_replace("\n",' ',$mesg); // 消除換行
		$mesg = str_replace(',','&#44;',$mesg); // 轉換","

		$this->_loadCache();

		$logs=@file($this->MESG_LOG);
		if($logs===false) $logs=array();
		if(($this->logcount+1) > $this->LOG_MAX) @array_splice($logs,$this->LOG_MAX-2); // chop by index

		// 檢查重複
		if($logs[0]) {
			list(,,,$lmsg,)=explode(',',$logs[0]);
			if($lmsg == $mesg) return;
		}
		$logs=(++$this->lastno).','.($now=time()).','.$this->EMOTIONS[$emo].",$mesg,$_SERVER[REMOTE_ADDR],\n".implode('',$logs);
		$this->_write($this->MESG_LOG,$logs);

		$this->_rebuildJSON($now,$this->EMOTIONS[$emo],$mesg);
		$this->_rebuildCache();
	}

	private function _showMessages($from,$to) {
		$PTE = PMCLibrary::getPTEInstance();
		$dat='';$pagebar='';$gotmesg=false;
		
		$dat .= $PTE->ParseBlock('REALSEPARATE',array()).'<div class="shout"><form action="'.$this->myPage1.'" method="post"><input type="hidden" name="action" value="delete" />';
		if($logs=@file($this->MESG_LOG)) { // mesgno,date,emo,mesg,ip = each $logs, order desc
			$mcnt=count($logs);
			for($i=$from;$i<$to;$i++) {
				if(!isset($logs[$i])) continue;
				$gotmesg=true;
				list($mno,$date,$emo,$mesg,)=explode(',',$logs[$i]);
					if(!$dat) $dat=$PTE->ParseBlock('REALSEPARATE',array()).'<form action="'.$this->myPage1.'" method="post"><input type="hidden" name="action" value="delete" />';
					$dat.="<input type='checkbox' name='$mno' value='delete' /><span class='e'>$emo</span>&gt; <span class='m'>$mesg</span> <span class='d'>(".gmdate("Y-m-d H:i:s",$date+TIME_ZONE*3600).')</span><br/>';
			}

			// 換頁列
			$pages=intval(($mcnt-1)/$this->MES_PER_PAGE);
			$thispage=$from/$this->MES_PER_PAGE;
			$pagebar='<div style="float:left;clear:right;">[ ';
			for($i=0;$i<=$pages;$i++) {
				if($i==$thispage) $pagebar.="<strong>$i</strong> ";
				else $pagebar.='<a href="'.$this->myPage1.'&page='.$i.'">'.$i.'</a> ';
			}
			$pagebar.=']</div>';
		}
		if(!$gotmesg) $dat.='沒有信息。';
		$dat .= $PTE->ParseBlock('REALSEPARATE',array()).$pagebar.'<div align="right">PASS:<input type="password" name="pwd" value="" size="8" class="shoutInput"/><input type="submit" name="delete" value="'._T('del_btn').'" class="shoutBtn"/></div></form>';
		return $dat;
	}

	private function _deleteMessage($no,$pass) {
		if(passwordVerify($pass)===false) return;//lib_common passwordVerify
		$found=false;
		if($logs=@file($this->MESG_LOG)) { // mesgno,date,emo,mesg,ip = each $logs, order desc
			$countlogs=count($logs);
			foreach($no as $n) {
				for($i=0;$i<$countlogs;$i++) {
					list($mno,)=explode(',',$logs[$i]);
					if($mno==$n) {
						$logs[$i]=''; // deleted
						$found=true;
						break;
					}
				}
			}
			if($found) {
				$newlogs=implode('',$logs);
				$this->_write($this->MESG_LOG,$newlogs);
				$newloglines=explode("\n",$newlogs);
				if(count($newloglines)) {
					list(,$now,$emo,$mesg,) = explode(',',$newloglines[0]);
					$this->_rebuildJSON($now,$emo,$mesg);
				}
				$this->_rebuildCache();
			}
		}
	}

	public function ModulePage(){
		$PTE = PMCLibrary::getPTEInstance();
		$action=isset($_REQUEST['action'])?$_REQUEST['action']:'';
		$page=isset($_REQUEST['page'])?intval($_REQUEST['page']):0;
		if($action == 'latest') {$this->_latestMessage(); return;}
		if($action == 'shout') $this->_post();
		if($action == 'delete' && isset($_POST['pwd'])) {
			$delno=array();
			while($item = each($_POST)) if($item[1]=='delete') array_push($delno, $item[0]);
			if(count($delno)) $this->_deleteMessage($delno,$_POST['pwd']);
		}
		$pte_vals = array('{$TITLE}'=>TITLE,'{$RESTO}'=>'');
		$dat = $PTE->ParseBlock('HEADER',$pte_vals);
		$this->autoHookHead($dat,0); // add my headers
		$dat .= '</head><body id="shoutbox_main">';
		$dat.='Shoutbox<br/><form action="'.$this->myPage1.'" method="post"><input type="hidden" name="action" value="shout"/><select name="emotion" id="shout_emo" class="shoutInput">'.$this->_getEmotionHTML().'</select>&gt;<input type="text" name="message" value="" id="shout_mesg" size="18" class="shoutInput"/><input type="submit" name="submit" value="'.$this->wordSHOUT.'" class="shoutBtn"/></form>';
		$dat.=$this->_showMessages($page * $this->MES_PER_PAGE,($page+1) * $this->MES_PER_PAGE);
		echo $dat.'</body></html>';
	}
}
