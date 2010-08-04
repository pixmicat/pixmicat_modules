<?php
class mod_showip{

	function getModuleName(){
		return 'mod_showip : 顯示部份IP/hostname';
	}

	function getModuleVersionInfo(){
		return 'v100804';
	}

	function _isgTLD($last,$add='') {
		$gtld = array('biz','com','info','name','net','org','pro','aero','asia','cat','coop','edu','gov','int','jobs','mil','mobi','museum','tel','travel','xxx');
		if(is_array($add)) {
			foreach($add as $a) {
				array_unshift($gtld,$a);
			}
		}
		foreach($gtld as $tld) {
			if($last == $tld) {
				return true;
			}
		}
		return false;
	}

	function autoHookThreadPost(&$arrLabels, $post, $isReply){
		global $language, $PIO;
		$iphost = $post['host'];
		if(ip2long($iphost)!==false) {
			$arrLabels['{$NOW}'] .= '(IP:'.preg_replace('/\d+\.\d+$/','*.*',$iphost).')';
		} else { // host
			$parthost=''; $iscctld = false; $isgtld = false;

			if($iphost == 'localhost') { // localhost hack
				$arrLabels['{$NOW}'] .= '(Host:localhost)';
				return;
			}

			$cctld = array('ac','ad','ae','af','ag','ai','al','am','an','ao','aq','ar','as','at','au','aw','ax','az','ba','bb','bd','be','bf','bg','bh','bi','bj','bm','bn','bo','br','bs','bt','bw','by','bz','ca','cc','cd','cf','cg','ch','ci','ck','cl','cm','cn','co','cr','cu','cv','cx','cy','cz','de','dj','dk','dm','do','dz','ec','ee','eg','er','es','et','eu','fi','fj','fk','fm','fo','fr','ga','gd','ge','gf','gg','gh','gi','gl','gm','gn','gp','gq','gr','gs','gt','gu','gw','gy','hk','hm','hn','hr','ht','hu','id','ie','il','im','in','io','iq','ir','is','it','je','jm','jo','jp','ke','kg','kh','ki','km','kn','kp','kr','kw','ky','kz','la','lb','lc','li','lk','lr','ls','lt','lu','lv','ly','ma','mc','md','me','mg','mh','mk','ml','mm','mn','mo','mp','mq','mr','ms','mt','mu','mv','mw','mx','my','mz','na','nc','ne','nf','ng','ni','nl','no','np','nr','nu','nz','om','pa','pe','pf','pg','ph','pk','pl','pn','pr','ps','pt','pw','py','qa','re','ro','rs','ru','rw','sa','sb','sc','sd','se','sg','sh','si','sk','sl','sm','sn','sr','st','su','sv','sy','sz','tc','td','tf','tg','th','tj','tk','tl','tm','tn','to','tr','tt','tv','tw','tz','ua','ug','uk','us','uy','uz','va','vc','ve','vg','vi','vn','vu','wf','ws','ye','za','zm','zw');
			preg_match('/([\w\-]+)\.(\w+)$/',$iphost,$parts);

			if(preg_match('/on-nets$/',$parts[1])) { // on-nets IP hack
				if(preg_match('/(\d+)\-(\d+)-on-nets/',$parts[1],$ipparts))
					$parthost = $ipparts[2].'.'.$ipparts[1].'.*.on-nets.com';
				else
					$parthost = '*-on-nets.com';
			// hinet/teksavvy/qwest/mchsi/smartone-vodafone/rr/swbell/sbcglobal/acanac/ameritech/telus/charter/embarqhsd/comcast/verizon IP hack
			} elseif($parts[1] == 'hinet' || $parts[1] == 'teksavvy' || $parts[1] == 'qwest'
			 || $parts[1] == 'mchsi' || $parts[1] == 'smartone-vodafone' || $parts[1] == 'rr'
			 || $parts[1] == 'swbell' || $parts[1] == 'sbcglobal' || $parts[1] == 'acanac'
			 || $parts[1] == 'ameritech' || $parts[1] == 'telus' || $parts[1] == 'charter'
			 || $parts[1] == 'embarqhsd' || $parts[1] == 'comcast' || $parts[1] == 'verizon') {
				if(preg_match('/^[a-zA-Z\-]*(\d+\-\d+)/',$iphost,$ipparts))
					$parthost = $ipparts[1].'-*.'.$parts[0];
				else
					$parthost = '*.'.$parts[0];
			// netvigator/bbtec/HKBN IP hack
			} elseif($parts[1] == 'netvigator' || $parts[1] == 'bbtec' || $parts[1] == 'ctinets') {
				if(preg_match('/^[a-zA-Z]*(\d{3})(\d{3})/',$iphost,$ipparts))
					$parthost = intval($ipparts[1]).'.'.intval($ipparts[2]).'.*.'.$parts[0];
				else
					$parthost = '*.'.$parts[0];
			} elseif($parts[1] == 'pldt') { // pldt IP hack
				if(preg_match('/^(\d+\.\d+)/',$iphost,$ipparts))
					$parthost = $ipparts[1].'.*.'.$parts[0];
				else
					$parthost = '*.'.$parts[0];
			} elseif($parts[1] == 'comunitel') { // comunitel IP hack
				if(preg_match('/^[\w\-]*(\d+)\-(\d+)-(\d+)\-(\d+)/',$iphost,$ipparts))
					$parthost = $ipparts[4].'-'.$ipparts[3].'-*.'.$parts[0];
				else
					$parthost = '*.'.$parts[0];
			} else {
				$lastpart = strtolower($parts[2]);
				$isgtld = $this->_isgTLD($lastpart);

				if(!$isgtld) {
					foreach($cctld as $tld) {
						if($lastpart == $tld) {
							$iscctld = true;
							preg_match('/([\w\-]+)\.([\w\-]+)\.(\w+)$/',$iphost,$parts);
							$isgtld = $this->_isgTLD($parts[2],array('ac','ad','co','ed','go','gr'.'lg','ne','or','ind','ltd','nic','plc','vet')); // '.co.uk' etc. are common
							if($isgtld) {
								// kbronet/seed/so-net.net.tw/tfn/giga/lsc/canvas/tpgi/adam/iinet IP hack
								if($parts[1] == 'kbronet' || $parts[1] == 'seed' || $parts[1] == 'so-net'
								 || $parts[1] == 'tfn' || $parts[1] == 'giga' || $parts[1] == 'lsc'
								 || $parts[1] == 'canvas' || $parts[1] == 'tpgi' || $parts[1] == 'adam' || $parts[1] == 'iinet') {
									if(preg_match('/^(\d+\-\d+)/',$iphost,$ipparts))
										$parthost = $ipparts[0].'-*.'.$parts[0];
									else
										$parthost = '*.'.$parts[0];
								} elseif($parts[1] == 'tcol' || $parts[1] == 'yournet' || $parts[1] == 'm1connect') { // tcol/yournet/m1connect IP hack
									if(preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)/',$iphost,$ipparts))
										$parthost = $ipparts[4].'-'.$ipparts[3].'-*.'.$parts[0];
									else
										$parthost = '*.'.$parts[0];
								} elseif($parts[1] == 'eaccess') { // eaccess IP hack
									if(preg_match('/^(\d+)\.(\d+)/',$iphost,$ipparts))
										$parthost = $ipparts[0].'.*.'.$parts[0];
									else
										$parthost = '*.'.$parts[0];
								} elseif($parts[1] == 'tinp') { // tinp IP hack
									if(preg_match('/^(\d+)\-(\d+)-(\d+)\-(\d+)/',$iphost,$ipparts))
										$parthost = $ipparts[4].'-'.$ipparts[3].'-*.'.$parts[0];
									else
										$parthost = '*.'.$parts[0];
								} elseif($parts[1] == 'totalbb') { // totalbb IP hack
									if(preg_match('/^[\w\-]+\.(\d+)-(\d+)\-(\d+)/',$iphost,$ipparts))
										$parthost = $ipparts[3].'-'.$ipparts[2].'-*.'.$parts[0];
									else
										$parthost = '*.'.$parts[0];
								} elseif($parts[1] == 'ocn') { // OCN hack (no IP hack available)
										preg_match('/([\w\-]+)\.([\w\-]+)\.([\w\-]+)\.(\w+)$/',$iphost,$parts);
										$parthost = '*.'.$parts[0];
								// i-cable/singnet/optusnet/plala/rosenet/bethere/asianet/home/hidatakayama/apol/pikara IP hack
								} elseif($parts[1] == 'hkcable' || $parts[1] == 'singnet' || $parts[1] == 'optusnet'
								 || $parts[1] == 'plala' || $parts[1] == 'rosenet' || $parts[1] == 'bethere'
								 || $parts[1] == 'asianet' || $parts[1] == 'home' || $parts[1] == 'hidatakayama'
								 || $parts[1] == 'apol' || $parts[1] == 'pikara') {
									if(preg_match('/^[a-zA-Z\-]*(\d+\-\d+)-\d+\-\d+/',$iphost,$ipparts))
										$parthost = $ipparts[1].'-*.'.$parts[0];
									else
										$parthost = '*.'.$parts[0];
								} elseif($parts[1] == 'mesh') { // mesh.ad.jp IP hack (partly)
									if(preg_match('/^\w+\-(\d+\-\d+)-\d+\-\d+/',$iphost,$ipparts))
										$parthost = $ipparts[1].'-*.'.$parts[0];
									else
										$parthost = '*.'.$parts[0];
								} elseif($parts[1] == 'dion' || $parts[1] == 'kcn-tv' || $parts[1] == 'janis') { // dion/kcn-tv/janis IP hack
									if(preg_match('/^[a-zA-Z]*(\d{3})(\d{3})/',$iphost,$ipparts))
										$parthost = intval($ipparts[1]).'.'.intval($ipparts[2]).'.*.'.$parts[0];
									else
										$parthost = '*.'.$parts[0];
								} else {
									$parthost = '*.'.$parts[0];
								}
							} else {
								if($parts[2] == 'wanadoo') { // wanadoo IP hack
									if(preg_match('/^[\w\-]+\.[a-zA-Z]{1}(\d+-\d+)/',$iphost,$ipparts))
										$parthost = $ipparts[1].'-*.'.$parts[2].'.'.$parts[3];
									else
										$parthost = '*.'.$parts[2].'.'.$parts[3];
								} elseif($parts[2] == 'corbina') { // corbina IP hack
									if(preg_match('/^(\d+\-\d+)/',$iphost,$ipparts))
										$parthost = $ipparts[0].'-*.'.$parts[2].'.'.$parts[3];
									else
										$parthost = '*.'.$parts[2].'.'.$parts[3];
								} elseif($parts[2] == 'commufa' || $parts[2] == 'unitymediagroup') { // commufa/unitymediagroup IP hack
									if(preg_match('/^[a-zA-Z]*-(\d+\-\d+)/',$iphost,$ipparts))
										$parthost = $ipparts[1].'-*.'.$parts[2].'.'.$parts[3];
									else
										$parthost = '*.'.$parts[2].'.'.$parts[3];
								} elseif($parts[2] == 'bbexcite') { // bbexcite IP hack
									if(preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)/',$iphost,$ipparts))
										$parthost = $ipparts[4].'-'.$ipparts[3].'-*.'.$parts[2].'.'.$parts[3];
									else
										$parthost = '*.'.$parts[2].'.'.$parts[3];
								} elseif($parts[2] == 'club-internet') { // club-internet IP hack
									if(preg_match('/^\w+\-\d+\-(\d+)\-(\d+)\-(\d+)\-(\d+)/',$iphost,$ipparts))
										$parthost = $ipparts[1].'-'.$ipparts[2].'-*.'.$parts[2].'.'.$parts[3];
									else
										$parthost = '*.'.$parts[2].'.'.$parts[3];
								} elseif($parts[2] == 'telecomitalia') { // telecomitalia IP hack
									if(preg_match('/^[\w\-]+\.(\d+)\-(\d+)\-/',$iphost,$ipparts))
										$parthost = $ipparts[2].'-'.$ipparts[1].'-*.'.$parts[2].'.'.$parts[3];
									else
										$parthost = '*.'.$parts[2].'.'.$parts[3];
								} else {
									$parthost = '*.'.$parts[2].'.'.$parts[3];
								}
							}
							break;
						}
					}
				} else {
					$parthost = '*.'.$parts[0];
				}
				if(!$iscctld && !$isgtld) $parthost = $iphost; // unresolvable
			}

			$arrLabels['{$NOW}'] .= '(Host:'.$parthost.')';
		}
	}

	function autoHookThreadReply(&$arrLabels, $post, $isReply){
		$this->autoHookThreadPost($arrLabels, $post, $isReply);
	}
}
?>