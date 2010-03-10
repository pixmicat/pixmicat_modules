<?php
/*
mod_imgsizelimit.php
*/

class mod_imgsizelimit{
	var $MIN_W, $MIN_H, $MIN_FILESIZE;

	function mod_imgsizelimit(){
		$this->MIN_W = 320; // 最小寬度 (0為不限制)
		$this->MIN_H = 240; // 最小高度 (0為不限制)
		$this->MIN_FILESIZE = 0; // 最小檔案大小 (位元組, 0為不限制)
	}

	function getModuleName(){
		return 'mod_imgsizelimit : 限制圖像最小大小';
	}

	function getModuleVersionInfo(){
		return 'v100310';
	}

	function autoHookRegistBeforeCommit(&$name, &$email, &$sub, &$com, &$category, &$age, $dest, $isReply, $imgWH, &$status){
		if( $dest &&
			($this->MIN_W && $this->MIN_W > $imgWH[2]) ||
			($this->MIN_H && $this->MIN_H > $imgWH[3]) ||
			($this->MIN_FILESIZE && $this->MIN_FILESIZE > filesize($dest))
			) { 
				unlink($dest);
				error('圖像大小小於限制。');
			}
	}
}
?>