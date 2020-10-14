<?php

namespace DontTapTheWhiteTile;

class GameTable {

	private $plugin;
	private $starttime,$endtime; //Time of GameStart and GameEnd
	private $startsign;
	private $gameplayer;
	private $blocks;
	private $colors;
	private $status;
	private $level;

	/**
	 * @param DontTapTheWhiteTile 	$plugin
	 * @param array					$tabblock
	 * @param bool				  	$isBuild
	 */

	public function __construct(DontTapTheWhiteTile $plugin, $tabblock, $isBuild = false){
		$this->$plugin;
		$this->status = 0;

		if ($tabblock["face"] == "z") {
			for ($i=0; $i < 5; $i++) { 
				for ($n=0; $n < 4; $n++) { 
					$this->blocks[$i][$n] = array(
						"x" => $tabblock["x"],
						"y" => $tabblock["y"] + $i,
						"z" => $tabblock["z"] + $n,
						"level" => $tabblock["level"],
						);
				}
			}
		}
		if($tabblock["face"] == "x") {
			for ($i=0; $i < 5; $i++) { 
				for ($n=0; $n < 4; $n++) { 
					$this->blocks[$i][$n] = array(
						"x" => $tabblock["x"] + $n,
						"y" => $tabblock["y"] + $i,
						"z" => $tabblock["z"],
						"level" => $tabblock["level"],
						);
				}
			}
		}





		if ($isBuild) {
			$this->resetting();
		}

	}

	private function build(){
		
	}

	public function start(){

	}

	public function stop(){

	}

	public function update(){

	}

	public function isTap(){

	}

	private function resetting(){

	}


}