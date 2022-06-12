<?php

namespace VietnamPMTeam\BedWars;

use pocketmine\block\BlockFactory;
use pocketmine\block\Bed;
use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\Chest;
use pocketmine\player\GameMode;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\utils\Random;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\utils\TextFormat;
use VietnamPMTeam\BedWars\math\Egg;
use VietnamPMTeam\BedWars\math\DragonTargetManager;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\event\entity\{EntityDamageByChildEntityEvent, EntityExplodeEvent};
use pocketmine\utils\Config;
use pocketmine\math\Vector2;
use VietnamPMTeam\BedWars\libs\BlockHorizons\Fireworks\item\Fireworks;
use VietnamPMTeam\BedWars\libs\BlockHorizons\Fireworks\entity\FireworksRocket;
use pocketmine\block\Air;
use pocketmine\inventory\{PlayerInventory,
    transaction\action\SlotChangeAction};
use pocketmine\block\inventory\{ChestInventory,
    EnderChestInventory};
use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerChatEvent, PlayerItemConsumeEvent};
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\entity\{EntityMotionEvent,
    EntityDamageEvent,
    ItemSpawnEvent,
    ProjectileHitEntityEvent,
    ProjectileLaunchEvent};
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\{BlockPlaceEvent, LeavesDecayEvent};
use pocketmine\event\inventory\{InventoryTransactionEvent, InventoryOpenEvent, InventoryCloseEvent};
use pocketmine\item\enchantment\{VanillaEnchantments, EnchantmentInstance};
use pocketmine\item\{Armor, ItemIds, Sword, Item, Pickaxe, Axe};
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\entity\{object\ItemEntity, Effect, Entity, projectile\Arrow,  projectile\Snowball};
use pocketmine\entity\effect\EffectInstance;
use pocketmine\world\{particle\BlockBreakParticle, World};
use pocketmine\world\Position;
use pocketmine\color\Color;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\StopSoundPacket;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\block\tile\{Furnace, Skull, EnchantTable};
use pocketmine\event\inventory\CraftItemEvent;
use VietnamPMTeam\BedWars\math\MapReset;
use VietnamPMTeam\BedWars\math\ShopVillager;
use VietnamPMTeam\BedWars\math\UpgradeVillager;
use pocketmine\entity\object\PrimedTNT;
use VietnamPMTeam\BedWars\math\{Vector3,Generator,Bedbug, Golem, Fireball, TowerEast, TowerNorth, TowerSouth, TowerWest};
use muqsit\invmenu\{
    InvMenu
};
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use muqsit\invmenu\transaction\{DeterministicInvMenuTransaction};
use VietnamPMTeam\BedWars\math\EnderDragon;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\sound\EndermanTeleportSound;
use pocketmine\item\Compass;
use VietnamPMTeam\BedWars\task\TaskTick;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;

/**
 * Class Game
 * @package BedWars\Game
 */
class Game implements Listener
{

    const MSG_MESSAGE = 0;
    const MSG_TIP = 1;
    const MSG_POPUP = 2;
    const MSG_TITLE = 3;

    const PHASE_LOBBY = 0;
    const PHASE_GAME = 1;
    const PHASE_RESTART = 2;

    /** @var BedWars $plugin */
    public $plugin;

    public $scheduler;
    /**
     * @var
     */
    public $mapReset;
    /**
     * @var array
     */
    public $spawnTowerDelay = [];
    /**
     * @var int
     */
    public $phase = 0;
    /**
     * @var array
     */
    public $kill = [];
    public $finalkill = [];
    public $broken = [];

    /** @var array $data */
    public $data = [];
    /**
     * @var array
     */
    public $placedBlock = [];
    /**
     * @var array
     */
    public $invis = [];

    /**
     * @var array
     */
    public $inChest = [];
    /**
     * @var array
     */
    public $teams = [];
    /**
     * @var array
     */
    public $countertrap = [];
    /**
     * @var array
     */
    public $itstrap = [];
    /**
     * @var array
     */
    public $minertrap = [];
    /**
     * @var array
     */
    public $alarmtrap = [];
    /**
     * @var array
     */
    public $armor = [];
    /**
     * @var array
     */
    public $axe = [];
    /**
     * @var array
     */
    public $tempTeam = [];
    /**
     * @var array
     */
    public $pickaxe = [];
    /**
     * @var array
     */
    public $spectators = [];
    /**
     * @var array
     */
    public $shear = [];
    /**
     * @var array
     */
    public $utilities = [];
    /**
     * @var array
     */
    public $tracking = [];

    /** @var bool $setting */
    public $setup = false;

    /** @var Player[] $players */
    public $players = [];
    /**
     * @var array
     */
    public $index = [];
    /**
     * @var int
     */
    private $maxPlayerPerTeam = 4;
    /**
     * @var int
     */
    private $maxPlayers = 16;


    /** @var World $world */
    public $world = null;
    /**
     * @var array
     */
    public $respawnC = [];
    /**
     * @var array
     */
    public $milk = [];

    public $suddendeath;
    /**
     * @var TowerSouth $towersouth
     */
    public $towersouth;
    /**
     * @var TowerEast $towereast
     */
    public $towereast;
    /**
     * @var TowerNorth $towernorth
     */
    public $towernorth;
    /**
     * @var TowerWest $towerwest
     */
    public $towerwest;


    /**
     * Game constructor.
     * @param BedWars $plugin
     * @param array $arenaFileData
     */
    public function __construct(BedWars $plugin, array $arenaFileData)
    {
        $this->plugin = $plugin;
        $this->data = $arenaFileData;
        $this->setup = !$this->enable(false);
        $this->plugin->getScheduler()->scheduleRepeatingTask($this->scheduler = new TaskTick($this), 20);
        $this->scheduler->reloadTimer();

        if ($this->setup) {
            if (empty($this->data)) {
                $this->createBasicData();
            }
        } else {
            $this->loadArena();
        }
    }


    public function initTeams()
    {
        if (!$this->setup) {
            unset($this->plugin->teams[$this->world->getFolderName()]);
            unset($this->utilities[$this->world->getFolderName()]);
            $this->towersouth = new TowerSouth($this);
            $this->towereast = new TowerEast($this);
            $this->towernorth = new TowerNorth($this);
            $this->towerwest = new TowerWest($this);
            $this->plugin->teams[$this->world->getFolderName()]["red"] = [];
            $this->plugin->teams[$this->world->getFolderName()]["blue"] = [];
            $this->plugin->teams[$this->world->getFolderName()]["yellow"] = [];
            $this->plugin->teams[$this->world->getFolderName()]["green"] = [];
            $this->plugin->teams[$this->world->getFolderName()]["aqua"] = [];
            $this->plugin->teams[$this->world->getFolderName()]["white"] = [];
            $this->plugin->teams[$this->world->getFolderName()]["pink"] = [];
            $this->plugin->teams[$this->world->getFolderName()]["gray"] = [];

        }
    }

    /**
     * @param Player $player
     * @return mixed
     */

    public function getWorlds(Player $player): int
    {
        $world = 0;
        $pl = $this->plugin->getServer()->getPluginManager()->getPlugin("Level_System");
        if ($pl == null) {
            $world = 0;
        } else {
            $colors = $pl->color->getColor($player);
            $world = $colors;
        }

        return $world;

    }

    /**
     * @param PlayerMoveEvent $ev
     */

    public function onShopMove(PlayerMoveEvent $ev)
    {
        $player = $ev->getPlayer();
        $from = $ev->getFrom();
        $to = $ev->getTo();
        if ($from->distance($to) < 0.1) {
            return;
        }
        $maxDistance = 10;
        foreach ($player->getWorld()->getNearbyEntities($player->getBoundingBox()->expandedCopy($maxDistance, $maxDistance, $maxDistance), $player) as $e) {
            if ($e instanceof Player) {
                continue;
            }
            $xdiff = $player->getPosition()->x - $e->getPosition()->x;
            $zdiff = $player->getPosition()->z - $e->getPosition()->z;
            $angle = atan2($zdiff, $xdiff);
            $yaw = (($angle * 180) / M_PI) - 90;
            $ydiff = $player->getPosition()->y - $e->getPosition()->y;
            $v = new Vector2($e->getPosition()->x, $e->getPosition()->z);
            $dist = $v->distance($player->getPosition()->x, $player->getPosition()->z);
            $angle = atan2($dist, $ydiff);
            $pitch = (($angle * 180) / M_PI) - 90;
            if (!isset($this->spectators[$player->getName()])) {
                if ($e instanceof ShopVillager || $e instanceof UpgradeVillager) {
                    $pk = new MoveActorAbsolutePacket();
                    $pk->entityRuntimeId = $e->getId();
                    $pk->position = $e->getPosition()->getPosition()->asVector3();
                    $pk->xRot = $pitch;
                    $pk->yRot = $yaw;
                    $pk->zRot = $yaw;
                    $player->getNetworkSession()->getNetworkSession()->sendDataPacket($pk);
                }
            }
        }
    }

    /**
     * @param Player $player
     */

    public function joinToArena(Player $player)
    {
        if (!$this->data["enabled"]) {
            $player->sendMessage("Arena not enabled");
            return;
        }

        if (count($this->players) >= $this->maxPlayers) {
            $this->plugin->joinToRandomArena($player);
            $player->setImmobile();
            return;
        }

        if ($this->inGame($player)) {

            return;
        }

        $selected = false;
        for ($lS = 1; $lS <= $this->maxPlayers; $lS++) {
            if (!$selected) {
                if (!isset($this->players[$lS])) {
                    $this->players[$lS] = $player;
                    $this->index[$player->getName()] = $lS;
                    $player->teleport(Position::fromObject(Vector3::fromString($this->data["lobby"]), $this->world));
                    $this->setTeam($player, $lS);
                    $selected = true;
                }
            }
        }


        $this->plugin->arenaPlayer[$player->getName()] = $this;
        $player->getEffects()->all()->clear();
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->getEnderInventory()->clearAll();
        $player->setAllowFlight(false);
        $player->setFlying(false);
        $player->getCursorInventory()->clearAll();
        $player->setAbsorption(0);
        $player->getInventory()->setItem(8, ItemFactory::getInstance()->get(ItemIds::BED, 14)->setCustomName("§aReturn to lobby"));
        $player->setGamemode(GameMode::ADVENTURE());
        $player->setHealth(20);
        $player->getHungerManager()->getHungerManager()->setFood(20);
        $player->setNameTagVisible();
        $this->broadcastMessage("§f{$player->getDisplayName()} §ejoined (§b" . count($this->players) . "§e/§b{$this->data["slots"]}§e)!");

    }

    /**
     * @param Player $player
     */
    public function setColorTag(Player $player)
    {
        if ($this->getTeam($player) == "") {
            return;
        }
        $color = ["red" => "§c", "blue" => "§9", "green" => "§a", "yellow" => "§e"];
        $nametag = $player->getDisplayName();
        $world = $this->getWorlds($player);
        $player->setNametag($color[$this->getTeam($player)] . " " . ucfirst($this->getTeam($player)[0]) . "§r " . "$world $nametag ");

    }

    /**
     * @param $team
     * @return int|string
     */
    public function getCountTeam($team)
    {
        foreach ($this->world->getPlayers() as $player) {

            if ($team == "red") {
                return count($this->plugin->teams[$player->getWorld()->getFolderName()]["red"]);
            }
            if ($team == "blue") {
                return count($this->plugin->teams[$player->getWorld()->getFolderName()]["blue"]);
            }
            if ($team == "yellow") {
                return count($this->plugin->teams[$player->getWorld()->getFolderName()]["yellow"]);
            }
            if ($team == "green") {
                return count($this->plugin->teams[$player->getWorld()->getFolderName()]["green"]);
            }

        }


        return "";
    }

    /**
     * @param Player $player
     */

    public function unsetPlayer(Player $player)
    {

        unset($this->plugin->teams[$player->getWorld()->getFolderName()][$this->getTeam($player)][$player->getName()]);


        unset($this->armor[$player->getName()]);

        unset($this->shear[$player->getName()]);

        unset($this->axe[$player->getId()]);

        unset($this->inChest[$player->getId()]);

        unset($this->pickaxe[$player->getId()]);

        unset($this->players[$player->getName()]);

        unset($this->spectators[$player->getName()]);

        unset($this->plugin->arenaPlayer[$player->getName()]);

        $player->setScoreTag("");
        $player->setNameTag($player->getDisplayName());

    }

    /**
     * @param Player $player
     * @param $index
     */
    public function setTeam(Player $player, $index)
    {
        if (in_array($index, [1, 2, 3, 4])) {

            if ($this->getCountTeam("red") <= $this->maxPlayerPerTeam) {
                $this->plugin->teams[$player->getWorld()->getFolderName()]["red"][$player->getName()] = $player;
            }
        }

        if (in_array($index, [5, 6, 7, 8])) {
            if ($this->getCountTeam("blue") <= $this->maxPlayerPerTeam) {
                $this->plugin->teams[$player->getWorld()->getFolderName()]["blue"][$player->getName()] = $player;
            }
        }

        if (in_array($index, [9, 10, 11, 12])) {
            if ($this->getCountTeam("yellow") <= $this->maxPlayerPerTeam) {
                $this->plugin->teams[$player->getWorld()->getFolderName()]["yellow"][$player->getName()] = $player;
            }
        }

        if (in_array($index, [13, 14, 15, 16])) {
            if ($this->getCountTeam("green") <= $this->maxPlayerPerTeam) {
                $this->plugin->teams[$player->getWorld()->getFolderName()]["green"][$player->getName()] = $player;
            }
        }

    }


    public function initshop()
    {
        $shopPos = $this->data["shop"];
        for ($a = 1; $a <= count($shopPos); $a++) {
            $pos = Vector3::fromString($this->data["shop"][$a]);
            $nbt = $this->createBaseNBT($pos);
            $entity = new  ShopVillager($this->world, $nbt);
            $entity->arena = $this;
            $entity->teleport($pos);
            $entity->spawnToAll();
        }
        for ($a = 1; $a <= count($this->data["upgrade"]); $a++) {
            $pos = Vector3::fromString($this->data["upgrade"][$a]);
            $nbt = $this->createBaseNBT($pos);
            $entity = new  UpgradeVillager($this->world, $nbt);
            $entity->arena = $this;
            $entity->teleport($pos);
            $entity->spawnToAll();
        }
    }

    /**
     * @param Player $player
     * @return mixed|string
     */

    public function getTeam(Player $player): string
    {
        $team = "";
        if (isset($this->tempTeam[$player->getName()])) {
            $team = $this->tempTeam[$player->getName()];
        }
        if (isset($this->plugin->teams[$player->getWorld()->getFolderName()]["red"][$player->getName()])) {
            $team = "red";
        }
        if (isset($this->plugin->teams[$player->getWorld()->getFolderName()]["blue"][$player->getName()])) {
            $team = "blue";
        }
        if (isset($this->plugin->teams[$player->getWorld()->getFolderName()]["yellow"][$player->getName()])) {
            $team = "yellow";
        }
        if (isset($this->plugin->teams[$player->getWorld()->getFolderName()]["green"][$player->getName()])) {
            $team = "green";
        }
        return $team;
    }


    /**
     * @param Player $player
     * @param string $quitMsg
     * @param bool $death
     */
    public function disconnectPlayer(Player $player, string $quitMsg = "", bool $death = false)
    {
        switch ($this->phase) {
            case Game::PHASE_LOBBY:
                $this->broadcastMessage("{$player->getDisplayName()} §equit!");
                $index = "";
                foreach ($this->players as $i => $pl) {
                    if ($pl->getId() == $player->getId()) {
                        $index = $i;
                    }
                }
                if ($index != "") {
                    unset($this->players[$index]);
                }
                break;
            default:
                unset($this->players[$player->getName()]);
                break;
        }
        if ($player->isOnline() && $player !== null) {
            $team = $this->getTeam($player);
            if ($this->inGame($player) && $this->phase == self::PHASE_GAME) {
                $count = 0;
                foreach ($this->players as $mate) {
                    if ($this->getTeam($mate) == $team) {
                        $count++;
                    }
                }
                if ($count <= 0) {
                    $spawn = Vector3::fromString($this->data["bed"][$team]);
                    foreach ($this->world->getEntities() as $g) {
                        if ($g instanceof Generator) {
                            if ($g->getPosition()->getPosition()->asVector3()->distance($spawn) < 20) {
                                $g->close();
                            }
                        }
                    }
                    $this->breakbed($team);
                    $color = [
                        "red" => "§cRed",
                        "blue" => "§9Blue",
                        "yellow" => "§eYellow",
                        "green" => "§aGreen"
                    ];
                    $this->broadcastMessage("§l§fTEAM ELIMINATED > §r§b$color[$team] §ewas eliminated!");
                }
            }
            if ($this->phase == self::PHASE_GAME) {
                $this->broadcastMessage("§b{$player->getDisplayName()} §7disconnected!");
            }
            $this->unsetPlayer($player);

            $player->getServer()->dispatchCommand($player, "lobby");
        }
    }

    public function createBaseNBT(Vector3 $pos, ?Vector3 $motion = null, float $yaw = 0.0, float $pitch = 0.0): CompoundTag
    {
        return CompoundTag::create()
            ->setTag("Pos", new ListTag([
                new DoubleTag($pos->x),
                new DoubleTag($pos->y),
                new DoubleTag($pos->z)
            ]))
            ->setTag("Motion", new ListTag([
                new DoubleTag($motion !== null ? $motion->x : 0.0),
                new DoubleTag($motion !== null ? $motion->y : 0.0),
                new DoubleTag($motion !== null ? $motion->z : 0.0)
            ]))
            ->setTag("Rotation", new ListTag([
                new FloatTag($yaw),
                new FloatTag($pitch)
            ]));
    }

