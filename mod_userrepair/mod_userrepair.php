<?php
class mod_userrepair {
	var $SELF;

	function mod_userrepair(){
		global $PMS;
		$PMS->hookModuleMethod('ModulePage', __CLASS__); // 向系統登記模組專屬獨立頁面
		$this->SELF = $PMS->getModulePageURL(__CLASS__); // 本頁面連結
	}

	function autoHookThreadFront(&$txt,$isReply){
		if(!$isReply) $txt.='<div style="text-align: right">[<a href="'.$this->SELF.'" rel="nofollow">討論串不見了？按一下這裡吧。</a>]</div>';
		else $txt.='<div style="text-align: right">[<a href="'.$this->SELF.'&amp;res='.$isReply.'" rel="nofollow">文章不見了？按一下這裡吧。</a>]</div>';
	}

	/* 模組獨立頁面 */
	function ModulePage(){
		global $PIO;
		if(!isset($_GET['res'])) {
			if(!file_exists('./.userrepair')||isset($_GET['force'])) {
				touch('./.userrepair');
				$PIO->dbMaintanence('repair',$PIO->dbMaintanence('repair'));
				updatelog(); // 重導向到靜態快取
				unlink('./.userrepair');
				header('HTTP/1.1 302 Moved Temporarily');
				header('Location: '.fullURL().PHP_SELF2.'?'.time());
			} else {
				error('已經有其他人在修復中。<p>[<a href="'.$this->SELF.'&amp;force=1">強制執行</a>]</p>');
			}
		} else {
			if(!file_exists('./.userrepair')||isset($_GET['force'])) {
				touch('./.userrepair');
				$no=intval($_GET['res']);
				deleteCache(array($no));
				unlink('./.userrepair');
				header('HTTP/1.1 302 Moved Temporarily');
				header('Location: '.fullURL().PHP_SELF.'?res='.$no);
			} else {
				error('已經有其他人在修復中。<p>[<a href="'.$this->SELF.'&amp;res='.$_GET['res'].'&amp;force=1">強制執行</a>]</p>');
			}
		}
	}

	/* Get the name of module */
	function getModuleName(){
		return 'mod_userrepair : 使用者自行修復';
	}

	/* Get the module version infomation */
	function getModuleVersionInfo(){
		return 'Pixmicat! User Repair Module v090526';
	}

}
?>