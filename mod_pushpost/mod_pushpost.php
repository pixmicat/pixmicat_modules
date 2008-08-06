<?php
class mod_pushpost{
	var $mypage;

	function mod_pushpost(){
		global $PMS;
		$PMS->hookModuleMethod('ModulePage', __CLASS__); // 向系統登記模組專屬獨立頁面
		$this->mypage = $PMS->getModulePageURL(__CLASS__);
		$this->PUSHPOST_SEPARATOR = '[MOD_PUSHPOST_USE]';
		AttachLanguage(array($this, '_loadLanguage')); // 載入語言檔
	}

	/* Get the name of module */
	function getModuleName(){
		return 'mod_pushpost : 文章推文機制';
	}

	/* Get the module version infomation */
	function getModuleVersionInfo(){
		return '4th.Release.3-dev (v080806)';
	}

	function autoHookHead(&$txt, $isReply){
		global $language;
		$txt .= '<style>.pushpost { background-color: #fff; font-size: 0.8em; padding: 10px; }</style>
<script type="text/javascript">
// <![CDATA[
function mod_pushpostShow(pid){
	$g("mod_pushpostID").value = pid;
	$("div#mod_pushpostBOX").insertBefore($("div#r"+pid+">.quote")).show();
	return false;
}
function mod_pushpostSend(){
	var o0 = $g("mod_pushpostID"), o1 = $g("mod_pushpostName"), o2 = $g("mod_pushpostComm");
	if(o2.value==""){ alert("'._T('modpushpost_nocomment').'"); return false; }
	$.post("'.str_replace('&amp;', '&', $this->mypage).'&no="+o0.value, {ajaxmode: true, name: o1.value, comm: o2.value}, function(rv){
		if(rv.substr(0, 4)!="+OK "){ alert("'._T('modpushpost_failure').'"); return false; }
		rv = rv.substr(4);
		$("div#r"+o0.value+">.quote>.pushpost").append("<br />"+rv);
		o0.value = o1.value = o2.value = "";
		$("div#mod_pushpostBOX").hide();
	});
}
// ]]>
</script>';
	}

	function autoHookFoot(&$foot){
		global $language;
		$foot .= '
<div id="mod_pushpostBOX" style="display:none">
<input type="hidden" id="mod_pushpostID" />'._T('modpushpost_pushpost').' <ul><li>'._T('form_name').' <input type="text" id="mod_pushpostName" /></li><li>'._T('form_comment').' <input type="text" id="mod_pushpostComm" size="50" maxlength="50" /><input type="button" value="'._T('form_submit_btn').'" onclick="mod_pushpostSend()" /></li></ul>
</div>
';
	}

	function autoHookThreadPost(&$arrLabels, $post, $isReply){
		global $language;
		$arrLabels['{$QUOTEBTN}'] .= '&nbsp;<a href="'.$this->mypage.'&amp;no='.$post['no'].'" onclick="return mod_pushpostShow('.$post['no'].')">'._T('modpushpost_pushbutton').'</a>';
		if(strpos($arrLabels['{$COM}'], $this->PUSHPOST_SEPARATOR.'<br />') !== false){
			$arrLabels['{$COM}'] = str_replace($this->PUSHPOST_SEPARATOR.'<br />', '<div class="pushpost">', $arrLabels['{$COM}']).'</div>';
		}
	}

	function autoHookThreadReply(&$arrLabels, $post, $isReply){
		$this->autoHookThreadPost($arrLabels, $post, $isReply);
	}

	function autoHookRegistBegin(&$name, &$email, &$sub, &$com, &$upfileInfo, $accessInfo, $isReply){
		if(strpos($com, $this->PUSHPOST_SEPARATOR."\r\n") !== false){ // 防止不正常的插入標籤形式
			$com = str_replace($this->PUSHPOST_SEPARATOR."\r\n", "\r\n", $com);
		}
	}

