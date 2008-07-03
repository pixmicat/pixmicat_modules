<?php
class mod_edit{
	var $mypage;
	var $shown_in_page;

	function mod_edit(){
		global $PMS;
		$PMS->hookModuleMethod('ModulePage', __CLASS__); // 向系統登記模組專屬獨立頁面
		$this->mypage = $PMS->getModulePageURL(__CLASS__);
		$this->shown_in_page = false; // 是否顯示編輯功能於前端頁面供使用者自行修改
	}

	/* Get the name of module */
	function getModuleName(){
		return 'mod_edit : 文章編輯功能';
	}

	/* Get the module version infomation */
	function getModuleVersionInfo(){
		return '4th.Release.3 (v080519)';
	}

	function autoHookAdminList(&$modFunc, $post, $isres){
		$modFunc .= '[<a href="'.$this->mypage.'&amp;no='.$post['no'].'" title="Edit">E</a>]';
	}

	function autoHookThreadPost(&$arrLabels, $post, $isReply){
		if($this->shown_in_page) $arrLabels['{$REPLYBTN}'] .= ' [<a href="'.$this->mypage.'&amp;no='.$post['no'].'">編輯</a>]';
	}

	function autoHookThreadReply(&$arrLabels, $post, $isReply){
		if($this->shown_in_page) $arrLabels['{$QUOTEBTN}'] .= ' [<a href="'.$this->mypage.'&amp;no='.$post['no'].'">編輯</a>]';
	}

	function _EditPostInfo(&$txt){
		$txt = '<li><span style="font-size:110%;font-weight:bold;">不用更換的欄位請留空。</span></li>'.$txt;
	}

