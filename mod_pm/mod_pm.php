<?php
/* mod_pm : Personal Messages for Trips (Pre-Alpha)
 * $Id$
 */
class mod_pm{
	var $MESG_LOG,$MESG_CACHE;
	var $myPage,$trips,$lastno;
	
	function mod_pm(){
		global $PMS, $PIO, $FileIO;

		$PMS->hookModuleMethod('ModulePage', 'mod_pm'); // 向系統登記模組專屬獨立頁面
		$this->myPage = $PMS->getModulePageURL('mod_pm'); // 基底位置
		$this->trips = array();

		$this->MESG_LOG = './tripmesg.log'; // PM紀錄檔位置
		$this->MESG_CACHE = './tripmesg.cc'; // PM快取檔位置
	}

	function getModuleName(){
		return 'mod_pm';
	}

	function getModuleVersionInfo(){
		return 'mod_pm : Personal Messages for Trip (Pre-Alpha)';
	}

	/* 自動掛載：頂部連結列 */
	function autoHookToplink(&$linkbar, $isReply){
		$linkbar .= '[<a href="'.$this->myPage.'">收件箱</a>] [<a href="'.$this->myPage.'&amp;action=write">發PM</a>]'."\n";
	}

	function autoHookThreadPost(&$arrLabels, $post, $isReply){
		if(preg_match('|(<a.*">)?(.*)<span class\="nor">'._T('trip_pre').'(.{10})</span>(<span.*</span>)(</a>)?|', $arrLabels['{$NAME}'], $matches)) {
			if($matches[3]) { // Trip found
				if($matches[2]) { // has name
					$arrLabels['{$NAME}']=$matches[1].$matches[2].($matches[1]?'</a>':'').'<span class="nor"><a href="'.$this->myPage.'&amp;action=write&amp;t='.$matches[3].'" style="text-decoration: overline underline" title="PM">'._T('trip_pre').'</a>'.$matches[1].$matches[3].($matches[1]?'</a>':'')."</span>".$matches[4];
				} else {
					$arrLabels['{$NAME}']='<span class="nor"><a href="'.$this->myPage.'&amp;action=write&amp;t='.$matches[3].'" style="text-decoration: overline underline" title="PM">'._T('trip_pre').'</a>'.$matches[1].$matches[3].($matches[1]?'</a>':'')."</span>".$matches[4];
				}
			}
		}
	}

	function autoHookThreadReply(&$arrLabels, $post, $isReply){
		$this->autoHookThreadPost($arrLabels, $post, $isReply);
	}

	function _tripping($str) {
		$salt = preg_replace('/[^\.-z]/', '.', substr($str.'H.', 1, 2));
		$salt = strtr($salt, ':;<=>?@[\\]^_`', 'ABCDEFGabcdef');
		return substr(crypt($str, $salt), -10);
	}

	function _latestPM() {
		$htm = '<table style="border:3pt outset white;background:#efefef" cellpadding="0" cellspacing="0">
<tr style="background:#000033;color:white"><td align="center">
<b>◆10日以内的投函一覧◆</b></td></tr>
<tr><td>
<div style="width:300;height:200;overflow-y:scroll">
<table style="width:100%;font-size:9pt">
<tr style="background:#005500;color:white">
<th>到着時間</th>
<th>Trip</th>
<th>信息數</th></tr>';
		$this->_loadCache();

		foreach($this->trips as $t => $v) { //d=last update date, c=count
			if($v['d']<time()-864000) break; // out of range (10 days)
			$htm.='<tr><td>'.date('Y-m-d H:i:s',$v['d']).($v['d']>time()-86400?' <span style="font-size:0.8em;color:#f44;">(new!)</span>':'').'</td><td class="name">'._T('trip_pre').substr($t,0,5)."...</td><td align='center'>$v[c] "._T('info_basic_threads')."</td></tr>";
		}
		return $htm.'</table></div></td></tr></table>';
	}

