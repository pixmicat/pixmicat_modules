<?php
class mod_vipquality{
	
	function mod_vipquality(){
	    list($usec, $sec) = explode(" ", microtime());
	    mt_srand(intval($usec*1000 + $sec));
    }

	function getModuleName(){
		return 'mod_vipquality : VIPクオリティ(子集)';
	}

	function getModuleVersionInfo(){
		return 'v071119';
	}

	function autoHookRegistBeforeCommit(&$name, &$email, &$sub, &$com, &$category, &$age, $dest, $isReply, $imgWH, &$status){
		if(strpos($name,"!dama")!==false) $this->_dama($name);
		if(strpos($name,"!omikuji")!==false) $this->_omikuji($name);
		if(strpos($name,"!uptime")!==false) $this->_uptime($name);
		if(strpos($name,"!power")!==false) $this->_power($name);
		if(strpos($name,"fusianasan")!==false) $name=str_replace("fusianasan","<span class='nor'>".gethostbyaddr($_SERVER['REMOTE_ADDR'])."</span>",$name);
		if(strpos($name,"mokorikomo")!==false) $name=str_replace("mokorikomo","<span class='nor'>".$_SERVER['REMOTE_ADDR']."</span>",$name);
	}

	function _uptime(&$name) {
		$loadavg='';
		if(strtoupper(substr(PHP_OS, 0, 3))!='WIN') {
			preg_match_all('/\d\.\d\d/',exec('uptime',$t1,$t2),$loadavg);
			$loadavg=$loadavg[0][2];
		}
		$name=str_replace("!uptime",$loadavg?"<span class='nor'>(LA:".$loadavg.")</span>":"",$name);
		unset($t1);unset($t2);
	}
	function _power(&$name) {
		$name=str_replace("!power","<span class='nor'>(Lv:".mt_rand(1,999).")</span>",$name);
	}
	function _dama(&$name) {
		$name=str_replace("!dama",(gmdate("j",time()+TIME_ZONE*60*60)==1?"<span class='nor'>【".(int)(mt_rand(0,1000)*(mt_rand(100,1000)/100)*1.1)."円】</span>":""),$name);
	}
	function _omikuji(&$name) {
		$omikuji = array('大吉','中吉','吉','小吉','末吉','凶','大凶','豚','ぴょん吉','だん吉','神','女神');
		$omi_v=mt_rand(0,8510);
		switch($omi_v){
		case ($omi_v<2000):
			$omi_v=0;
			break;
		case ($omi_v>=2000 && $omi_v<3500):
			$omi_v=1;
			break;
		case ($omi_v>=3500 && $omi_v<4500):
			$omi_v=2;
			break;
		case ($omi_v>=4500 && $omi_v<5500):
			$omi_v=3;
			break;
		case ($omi_v>=5500 && $omi_v<6000):
			$omi_v=4;
			break;
		case ($omi_v>=6000 && $omi_v<6500):
			$omi_v=5;
			break;
		case ($omi_v>=6500 && $omi_v<7000):
			$omi_v=6;
			break;
		case ($omi_v>=7000 && $omi_v<7500):
			$omi_v=7;
			break;
		case ($omi_v>=7500 && $omi_v<8000):
			$omi_v=8;
			break;
		case ($omi_v>=8000 && $omi_v<8500):
			$omi_v=9;
			break;
		case ($omi_v>=8500 && $omi_v<8505):
			$omi_v=10;
			break;
		case ($omi_v>=8505 && $omi_v<=8510):
			$omi_v=11;
			break;
		}
		$name=str_replace("!omikuji","<span class='nor'>【".$omikuji[$omi_v]."】</span>",$name);
	}


}
?>