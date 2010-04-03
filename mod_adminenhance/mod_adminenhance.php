<?php
class mod_adminenhance{
	var $mypage;
	var $ipfile, $imgfile;

	function mod_adminenhance(){
		global $PMS;
		$PMS->hookModuleMethod('ModulePage', __CLASS__);
		$this->mypage = $PMS->getModulePageURL(__CLASS__);
		$this->ipfile = '.ht_blacklist'; $this->imgfile = '.ht_md5list';
	}

	function getModuleName(){
		return 'mod_adminenhance : 管理工具增強組合包';
	}

	function getModuleVersionInfo(){
		return '5th.Release (v100318)';
	}

	/* 從資料檔抓出資料 */
	function _parseBlackListFile($fname, $only1st=false){
		if(!is_file($fname)) return array();
		$l = file($fname);
		$r = array(); $autodelno = array();
		$tmp = '';
		$now = time();
		for($i = 0, $len = count($l); $i < $len; $i++){
			$tmp = explode("\t", rtrim($l[$i]));
			if(isset($tmp[3]) && $tmp[3] != '0'){ // 封鎖時段已過
				if($tmp[2] + intval($tmp[3]) * 86400 < $now){
					$autodelno[] = $i;
					continue;
				}
			}
			$r[] = $only1st ? $tmp[0] : $tmp;
		}
		if(count($autodelno)) $this->_arrangeRecord($this->ipfile, $autodelno, ''); // 進行清除動作
		return $r;
	}

	/* 重新整理記錄檔內容 (同步進行刪除及新增動作) */
	function _arrangeRecord($fname, $arrDel, $newline){
		$line = is_file($fname) ? file($fname) : array();
		if(is_array($arrDel)) foreach($arrDel as $delid) unset($line[$delid]); // 刪除
		$line = implode('', $line).$newline;
		$fp = fopen($fname, 'w');
		fwrite($fp, $line);
		fclose($fp);
	}

	/* 在前端管理顯示 Hostname */
	function _showHostString(&$arrLabels, $post, $isReply){
		$arrLabels['{$NOW}'] .= " <u>{$post['host']}</u>";
	}

	/* 封鎖黑名單管理頁面插入CSS & JS */
	function _hookHeadCSS(&$style, $isReply){
		$style .= '<style type="text/css">
.dos_list_short {
	height: 150px;
	width: 800px;
	overflow: auto;
	background: #e9f5ff;
	border: 1px solid #666;
}
</style>
<script type="text/javascript">
// <![CDATA[
function add(form){
	var op = form.operate.value, ndata = form.newdata.value, nperiod = form.newperiod.value, ndesc = form.newdesc.value;
	$.post("'.str_replace('&amp;', '&', $this->mypage).'", {operate: op, newdata: ndata, newperiod: nperiod, newdesc: ndesc, ajax: true}, function(d){
		var l, lastno = (l = $("input:checkbox:last", form).get(0)) ? parseInt(l.value) + 1 : 0;
		$("table", form).append(d.replace("#NO#", lastno));
		form.newdata.value = form.newdesc.value = "";
	});
	return false;
}
// ]]>
</script>
';
	}

	function autoHookRegistBegin(){
		global $BANPATTERN, $BAD_FILEMD5;
		// 載入封鎖黑名單定義檔
		if(is_file($this->ipfile)) $BANPATTERN = array_merge($BANPATTERN, array_map('rtrim', $this->_parseBlackListFile($this->ipfile, true)));
		if(is_file($this->imgfile)) $BAD_FILEMD5 = array_merge($BAD_FILEMD5, array_map('rtrim', $this->_parseBlackListFile($this->imgfile, true)));
	}

