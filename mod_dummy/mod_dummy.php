<?php
/**
 * mod_dummy : 範例模組的撰寫
 *
 * @author Pixmicat! Development Team
 * @since 7th.Release
 */

class mod_dummy {
	private static $PMS;
	private static $SELF;

	/**
	 * 建構元
	 */
	public function __construct($PMS) {
		// 自建構元取出 $PMS 變數 (6th.Release 開始加入)
		// 暫存 PMS 以供日後參考
		self::$PMS = $PMS;

		// 使用 hookModuleMethod 註冊 ModulePage
		// __CLASS__ 代表物件類別名稱 (本例為 mod_dummy)
		$PMS->hookModuleMethod('ModulePage', __CLASS__);
		// 取得 ModulePage URL，並存在變數裡
		self::$SELF = $PMS->getModulePageURL(__CLASS__);

		// 註冊自訂掛載點: mod_dummy_append
		$PMS->addCHP(__CLASS__.'_append', array($this, 'append'));
	}

	/**
	 * 回傳模組名稱方法
	 *
	 * @return 模組名稱。建議回傳格式: mod_xxx : 簡短註解
	 */
	public function getModuleName() {
		return __CLASS__.' : 展示掛載點功能模組';
	}

	/**
	 * 回傳模組版本號方法
	 *
	 * @return 模組版本號。
	 */
	public function getModuleVersionInfo() {
		return '7th.Release (v130112)';
	}

	// 以下為 autoHookXXX 的實驗，可以觀察到各常用掛載點是如何操作
	public function autoHookToplink(&$link) {
		$link .= '[<a href="'.self::$SELF.'">統計</a>]'."\n";
	}

	public function autoHookPostInfo(&$txt) {
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
		echo "Welcome to my world.";
	}

	/**
	 * 自訂掛載點方法。需要在建構元註冊
	 *
	 * 這裡示範可以讓其他模組傳值，並附加一段字。
	 * 也就是藉由自訂掛載點分享自己模組的功能給其他模組使用，讓其他模組更強。
	 *
	 * @param  string $message 訊息
	 */
	public function append(&$message) {
		$message .= ' (append)';
	}
}