<?php
/*
mod_imgsizelimit.php
*/

class mod_imgsizelimit{
	var $MIN_W, $MIN_H, $MIN_FILESIZE, $MAX_W, $MAX_H;

	function mod_imgsizelimit(){
		$this->MIN_W = 320; // 最小寬度 (0為不限制)
		$this->MIN_H = 240; // 最小高度 (0為不限制)
		$this->MAX_W = 5000; // 最大寬度 (0為不限制)
		$this->MAX_H = 5000; // 最大高度 (0為不限制)
		$this->MIN_FILESIZE = 0; // 最小檔案大小 (位元組, 0為不限制)
	}

	function getModuleName(){
		return 'mod_imgsizelimit : 限制圖像長寬大小';
	}

	function getModuleVersionInfo(){
		return 'v110528';
	}

	function autoHookRegistBeforeCommit(&$name, &$email, &$sub, &$com, &$category, &$age, $dest, $isReply, $imgWH, &$status){
		if( ($dest !== '') && (
			($this->MIN_W && $this->MIN_W > $imgWH[2]) ||
			($this->MIN_H && $this->MIN_H > $imgWH[3]) ||
			($this->MAX_W && $imgWH[2] > $this->MAX_W) ||
			($this->MAX_H && $imgWH[3] > $this->MAX_H) ||
			($this->MIN_FILESIZE && $this->MIN_FILESIZE > filesize($dest)) )
			) { 
				unlink($dest);
				error('圖像大小不符限制。');
			}
	}
}
?>