    /**
     * @param Player $player
     */
    public function spectator(Player $player)
    {
        switch ($this->phase) {
            case Game::PHASE_LOBBY:
                $index = "";
                foreach ($this->players as $i => $p) {
                    if ($p->getId() == $player->getId()) {
                        $index = $i;
                    }
                }
                if ($index != "") {
                    unset($this->players[$index]);
                }
                break;
            default:
                unset($this->players[$player->getName()]);
                break;
        }
        $team = $this->getTeam($player);
        if ($this->phase == self::PHASE_GAME) {
            $count = 0;
            foreach ($this->players as $peler) {
                if ($this->getTeam($peler) == $team) {
                    if (!isset($this->spectators[$peler->getName()])) {
                        $count++;
                    }
                }
            }
            if ($count <= 0) {
                $spawn = Vector3::fromString($this->data["bed"][$team]);
                foreach ($this->world->getEntities() as $g) {
                    if ($g instanceof Generator) {
                        if ($g->getPosition()->getPosition()->asVector3()->distance($spawn) < 20) {
                            $g->close();
                        }
                    }
                }
                $color = [
                    "red" => "§cRED",
                    "blue" => "§9BLUE",
                    "yellow" => "§eYELLOW",
                    "green" => "§aGREEN"
                ];
                $this->broadcastMessage("§l§fTEAM ELIMINATED > §r§b$color[$team] §ewas eliminated!");
            }

        }

        $player->sendTitle("§l§cYOU DIED!", "§7You are now spectator");

        $player->setScoreTag("");
        $player->setNameTag($player->getDisplayName());
        $this->tempTeam[$player->getName()] = $this->getTeam($player);
        $this->spectators[$player->getName()] = $player;
        unset($this->plugin->teams[$player->getWorld()->getFolderName()][$this->getTeam($player)][$player->getName()]);
        unset($this->armor[$player->getName()]);

        unset($this->shear[$player->getName()]);

        unset($this->axe[$player->getId()]);

        unset($this->inChest[$player->getId()]);

        unset($this->pickaxe[$player->getId()]);

        unset($this->players[$player->getName()]);
        $player->getEffects()->all()->clear();
        $player->setGamemode(GameMode::SPECTATOR());

        $player->setHealth(20);
        $player->setAllowFlight(true);
        $player->setFlying(true);
        $player->getHungerManager()->getHungerManager()->setFood(20);
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->getCursorInventory()->clearAll();
        $player->getInventory()->setHeldItemIndex(4);
        $spawnLoc = $this->world->getSafeSpawn();
        $spawnPos = new Vector3(round($spawnLoc->getX()) + 0.5, $spawnLoc->getY() + 10, round($spawnLoc->getZ()) + 0.5);
        $player->teleport($spawnPos);
        $player->getInventory()->setItem(8, ItemFactory::getInstance()->get(ItemIds::BED, 14)->setCustomName("§aReturn to lobby"));
        $player->getInventory()->setItem(0, ItemFactory::getInstance()->get(ItemIds::PAPER)->setCustomName("§aPlay Again"));
        $player->getInventory()->setItem(4, ItemFactory::getInstance()->get(ItemIds::COMPASS)->setCustomName("§eSpectator"));
    }

    /**
     * @param DataPacketReceiveEvent $event
     */

    public function onReceivePacket(DataPacketReceiveEvent $event)
    {
        $packet = $event->getPacket();
        $player = $event->getOrigin()->getPlayer();
        if ($packet instanceof LevelSoundEventPacket) {

            if (isset($this->spectators[$event->getOrigin()->getPlayer()->getName()])) {
                $event->cancel();
                $player->getNetworkSession()->getNetworkSession()->sendDataPacket($packet);
            }
            if ($this->inGame($player) && $this->phase == 0) {
                if ($packet->sound == 42 || $packet->sound == 43 || $packet->sound == 41 || $packet->sound == 40 || $packet->sound == 35) {
                    $event->cancel();
                }
            }
        }


    }

    /**
     * @param DataPacketSendEvent $event
     */

    public function onSpectator(DataPacketSendEvent $event)
    {
        $packet = $event->getPacket();
        if ($packet::NETWORK_ID == AdventureSettingsPacket::NETWORK_ID) {
            $packet->setFlag($packet::NO_CLIP, false);
            $packet->setFlag($packet::NO_PVP, true);
        }
    }

    /**
     * @param Player $player
     */
    public function respawn(Player $player)
    {
        if (!($player instanceof Player)) return;
        $player->setGamemode(GameMode::SURVIVAL());
        $player->sendTitle("§l§aRESPAWNED!");
        $player->setHealth(20);
        $player->setAllowFlight(false);
        $player->setFlying(false);
        $player->getHungerManager()->getHungerManager()->setFood(20);
        $this->teleport($player);
        $this->setArmor($player);
        $sword = ItemFactory::getInstance()->get(ItemIds::WOODEN_SWORD);
        $this->setSword($player, $sword);
        $axe = $this->getAxeByTier($player, false);
        $pickaxe = $this->getPickaxeByTier($player, false);
        if (isset($this->axe[$player->getId()])) {
            if ($this->axe[$player->getId()] > 1) {
                $player->getInventory()->addItem($axe);
            }
        }
        if (isset($this->pickaxe[$player->getId()])) {
            if ($this->pickaxe[$player->getId()] > 1) {
                $player->getInventory()->addItem($pickaxe);
            }
        }
    }


