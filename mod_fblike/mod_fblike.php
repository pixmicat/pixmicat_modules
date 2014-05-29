<?php
class mod_fblike extends ModuleHelper {
	private $site = TITLE;
	private $url ;

	public function __construct($PMS) {
		parent::__construct($PMS);
		$this->url =  fullURL().PHP_SELF.'?res=';

	}

	function mod_fblike(){
		$this->site = TITLE;
		$this->url = fullURL().PHP_SELF.'?res=';
	}

	function getModuleName(){
		return __CLASS__.' : Facebook Like Button';
	}

	function getModuleVersionInfo(){
		return 'v140529';
	}

	function autoHookHead(&$style, $isReply){
		$PIO = PMCLibrary::getPIOInstance();
		if($isReply){
			$p = $PIO->fetchPosts($isReply);
			$sub = $p[0]['sub'];
			$style .= <<< _HERE_
<meta property="og:site_name" content="{$this->site}" />
<meta property="og:title" content="{$sub}" />
<meta property="og:url" content="{$this->url}{$isReply}" />
_HERE_;
		}
	}

	function autoHookThreadPost(&$arrLabels, $post, $isReply){
		$arrLabels['{$REPLYBTN}'] .= '&nbsp;<iframe src="http://www.facebook.com/plugins/like.php?href='.urlencode($this->url.$post['no']).'&amp;layout=button_count&amp;width=90&amp;font=arial" scrolling="no" frameborder="0" allowTransparency="true" style="border: medium none; overflow: hidden; height: 20px; width: 90px; vertical-align: text-bottom;"></iframe>';
	}
}