<?php
class mod_opentag{
	var $mypage;

	function mod_opentag(){
		global $PMS;
		$PMS->hookModuleMethod('ModulePage', __CLASS__); // 向系統登記模組專屬獨立頁面
		$this->mypage = $PMS->getModulePageURL(__CLASS__);
	}

	/* Get the name of module */
	function getModuleName(){
		return 'mod_opentag : 開放標籤編輯';
	}

	/* Get the module version infomation */
	function getModuleVersionInfo(){
		return '4th.Release.3-dev (v080519)';
	}

	function autoHookHead(&$txt, $isReply){
		$txt .= '<script type="text/javascript" src="module/jquery-1.2.3.min.js"></script>
<script type="text/javascript">
// <![CDATA[
jQuery(function($){
	$("div.category a.change").click(function(){
		var tag = "";
		var no = this.href.match(/&no=([0-9]+)/) ? RegExp.$1 : 0;
		var obj = $(this);
		obj.siblings("a").each(function(){ tag += "," + this.innerHTML; });
		obj.parent().html("<input type=\'text\' id=\'attrTag" + no + "\' size=\'28\' /><input type=\'button\' value=\'Tag!\' id=\'sendTag" + no + "\' />");
		$g("attrTag" + no).value = tag.substr(1);
		$("#sendTag" + no).click(function(){
			var tmpthis = this;
			$.post("'.str_replace('&amp;', '&', $this->mypage).'&no=" + no, {ajaxmode: true, tag: this.previousSibling.value}, function(newTag){
				newTag = $.map(newTag.split(","), function(n){
					return n.link("pixmicat.php?mode=category&c=" + encodeURI(n));
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
		if(USE_CATEGORY) $arrLabels['{$CATEGORY}'] = '<span>'.$arrLabels['{$CATEGORY}'].' [<a href="'.$this->mypage.'&amp;no='.$post['no'].'" class="change">變更</a>]</span>';
	}

	function autoHookThreadReply(&$arrLabels, $post, $isReply){
		$this->autoHookThreadPost($arrLabels, $post, $isReply);
	}

	function ModulePage(){
		global $PIO, $PTE;

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
			if(USE_CATEGORY && $Tag){ // 修整標籤樣式
				$ss = method_exists($PIO, '_replaceComma') ? '&#44;' : ','; // Dirty implement
				$category = explode(',', $Tag); // 把標籤拆成陣列
				$category = $ss.implode($ss, array_map('trim', $category)).$ss; // 去空白再合併為單一字串 (左右含,便可以直接以,XX,形式搜尋)
			}else{ $category = ''; }

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
	}
}
?>