	function _loadCache() {
		if(!$this->trips) {
			if($logs=@file($this->MESG_CACHE)) { // 有快取
				$this->lastno=trim($logs[0]);
				$this->trips=unserialize($logs[1]);
				return true;
			} else { // 無快取
				return $this->_rebuildCache();
			}
		} else return true;
	}

	function _rebuildCache() {
		$this->trips = array();
		if($logs=@file($this->MESG_LOG)) { // mesgno,trip,date,from,topic,mesg = each $logs, order desc
			if(!$this->lastno) if(isset($logs[0])) $this->lastno = intval(substr($logs[0],strpos($logs[0],','))); // last no
			foreach($logs as $log) {
				list($mno,$trip,$pdate,)=explode(',',trim($log));
				if(isset($this->trips[$trip])) {
					$this->trips[$trip]['c']++;
//					if($this->trips[$trip]['d']<$pdate) $this->trips[$trip]['d'] = $pdate;
				} else {
					$this->trips[$trip]=array('c'=>1,'d'=>$pdate);
				}
			}

			// Sort in order
			foreach ($this->trips as $key => $row) {
			    $c[$key] = $row['c'];
			    $d[$key] = $row['d'];
			}
			array_multisort($d, SORT_DESC, $c, SORT_ASC, $this->trips);

			$this->_writeCache();

			return true;
		} else {
			$this->_writeCache();
			return false;
		}
	}

	function _writeCache() {
		$this->_write($this->MESG_CACHE,$this->lastno."\n".serialize($this->trips));
	}

	function _write($file,$data) {
		$rp = fopen($file, "w");
		flock($rp, LOCK_EX); // 鎖定檔案
		@fputs($rp,$data);
		flock($rp, LOCK_UN); // 解鎖
		fclose($rp);
		chmod($file,0666);
	}

	function _postPM($from,$to,$topic,$mesg) {
		if(!preg_match('/^[0-9a-zA-Z\.\/]{10}$/',$to)) error("Trip有誤");
		$from=CleanStr($from); $to=CleanStr($to); $topic=CleanStr($topic); $mesg=CleanStr($mesg);
		if(!$from) if(ALLOW_NONAME) $from = DEFAULT_NONAME;
		if(!$topic)  $topic = DEFAULT_NOTITLE;
		if(!$mesg) error("請填入內文");
		if(preg_match('/(.*?)[#＃](.*)/u', $from, $regs)){ // トリップ(Trip)機能
			$from = $nameOri = $regs[1]; $cap = strtr($regs[2], array('&amp;'=>'&'));
			$from = $from.'<span class="nor">'._T('trip_pre').$this->_tripping($cap)."</span>";
		}
		$from = str_replace(_T('admin'), '"'._T('admin').'"', $from);
		$from = str_replace(_T('deletor'), '"'._T('deletor').'"', $from);
		$from = str_replace('&'._T('trip_pre'), '&amp;'._T('trip_pre'), $from); // 避免 &#xxxx; 後面被視為 Trip 留下 & 造成解析錯誤
		$mesg = str_replace(',','&#44;',$mesg); // 轉換","
		$mesg = str_replace("\n",'<br/>',$mesg); //nl2br不行

		$this->_loadCache();

		$logs=(++$this->lastno).",$to,".time().",$from,$topic,$mesg,$_SERVER[REMOTE_ADDR],\n".@file_get_contents($this->MESG_LOG);
		$this->_write($this->MESG_LOG,$logs);

		$this->_rebuildCache();
	}

