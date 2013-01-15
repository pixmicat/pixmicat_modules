<?php
/**
 * mod_dummyNext : 範例模組的撰寫 (使用 ModuleHelper)
 *
 * @author Pixmicat! Development Team
 * @since 7th.Release
 */

class mod_dummyNext extends ModuleHelper {
	/**
	 * 建構元
	 */
	public function __construct($PMS) {
		// 必先呼叫父類別建構元
		parent::__construct($PMS);

		// 此處已不須手動註冊模組頁面，由父類別代勞

		// 註冊自訂掛載點: mod_dummy_append
		$this->addCHP(__CLASS__.'_append', array($this, 'append'));
	}

	/**
	 * 回傳模組名稱方法
	 *
	 * @return 模組名稱。建議回傳格式: mod_xxx : 簡短註解
	 */
	public function getModuleName() {
		// 使用 moduleNameBuilder 只需帶入說明
		return $this->moduleNameBuilder('展示掛載點功能模組 (ModuleHelper ver.)');
	}

	/**
	 * 回傳模組版本號方法
	 *
	 * @return 模組版本號。
	 */
	public function getModuleVersionInfo() {
		return '7th.Release (v130115)';
	}

	// 以下為 autoHookXXX 的實驗，可以觀察到各常用掛載點是如何操作
	public function autoHookToplink(&$link) {
		// 可以代為產生 Query string
		$link .= '[<a href="'.$this->getModulePageURL(
			array('name' => 'Johnny', 'age' => 16)
		).'">問好</a>]'."\n";
	}

	public function autoHookPostInfo(&$txt) {
		// 可由 self::$PMS 取得 PMS 參考 (父類別定義)
		$moduleCount = count(self::$PMS->getLoadedModules());
		$txt .= "<li>目前載入模組數： {$moduleCount}</li>\n";
	}

	public function autoHookThreadFront(&$txt) {
		$txt .= '<a href="#">[AD] 這是廣告#01！</a>'."\n";
	}

	public function autoHookThreadRear(&$txt) {
		$txt .= '<a href="#">[AD] 這是廣告#02！</a>'."\n";
	}

	public function autoHookFoot(&$foot) {
		$foot .= '<span class="warn_txt2">本網站由 雙貓聯合站 提供資源，謹此致謝</span>'."\n";
	}

	/**
	 * 模組頁面方法
	 *
	 * 此方法為瀏覽 ModulePage URL 之後，程式會呼叫的方法。
	 * 你可以在此印出屬於模組自己的內容，比如說設定項目，列表等等。
	 */
	public function ModulePage() {
		$name = @$_GET['name'] ?: 'Anonymous';
		$greetings = "Welcome to my world, {$name}.";
		// 嘗試呼叫 mod_dummy 自訂的 mod_dummy_append 掛載點，對 $greetings 做出更動
		// 需要 mod_dummy 有載入
		$this->callCHP('mod_dummy_append', array(&$greetings));
		echo $greetings;
	}
}