<?php
class mod_dummy{
	function mod_dummy(){
		global $PMS;
		$PMS->hookModuleMethod('ModulePage', 'mod_dummy');
	}

	function getModuleName(){
		return 'mod_dummy';
	}

	function getModuleVersionInfo(){
		return 'mod_dummy : 展示掛載點功能模組';
	}

	function ModulePage(){
		echo "Welcome to my world.";
	}

	function autoHookToplink(&$link){
		global $PMS;
		$link .= '[<a href="'.$PMS->getModulePageURL('mod_dummy').'">統計</a>]'."\n";
	}

	function autoHookPostInfo(&$txt){
		$txt .= '<li>目前線上人數：102</li>'."\n";
	}

	function autoHookThreadFront(&$txt){
		$txt .= '<div style="text-align: center;"><a href="#">[AD] 這是廣告#01！</a></div>'."\n";
	}
	
	function autoHookThreadRear(&$txt){
		$txt .= '<div style="text-align: center;"><a href="#">[AD] 這是廣告#02！</a></div>'."\n";
	}

	function autoHookFoot(&$foot){
		$foot .= '<span class="warn_txt2">本網站由 雙貓聯合站 提供資源，謹此致謝</span>'."\n";
	}
}
?>