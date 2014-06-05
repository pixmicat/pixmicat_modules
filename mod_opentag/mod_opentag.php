<?php
class mod_opentag extends ModuleHelper {
	private $mypage;

	public function __construct($PMS) {
		parent::__construct($PMS);
		$this->mypage = $this->getModulePageURL();
	}

	/* Get the name of module */
	public function getModuleName(){
		return 'mod_opentag : 開放標籤編輯';
	}

	/* Get the module version infomation */
	public function getModuleVersionInfo(){
		return '7th.Release.1-dev (v140605)';
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
		obj.siblings("a").each(function(){ tag += "," + $(this).text(); });
		obj.parent().html("<input type=\'text\' id=\'attrTag" + no + "\' size=\'28\' /><input type=\'button\' value=\'Tag!\' id=\'sendTag" + no + "\' />");
		$g("attrTag" + no).value = tag.substr(1);
		$("#sendTag" + no).click(function(){
			var tmpthis = this;
			$.post("'.str_replace('&amp;', '&', $this->mypage).'&no=" + no, {ajaxmode: true, tag: this.previousSibling.value}, function(newTag){
				newTag = $.map(newTag.split(","), function(n){
					return n.link("pixmicat.php?mode=category&amp;c=" + encodeURI(n));
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

	public function autoHookToplink(&$linkbar, $isReply){
		$linkbar .= '[<a href="'.$this->mypage.'&amp;action=tagcloud">標籤雲</a>]'."\n";
	}

	public function autoHookThreadPost(&$arrLabels, $post, $isReply){
		if(USE_CATEGORY) $arrLabels['{$CATEGORY}'] = '<span>'.$arrLabels['{$CATEGORY}'].' [<a href="'.$this->mypage.'&amp;no='.$post['no'].'" class="change">變更</a>]</span>';
	}

	public function autoHookThreadReply(&$arrLabels, $post, $isReply){
		$this->autoHookThreadPost($arrLabels, $post, $isReply);
	}

	public function ModulePage(){
		$PIO = PMCLibrary::getPIOInstance();
		$PTE = PMCLibrary::getPTEInstance(); 

		if(isset($_GET['action'])){ // 標籤雲
			require './module/wordcloud.class.php';
			$pte_vals = array('{$TITLE}'=>TITLE, '{$RESTO}'=>'');
			$dat = $PTE->ParseBlock('HEADER', $pte_vals);
			$dat .= '<style type="text/css">
.word { padding: 4px 4px 4px 4px; letter-spacing: 3px; text-decoration: none; font-weight: normal; }
.size9 { color: #000 !important; font-size: 200%; }
.size8 { color: #111 !important; font-size: 170%; }
.size7 { color: #222 !important; font-size: 150%; }
.size6 { color: #333 !important; font-size: 120%; }
.size5 { color: #444 !important; font-size: 110%; }
.size4 { color: #555 !important; font-size: 100%; }
.size3 { color: #666 !important; font-size: 90%; }
.size2 { color: #777 !important; font-size: 80%; }
.size1 { color: #888 !important; font-size: 70%; }
.size0 { color: #999 !important; font-size: 60%; }
</style>
</head>
<body id="main">';

			$p = $PIO->fetchPosts($PIO->fetchPostList());
			$cloud = new wordCloud();
			foreach($p as $pp){
				if($pp['category']){
					$pp['category'] = substr(str_replace(array(',', '&#44;'), ' ', $pp['category']), 1, -1);
					$cloud->addString($pp['category']);
				}
			}

			$myCloud = $cloud->showCloud('array');
			if(is_array($myCloud)){
				foreach ($myCloud as $key => $value){
					$dat .= '<a href="./pixmicat.php?mode=category&c='.urlencode($value['word']).'" class="word size'.$value['range'].'">'.$value['word'].'</a>'."\n";
				}
			}
			echo $dat."</body></html>";
			return;
		}
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

