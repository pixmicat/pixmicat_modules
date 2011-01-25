<?php
/*
mod_threadlist : 討論串列表
By: scribe
*/

class mod_threadlist{
	var $THREADLIST_NUMBER,$THREADLIST_NUMBER_IN_MAIN,$SHOW_IN_MAIN,$FORCE_SUBJECT,$SHOW_FORM,$HIGHLIGHT_COUNT;

	function mod_threadlist(){
		global $PMS;
		$PMS->hookModuleMethod('ModulePage', __CLASS__); // 向系統登記模組專屬獨立頁面

		$this->THREADLIST_NUMBER = 50; // 一頁顯示列表個數
		$this->THREADLIST_NUMBER_IN_MAIN = 20; // 在主頁面顯示列表個數
		$this->SHOW_IN_MAIN = true; // 在主頁面顯示
		$this->FORCE_SUBJECT = false; // 是否強制開新串要有標題
		$this->SHOW_FORM = true; // 是否顯示刪除表單
		$this->HIGHLIGHT_COUNT = 30; // 熱門回應數，超過這個值回應數會變紅色 (0 為不使用)
	}

	/* Get the name of module */
	function getModuleName(){
		return __CLASS__.' : 討論串列表';
	}

	/* Get the module version infomation */
	function getModuleVersionInfo(){
		return '5th.Release.2 (v110125)';
	}

	function autoHookRegistBeforeCommit(&$name, &$email, &$sub, &$com, &$category, &$age, $dest, $isReply, $imgWH, &$status){
		if($this->FORCE_SUBJECT && !$isReply && $sub==DEFAULT_NOTITLE) error('發文不可以沒有標題喔', $dest);
	}

	/* 自動掛載：頂部連結列 */
	function autoHookToplink(&$linkbar, $isReply){
		global $PMS;
		$linkbar .= '[<a href="'.$PMS->getModulePageURL('mod_threadlist').'">主題列表</a>]'."\n";
	}

	function autoHookThreadFront(&$txt,$isReply){
		global $PMS, $PIO, $FileIO;
		if($this->SHOW_IN_MAIN && !$isReply) {
			$dat = ''; // HTML Buffer
			$plist = $PIO->fetchThreadList(0, $this->THREADLIST_NUMBER_IN_MAIN, true); // 編號由大到小排序
			$PMS->useModuleMethods('ThreadOrder', array($isReply,0,0,&$plist)); // "ThreadOrder" Hook Point
			$post = $PIO->fetchPosts($plist); // 取出資料
			$post_count = ceil(count($post) / 2);
		    $dat .= '<div id="topiclist" style="text-align: center; clear: both;">
<table cellpadding="1" cellspacing="1" border="0" width="100%" style="margin: 0px auto; text-align: left; margin-bottom: 1em; font-size: 1em;">
<tr><th class="reply_hl" colspan="2" style="text-align: center;">題名一覽</th></tr>
';
			for($i = 0; $i < $post_count; $i++){
				$leftStr = $post[$i]['no'].': <a href="'.PHP_SELF.'?res='.$post[$i]['no'].'">'.$post[$i]['sub'].' ('.($PIO->postCount($post[$i]['no']) - 1).')</a>';
				$rightStr = isset($post[$i + $post_count]) ? $post[$i + $post_count]['no'].': <a href="'.PHP_SELF.'?res='.$post[$i + $post_count]['no'].'">'.$post[$i + $post_count]['sub'].' ('.($PIO->postCount($post[$i + $post_count]['no']) - 1).')</a>' : '';
				$dat .= '<tr class="ListRow'.($i % 2 + 1).'_bg"><td style="width: 50%">'.$leftStr.'</td><td>'.$rightStr.'</td></tr>'."\n";
			}
$dat .= '</table>
</div>

';
		    $txt .= $dat;
		}
	}

	function _getPostCounts($posts) {
		global $PIO;

		$pc = array();
		foreach($posts as $post)
			$pc[$post] = $PIO->postCount($post);

		return $pc;
	}

	function _kasort(&$a,$revkey=false,$revval=false) {
		$t=$u=array();
		foreach($a as $k=>&$v) { // flip array
			if(!isset($t[$v])) $t[$v] = array($k);
			else $t[$v][] = $k;
		}

		if($revkey) krsort($t);
		else ksort($t);
		
		foreach($t as $k=>&$vv) {
			if($revval) rsort($vv);
			else sort($vv);
		}
		foreach($t as $k=>&$vv) { // reconstruct array
			foreach($vv as &$v)
				$u[$v] = $k;
		}
		$a=$u;
	}

