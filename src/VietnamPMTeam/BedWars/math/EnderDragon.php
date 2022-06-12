<?php

declare(strict_types=1);

namespace VietnamPMTeam\BedWars\math;

use JetBrains\PhpStorm\Pure;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\world\World;
use pocketmine\block\Chest;
use pocketmine\block\EnderChest;
use VietnamPMTeam\BedWars\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\timings\Timings;
use VietnamPMTeam\BedWars\math\DragonTargetManager;
use VietnamPMTeam\BedWars\math\Math;

/**
 * Class EnderDragon
 * - This class has 2x smaller bounding box
 *
 * @package vixikhd\dragons\entity
 */
class EnderDragon extends Living {

    public static function getNetworkTypeId() : string{ return EntityIds::ENDER_DRAGON; }

    #[Pure] protected function getInitialSizeInfo() : EntitySizeInfo{
        return new EntitySizeInfo(4.0, 8.0);
    }

    /** @var DragonTargetManager $targetManager */
    public $targetManager;

    /** @var bool $isRotating */
    public $isRotating = false;
    /** @var int $rotationTicks */
	public $mid;
    public $rotationTicks = 0;
    public $timers = 0;
    public $isTarget = 0;
  
    /** @var float $lastRotation */
    public $lastRotation = 0.0;
    /** @var int $rotationChange */
    public $rotationChange; // from 5 to 10 (- or +)
    /** @var int $pitchChange */
    public $pitchChange;
    public $team;

    /**
     * EnderDragon constructor.
     *
     * @param World $world
     * @param CompoundTag $nbt
     * @param DragonTargetManager|null $targetManager
     * @param string $color
     */
    public function __construct(World $world, CompoundTag $nbt, DragonTargetManager $targetManager,string $color) {
        parent::__construct($world, $nbt);

        if($targetManager === null) {
            $this->flagForDespawn();
        }
        $this->team = $color;
        $this->targetManager = $targetManager;
    }

    public function changeRotation(bool $canStart = false) {
        // checks for new rotation
        
        if(!$this->isRotating) {
            if(!$canStart) {
                return;
            }

            if(microtime(true)-$this->lastRotation < 10) {
                return;
            }

            $this->rotationChange = mt_rand(5, 30);
            if(mt_rand(0, 1) === 0) {
                $this->rotationChange *= -1;
            }
            $this->pitchChange = mt_rand(-4, 4);

            $this->isRotating = true;
        }
     
        // checks for rotation cancel
        if($this->rotationTicks > mt_rand(5, 8)) {
            $this->lastRotation = microtime(true);
            $this->isRotating = false;
            return;
        }

        $this->setRotation(($this->getYaw() + ($this->rotationChange / 3)) % 360, ($this->getPitch() + ($this->pitchChange / 10)) % 360);
        $this->rotationTicks++;
    }

    /**
     * @param int $tickDiff
     * @return bool
     */
    public function entityBaseTick(int $tickDiff = 1): bool { // TODO - make better movement system
        $return = parent::entityBaseTick($tickDiff);
        if($this->targetManager === null) {
            $this->flagForDespawn();
            return false;
        }

        $blocks = array_values($this->targetManager->blocks);
        $time = $this->targetManager->plugin->scheduler->suddendeath[$this->targetManager->plugin->data["level"]];
        $red = $this->targetManager->plugin->data["location"]["red"];
        $blue = $this->targetManager->plugin->data["location"]["blue"];
        $yellow = $this->targetManager->plugin->data["location"]["yellow"];
        $green = $this->targetManager->plugin->data["location"]["green"];
        $corner1 = $this->targetManager->plugin->data["corner1"];
        $plugin = $this->targetManager->plugin;
        if ($this->distance($this->targetManager->mid) >= DragonTargetManager::MAX_DRAGON_MID_DIST || $this->getY() < 4 || $this->getY() > 250) {
	       if($this->team == "green"){
			   $loc = $this->targetManager->plugin->calculate($corner1, $yellow);
			   $this->targetManager->mid = $loc;
				$this->lookAt($loc);
				$this->setMotion($this->getDirectionVector());
			}
	       if($this->team == "yellow"){
			   $loc = $this->targetManager->plugin->calculate($red, $green);
			   $this->targetManager->mid = $loc;
			   $this->lookAt($loc);
			   $this->setMotion($this->getDirectionVector());
		   }
	       if($this->team == "blue"){
			   $loc = $this->targetManager->plugin->calculate($yellow, $blue);
			   $this->targetManager->mid = $loc;
			   $this->lookAt($loc);
			   $this->setMotion($this->getDirectionVector());
		   }
	       if($this->team == "red"){
			   $loc = $this->targetManager->plugin->calculate($green, $yellow);
			   $this->targetManager->mid = $loc;
			   $this->lookAt($loc);
			   $this->setMotion($this->getDirectionVector());
		   }

        }

        $this->changeRotation();
		$this->setMotion($this->getDirectionVector());


        return $return;
    }

    /**
     * Function copied from PocketMine (api missing - setting entity noclip)
     *
     * @param float $dx
     * @param float $dy
     * @param float $dz
     */
    public function move(float $dx, float $dy, float $dz): void {
        $this->blocksAround = null;

        Timings::$entityMove->startTiming();

        $movX = $dx;
        $movY = $dy;
        $movZ = $dz;

        if($this->keepMovement){
            $this->boundingBox->offset($dx, $dy, $dz);
        }else{
            $this->ySize *= 0.4;

            $axisalignedbb = clone $this->boundingBox;

            assert(abs($dx) <= 20 and abs($dy) <= 20 and abs($dz) <= 20, "Movement distance is excessive: dx=$dx, dy=$dy, dz=$dz");

            $list = $this->world->getCollisionCubes($this, $this->world->getTickRateTime() > 50 ? $this->boundingBox->offsetCopy($dx, $dy, $dz) : $this->boundingBox->addCoord($dx, $dy, $dz), false);
            foreach ($list as $bb) {
                $blocks = $this->world->getBlockAt((int)$bb->minX, (int)$bb->minY, (int)$bb->minZ);
                if(!$blocks instanceof EnderChest && !$blocks instanceof Chest){
                $this->targetManager->removeBlock($this, (int)$bb->minX, (int)$bb->minY, (int)$bb->minZ);
                }
            }

            $this->boundingBox->offset(0, $dy, 0); // x
            $fallingFlag = ($this->onGround or ($dy != $movY and $movY < 0));
            $this->boundingBox->offset($dx, 0, 0); // y
            $this->boundingBox->offset(0, 0, $dz); // z

            if($this->stepHeight > 0 and $fallingFlag and $this->ySize < 0.05 and ($movX != $dx or $movZ != $dz)){
                $cx = $dx;
                $cy = $dy;
                $cz = $dz;
                $dx = $movX;
                $dy = $this->stepHeight;
                $dz = $movZ;

                $axisalignedbb1 = clone $this->boundingBox;

                $this->boundingBox->setBB($axisalignedbb);

                foreach (Math::getCollisionBlocks($this->world, $this->boundingBox->addCoord($dx, $dy, $dz)) as $block) {
                    $this->targetManager->removeBlock($this, $block->getX(), $block->getY(), $block->getZ());
                }

                $this->boundingBox->offset(0, $dy, 0);
                $this->boundingBox->offset($dx, 0, 0);
                $this->boundingBox->offset(0, 0, $dz);

                if(($cx ** 2 + $cz ** 2) >= ($dx ** 2 + $dz ** 2)){
                    $dx = $cx;
                    $dy = $cy;
                    $dz = $cz;
                    $this->boundingBox->setBB($axisalignedbb1);
                }
                else {
                    $this->ySize += 0.5;
                }
            }
        }

        $this->x = ($this->boundingBox->minX + $this->boundingBox->maxX) / 2;
        $this->y = $this->boundingBox->minY - $this->ySize;
        $this->z = ($this->boundingBox->minZ + $this->boundingBox->maxZ) / 2;

        $this->checkChunks();
        $this->checkBlockCollision();
        $this->checkGroundState($movX, $movY, $movZ, $dx, $dy, $dz);
        $this->updateFallState($dy, $this->onGround);

        if($movX != $dx){
            $this->motion->x = 0;
        }
        if($movY != $dy){
            $this->motion->y = 0;
        }
        if($movZ != $dz){
            $this->motion->z = 0;
        }

        Timings::$entityMoveTimer->stopTiming();
    }

    /**
     * Wtf mojang
     * - Function edited to send +180 yaw
     *
     * @param bool $teleport
     */
    protected function broadcastMovement(bool $teleport = false) : void{
        $pk = new MoveActorAbsolutePacket();
        $pk->entityRuntimeId = $this->id;
        $pk->position = $this->getOffsetPosition($this);

        //this looks very odd but is correct as of 1.5.0.7
        //for arrows this is actually x/y/z rotation
        //for mobs x and z are used for pitch and yaw, and y is used for headyaw
        $pk->xRot = $this->pitch;
        $pk->yRot = ($this->yaw + 180) % 360; //TODO: head yaw
        $pk->zRot = ($this->yaw + 180) % 360;

        if($teleport){
            $pk->flags |= MoveActorAbsolutePacket::FLAG_TELEPORT;
        }

        $this->world->broadcastPacketToViewers($this, $pk);
    }

    /**
     * @param EntityDamageEvent $source
     */
 

    /**
     * @param Player $player
     */
    public function onCollideWithPlayer(Player $player): void {
        $player->attack(new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 0.5));

        parent::onCollideWithPlayer($player);
    }

    /**
     * @param int $seconds
     */
    public function setOnFire(int $seconds): void {}

    /**
     * @return string
     */
    public function getName(): string {
        return "Ender Dragon";
    }

}