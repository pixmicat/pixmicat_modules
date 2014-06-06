<?php
class mod_userrepair extends ModuleHelper {
	private $SELF;

	public function __construct($PMS) {
		parent::__construct($PMS);
		$this->SELF = $this->getModulePageURL();
	}

	public function autoHookThreadFront(&$txt,$isReply){
		if(!$isReply) $txt.='<div style="text-align: right">[<a href="'.$this->SELF.'" rel="nofollow">討論串不見了？按一下這裡吧。</a>]</div>';
		else $txt.='<div style="text-align: right">[<a href="'.$this->SELF.'&amp;res='.$isReply.'" rel="nofollow">文章不見了？按一下這裡吧。</a>]</div>';
	}

	/* 模組獨立頁面 */
	public function ModulePage(){
		$PIO = PMCLibrary::getPIOInstance();
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
	public function getModuleName(){
		return 'mod_userrepair : 使用者自行修復';
	}

	/* Get the module version infomation */
	public function getModuleVersionInfo(){
		return 'Pixmicat! User Repair Module 7th Dev v140606';
	}
}