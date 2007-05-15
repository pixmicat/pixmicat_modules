<?php
class mod_showhide{
	function getModuleName(){
		return 'mod_showhide';
	}

	function getModuleVersionInfo(){
		return 'mod_showhide : 自由隱藏顯示討論串 v070506';
	}

	function autoHookHead(&$txt){
		$txt .= '<script type="text/javascript" src="module/jquery-latest.pack.js"></script>
<script type="text/javascript" src="module/mod_showhide.pack.js"></script>'."\n";
	}
}
?>