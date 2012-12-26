<?php
class mod_globalvars{
	function mod_globalvars($_PMS){
		global $PIO, $FileIO, $PTE, $PMS;
		$PIO = PMCLibrary::getPIOInstance();
		$FileIO = PMCLibrary::getFileIOInstance();
		$PTE = PMCLibrary::getPTEInstance();
		$PMS = $_PMS;
	}

	function getModuleName(){
		return __CLASS__.' : 舊模組相容模組';
	}

	function getModuleVersionInfo(){
		return '7th.Release (v121226)';
	}
}