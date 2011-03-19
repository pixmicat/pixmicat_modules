<?php
class mod_bbbutton{
	var $bbicon, $bbicon_url;
	
	function mod_bbbutton(){
		$this->bbicon = false; // 使用圖示
		$this->bbicon_url = 'bbicons/'; // 圖示位置
    }

	function getModuleName(){
		return 'mod_bbbutton';
	}

	function getModuleVersionInfo(){
		return 'mod_bbbutton : BBcode按鈕';
	}

	function autoHookHead(&$txt, $isReply){
		global $PMS;
		$txt .= '<!--script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script-->
<script src="jquery.bbcode.js" type="text/javascript"></script>
<script type="text/javascript">
var bbbuttons = { tags: {}, button_image: '.(int)$this->bbicon.', image_url: "'.$this->bbicon_url.'" };
';
		if(method_exists($PMS,'callCHP')) {
			$PMS->callCHP('mod_bbbutton_addButtons',array(&$txt));
		}

		$txt .= '
  $(document).ready(function(){
	$("#fcom").bbcode(bbbuttons);
  });
</script>';
	}

}
?>