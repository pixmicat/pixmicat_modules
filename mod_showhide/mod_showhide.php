<?php
class mod_showhide{
	function getModuleName(){
		return 'mod_showhide : 自由隱藏顯示討論串';
	}

	function getModuleVersionInfo(){
		return '4th.Release.2 (v071112)';
	}

	function autoHookHead(&$txt, $isReply){
		$txt .= '<script type="text/javascript" src="module/jquery-latest.pack.js"></script>
<script type="text/javascript" src="module/mod_showhide.pack.js"></script>'."\n";
	}
}
?>