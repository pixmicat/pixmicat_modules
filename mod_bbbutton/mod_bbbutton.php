<?php
class mod_bbbutton extends ModuleHelper {
	private $bbicon=false;
	private $bbicon_url='bbicons/';
	
	public function __construct($PMS) {
		parent::__construct($PMS);
		$this->myPage = $this->getModulePageURL();// 基底位置
	}

	public function getModuleName(){
		return 'mod_bbbutton';
	}

	public function getModuleVersionInfo(){
		return 'mod_bbbutton : BBcode按鈕';
	}

	public function autoHookHead(&$txt, $isReply){
//如果不需要JQUERY可以COMMENT 第一行，第一行為自動決定加載JQUERY的JAVASCRIPT
		$txt .= '<script type="text/javascript">window.jQuery || document.write("\x3Cscript src=\x22//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js\x22>\x3C/script>");</script>
<script src="jquery.bbcode.js" type="text/javascript"></script>
<script type="text/javascript">';
		if(method_exists(self::$PMS,'callCHP')) {
			self::$PMS->callCHP('mod_bbbutton_addButtons',array(&$txt));
		}
		$txt .= '
  $(document).ready(function(){
	$("#fcom").bbcode( { tags: {}, button_image: '.($this->bbicon?'true':'false').', image_url: "'.$this->bbicon_url.'" });
  });
</script>';
	}

}//End of Module
