<?php

declare(strict_types=1);

namespace VietnamPMTeam\BedWars\math;

use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;
use VietnamPMTeam\BedWars\Game;
use VietnamPMTeam\BedWars\math\EnderDragon;
use VietnamPMTeam\BedWars\math\ThrownBlock;

/**
 * Class DragonTargetManager
 * @package vixikhd\dragons\arena
 */
class DragonTargetManager {

    public const MAX_DRAGON_MID_DIST = 100; // Dragon will rotate when will be distanced 64 blocks from map center

    public $plugin;

    public $blocks = [];

    public $baits = [];

    public $mid; // Used when all the blocks the are broken

    public $dragons = [];


    public $random;


    public function __construct(Game $plugin, array $blocksToDestroy, Vector3 $mid) {
        $this->plugin = $plugin;
        $this->blocks = $blocksToDestroy;
        $this->mid = $mid;

        $this->random = new Random();
    }


    /**
     * @param EnderDragon $dragon
     *
     * @param int $x
     * @param int $y
     * @param int $z
     */
    public function removeBlock(EnderDragon $dragon, int $x, int $y, int $z): void {
        $blockPos = new Vector3($x, $y, $z);
        $block = $this->plugin->level->getBlock($blockPos);


        $this->plugin->level->setBlock($blockPos, BlockFactory::getInstance()->get(BlockLegacyIds::AIR, 0));

        unset($this->blocks["$x:$y:$z"]);

        $dragon->changeRotation(true);
    }

    /**
     * @param $team
     */
    public function addDragon($team): void {
        $findSpawnPos = function (Vector3 $mid): Vector3 {
            $randomAngle = mt_rand(0, 359);
            $x = ((DragonTargetManager::MAX_DRAGON_MID_DIST - 5) * cos($randomAngle)) + $mid->getX();
            $z = ((DragonTargetManager::MAX_DRAGON_MID_DIST - 5) * sin($randomAngle)) + $mid->getZ();

            return new Vector3($x, $mid->getY(), $z);
        };

        $dragon = new EnderDragon($this->plugin->level, EnderDragon::createBaseNBT($findSpawnPos($this->mid), new Vector3()), $this,$team);
        $dragon->lookAt($this->mid->getPosition()->asVector3());
        $dragon->setMaxHealth(70);
        $dragon->setHealth(70);

        $dragon->spawnToAll();
    }

    /**
     * @param Vector3 $baitPos
     */
    public function addBait(Vector3 $baitPos) {
        $this->baits[] = $baitPos;
    }
}