	/* 模組獨立頁面 */
	function ModulePage(){
		global $PMS, $PIO, $FileIO, $PTE;

		$thisPage = $PMS->getModulePageURL('mod_threadlist'); // 基底位置
		$dat = ''; // HTML Buffer
		$listMax = $PIO->threadCount(); // 討論串總筆數
		$pageMax = ceil($listMax / $this->THREADLIST_NUMBER) - 1; // 分頁最大編號
		$page = isset($_GET['page']) ? intval($_GET['page']) : 0; // 目前所在分頁頁數
		$sort = isset($_GET['sort']) ? $_GET['sort'] : 'no';
		if($page < 0 || $page > $pageMax) exit('Page out of range.'); // $page 超過範圍
		if(strpos($sort, 'post') !== false) {
			$plist = $PIO->fetchThreadList();
			$pc = $this->_getPostCounts($plist);
			$this->_kasort($pc,$sort == 'postdesc',true);

			$plist = array_keys($pc);

			$plist = array_slice($plist, $this->THREADLIST_NUMBER * $page, $this->THREADLIST_NUMBER); //切出需要的大小
		} else {
			$plist = $PIO->fetchThreadList($this->THREADLIST_NUMBER * $page, $this->THREADLIST_NUMBER, $sort == 'date' ? false : true); // 編號由大到小排序
			$PMS->useModuleMethods('ThreadOrder', array(0,$page,0,&$plist)); // "ThreadOrder" Hook Point
			$pc = $this->_getPostCounts($plist);
		}
		$post = $PIO->fetchPosts($plist); // 取出資料
		$post_count = count($post);

		if(strpos($sort, 'post') !== false) { // 要重排次序
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
		$dat .= '<div id="contents">
[<a href="'.PHP_SELF2.'?'.time().'">回到版面</a>]
<div class="bar_reply">列表模式</div>'.($this->SHOW_FORM ? '<form action="'.PHP_SELF.'" method="post">' : '').'<table align="center" width="98%"><tr>
'.($this->SHOW_FORM ? '<th></th>' : '').'
<th><a href="'.$thisPage.'&amp;sort=no">No.'.($sort == 'no' ? ' ▼' : '').'</a></th>
<th width="48%">標題</th>
<th>發文者</th>
<th><a href="'.$thisPage.'&amp;sort='.($sort == 'postdesc' ? 'postasc' : 'postdesc').'">回應'.($sort == 'postdesc' ? ' ▼' : ($sort == 'postasc' ? ' ▲' : '')).'</a></th>
<th><a href="'.$thisPage.'&amp;sort=date">日期'.($sort == 'date' ? ' ▼' : '').'</a></th></tr>
';
		// 逐步取資料
		for($i = 0; $i < $post_count; $i++){
			list($no, $sub, $name, $now) = array($post[$i]['no'], $post[$i]['sub'],$post[$i]['name'], $post[$i]['now']);

			$rescount = $pc[$no] - 1;
			if($this->HIGHLIGHT_COUNT > 0 && $rescount > $this->HIGHLIGHT_COUNT){
				$rescount = '<span style="color:red">'.$rescount.'</span>';
			}
			$dat .= '<tr class="ListRow'.($i % 2 + 1).'_bg">'.($this->SHOW_FORM ? '<td><input type="checkbox" name="'.$no.'" value="delete" /></td>' : '').'<td>'.$no.'</td><td><a href="'.PHP_SELF.'?res='.$no.'">'.$sub.'</a></td><td>'.$name.'</td><td>'.$rescount.'</td><td>'.$now.'</td></tr>'."\n";
		}

		$dat .= '</table>
<hr />

<div id="page_switch">
<table border="1" style="float: left;"><tr>
';
		if($page) $dat .= '<td><a href="'.$thisPage.'&amp;page='.($page - 1).'&amp;sort='.$sort.'">上一頁</a></td>';
		else $dat .= '<td style="white-space: nowrap;">第一頁</td>';
		$dat .= '<td>';
		for($i = 0; $i <= $pageMax; $i++){
			if($i==$page) $dat .= '[<b>'.$i.'</b>] ';
			else $dat .= '[<a href="'.$thisPage.'&amp;page='.$i.'&amp;sort='.$sort.'">'.$i.'</a>] ';
		}
		$dat .= '</td>';
		if($page < $pageMax) $dat .= '<td><a href="'.$thisPage.'&amp;page='.($page + 1).'&amp;sort='.$sort.'">下一頁</a></td>';
		else $dat .= '<td style="white-space: nowrap;">最後一頁</td>';
		$dat .= '
</tr></table>
</div>';
		if($this->SHOW_FORM) {
			$pte_vals = array('{$DEL_HEAD_TEXT}' => '<input type="hidden" name="mode" value="usrdel" />'._T('del_head'),
				'{$DEL_IMG_ONLY_FIELD}' => '<input type="checkbox" name="onlyimgdel" id="onlyimgdel" value="on" />',
				'{$DEL_IMG_ONLY_TEXT}' => _T('del_img_only'),
				'{$DEL_PASS_TEXT}' => _T('del_pass'),
				'{$DEL_PASS_FIELD}' => '<input type="password" name="pwd" size="8" value="" />',
				'{$DEL_SUBMIT_BTN}' => '<input type="submit" value="'._T('del_btn').'" />');
			$dat .= $PTE->ParseBlock('DELFORM', $pte_vals).'</form>';
		}

		$dat .= '</div>';
		foot($dat);
		echo $dat;
	}
}
?>