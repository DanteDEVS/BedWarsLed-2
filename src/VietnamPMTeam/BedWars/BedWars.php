<?php



namespace VietnamPMTeam\BedWars;


use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\network\mcpe\raklib\RakLibInterface;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\BlockFactory;
use pocketmine\utils\Config;
use pocketmine\world\World;
use pocketmine\plugin\PluginBase;
use VietnamPMTeam\BedWars\libs\scoreboard\ScoreAPI;
use VietnamPMTeam\BedWars\math\MapReset;
use VietnamPMTeam\BedWars\commands\BedWarsCommand;
use VietnamPMTeam\BedWars\provider\DatabaseMYSQL;
use VietnamPMTeam\BedWars\math\Vector3;
use VietnamPMTeam\BedWars\math\Bedbug;
use VietnamPMTeam\BedWars\math\Golem;
use VietnamPMTeam\BedWars\math\Fireball;
use VietnamPMTeam\BedWars\math\Generator;
use VietnamPMTeam\BedWars\math\ShopVillager;
use VietnamPMTeam\BedWars\math\UpgradeVillager;
use VietnamPMTeam\BedWars\provider\YamlDataProvider;
use pocketmine\entity\Skin;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityFactory;
use pocketmine\world\Position;
use pocketmine\player\Player;
use VietnamPMTeam\BedWars\math\EnderDragon;
use VietnamPMTeam\BedWars\math\Egg;
use muqsit\invmenu\InvMenuHandler;

/**
 * Class BedWars
 * @package VietnamPMTeam\BedWars
 */
class BedWars extends PluginBase implements Listener {

    /** @var YamlDataProvider */
    public $dataProvider;
    /**
     * @var
     */
    public $config;
    /**
     * @var array
     */
    public $placedBlock = [];
    /**
     * @var array
     */
    public $arenas = [];

    /**
     * @var array
     */
    public $setters = [];
    /**
     * @var array
     */
    public $setupData = [];
    /**
     * @var
     */
    public $mysqldata;
    /**
     * @var array
     */
    public $arenaPlayer = [];
    /**
     * @var array
     */
    public $teams = [];
    /**
     * @var
     */
    public static $score;

    /**
     * @var
     */
    public static $instance;

    /**
     * @var
     */
    public $shop;
    /**
     * @var
     */
    public $upgrade;

    protected function onEnable(): void{
        self::$instance = $this;
        self::$score = new ScoreAPI($this);
        $this->saveResource("config.yml");
        $this->saveResource("diamond.png");
        $this->saveResource("emerald.png");
        $this->registerEntity();
        parent::onEnable();
        $this->mysqldata = new DatabaseMYSQL($this);
        $this->config = (new Config($this->getDataFolder() . "config.yml", Config::YAML))->getAll();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->dataProvider = new YamlDataProvider($this);
        $this->dataProvider->loadArenas();
        $this->getServer()->getCommandMap()->register("bw", new BedWarsCommand($this));
        if(!InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }
        foreach ($this->getServer()->getNetwork()->getInterfaces() as $interface) {
            if($interface instanceof RakLibInterface) {
                $interface->setPacketLimit(PHP_INT_MAX);
                break;
            }
        }
        if(is_null($this->getConfig()->get("join-arena"))){
            $this->getConfig()->set("join-arena","false");
            $this->getConfig()->save();
            $this->getConfig()->reload();
        }
        $this->getServer()->getLogger()->info("§l§eBedWars actived");

    }

    
    public static function getInstance(){
        return self::$instance;
    }

    public function isInGame(Player $player): bool
    {
        if(isset($this->arenaPlayer[$player->getName()])){
            return true;
        } else {
            return false;
        }
    }

    public function getArenaByPlayer(Player $player){
        return $this->arenaPlayer[$player->getName()];
    }

    public function registerEntity(){
        EntityFactory::getInstance()->register(EnderDragon::class, true);
        EntityFactory::getInstance()->register(ShopVillager::class, true);
        EntityFactory::getInstance()->register(UpgradeVillager::class, true);
        EntityFactory::getInstance()->register(\pocketmine\world\generator\GeneratorManager::getInstance()->class, true);
        EntityFactory::getInstance()->register(Bedbug::class, true);
        EntityFactory::getInstance()->register(Egg::class,true);
        EntityFactory::getInstance()->register(Golem::class, true);
        EntityFactory::getInstance()->register(Fireball::class, true);
    
    }

