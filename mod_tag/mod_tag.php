<?php
/* 
	注意:	這個插件大部分的原碼來自 mod_opentag 以及 pixmicat 內建的 "category" 功能。
		絕大部分還是剪貼過來的, 而且本插件採用相同的資料庫欄位。因此，本插件不會修改
		您的資料庫格式。但是您不能同時採用這個插件以及內建的 "category" 功能。
		簡單說，這個插件修改程式基本作業，廢掉內建的 "category" 來提供更實用的標籤系統。

		本插件採取類似論壇軟體的標籤系統，只允許 OP 帖可以有標籤。搜尋的時候自動調用
		整串討論出來。如果您需要允許每篇回覆都有自己的標籤，請用內建的 "category" 功能。
*/
class mod_tag extends ModuleHelper {
	private $mypage;
	private $LANGUAGE = array(
			'zh_TW' => array(
				'modtag_tag' => '標籤', 
				'modtag_separate_with_comma' => '請以 , 逗號分隔多個標籤',
				'modtag_edit' => '編輯'
			),
			'ja_JP' => array(
				'modtag_tag' => 'タグ', 
				'modtag_separate_with_comma' => '半形カンマ（,）でタグを個別してください。',
				'modtag_edit' => '編集'
			),
			'en_US' => array(
				'modtag_tag' => '標籤', 
				'modtag_separate_with_comma' => 'Please separate tags with a single comma [,]',
				'modtag_edit' => 'Edit'
			)
		);

	public function __construct($PMS) {
		parent::__construct($PMS);
		$this->mypage = $this->getModulePageURL();
		$this->attachLanguage($this->LANGUAGE, 'en_US');// 載入語言檔
	}

	/* Get the name of module */
	public function getModuleName(){
		return 'mod_tag : 標籤編輯系統';
	}

	/* Get the module version infomation */
	public function getModuleVersionInfo(){
		return 'Beta Release (git: r700)';
	}

	public function autoHookHead(&$txt, $isReply){
//如果不需要JQUERY可以COMMENT 第一行，第一行為自動決定加載JQUERY的JAVASCRIPT
		$txt .= '<script type="text/javascript">window.jQuery || document.write("\x3Cscript src=\x22//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js\x22>\x3C/script>");</script>
<script type="text/javascript">
// <![CDATA[
jQuery(function($){
	$("div.category a.change").click(function(){
		var tag = "";
		var no = this.href.match(/&no=([0-9]+)/) ? RegExp.$1 : 0;
		var obj = $(this);
		obj.siblings("a").each(function(){ tag += "," + this.innerHTML; });
		obj.parent().html("<input type=\'text\' id=\'attrTag" + no + "\' size=\'28\'><input type=\'button\' value=\'Tag!\' id=\'sendTag" + no + "\'>");
		$g("attrTag" + no).value = tag.substr(1);
		$("#sendTag" + no).click(function(){
			var tmpthis = this;
			$.post("'.str_replace('&amp;', '&', $this->mypage).'&no=" + no, {ajaxmode: true, tag: this.previousSibling.value}, function(newTag){
				newTag = $.map(newTag.split(","), function(n){
					return n.link("pixmicat.php?mode=module&load=mod_tag&do=search&c=" + encodeURI(n));
				});
				tmpthis.parentNode.innerHTML = newTag.join(", ");
			});
		});
		return false;
	});
});
// ]]>
</script>';
	}

	public function autoHookThreadPost(&$arrLabels, $post, $isReply){
		// 改變 category 顯示規定
		if ($post['resto'] == 0) {
		// if (!$isReply) {
			// OP，把連結改到我們的 tag search
			$arrLabels['{$CATEGORY}'] = str_replace('mode=category&amp;c=', 'mode=module&amp;load=mod_tag&amp;do=search&amp;c=', $arrLabels['{$CATEGORY}']);
			$arrLabels['{$CATEGORY}'] = '<span>'.$arrLabels['{$CATEGORY}'].'[<a href="'.$this->mypage.'&amp;no='.$post["no"].'" class="change">'.$this->_T('modtag_edit').'</a>]</span>';
		} else { // 回文，刪除 category 資料
			$arrLabels['{$CATEGORY}'] = '';	
		}
	}

