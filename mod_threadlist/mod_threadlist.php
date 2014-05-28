<?php
class mod_threadlist extends ModuleHelper {
	// 一頁顯示列表個數
	private $THREADLIST_NUMBER = 50;
	// 是否強制開新串要有標題
	private $FORCE_SUBJECT = false;
	// 是否在主頁面顯示
	private $SHOW_IN_MAIN = true;
	// 在主頁面顯示列表個數
	private $THREADLIST_NUMBER_IN_MAIN = 20;
	// 是否顯示刪除表單
	private $SHOW_FORM = true;
	// 熱門回應數，超過這個值回應數會變紅色 (0 為不使用)
	private $HIGHLIGHT_COUNT = 30;

	public function __construct($PMS) {
		parent::__construct($PMS);

		$this->attachLanguage(array(
			'zh_TW' => array(
				'modulename' => '討論串列表',
				'no_title' => '發文不可以沒有標題喔',
				'link' => '主題列表',
				'main_title' => '主題一覽',
				'page_title' => '列表模式',
				'date' => '日期'
			),
			'en_US' => array(
				'modulename' => 'Thread list',
				'no_title' => 'We do NOT accept a post which is no title.',
				'link' => 'Thread List',
				'main_title' => 'Thread overview',
				'page_title' => 'List mode',
				'date' => 'Date'
			)
		), 'en_US');
	}

	public function getModuleName() {
		return $this->moduleNameBuilder($this->_T('modulename'));
	}

	public function getModuleVersionInfo() {
		return '7th.Release.3 (v140528)';
	}

	public function autoHookRegistBeforeCommit(&$name, &$email, &$sub, &$com,
		&$category, &$age, $dest, $isReply, $imgWH, &$status) {
		if ($this->FORCE_SUBJECT && !$isReply && $sub == DEFAULT_NOTITLE) {
			error($this->_T('no_title'), $dest);
		}
	}

	public function autoHookToplink(&$linkbar, $isReply) {
		$linkbar .= '[<a href="'.$this->getModulePageURL().'">'.$this->_T('link').'</a>]'."\n";
	}

	public function autoHookThreadFront(&$txt, $isReply) {
		$PIO = PMCLibrary::getPIOInstance();
		if($this->SHOW_IN_MAIN && !$isReply) {
			$dat = ''; // HTML Buffer
			$plist = $PIO->fetchThreadList(0, $this->THREADLIST_NUMBER_IN_MAIN, true); // 編號由大到小排序
			self::$PMS->useModuleMethods('ThreadOrder', array($isReply, 0, 0, &$plist)); // "ThreadOrder" Hook Point

			$post = $PIO->fetchPosts($plist); // 取出資料
		    $dat .= '<div id="topiclist" style="text-align: center; clear: both;">
<table cellpadding="1" cellspacing="1" border="0" width="100%" style="margin: 0px auto; text-align: left; margin-bottom: 1em; font-size: 1em;">
<tr><th class="reply_hl" colspan="2" style="text-align: center;"><a href="'.$this->getModulePageURL().'">'.$this->_T('main_title').'</a></th></tr>
';
			$post_count = ceil(count($post) / 2);
			for ($i = 0; $i < $post_count; $i++) {
				$leftStr = sprintf('%d: <a href="%s">%s (%d)</a>',
					$post[$i]['no'],
					PHP_SELF.'?res='.$post[$i]['no'],
					$post[$i]['sub'],
					$PIO->postCount($post[$i]['no']) - 1
				);

				if (isset($post[$i + $post_count])) {
					$rightStr = sprintf('%d: <a href="%s">%s (%d)</a>',
						$post[$i + $post_count]['no'],
						PHP_SELF.'?res='.$post[$i + $post_count]['no'],
						$post[$i + $post_count]['sub'],
						$PIO->postCount($post[$i + $post_count]['no']) - 1
					);
				} else {
					$rightStr = '';
				}

				$dat .= '<tr class="ListRow'.($i % 2 + 1).'_bg"><td style="width: 50%">'.
					$leftStr.'</td><td>'.$rightStr.'</td></tr>'."\n";
			}
			$dat .= '</table>
</div>
';
		    $txt .= $dat;
		}
	}