    public function onPlayerJoin(PlayerJoinEvent $event){
        $event->setJoinMessage("");
    }

    public function join(PlayerLoginEvent $event)
	{
		$player = $event->getPlayer();
	
		if (!$this->mysqldata->getAccount($player)) {
			$this->mysqldata->registerAccount($player);
		}  
        if($this->getConfig()->get("join-arena") == true && count($this->arenas) != 0){
            $this->joinToRandomArena($player);
        }
	}

    /**
     * @param $path
     * @return Skin
     */
    
    public function getSkinFromFile($path) : Skin{
        $img = imagecreatefrompng($path);
        $bytes = '';
        $l = (int) getimagesize($path)[1];
        for ($y = 0; $y < $l; $y++) {
            for ($x = 0; $x < 64; $x++) {
                $rgba = imagecolorat($img, $x, $y);
                $r = ($rgba >> 16) & 0xff;
                $a = ((~((int)($rgba >> 24))) << 1) & 0xff;
                $g = ($rgba >> 8) & 0xff;
                $b = $rgba & 0xff;
                $bytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }
        imagedestroy($img);
        return new Skin("Standard_CustomSlim", $bytes);
    }

    protected function onDisable(): void{
        $this->dataProvider->saveArenas();
        if(file_exists($this->getDataFolder()."finalkills.yml")){
            unlink($this->getDataFolder()."finalkills.yml");
        }
    }

    public function onChat(PlayerChatEvent $event) {
        $player = $event->getPlayer();
        
        if(!$player instanceof Player){
            return;
        }
        if(!isset($this->setters[$player->getName()])) {
            return;
        }


        $event->cancel();
        $args = explode(" ", $event->getMessage());
        $arena = $this->setters[$player->getName()];

        switch ($args[0]) {
            case "help":
                $player->sendMessage(
                "§bhelp : §aDisplays list of available setup commands\n" .
                "§bslots : §aUpdates arena slots\n".
                "§blevel : §aSets arena level\n".
                "§blobby : §aSets Lobby Spawn\n".
                "§blocation: §aSets arena location\n".
                "§bcorner1: §aSets arena dragon 1\n".
                "§bcorner2: §aSets arena dragon 2\n".
                "§bjoinsign : §aSets arena join sign\n".
                "§bsavelevel : §aSaves the arena level\n".
                "§bsetbed : §aset bed position \n".
                "§benable : §aEnables the arena");
                break;
            case "corner1":
                $arena->data["corner1"] =  Position::fromObject($player->ceil(), $player->getWorld());
                $player->sendMessage("Sucessfuly set ender dragon position 1");
                break;
            case "corner2":
                $firstPos = $arena->data["corner1"];

                $world = $player->getWorld();
               

                $player->sendMessage("§6> Importing blocks...");
                $secondPos = $player->ceil();
                $blocks = [];

                for($x = min($firstPos->getX(), $secondPos->getX()); $x <= max($firstPos->getX(), $secondPos->getX()); $x++) {
                    for($y = min($firstPos->getY(), $secondPos->getY()); $y <= max($firstPos->getY(), $secondPos->getY()); $y++) {
                        for($z = min($firstPos->getZ(), $secondPos->getZ()); $z <= max($firstPos->getZ(), $secondPos->getZ()); $z++) {
                            if($world->getBlockLightAt($x, $y, $z) !== BlockLegacyIds::AIR) {
                                $blocks["$x:$y:$z"] = new Vector3($x,$y,$z);
                            }
                        }
                    }
                }

                $player->sendMessage("§aDragon position 2 set to {$player->getPosition()->getPosition()->asVector3()->__toString()} in world {$world->getName()}");
                $arena->data["corner1"] =  (new Vector3((int)$firstPos->getX(), (int)$firstPos->getY(), (int)$firstPos->getZ()))->__toString();
                $arena->data["corner2"] = (new Vector3((int)$player->getPosition()->getX(), (int)$player->getPosition()->getY(), (int)$player->getPosition()->getZ()))->__toString();
                $arena->data["blocks"] = $blocks;
                $player->sendMessage("Sucessfuly set ender dragon position 2");
                break;
           /* case "slots":
                if(!isset($args[1])) {
                    $player->sendMessage("§cUsage: §7slots <int: slots>");
                    break;
                }
                $arena->data["slots"] = (int)$args[1];
                $player->sendMessage("§bSlots updated to $args[1]!");
                break;*/
            case "level":
                if(!isset($args[1])) {
                    $player->sendMessage("§bUsage: §7level <levelName>");
                    break;
                }
                if(!$this->getServer()->getWorldManager()->isWorldGenerated($args[1])) {
                    $player->sendMessage("§bLevel $args[1] does not found!");
                    break;
                }
                $player->sendMessage("§bArena level updated to $args[1]!");
                $arena->data["level"] = $args[1];
                break;
            case "addupgrade":
                $upgrade = $this->upgrade[$player->getName()];
                $arena->data["upgrade"]["$upgrade"] = (new Vector3(floor($player->getPosition()->getX()), floor($player->getPosition()->getY()), floor($player->getPosition()->getZ())))->__toString();
                $player->sendMessage("§bSpawn upgrade $upgrade setted " . (string)floor($player->getPosition()->getX()) . " Y: " . (string)floor($player->getPosition()->getY()) . " Z: " . (string)floor($player->getPosition()->getZ()));
                $this->upgrade[$player->getName()]++;
                break;
            case "addshop":
                $shop = $this->shop[$player->getName()];
                $arena->data["shop"]["$shop"] = (new Vector3(floor($player->getPosition()->getX()), floor($player->getPosition()->getY()), floor($player->getPosition()->getZ())))->__toString();
                $player->sendMessage("§bSpawn Shop  $shop setted " . (string)floor($player->getPosition()->getX()) . " Y: " . (string)floor($player->getPosition()->getY()) . " Z: " . (string)floor($player->getPosition()->getZ()));
                $this->shop[$player->getName()]++;
            break;
            case "setdistance":
              $arena->data["distance"] = Vector3::fromString($arena->data["location"]["red"])->distance($player->getEyePos()->getPosition()->asVector3());
            break;
            case "location":
                if(!in_array($args[1], ["red", "blue", "yellow", "green"])){
                    $player->sendMessage("§cUsage: §7location blue/red/yellow/green");
                    break;
                }
                if(!isset($args[1])) {
                    $player->sendMessage("§cUsage: §7location blue/red/yellow/green");
                    break;
                }
                $arena->data["location"]["{$args[1]}"] = (new Vector3(floor($player->getPosition()->getX()), floor($player->getPosition()->getY()), floor($player->getPosition()->getZ())))->__toString();
                $player->sendMessage("§bLocation Team $args[1] set to X: " . (string)floor($player->getArmorPoints()) . " Y: " . (string)floor($player->getPosition()->getY()) . " Z: " . (string)floor($player->getPosition()->getZ()));

            break;   
            case "setbed":
                if(!isset($args[1])) {
                    $player->sendMessage("§cUsage: §7setspawn blue/red/yellow/green");
                    break;
                }
                if(!in_array($args[1], ["red", "blue", "yellow", "green"])){
                    break;
                }
                $arena->data["bed"]["{$args[1]}"] = (new Vector3(floor($player->getPosition()->getX()), floor($player->getPosition()->getY()), floor($player->getPosition()->getZ())))->__toString();
                $player->sendMessage("§a Bed position $args[1] set to X: " . (string)floor($player->getPosition()->getX()) . " Y: " . (string)floor($player->getPosition()->getY()) . " Z: " . (string)floor($player->getPosition()->getZ()));
                break; 
            case "lobby":
                $arena->data["lobby"] = (new Vector3(floor($player->getPosition()->getX()) + 0.0, floor($player->getPosition()->getY()), floor($player->getPosition()->getZ()) + 0.0))->__toString();
                $player->sendMessage("§bLobby set to X: " . (string)floor($player->getPosition()->getX()) . " Y: " . (string)floor($player->getPosition()->getY()) . " Z: " . (string)floor($player->getPosition()->getZ()));
                break;
            case "joinsign":
                $player->sendMessage("§a> Break block to set join sign!");
                $this->setupData[$player->getName()] = 0;
                break;
            case "savelevel":
                if(!$arena->world instanceof World) {
                    $player->sendMessage("§c> Error when saving level: world not found.");
                    if($arena->setup) {
                        $player->sendMessage("§bEror arena not enabled");
                    }
                    break;
                }
                $arena->mapReset->saveMap($arena->level);
                $player->sendMessage("Level Saved");
                break;
            case "enable":
                if(!$arena->setup) {
                    $player->sendMessage("§6> Arena is already enabled!");
                    break;
                }

                if(!$arena->enable(false)) {
                    $player->sendMessage("§cCould not load arena, there are missing information!");
                    break;
                }

                if($this->getServer()->getWorldManager()->isWorldGenerated($arena->data["level"])) {
                    if(!$this->getServer()->getWorldManager()->isWorldLoaded($arena->data["level"]))
                        $this->getServer()->getWorldManager()->loadWorld($arena->data["level"]);
                    if(!$arena->mapReset instanceof MapReset)
                        $arena->mapReset = new MapReset($arena);
                    $arena->mapReset->saveMap($this->getServer()->getWorldManager()->getWorldByName($arena->data["level"]));
                }

                $arena->loadArena(false);
                $player->sendMessage("§aArena enabled!");
                break;   
            case "done":
                $player->sendMessage("§eArena saved to database");
                unset($this->setters[$player->getName()]);
                unset($this->upgrade[$player->getName()]);
                unset($this->shop[$player->getName()]);
                if(isset($this->setupData[$player->getName()])) {
                    unset($this->setupData[$player->getName()]);
                }
                break;
            default:
                $player->sendMessage("§etype 'help' for list commands ");
                break;
        }
    }

    /**
     * @param BlockBreakEvent $event
     */
    public function onBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if(isset($this->setupData[$player->getName()])) {
            switch ($this->setupData[$player->getName()]) {
                case 0:
                    $this->setters[$player->getName()]->data["joinsign"] = [(new Vector3($block->getPosition()->getX(), $block->getPosition()->getY(), $block->getPosition()->getZ()))->__toString(), $block->getPosition()->getWorld()->getFolderName()];
                    $player->sendMessage("§aJoin sign seted");
                    unset($this->setupData[$player->getName()]);
                    $event->cancel();
                    break;
            }
        }
    }