	function _getPM($trip) {
		global $PTE,$PMS;
		$dat='';
		$trip=substr($trip,1);
		$tripped=$this->_tripping($trip);
		
		if($logs=@file($this->MESG_LOG)) { // mesgno,trip,date,from,topic,mesg,ip = each $logs, order desc
			foreach($logs as $log) {
				list($mno,$totrip,$pdate,$from,$topic,$mesg,$ip)=explode(',',trim($log));
				if($totrip==$tripped) {
					if(!$dat) $dat=$PTE->ParseBlock('REALSEPARATE',array()).'<form action="'.$this->myPage.'" method="POST"><input type="hidden" name="action" value="delete" /><input type="hidden" name="trip" value="'.$trip.'" />';
					$arrLabels = array('{$NO}'=>$mno, '{$SUB}'=>$topic, '{$NAME}'=>$from, '{$NOW}'=>date('Y-m-d H:i:s',$pdate)." IP:".preg_replace('/\d+$/','*',$ip), '{$COM}'=>$mesg, '{$QUOTEBTN}'=>"No.$mno", '{$REPLYBTN}'=>'', '{$IMG_BAR}'=>'', '{$IMG_SRC}'=>'', '{$WARN_OLD}'=>'', '{$WARN_BEKILL}'=>'', '{$WARN_ENDREPLY}'=>'', '{$WARN_HIDEPOST}'=>'', '{$NAME_TEXT}'=>_T('post_name'), '{$RESTO}'=>1);
					$PMS->useModuleMethods('ThreadPost', array(&$arrLabels, array(), 0)); // "ThreadPost" Hook Point
					$dat .= $PTE->ParseBlock('THREAD',$arrLabels);
					$dat .= $PTE->ParseBlock('REALSEPARATE',array());
				}
			}
		}
		if(!$dat) $dat="沒有信息。";
		else $dat.='<input type="submit" name="delete" value="'._T('del_btn').'" /></form>';
		return $dat;
	}

	function _deletePM($no,$trip) {
		$tripped=$this->_tripping($trip);
		$found=false;
		if($logs=@file($this->MESG_LOG)) { // mesgno,trip,date,from,topic,mesg = each $logs, order desc
			$countlogs=count($logs);
			foreach($no as $n) {
				for($i=0;$i<$countlogs;$i++) {
					list($mno,$totrip,)=explode(',',$logs[$i]);
					if($totrip==$tripped && $mno==$n) {
						$logs[$i]=''; // deleted
						$found=true;
						break;
					}
				}
			}
			if($found) {
				$newlogs=implode('',$logs);
				$this->_write($this->MESG_LOG,$newlogs);
				$this->_rebuildCache();
			}
		}
	}

