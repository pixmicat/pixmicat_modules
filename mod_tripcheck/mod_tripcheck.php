<?php
class mod_tripcheck{
	var $TRIPFILE,$TRIPPOST_THREAD,$TRIPPOST_THREAD_REGED,$TRIPPOST_REPLY,$TRIPPOST_REPLY_REGED;
	
	function mod_tripcheck(){
		$this->TRIPFILE = 'board.trip'; // トリップ記錄檔檔名
		$this->TRIPPOST_THREAD = 1; // 發新討論串需要トリップ (是：1 否：0)
		$this->TRIPPOST_THREAD_REGED = 1; // 發新討論串需要已登錄的トリップ (是：1 否：0)
		$this->TRIPPOST_REPLY = 0; // 回文需要トリップ (是：1 否：0)
		$this->TRIPPOST_REPLY_REGED = 0; //  回文需要已登錄的トリップ (是：1 否：0)
	}

	function getModuleName(){
		return 'mod_tripcheck';
	}

	function getModuleVersionInfo(){
		return 'mod_tripcheck : Trip限制模組';
	}

	function autoHookRegistBeforeCommit(&$name, &$email, &$sub, &$com, &$category, &$age, $dest, $resto, $imgWH){
		$trip='';
		if(($trippos=strpos($name,_T('trip_pre')))!==false) {
			$trip=substr($name,$trippos+strlen(_T('trip_pre')));
			if((!$resto && $this->TRIPPOST_THREAD && $this->TRIPPOST_THREAD_REGED)||($resto && $this->TRIPPOST_REPLY && $this->TRIPPOST_REPLY_REGED)) {
				$tres=$this->_tripCheck($trip);
				switch($tres) {
					case 'OK':
						break;
					case 'BN':
						error("Trip已被封鎖",$dest);
					case 'NA':
						error("Trip未啟用",$dest);
					case 'NF':
						error("Trip無效",$dest);
				}
			}
		}else{
			if(!$resto && $this->TRIPPOST_THREAD) error("發文需要Trip",$dest);
			if($resto && $this->TRIPPOST_REPLY) error("回文需要Trip",$dest);
		}
	}

	function _tripCheck($trip) {
		$TripList = @file($this->TRIPFILE);
		$res='NF';

		if(is_array($TripList)) {
			foreach($TripList as $tripline){
				list($szTrip,$szTime,$szIP,$szActivate,$szBan) = explode("<>", $tripline);
				if($trip==$szTrip && $szActivate && !$szBan) {$res='OK'; break;}
				elseif($trip==$szTrip && !$szActivate) {$res='NA'; break;}
				elseif($trip==$szTrip && $szBan) {$res='BN'; break;}
			}
		}
		return $res;
	}


}
?>