	function autoHookAdminFunction($action, &$param, $funcLabel, &$message){
		global $PIO, $PMS;
		if($action=='add'){
			// Manual hook: showing hostname of users
			$PMS->hookModuleMethod('ThreadPost', array(&$this, '_showHostString'));
			$PMS->hookModuleMethod('ThreadReply', array(&$this, '_showHostString'));

			$param[] = array('mod_adminenhance_thstop', 'AE: 停止/恢復討論串');
			$param[] = array('mod_adminenhance_banip', 'AE: IP 加到黑名單 (鎖 Class C)');
			$param[] = array('mod_adminenhance_banimg', 'AE: 圖檔 MD5 加到黑名單');
			return;
		}

		switch($funcLabel){
			case 'mod_adminenhance_thstop':
				$infectThreads = array();
				foreach($PIO->fetchPosts($param) as $th){
					if($th['resto']) continue; // 是回應
					$infectThreads[] = $th['no'];
					$flgh = $PIO->getPostStatus($th['status']);
					$flgh->toggle('TS');
					$PIO->setPostStatus($th['no'], $flgh->toString());
				}
				$PIO->dbCommit();
				$message .= '停止/恢復討論串 (No.'.implode(', ', $infectThreads).') 完成<br />';
				break;
			case 'mod_adminenhance_banip':
				$fp = fopen($this->ipfile, 'a');
				foreach($PIO->fetchPosts($param) as $th){
					if(($IPaddr = gethostbyname($th['host'])) != $th['host']) $IPaddr .= '/24';
					fwrite($fp, $IPaddr."\t\t".time()."\t0\n");
				}
				fclose($fp);
				$message .= 'IP 黑名單更新完成<br />';
				break;
			case 'mod_adminenhance_banimg':
				$fp = fopen($this->imgfile, 'a');
				foreach($PIO->fetchPosts($param) as $th){
					if($th['md5chksum']) fwrite($fp, $th['md5chksum']."\n");
				}
				fclose($fp);
				$message .= '圖檔黑名單更新完成<br />';
				break;
			default:
		}
	}

	function autoHookLinksAboveBar(&$link, $pageId, $addinfo=false){
		if($pageId == 'admin' && $addinfo == true)
			$link .= '[<a href="'.$this->mypage.'">封鎖黑名單管理</a>]';
	}

