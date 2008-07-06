<?php
class mod_tag{
	var $mypage;

	function mod_tag(){
		global $PMS;
		$PMS->hookModuleMethod('ModulePage', __CLASS__); // 向系統登記模組專屬獨立頁面
		$this->mypage = $PMS->getModulePageURL(__CLASS__);

		AttachLanguage(array($this, '_loadLanguage')); // 載入語言檔

	}

	/* Get the name of module */
	function getModuleName(){
		/* Note:	Majority of this code is based off of mod_opentag and base pixmicat's category feature.
				In fact, we are stealing code pretty much directly word for word for a lot of the features,
				and we are using the same field in the database.  This means there will be no modification
				to your database, but you will not be able to use both features at the same time.
		*/
		return 'mod_tag : 標籤編輯系統';
	}

	/* Get the module version infomation */
	function getModuleVersionInfo(){
		return 'Alpha Release';
	}

	function autoHookHead(&$txt, $isReply){
		$txt .= '<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.2.6/jquery.min.js"></script>
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

	function autoHookThreadPost(&$arrLabels, $post, $isReply){
		global $language;
		// use string replace to force tags to go to our own module page instead!
		// die ($arrLabels['{$CATEGORY}']);
		// die (var_dump($post));
		if ($post["resto"] == 0) {
			// parse the tags to our own URL
			$arrLabels['{$CATEGORY}'] = str_replace("mode=category&amp;c=", "mode=module&amp;load=mod_tag&amp;do=search&amp;c=", $arrLabels['{$CATEGORY}']);
			$arrLabels['{$CATEGORY}'] = '<span>'.$arrLabels['{$CATEGORY}'].' [<a href="'.$this->mypage.'&amp;no='.$post['no'].'" class="change">' . $language['modtag_edit'] . '</a>]</span>';
		} else {
			// die (var_dump($arrLabels));
			// kill the tag for a non-thread start
			$arrLabels['{$CATEGORY}'] = '';	
		}
	}

	// These shouldn't appear for replies, it should only be present for the first post in each thread.
	function autoHookThreadReply(&$arrLabels, $post, $isReply){
		$this->autoHookThreadPost($arrLabels, $post, $isReply);
	}

/*
	// Don't need to have a custom one if we're using category field on form
	function autoHookPostForm(&$form){
		global $languages, $resto;
		if (!isset($_GET['res']) && (!isset($resto))) {
			// poor check like this, need to also add it for edit scren only if it is an op?
			$form .= '<tr><td class="Form_bg"><b>'._T('modtag_tag').'</b></td><td><input type="text" name="category" size="28" value="" /><small>('._T('modtag_separate_with_comma').')</small></td></tr>'."\n";
		}
	}
*/

	function autoHookRegistBeforeCommit(&$name, &$email, &$sub, &$com, &$category, &$age, $dest, $isReply, $imgWH, &$status) {
		if ($isReply) {
			// strip replies of its category
			$category = "";
		}
	}


	function ModulePage(){
		global $PIO, $PTE;
		if(!isset($_GET['do'])) {
		// no do condition, legacy links for listing current post tags, and adding tags
			if(!isset($_GET['no'])) die('[Error] not enough parameter.');
			if(!isset($_POST['tag'])) {
				$post = $PIO->fetchPosts($_GET['no']);
				if(!count($post)) die('[Error] Post does not exist.');
				$pte_vals = array('{$TITLE}'=>TITLE, '{$RESTO}'=>'');
				$dat = $PTE->ParseBlock('HEADER', $pte_vals);
				$dat .= '</head><body id="main">';
				$dat .= '<form action="'.$this->mypage.'&amp;no='.$_GET['no'].'" method="POST">Tag: <input type="text" name="tag" value="'.htmlentities(substr(str_replace('&#44;', ',', $post[0]['category']),1,-1), ENT_QUOTES, 'UTF-8').'" size="28" /><input type="submit" name="submit" value="Tag!" /></form>';
				echo $dat."</body></html>";
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
		// yes do condition, what are we doing?
			if ($_GET['do'] == "search") {
			// searching for threads with the given tag
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
			} else if ($_GET['do'] == "cloud") {
			// get a pretty tag cloud?
				// blah blah blah
			}
		}
	}

	function _loadLanguage(){
		global $language;
		if(PIXMICAT_LANGUAGE != 'zh_TW' && PIXMICAT_LANGUAGE != 'ja_JP' && PIXMICAT_LANGUAGE != 'en_US') $lang = 'en_US';
		else $lang = PIXMICAT_LANGUAGE;

		if($lang=='zh_TW'){
			$language['modtag_tag'] = '標籤';
			$language['modtag_separate_with_comma'] = '請以 , 逗號分隔多個標籤';
			$language['modtag_edit'] = '編輯';
		}elseif($lang=='ja_JP'){
			$language['modtag_tag'] = 'タグ';
			$language['modtag_separate_with_comma'] = '半形カンマ（,）でタグを個別してください。';
			$language['modtag_edit'] = '編集';
		}elseif($lang=='en_US'){
			$language['modtag_tag'] = 'Tags';
			$language['modtag_separate_with_comma'] = 'Please separate tags with a single comma [,]';
			$language['modtag_edit'] = 'Edit';
		}
	}

}
?>
