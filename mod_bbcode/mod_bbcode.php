<?php
class mod_bbcode{
	var $ImgTagTagMode, $URLTagMode, $MaxURLCount, $URLTrapLog;

	function mod_bbcode(){
		$this->ImgTagTagMode = 1; // [img]標籤行為 (0:不轉換 1:無貼圖時轉換 2:常時轉換)
		$this->URLTagMode = 1; // [url]標籤行為 (0:不轉換 1:正常)
		$this->MaxURLCount = 2; // [url]標籤上限 (超過上限時標籤為陷阱標籤[寫入至$URLTrapLog])
		$this->URLTrapLog = './URLTrap.log'; // [url]陷阱標籤記錄檔
	}

	function getModuleName(){
		return 'mod_bbcode';
	}

	function getModuleVersionInfo(){
		return 'mod_bbcode : 內文BBCode轉換';
	}

	function autoHookPostInfo(&$postinfo){
		$postinfo .= '<li>可使用 BBCode</li>'."\n";
	}

	function autoHookRegistBeforeCommit(&$name, &$email, &$sub, &$com, &$category, &$age, $dest, $resto, $imgWH){
		$com=$this->_bb2html($com,$dest);
	}

	function _bb2html($string,$dest) {
		$urlcount=0;
		$string = preg_replace("/\[b\](.*?)\[\/b\]/si", "<b>\\1</b>", $string);
		$string = preg_replace("/\[i\](.*?)\[\/i\]/si", "<i>\\1</i>", $string);
		$string = preg_replace("/\[u\](.*?)\[\/u\]/si", "<u>\\1</u>", $string);
		$string = preg_replace("/\[p\](.*?)\[\/p\]/si", "<p>\\1</p>", $string);

		$string = preg_replace("/\[color=(\S+?)\](.*?)\[\/color\]/si",
			"<font color=\"\\1\">\\2</font>", $string);

		$string = preg_replace("/\[s([1-7])\](.*?)\[\/s([1-7])\]/si",
			"<font size=\"\\1\">\\2</font>", $string);

		$string = preg_replace("/\[pre\](.*?)\[\/pre\]/si", "<pre>\\1</pre>", $string);
		$string = preg_replace("/\[quote\](.*?)\[\/quote\]/si", "<blockquote>\\1</blockquote>", $string);

		if($this->URLTagMode) {
			
			if(preg_match_all("/\[url\](http|https|ftp)(:\/\/\S+?)\[\/url\]/si", $string, $matches, PREG_SET_ORDER)){
				$urlcount+=count($matches);
				foreach($matches as $submatches){
					$string = @str_replace($submatches[0], "<a href=\"$submatches[1]$submatches[2]\" target=\"_blank\">$submatches[1]$submatches[2]</a>", $string);
				}
			}

			if(preg_match_all("/\[url\](\S+?)\[\/url\]/si", $string, $matches, PREG_SET_ORDER)){
				$urlcount+=count($matches);
				foreach($matches as $submatches){
					$string = @str_replace($submatches[0], "<a href=\"http://$submatches[1]\" target=\"_blank\">$submatches[1]</a>", $string);
				}
			}

			if(preg_match_all("/\[url=(http|https|ftp)(:\/\/\S+?)\](.*?)\[\/url\]/si", $string, $matches, PREG_SET_ORDER)){
				$urlcount+=count($matches);
				foreach($matches as $submatches){
					$string = @str_replace($submatches[0], "<a href=\"$submatches[1]$submatches[2]\" target=\"_blank\">$submatches[3]</a>", $string);
				}
			}

			if(preg_match_all("/\[url=(\S+?)\](\S+?)\[\/url\]/si", $string, $matches, PREG_SET_ORDER)){
				$urlcount+=count($matches);
				foreach($matches as $submatches){
					$string = @str_replace($submatches[0], "<a href=\"http://$submatches[1]\" target=\"_blank\">$submatches[2]</a>", $string);
				}
			}
			$this->_URLExcced($urlcount);
		}

		$string = preg_replace("/\[email\](\S+?@\S+?\\.\S+?)\[\/email\]/si",
			"<a href=\"mailto:\\1\">\\1</a>", $string);

		$string = preg_replace("/\[email=(\S+?@\S+?\\.\S+?)\](.*?)\[\/email\]/si",
			"<a href=\"mailto:\\1\">\\2</a>", $string);
		if (($this->ImgTagTagMode == 2) || ($this->ImgTagTagMode && !$dest)) {
			$string = preg_replace("#\[img\](([a-z]+?)://([^ \n\r]+?))\[\/img\]#si",
				"<img src=\"\\1\" border=\"0\" alt=\"\\1\" />", $string);
		} 

		return $string;
	}
	
	function _URLExcced($cnt) {
		if($cnt > $this->MaxURLCount) {
		  	  $fh=fopen($this->URLTrapLog,'a+b');
		  	  fwrite($fh,time()."\t$_SERVER[REMOTE_ADDR]\t$cnt\n");
		  	  fclose($fh);
		  	  error("[url]標籤超過上限");
		}
	}
}
?>