	function ModulePage(){
		global $PMS;
		if(!adminAuthenticate('check')) die('[Error] Access Denied.');

		// 進行新增、刪除等動作
		if(isset($_POST['operate'])){
			$op = $_POST['operate'];
			// 新增資料
			$ndata = isset($_POST['newdata']) ? (get_magic_quotes_gpc() ? stripslashes($_POST['newdata']) : $_POST['newdata']) : ''; // 資料內容
			$nperiod = isset($_POST['newperiod']) ? intval($_POST['newperiod']) : 0; // 封鎖天數
			$ndesc = isset($_POST['newdesc']) ? CleanStr($_POST['newdesc']) : ''; // 註解
			// 刪除資料
			$del = isset($_POST['del']) ? $_POST['del'] : null;
			$newline = '';
			$ismodified = ($ndata != '' || $del != null); // 是否需要修改檔案內容
			if($ismodified){
				switch($op){
					case 'ip':
						$file = $this->ipfile;
						if($ndata != '') $newline = $ndata."\t".$ndesc."\t".time()."\t".$nperiod."\n";
						break;
					case 'img':
						$file = $this->imgfile;
						if($ndata != '') $newline = $ndata."\t".$ndesc."\n";
						break;
				}
				$this->_arrangeRecord($file, $del, $newline); // 同步進行刪除及更新
			}
			if(isset($_POST['ajax'])){ // AJAX 要求在此即停止，一般要求則繼續印出頁面
				$extend = ($op=='ip') ? '<td>'.date('Y/m/d H:m:s', time())." ($nperiod)</td>" : ''; // IP黑名單資訊比圖檔多
				echo '<tr><td>'.htmlspecialchars($ndata).'</td><td>'.$ndesc.'</td>'.$extend.'<td><input type="checkbox" name="del[]" value="#NO#" /></td></tr>';
				return;
			}
		}

		$dat = '';
		$PMS->hookModuleMethod('Head', array(&$this, '_hookHeadCSS'));
		head($dat);
		$dat .= '<div class="bar_admin">封鎖黑名單管理</div>
<div id="content">
<form action="'.$this->mypage.'" method="post">
<div id="ipconfig"><input type="hidden" name="operate" value="ip" />
IP 黑名單<br />
Pattern: <input type="text" name="newdata" size="30" />
Period: <input type="text" name="newperiod" size="5" value="0" />Day(s)
Desc: <input type="text" name="newdesc" size="30" />
<input type="submit" value="新增" onclick="return add(this.form);" /><br />
<div class="dos_list_short">
<table border="0" width="100%">
<tr><td>Pattern</td><td>Description</td><td>Add Date (Period)</td><td>Delete</td></tr>
';
		foreach($this->_parseBlackListFile($this->ipfile) as $i => $l){
			$dat .= '<tr><td>'.htmlspecialchars($l[0]).'</td><td>'.(isset($l[1]) ? $l[1] : '').'</td>'.
			'<td>'.(isset($l[2]) ? date('Y/m/d H:m:s', $l[2]) : '-').(isset($l[3]) ? ' ('.$l[3].')' : ' (0)').'</td>'.
			'<td><input type="checkbox" name="del[]" value="'.$i.'" /></td></tr>'."\n";
		}
		$dat .= '</table>
</div>
<input type="submit" value="刪除" />
</div>
</form>

<form action="'.$this->mypage.'" method="post">
<div id="imgconfig"><input type="hidden" name="operate" value="img" />
圖檔 MD5 黑名單<br />
MD5: <input type="text" name="newdata" size="30" />
Desc: <input type="text" name="newdesc" size="30" />
<input type="hidden" name="newperiod" value="0" />
<input type="submit" value="新增" onclick="return add(this.form);" /><br />
<div class="dos_list_short">
<table border="0" width="100%">
<tr><td>MD5</td><td>Description</td><td>Delete</td></tr>
';
		foreach($this->_parseBlackListFile($this->imgfile) as $i => $l){
			$dat .= '<tr><td>'.htmlspecialchars($l[0]).'</td><td>'.(isset($l[1]) ? $l[1] : '').'</td><td><input type="checkbox" name="del[]" value="'.$i.'" /></td></tr>'."\n";
		}
		$dat .= '</table>
</div>
<input type="submit" value="刪除" />
</div>
</form>
<hr />
<div id="help"><pre>
說明

Pattern:

可封鎖特定IP/Hostname發文。以使用者的IP位置或Host名稱進行判斷，所以兩種形式都可以使用。
例如 127.0.0.1 (IP) 和 localhost (Host) 代表的都是相同的電腦。
接受下列格式

- 完全相符
即是完全一模一樣的情況下才封鎖。
範例:
127.0.0.1 (127.0.0.1 Ｏ；127.0.0.2 Ｘ)
localhost (localhost Ｏ；local Ｘ)

- 萬用字元
可接受 * , ? 來代替一段未知的字元 (如同大家熟知的使用方式)，這樣一來可匹配的情況將增加。
範例:
192.168.0.* (192.168.0.3 Ｏ；192.168.1.3 Ｘ)
local* (localhost Ｏ；remotehost Ｘ)

- 正規表達式
使用Regular Expression來進行匹配，可作出更多樣、更適合的條件。注意使用時需要使用 / 斜線將表達式括住。
範例:
/127\.0\.0\.[0-9]{2}/ (127.0.0.28 Ｏ；127.0.0.1 Ｘ)
/^.+\.proxy\.com$/ (gate1.proxy.com Ｏ；proxy2.com.tw Ｘ)

- CIDR Notation
使用 Classless Addressing 這種更有彈性的方式切割子網路，其表示法稱作 CIDR，以一段IP位置加上Mask來劃分子網路 (注意此表示法僅能使用 IP)。
範例:
192.168.0.1/20 (192.168.7.243 Ｏ；192.168.18.144 Ｘ)

Period:

設定封鎖期限，在過期時可以自動刪除解鎖，以天為單位。如果想永久封鎖 (系統不自動回收，需手動解鎖) 則將此值設為 0 (0 表示無期限)。</pre>
</div>
</div>';
		foot($dat);
		echo $dat;
	}
}
?>