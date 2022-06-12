<?php

namespace VietnamPMTeam\BedWars\provider;


use pocketmine\player\Player;
use VietnamPMTeam\BedWars\BedWars;
use mysqli;

class DatabaseMYSQL{

	private $plugin;

	public function __construct(BedWars $plugin)
	{
		$this->plugin = $plugin;
		$this->init();
	}

	public function init(){
		$this->getDatabase()->query("CREATE TABLE IF NOT EXISTS RolzzDev (
			PlayerName VARCHAR(255) PRIMARY KEY, BedBroken TEXT NOT NULL, GamePlayed TEXT NOT NULL, MainKill TEXT NOT NULL, FinalKill TEXT NOT NULL, Victory TEXT NOT NULL, QuickBuyData TEXT NOT NULL
			);");
	}

	public function getDatabase(){
		$config = $this->plugin->getConfig()->get("mysql");
		$koneksi =  new mysqli($config["ip"],$config["user"],$config["password"],$config["database"]);
		if($koneksi){	
		    return $koneksi;
		} else {
			BedWars::getInstance()->geLogger()->alert("Could connect mysql");
			return null;
		}
	}

	public function registerAccount(Player  $player){
		$playerName = $player->getName();
		$oke = $this->getDatabase();
		mysqli_query($oke,"INSERT INTO RolzzDev (PlayerName, BedBroken,GamePlayed,MainKill,FinalKill,Victory,QuickBuyData )VALUES('$playerName', '0','0','0','0','0','')");
        $oke->close();
	}

	public function addscore(Player $player,$type){
	    $playerName = $player->getName();
		$oke = $this->getDatabase();
		$query = function() use ($oke){
			mysqli_query($oke,func_get_arg(0));
		};
		if($type == "kill"){
		    $query("UPDATE RolzzDev SET MainKill = MainKill + 1 WHERE PlayerName = '$playerName'");
		}
		if($type == "fk"){
			$query("UPDATE RolzzDev SET FinalKill = FinalKill + 1 WHERE PlayerName = '$playerName'");
		}
		if($type == "GamePlayed"){
			$query("UPDATE RolzzDev SET GamePlayed = GamePlayed + 1 WHERE PlayerName = '$playerName'");
		}
		if($type == "Victory"){
		    $query("UPDATE RolzzDev SET Victory = Victory + 1 WHERE PlayerName = '$playerName'");
		}
		if($type == "BedBroken"){
			$query("UPDATE RolzzDev SET BedBroken = BedBroken + 1 WHERE PlayerName = '$playerName'");
		}
		$oke->close();
	}

	public function getAccount(Player $player)
    {
        $playerName = $player->getName();
		$oke = $this->getDatabase();
		if(isset(mysqli_query($oke,"SELECT * FROM RolzzDev WHERE PlayerName = '$playerName'")->fetch_assoc()["PlayerName"])){
			return true;
		} else {
			return false;
		}
		$oke->close();
	}

}
