<?php
class mod_plusone{
	var $site, $url;
	function mod_plusone(){
		$this->site = TITLE;
		$this->url = fullURL().PHP_SELF.'?res=';
	}

	function getModuleName(){
		return __CLASS__.' : Google +1 Button';
	}

	function getModuleVersionInfo(){
		return 'v110813';
	}

	function autoHookHead(&$style, $isReply){
		global $PIO, $FileIO;
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

	function autoHookFoot(&$foot){
		$foot .= '
<script type="text/javascript" src="https://apis.google.com/js/plusone.js">
  {lang: "zh-TW"}
</script>
';
	}


	function autoHookThreadPost(&$arrLabels, $post, $isReply){
		$arrLabels['{$REPLYBTN}'] .= '&#xA0;<div class="g-plusone" data-size="small" data-href="'.$this->url.$post['no'].'"></div>';
	}
}
?>