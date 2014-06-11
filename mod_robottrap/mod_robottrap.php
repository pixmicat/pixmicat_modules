<?php
/*
mod_robottrap : 機器人及砍站程式陷阱程式
由 scribe 略改自しおからｐｈｐスクリプト的「畫像一括ダウンロードロボット‧排除用トラップ」
*/
class mod_robottrap extends ModuleHelper {
	private $DENYLIST_FILE = './denylist.txt';// 封鎖名單列表檔案

	public function __construct($PMS) {
		parent::__construct($PMS);
	}

	/* Get the name of module */
	public function getModuleName(){
		return 'mod_robottrap : 機器人及砍站程式陷阱程式';
	}

	/* Get the module version infomation */
	public function getModuleVersionInfo(){
		return '7th.Release (v140606)';
	}

	/* 自動掛載陷阱點 */
	public function autoHookThreadFront(&$dat, $isReply){
		$dat .= '<a href="imglist.htm"></a><a href="src/11266284o2168.jpg"></a>'."\n";
	}

	/* 自動掛載陷阱點 */
	public function autoHookThreadRear(&$dat, $isReply){
		$dat .= '<a href="allimage.htm"></a><a href="src/113145915o542.jpg"></a>'."\n";
	}

	/* 加入封鎖名單 */
	public function ModulePage(){
		$fp = fopen('./.htaccess', 'a');
		$remoteaddr =getREMOTE_ADDR(); 
		$denyip = 'Deny from '.$remoteaddr."\n"; // 加入一行新封鎖設定
		
		fwrite($fp, $denyip);
		fclose($fp);

		$fp = fopen($this->DENYLIST_FILE, 'a');
		$denytime = gmdate('y/m/d H:i:s', time() + TIME_ZONE * 3600);
		$denytxt = $denytime."\t".$remoteaddr."\t".gethostbyaddr(remoteaddr)."\t".$_SERVER['HTTP_USER_AGENT']."\n"; // 加入一行新記錄
		fwrite($fp, $denytxt);
		fclose($fp);

		echo 'Denied from '.$remoteaddr."\n";
	}
}//End of Module
