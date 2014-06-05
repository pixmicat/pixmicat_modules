<?php
class mod_plusone extends ModuleHelper {
	private $site = TITLE;
	private $url;

	public function __construct($PMS) {
		parent::__construct($PMS);
		$this->url = fullURL().PHP_SELF.'?res=';
	}
	
	public function getModuleName(){
		return 'mod_plusone : Google +1 Button';
	}

	public function getModuleVersionInfo(){
		return '7th Release v140605';
	}

	public function autoHookHead(&$style, $isReply){
		$PIO = PMCLibrary::getPIOInstance();
		$FileIO = PMCLibrary::getFileIOInstance();; 
		if($isReply){
			$p = $PIO->fetchPosts($isReply);
			$sub = $p[0]['sub'];
			$com = htmlentities(str_cut($p[0]['com'], 100), ENT_QUOTES, 'UTF-8');
			$thumb = '';
			if($p[0]['ext'] != ''){
				$thumb = $FileIO->resolveThumbName($p[0]['tim']); // 檢查是否有預覽圖可以顯示
				$thumb = $thumb ? $FileIO->getImageURL($thumb) : '';
			}
			$style .= <<< _HERE_
<meta property="og:image" content="{$thumb}" />
<meta property="og:title" content="{$sub} - {$this->site}" />
<meta property="og:description" content="{$com}" />
_HERE_;
		}
	}

	public function autoHookFoot(&$foot){
		$foot .= '
<script type="text/javascript" src="https://apis.google.com/js/plusone.js">
  {lang: "zh-TW"}
</script>
';
	}

	public function autoHookThreadPost(&$arrLabels, $post, $isReply){
		$arrLabels['{$REPLYBTN}'] .= '&#xA0;<div class="g-plusone" data-width="30px" data-size="small" data-href="'.$this->url.$post['no'].'"></div>';
	}
}//End of Module