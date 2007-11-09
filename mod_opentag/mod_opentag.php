<?php

class mod_opentag{
	var $mypage;

	function mod_opentag(){
		global $PMS;
		$PMS->hookModuleMethod('ModulePage', 'mod_opentag'); // 向系統登記模組專屬獨立頁面
		$this->mypage = $PMS->getModulePageURL('mod_opentag');
	}

	/* Get the name of module */
	function getModuleName(){
		return 'mod_opentag : 開放標籤編輯';
	}

	/* Get the module version infomation */
	function getModuleVersionInfo(){
		return '4th.Release.2 (v071109)';
	}

	function autoHookThreadPost(&$arrLabels, $post, $isReply){
		if(USE_CATEGORY) $arrLabels['{$CATEGORY}'].=' [<a href="'.$this->mypage.'&amp;no='.$post['no'].'">變更</a>]';
	}

	function autoHookThreadReply(&$arrLabels, $post, $isReply){
		if(USE_CATEGORY) $arrLabels['{$CATEGORY}'].=' [<a href="'.$this->mypage.'&amp;no='.$post['no'].'">變更</a>]';
	}

	function ModulePage(){
		global $PIO, $FileIO, $PMS, $PTE;

		if(!isset($_GET['no'])) die('[Error] not enough parameter.');
		if(!isset($_POST['tag'])) {
			$post = $PIO->fetchPosts($_GET['no']);
			if(!count($post)) die('[Error] Post does not exist.');
			$pte_vals = array('{$TITLE}'=>TITLE, '{$RESTO}'=>'');
			$dat = $PTE->ParseBlock('HEADER', $pte_vals);
			$dat .= '</head><body id="main">';
			$dat .= '<form action="'.$this->mypage.'&amp;no='.$_GET['no'].'" method="POST">Tag: <input type="text" name="tag" value="'.substr(str_replace('&#44;', ',', $post[0]['category']),1,-1).'" size="28" /><input type="submit" name="submit" value="Tag!" /></form>';
			echo $dat."</body></html>";
		} else {
			if($_SERVER['REQUEST_METHOD'] != 'POST') error(_T('regist_notpost')); // 非正規POST方式
			$post = $PIO->fetchPosts($_GET['no']);
			if(!count($post)) die('[Error] Post does not exist.');
			if(USE_CATEGORY && $_POST['tag']){ // 修整標籤樣式
				$category = explode(',', $_POST['tag']); // 把標籤拆成陣列
				$category = '&#44;'.implode('&#44;', array_map('trim', $category)).'&#44;'; // 去空白再合併為單一字串 (左右含,便可以直接以,XX,形式搜尋)
			}else{ $category = ''; }

			$PIO->updatePost($_GET['no'], array('category'=>$category));
			$PIO->dbCommit();
			echo "Done. Please go back and update pages.";
		}
	}
}
?>