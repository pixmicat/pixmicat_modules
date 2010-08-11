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
		return 'mod_tripcheck : Trip限制模組';
	}

	function getModuleVersionInfo(){
		return 'v071119';
	}

	function autoHookRegistBeforeCommit(&$name, &$email, &$sub, &$com, &$category, &$age, $dest, $isReply, $imgWH, &$status){
		$trip='';
		if(($trippos=strpos($name,_T('trip_pre')))!==false) {
			$trip=substr($name,$trippos+strlen(_T('trip_pre')),10);
			if((!$isReply && $this->TRIPPOST_THREAD && $this->TRIPPOST_THREAD_REGED)||($isReply && $this->TRIPPOST_REPLY && $this->TRIPPOST_REPLY_REGED)) {
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
			if(!$isReply && $this->TRIPPOST_THREAD) error("發文需要Trip",$dest);
			if($isReply && $this->TRIPPOST_REPLY) error("回文需要Trip",$dest);
		}
	}

	function autoHookLinksAboveBar(&$link, $pageId, $addinfo=false) {
		if($pageId == 'admin') $link.=' [<a href="tripadmin.php">Trip管理</a>]';
	}

	function autoHookAuthenticate($pass, $act, &$result){
		if(!$result) $result = ($this->_tripPermission($this->_tripping(substr($pass,1))) == 'OK');
	}

	function _tripCheck($trip) {
		$res='NF';
		if(!$this->_tripFormat($trip)) return $res;
		$TripList = @file($this->TRIPFILE);

		if(is_array($TripList)) {
			foreach($TripList as $tripline){
				@list($szTrip,$szTime,$szIP,$szActivate,$szBan) = @explode("<>", trim($tripline));
				if($trip==$szTrip && $szActivate && !$szBan) {$res='OK'; break;}
				elseif($trip==$szTrip && !$szActivate) {$res='NA'; break;}
				elseif($trip==$szTrip && $szBan) {$res='BN'; break;}
			}
		}
		return $res;
	}

	function _tripPermission($trip) {
		$res='NF';
		if(!$this->_tripFormat($trip)) return $res;
		$TripList = @file($this->TRIPFILE);

		if(is_array($TripList)) {
			foreach($TripList as $tripline){
				@list($szTrip,$szTime,$szIP,$szActivate,$szBan,$szDelPerm) = @explode("<>", trim($tripline));
				if($trip==$szTrip && $szActivate && !$szBan && $szDelPerm) {$res='OK'; break;}
				else {$res='NG'; break;}
			}
		}
		return $res;
	}

	function _tripFormat($trip) {
		return strlen($trip) == 10;
	}

	function _tripping($str) {
		$salt = preg_replace('/[^\.-z]/', '.', substr($str.'H.', 1, 2));
		$salt = strtr($salt, ':;<=>?@[\\]^_`', 'ABCDEFGabcdef');
		return substr(crypt($str, $salt), -10);
	}

}
?>