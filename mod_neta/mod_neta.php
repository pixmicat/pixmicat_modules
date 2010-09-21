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
		return '5th.Release.2-dev (v100921)';
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
		$tmp_len = strlen($this->NETA_LABEL.'<br />');
		if(($tmp_pos = strpos($arrLabels['{$COM}'], $this->NETA_LABEL.'<br />')) !== false){
			$arrLabels['{$COM}'] = substr_replace($arrLabels['{$COM}'], '<div class="hide" id="netacoll'.$arrLabels['{$NO}'].'">', $tmp_pos, $tmp_len).'</div>';
			$arrLabels['{$WARN_BEKILL}'] .= '<span class="warn_txt2" id="netabar'.$arrLabels['{$NO}'].'">這篇文章可能含有劇情洩漏，要閱讀全文請按下<a href="#">此處</a>展開。<br /></span>'."\n";
		}
	}
}
?>