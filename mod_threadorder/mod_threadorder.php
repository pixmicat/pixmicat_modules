<?php
class mod_threadorder{
	var $TOPMOST_LOG,$BOTTOMMOST_LOG;
	function mod_threadorder(){
		$this->TOPMOST_LOG = './topmost.log'; // 置頂紀錄檔位置
		$this->BOTTOMMOST_LOG = './buttommost.log'; // 置底紀錄檔位置
	}

	function getModuleName(){
		return 'mod_threadorder : 討論串置頂置底';
	}

	function getModuleVersionInfo(){
		return 'v080222';
	}

	function _write($file,$data) {
		$rp = fopen($file, "w");
		flock($rp, LOCK_EX); // 鎖定檔案
		@fputs($rp,$data);
		flock($rp, LOCK_UN); // 解鎖
		fclose($rp);
		chmod($file,0666);
	}

	function autoHookThreadOrder($resno,$page_num,$single_page,&$threads){
		if($logs=@file($this->TOPMOST_LOG)) { // order asc
			foreach($logs as $tm) {
			    $temp = array_search( $tm=trim($tm), $threads );
			    if($temp !== NULL || $temp !== FALSE) {
					array_splice($threads, $temp, 1);
					array_unshift($threads, $tm);
				}
			}
		}
		if($logs=@file($this->BOTTOMMOST_LOG)) { // order asc
			foreach($logs as $bm) {
			    $temp = array_search( $bm=trim($bm), $threads );
			    if($temp !== NULL || $temp !== FALSE) {
					array_splice($threads, $temp, 1);
					array_push($threads, $bm);
				}
			}
		}
	}
//		die(print_r($threads,true));

}