	function ModulePage(){
		global $PIO, $PTE, $language;
		if(!isset($_GET['no'])) die('[Error] not enough parameter.');
		if(!isset($_POST['comm'])){
			$post = $PIO->fetchPosts($_GET['no']);
			if(!count($post)) die('[Error] Post does not exist.');

			$dat = $PTE->ParseBlock('HEADER', array('{$TITLE}'=>TITLE, '{$RESTO}'=>''));
			$dat .= '</head><body id="main">';
			$dat .= '<form action="'.$this->mypage.'&amp;no='.$_GET['no'].'" method="POST">
'._T('modpushpost_pushpost').' <ul><li>'._T('form_name').' <input type="text" name="name" /></li><li>'._T('form_comment').' <input type="text" name="comm" size="50" maxlength="50" /><input type="submit" value="'._T('form_submit_btn').'" /></li></ul>
</form>';
			echo $dat, '</body></html>';
		}else{
			if($_SERVER['REQUEST_METHOD'] != 'POST') error(_T('regist_notpost'));
			$name = CleanStr($_POST['name']); $comm = CleanStr($_POST['comm']);
			if(strlen($comm) > 160) error(_T('modpushhpost_maxlength'));
			$name = str_replace(_T('trip_pre'), _T('trip_pre_fake'), $name);
			$pushtime = gmdate('y/m/d H:i', time() + intval(TIME_ZONE) * 3600);
			if(preg_match('/(.*?)[#＃](.*)/u', $name, $regs)){
				$cap = strtr($regs[2], array('&amp;'=>'&'));
				$salt = strtr(preg_replace('/[^\.-z]/', '.', substr($cap.'H.', 1, 2)), ':;<=>?@[\\]^_`', 'ABCDEFGabcdef');
				$name = $regs[1]._T('trip_pre').substr(crypt($cap, $salt), -10);
			}
			if(!$name || ereg("^[ |　|]*$", $name)){
				if(ALLOW_NONAME) $name = DEFAULT_NONAME;
				else error(_T('regist_withoutname'));
			}
			$pushpost = $name.': '.$comm.' ('.$pushtime.')'; // 推文主體

			$post = $PIO->fetchPosts($_GET['no']);
			if(!count($post)) die('[Error] Post does not exist.'); // 被推之文章不存在

			$parentNo = $post[0]['resto'] ? $post[0]['resto'] : $post[0]['no'];
			$threads = array_flip($PIO->fetchThreadList());
			$threadPage = floor($threads[$parentNo] / PAGE_DEF);

			$p = ($parentNo==$post[0]['no']) ? $post : $PIO->fetchPosts($parentNo); // 取出首篇
			$flgh = $PIO->getPostStatus($p[0]['status']);
			if($flgh->exists('TS')) die('[Error] '._T('regist_threadlocked')); // 首篇禁止回應/同時表示禁止推文

			$post[0]['com'] .= ((strpos($post[0]['com'], $this->PUSHPOST_SEPARATOR)===false) ? $this->PUSHPOST_SEPARATOR : '').'<br />'.$pushpost;
			$PIO->updatePost($_GET['no'], array('com'=>$post[0]['com']));
			$PIO->dbCommit();
			if(STATIC_HTML_UNTIL == -1 || $threadPage <= STATIC_HTML_UNTIL) updatelog(0, $threadPage, true); // 僅更新討論串出現那頁
			deleteCache(array($parentNo)); // 刪除討論串舊快取

			if(isset($_POST['ajaxmode'])){
				echo '+OK ', $pushpost;
			}else{
				header('HTTP/1.1 302 Moved Temporarily');
				header('Location: '.fullURL().PHP_SELF2.'?'.time());
			}
		}
	}

	function _loadLanguage(){
		global $language;
		if(PIXMICAT_LANGUAGE != 'zh_TW' && PIXMICAT_LANGUAGE != 'ja_JP' && PIXMICAT_LANGUAGE != 'en_US') $lang = 'en_US';
		else $lang = PIXMICAT_LANGUAGE;

		if($lang=='zh_TW'){
			$language['modpushpost_nocomment'] = '請輸入內文';
			$language['modpushpost_failure'] = '推文失敗，請檢查網路連線或輸入內容是否超過上限';
			$language['modpushpost_pushpost'] = '[推文]';
			$language['modpushpost_pushbutton'] = '推';
			$language['modpushpost_maxlength'] = '你話太多了';
		}elseif($lang=='ja_JP'){
			$language['modpushpost_nocomment'] = '何か書いて下さい';
			$language['modpushpost_failure'] = '失敗しました';
			$language['modpushpost_pushpost'] = '[推文]';
			$language['modpushpost_pushbutton'] = '推';
			$language['modpushpost_maxlength'] = 'コメントが長すぎます';
		}elseif($lang=='en_US'){
			$language['modpushpost_nocomment'] = 'Please type your comment.';
			$language['modpushpost_failure'] = 'Push this post failed. Check your Internet connection or if your length of comment reached the limit.';
			$language['modpushpost_pushpost'] = '[Push this post]';
			$language['modpushpost_pushbutton'] = 'PUSH';
			$language['modpushpost_maxlength'] = 'You typed too many words';
		}
	}
}
?>