	public function autoHookThreadReply(&$arrLabels, $post, $isReply){
		// 導向至 autoHookThreadPost 來一起處理
		$this->autoHookThreadPost($arrLabels, $post, $isReply);
	}

	public function autoHookPostForm(&$form){
		// 很白目的隱藏表單上 category 的欄位的辦法
		if (isset($_GET['res']) || (isset($_GET['no']) && ($_GET['load'] == 'mod_edit'))) {
			$PTE = PMCLibrary::getPTEInstance();
			// 從回文的表單上移除標籤欄位
			$what = '<tr><td class="Form_bg"><b>{$FORM_CATEGORY_TEXT}</b></td><td>{$FORM_CATEGORY_FIELD}<small>{$FORM_CATEGORY_NOTICE}</small></td></tr>';
			$PTE->tpl = str_replace($what, '', $PTE->tpl);
		}
	}

	public function autoHookRegistBeforeCommit(&$name, &$email, &$sub, &$com, &$category, &$age, $dest, $isReply, $imgWH, &$status) {
		// 移除回文的 category
		if ($isReply) {
			$category = '';
		}
	}


	public function ModulePage(){
		$PIO = PMCLibrary::getPIOInstance();
		$PTE = PMCLibrary::getPTEInstance();
		if(!isset($_GET['do'])) {
		// 沒有 "do" 指令，舊的 tag 連接
			if(!isset($_GET['no'])) die('[Error] not enough parameter.');
			if(!isset($_POST['tag'])) {
				$post = $PIO->fetchPosts($_GET['no']);
				if(!count($post)) die('[Error] Post does not exist.');
				$pte_vals = array('{$TITLE}'=>TITLE, '{$RESTO}'=>'');
				$dat = $PTE->ParseBlock('HEADER', $pte_vals);
				$dat .= '</head><body id="main">';
				$dat .= '<form action="'.$this->mypage.'&amp;no='.$_GET['no'].'" method="post">Tag: <input type="text" name="tag" value="'.htmlentities(substr(str_replace('&#44;', ',', $post[0]['category']),1,-1), ENT_QUOTES, 'UTF-8').'" size="28" /><input type="submit" name="submit" value="Tag!" /></form>';
				echo $dat.'</body></html>';
			} else {
				$Tag = CleanStr($_POST['tag']);
				if($_SERVER['REQUEST_METHOD'] != 'POST') error(_T('regist_notpost')); // 非正規POST方式
				$post = $PIO->fetchPosts($_GET['no']);
				$parentNo = $post[0]['resto'] ? $post[0]['resto'] : $post[0]['no'];
				$threads = array_flip($PIO->fetchThreadList());
				$threadPage = floor($threads[$parentNo] / PAGE_DEF);
				if(!count($post)) die('[Error] Post does not exist.');
				$ss = method_exists($PIO, '_replaceComma') ? '&#44;' : ','; // Dirty implement
				$category = explode(',', $Tag); // 把標籤拆成陣列
				$category = $ss.implode($ss, array_map('trim', $category)).$ss; // 去空白再合併為單一字串 (左右含,便可以直接以,XX,形式搜尋)

				$PIO->updatePost($_GET['no'], array('category'=>$category));
				$PIO->dbCommit();
				if(STATIC_HTML_UNTIL == -1 || $threadPage <= STATIC_HTML_UNTIL) updatelog(0, $threadPage, true); // 僅更新討論串出現那頁
				deleteCache(array($parentNo)); // 刪除討論串舊快取

				if(isset($_POST['ajaxmode'])){
					echo $Tag;
				}else{
					header('HTTP/1.1 302 Moved Temporarily');
					header('Location: '.fullURL().PHP_SELF2.'?'.time());
				}
			}
		} else {
		// 有 "do" 指令，查看下一步
			if ($_GET['do'] == 'search') {
			// 搜尋符合標籤的主題
				global $PTE, $PIO, $PMS, $FileIO, $language;
				$category = isset($_GET['c']) ? strtolower(strip_tags(trim($_GET['c']))) : ''; // 搜尋之類別標籤
				if(!$category) error(_T('category_nokeyword'));
				$category_enc = urlencode($category); $category_md5 = md5($category);
				$page = isset($_GET['p']) ? @intval($_GET['p']) : 1; if($page < 1) $page = 1; // 目前瀏覽頁數
				$isrecache = isset($_GET['recache']); // 是否強制重新生成快取

				// 利用Session快取類別標籤出現篇別以減少負擔
				session_start(); // 啟動Session
				if(!isset($_SESSION['loglist_'.$category_md5]) || $isrecache){
					$loglist = $PIO->searchCategory($category);
					$_SESSION['loglist_'.$category_md5] = serialize($loglist);
				}else $loglist = unserialize($_SESSION['loglist_'.$category_md5]);

				$loglist_count = count($loglist);
				if(!$loglist_count) error(_T('category_notfound'));
				$page_max = ceil($loglist_count / PAGE_DEF); if($page > $page_max) $page = $page_max; // 總頁數

				// 分割陣列取出適當範圍作分頁之用
				$loglist_cut = array_slice($loglist, PAGE_DEF * ($page - 1), PAGE_DEF); // 取出特定範圍文章
				$loglist_cut_count = count($loglist_cut);

				$dat = '';
				head($dat);
				$links = '[<a href="'.PHP_SELF2.'?'.time().'">'._T('return').'</a>][<a href="'.PHP_SELF.'?mode=module&amp;load=mod_tag&amp;do=search&amp;c='.$category_enc.'&amp;recache=1">'._T('category_recache').'</a>]';
				$PMS->useModuleMethods('LinksAboveBar', array(&$links,'category'));
				$dat .= "<div>$links</div>\n";
				for($i = 0; $i < $loglist_cut_count; $i++){
					$tID = $loglist_cut[$i];
					$tree_count = $PIO->postCount($tID) - 1; // 討論串回應個數
					$RES_start = $tree_count - RE_DEF + 1; if($RES_start < 1) $RES_start = 1; // 開始
					$RES_amount = RE_DEF; // 取幾個
					$hiddenReply = $RES_start - 1; // 被隱藏回應
					// $RES_start, $RES_amount 拿去算新討論串結構 (分頁後, 部分回應隱藏)
					$tree = $PIO->fetchPostList($tID); // 整個討論串樹狀結構
					$tree_cut = array_slice($tree, $RES_start, $RES_amount); array_unshift($tree_cut, $tID); // 取出特定範圍回應
					$posts = $PIO->fetchPosts($tree_cut); // 取得文章架構內容
					$dat .= arrangeThread($PTE, $tree, $tree_cut, $posts, $hiddenReply, 0, array(), array(), false, false, false);
				}

				$dat .= '<table border="1"><tr>';
				if($page > 1) $dat .= '<td><form action="'.PHP_SELF.'?mode=module&amp;load=mod_tag&amp;do=search&amp;c='.$category_enc.'&amp;p='.($page - 1).'" method="post"><div><input type="submit" value="'._T('prev_page').'" /></div></form></td>';
				else $dat .= '<td style="white-space: nowrap;">'._T('first_page').'</td>';
				$dat .= '<td>';
				for($i = 1; $i <= $page_max ; $i++){
					if($i==$page) $dat .= "[<b>".$i."</b>] ";
					else $dat .= '[<a href="'.PHP_SELF.'?mode=module&amp;load=mod_tag&amp;do=search&amp;c='.$category_enc.'&amp;p='.$i.'">'.$i.'</a>] ';
				}
				$dat .= '</td>';
				if($page < $page_max) $dat .= '<td><form action="'.PHP_SELF.'?mode=module&amp;load=mod_tag&amp;do=search&amp;c='.$category_enc.'&amp;p='.($page + 1).'" method="post"><div><input type="submit" value="'._T('next_page').'" /></div></form></td>';
				else $dat .= '<td style="white-space: nowrap;">'._T('last_page').'</td>';
				$dat .= '</tr></table>'."\n";

				foot($dat);
				echo $dat;
			} else if ($_GET['do'] == 'cloud') {
			// 建立 tag cloud?
				// blah blah blah
			} else {
			// 不知道該如何處理的 "do" 指令
				echo 'スクリプトはTranslation Server Errorに免費の午餐を食べています！<br />';
				echo '...你想表達什麼?';
			}
		}
	}
}//End of Module
