<?php
class mod_dummy{
	var $SELF;

	/**
	 * 建構元
	 */
	function mod_dummy($PMS){
		// 自建構元取出 $PMS 變數 (6th.Release 開始加入)
		// 使用 hookModuleMethod 註冊 ModulePage，__CLASS__ 代表物件類別名稱 (本例為 mod_dummy)
		$PMS->hookModuleMethod('ModulePage', __CLASS__);
		// 取得 ModulePage URL，並存在變數裡
		$this->SELF = $PMS->getModulePageURL(__CLASS__);
	}

	/**
	 * 回傳模組名稱方法
	 * 
	 * @return 模組名稱。建議回傳格式: mod_xxx : 簡短註解
	 */
	function getModuleName(){
		return __CLASS__.' : 展示掛載點功能模組';
	}

	/**
	 * 回傳模組版本號方法
	 * 
	 * @return 模組版本號。
	 */
	function getModuleVersionInfo(){
		return 'v110403';
	}

	// 以下為 autoHookXXX 的實驗，可以觀察到各常用掛載點是如何操作
	function autoHookToplink(&$link){
		$link .= '[<a href="'.$this->SELF.'">統計</a>]'."\n";
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

	/**
	 * 模組頁面方法
	 * 
	 * 此方法為瀏覽 ModulePage URL 之後，程式會呼叫的方法。你可以在此印出屬於模組自己的內容，比如說設定項目，列表等等。
	 */
	function ModulePage(){
		echo "Welcome to my world.";
	}
}
?>