	function ModulePage(){
		global $PMS, $PIO, $FileIO;
		$trip=isset($_REQUEST['t'])?$_REQUEST['t']:'';
		$action=isset($_REQUEST['action'])?$_REQUEST['action']:'';
		$dat='';

		if($action != 'postverify') {
			head($dat);
			echo $dat.'[<a href="'.PHP_SELF2.'?'.time().'">'._T('return').'</a>]';
		}
		if($action == 'write') {
			echo '<div class="bar_reply">發送私人信息</div>
<div style="text-align: center;">
<form id="pmform" action="'.$this->myPage.'" method="POST">
<input type="hidden" name="action" value="post" />
<table cellpadding="1" cellspacing="1" id="postform_tbl" style="margin: 0px auto; text-align: left;">
<tr><td class="Form_bg"><b>由</b></td><td><input type="text" name="from" value="" size="28" />(Trip可)</td></tr>
<tr><td class="Form_bg"><b>至</b></td><td>'._T('trip_pre').'<input type="text" name="t" value="'.$trip.'" maxlength="10" size="14" /></td></tr>
<tr><td class="Form_bg"><b>'._T('form_topic').'</b></td><td><input type="text" name="topic" size="28" value="" /></td></tr>
<tr><td class="Form_bg"><b>'._T('form_comment').'</b></td><td><textarea cols="40" rows="5" name="content"></textarea></td></tr>
<tr><td colspan="2" align="right"><input type="submit" name="submit" value="'._T('form_submit_btn').'"/></td></tr>
</table>
</form>
</div>
<script type="text/javascript">
$g("pmform").from.value=getCookie("namec");
</script>
';
		} elseif($action == 'post') {
			echo '<div class="bar_reply">確認送出私人信息</div>
<div style="text-align: center;">
<form id="pmform" action="'.$this->myPage.'" method="POST">
<input type="hidden" name="action" value="postverify" />
<table cellpadding="1" cellspacing="1" id="postform_tbl" style="margin: 0px auto; text-align: left;">
<tr><td colspan="2">請確認將會送出的私人信息。按['._T('form_submit_btn').']繼續。</td></tr>
<tr><td class="Form_bg"><b>由</b></td><td><input type="text" name="from" value="'.$_POST['from'].'" size="28" /></td></tr>
<tr><td class="Form_bg"><b>至</b></td><td>'._T('trip_pre').'<input type="text" name="t" value="'.$_POST['t'].'" maxlength="10" size="14" /></td></tr>
<tr><td class="Form_bg"><b>'._T('form_topic').'</b></td><td><input type="text" name="topic" size="28" value="'.$_POST['topic'].'" /></td></tr>
<tr><td class="Form_bg"><b>'._T('form_comment').'</b></td><td><textarea cols="40" rows="5" name="content">'.$_POST['content'].'</textarea></td></tr>
<tr><td colspan="2" align="right"><input type="submit" name="submit" value="'._T('form_submit_btn').'"/></td></tr>
</table>
</form>
</div>';
		} elseif($action == 'postverify') {
			$this->_postPM($_POST['from'],$_POST['t'],$_POST['topic'],$_POST['content']);
			if(preg_match('/(.*?)[#＃](.*)/u', $_POST['from'], $regs)){ // トリップ(Trip)機能
				$_POST['from'] = $nameOri = $regs[1]; $cap = strtr($regs[2], array('&amp;'=>'&'));
				$_POST['from'] = $_POST['from'].'<span class="nor">'._T('trip_pre').$this->_tripping($cap)."</span>";
			}
			head($dat);
			echo $dat.'[<a href="'.PHP_SELF2.'?'.time().'">'._T('return').'</a>]';
			echo '<div class="bar_reply">已送出私人信息</div>
<table cellpadding="1" cellspacing="1" id="postform_tbl" style="margin-left:1.5em">
<tr><td colspan="2">已送出。</td></tr>
<tr><td class="Form_bg"><b>由</b></td><td class="name">'.$_POST['from'].'</td></tr>
<tr><td class="Form_bg"><b>至</b></td><td>'._T('trip_pre').$_POST['t'].'</td></tr>
<tr><td class="Form_bg"><b>'._T('form_topic').'</b></td><td>'.$_POST['topic'].'</td></tr>
<tr><td class="Form_bg"><b>'._T('form_comment').'</b></td><td><blockquote>'.$_POST['content'].'</blockquote></td></tr>
</table>';
		} else {
			echo '<div class="bar_reply">收件箱</div>';
			if($action == 'delete' && isset($_POST['trip'])) {
				$delno=array();
				while($item = each($_POST)) if($item[1]=='delete') array_push($delno, $item[0]);
				if(count($delno)) $this->_deletePM($delno,$_POST['trip']);
			}
			echo $this->_latestPM();
			echo '【檢查收件箱】<form id="pmform" action="'.$this->myPage.'" method="POST">
<input type="hidden" name="action" value="check" />
<label>Trip:<input type="text" name="trip" value="" size="28" /></label><input type="submit" name="submit" value="'._T('form_submit_btn').'"/>(以"#"為首)
</form>
<script type="text/javascript">
$g("pmform").trip.value=getCookie("namec").replace(/^[^#]*#/,"#");
</script>';
			if($action == 'check' && isset($_POST['trip']) && substr($_POST['trip'],0,1) == '#') echo $this->_getPM($_POST['trip']);

		}
		$dat='';
		foot($dat);
		echo $dat;
	}
}
?>