<?php
/*
mod_rss : 提供RSS Feed訂閱服務
By: scribe
*/

class mod_rss{
	var $FEED_COUNT, $FEED_STATUSFILE, $FEED_CACHEFILE, $FEED_DISPLAYTYPE, $BASEDIR, $SELF;

	function mod_rss(){
		global $PMS;

		$this->FEED_COUNT = 10; // RSS 產生最大篇數
		$this->FEED_UPDATETYPE = 1; // RSS 更新時機 (1: 瀏覽 MODULEPAGE 時更新, 2: 有新文章貼出時更新)
		$this->FEED_DISPLAYTYPE = 'T'; // 資料取出形式 (T: 討論串取向, P: 文章取向)
		$this->FEED_CACHEFILE = 'rss.xml'; // 資料輸出暫存檔 (靜態快取Feed格式)

		$this->BASEDIR = fullURL(); // 基底 URL
		switch($this->FEED_UPDATETYPE){
			case 1: // MODULEPAGE
				$PMS->hookModuleMethod('ModulePage', __CLASS__); // 註冊獨立頁面
				$this->SELF = $this->BASEDIR.$PMS->getModulePageURL(__CLASS__); // RSS 連結
				$this->FEED_STATUSFILE = __CLASS__.'.tmp'; // 資料狀態暫存檔 (檢查資料需不需要更新)
				break;
			case 2: // Update on RegistAfterCommit
				$this->SELF = $this->BASEDIR.$this->FEED_CACHEFILE; // RSS 連結
				break;
		}
	}

	function getModuleName(){
		return __CLASS__.' : 提供RSS Feed訂閱服務';
	}

	function getModuleVersionInfo(){
		return '4th.Release.3 (v110330)';
	}

	/* 在頁面加入指向 RSS 的 <link> 標籤*/
	function autoHookHead(&$txt, $isReply){
		$txt .= '<link rel="alternate" type="application/rss+xml" title="RSS 2.0 Feed" href="'.$this->SELF.'" />'."\n";
	}

	/* 文章儲存後更新 RSS 檔案 ($this->FEED_UPDATETYPE == 2 觸發) */
	function autoHookRegistAfterCommit(){
		global $PIO;
		if($this->FEED_UPDATETYPE == 2){
			$PIO->dbPrepare();
			$this->GenerateCache(); // 更新 RSS
		}
	}

	function autoHookFoot(&$foot){
		$foot .= '<div style="position: absolute; top: 10px; left: 10px;"><a href="'.$this->SELF.'">RSS feed</a></div>
';
	}

	/* 模組獨立頁面 */
	function ModulePage(){
		global $PIO;

		$PIO->dbPrepare();
		if($this->IsDATAUpdated()) $this->GenerateCache(); // 若資料已更新則也更新RSS Feed快取
		$this->RedirectToCache(); // 重導向到靜態快取
	}

	/* 檢查資料有沒有更新 */
	function IsDATAUpdated(){
		global $PIO;
		if(isset($_GET['force'])) return true; // 強迫更新RSS Feed

		$tmp_fsize = $PIO->getLastPostNo('afterCommit');
		$tmp_ssize = file_exists($this->FEED_STATUSFILE) ? file_get_contents($this->FEED_STATUSFILE) : 0; // 讀取狀態暫存資料
		if($tmp_fsize == $tmp_ssize) return false; // LastNo 相同，沒有更新

		$fp = fopen($this->FEED_STATUSFILE, 'w');
		stream_set_write_buffer($fp, 0); // 立刻寫入不用緩衝
		flock($fp, LOCK_EX); // 鎖定
		fwrite($fp, $tmp_fsize); // 更新
		flock($fp, LOCK_UN); // 解鎖
		fclose($fp);
		@chmod($this->FEED_STATUSFILE, 0666); // 可讀可寫
		return true; // 有更新過
	}