	function ModulePage(){
		global $PIO, $FileIO, $PMS, $language, $BAD_STRING, $BAD_FILEMD5, $BAD_IPADDR, $LIMIT_SENSOR;

		if(!isset($_GET['no'])) die('[Error] not enough parameter.');
		if(!isset($_POST['mode'])){ // 顯示表單
			if(!$this->shown_in_page && !adminAuthenticate('check')) die('[Error] Access Denied.');

			$post = $PIO->fetchPosts($_GET['no']);
			if(!count($post)) die('[Error] Post does not exist.');
			extract($post[0]);
			$PMS->loadModules('mod_bbcode'); //嘗試載入mod_bbcode
			if($bbcode=$PMS->getModuleInstance('mod_bbcode')) $bbcode->_html2bb($com);
			$name=preg_replace('|<span.*?>(.*?)</span>|','\1',$name);
			$dat='';
			head($dat);
			$PMS->hookModuleMethod('PostInfo', array($this,'_EditPostInfo'));
			form($dat, $resto, false, $this->mypage.'&amp;no='.$_GET['no'], $name, $email, $sub, str_replace('<br />', "\n", $com), substr(str_replace('&#44;', ',', $category),1,-1), 'edit');
			foot($dat);
			echo $dat;
		} else { // 儲存
			if($_SERVER['REQUEST_METHOD'] != 'POST') error(_T('regist_notpost')); // 非正規POST方式
			$post = $PIO->fetchPosts($_GET['no']);
			$newValues = array();

			if(!count($post)) die('[Error] Post does not exist.');

			$name = isset($_POST[FT_NAME]) ? $_POST[FT_NAME] : '';
			$email = isset($_POST[FT_EMAIL]) ? $_POST[FT_EMAIL] : '';
			$sub = isset($_POST[FT_SUBJECT]) ? $_POST[FT_SUBJECT] : '';
			$com = isset($_POST[FT_COMMENT]) ? $_POST[FT_COMMENT] : '';
			$pwd = isset($_POST['pwd']) ? $_POST['pwd'] : '';
			$category = isset($_POST['category']) ? $_POST['category'] : '';
			$resto = isset($_POST['resto']) ? $_POST['resto'] : 0;
			$upfile = '';
			$upfile_path = '';
			$upfile_name = false;
			$upfile_status = 4;
			$pwdc = isset($_COOKIE['pwdc']) ? $_COOKIE['pwdc'] : '';

			if($resto && !$PIO->isThread($resto)) die('[Error] Thread was deleted.');
			$is_admin = $haveperm = ($pwd==ADMIN_PASS) || adminAuthenticate('check');
			$PMS->useModuleMethods('Authenticate', array($pwd,'useredit',&$haveperm));

			if($pwd=='' && $pwdc!='') $pwd = $pwdc;
			$pwd_md5 = substr(md5($pwd),2,8);
			$host = gethostbyaddr(getREMOTE_ADDR());
			if(!($pwd_md5==$post[0]['pwd'] || $host==$post[0]['host'] || $haveperm)) die('[Error] Access denied.');

			// 欄位陷阱
			$FTname = isset($_POST['name']) ? $_POST['name'] : '';
			$FTemail = isset($_POST['email']) ? $_POST['email'] : '';
			$FTsub = isset($_POST['sub']) ? $_POST['sub'] : '';
			$FTcom = isset($_POST['com']) ? $_POST['com'] : '';
			$FTreply = isset($_POST['reply']) ? $_POST['reply'] : '';
			if($FTname != 'spammer' || $FTemail != 'foo@foo.bar' || $FTsub != 'DO NOT FIX THIS' || $FTcom != 'EID OG SMAPS' || $FTreply != '') error(_T('regist_nospam'));

			// 封鎖：IP/Hostname/DNSBL 檢查機能
			$ip = getREMOTE_ADDR(); $host = gethostbyaddr($ip); $baninfo = '';
			if(BanIPHostDNSBLCheck($ip, $host, $baninfo)) error(_T('regist_ipfiltered', $baninfo));
			// 封鎖：限制出現之文字
			foreach($BAD_STRING as $value){
				if(strpos($com, $value)!==false || strpos($sub, $value)!==false || strpos($name, $value)!==false || strpos($email, $value)!==false){
					error(_T('regist_wordfiltered'));
				}
			}
			$PMS->useModuleMethods('RegistBegin', array(&$name, &$email, &$sub, &$com, array('file'=>&$upfile, 'path'=>&$upfile_path, 'name'=>&$upfile_name, 'status'=>&$upfile_status), array('ip'=>$ip, 'host'=>$host))); // "RegistBegin" Hook Point

			// 檢查是否輸入櫻花日文假名
			$chkanti = array($name, $email, $sub, $com);
			foreach($chkanti as $anti) if(anti_sakura($anti)) error(_T('regist_sakuradetected'));

			// 檢查表單欄位內容並修整
			if(strlen($name) > 100) error(_T('regist_nametoolong'));
			if(strlen($email) > 100) error(_T('regist_emailtoolong'));
			if(strlen($sub) > 100) error(_T('regist_topictoolong'));
			if(strlen($resto) > 10) error(_T('regist_longthreadnum'));

			$email = CleanStr($email); $email = str_replace("\r\n", '', $email);
			$sub = CleanStr($sub); $sub = str_replace("\r\n", '', $sub);
			$resto = CleanStr($resto); $resto = str_replace("\r\n", '', $resto);
			// 名稱修整
			$name = CleanStr($name);
			$name = str_replace(_T('trip_pre'), _T('trip_pre_fake'), $name); // 防止トリップ偽造
			$name = str_replace(CAP_SUFFIX, _T('cap_char_fake'), $name); // 防止管理員キャップ偽造
			$name = str_replace("\r\n", '', $name);
			$nameOri = $name; // 名稱
			if(preg_match('/(.*?)[#＃](.*)/u', $name, $regs)){ // トリップ(Trip)機能
				$name = $nameOri = $regs[1]; $cap = strtr($regs[2], array('&amp;'=>'&'));
				$salt = preg_replace('/[^\.-z]/', '.', substr($cap.'H.', 1, 2));
				$salt = strtr($salt, ':;<=>?@[\\]^_`', 'ABCDEFGabcdef');
				$name = $name._T('trip_pre').substr(crypt($cap, $salt), -10);
			}
			if(CAP_ENABLE && preg_match('/(.*?)[#＃](.*)/', $email, $aregs)){ // 管理員キャップ(Cap)機能
				$acap_name = $nameOri; $acap_pwd = strtr($aregs[2], array('&amp;'=>'&'));
				if($acap_name==CAP_NAME && $acap_pwd==CAP_PASS){
					$name = '<span class="admin_cap">'.$name.CAP_SUFFIX.'</span>';
					$is_admin = true;
					$email = $aregs[1]; // 去除 #xx 密碼
				}
			}
			if(!$is_admin){ // 非管理員
				$name = str_replace(_T('admin'), '"'._T('admin').'"', $name);
				$name = str_replace(_T('deletor'), '"'._T('deletor').'"', $name);
			}
			$name = str_replace('&◆', '&amp;◆', $name); // 避免 &#xxxx; 後面被視為 Trip 留下 & 造成解析錯誤
			// 內文修整
			if((strlen($com) > COMM_MAX) && !$is_admin) error(_T('regist_commenttoolong'));
			$com = CleanStr($com, $is_admin); // 引入$is_admin參數是因為當管理員キャップ啟動時，允許管理員依config設定是否使用HTML
			$com = str_replace("\r\n","\n", $com);
			$com = str_replace("\r","\n", $com);
			$com = ereg_replace("\n((　| )*\n){3,}", "\n", $com);
			if(!BR_CHECK || substr_count($com,"\n") < BR_CHECK) $com = nl2br($com); // 換行字元用<br />代替
			$com = str_replace("\n",'', $com); // 若還有\n換行字元則取消換行
			if($category && USE_CATEGORY){ // 修整標籤樣式
				$category = explode(',', $category); // 把標籤拆成陣列
				$category = '&#44;'.implode('&#44;', array_map('trim', $category)).'&#44;'; // 去空白再合併為單一字串 (左右含,便可以直接以,XX,形式搜尋)
			}else{ $category = ''; }

			$age = false; $dest = '';
			$W = $post[0]['tw']; $H = $post[0]['th']; $imgW = $post[0]['imgw']; $imgH = $post[0]['imgh']; $status = $post[0]['status'];
			$PMS->useModuleMethods('RegistBeforeCommit', array(&$name, &$email, &$sub, &$com, &$category, &$age, $dest, $resto, array($W, $H, $imgW, $imgH), &$status)); // "RegistBeforeCommit" Hook Point

			if($name != $post[0]['name'] && $_POST[FT_NAME]) $newValues['name'] = $name;
			if($email != $post[0]['email'] && $_POST[FT_EMAIL]) $newValues['email'] = $email;
			if($sub != $post[0]['sub'] && $_POST[FT_SUBJECT]) $newValues['sub'] = $sub;
			if($com != $post[0]['com'] && $_POST[FT_COMMENT]) $newValues['com'] = $com;
			if($category != $post[0]['category'] && $_POST['category']) $newValues['category'] = $category;

			$PIO->updatePost($_GET['no'], $newValues);
			$PIO->dbCommit();

			$parentNo = $post[0]['resto'] ? $post[0]['resto'] : $post[0]['no'];
			$threads = array_flip($PIO->fetchThreadList());
			$threadPage = floor($threads[$parentNo] / PAGE_DEF);
			if(STATIC_HTML_UNTIL == -1 || $threadPage <= STATIC_HTML_UNTIL) updatelog(0, $threadPage, true); // 僅更新討論串出現那頁
			deleteCache(array($parentNo)); // 刪除討論串舊快取

			header('HTTP/1.1 302 Moved Temporarily');
			header('Location: '.fullURL().PHP_SELF2.'?'.time());
		}
	}
}
?>