	private function _getPostCounts($posts) {
		$PIO = PMCLibrary::getPIOInstance();

		$pc = array();
		foreach($posts as $post)
			$pc[$post] = $PIO->postCount($post);

		return $pc;
	}

	private function _kasort(&$a, $revkey = false, $revval = false) {
		$t = $u= array();
		foreach ($a as $k => &$v) { // flip array
			if (!isset($t[$v])) $t[$v] = array($k);
			else $t[$v][] = $k;
		}

		if ($revkey) krsort($t);
		else ksort($t);

		foreach ($t as $k=>&$vv) {
			if ($revval) rsort($vv);
			else sort($vv);
		}
		foreach ($t as $k=>&$vv) { // reconstruct array
			foreach ($vv as &$v)
				$u[$v] = $k;
		}
		$a = $u;
	}

	public function ModulePage() {
		$PIO = PMCLibrary::getPIOInstance();
		$thisPage = $this->getModulePageURL(); // 基底位置
		$dat = ''; // HTML Buffer
		$listMax = $PIO->threadCount(); // 討論串總筆數
		$pageMax = ceil($listMax / $this->THREADLIST_NUMBER) - 1; // 分頁最大編號
		$page = isset($_GET['page']) ? intval($_GET['page']) : 0; // 目前所在分頁頁數
		$sort = isset($_GET['sort']) ? $_GET['sort'] : 'no';
		if ($page < 0 || $page > $pageMax) exit('Page out of range.'); // $page 超過範圍

		if (strpos($sort, 'post') !== false) {
			$plist = $PIO->fetchThreadList();
			$pc = $this->_getPostCounts($plist);
			$this->_kasort($pc,$sort == 'postdesc',true);
			// 切出需要的大小
			$plist = array_slice(
				array_keys($pc),
				$this->THREADLIST_NUMBER * $page,
				$this->THREADLIST_NUMBER
			);
		} else {
			$plist = $PIO->fetchThreadList($this->THREADLIST_NUMBER * $page, $this->THREADLIST_NUMBER, $sort == 'date' ? false : true); // 編號由大到小排序
			self::$PMS->useModuleMethods('ThreadOrder', array(0,$page,0,&$plist)); // "ThreadOrder" Hook Point
			$pc = $this->_getPostCounts($plist);
		}
		$post = $PIO->fetchPosts($plist); // 取出資料
		$post_count = count($post);

		if($sort=='date' || strpos($sort, 'post') !== false) { // 要重排次序
			$mypost = array();

			foreach($plist as $p) {
				while (list($k, $v) = each($post)) {
					if($v['no'] == $p) {
						$mypost[] = $v;
					    unset($post[$k]);
					    break;
				    }
				}
				reset($post);
			}
			$post = $mypost;
		}

		head($dat);
		$dat .= '<script>
var selectall = "";
function checkall(){
	selectall = selectall ? "" : "checked";
	var inputs = document.getElementsByTagName("input");
	for(x=0; x < inputs.length; x++){
		if(inputs[x].type == "checkbox" && parseInt(inputs[x].name)) {
			inputs[x].checked = selectall;
		}
	}
}
</script>';
		$dat .= '<div id="contents">
[<a href="'.PHP_SELF2.'?'.time().'">'._T('return').'</a>]
<div class="bar_reply">'.$this->_T('page_title').'</div>'.($this->SHOW_FORM ? '<form action="'.PHP_SELF.'" method="post">' : '').'<table align="center" width="98%"><tr>
'.($this->SHOW_FORM ? '<th><a href="javascript:checkall()">↓</a></th>' : '').'
<th><a href="'.$thisPage.'&amp;sort=no">No.'.($sort == 'no' ? ' ▼' : '').'</a></th>
<th width="48%">'._T('form_topic').'</th>
<th>'._T('form_name').'</th>
<th><a href="'.$thisPage.'&amp;sort='.($sort == 'postdesc' ? 'postasc' : 'postdesc').'">'._T('reply_btn').($sort == 'postdesc' ? ' ▼' : ($sort == 'postasc' ? ' ▲' : '')).'</a></th>
<th><a href="'.$thisPage.'&amp;sort=date">'.$this->_T('date').($sort == 'date' ? ' ▼' : '').'</a></th></tr>
';

		for ($i = 0; $i < $post_count; $i++) {
			list($no, $sub, $name, $now) = array($post[$i]['no'], $post[$i]['sub'], $post[$i]['name'], $post[$i]['now']);

			$rescount = $pc[$no] - 1;
			if ($this->HIGHLIGHT_COUNT > 0 && $rescount > $this->HIGHLIGHT_COUNT) {
				$rescount = '<span style="color:red">'.$rescount.'</span>';
			}
			$dat .= '<tr class="ListRow'.($i % 2 + 1).'_bg">'.
				($this->SHOW_FORM ? '<td><input type="checkbox" name="'.$no.'" value="delete" /></td>' : '').
				'<td>'.$no.'</td><td><a href="'.PHP_SELF.'?res='.$no.'">'.$sub.
				'</a></td><td>'.$name.'</td><td>'.$rescount.'</td><td>'.$now.'</td></tr>'."\n";
		}

		$dat .= '</table>
<hr />

<div id="page_switch">
<table border="1" style="float: left;"><tr>
';
		if ($page) {
			$dat .= '<td><a href="'.$thisPage.'&amp;page='.($page - 1).'&amp;sort='.$sort.'">'.
				_T('prev_page').'</a></td>';
		}
		else $dat .= '<td style="white-space: nowrap;">'._T('first_page').'</td>';
		$dat .= '<td>';
		for ($i = 0; $i <= $pageMax; $i++) {
			if($i==$page) $dat .= '[<b>'.$i.'</b>] ';
			else $dat .= '[<a href="'.$thisPage.'&amp;page='.$i.'&amp;sort='.$sort.'">'.$i.'</a>] ';
		}
		$dat .= '</td>';
		if ($page < $pageMax) {
			$dat .= '<td><a href="'.$thisPage.'&amp;page='.($page + 1).'&amp;sort='.$sort.'">'.
				_T('next_page').'</a></td>';
		}
		else $dat .= '<td style="white-space: nowrap;">'._T('last_page').'</td>';
		$dat .= '
</tr></table>
</div>';
		if ($this->SHOW_FORM) {
			$adminMode = adminAuthenticate('check'); // 前端管理模式
			$adminFunc = ''; // 前端管理選擇
			if($adminMode){
				$adminFunc = '<select name="func"><option value="delete">'._T('admin_delete').'</option>';
				$funclist = array();
				$dummy = '';
				self::$PMS->useModuleMethods('AdminFunction', array('add', &$funclist, null, &$dummy)); // "AdminFunction" Hook Point
				foreach($funclist as $f) $adminFunc .= '<option value="'.$f[0].'">'.$f[1].'</option>'."\n";
				$adminFunc .= '</select>';
			}

			$pte_vals = array('{$DEL_HEAD_TEXT}' => '<input type="hidden" name="mode" value="usrdel" />'._T('del_head'),
				'{$DEL_IMG_ONLY_FIELD}' => '<input type="checkbox" name="onlyimgdel" id="onlyimgdel" value="on" />',
				'{$DEL_IMG_ONLY_TEXT}' => _T('del_img_only'),
				'{$DEL_PASS_TEXT}' => ($adminMode ? $adminFunc : '')._T('del_pass'),
				'{$DEL_PASS_FIELD}' => '<input type="password" name="pwd" size="8" value="" />',
				'{$DEL_SUBMIT_BTN}' => '<input type="submit" value="'._T('del_btn').'" />');
			$dat .= PMCLibrary::getPTEInstance()->ParseBlock('DELFORM', $pte_vals).'</form>';
		}

		$dat .= '</div>';
		foot($dat);
		echo $dat;
	}
}