<?php
/*
mod_catalog : 以相簿模式列出圖檔方便瀏覽及抓取
By: scribe (Adopted from Pixmicat!-Festival)
*/

class mod_catalog extends ModuleHelper {
	var $CATALOG_NUMBER = 20; // 相簿模式一頁最多顯示個數 (視文章是否有貼圖而有實際變動)
	var $USE_SEARCH_CODE = true; // 使用搜尋程序來建立相簿?
	var $myPage;

	public function __construct($PMS) {
		parent::__construct($PMS);
		$this->myPage = $this->getModulePageURL();// 基底位置
	}

	/* Get the name of module */
	function getModuleName(){
		return 'mod_catalog : 以相簿模式列出圖檔';
	}

	/* Get the module version infomation */
	function getModuleVersionInfo(){
		return '7th.Release (v140529)';
	}

	/* 自動掛載：頂部連結列 */
	function autoHookToplink(&$linkbar, $isReply){
		$linkbar .= '[<a href="'.$this->myPage.'">相簿模式</a>]'."\n";
	}

	/* 掛載樣式表 */
	function hookHeadCSS(&$style, $isReply){
		$style .= '<style type="text/css">
div.list { float: left; margin: 5px; width: '.MAX_RW.'px; height: '.MAX_RH.'px; } /* (相簿模式) div 框格設定 */
div.list input { width:14px; height:14px; }
div.list .tools { position: absolute; overflow:hidden; width:18px; height:18px; }
div.list .tools-expend { position: absolute; overflow:hidden; width:auto; }
</style>
';
	}

