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
		if(ip2long($post['host'])!==false) {
			$arrLabels['{$NOW}'] .= '(IP:'.preg_replace('/\d+\.\d+$/','*.*',$post['host']).')';
		} else { // host
			$parthost=''; $iscctld = false; $isgtld = false;

			if($post['host'] == 'localhost') { // localhost hack
				$arrLabels['{$NOW}'] .= '(Host:localhost)';
				return;
			}

			$cctld = array('ac','ad','ae','af','ag','ai','al','am','an','ao','aq','ar','as','at','au','aw','ax','az','ba','bb','bd','be','bf','bg','bh','bi','bj','bm','bn','bo','br','bs','bt','bw','by','bz','ca','cc','cd','cf','cg','ch','ci','ck','cl','cm','cn','co','cr','cu','cv','cx','cy','cz','de','dj','dk','dm','do','dz','ec','ee','eg','er','es','et','eu','fi','fj','fk','fm','fo','fr','ga','gd','ge','gf','gg','gh','gi','gl','gm','gn','gp','gq','gr','gs','gt','gu','gw','gy','hk','hm','hn','hr','ht','hu','id','ie','il','im','in','io','iq','ir','is','it','je','jm','jo','jp','ke','kg','kh','ki','km','kn','kp','kr','kw','ky','kz','la','lb','lc','li','lk','lr','ls','lt','lu','lv','ly','ma','mc','md','me','mg','mh','mk','ml','mm','mn','mo','mp','mq','mr','ms','mt','mu','mv','mw','mx','my','mz','na','nc','ne','nf','ng','ni','nl','no','np','nr','nu','nz','om','pa','pe','pf','pg','ph','pk','pl','pn','pr','ps','pt','pw','py','qa','re','ro','rs','ru','rw','sa','sb','sc','sd','se','sg','sh','si','sk','sl','sm','sn','sr','st','su','sv','sy','sz','tc','td','tf','tg','th','tj','tk','tl','tm','tn','to','tr','tt','tv','tw','tz','ua','ug','uk','us','uy','uz','va','vc','ve','vg','vi','vn','vu','wf','ws','ye','za','zm','zw');
			preg_match('/([\w\-]+)\.(\w+)$/',$post['host'],$parts);

			if(preg_match('/on-nets$/',$parts[1])) { // on-nets IP hack
				if(preg_match('/(\d+)\-(\d+)-on-nets/',$parts[1],$ipparts))
					$parthost = $ipparts[2].'.'.$ipparts[1].'.*.on-nets.com';
				else
					$parthost = '*-on-nets.com';
			} elseif($parts[1] == 'hinet') { // hinet IP hack
				if(preg_match('/^(\d+\-\d+)/',$post['host'],$ipparts))
					$parthost = $ipparts[0].'-*.'.$parts[0];
				else
					$parthost = '*.'.$parts[0];
			} else {
				$lastpart = strtolower($parts[2]);
				$isgtld = $this->_isgTLD($lastpart);

				if(!$isgtld) {
					foreach($cctld as $tld) {
						if($lastpart == $tld) {
							$iscctld = true;
							preg_match('/([\w\-]+)\.([\w\-]+)\.(\w+)$/',$post['host'],$parts);
							$isgtld = $this->_isgTLD($parts[2],array('ac','ad','co','ed','go','gr'.'lg','ne','or','ind','ltd','nic','plc','vet')); // '.co.uk' etc. are common
							if($isgtld) {
								if($parts[1] == 'kbronet' || $parts[1] == 'seed' || $parts[1] == 'so-net' || $parts[1] == 'tfn') { // kbronet/seed/so-net/tfn IP hack
									if(preg_match('/^(\d+\-\d+)/',$post['host'],$ipparts))
										$parthost = $ipparts[0].'-*.'.$parts[0];
									else
										$parthost = '*.'.$parts[0];
								} else 
									$parthost = '*.'.$parts[0];
							} else {
								$parthost = '*.'.$parts[2].'.'.$parts[3];
							}
							break;
						}
					}
				} else {
					$parthost = '*.'.$parts[0];
				}
			}

			$arrLabels['{$NOW}'] .= '(Host:'.$parthost.')';
		}
	}

	function autoHookThreadReply(&$arrLabels, $post, $isReply){
		$this->autoHookThreadPost($arrLabels, $post, $isReply);
	}
}
?>