    public function removeLobby()
    {
        $pos = Position::fromObject(Vector3::fromString($this->data["lobby"]), $this->world);
        for ($x = -15; $x <= 16; $x++) {
            for ($y = -4; $y <= 10; $y++) {
                for ($z = -15; $z <= 16; $z++) {
                    $world = $this->world;
                    $block = $world->getBlock($pos->add($x, $y, $z));
                    (float)$world->setBlock($block, BlockFactory::getInstance()->get(0, 0));
                }
            }
        }
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function bedState(Player $player): bool
    {
        $team = $this->getTeam($player);
        $state = false;
        if ($team !== "") {
            $vc = Vector3::fromString($this->data["bed"][$team]);
            if (($tr = $this->world->getBlockAt($vc->x, $vc->y, $vc->z)) instanceof Bed) {
                $state = true;
            }
        }
        return $state;
    }

    /**
     * @param Player $player
     */

    public function dropItem(Player $player)
    {
        foreach ($player->getInventory()->getContents() as $cont) {
            if (in_array($cont->getId(), [ItemIds::WOOL, 172, 49, 386, 264, 266, 265, 121, 65, 241, 5, 373, ItemIds::GOLDEN_APPLE, ItemIds::FIREBALL, ItemIds::TNT, ItemIds::SPAWN_EGG, ItemIds::SNOWBALL, ItemIds::EGG])) {
                $player->getWorld()->dropItem($player, $cont);

            }
        }
    }

    /**
     * @param $team
     * @return string
     */

    public function statusTeam(string $team): string
    {
        $vc = Vector3::fromString($this->data["bed"][$team]);
        if ($this->world->getBlockAt($vc->x, $vc->y, $vc->z) instanceof Bed) {
            return "§a✔§r";
        } else {
            $count = $this->getCountTeam($team);
            if ($count == 0) {
                return "§c✘§r";
            } else {
                return "§b $count";
            }
        }


    }

    /**
     * @param Player $player
     * @param Item $sword
     */

    public function setSword(Player $player, Item $sword)
    {
        if ($player instanceof Player) {
            $team = $this->getTeam($player);
            $enchant = null;
            if (isset($this->utilities[$player->getWorld()->getFolderName()][$team]["sharpness"])) {

                if ($this->utilities[$player->getWorld()->getFolderName()][$team]["sharpness"] == 2) {
                    $enchant = new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 1);

                }
                if ($this->utilities[$player->getWorld()->getFolderName()][$team]["sharpness"] == 3) {
                    $enchant = new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 2);
                }
                if ($this->utilities[$player->getWorld()->getFolderName()][$team]["sharpness"] == 4) {
                    $enchant = new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 3);
                }
                if ($this->utilities[$player->getWorld()->getFolderName()][$team]["sharpness"] == 5) {
                    $enchant = new EnchantmentInstance(VanillaEnchantments::SHARPNESS(), 4);
                }
            }
            if ($enchant !== null) {
                $sword->addEnchantment($enchant);
            }
            $sword->setUnbreakable(true);
            $player->getInventory()->removeItem($player->getInventory()->getItem(0));
            $player->getInventory()->setItem(0, $sword);
            if (isset($this->shear[$player->getName()])) {
                if (!$player->getInventory()->contains(ItemFactory::getInstance()->get(ItemIds::SHEARS))) {
                    $sh = ItemFactory::getInstance()->get(ItemIds::SHEARS);
                    $sh->setUnbreakable(true);
                    $player->getInventory()->addItem($sh);
                }
            }
        }
    }

    /**
     * @param Player $player
     */

    public function setArmor(Player $player)
    {
        if ($player instanceof Player) {
            $team = $this->getTeam($player);
            $player->getArmorInventory()->clearAll();
            $enchant = null;
            if (isset($this->utilities[$player->getWorld()->getFolderName()][$team]["protection"])) {

                if ($this->utilities[$player->getWorld()->getFolderName()][$team]["protection"] == 2) {
                    $enchant = new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 1);
                }
                if ($this->utilities[$player->getWorld()->getFolderName()][$team]["protection"] == 3) {
                    $enchant = new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 2);
                }
                if ($this->utilities[$player->getWorld()->getFolderName()][$team]["protection"] == 4) {
                    $enchant = new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 3);
                }
                if ($this->utilities[$player->getWorld()->getFolderName()][$team]["protection"] == 5) {
                    $enchant = new EnchantmentInstance(VanillaEnchantments::PROTECTION(), 4);
                }
            }
            $color = null;
            if ($team == "red") {
                $color = new Color(255, 0, 0);
            }
            if ($team == "blue") {
                $color = new Color(0, 0, 255);
            }
            if ($team == "yellow") {
                $color = new Color(246, 246, 126);
            }
            if ($team == "green") {
                $color = new Color(72, 253, 72);
            }
            if ($color == null) {
                $color = new Color(0, 0, 0);
            }
            if (isset($this->armor[$player->getName()])) {
                $arm = $player->getArmorInventory();
                $armor = $this->armor[$player->getName()];
                if ($armor == "chainmail") {
                    $player->getArmorInventory()->clearAll();
                    $helm = ItemFactory::getInstance()->get(ItemIds::LEATHER_CAP);
                    $helm->setCustomColor($color);
                    $helm->setUnbreakable(true);
                    if ($enchant !== null) {
                        $helm->addEnchantment($enchant);
                    }
                    $arm->setHelmet($helm);
                    $chest = ItemFactory::getInstance()->get(ItemIds::LEATHER_TUNIC);
                    $chest->setCustomColor($color);
                    if ($enchant !== null) {
                        $chest->addEnchantment($enchant);
                    }
                    $chest->setUnbreakable(true);
                    $arm->setChestplate($chest);
                    $leg = ItemFactory::getInstance()->get(ItemIds::CHAINMAIL_LEGGINGS);
                    if ($enchant !== null) {
                        $leg->addEnchantment($enchant);
                    }
                    $leg->setUnbreakable(true);
                    $leg->setCustomColor($color);
                    $arm->setLeggings($leg);
                    $boots = ItemFactory::getInstance()->get(ItemIds::CHAINMAIL_BOOTS);
                    $boots->setUnbreakable(true);
                    $boots->setCustomColor($color);
                    if ($enchant !== null) {
                        $boots->addEnchantment($enchant);
                    }
                    $arm->setBoots($boots);
                }
                if ($armor == "iron") {
                    $helm = ItemFactory::getInstance()->get(ItemIds::LEATHER_CAP);
                    $helm->setCustomColor($color);
                    $helm->setUnbreakable(true);
                    if ($enchant !== null) {
                        $helm->addEnchantment($enchant);
                    }
                    $arm->setHelmet($helm);
                    $chest = ItemFactory::getInstance()->get(ItemIds::LEATHER_TUNIC);
                    $chest->setCustomColor($color);
                    if ($enchant !== null) {
                        $chest->addEnchantment($enchant);
                    }
                    $chest->setUnbreakable(true);
                    $arm->setChestplate($chest);
                    $leg = ItemFactory::getInstance()->get(ItemIds::IRON_LEGGINGS);
                    if ($enchant !== null) {
                        $leg->addEnchantment($enchant);
                    }
                    $leg->setUnbreakable(true);
                    $arm->setLeggings($leg);
                    $boots = ItemFactory::getInstance()->get(ItemIds::IRON_BOOTS);
                    if ($enchant !== null) {
                        $boots->addEnchantment($enchant);
                    }
                    $boots->setUnbreakable(true);
                    $arm->setBoots($boots);
                }
                if ($armor == "diamond") {
                    $helm = ItemFactory::getInstance()->get(ItemIds::LEATHER_CAP);
                    $helm->setCustomColor($color);
                    $helm->setUnbreakable(true);
                    if ($enchant !== null) {
                        $helm->addEnchantment($enchant);
                    }
                    $arm->setHelmet($helm);
                    $chest = ItemFactory::getInstance()->get(ItemIds::LEATHER_TUNIC);
                    $chest->setCustomColor($color);
                    if ($enchant !== null) {
                        $chest->addEnchantment($enchant);
                    }
                    $chest->setUnbreakable(true);
                    $arm->setChestplate($chest);
                    $leg = ItemFactory::getInstance()->get(ItemIds::DIAMOND_LEGGINGS);
                    if ($enchant !== null) {
                        $leg->addEnchantment($enchant);
                    }
                    $leg->setUnbreakable(true);
                    $arm->setLeggings($leg);
                    $leg->setCustomColor($color);
                    $boots = ItemFactory::getInstance()->get(ItemIds::DIAMOND_BOOTS);
                    if ($enchant !== null) {
                        $boots->addEnchantment($enchant);
                    }
                    $boots->setCustomColor($color);
                    $boots->setUnbreakable(true);
                    $arm->setBoots($boots);
                }
            } else {
                $arm = $player->getArmorInventory();
                $helm = ItemFactory::getInstance()->get(ItemIds::LEATHER_CAP);
                $helm->setCustomColor($color);
                $helm->setUnbreakable(true);
                if ($enchant !== null) {
                    $helm->addEnchantment($enchant);
                }
                $arm->setHelmet($helm);
                $chest = ItemFactory::getInstance()->get(ItemIds::LEATHER_TUNIC);
                $chest->setCustomColor($color);
                if ($enchant !== null) {
                    $chest->addEnchantment($enchant);
                }
                $chest->setUnbreakable(true);
                $arm->setChestplate($chest);
                $leg = ItemFactory::getInstance()->get(ItemIds::LEATHER_PANTS);
                $leg->setCustomColor($color);
                if ($enchant !== null) {
                    $leg->addEnchantment($enchant);
                }
                $leg->setUnbreakable(true);
                $arm->setLeggings($leg);
                $boots = ItemFactory::getInstance()->get(ItemIds::LEATHER_BOOTS);
                $boots->setCustomColor($color);
                if ($enchant !== null) {
                    $boots->addEnchantment($enchant);
                }
                $boots->setUnbreakable(true);
                $arm->setBoots($boots);
            }
        }
    }

    /**
     * @param Player $player
     */

    public function startRespawn(Player $player)
    {
        if (!($player instanceof Player)) return;

        $player->getInventory()->clearAll();
        $player->getEffects()->all()->clear();
        $player->setGamemode(GameMode::SPECTATOR());
        $player->setAllowFlight(true);
        $player->teleport($player->getPosition()->getPosition()->asVector3()->add(0, 5, -1));
        $player->sendTitle("§l§cYOU DIED!");
        $this->respawnC[$player->getName()] = 6;
        $axe = $this->getLessTier($player, true);
        $pickaxe = $this->getLessTier($player, false);
        $this->axe[$player->getId()] = $axe;
        $this->pickaxe[$player->getId()] = $pickaxe;
    }

    /**
     * @param Block $block
     */
    public function addPlacedBlock(Block $block)
    {
        $this->placedBlock[] = $block->getPosition()->getPosition()->asVector3()->__toString();

    }


    public function addexp(Player $player): bool
    {
        $coin = $this->plugin->getServer()->getPluginManager()->getPlugin("Coins");
        $lv = $this->plugin->getServer()->getPluginManager()->getPlugin("Level_System");
        if ($lv == null || $coin == null) {
            return false;
        }
        $exp = mt_rand(1, 100);
        $coins = mt_rand(1, 50);
        $player->sendMessage("§b+$exp EXP §6+ $coins Coins");
        //$this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender($this), " addcoin {$player->getName()} $coins");
        //$this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender($this), " addexp {$player->getName()} $exp");
        return true;
    }

    public function startGame()
    {

        $players = [];
        $this->initshop();
        $this->world->setTime(5000);
        foreach ($this->players as $player) {
            if ($player instanceof Player) {
                $this->addexp($player);
                $api = $this->plugin->getScore();
                $api->remove($player);
                $this->plugin->mysqldata->addscore($player, "playtime");
                $this->kill[$player->getId()] = 0;
                $this->finalkill[$player->getId()] = 0;
                $this->broken[$player->getId()] = 0;
                $this->axe[$player->getId()] = 1;
                $this->tracking[$player->getName()] = $this->getTeam($player);
                $this->pickaxe[$player->getId()] = 1;
                $this->setColorTag($player);
                $player->setImmobile();
                $this->teleport($player);
                $player->setNameTagVisible();
                $player->getInventory()->clearAll();
                $player->setGamemode(GameMode::SURVIVAL());
                $this->setArmor($player);
                $this->setSword($player, ItemFactory::getInstance()->get(ItemIds::WOODEN_SWORD));
                $player->setImmobile(false);
                $player->sendTitle("§l§aFIGHT!");

                $players[$player->getName()] = $player;
            }
        }
        $this->phase = self::PHASE_GAME;
        $this->players = $players;
        $this->prepareWorld();
        $this->removeLobby();


    }

    /**
     * @param $player
     */

    public function teleport($player)
    {
        $team = $this->getTeam($player);
        $vc = Vector3::fromString($this->data["location"][$team]);
        $x = $vc->getX();
        $y = $vc->getY();
        $z = $vc->getZ();
        $player->teleport(new Vector3($x + 0.5, $y + 0.5, $z + 0.5));

    }


    public function calculate($pos1, $pos2)
    {
        $pos1 = Vector3::fromString($pos1);
        $pos2 = Vector3::fromString($pos2);
        $max = new Vector3(max($pos1->getX(), $pos2->getX()), max($pos1->getY(), $pos2->getY()), max($pos1->getZ(), $pos2->getZ()));
        $min = new Vector3(min($pos1->getX(), $pos2->getX()), min($pos1->getY(), $pos2->getY()), min($pos1->getZ(), $pos2->getZ()));
        return $min->add($max->subtract($min)->divide(2)->ceil());
    }


    public function prepareWorld()
    {
        foreach (["red", "blue", "yellow", "green"] as $teams) {
            $this->utilities[$this->level->getFolderName()][$teams]["generator"] = 1;
            $this->utilities[$this->level->getFolderName()][$teams]["sharpness"] = 1;
            $this->utilities[$this->level->getFolderName()][$teams]["protection"] = 1;
            $this->utilities[$this->level->getFolderName()][$teams]["haste"] = 1;
            $this->utilities[$this->level->getFolderName()][$teams]["health"] = 1;
            $this->utilities[$this->level->getFolderName()][$teams]["traps"] = 1;

        }
        $this->initGenerator();
        $this->checkTeam();
        foreach ($this->level->getEntities() as $e) {
            if ($e instanceof ItemEntity) {
                $e->flagForDespawn();
            }
        }
    }

    public function initGenerator()
    {
        foreach ($this->level->getTiles() as $tile) {
            if ($tile instanceof Furnace) {
                $nbt = Entity::createEntity() (new Vector3($tile->x + 0.5, $tile->y + 1, $tile->z + 0.5));
                $path = $this->plugin->getDataFolder() . "diamond.png";
                $skin = $this->plugin->getSkinFromFile($path);
                $nbt->setTag(new CompoundTag('Skin', [
                    new StringTag('Data', $skin->getSkinData()),
                    new StringTag('Name', 'Standard_CustomSlim'),
                    new StringTag('GeometryName', 'geometry.player_head'),
                    new ByteArrayTag('GeometryData', \pocketmine\world\generator\GeneratorManager::getInstance()->GEOMETRY)]));
                $g = new Generator($tile->getPosition()->getWorld(), $nbt);
                $g->type = "gold";
                $g->Glevel = 1;
                $g->setScale(0.000001);
                $g->spawnToAll();
                $tile->getPosition()->getWorld()->setBlock(new Vector3($tile->getPosition()->x, $tile->getPosition()->y, $tile->getPosition()->z), BlockFactory::getInstance()->get(BlockLegacyIds::STONE, 0));
            }
            if ($tile instanceof EnchantTable) {
                $nbt = $this->createBaseNBT(new Vector3($tile->x + 0.5, $tile->y + 4, $tile->z + 0.5));
                $path = $this->plugin->getDataFolder() . "diamond.png";
                $skin = $this->plugin->getSkinFromFile($path);
                $nbt->setTag(new CompoundTag('Skin', [
                    new StringTag('Data', $skin->getSkinData()),
                    new StringTag('Name', 'Standard_CustomSlim'),
                    new StringTag('GeometryName', 'geometry.player_head'),
                    new ByteArrayTag('GeometryData', \pocketmine\world\generator\GeneratorManager::getInstance()->GEOMETRY)]));
                $g = new Generator($tile->getPosition()->getWorld(), $nbt);
                $g->type = "diamond";
                $g->Glevel = 1;
                $g->setScale(1.4);
                $g->yaw = 0;
                $g->spawnToAll();
                $tile->getPosition()->getWorld()->setBlock(new Vector3($tile->x, $tile->y, $tile->z), BlockFactory::getInstance()->get(BlockLegacyIds::STONE, 0));
            }
            if ($tile instanceof Skull) {
                $nbt = Entity::createBaseNBT(new Vector3($tile->x + 0.5, $tile->y + 4, $tile->z + 0.5));
                $path = $this->plugin->getDataFolder() . "emerald.png";
                $skin = $this->plugin->getSkinFromFile($path);
                $nbt->setTag(new CompoundTag('Skin', [
                    new StringTag('Data', $skin->getSkinData()),
                    new StringTag('Name', 'Standard_CustomSlim'),
                    new StringTag('GeometryName', 'geometry.player_head'),
                    new ByteArrayTag('GeometryData', \pocketmine\world\generator\GeneratorManager::getInstance()->GEOMETRY)]));
                $g = new Generator($tile->getPosition()->getWorld(), $nbt);
                $g->type = "emerald";
                $g->Glevel = 1;
                $g->yaw = 0;
                $g->setScale(1.4);
                $g->spawnToAll();
                $tile->getPosition()->getWorld()->setBlock(new Vector3($tile->getPosition()->x, $tile->getPosition()->y, $tile->getPosition()->z), BlockFactory::getInstance()->get(BlockLegacyIds::STONE, 0));
            }
        }
    }

    /**
     * @param string $type
     * @param $level
     */

    public function upgradeGeneratorTier(string $type, $level)
    {
        if ($type == "diamond") {
            foreach ($this->level->getEntities() as $e) {
                if ($e instanceof Generator) {
                    if ($e->type == "diamond") {
                        $e->Glevel = $level;
                    }
                }
            }
        }
        if ($type == "emerald") {
            foreach ($this->level->getEntities() as $e) {
                if ($e instanceof Generator) {
                    if ($e->type == "emerald") {
                        $e->Glevel = $level;
                    }
                }
            }
        }
    }

    /**
     * @param string $team
     * @return bool
     */
    public function bedStatus(string $team): bool
    {
        $status = null;
        $vc = Vector3::fromString($this->data["bed"][$team]);
        if ($this->level->getBlockAt($vc->x, $vc->y, $vc->z) instanceof Bed) {
            $status = true;
        } else {
            $status = false;
        }
        return $status;
    }


    public function destroyAllBeds()
    {
        $this->broadcastMessage("§eAll bed was destoyed");
        foreach (["red", "blue", "yellow", "green"] as $t) {
            $pos = Vector3::fromString($this->data["bed"][$t]);
            $bed = $this->level->getBlockAt($pos->x, $pos->y, $pos->z);
            if ($bed instanceof Bed) {
                $next = $bed->getOtherHalf();
                $this->level->setBlock($bed, \pocketmine\block\BlockFactory::getInstance()->get(0, 0));
                $this->level->setBlock($next, \pocketmine\block\BlockFactory::getInstance()->get(0, 0));
                foreach ($this->players as $player) {
                    if ($player instanceof Player) {
                        $player->sendTitle("§l§CBED DESTORYED", "§r§cyou will no longer respawn");
                        $this->addSound($player, 'mob.wither.death');
                    }
                }
            }
        }
    }

    public function checkTeam()
    {
        if ($this->getCountTeam("red") <= 0) {
            $pos = Vector3::fromString($this->data["bed"]["red"]);
            if (($bed = $this->level->getBlockAt($pos->x, $pos->y, $pos->z)) instanceof Bed) {
                $this->level->setBlock($bed, \pocketmine\block\BlockFactory::getInstance()->get(0, 0));
                $this->level->setBlock($bed->getOtherHalf(), \pocketmine\block\BlockFactory::getInstance()->get(0, 0));
            }
            foreach ($this->level->getEntities() as $g) {
                if ($g instanceof Generator) {
                    if ($g->getPosition()->asVector3()->distance($pos) < 20) {
                        $g->close();
                    }
                }
            }
        }
        if ($this->getCountTeam("blue") <= 0) {
            $pos = Vector3::fromString($this->data["bed"]["blue"]);
            if (($bed = $this->world->getBlockAt($pos->x, $pos->y, $pos->z)) instanceof Bed) {
                $this->world->setBlock($bed, \pocketmine\block\BlockFactory::getInstance()->get(0, 0));
                $this->world->setBlock($bed->getOtherHalf(), \pocketmine\block\BlockFactory::getInstance()->get(0, 0));
            }
            foreach ($this->world->getEntities() as $g) {
                if ($g instanceof Generator) {
                    if ($g->getPosition()->asVector3()->distance($pos) < 20) {
                        $g->close();
                    }
                }
            }
        }
        if ($this->getCountTeam("yellow") <= 0) {
            $pos = Vector3::fromString($this->data["bed"]["yellow"]);
            if (($bed = $this->world->getBlockAt($pos->x, $pos->y, $pos->z)) instanceof Bed) {
                $this->world->setBlock($bed, \pocketmine\block\BlockFactory::getInstance()->get(0, 0));
                $this->world->setBlock($bed->getOtherHalf(), \pocketmine\block\BlockFactory::getInstance()->get(0, 0));
            }
            foreach ($this->world->getEntities() as $g) {
                if ($g instanceof Generator) {
                    if ($g->getPosition()->getPosition()->asVector3()->distance($pos) < 20) {
                        $g->close();
                    }
                }
            }
        }
        if ($this->getCountTeam("green") <= 0) {
            $pos = Vector3::fromString($this->data["bed"]["green"]);
            if (($bed = $this->world->getBlockAt($pos->x, $pos->y, $pos->z)) instanceof Bed) {
                $this->world->setBlock($bed, BlockFactory::getInstance()->get(0, 0));
                $this->world->setBlock($bed->getOtherHalf(), BlockFactory::getInstance()->get(0, 0));
            }
            foreach ($this->world->getEntities() as $g) {
                if ($g instanceof Generator) {
                    if ($g->getPosition()->asVector3()->distance($pos) < 20) {
                        $g->close();
                    }
                }
            }
        }
    }

    /**
     * @param $team
     * @param null $player
     */
    public function breakbed($team, $player = null)
    {
        if (!isset($this->data["bed"][$team])) return;
        $pos = Vector3::fromString($this->data["bed"][$team]);
        $bed = $bed = $this->world->getBlockAt($pos->x, $pos->y, $pos->z);
        if ($bed instanceof Bed) {
            $next = $bed->getOtherHalf();
            $this->level->addParticle(new BlockBreakParticle($bed, $bed));
            $this->level->addParticle(new BlockBreakParticle($next, $bed));
            $this->level->setBlock($bed, BlockFactory::getInstance()->get(0, 0));
            $this->level->setBlock($next, BlockFactory::getInstance()->get(0, 0));
        }
        $c = null;
        if ($team == "red") {
            $c = "§c";
        }
        if ($team == "blue") {
            $c = "§9";
        }
        if ($team == "yellow") {
            $c = "§e";
        }
        if ($team == "green") {
            $c = "§a";
        }
        $tn = ucwords($team);
        if ($player instanceof Player) {
            $this->broadcastMessage("§l§fBED DECONTRUCTION > §r§e{$c}{$tn} §ebed was destroyed by §r§f {$player->getDisplayName()}");
            if (isset($this->broken[$player->getId()])) {
                $this->broken[$player->getId()]++;
            }
        }
        foreach ($this->players as $p) {
            if ($p instanceof Player && $this->getTeam($p) == $team) {
                $p->sendTitle("§l§CBED DESTORYED", "§r§cyou will no longer respawn");
                $this->addSound($p, 'mob.wither.death');
            }
        }
    }

    /**
     * @return string
     */

    public function getFireworksColor(): string
    {
        $colors = [
            Fireworks::COLOR_BLACK,
            Fireworks::COLOR_RED,
            Fireworks::COLOR_DARK_GREEN,
            Fireworks::COLOR_BROWN,
            Fireworks::COLOR_BLUE,
            Fireworks::COLOR_DARK_PURPLE,
            Fireworks::COLOR_DARK_AQUA,
            Fireworks::COLOR_GRAY,
            Fireworks::COLOR_DARK_GRAY,
            Fireworks::COLOR_PINK,
            Fireworks::COLOR_GREEN,
            Fireworks::COLOR_YELLOW,
            Fireworks::COLOR_LIGHT_AQUA,
            Fireworks::COLOR_DARK_PINK,
            Fireworks::COLOR_GOLD,
            Fireworks::COLOR_WHITE
        ];

        return $colors[array_rand($colors)];
    }

    /**
     * @param Player $player
     */

    public function addRocket(Player $player)
    {
        $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player): void {
            $fw = ItemFactory::getInstance()->get(ItemIds::FIREWORKS);
            if ($fw instanceof Fireworks) {
                $fw->addExplosion(mt_rand(0, 4), $this->getFireworksColor(), "", true, true);
                $fw->setFlightDuration(3);
                $world = $player->getWorld();
                if ($world instanceof World) {
                    $x = $player->getPosition()->getX();
                    $y = $player->getPosition()->getY();
                    $z = $player->getPosition()->getZ();
                    if (!$player == null) {
                        $nbt = FireworksRocket::createBaseNBT(new Vector3($x, $y, $z), new Vector3(0.001, 0.05, 0.001), lcg_value() * 360, 90);
                        $entity = FireworksRocket::createEntity("FireworksRocket", $world, $nbt, $fw);

                        if ($entity instanceof FireworksRocket) {
                            $entity->spawnToAll();
                        }
                    }
                }
            }
        }), 15);
        $player->getWorld()->broadcastLevelSoundEvent(new Vector3($player->getPosition()->getX(), $player->getPosition()->getY(), $player->getPosition()->getZ()), LevelSoundEvent::SOUND_LAUNCH);

    }

    public function destroyEntity()
    {
        foreach ($this->level->getEntities() as $g) {
            if ($g instanceof Generator) {
                $g->close();
            }
            if ($g instanceof EnderDragon) {
                $g->close();
            }
            if ($g instanceof Golem) {
                $g->close();
            }
            if ($g instanceof Egg) {
                $g->close();
            }
            if ($g instanceof Bedbug) {
                $g->close();
            }
            if ($g instanceof Fireball) {
                $g->close();
            }
            if ($g instanceof ItemEntity) {
                $g->close();
            }
            if ($g instanceof ShopVillager) {
                $g->close();
            }
            if ($g instanceof UpgradeVillager) {
                $g->close();
            }
        }
        foreach ($this->level->getPlayers() as $p) {
            unset($this->tempTeam[$p->getName()]);
            $this->placedBlock[] = [];
        }
    }

    /**
     * @param string $team
     */

    public function Wins(string $team)
    {
        $this->destroyEntity();
        foreach ($this->level->getPlayers() as $p) {
            $p->setNametag($p->getDisplayName());
            $p->setScoreTag("");
        }
        foreach ($this->players as $player) {
            $this->TopFinalKills($player);
            $cfg = new Config($this->plugin->getDataFolder() . "finalkills.yml", Config::YAML);
            $cfg->set($player->getName(), 0);
            $cfg->save();
            if ($this->getTeam($player) == $team) {
                $player->setHealth(20);
                $player->getHungerManager()->getHungerManager()->setFood(20);
                $player->getInventory()->clearAll();
                $player->setGamemode(GameMode::ADVENTURE());
                $this->plugin->mysqldata->addscore($player, "victory");
                $player->getArmorInventory()->clearAll();
                $player->getCursorInventory()->clearAll();
                $this->addRocket($player);
                $this->addRocket($player);
                $this->addRocket($player);
                $this->addRocket($player);
                $this->addRocket($player);
                $player->sendTitle("§l§eVICTORY");
                $this->addSound($player, "random.levelup", 1.25);
                $api = $this->plugin->getScore();
                $api->remove($player);
                $player->getInventory()->clearAll();
                $player->getInventory()->setItem(8, ItemFactory::getInstance()->get(ItemIds::BED, 14)->setCustomName("§aReturn to lobby"));
                $player->getInventory()->setItem(0, ItemFactory::getInstance()->get(ItemIds::PAPER)->setCustomName("§aPlay Again"));
            }
        }
        $this->placedBlock[] = [];
        $this->utilities[$this->level->getFolderName()] = [];
        $this->axe = [];
        $this->pickaxe = [];
        $this->milk = [];
        $this->inChest = [];
        $teamName = [
            "red" => "§r§c Red",
            "blue" => "§r§9 Blue",
            "green" => "§r§a Green",
            "yellow" => "§r§e Yellow"
        ];
        $this->broadcastMessage("§aTeam $teamName[$team] §eVictory");
        $this->phase = self::PHASE_RESTART;
    }


    /**
     * @param Player $player
     */
    public function TopFinalKills(Player $player): void
    {
        if ($player instanceof Player) {
            $player->sendMessage("§l§e===================================");
            $player->sendMessage("         §l§aTOP FINAL KILLS          ");
            $player->sendMessage("                                     ");
            $kconfig = new Config($this->plugin->getDataFolder() . "finalkills.yml", Config::YAML, [$player->getName() => 0]);
            $kills = $kconfig->getAll();
            arsort($kills);
            $i = 0;
            foreach ($kills as $playerName => $killCount) {
                $i++;
                if ($i < 4 && $killCount) {
                    switch ($i) {
                        case 1:
                            $satu = "§a1 st  §f" . $playerName . " - §f" . $killCount . "\n \n \n";
                            $player->sendMessage($satu);
                            break;
                        case 2:
                            $dua = "§a2 st §f" . $playerName . " - §f" . $killCount . "\n \n \n";
                            $player->sendMessage($dua);
                            break;
                        case 3:
                            $tiga = "§a3 st §f" . $playerName . " - §f" . $killCount . "\n \n \n";
                            $player->sendMessage($tiga);
                            break;
                        default:

                            break;
                    }
                }
            }
            $player->sendMessage("                                     ");
            $player->sendMessage("§l§e===================================");
        }
    }

    public function draw()
    {
        $this->destroyEntity();
        foreach ($this->level->getPlayers() as $p) {
            $p->setScoreTag("");
            $p->setNameTag($p->getDisplayName());

        }
        foreach ($this->players as $player) {
            if ($player === null || (!$player instanceof Player) || (!$player->isOnline())) {
                $this->phase = self::PHASE_RESTART;
                return;
            }
            $player->setHealth(20);
            $player->getHungerManager()->getHungerManager()->setFood(20);
            $player->setGamemode(GameMode::ADVENTURE());
            $player->getInventory()->clearAll();
            $player->getArmorInventory()->clearAll();
            $player->getCursorInventory()->clearAll();
            $api = $this->plugin->getScore();
            $api->remove($player);
            $this->unsetPlayer($player);
            $player->getInventory()->setItem(8, ItemFactory::getInstance()->get(ItemIds::BED, 14)->setCustomName("§aReturn to lobby"));
            $player->getInventory()->setItem(0, ItemFactory::getInstance()->get(ItemIds::PAPER)->setCustomName("§aPlay Again"));
        }
        $this->placedBlock[] = [];
        $this->utilities[$this->level->getFolderName()] = [];
        $this->axe = [];
        $this->pickaxe = [];
        $this->milk = [];
        $this->inChest = [];
        $this->broadcastMessage("§l§cGAME OVER", self::MSG_TITLE);
        $this->phase = self::PHASE_RESTART;
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function inGame(Player $player): bool
    {
        if ($this->phase == self::PHASE_LOBBY) {
            $inGame = false;
            foreach ($this->players as $players) {
                if ($players->getId() == $player->getId()) {
                    $inGame = true;
                }
            }
            return $inGame;
        } else {
            return isset($this->players[$player->getName()]);
        }
    }

    /**
     * @param string $message
     * @param int $id
     * @param string $subMessage
     */
    public function broadcastMessage(string $message, int $id = 0, string $subMessage = "")
    {
        foreach ($this->level->getPlayers() as $player) {
            switch ($id) {
                case self::MSG_MESSAGE:
                    $player->sendMessage($message);
                    break;
                case self::MSG_TIP:
                    $player->sendTip($message);
                    break;
                case self::MSG_POPUP:
                    $player->sendPopup($message);
                    break;
                case self::MSG_TITLE:
                    $player->sendTitle($message, $subMessage);
                    break;
            }
        }
    }

    /**
     * @param InventoryTransactionEvent $event
     */

    public function onTrans(InventoryTransactionEvent $event)
    {
        $transaction = $event->getTransaction();
        if ($this->phase !== self::PHASE_GAME) return;
        foreach ($transaction->getActions() as $action) {
            $item = $action->getSourceItem();
            $source = $transaction->getSource();
            if ($source instanceof Player) {
                if ($this->inGame($source)) {
                    if ($action instanceof SlotChangeAction) {
                        if ($action->getInventory() instanceof PlayerInventory) {
                            if ($this->phase == self::PHASE_LOBBY) {
                                $event->cancel();
                            }

                            if ($this->phase == self::PHASE_RESTART) {
                                $event->cancel();
                            }
                        }
                        if (isset($this->inChest[$source->getId()]) && $action->getInventory() instanceof PlayerInventory) {
                            if ($item instanceof Pickaxe || $item instanceof Axe) {
                                $event->cancel();
                            }
                        }
                        if ($action->getInventory() instanceof ArmorInventory) {
                            if ($item instanceof Armor) {
                                $event->cancel();
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param ProjectileHitEntityEvent $event
     */


    public function hitEntity(ProjectileHitEntityEvent $event)
    {
        $pro = $event->getEntity();
        $hitEntity = $event->getEntityHit();
        $owner = $pro->getOwningEntity();
        if ($pro instanceof Arrow) {
            if ($owner instanceof Player && $hitEntity instanceof Player) {
                if ($this->inGame($owner)) {
                    $owner->sendMessage("§b{$hitEntity->getDisplayName()} §fis now {$hitEntity->getHealth()} heart");

                }
            }
        }
    }

    /**
     * @param ItemSpawnEvent $event
     */

    public function itemSpawnEvent(ItemSpawnEvent $event)
    {
        $entity = $event->getEntity();
        if ($entity->level->getFolderName() !== $this->level->getFolderName()) return;
        $entities = $entity->getWorld()->getNearbyEntities($entity->getBoundingBox()->expandedCopy(1, 1, 1));
        if (empty($entities)) {
            return;
        }
        if ($entity instanceof ItemEntity) {
            $originalItem = $entity->getItem();
            $i = 0;
            foreach ($entities as $e) {
                if ($e instanceof ItemEntity and $entity->getId() !== $e->getId()) {
                    $item = $e->getItem();
                    if (in_array($originalItem->getId(), [ItemIds::DIAMOND, ItemIds::EMERALD])) {
                        if ($item->getId() === $originalItem->getId()) {
                            $e->flagForDespawn();
                            $entity->getItem()->setCount(is_float($originalItem->getCount()) ? 0 : ($originalItem->getCount() + is_float($item->getCount()) ? 0 : $item->getCount()));
                        }
                    }
                }
            }
        }
    }

    /**
     * @param CraftItemEvent $event
     */

    public function onCraftItem(CraftItemEvent $event)
    {
        $player = $event->getPlayer();
        if ($player instanceof Player) {
            if ($this->inGame($player)) {
                $event->cancel();
            }
        }
    }

    /**
     * @param PlayerItemConsumeEvent $event
     */

    public function onConsume(PlayerItemConsumeEvent $event)
    {
        $player = $event->getPlayer();
        $item = $event->getItem();
        if ($this->inGame($player)) {
            if ($item->getId() == 373 && $item->getDamage() == 16) {
                $event->cancel();
                $player->getInventory()->setItemInHand(\pocketmine\item\ItemFactory::getInstance()->get(0));
                $eff = new EffectInstance(\pocketmine\data\bedrock\EffectIdMap::getInstance()->fromId(Effect::SPEED), 900, 1);
                $eff->setVisible(true);
                $player->getEffects()->all()->add($eff);
            }
            if ($item->getId() == 373 && $item->getDamage() == 11) {
                $event->cancel();
                $player->getInventory()->setItemInHand(\pocketmine\item\ItemFactory::getInstance()->get(0));
                $eff = new EffectInstance(\pocketmine\data\bedrock\EffectIdMap::getInstance()->fromId(Effect::JUMP_BOOST), 900, 3);
                $eff->setVisible(true);
                $player->getEffects()->all()->add($eff);
            }
            if ($item->getId() == 373 && $item->getDamage() == 7) {
                $event->cancel();
                $player->getInventory()->setItemInHand(\pocketmine\item\ItemFactory::getInstance()->get(0));
                $eff = new EffectInstance(\pocketmine\data\bedrock\EffectIdMap::getInstance()->fromId(Effect::INVISIBILITY), 600, 1);
                $eff->setVisible(true);
                $player->getEffects()->all()->add($eff);
                $this->setInvis($player, true);
            }
            if ($item->getId() == ItemIds::BUCKET && $item->getDamage() == 1) {
                $event->cancel();
                $player->getInventory()->setItemInHand(ItemFactory::getInstance()->get(0));
                $this->milk[$player->getId()] = 30;
                $player->sendMessage("§eTrap effected in 30 seconds!");
            }
        }
    }

    public function setInvis($player, $value)
    {
        $arm = $player->getArmorInventory();
        if ($value) {
            $this->invis[$player->getId()] = $player;
            $hide = $this->armorInvis($player);
            foreach ($this->players as $p) {
                if ($player->getId() == $p->getId()) {
                    $pk2 = new InventoryContentPacket();
                    $pk2->windowId = $player->getWindowId($arm);
                    $pk2->items = array_map([ItemStackWrapper::class, 'legacy'], $arm->getContents(true));
                    $player->getNetworkSession()->sendDataPacket($pk2);
                } else {
                    if ($this->getTeam($player) !== $this->getTeam($p)) {
                        $p->getNetworkSession()->sendDataPacket($hide);
                    }
                }
            }
        } else {
            if (isset($this->invis[$player->getId()])) {
                unset($this->invis[$player->getId()]);
            }
            $player->setInvisible(false);
            $nohide = $this->armorInvis($player, false);
            foreach ($this->players as $p) {
                if ($player->getId() == $p->getId()) {

                    $pk2 = new InventoryContentPacket();
                    $pk2->windowId = $player->getWindowId($arm);
                    $pk2->items = array_map([ItemStackWrapper::class, 'legacy'], $arm->getContents(true));
                    $player->getNetworkSession()->sendDataPacket($pk2);
                } else {
                    if ($this->getTeam($player) !== $this->getTeam($p)) {
                        $p->getNetworkSession()->sendDataPacket($nohide);
                    }
                }
            }
        }
    }

    public function armorInvis($player, bool $hide = true): MobArmorEquipmentPacket
    {
        if ($hide) {
            $pk = new MobArmorEquipmentPacket();
            $pk->entityRuntimeId = $player->getId();
            $pk->head = ItemStackWrapper::legacy(\pocketmine\item\ItemFactory::getInstance()->get(ItemIds::AIR));
            $pk->chest = ItemStackWrapper::legacy(\pocketmine\item\ItemFactory::getInstance()->get(ItemIds::AIR));
            $pk->legs = ItemStackWrapper::legacy(\pocketmine\item\ItemFactory::getInstance()->get(ItemIds::AIR));
            $pk->feet = ItemStackWrapper::legacy(\pocketmine\item\ItemFactory::getInstance()->get(ItemIds::AIR));
            $pk->encode();
            return $pk;
        } else {
            $arm = $player->getArmorInventory();
            $pk = new MobArmorEquipmentPacket();
            $pk->entityRuntimeId = $player->getId();
            $pk->head = $arm->getHelmet();
            $pk->chest = $arm->getChestplate();
            $pk->legs = $arm->getLeggings();
            $pk->feet = $arm->getBoots();
            $pk->encode();
            return $pk;
        }
    }

    /**
     * @param EntityExplodeEvent $event
     */
    public function onExplode(EntityExplodeEvent $event)
    {
        $tnt = $event->getEntity();

        if ($tnt->getWorld()->getFolderName() !== $this->level->getFolderName()) return;

        if ($tnt instanceof PrimedTNT || $tnt instanceof Fireball) {
            $newList = [];
            foreach ($event->getBlockList() as $block) {
                if ($block->getId() !== BlockLegacyIds::OBSIDIAN && $block->getId() !== 241) {
                    if (in_array($block->getPosition()->getPosition()->asVector3()->__toString(), $this->placedBlock)) {
                        $newList[] = $block;
                    }
                }
            }
            $event->setBlockList($newList);
        }
    }

    public function isAllowedPlace(Vector3 $pos)
    {
        $red = Vector3::fromString($this->data["location"]["red"]);
        $blue = Vector3::fromString($this->data["location"]["blue"]);
        $yellow = Vector3::fromString($this->data["location"]["yellow"]);
        $green = Vector3::fromString($this->data["location"]["green"]);
        if ($pos->distance($red) > 8 || $pos->distance($blue) > 8 || $pos->distance($yellow) > 8 || $pos->distance($green) > 8) {
            return true;
        }
        return false;
    }

    public function leavesDecayEvent(LeavesDecayEvent $event)
    {
        $event->cancel();
    }

    /**
     * @param PlayerMoveEvent $event
     */
    public function onMove(PlayerMoveEvent $event)
    {
        $player = $event->getPlayer();

        if ($this->inGame($player)) {
            if ($this->phase == self::PHASE_LOBBY) {
                $lv = Vector3::fromString($this->data["lobby"]);
                $p = $lv->getY() - 3;
                if ($player->getWorld()->getFolderName() == $this->level->getFolderName()) {
                    if ($player->getPosition()->getY() < $p) {
                        $player->teleport(Vector3::fromString($this->data["lobby"]));
                    }
                }
            }

            if ($this->phase == self::PHASE_GAME) {
                if (isset($this->milk[$player->getId()])) return;
                if (isset($this->spectators[$player->getName()])) return;
                foreach (["red", "blue", "yellow", "green"] as $teams) {
                    $pos = Vector3::fromString($this->data["bed"][$teams]);
                    if ($player->distance($pos) < 4) {
                        if ($this->getTeam($player) !== $teams) {
                            if (isset($this->itstrap[$teams])) {
                                $this->utilities[$this->level->getFolderName()][$teams]["traps"]--;
                                unset($this->itstrap[$teams]);
                                $eff = new EffectInstance(\pocketmine\data\bedrock\EffectIdMap::getInstance()->fromId(Effect::BLINDNESS), 160, 0);
                                $eff->setVisible(true);
                                $player->getEffects()->all()->add($eff);
                                $eff = new EffectInstance(\pocketmine\data\bedrock\EffectIdMap::getInstance()->fromId(Effect::SLOWNESS), 160, 1);
                                $eff->setVisible(true);
                                $player->getEffects()->all()->add($eff);
                                foreach ($this->players as $p) {
                                    if ($this->getTeam($p) == $teams) {
                                        $p->sendTitle("§l§cTRAP TRIGGERED");
                                    }
                                }
                            }
                            if (isset($this->minertrap[$teams])) {
                                $this->utilities[$player->getWorld()->getFolderName()][$teams]["traps"]--;
                                unset($this->minertrap[$teams]);
                                $eff = new EffectInstance(\pocketmine\data\bedrock\EffectIdMap::getInstance()->fromId(Effect::FATIGUE), 160, 0);
                                $eff->setVisible(true);
                                $player->getEffects()->all()->add($eff);
                                foreach ($this->players as $p) {
                                    if ($this->getTeam($p) == $teams) {
                                        $p->sendTitle("§l§cTRAP TRIGGERED");
                                    }
                                }
                            }
                            if (isset($this->alarmtrap[$teams])) {
                                $this->utilities[$player->getWorld()->getFolderName()][$teams]["traps"]--;
                                unset($this->alarmtrap[$teams]);
                                foreach ($this->players as $p) {
                                    if ($this->getTeam($p) == $teams) {
                                        $p->sendTitle("§l§cTRAP TRIGGERED");
                                    }
                                }
                            }
                            if (isset($this->countertrap[$teams])) {
                                $this->utilities[$player->getWorld()->getFolderName()][$teams]["traps"]--;
                                unset($this->countertrap[$teams]);
                                foreach ($this->players as $p) {
                                    if ($this->getTeam($p) == $teams) {
                                        $p->sendTitle("§l§cTRAP TRIGGERED");
                                        $eff = new EffectInstance(\pocketmine\data\bedrock\EffectIdMap::getInstance()->fromId(Effect::SPEED), 300, 0);
                                        $eff->setVisible(true);
                                        $p->getEffects()->all()->add($eff);
                                        $eff = new EffectInstance(\pocketmine\data\bedrock\EffectIdMap::getInstance()->fromId(Effect::JUMP_BOOST), 300, 1);
                                        $eff->setVisible(true);
                                        $p->getEffects()->all()->add($eff);

                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }


    public function projectileLaunchevent(ProjectileLaunchEvent $event)
    {
        $pro = $event->getEntity();
        $player = $pro->getOwningEntity();
        if ($player instanceof Player) {
            if ($this->inGame($player) && $this->phase == 1) {
                if ($pro instanceof Egg) {
                    if ($this->getTeam($player) !== "") {
                        $team = $this->getTeam($player);
                        $pro->team = $team;
                        $pro->arena = $this;
                        $pro->owner = $player;
                    }
                }
                if ($pro instanceof Snowball) {
                    $this->spawnBedbug($pro->getPosition()->asVector3(), $player->getWorld(), $player);
                }
            }
        }
    }


    public function onChat(PlayerChatEvent $event)
    {
        $player = $event->getPlayer();
        $msg = $event->getMessage();
        $level = $this->getLevels($player);
        $team = $this->getTeam($player);
        if ($event->isCancelled()) return;
        if ($this->phase == self::PHASE_LOBBY) {
            foreach ($this->players as $players) {
                $players->sendMessage("§7$level §r{$player->getDisplayName()} §7: {$event->getMessage()}");
            }
        }
        if ($this->phase == self::PHASE_RESTART) {
            foreach ($this->players as $players) {
                $players->sendMessage("§7$level §r{$player->getDisplayName()} §7: {$event->getMessage()}");
            }
        }
        if (isset($this->spectators[$player->getName()])) {
            foreach ($this->level->getPlayers() as $pt) {
                $pt->sendMessage("§7[SPECTATOR] §r{$player->getDisplayName()}: §7{$msg}");
            }
        }
        if (!$this->inGame($player)) return;
        if ($this->phase == self::PHASE_GAME) {
            $f = $msg[0];
            if ($msg === "!t") {
                if ($player->isOp()) {
                    $this->reduceTime($player);
                } else {
                    foreach ($this->players as $pt) {
                        if ($this->getTeam($pt) == $team) {
                            if (!isset($this->spectators[$player->getName()])) {
                                $pt->sendMessage("§aTEAM > §r{$player->getDisplayName()}: §7{$msg}");
                            }
                        }
                    }
                }
            } elseif ($f == "!") {
                if (!isset($this->spectators[$player->getName()])) {
                    $msg = str_replace("!", "", $msg);
                    if (trim($msg) !== "") {
                        $color = ["red" => "§c[RED]", "blue" => "§9[BLUE]", "green" => "§a[GREEN]", "yellow" => "§e[YELLOW]"];
                        $team = $color[$this->getTeam($player)];
                        $this->broadcastMessage("§6SHOUT > §r§7 $team §r $level §r{$player->getDisplayName()}: §7{$msg}");
                    }
                }
            } else {
                foreach ($this->players as $pt) {
                    if ($this->getTeam($pt) == $team) {
                        $pt->sendMessage("§aTEAM > §r{$player->getDisplayName()}: §7{$msg}");
                    }
                }
            }
        }

        $event->cancel();

    }

    public function onUpdateBlock(BlockUpdateEvent $event)
    {
        $event->cancel();
    }

    public function reduceTime($player)
    {
        if (in_array($this->scheduler->upgradeNext[$this->data["level"]], [1, 2, 3, 4])) {
            if ($this->scheduler->upgradeTime[$this->data["level"]] > 70) {
                $this->scheduler->upgradeTime[$this->data["level"]] -= 50;
            } else {
                $player->sendMessage("§cPlease wait to reduce time again!");
            }
        } else {
            if ($this->scheduler->upgradeNext[$this->data["level"]] == 5) {
                if ($this->scheduler->bedgone[$this->data["level"]] > 70) {
                    $this->scheduler->bedgone[$this->data["level"]] -= 50;
                } else {
                    $player->sendMessage("§cPlease wait to reduce time again!");
                }
            }
            if ($this->scheduler->upgradeNext[$this->data["level"]] == 6) {
                if ($this->scheduler->suddendeath[$this->data["level"]] > 70) {
                    $this->scheduler->suddendeath[$this->data["level"]] -= 50;
                } else {
                    $player->sendMessage("§cPlease wait to reduce time again!");
                }
            }
            if ($this->scheduler->upgradeNext[$this->data["level"]] == 7) {
                if ($this->scheduler->gameover[$this->data["level"]] > 70) {
                    $this->scheduler->gameover[$this->data["level"]] -= 50;
                } else {
                    $player->sendMessage("§cPlease wait to reduce time again!");
                }
            }
        }
    }


    public function onExhaust(PlayerExhaustEvent $event)
    {
        $player = $event->getPlayer();
        if ($player instanceof Generator) {
            $event->cancel();
        }
        if ($this->phase == self::PHASE_LOBBY || $this->phase == self::PHASE_RESTART) {
            $event->cancel();
        }
    }

    public function onRegen(EntityRegainHealthEvent $event)
    {
        $player = $event->getEntity();
        if ($event->isCancelled()) return;
        if ($player instanceof Player) {
            if ($event->getRegainReason() == $event::CAUSE_SATURATION) {
                $event->setAmount(0.001);
            }
        }
    }

    public function onOpenInventory(InventoryOpenEvent $event)
    {
        $player = $event->getPlayer();
        $inv = $event->getInventory();
        if ($this->inGame($player)) {
            if ($this->phase == self::PHASE_GAME) {
                if ($inv instanceof ChestInventory || $inv instanceof EnderChestInventory) {
                    $this->inChest[$player->getId()] = $player;
                }
            }
        }
    }

    public function onCloseInventory(InventoryCloseEvent $event)
    {
        $player = $event->getPlayer();
        $inv = $event->getInventory();
        if ($this->inGame($player)) {
            if ($this->phase == self::PHASE_GAME) {
                if ($inv instanceof ChestInventory || $inv instanceof EnderChestInventory) {
                    if (isset($this->inChest[$player->getId()])) {
                        unset($this->inChest[$player->getId()]);
                    }
                }
            }
        }
    }


    public function onBlockBreak(BlockBreakEvent $event)
    {
        $block = $event->getBlock();
        $player = $event->getPlayer();
        $team = null;
        if (isset($this->spectators[$player->getName()])) {
            $event->cancel();
        }
        if ($this->inGame($player) && $this->phase == 0) {
            $event->cancel();
        }
        if ($this->inGame($player) && $this->phase == 2) {
            $event->cancel();
        }
        if ($this->inGame($player) && $this->phase == self::PHASE_GAME) {
            $event->setXpDropAmount(0);
            if ($block instanceof Bed) {
                $next = $block->getOtherHalf();
                $red = $this->data["bed"]["red"];
                $blue = $this->data["bed"]["blue"];
                $yellow = $this->data["bed"]["yellow"];
                $green = $this->data["bed"]["green"];

                if (in_array(($pos = (new Vector3($block->getPosition()->x, $block->getPosition()->y, $block->getPosition()->z))->__toString()), [$red, $blue, $yellow, $green])) {
                    if ($pos == $red) {
                        $team = "red";
                    }
                    if ($pos == $blue) {
                        $team = "blue";
                    }
                    if ($pos == $yellow) {
                        $team = "yellow";
                    }
                    if ($pos == $green) {
                        $team = "green";
                    }
                    if ($this->getTeam($player) !== $team) {
                        $this->breakbed($team, $player);
                        $event->setDrops([]);
                        $this->plugin->mysqldata->addscore($player, "bedbroken");
                    } else {
                        $player->sendMessage("§cyou can't break bed your team");
                        $event->cancel();
                    }

                }
                if (in_array(($pos = (new Vector3($next->x, $next->y, $next->z))->__toString()), [$red, $blue, $yellow, $green])) {
                    $team = null;
                    if ($pos == $red) {
                        $team = "red";
                    }
                    if ($pos == $blue) {
                        $team = "blue";
                    }
                    if ($pos == $yellow) {
                        $team = "yellow";
                    }
                    if ($pos == $green) {
                        $team = "green";
                    }
                    if ($this->getTeam($player) !== $team && !$player->isSpectator()) {
                        $this->breakbed($team, $player);
                        $event->setDrops([]);
                        $this->plugin->mysqldata->addscore($player, "bedbroken");
                    } else {
                        $player->sendMessage("§cyou can't break bed your team");
                        $event->cancel();
                    }

                }
            } else {

                if (!in_array($block->getPosition()->getPosition()->asVector3()->__toString(), $this->placedBlock)) {
                    $event->cancel(true);
                    if (!$player->isSpectator()) {
                        $player->sendMessage("§cYou can't break block in here");
                    }
                    return;

                } else {
                    $ar = array_values($this->placedBlock);
                    unset($ar[$block->getPosition()->getPosition()->asVector3()->__toString()]);

                }
            }
        }
    }

    public function onDamageChild(EntityDamageByChildEntityEvent $event)
    {
        if ($event->getChild() instanceof Egg) {
            $event->cancel();
        }
    }

    public function onPlace(BlockPlaceEvent $event)
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if (isset($this->spectators[$player->getName()])) {
            $event->cancel();
        }
        if ($this->inGame($player) && $this->phase == 0) {
            $event->cancel();
        }
        if ($this->inGame($player) && $this->phase == 2) {
            $event->cancel();
        }
        if ($this->inGame($player) && $this->phase == self::PHASE_GAME && !$player->isSpectator()) {
            if ($block->getPosition()->getY() > 256) {
                $event->cancel();
                $player->sendMessage("§cPlaced block is max!");
            }
            if ($block->getId() == BlockLegacyIds::TNT) {
                $ih = $player->getInventory()->getItemInHand();
                $block->ignite(50);
                $event->cancel();
                $ih->setCount($ih->getCount() - 1);
                $player->getInventory()->setItemInHand($ih);
            }


            foreach ($this->data["location"] as $spawn) {
                $lv = Vector3::fromString($spawn);
                if ($block->getPosition()->getPosition()->asVector3()->distance($lv) < 8) {
                    $event->cancel();
                    $player->sendMessage("§cyou can't placed block in region base");
                } else {
                    if ($block->getId() == BlockLegacyIds::CHEST) {
                        if (!$event->isCancelled()) {
                            $this->spawnTower($player, $block);

                            $event->cancel(true);
                        }
                    } else {
                        $this->addPlacedBlock($block);
                    }
                }


            }
        }
    }

    public function spawnTower(Player $player, Block $block)
    {
        $rotation = ($player->getLocation()->getYaw() - 90.0) % 360.0;
        if ($rotation < 0.0) {
            $rotation += 360.0;
        }
        $ih = $player->getInventory()->getItemInHand();
        $ih->setCount($ih->getCount() - 1);
        $player->getInventory()->setItemInHand($ih);
        if (315.0 <= $rotation && $rotation < 360.0) {
            $a = $this->towereast;
            $a->Tower($block, $player, $this->getTeam($player));

        }
        if (135.0 <= $rotation && $rotation < 225.0) {
            $a = $this->towerwest;
            $a->Tower($block, $player, $this->getTeam($player));
        }
        if (0.0 <= $rotation && $rotation < 45.0) {
            $a = $this->towereast;
            $a->Tower($block, $player, $this->getTeam($player));

        }
        if (45.0 <= $rotation && $rotation < 135.0) {
            $a = $this->towersouth;
            $a->Tower($block, $player, $this->getTeam($player));

        }
        if (225.0 <= $rotation && $rotation < 315.0) {
            $a = $this->towernorth;
            $a->Tower($block, $player, $this->getTeam($player));
        }
    }


    public function spawnGolem($pos, $level, $player)
    {
        if ($this->phase !== self::PHASE_GAME) return;
        $nbt = Entity::createBaseNBT($pos);
        $entity = new Golem($level, $nbt);
        $entity->arena = $this;
        $entity->owner = $player;
        $entity->spawnToAll();
    }

    public function spawnBedbug($pos, $level, $player)
    {
        if ($this->phase !== self::PHASE_GAME) return;
        $nbt = Entity::createBaseNBT($pos);
        $entity = new Bedbug($level, $nbt);
        $entity->arena = $this;
        $entity->owner = $player;
        $entity->spawnToAll();
    }


    public function spawnFireball($pos, $level, $player)
    {
        $nbt = Entity::createBaseNBT($pos, $player->getDirectionVector(), ($player->yaw > 180 ? 360 : 0) - $player->yaw, -$player->pitch);
        $entity = new Fireball($level, $nbt, $player);
        $entity->setMotion($player->getDirectionVector()->normalize()->multiply(0.4));
        $entity->spawnToAll();
        $entity->arena = $this;
        $entity->owner = $player;
    }


    public function onItemDrop(PlayerDropItemEvent $event)
    {
        $player = $event->getPlayer();
        $item = $event->getItem();
        if ($this->inGame($player) && $this->phase == self::PHASE_LOBBY) {
            $event->cancel();
        }
        if ($this->inGame($player) && $this->phase == self::PHASE_RESTART) {
            $event->cancel();
        }
        if (isset($this->spectators[$player->getName()])) {
            $event->cancel();
        }
        if ($this->phase == self::PHASE_GAME) {
            if ($item instanceof Sword || $item instanceof Armor || $item->getId() == ItemIds::SHEARS || $item instanceof Pickaxe || $item instanceof Axe) {
                $event->cancel();
            }
        }

    }

    public function playAgain(Player $player): bool
    {
        if (!class_exists(CheckPartyQuery::class)) {
            BedWars::getInstance()->joinToRandomArena($player);
            return false;
        }
        QueryQueue::submitQuery(new CheckPartyQuery($player->getName()), function (CheckPartyQuery $query) use ($player) {
            if (!$query->type) {
                BedWars::getInstance()->joinToRandomArena($player);
                return false;
            }
            QueryQueue::submitQuery(new FetchAllParty($query->output), function (FetchAllParty $ingfo) use ($player, $query) {
                QueryQueue::submitQuery(new MemberPartyQuery($query->output), function (MemberPartyQuery $query) use ($player, $ingfo) {
                    if ($ingfo->leader !== $player->getName()) {
                        $player->sendMessage("§cYou must leader party or leave party to play again!");
                        return false;
                    }
                    $members = array_values(array_filter($query->member));
                    foreach ($members as $member) {
                        $p = $this->plugin->getServer()->getPlayer($member);
                        if (!$p->isOnline() && $p == null) {
                            return false;
                        }
                        BedWars::getInstance()->joinToRandomArena($p);

                    }

                });
            });
            return true;
        });
    }




    
    public function onInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $item = $event->getItem();
        $itemN = $item->getCustomName();
        $action = $event->getAction();
        if(isset($this->spectators[$player->getName()])){
			if($block instanceof Block){
				$event->cancel();
			}
		}
		if($action == $event::RIGHT_CLICK_BLOCK){
				if ($itemN == "§aPlay Again") {
					$this->playAgain($player);
					$player->getInventory()->setHeldItemIndex(1);
				}
					
				if ($itemN == "§cBack To Lobby") {
					$player->getServer()->dispatchCommand($player,"lobby");
				}
				if ($itemN == "§aReturn to lobby") {
					$player->getServer()->dispatchCommand($player,"lobby");
				}
				if ($itemN == "§eSpectator") {
					$this->playerlist($player);
				}
			}

            
        if($this->inGame($player) && $this->phase == self::PHASE_GAME) {	
                $ih = $player->getInventory()->getItemInHand();	
                if($action == $event::RIGHT_CLICK_BLOCK){
                    if($block instanceof Bed){
                        if(!$player->isSneaking()){
                        $event->cancel();
                    }
                }
                if($item->getId() == ItemIds::SPAWN_EGG && $item->getDamage() == 14){
                    $this->spawnGolem($block->add(0, 1), $player->world, $player);
                    $ih->setCount($ih->getCount() - 1);
                    $player->getInventory()->setItemInHand($ih); 
                    $event->cancel();
                }
        }

		if($action == $event::RIGHT_CLICK_BLOCK){

            if($item->getId() == ItemIds::FIRE_CHARGE){
                $this->spawnFireball($player->add(0, $player->getEyeHeight()), $player->level, $player);
                $this->addSound($player, 'mob.blaze.shoot');
                $ih->setCount($ih->getCount() - 1);
                $player->getInventory()->setItemInHand($ih); 
                $event->cancel();
            }

            if($block->getId() == BlockLegacyIds::LIT_FURNACE || $block->getId() == BlockLegacyIds::CRAFTING_TABLE || $block->getId() == BlockLegacyIds::BREWING_STAND_BLOCK || $block->getId() == BlockLegacyIds::FURNACE){
                 $event->cancel();
            }

        }
                  
        }
    
    }

    public function InventoryPickArrow(EntityItemPickupEvent $event){
        $inv = $event->getInventory();
        if($inv instanceof  PlayerInventory) {
			$player = $inv->getHolder();
			if ($event->isCancelled()) return;
			if (isset($this->spectators[$player->getName()])) {
				$event->cancel();
			}
			if ($player instanceof Player && $player->getWorld()->getFolderName() == $this->level->getFolderName()) {
				if ($this->phase == self::PHASE_RESTART) {
					$event->cancel();
				}
			}
		}
    }


	public function addKill(Player $damager,$type){
		if($type == "fk"){
			$this->addRocket($damager);
			$this->addRocket($damager);
			$this->addRocket($damager);
			$this->addSound($damager, 'random.levelup');
			$this->plugin->mysqldata->addscore($damager,"fk");
		
			$this->plugin->addFinalKill($damager);
			if(isset($this->finalkill[$damager->getId()])){
			$this->finalkill[$damager->getId()]++;
			}
		}
		if($type == "kill"){
            $this->addRocket($damager);
            $this->addRocket($damager);
            $this->addRocket($damager);
            $this->addSound($damager, 'random.levelup');
            if(isset($this->kill[$damager->getId()])){
            $this->kill[$damager->getId()]++;
            }
            $this->plugin->mysqldata->addscore($damager,"kill");
		}
	}

  
    
    public function onDamage(EntityDamageEvent $event) {
        $player = $event->getEntity();
        $entity = $event->getEntity();
        $isFinal = "";

		if($entity instanceof Generator){
			$event->cancel();
		}
        if($entity instanceof Player){
            if($this->inGame($entity)){
                if($this->phase == self::PHASE_GAME){
                    if($event instanceof EntityDamageByEntityEvent){
						if($event->getDamager() instanceof EnderDragon){
							$entity->getPosition()->asVector3()->multiply(2);
						}
						if($event->getDamager() instanceof Player){

							if($this->inGame($event->getDamager())){
								if($this->getTeam($event->getDamager()) == $this->getTeam($entity)){
									$event->cancel();
								}
	
							}
						}
                 
                   }
                 }
            }
        }
        if(!$entity instanceof Player){
            return;
        }
		if($this->inGame($entity) && $this->phase === 2) {
			$event->cancel(true);
		}
		if($this->inGame($entity) && $this->phase === 0) {
			$event->cancel(true);
		}
		if(!BedWars::getInstance()->isInGame($entity)){
			$event->cancel();
		}


		if(!$this->inGame($entity)) {

			return;
		}

		if($this->phase !== 1) {
			return;
		}
		
		if(!$entity instanceof Player) return;

		if(BedWars::getInstance()->getArenaByPlayer($entity)->level->getFolderName() !== $entity->getWorld()->getFolderName()){
			$event->cancel();
		}
		if(isset($this->respawnC[$entity->getName()])){
			$event->cancel();
		}
		if(isset($this->spectators[$entity->getName()])){
			$event->cancel();
		}
        if($entity->getHealth()-$event->getFinalDamage() <= 0) {
            $event->cancel(true);

           if($event instanceof  EntityDamageByEntityEvent){
            	$damager = $event->getDamager();
            	if(!$damager instanceof Player){
					return;
				}
				if(!$entity instanceof Player){
					return;
				}

                if($this->getTeam($damager) !== $this->getTeam($entity)){
                	if(!$this->bedState($entity)){
					$this->addKill($damager,"fk");
					$this->dropItem($entity);
					$isFinal = "§l§bFINAL KILLS";

				} else {
					$this->addKill($damager,"kill");
				}
                }
	

            }

			if($this->bedState($entity)){
				$this->startRespawn($entity);
                $entity->teleport($this->level->getSafeSpawn());
			} else {
				$this->Spectator($entity);
			}
            switch ($event->getCause()) {
                case $event::CAUSE_CONTACT:
                case $event::CAUSE_ENTITY_ATTACK:
                    if($event instanceof EntityDamageByEntityEvent) {
                        $damager = $event->getDamager();
                        if($damager instanceof Player) {
                        	if($player instanceof Player) {
								$msg = "§r{$entity->getDisplayName()} §e was killed by §r{$damager->getDisplayName()} {$isFinal}";
								$this->broadcastMessage($msg);

								break;
							}
                        }
                    }
                   break;
                case $event::CAUSE_PROJECTILE:
                    if($event instanceof EntityDamageByEntityEvent) {
                        $damager = $event->getDamager();
                        if($damager instanceof Player) {
                        	if($player instanceof Player) {
                        	    $msg = "{$entity->getDisplayName()} §e was killed By {$damager->getDisplayName()} §ewith projectile {$isFinal}";
								$this->broadcastMessage($msg);
							}
                            break;
                        }
                    }
                    if($player instanceof Player) {
                        $msg = "{$entity->getDisplayName()} §e death with projectile {$isFinal}";
						$this->broadcastMessage($msg);
					}

                   break;
                case $event::CAUSE_BLOCK_EXPLOSION:
					if($event instanceof EntityDamageByEntityEvent) {
						$damager = $event->getDamager();
						if($damager instanceof Player) {
							if($player instanceof Player) {
							    $msg = "{$entity->getDisplayName()} §e death with explosion by {$damager->getDisplayName()} {$isFinal}";
								$this->broadcastMessage($msg);
							}

							break;
						}
					}
                	if($player instanceof Player) {
                	    $msg = "{$entity->getDisplayName()} §e death by explosion {$isFinal}";
						$this->broadcastMessage($msg);
					}
                    
                    break;
                case $event::CAUSE_FALL:
					if($event instanceof EntityDamageByEntityEvent) {
						$damager = $event->getDamager();
						if($damager instanceof Player) {
							if($player instanceof Player) {
							    $msg = "{$entity->getDisplayName()} §e fell from high place by {$damager->getDisplayName()} {$isFinal}";
								$this->broadcastMessage($msg);
							}

							break;
						}
					}
                	if($player instanceof Player) {
                	    $msg = "{$entity->getDisplayName()} §e fell from high place {$isFinal}";
						$this->broadcastMessage($msg);
					}
                   
                    break;
                case $event::CAUSE_VOID:
                    if($event instanceof EntityDamageByEntityEvent) {
                        $damager = $event->getDamager();
                        if($damager instanceof Player && $this->inGame($damager)) {
                            $msg = "{$entity->getDisplayName()} §ewas thrown into void by §f{$damager->getDisplayName()} {$isFinal}";
                            $this->broadcastMessage($msg);
                            break;
                        }
                    }
                    $msg = null;
                    if($player instanceof  Player) {
                        $msg = "§r{$entity->getDisplayName()} §e fell into void {$isFinal}";
						$this->broadcastMessage($msg);
					}
                 
                    break;
               case $event::CAUSE_ENTITY_EXPLOSION:
                   if($event instanceof EntityDamageByEntityEvent) {
                       $damager = $event->getDamager();
                       if($damager instanceof Player) {
                           if($player instanceof Player) {
                               $msg = " §b".$entity->getDisplayName(). " §ewas exploded by  §r {$damager->getDisplayName()} {$isFinal}";
                               $this->broadcastMessage($msg);
                           }

                           break;
                       }
                   }
                   if($player instanceof Player) {
                       $msg = " §b" . $entity->getDisplayName() . " §edeath by explosion {$isFinal}";
					   $this->broadcastMessage($msg);
				
				   }
               break;
                default:
                	if($player instanceof Player) {
						$this->broadcastMessage("§r{$entity->getDisplayName()} §edeath {$isFinal}");
					
					}
                     
            }


			
        }
  
    }

    public function onEntityMotion(EntityMotionEvent $event)
	{
		$entity = $event->getEntity();

		if ($entity instanceof Player){
			if (isset($this->spectators[$entity->getName()])) {
				$event->cancel();
			}
	    }

        if($entity instanceof ShopVillager || $entity instanceof UpgradeVillager || $entity instanceof Generator){
        	$event->cancel(true);

        }
    
                   
    }
    
    public function playerlist($player) : bool{
		$api = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI");
		$form = $api->createSimpleForm(function (Player $player, $data = null){
			$target = $data;
			if($target === null){
				return true;
			}
			foreach($this->world->getPlayers() as $pl){
				if($player->getPosition()->getWorld()->getFolderName() == $this->world->getFolderName()){
						if($pl->getDisplayName() == $target){
						if($this->inGame($pl)){
						$player->teleport($pl->getPosition()->getPosition()->asVector3());
						$player->sendMessage("§eYou spectator {$pl->getName()}");
						}
					}
				}
			}
			return true;

		});
		$form->setTitle("Spectator Player");
		if(empty($this->players)){
			$form->setContent("§cno players!");
		   $form->addButton("CLOSE", 0, "textures/blocks/barrier");
		   return true;
	   }
	   $count = 0;
	   foreach($this->players as $pl){
	   $count++;
	   $form->addButton($pl->getDisplayName(),-1,"",$pl->getDisplayName());
	   }
	   if($count == count($this->players)){
		   $form->addButton("Close", 0, "textures/blocks/barrier");
	   }
	   $form->sendToPlayer($player);
	   return true;

	}


    public function addGlobalSound($player, string $sound = '', float $pitch = 1){
        $pk = new PlaySoundPacket();
		$pk->x = $player->getPosition()->getX();
		$pk->y = $player->getPosition()->getY();
		$pk->z = $player->getPosition()->getZ();
		$pk->volume = 2;
		$pk->pitch = $pitch;
		$pk->soundName = $sound;
	    Server::getInstance()->broadcastPackets($player->getWorld()->getPlayers(), $pk);
    }  
    
    public function addSound($player, string $sound = '', float $pitch = 1){
        $pk = new PlaySoundPacket();
		$pk->x = $player->getPosition()->getX();
		$pk->y = $player->getPosition()->getY();
		$pk->z = $player->getPosition()->getZ();
		$pk->volume = 4;
		$pk->pitch = $pitch;
		$pk->soundName = $sound;
        $player->getNetworkSession()->getNetworkSession()->sendDataPacket($pk);
    }
    
    public function stopSound($player, string $sound = '', bool $all = true){
        $pk = new StopSoundPacket();
		$pk->soundName = $sound;
		$pk->stopAll = $all;
	    Server::getInstance()->broadcastPackets($player->getWorld()->getPlayers(), $pk);
    }


    /**
     * @param PlayerQuitEvent $event
     */
    public function onQuit(PlayerQuitEvent $event) {
        $player = $event->getPlayer();
        $event->setQuitMessage("");
		if($this->inGame($player)) {
            $this->disconnectPlayer($player);
        }

    }
    
    public function upgradeGenerator($team, $player){
        $pos = Vector3::fromString($this->data["bed"][$team]);
		$this->utilities[$this->level->getFolderName()][$team]["generator"]++;
        foreach($this->level->getEntities() as $g){
            if($g instanceof Generator){
                if($g->getPosition()->getPosition()->asVector3()->distance($pos) < 20){
                    $g->Glevel = $g->Glevel + 1;
                }
            }
        }
        foreach($this->players as $t){
            if($this->getTeam($t) == $team){
                $lvl = 	$this->utilities[$this->world->getFolderName()][$team]["generator"] - 1;
                $t->sendMessage("{$player->getDisplayName()} §ehas bought §aForge §eLevel §a" . $lvl);
                
            }
        }
    }
    
    public function upgradeArmor($team, $player){
        $this->utilities[$this->world->getFolderName()][$team]["protection"]++;
        foreach($this->players as $pt){
            if($this->getTeam($pt) == $team){
                
                $lvl = $this->utilities[$this->world->getFolderName()][$team]["protection"] - 1;
                $this->addSound($pt, 'random.levelup');
                $this->setArmor($pt);
		        $pt->sendMessage("{$player->getDisplayName()} §eHas Bought §aResistance §eLevel §a" . $lvl);
            }
        }
    }
    
    public function upgradeHaste($team, $player){
        $this->utilities[$this->world->getFolderName()][$team]["haste"]++;
		foreach($this->players as $pt){
		    if($this->getTeam($pt) == $team){
		        $lvl = $this->utilities[$this->world->getFolderName()][$team]["haste"] - 1;
		        $this->addSound($pt, 'random.levelup');
		        $pt->sendMessage("{$player->getDisplayName()} §eHas Bought §aManiac Miner §eLevel §a" . $lvl);
		    }
		}
    }
    
    public function upgradeSword($team, $player){
        $this->utilities[$this->world->getFolderName()][$team]["sharpness"]++;
		foreach($this->players as $pt){
		    if($this->getTeam($pt) == $team){
		        $this->addSound($pt, 'random.levelup');
		        $lvl = $this->utilities[$this->world->getFolderName()][$team]["sharpness"] - 1;
		        $this->setSword($pt, $pt->getInventory()->getItem(0));
		        $pt->sendMessage("{$player->getDisplayName()} §eHas Bought §aSharpNess §eLevel §a ". $lvl);
		    }
		}
    }
    
    public function upgradeHeal($team, $player){
        $this->utilities[$this->world->getFolderName()][$team]["health"]++;
		foreach($this->players as $pt){
		    if($this->getTeam($pt) == $team){
		        $this->addSound($pt, 'random.levelup');
		        $pt->sendMessage("{$player->getDisplayName()} §eHas Bought §aHeal Pool");
		    }
		}
    } 
	
	public function upgradeMenu(Player $player){
	    $team = $this->getTeam($player); 
	    $trapprice = $this->utilities[$this->world->getFolderName()][$team]["traps"];
	    $slevel = $this->utilities[$this->world->getFolderName()][$team]["sharpness"];
	    $Slevel = str_replace(["0"], ["-"], "" . ($slevel - 1) . "");
	    $plevel = $this->utilities[$this->world->getFolderName()][$team]["protection"];
	    $Plevel = str_replace(["0"], ["-"], "" . ($plevel - 1) . ""); 
	    $hlevel = $this->utilities[$this->world->getFolderName()][$team]["haste"];
	    $Hlevel = str_replace(["0"], ["-"], "" . ($hlevel - 1) . ""); 
	    $glevel = $this->utilities[$this->world->getFolderName()][$team]["generator"];
	    $Glevel = str_replace(["0"], ["-"], "" . ($glevel - 1) . "");
	    $htlevel = $this->utilities[$this->world->getFolderName()][$team]["health"];
	    $HTlevel = str_replace(["0"], ["-"], "" . ($htlevel - 1) . "");  
	    $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST); 
	    $menu->setName("Team Upgrade");

	    $inv = $menu->getInventory();
	    $menu->setListener(InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction) : void{ 
	    $player = $transaction->getPlayer();
	    $pinv = $player->getInventory();
	    $item = $transaction->getItemClicked();
        $team = $this->getTeam($player);
        $pt = $player;
	    $slevel = $this->utilities[$this->world->getFolderName()][$team]["sharpness"];
	    $Slevel = str_replace(["0"], ["-"], "" . ($slevel - 1) . "");
	    $plevel = $this->utilities[$this->world->getFolderName()][$team]["protection"];
	    $Plevel = str_replace(["0"], ["-"], "" . ($plevel - 1) . ""); 
	    $hlevel = $this->utilities[$this->world->getFolderName()][$team]["haste"];
	    $Hlevel = str_replace(["0"], ["-"], "" . ($hlevel - 1) . ""); 
	    $glevel = $this->utilities[$this->world->getFolderName()][$team]["generator"];
	    $Glevel = str_replace(["0"], ["-"], "" . ($glevel - 1) . "");
	    $htlevel = $this->utilities[$this->world->getFolderName()][$team]["health"];
	    $HTlevel = str_replace(["0"], ["-"], "" . ($htlevel - 1) . "");  
        if($item instanceof Sword && $item->getId() == ItemIds::IRON_SWORD){
            if(isset($this->utilities[$this->world->getFolderName()][$team]["sharpness"])){
                $g =  $this->utilities[$this->world->getFolderName()][$team]["sharpness"];
		        $cost = 1;
		        if($g == 1){
		            $cost = 2;
                }
		        if($g == 2){
		            $cost = 4;

                }
		        if($g == 3){
		            $cost = 8;
                }
		        if($g == 4){
		            $cost = 16;
                }
                if($g <= 2) {
                    if ($pinv->contains(ItemFactory::getInstance()->get(ItemIds::DIAMOND, 0, $cost))) {
                        $pinv->removeItem(ItemFactory::getInstance()->get(ItemIds::DIAMOND, 0, $cost));
                        $this->upgradeSword($team, $player);
                        $this->addSound($pt, 'random.levelup');
                    } else {
                        $player->getWorld()->addSound(new EndermanTeleportSound($player), [$player]);
                    }
                }
                
            }
        }
        if($item instanceof Armor && $item->getId() == ItemIds::IRON_CHESTPLATE){
			if(isset($this->utilities[$this->world->getFolderName()][$team]["protection"])){
                $g =  $this->utilities[$this->world->getFolderName()][$team]["protection"];
		        $cost = 5;
		        if($g === 1){
		            $cost = 5;
                }
		        if($g === 2){
		            $cost = 10;
                }
		        if($g === 3){
		            $cost = 15;
                }
		        if($g === 4){
		            $cost = 20;
                }
             
                if($g <= 4){
                    if ($pinv->contains(ItemFactory::getInstance()->get(ItemIds::DIAMOND, 0, $cost))) {
                        $pinv->removeItem(ItemFactory::getInstance()->get(ItemIds::DIAMOND, 0, $cost));
                        $this->addSound($pt, 'random.levelup');
                        $this->upgradeArmor($team, $player);
                    } else {
                        $player->getWorld()->addSound(new EndermanTeleportSound($player), [$player]);
                    }
                }
                
                
            }
        }
        if($item->getId() == ItemIds::IRON_PICKAXE){
            if(isset($this->utilities[$this->world->getFolderName()][$team]["haste"])){
				$g =  $this->utilities[$this->world->getFolderName()][$team]["haste"];
		        $cost = 4 * $g;
		        if($g == 3){
		            return;
		        }
		        if($pinv->contains(ItemFactory::getInstance()->get(ItemIds::DIAMOND, 0, $cost))){
		            $pinv->removeItem(ItemFactory::getInstance()->get(ItemIds::DIAMOND, 0, $cost));
		            
    
                 $this->addSound($pt, 'random.levelup');
		            $this->upgradeHaste($team, $player);
		        } else {
		          $player->getWorld()->addSound(new EndermanTeleportSound($player), [$player]); 
		        }
            }
        }
        if($item->getId() == BlockLegacyIds::FURNACE){
			if(isset($this->utilities[$this->world->getFolderName()][$team]["generator"])){
                $g =  $this->utilities[$this->world->getFolderName()][$team]["generator"];
		        $cost = 4 * $g;
		        if($g == 5){
		            return;
		        }
		        if($pinv->contains(ItemFactory::getInstance()->get(ItemIds::DIAMOND, 0, $cost))){
		            $pinv->removeItem(ItemFactory::getInstance()->get(ItemIds::DIAMOND, 0, $cost));
		             $this->addSound($pt, 'random.levelup');
		            $this->upgradeGenerator($team, $player);
		        } else {
		         $player->getWorld()->addSound(new EndermanTeleportSound($player), [$player]); 
		        }
            }
        }
        if($item->getId() == BlockLegacyIds::BEACON){
            if(isset($this->utilities[$this->world->getFolderName()][$team]["health"])){
                $g =  $this->utilities[$this->world->getFolderName()][$team]["health"];
		        $cost = 2 * $g;
		        if($g == 2){
		            return;
		        }
		        if($pinv->contains(ItemFactory::getInstance()->get(ItemIds::DIAMOND, 0, $cost))){
		            $pinv->removeItem(ItemFactory::getInstance()->get(ItemIds::DIAMOND, 0, $cost));
		           $this->addSound($pt, 'random.levelup');
		            $this->upgradeHeal($team, $player);
		        } else {
		         $player->getWorld()->addSound(new EndermanTeleportSound($player), [$player]);
		        }
            } 
        }
        $trapprice =  $this->utilities[$this->world->getFolderName()][$team]["traps"];
        if($item->getId() == BlockLegacyIds::TRIPWIRE_HOOK){
            if(isset($this->itstrap[$team])){
                return; 
            }
            if($pinv->contains(ItemFactory::getInstance()->get(ItemIds::DIAMOND, 0, $trapprice))){
                $pinv->removeItem(ItemFactory::getInstance()->get(ItemIds::DIAMOND, 0, $trapprice));
		          $this->addSound($pt, 'random.levelup');
		        $this->itstrap[$team] = $team;
		        foreach($this->players as $pt){
		            if($this->getTeam($pt) == $team){
		                $pt->sendMessage("{$player->getDisplayName()} §ehas bought It's Trap");
		            }
		        }
				$this->utilities[$this->world->getFolderName()][$team]["traps"]++;
            } else {
		     $player->getWorld()->addSound(new EndermanTeleportSound($player), [$player]);
            }
        }
        if($item->getId() == ItemIds::FEATHER){
            if(isset($this->countertrap[$team])){
                return; 
            }
            if($pinv->contains(ItemFactory::getInstance()->get(ItemIds::DIAMOND, 0, $trapprice))){
                $pinv->removeItem(ItemFactory::getInstance()->get(ItemIds::DIAMOND, 0, $trapprice));
		        $this->addSound($pt, 'random.levelup');
		        $this->countertrap[$team] = $team;
		        foreach($this->players as $pt){
		            if($this->getTeam($pt) == $team){
		                $pt->sendMessage("{$player->getDisplayName()} §ehas bought Counter Offensive Trap");
		            }
		        } 
				$this->utilities[$this->world->getFolderName()][$team]["traps"]++;
            } else {
		        $player->getWorld()->addSound(new EndermanTeleportSound($player), [$player]);
            }
        }
        if($item->getId() == BlockLegacyIds::LIT_REDSTONE_TORCH){
            if(isset($this->alarmtrap[$team])){
                return; 
            }
            if($pinv->contains(ItemFactory::getInstance()->get(ItemIds::DIAMOND, 0, $trapprice))){
                $pinv->removeItem(ItemFactory::getInstance()->get(ItemIds::DIAMOND, 0, $trapprice));
		           $this->addSound($pt, 'random.levelup');
		        $this->alarmtrap[$team] = $team;
		        foreach($this->players as $pt){
		            if($this->getTeam($pt) == $team){
		                $pt->sendMessage("{$player->getDisplayName()} §ehas bought Alarm Trap");
		            }
		        } 
				$this->utilities[$this->world->getFolderName()][$team]["traps"]++;
            } else {
		       $player->getWorld()->addSound(new EndermanTeleportSound($player), [$player]);
            }
        }
        if($item->getId() == ItemIds::WOODEN_PICKAXE){
            if(isset($this->minertrap[$team])){
                return; 
            }
            if($pinv->contains(ItemFactory::getInstance()->get(ItemIds::DIAMOND, 0, $trapprice))){
                $pinv->removeItem(ItemFactory::getInstance()->get(ItemIds::DIAMOND, 0, $trapprice));
		        $this->addSound($pt, 'random.levelup');
		        $this->minertrap[$team] = $team;
		        foreach($this->players as $pt){
		            if($this->getTeam($pt) == $team){
		                $pt->sendMessage("{$player->getDisplayName()} §ehas bought Miner Fatigue Trap");
		            }
		        } 
				$this->utilities[$this->world->getFolderName()][$team]["traps"]++;
            } else {
		        $player->getWorld()->addSound(new EndermanTeleportSound($player), [$player]);
            }
        }  
	   
	    })); 
		$sharp = null;
	    if($slevel > 4){
	        $sharp = "§eSharpness §c(max)";
	    } else {
	        $sharp = "§eSharpness";
	    }
	    $inv->setItem(11, ItemFactory::getInstance()->get(ItemIds::IRON_SWORD)
	    ->setCustomName("$sharp")
	    ->setLore([

	        "§bTier 1 - 2 Diamond\n",
	        "§bTier 2 - 4 Diamond\n",
	        "§fAll player in your team will get enchanted Sharpness $Slevel sword"
	        ])
	    );
	    $prot = null;
	    if($plevel > 3){
	        $prot = "§eResistance §c(max)";
	    } else {
	        $prot = "§eResistance";
	    }
	    $inv->setItem(12, ItemFactory::getInstance()->get(ItemIds::IRON_CHESTPLATE)
	    ->setCustomName("$prot")
	    ->setLore([
	    	"§aReinforced Armor",
	        "§eLevel 1 - 5 Diamond",
	        "§eLevel 2 - 10 Diamond",
	        "§eLevel 3 - 15 Diamond",
	        "§eLevel 4 - 20 Diamond\n",
	        "§eCurrent Tier: §a{$Plevel}\n",
	        "§fAll player in your team get enchanted protection Armor"
	        ])
	    );
	    $haste = null;
	    if($hlevel > 1){
	        $haste = "§eManiac Miner §c(max)";
	    } else {
	        $haste = "§eManiac Miner";
	    }
	    $inv->setItem(13, ItemFactory::getInstance()->get(ItemIds::IRON_PICKAXE)
	    ->setCustomName("$haste")
	    ->setLore([

	        "§eTier 1 - 2 Diamond (Haste 1)",
	        "§eCurrent Tier: §c{$Hlevel}\n",
	        "§fAll player in your team get maniac miner"
	        ])
	    );
	    $gen = null;
	    if($glevel > 4){
	        $gen = "§eForge §c(max)";
	    } else {
	        $gen = "§eForge";
	    }
	    $inv->setItem(14, ItemFactory::getInstance()->get(BlockLegacyIds::FURNACE)
	    ->setCustomName("$gen")
	    ->setLore([
	        "§aCurrent Forge: §c{$Glevel}\n",
	        "§eIron Forge - 2 Diamond 50% IronIngot",
	        "§eGold Forge Forge -  4 Diamond 50% Gold",
	        "§eEmerald Forge - 6 Diamond (spawn emerald in your team generator)",
	        "§eDouble Forge - 16 Diamond (increase iron & gold generator spawn 100%)\n",
	        "§eIncrease Generator In Your Team"
	        ])
	    );
	    $health = null;
	    if($htlevel > 4){
	        $health = "§cHeal Pool (max)";
	    } else {
	        $health = "§eHeal Pool";
	    }
	    $inv->setItem(15, ItemFactory::getInstance()->get(BlockLegacyIds::BEACON)
	    ->setCustomName("$health")
	    ->setLore([
	        "§fCurrent Level: §c{$HTlevel}\n",
	        "§e2 Diamond\n",
	        "§fyour team infinite regen nearby your base"
	        ])
	    );
	    $itstrap = null;
	    $itsprice = null;
	    if(isset($this->itstrap[$team])){
	        $itsprice = "";
	        $itstrap = "§aActived";
	    } else {
	        $itsprice = "§e{$trapprice} Diamond\n";
	        $itstrap = "§cDisabled";
	    }
	    $inv->setItem(29, ItemFactory::getInstance()->get(BlockLegacyIds::TRIPWIRE_HOOK)
	    ->setCustomName("§eIt's Trap")
	    ->setLore([
	        "§eStatus: {$itstrap}\n",
	        "{$itsprice}",
	        "§fGive enemy slowness and blindness effect 8 seconds"
	        ])
	    );
	    $countertrap = null;
	    $counterprice = null;
	    if(isset($this->countertrap[$team])){
	        $countertrap = "§aActived";
	        $counterprice = "";
	    } else {
	        $countertrap = "§cDisabled";
	        $counterprice = "§e{$trapprice} Diamond\n";
	    }
	    $inv->setItem(30, ItemFactory::getInstance()->get(ItemIds::FEATHER)
	    ->setCustomName("§eCounter Offensive Trap")
	    ->setLore([
	        "§eStatus: {$countertrap}\n",
	        "{$counterprice}",
	        "§fGive team jump boost II and speed effect 15 seconds"
	        ])
	    );
	    $alarmtrap = null;
	    $alarmprice = null;
	    if(isset($this->alarmtrap[$team])){
	        $alarmtrap = "§aActived";
	        $alarmprice = "";
	    } else {
	        $alarmtrap = "§cDisabled";
	        $alarmprice = "§e{$trapprice} Diamond\n"; 
	    }
	    $inv->setItem(31, ItemFactory::getInstance()->get(BlockLegacyIds::LIT_REDSTONE_TORCH)
	    ->setCustomName("§eAlarm Trap")
	    ->setLore([
	        "§eStatus: {$alarmtrap}\n",
	        "{$alarmprice}",
	        "§fReveal invisible"
	        ])
	    );
	    $minertrap = null;
	    $minerprice = null;
	    if(isset($this->minertrap[$team])){
	        $minertrap = "§aActived";
	        $minerprice = "";
	    } else {
	        $minertrap = "§cDisabled";
	        $minerprice = "§e{$trapprice} Diamond\n"; 
	    }
	    $inv->setItem(32, ItemFactory::getInstance()->get(ItemIds::WOODEN_PICKAXE)
	    ->setCustomName("§eMiner Fatigue Trap")
	    ->setLore([
	        "§eStatus: {$minertrap}\n",
	        "{$minerprice}",
	        "§fGive enemy mining fatigue effect 8 seconds"
	        ])
	    );

	    $menu->send($player);
	}

	public function shopMenu(Player $player){
	    $team = $this->getTeam($player);
	    $meta = [
	        "red" => 14,
	        "blue" => 11,
	        "yellow" => 4,
	        "green" => 5
	    ];
	    $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST); 
	    $menu->setName("Item Shop");
	    $inv = $menu->getInventory();
	    $menu->setListener(InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction) : void{  
	    $player = $transaction->getPlayer();
	    $pinv = $player->getInventory();
	    $item = $transaction->getItemClicked();
	    $inv = $transaction->getAction()->getInventory();
        $in = $item->getCustomName();
        if(in_array($in, ["§fBlocks", "§fMelee", "§fArmor", "§fTools", "§fBow & Arrow", "§fPotions", "§fUtility"])){
            $this->manageShop($player, $inv, $in);
            return;
        }
        if($item instanceof Sword && $in == "§eStone Sword"){
			if(!$pinv->contains(ItemFactory::getInstance()->get(ItemIds::STONE_SWORD))){
            if($pinv->contains(ItemFactory::getInstance()->get(ItemIds::IRON_INGOT, 0, 10))){
                $pinv->removeItem(ItemFactory::getInstance()->get(ItemIds::IRON_INGOT, 0, 10));
                $this->messagebuy($player,"Stone Sword");
                $this->setSword($player, ItemFactory::getInstance()->get(ItemIds::STONE_SWORD));
            } else {
				$this->notEnought($player,"Iron ingot");
                $player->getWorld()->addSound(new EndermanTeleportSound($player), [$player]);
            }
         
		    } else {
                $player->getWorld()->addSound(new EndermanTeleportSound($player), [$player]);
                $player->sendMessage("§cYou already bought this!");
            }
			return;
        }
        if($item instanceof Sword && $in == "§eIron Sword"){
			if(!$pinv->contains(ItemFactory::getInstance()->get(ItemIds::IRON_SWORD))){
            if($pinv->contains(ItemFactory::getInstance()->get(ItemIds::GOLD_INGOT, 0, 7))){
			
                $pinv->removeItem(ItemFactory::getInstance()->get(ItemIds::GOLD_INGOT, 0, 7));
                $this->messagebuy($player,"Iron Sword");
                $this->setSword($player,  ItemFactory::getInstance()->get(ItemIds::IRON_SWORD));
            } else {
				$this->notEnought($player,"Gold ingot");
               $player->getWorld()->addSound(new EndermanTeleportSound($player), [$player]);
            }
      
		   } else {
                $player->getWorld()->addSound(new EndermanTeleportSound($player), [$player]);
                $player->sendMessage("§cYou already bought this!");
            }
		   return; 
        }
        if($item instanceof Sword && $in == "§eDiamond Sword"){
			if(!$pinv->contains(ItemFactory::getInstance()->get(ItemIds::DIAMOND_SWORD))){
            if($pinv->contains(ItemFactory::getInstance()->get(ItemIds::EMERALD, 0, 3))){
                $pinv->removeItem(ItemFactory::getInstance()->get(ItemIds::EMERALD, 0, 3));
                $this->messagebuy($player,"Diamond Sword");
                $this->setSword($player,ItemFactory::getInstance()->get(ItemIds::DIAMOND_SWORD));
            } else {
				$this->notEnought($player,"Emerald");
               $player->getWorld()->addSound(new EndermanTeleportSound($player), [$player]);
            }

			} else {
                $player->getWorld()->addSound(new EndermanTeleportSound($player), [$player]);
                $player->sendMessage("§cYou already bought this!");
            }
		 return;
        }
        if($in == "§eShears"){
            if(isset($this->shear[$player->getName()])){
                return;
            }
            if($pinv->contains(ItemFactory::getInstance()->get(ItemIds::IRON_INGOT, 0, 20))){
                $pinv->removeItem(ItemFactory::getInstance()->get(ItemIds::IRON_INGOT, 0, 20));

                $this->shear[$player->getName()] = $player;
                $this->messagebuy($player,"Shears");
                $sword = $pinv->getItem(0);
                $this->setSword($player, $sword);
            } else {
				$this->notEnought($player,"Gold ingot");
                $player->getWorld()->addSound(new EndermanTeleportSound($player), [$player]);
            }
            return;
        }
        if($in == "§eKnockback Stick"){
            if($pinv->contains(ItemFactory::getInstance()->get(ItemIds::GOLD_INGOT, 0, 5))){
                $pinv->removeItem(ItemFactory::getInstance()->get(ItemIds::GOLD_INGOT, 0, 5));
                $this->messagebuy($player,"KnockBack Stick");
                $stick = ItemFactory::getInstance()->get(ItemIds::STICK);
                $stick->setCustomName("§bKnockback Stick");
                $stick->addEnchantment(new EnchantmentInstance(VanillaEnchantments::KNOCKBACK(), 1));
                $pinv->addItem($stick);
            } else {
            	$this->notEnought($player,"Gold ingot");
                $player->getWorld()->addSound(new EndermanTeleportSound($player), [$player]);
            }
            return;
        }
        if($in == "§eBow (Power I)"){
            if($pinv->contains(ItemFactory::getInstance()->get(ItemIds::GOLD_INGOT, 0, 24))){
                $pinv->removeItem(ItemFactory::getInstance()->get(ItemIds::GOLD_INGOT, 0, 24));
                $this->messagebuy($player,"Bow (Power I)");
                $bow = ItemFactory::getInstance()->get(ItemIds::BOW);
               
                $bow->addEnchantment(new EnchantmentInstance(VanillaEnchantments::POWER(), 1));
                $pinv->addItem($bow);
            } else {
            	$this->notEnought($player,"Gold ingot");
                $player->getWorld()->addSound(new EndermanTeleportSound($player), [$player]);
            }
            return;
        }
        if($in == "§eBow (Power I, Punch I)"){
            if($pinv->contains(ItemFactory::getInstance()->get(ItemIds::EMERALD, 0, 2))){
                $pinv->removeItem(ItemFactory::getInstance()->get(ItemIds::EMERALD, 0, 2));
                $this->messagebuy($player,"Bow (Power I, Punch I)");

                $bow = ItemFactory::getInstance()->get(ItemIds::BOW);
                $bow->addEnchantment(new EnchantmentInstance(VanillaEnchantments::POWER(), 1));
                $bow->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PUNCH(), 1));
                $pinv->addItem($bow);
            } else {
            	$this->notEnought($player,"Emerald");
               $player->getWorld()->addSound(new EndermanTeleportSound($player), [$player]);
            } 
            return;
        }
        if($item instanceof Armor && $in == "§eChainmail Set"){
            if(isset($this->armor[$player->getName()]) && in_array($this->armor[$player->getName()], ["iron", "diamond"])){
                return;
            }
            if(isset($this->armor[$player->getName()]) && $this->armor[$player->getName()] !== "chainmail") {

                if ($pinv->contains(ItemFactory::getInstance()->get(ItemIds::IRON_INGOT, 0, 40))) {
                    $pinv->removeItem(ItemFactory::getInstance()->get(ItemIds::IRON_INGOT, 0, 40));
                    $this->messagebuy($player, "Chainmail set");
                    $this->armor[$player->getName()] = "chainmail";
                    $this->setArmor($player);
                } else {
                    $this->notEnought($player, "Iron ingot");
                    $player->getWorld()->addSound(new EndermanTeleportSound($player), [$player]);
                }
                return;
            } else {
                $player->getWorld()->addSound(new EndermanTeleportSound($player), [$player]);
            }
        }
        if($item instanceof Armor && $in == "§eIron Set"){
            if(isset($this->armor[$player->getName()]) && in_array($this->armor[$player->getName()], ["diamond"])){
                return;
            }
            if($pinv->contains(ItemFactory::getInstance()->get(ItemIds::GOLD_INGOT, 0, 12))){
                $pinv->removeItem(ItemFactory::getInstance()->get(ItemIds::GOLD_INGOT, 0, 12));
                $this->messagebuy($player,"Iron set");
                 $this->armor[$player->getName()] = "iron";
                $this->setArmor($player);
            } else {
               $player->getWorld()->addSound(new EndermanTeleportSound($player), [$player]);
               $this->notEnought($player,"Gold ingot");
            } 
            return;
        }
        if($item instanceof Armor && $in == "§eDiamond Set"){
            if(isset($this->armor[$player->getName()]) && in_array($this->armor[$player->getName()], ["diamond"])){
                return;
            }
            if($pinv->contains(ItemFactory::getInstance()->get(ItemIds::EMERALD, 0, 6))){
                $pinv->removeItem(ItemFactory::getInstance()->get(ItemIds::EMERALD, 0, 6));
                $this->messagebuy($player,"Diamond set");
                $this->armor[$player->getName()] = "diamond";
                $this->setArmor($player);
            } else {
              $player->getWorld()->addSound(new EndermanTeleportSound($player), [$player]);
              $this->notEnought($player,"Emerald");
            }
            return;
        }
        $this->buyItem($item, $player); 
        if($item instanceof Pickaxe){
            $pickaxe = $this->getPickaxeByTier($player);
            $inv->setItem(20, $pickaxe);
        }
        if($item instanceof Axe){
            $axe = $this->getAxeByTier($player);
            $inv->setItem(21, $axe);
        }
	    }));
	    // Main Menu //
	    $inv->setItem(1, ItemFactory::getInstance()->get(BlockLegacyIds::WOOL, $meta[$team])->setCustomName("§fBlocks"));
	    $inv->setItem(2, ItemFactory::getInstance()->get(ItemIds::GOLDEN_SWORD)->setCustomName("§fMelee"));
	    $inv->setItem(3, ItemFactory::getInstance()->get(ItemIds::CHAINMAIL_BOOTS)->setCustomName("§fArmor"));
	    $inv->setItem(4, ItemFactory::getInstance()->get(ItemIds::STONE_PICKAXE)->setCustomName("§fTools"));
	    $inv->setItem(5, ItemFactory::getInstance()->get(ItemIds::BOW)->setCustomName("§fBow & Arrow"));
	    $inv->setItem(6, ItemFactory::getInstance()->get(ItemIds::BREWING_STAND)->setCustomName("§fPotions"));
	    $inv->setItem(7, ItemFactory::getInstance()->get(BlockLegacyIds::TNT)->setCustomName("§fUtility"));

	    // Block Menu //
	    $this->manageShop($player, $inv, "§fBlocks");
	    $menu->send($player);
	}

	public function messagebuy(Player $player, $item){
    	$pk = new LevelSoundEventPacket();
    	$pk->sound = 81;
    	$pk->extraData = 13;
    	$pk->disableRelativeVolume = false;
    	$pk->isBabyMob = false;
    	$pk->entityType = ":";
    	$pk->position = $player->getPosition();
    	$player->getNetworkSession()->getNetworkSession()->sendDataPacket($pk);
    	$player->sendMessage("§aYou purchased §7". $item);
	}

	public function notEnought(Player $player, $item){
		$player->sendMessage("§cYour $item not enought");

	}

    public function manageShop($player, $inv, $type){
        $team = $this->getTeam($player);
        $meta = [
            "red" => 14,
            "blue" => 11,
            "yellow" => 4,
            "green" => 5
        ];
        // BLOCKS //
        if($type == "§fBlocks"){
        $inv->setItem(19, ItemFactory::getInstance()->get(BlockLegacyIds::WOOL, $meta[$team], 16)
        ->setLore(["§f4 Iron"])
        ->setCustomName("§eWool")
        );
	    $inv->setItem(20, ItemFactory::getInstance()->get(BlockLegacyIds::TERRACOTTA, $meta[$team], 16)
	    ->setLore(["§f12 Iron"])
	    ->setCustomName("§eTerracotta")
	    );
	    $inv->setItem(21, ItemFactory::getInstance()->get(241, $meta[$team], 4)
	    ->setLore(["§f12 Iron"])
	    ->setCustomName("§eStained Glass")
	    );
	    $inv->setItem(22, ItemFactory::getInstance()->get(BlockLegacyIds::END_STONE, 0, 12)
	    ->setLore(["§f24 Iron"])
	    ->setCustomName("§eEnd Stone")
	    );
	    $inv->setItem(23, ItemFactory::getInstance()->get(BlockLegacyIds::LADDER, 0, 16)
	    ->setLore(["§f4 Iron"])
	    ->setCustomName("§eLadder")
	    );
	    $inv->setItem(24, ItemFactory::getInstance()->get(5, 0, 16)
	    ->setLore(["§64 Gold"])
	    ->setCustomName("§ePlank")
	    );
	    $inv->setItem(25, ItemFactory::getInstance()->get(BlockLegacyIds::OBSIDIAN, 0, 4)
	    ->setLore(["§24 Emerald"])
	    ->setCustomName("§eObsidian")
	    );
	    $inv->setItem(28, ItemFactory::getInstance()->get(0));
	    $inv->setItem(29, ItemFactory::getInstance()->get(0));
	    $inv->setItem(30, ItemFactory::getInstance()->get(0));
        }
        // SWORD //
        if($type == "§fMelee"){
        $inv->setItem(19, ItemFactory::getInstance()->get(ItemIds::STONE_SWORD)
        ->setLore(["§f10 Iron"])
        ->setCustomName("§eStone Sword")
        );
	    $inv->setItem(20, ItemFactory::getInstance()->get(ItemIds::IRON_SWORD)
	    ->setLore(["§67 Gold"])
	    ->setCustomName("§eIron Sword")
	    );
	    $inv->setItem(21, ItemFactory::getInstance()->get(ItemIds::DIAMOND_SWORD)
	    ->setLore(["§23 Emerald"])
	    ->setCustomName("§eDiamond Sword")
	    );
	    $stick = ItemFactory::getInstance()->get(ItemIds::STICK);
	    $stick->setLore(["§65 Gold"]);
	    $stick->setCustomName("§eKnockback Stick");
	    $stick->addEnchantment(new EnchantmentInstance(VanillaEnchantments::KNOCKBACK(), 1));
	    $inv->setItem(22, $stick);
	    $inv->setItem(23, ItemFactory::getInstance()->get(0));
	    $inv->setItem(24, ItemFactory::getInstance()->get(0));
	    $inv->setItem(25, ItemFactory::getInstance()->get(0));
	    $inv->setItem(28, ItemFactory::getInstance()->get(0));
	    $inv->setItem(29, ItemFactory::getInstance()->get(0));
	    $inv->setItem(30, ItemFactory::getInstance()->get(0));
        }
        // ARMOR //
        if($type == "§fArmor"){
        $inv->setItem(19, ItemFactory::getInstance()->get(ItemIds::CHAINMAIL_BOOTS)
        ->setLore(["§f40 Iron"])
        ->setCustomName("§eChainmail Set")
        );
	    $inv->setItem(20, ItemFactory::getInstance()->get(ItemIds::IRON_BOOTS)
	    ->setLore(["§612 Gold"])
	    ->setCustomName("§eIron Set")
	    );
	    $inv->setItem(21, ItemFactory::getInstance()->get(ItemIds::DIAMOND_BOOTS)
	    ->setLore(["§26 Emerald"])
	    ->setCustomName("§eDiamond Set")
	    );
	    $inv->setItem(22, ItemFactory::getInstance()->get(0));
	    $inv->setItem(23, ItemFactory::getInstance()->get(0));
	    $inv->setItem(24, ItemFactory::getInstance()->get(0));
	    $inv->setItem(25, ItemFactory::getInstance()->get(0));
	    $inv->setItem(28, ItemFactory::getInstance()->get(0));
	    $inv->setItem(29, ItemFactory::getInstance()->get(0));
	    $inv->setItem(30, ItemFactory::getInstance()->get(0));
        }
        if($type == "§fTools"){
        $inv->setItem(19, ItemFactory::getInstance()->get(ItemIds::SHEARS)
        ->setLore(["§f20 Iron"])
        ->setCustomName("§eShears")
        );
        $pickaxe = $this->getPickaxeByTier($player);
        $inv->setItem(20, $pickaxe);  
	    $axe = $this->getAxeByTier($player);
        $inv->setItem(21, $axe);  
	    $inv->setItem(22, ItemFactory::getInstance()->get(0));
	    $inv->setItem(23, ItemFactory::getInstance()->get(0));
	    $inv->setItem(24, ItemFactory::getInstance()->get(0));
	    $inv->setItem(25, ItemFactory::getInstance()->get(0));
	    $inv->setItem(28, ItemFactory::getInstance()->get(0));
	    $inv->setItem(29, ItemFactory::getInstance()->get(0));
	    $inv->setItem(30, ItemFactory::getInstance()->get(0));
        }
        if($type == "§fBow & Arrow"){
        $inv->setItem(19, ItemFactory::getInstance()->get(ItemIds::ARROW, 0, 8)
        ->setLore(["§62 Gold"])
        ->setCustomName("§eArrow")
        );
	    $inv->setItem(20, ItemFactory::getInstance()->get(ItemIds::BOW)
	    ->setLore(["§612 Gold"])
	    ->setCustomName("§eBow")
	    );
	    $bowpower = ItemFactory::getInstance()->get(ItemIds::BOW);
	    $bowpower->setLore(["§624 Gold"]);
	    $bowpower->setCustomName("§eBow (Power I)");
	    $bowpower->addEnchantment(new EnchantmentInstance(VanillaEnchantments::POWER(), 1));
	    $inv->setItem(21, $bowpower);
	    $bowpunch = ItemFactory::getInstance()->get(ItemIds::BOW);
	    $bowpunch->setLore(["§22 Emerald"]);
	    $bowpunch->setCustomName("§eBow (Power I, Punch I)");
	    $bowpunch->addEnchantment(new EnchantmentInstance(VanillaEnchantments::POWER(), 1));
        $bowpunch->addEnchantment(new EnchantmentInstance(VanillaEnchantments::PUNCH(), 1));
	    $inv->setItem(22, $bowpunch);
        $inv->setItem(23, ItemFactory::getInstance()->get(0));
	    $inv->setItem(24, ItemFactory::getInstance()->get(0));
	    $inv->setItem(25, ItemFactory::getInstance()->get(0));
	    $inv->setItem(28, ItemFactory::getInstance()->get(0));
	    $inv->setItem(29, ItemFactory::getInstance()->get(0));
	    $inv->setItem(30, ItemFactory::getInstance()->get(0));
        }
        if($type == "§fPotions"){
        $inv->setItem(19, ItemFactory::getInstance()->get(373, 16)
        ->setLore(["§21 Emerald"])
        ->setCustomName("§eSpeed Potion II (45 seconds)")
        );
	    $inv->setItem(20, ItemFactory::getInstance()->get(373, 11)
	    ->setLore(["§21 Emerald"])
	    ->setCustomName("§eJump Potion III (45 seconds)")
	    );
	    $inv->setItem(21, ItemFactory::getInstance()->get(373, 7)
	    ->setLore(["§22 Emerald"])
	    ->setCustomName("§eInvisibility Potion (30 seconds)")
	    );
	    $inv->setItem(22, ItemFactory::getInstance()->get(0));
	    $inv->setItem(23, ItemFactory::getInstance()->get(0));
	    $inv->setItem(24, ItemFactory::getInstance()->get(0));
	    $inv->setItem(25, ItemFactory::getInstance()->get(0));
	    $inv->setItem(28, ItemFactory::getInstance()->get(0));
	    $inv->setItem(29, ItemFactory::getInstance()->get(0));
	    $inv->setItem(30, ItemFactory::getInstance()->get(0));
        }
        if($type == "§fUtility"){
        $inv->setItem(19, ItemFactory::getInstance()->get(ItemIds::GOLDEN_APPLE)
        ->setLore(["§63 Gold"])
        ->setCustomName("§eGolden Apple")
        );
        $inv->setItem(20, ItemFactory::getInstance()->get(ItemIds::SNOWBALL)
        ->setLore(["§f40 Iron"])
        ->setCustomName("§eBedbug")
        );
        $inv->setItem(21, ItemFactory::getInstance()->get(ItemIds::SPAWN_EGG, 14)
        ->setLore(["§f120 Iron"])
        ->setCustomName("§eDream Defender")
        );
        $inv->setItem(22, ItemFactory::getInstance()->get(ItemIds::FIREBALL)
        ->setLore(["§f40 Iron"])
        ->setCustomName("§eFireball")
        ); 
        $inv->setItem(23, ItemFactory::getInstance()->get(BlockLegacyIds::TNT)
        ->setLore(["§68 Gold"])
        ->setCustomName("§eTNT")
        );
        $inv->setItem(24, ItemFactory::getInstance()->get(ItemIds::ENDER_PEARL)
        ->setLore(["§24 Emerald"])
        ->setCustomName("§eEnder Pearl")
        );
        $inv->setItem(25, ItemFactory::getInstance()->get(ItemIds::EGG)
        ->setLore(["§23 Emerald"])
        ->setCustomName("§eEgg Bridge")
        );
        $inv->setItem(26, ItemFactory::getInstance()->get(ItemIds::BUCKET, 1)
        ->setLore(["§64 Gold"])
        ->setCustomName("§eMagic Milk")
        );
		$inv->setItem(28, ItemFactory::getInstance()->get(ItemIds::CHEST, 1)
        ->setLore(["§f24 Iron"])
        ->setCustomName("§eCompact pop up tower")
        );
        }
    }

    public function dragon(){

	   foreach($this->players as $player){
	       $player->sendTitle("§cSudden Death");
           $this->addSound($player,'mob.enderdragon.growl');
	   }
        $this->suddendeath = new DragonTargetManager($this, $this->data["blocks"], $this->calculate($this->data["corner1"], $this->data["corner2"]));
	    $this->suddendeath->addDragon("green");
	    $this->suddendeath->addDragon("yellow");
	    $this->suddendeath->addDragon("blue");
	    $this->suddendeath->addDragon("red");
    }

    
    public function getPickaxeByTier($player, bool $forshop = true) : Item {
        if(isset($this->pickaxe[$player->getId()])){
            $tier = $this->pickaxe[$player->getId()];
            $pickaxe = [
                1 => ItemFactory::getInstance()->get(ItemIds::WOODEN_PICKAXE),
                2 => ItemFactory::getInstance()->get(ItemIds::WOODEN_PICKAXE),
                3 => ItemFactory::getInstance()->get(ItemIds::IRON_PICKAXE),
                4 => ItemFactory::getInstance()->get(ItemIds::GOLDEN_PICKAXE),
                5 => ItemFactory::getInstance()->get(ItemIds::DIAMOND_PICKAXE),
                6 => ItemFactory::getInstance()->get(ItemIds::DIAMOND_PICKAXE)
            ];
            $enchant = [
                1 => new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 1),
                2 => new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 1),
                3 => new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 2),
                4 => new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 2),
                5 => new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 3),
                6 => new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 3)
            ];
            $name = [
                1 => "§aWooden Pickaxe (Efficiency I)",
                2 => "§aWooden Pickaxe (Efficiency I)", 
                3 => "§aIron Pickaxe (Efficiency II)",
                4 => "§aGolden Pickaxe (Efficiency II)",
                5 => "§aDiamond Pickaxe (Efficiency III)",
                6 => "§aDiamond Pickaxe (Efficiency III)"
            ];
            $lore = [
                1 => [
                    "§f10 Iron",
                    "§eTier: §cI", 
                    ""
                ],
                2 => [
                    "§f10 Iron",
                    "§eTier: §cI", 
                    ""
                ], 
                3 => [
                    "§f10 Iron",
                    "§eTier: §cII", 
                    ""
                ], 
                4 => [
                    "§63 Gold",
                    "§eTier: §cIII", 
                    ""
                ],
                5 => [
                    "§66 Gold",
                    "§eTier: §cIV", 
                    ""
                ],
                6 => [
                    "§66 Gold",
                    "§eTier: §cV", 
                    "§aMax",
                    ""
                ] 
            ];
            $pickaxe[$tier]->addEnchantment($enchant[$tier]);
            if($forshop){
                $pickaxe[$tier]->setLore($lore[$tier]);
                $pickaxe[$tier]->setCustomName($name[$tier]);
            }
            return $pickaxe[$tier];
        }
        return \pocketmine\item\ItemFactory::getInstance()->get(0);
    }
    
    public function getAxeByTier($player, bool $forshop = true) : Item{
        if(isset($this->axe[$player->getId()])){
            $tier = $this->axe[$player->getId()];
            $axe = [
                1 => ItemFactory::getInstance()->get(ItemIds::WOODEN_AXE),
                2 => ItemFactory::getInstance()->get(ItemIds::WOODEN_AXE),
                3 => ItemFactory::getInstance()->get(ItemIds::STONE_AXE),
                4 => ItemFactory::getInstance()->get(ItemIds::IRON_AXE),
                5 => ItemFactory::getInstance()->get(ItemIds::DIAMOND_AXE),
                6 => ItemFactory::getInstance()->get(ItemIds::DIAMOND_AXE)
            ];
            $enchant = [
                1 => new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 1),
                2 => new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 1),
                3 => new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 1),
                4 => new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 2),
                5 => new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 3),
                6 => new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 3)
            ];
            $name = [
                1 => "§aWooden Axe (Efficiency I)",
                2 => "§aWooden Axe (Efficiency I)", 
                3 => "§aStone Axe (Efficiency I)",
                4 => "§aIron Axe (Efficiency II)",
                5 => "§aDiamond Axe (Efficiency III)",
                6 => "§aDiamond Axe (Efficiency III)" 
            ];
            $lore = [
                1 => [
                    "§f10 Iron",
                    "§eTier: §cI", 
                    ""
                ],
                2 => [
                    "§f10 Iron",
                    "§eTier: §cI", 
                    ""
                ], 
                3 => [
                    "§f10 Iron",
                    "§eTier: §cII", 
                    "",
                    "§7This is an upgradable item.",
                    "§7will lose 1 tier upon",
                    "§7death!",
                    ""
                ], 
                4 => [
                    "§63 Gold",
                    "§eTier: §cIII", 
                    ""
                ],
                5 => [
                    "§66 Gold",
                    "§eTier: §cIV", 
                    ""
                ],
                6 => [
                    "§66 Gold",
                    "§eTier: §cV", 
                    "§aMax",
                    ""
                ] 
            ];
            $axe[$tier]->addEnchantment($enchant[$tier]);
            if($forshop){
                $axe[$tier]->setLore($lore[$tier]);
                $axe[$tier]->setCustomName($name[$tier]);
            }
            return $axe[$tier];
        }
        return ItemFactory::getInstance()->get(0,0,0);
    } 

    
    public function buyItem(Item $item, Player $player){
        if(!isset($item->getLore()[0])) return;
        $lore = TextFormat::clean($item->getLore()[0], true);
        $desc = explode(" ", $lore);
        $value = $desc[0];
        $valueType = $desc[1];
        $value = intval($value);
        $id = null;
        if ($value < 1) return;
        if(!$item instanceof Pickaxe && !$item instanceof Axe){
            $item = $item->setLore([]);
        }
        switch ($valueType) {
            case "Iron":
                $id = ItemIds::IRON_INGOT;
                break;
            case "Gold":
                $id = ItemIds::GOLD_INGOT;
                break;
            case "Emerald":
                $id = ItemIds::EMERALD;
                break;
            default:
                break;
        }

        if($item instanceof Pickaxe){
            if(isset($this->pickaxe[$player->getId()])){
                if($this->pickaxe[$player->getId()] >= 6){
                    return;
                }
            }
            $item = $item->setLore([]);
            $item->setUnbreakable(true); 
            $c = 0;
            $i = 0;
            foreach($player->getInventory()->getContents() as $slot => $isi){
                if($isi instanceof Pickaxe){
                    $c++;
                    $i = $slot;
                }
            }

            $payment = ItemFactory::getInstance()->get($id, 0, $value);
            if ($player->getInventory()->contains($payment)) { 
                $this->pickaxe[$player->getId()] = $this->getNextTier($player, false); 
                $player->getInventory()->removeItem($payment);
				$this->messagebuy($player,"{$item->getName()}");
                if($c > 0){
                    $player->getInventory()->setItem($i, $item); 
                } else {
                    $player->getInventory()->addItem($item); 
                }
            } else {
                $player->getWorld()->addSound(new EndermanTeleportSound($player), [$player]);
                $this->notEnought($player,$payment->getName());
            }
            return;
        }
        if($item instanceof Axe){
            if(isset($this->axe[$player->getId()])){
                if($this->axe[$player->getId()] >= 6){
                    return;
                }
            } 
            $item = $item->setLore([]);
            $item->setUnbreakable(true);
            $c = 0;
            $i = 0;
            foreach($player->getInventory()->getContents() as $slot => $isi){
                if($isi instanceof Axe){
                    $c++;
                    $i = $slot;
                }
            }
            $payment = \pocketmine\item\ItemFactory::getInstance()->get($id, 0, $value);
            if ($player->getInventory()->contains($payment)) { 
                $this->axe[$player->getId()] = $this->getNextTier($player, true); 
                $player->getInventory()->removeItem($payment);
				$this->messagebuy($player,"{$item->getName()}");

                if($c > 0){
                    $player->getInventory()->setItem($i, $item); 
                } else {
                    $player->getInventory()->addItem($item); 
                }
            } else {
				$this->notEnought($player,$payment->getName());
              $player->getWorld()->addSound(new EndermanTeleportSound($player), [$player]); 
            }
            return; 
        }
        $payment = \pocketmine\item\ItemFactory::getInstance()->get($id, 0, $value);
        if ($player->getInventory()->contains($payment)) {
            $player->getInventory()->removeItem($payment);
            $it = \pocketmine\item\ItemFactory::getInstance()->get($item->getId(), $item->getDamage(), $item->getCount());
            if(in_array($item->getCustomName(), ["§eMagic Milk", "§eBedbug", "§beream Defender", "§eFireball", "§eInvisibility Potion (30 seconds)", "§eSpeed Potion II (45 seconds)", "§eJump Potion III (45 seconds)"])){
                $it->setCustomName("{$item->getCustomName()}");
            }
            if($player->getInventory()->canAddItem($it)){
                $player->getInventory()->addItem($it); 
            } else {
                $player->getWorld()->dropItem($player, $it);
            }
            $this->messagebuy($player,"{$item->getName()}");
        } else {
            $this->notEnought($player,$payment->getName());
            $player->getWorld()->addSound(new EndermanTeleportSound($player), [$player]);
        }
    }

    
    public function getLessTier($player, bool $type){
        if($type){
            if(isset($this->axe[$player->getId()])){
                $tier = $this->axe[$player->getId()];
                $less = [
                    6 => 4,
                    5 => 4,
                    4 => 3,
                    3 => 2,
                    2 => 1,
                    1 => 1
                ];
                return $less[$tier];
            }
        } else {
            if(isset($this->pickaxe[$player->getId()])){
                $tier = $this->pickaxe[$player->getId()];
                $less = [
                    6 => 4,
                    5 => 4,
                    4 => 3,
                    3 => 2,
                    2 => 1,
                    1 => 1
                ];
                return $less[$tier];
            } 
        }
        return "";
    }


    
    public function getNextTier($player, bool $type){
        if($type){
            if(isset($this->axe[$player->getId()])){
                $tier = $this->axe[$player->getId()];
                $less = [
                    1 => 3,
                    2 => 3,
                    3 => 4,
                    4 => 5,
                    5 => 6,
                    6 => 6
                ];
                return $less[$tier];
            }
        } else {
            if(isset($this->pickaxe[$player->getId()])){
                $tier = $this->pickaxe[$player->getId()];
                $less = [
                    1 => 3,
                    2 => 3,
                    3 => 4,
                    4 => 5,
                    5 => 6,
                    6 => 6
                ];
                return $less[$tier];
            } 
        }
        return "";
    } 

    /**
     * @param bool $restart
     */
    public function loadArena(bool $restart = false) {
        if(!$this->data["enabled"]) {
            $this->plugin->getLogger()->error("Can not load arena: Arena is not enabled!");
            return;
        }

        if(!$this->mapReset instanceof MapReset) {
            $this->mapReset = new MapReset($this);
        }

        if(!$restart) {
            $this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
        }
        else {
            $this->scheduler->reloadTimer();
            $this->world = $this->mapReset->loadMap($this->data["level"]);
        }

        if(!$this->world instanceof World) {
            $world = $this->mapReset->loadMap($this->data["level"]);
            if(!$world instanceof World) {
                $this->plugin->getLogger()->error("Arena level wasn't found. Try save level in setup mode.");
                $this->setup = true;
                return;
            }
            $this->world = $world;
        }

        $this->world->setAutoSave(false);

		$this->initTeams();


        $this->phase = static::PHASE_LOBBY;
        $this->players = [];

    }

    /**
     * @param bool $loadArena
     * @return bool $isEnabled
     */
    public function enable(bool $loadArena = true): bool {
        if(empty($this->data)) {
            return false;
        }
        if($this->data["level"] == null) {
            return false;
        }
        if(!$this->plugin->getServer()->getWorldManager()->isWorldGenerated($this->data["level"])) {
            return false;
        }
        if(!is_int($this->data["slots"])) {
            return false;
        }
        if(!is_array($this->data["location"])) {
            return false;
        }
        if(!is_array($this->data["joinsign"])) {
            return false;
        }
        if(count($this->data["joinsign"]) !== 2) {
            return false;
        }
        $this->data["enabled"] = true;
        $this->setup = false;
        if($loadArena) $this->loadArena();
        return true;
    }

    private function createBasicData() {
        $this->data = [
            "level" => null,
            "slots" => 16,
            "lobby" => null,
            "bed" => [],
            "shop" => [],
            "upgrade" => [],
            "location" => [],
            "distance" => null,
            "enabled" => false,
            "corner1" => [],
            "corner2" => [],
            "blocks" => [],
            "joinsign" => []
        ];
    }

    public function __destruct() {
        unset($this->scheduler);
    }
}