	/* 模組獨立頁面 */
	function ModulePage(){
		$PTE = PMCLibrary::getPTEInstance();
		$PMS = PMCLibrary::getPMSInstance();
		$PIO = PMCLibrary::getPIOInstance();
		$FileIO = PMCLibrary::getFileIOInstance();; 
		$dat = ''; // HTML Buffer
		$listMax = $PIO->postCount(); // 文章總筆數
		$pageMax = ceil($listMax / $this->CATALOG_NUMBER) - 1; // 分頁最大編號
		$page = isset($_GET['page']) ? intval($_GET['page']) : 0; // 目前所在分頁頁數
		if($page < 0 || $page > $pageMax) exit('Page out of range.'); // $page 超過範圍
		if(!$this->USE_SEARCH_CODE && !isset($_GET['search'])) {
			$plist = $PIO->fetchPostList(0, $this->CATALOG_NUMBER * $page, $this->CATALOG_NUMBER); // 取得定位正確的 N 筆資料號碼
			$post = $PIO->fetchPosts($plist); // 取出資料
		} else {
			$post = $PIO->searchPost(array('.'), 'ext', 'AND');
			$pageMax = ceil(count($post) / $this->CATALOG_NUMBER) - 1;
			$post = array_slice($post, $this->CATALOG_NUMBER * $page, $this->CATALOG_NUMBER);
		}
		$post_count = count($post);

		$PMS->hookModuleMethod('Head', array(&$this, 'hookHeadCSS'));
		head($dat);
		$dat .= '<div id="contents">
[<a href="'.PHP_SELF2.'?'.time().'">回到版面</a>]
<div class="bar_reply">相簿模式'.(@$_GET['style']=='detail'?' <a href="'.$this->myPage.($page?'&amp;page='.$page:'').(isset($_GET['search'])?'&amp;search':'').'&amp;style=simple">■</a>':' <a href="'.$this->myPage.($page?'&amp;page='.$page:'').(isset($_GET['search'])?'&amp;search':'').'&amp;style=detail">≡</a>').'</div>
';
		if($_GET['style']=='detail'){
			$dat .= '<script>
function hover(obj,ishover){
if(ishover) obj.className="tools-expend";
else obj.className="tools";
}
</script>
<form action="'.PHP_SELF.'" method="post">';
		}

		// 逐步取資料
		for($i = 0; $i < $post_count; $i++){
			list($no, $resto, $imgw, $imgh, $tw, $th, $tim, $ext, $now) = array($post[$i]['no'], $post[$i]['resto'], $post[$i]['imgw'], $post[$i]['imgh'],$post[$i]['tw'], $post[$i]['th'], $post[$i]['tim'], $post[$i]['ext'], $post[$i]['now']);
			if($FileIO->imageExists($tim.$ext)){
				$dat .= '<div class="list">'.(@$_GET['style']=='detail'?'<div class="tools" onmouseover="hover(this,true)" onmouseout="hover(this,false)"><input type="checkbox" name="'.$no.'" value="delete" /><a href="'.PHP_SELF.'?res='.($resto?$resto:$no).'#r'.$no.'">†</a></div>':'').'<a href="'.$FileIO->getImageURL($tim.$ext).'" rel="_blank"><img src="'.$FileIO->getImageURL($tim.'s.jpg').'" style="'.$this->OptimizeImageWH($tw, $th).'" title="'.(@$_GET['style']=='detail'?'No.'.$no.($resto?'('.$resto.')':'').' '.$now.' ':'').$imgw.'x'.$imgh.'" alt="'.$tim.$ext.'" /></a></div>'."\n";
			}
		}

		$dat .= '</div><hr />';

		if(@$_GET['style']=='detail') {
			$adminMode = adminAuthenticate('check'); // 前端管理模式
			$adminFunc = ''; // 前端管理選擇
			if($adminMode){
				$adminFunc = '<select name="func"><option value="delete">'._T('admin_delete').'</option>';
				$funclist = array();
				$dummy = '';
				$PMS->useModuleMethods('AdminFunction', array('add', &$funclist, null, &$dummy)); // "AdminFunction" Hook Point
				foreach($funclist as $f) $adminFunc .= '<option value="'.$f[0].'">'.$f[1].'</option>'."\n";
				$adminFunc .= '</select>';
			}

			$pte_vals = array('{$DEL_HEAD_TEXT}' => '<input type="hidden" name="mode" value="usrdel" />'._T('del_head'),
				'{$DEL_IMG_ONLY_FIELD}' => '<input type="checkbox" name="onlyimgdel" id="onlyimgdel" value="on" />',
				'{$DEL_IMG_ONLY_TEXT}' => _T('del_img_only'),
				'{$DEL_PASS_TEXT}' => ($adminMode ? $adminFunc : '')._T('del_pass'),
				'{$DEL_PASS_FIELD}' => '<input type="password" name="pwd" size="8" value="" />',
				'{$DEL_SUBMIT_BTN}' => '<input type="submit" value="'._T('del_btn').'" />');
			$dat .= $PTE->ParseBlock('DELFORM', $pte_vals).'</form>';
		}

		$dat .= '

<div id="page_switch">
<table border="1" style="float: left;"><tr>
';
		if($page) $dat .= '<td><a href="'.$this->myPage.'&amp;page='.($page - 1).(@$_GET['style']=='detail'?'&amp;style=detail':'').(isset($_GET['search'])?'&amp;search':'').'">上一頁</a></td>';
		else $dat .= '<td style="white-space: nowrap;">第一頁</td>';
		$dat .= '<td>';
		for($i = 0; $i <= $pageMax; $i++){
			if($i==$page) $dat .= '[<b>'.$i.'</b>] ';
			else $dat .= '[<a href="'.$this->myPage.'&amp;page='.$i.(@$_GET['style']=='detail'?'&amp;style=detail':'').(isset($_GET['search'])?'&amp;search':'').'">'.$i.'</a>] ';
		}
		$dat .= '</td>';
		if($page < $pageMax) $dat .= '<td><a href="'.$this->myPage.'&amp;page='.($page + 1).(@$_GET['style']=='detail'?'&amp;style=detail':'').(isset($_GET['search'])?'&amp;search':'').'">下一頁</a></td>';
		else $dat .= '<td style="white-space: nowrap;">最後一頁</td>';
		$dat .= '
</tr></table>
</div>

';
		foot($dat);
		echo $dat;
	}

	/* 最佳化圖顯示尺寸 */
	function OptimizeImageWH($w, $h){
		if($w > MAX_RW || $h > MAX_RH){
			$W2 = MAX_RW / $w; $H2 = MAX_RH / $h;
			$tkey = ($W2 < $H2) ? $W2 : $H2;
			$w = ceil($w * $tkey); $h = ceil($h * $tkey);
		}
		return 'width: '.$w.'px; height: '.$h.'px;';
	}
}