    public function startSetup(Player $player,string $map){
        $player->teleport($player->getServer()->getWorldManager()->getWorldByName($map)->getSafeSpawn());
        $this->setters[$player->getName()] = $this->arenas[$map];
        $this->upgrade[$player->getName()] = 1;
        $this->shop[$player->getName()] = 1;
    }

    public function getRandomArena(){

        $availableArenas = [];
        foreach ($this->arenas as $index => $arena) {
            $availableArenas[$index] = $arena;
        }

        //2.
        foreach ($availableArenas as $index => $arena) {
            if($arena->phase !== 0 || $arena->setup) {
                unset($availableArenas[$index]);
            }
        }

        //3.
        $arenasByPlayers = [];
        foreach ($availableArenas as $index => $arena) {
            $arenasByPlayers[$index] = count($arena->players);
        }

        arsort($arenasByPlayers);
        $top = -1;
        $availableArenas = [];

        foreach ($arenasByPlayers as $index => $players) {
            if($top == -1) {
                $top = $players;
                $availableArenas[] = $index;
            }
            else {
                if($top == $players) {
                    $availableArenas[] = $index;
                }
            }
        }

        if(empty($availableArenas)) {
            return null;
        }

        return $this->arenas[$availableArenas[array_rand($availableArenas, 1)]];
    }
    

    
    
    public function joinToRandomArena(Player $player) {
        $arena = $this->getRandomArena();
        if(!is_null($arena)) {
            $arena->joinToArena($player);
            return;
        }
       
        if($this->getConfig()->get("join-arena")){
            //CODE
        }
        $player->getInventory()->clearAll();
        $player->teleport($this->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
        
    }

    public function getScore(){
        return self::$score;
    }


    public function addFinalKill(Player $player){
        $name = $player->getName();
        $kills = new Config($this->getDataFolder() . "finalkills.yml", Config::YAML);
        $k = $kills->get($name);
        $kills->set($name, $k + 1);
        $kills->save();
    }
    
}