	/* 生成 / 更新靜態快取RSS Feed檔案 */
	function GenerateCache(){
		global $PIO, $FileIO;
		$RFC_timezone = ' '.(TIME_ZONE < 0 ? '-' : '+').substr('0'.abs(TIME_ZONE), -2).'00'; // RFC標準所用之時區格式

		switch($this->FEED_DISPLAYTYPE){
			case 'T':
				$plist = $PIO->fetchThreadList(0, $this->FEED_COUNT); // 取出前n筆討論串首篇編號
				$plist_count = count($plist);
				// 為何這樣取？避免 SQL-like 自動排序喪失時間順序
				$post = array();
				for($p = 0; $p < $plist_count; $p++){
					$post[] = current($PIO->fetchPosts($plist[$p])); // 取出編號文章資料
				}
				break;
			case 'P':
				$plist = $PIO->fetchPostList(0, 0, $this->FEED_COUNT); // 取出前n筆文章編號
				$post = $PIO->fetchPosts($plist);
				break;
		}
		$post_count = count($post);
		// RSS Feed內容
		$tmp_c = '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
<title>'.TITLE.'</title>
<link>'.$this->BASEDIR.'</link>
<description>'.TITLE.'</description>
<language>zh-TW</language>
<generator>'.$this->getModuleName().' '.$this->getModuleVersionInfo().'</generator>
<atom:link href="'.$this->SELF.'" rel="self" type="application/rss+xml" />
';
		for($i = 0; $i < $post_count; $i++){
			$imglink = ''; // 圖檔
			$resto = 0; // 回應
			list($no, $resto, $time, $tw, $th, $tim, $ext, $sub, $com) = array(
				$post[$i]['no'],
				$post[$i]['resto'],
				substr($post[$i]['tim'], 0, -3),
				$post[$i]['tw'],
				$post[$i]['th'],
				$post[$i]['tim'],
				$post[$i]['ext'],
				$post[$i]['sub'],
				$post[$i]['com']);

			// 處理資料
			if($ext && $FileIO->imageExists($tim.'s.jpg'))
				$imglink = '<img src="'.$FileIO->getImageURL($tim.'s.jpg').'" alt="'.$tim.$ext.'" width="'.$tw.'" height="'.$th.'" /><br />';
			$time = gmdate("D, d M Y H:i:s", $time + TIME_ZONE * 60 * 60).$RFC_timezone; // 本地時間RFC標準格式
			$reslink = $this->BASEDIR.PHP_SELF.'?res='.($resto ? $resto : $no); // 回應連結
			switch($this->FEED_DISPLAYTYPE){
				case 'T':
					$titleBar = $sub.' No.'.$no.' (Res: '.($PIO->postCount($no) - 1).')'; // 標題 No.編號 (Res:回應數)
					break;
				case 'P':
					$titleBar = $sub.' ('.$no.')'; // 標題 (編號)
					break;
			}

			$tmp_c .= '<item>
	<title>'.$titleBar.'</title>
	<link>'.$reslink.'</link>
	<description>
	<![CDATA[
'.$imglink.$com.'
	]]>
	</description>
	<comments>'.$reslink.'</comments>
	<guid isPermaLink="true">'.$reslink.'#r'.$no.'</guid>
	<pubDate>'.$time.'</pubDate>
</item>
';
		}
		$tmp_c .= '</channel>
</rss>';
		$fp = fopen($this->FEED_CACHEFILE, 'w');
		flock($fp, LOCK_EX); // 鎖定
		fwrite($fp, $tmp_c); // 更新
		flock($fp, LOCK_UN); // 解鎖
		fclose($fp);
		@chmod($this->FEED_CACHEFILE, 0666); // 可讀可寫
	}

	/* 重導向到靜態快取 */
	function RedirectToCache(){
		header('HTTP/1.1 302 Moved Temporarily'); // 暫時性導向
		header('Location: '.$this->BASEDIR.$this->FEED_CACHEFILE);
	}
}
?>