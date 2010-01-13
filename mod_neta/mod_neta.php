<?php
class mod_neta{
	var $NETA_LABEL;

	function mod_neta(){
		$this->NETA_LABEL = '[NETABARE_ARI]';
	}

	function getModuleName(){
		return 'mod_neta : 劇情洩漏隱藏';
	}

	function getModuleVersionInfo(){
		return '4th.Release.2 (v100113)';
	}

	function autoHookHead(&$dat){
		$dat .= '<script type="text/javascript">
// <![CDATA[
jQuery(function($){
	$(\'div.threadpost, div.reply\').find(\'span[id^=neta] > a\').bind(\'click\', function(){
		$(this).parent().parent().find(\'*[id^=neta]\')
			.filter(\'span\').hide().end()
			.filter(\'div\').slideDown();
		return false;
	});
});
// ]]>
</script>
';
	}

	function autoHookPostInfo(&$postinfo){
		$postinfo .= '<li><b>要捏他，請在內文使用<span style="color:blue;">'.$this->NETA_LABEL.'</span>標籤註明劇情洩漏！</b></li>'."\n";
	}

	function autoHookThreadPost(&$arrLabels, $post, $isReply){
		$this->netaCollapse($arrLabels, $post, $isReply);
	}

	function autoHookThreadReply(&$arrLabels, $post, $isReply){
		$this->netaCollapse($arrLabels, $post, $isReply);
	}

	function netaCollapse(&$arrLabels, $post, $isReply){
		if(strpos($arrLabels['{$COM}'], $this->NETA_LABEL.'<br />') !== false){
			$arrLabels['{$COM}'] = str_replace($this->NETA_LABEL.'<br />', '<div class="hide" id="netacoll'.$arrLabels['{$NO}'].'">', $arrLabels['{$COM}']).'</div>';
			$arrLabels['{$WARN_BEKILL}'] .= '<span class="warn_txt2" id="netabar'.$arrLabels['{$NO}'].'">這篇文章可能含有劇情洩漏，要閱讀全文請按下<a href="#">此處</a>展開。<br /></span>'."\n";
		}
	}

	function netaHide(&$arrLabels, $post, $isReply){
		if(($tmp_pos = strpos($arrLabels['{$COM}'], $this->NETA_LABEL.'<br />')) !== false){
			if($isReply){
				$arrLabels['{$COM}'] = str_replace($this->NETA_LABEL.'<br />', '', $arrLabels['{$COM}']);
			}else{
				$arrLabels['{$COM}'] = substr($arrLabels['{$COM}'], 0, $tmp_pos);
			}
		}
		if($tmp_pos !==false && !$isReply){
			$arrLabels['{$WARN_BEKILL}'] .= '<span class="warn_txt2">這篇文章可能含有劇情洩漏，要閱讀全文請按下回應連結。</span><br />'."\n";
		}else{
			$arrLabels['{$WARN_BEKILL}'] .= '';
		}
	}
}
?>