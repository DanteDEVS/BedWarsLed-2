<?php

namespace VietnamPMTeam\BedWars\math;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Throwable;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\world\{Position, World};
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\world\Explosion;
use VietnamPMTeam\BedWars\Game;
use pocketmine\math\RayTraceResult;
use pocketmine\math\AxisAlignedBB;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\player\Player;

class Fireball extends Throwable{
    
    public static function getNetworkTypeId() : string{ return EntityIds::FIREBALL; }

    protected function getInitialSizeInfo() : EntitySizeInfo{
        return new EntitySizeInfo(0.50, 0.50); //TODO: eye height ??
    }

    /** @var Entity $entity */
    protected $entity;

    protected $gravity = 0.0;



    protected $drag = 0;
    
    protected $life = 0;
    
    protected $damage = 2;
    public $owner = null;
    public $arena = null;

    public function __construct(Location $location, World $world, CompoundTag $nbt, Player $player = null) {
        parent::__construct($location, $world, $nbt);
    }

    public function getName(): string{
        return "Fireball";
    }
    
    public function entityBaseTick(int $tickDiff = 1) : bool {
        if ($this->closed){
            return false;
        }
         if(!$this->arena instanceof Game){
            $this->flagForDespawn();
            return false;
        }
          if(!$this->owner instanceof Player){
            $this->flagForDespawn();
            return false;
        }

        $this->life++;
        if($this->life > 200){
            $this->flagForDespawn();
        }
        if ($this->getOwningEntity() == null){
            $this->flagForDespawn();
            return true;
        }

        return true;
    }
    
    public function attack(EntityDamageEvent $source) : void{
		if($source->getCause() === EntityDamageEvent::CAUSE_VOID){
			parent::attack($source);
		}
		if($source instanceof EntityDamageByEntityEvent){
		     $damager = $source->getDamager();
			 $this->setMotion($damager->getDirectionVector()->add(0, 0, 0)->multiply(0.5));
		}
	}
	
	public function isCritical() : bool{
		return $this->entity->syncNetworkData(EntityMetadataFlags::CRITICAL);
	}

	public function setCritical(bool $value = true) : void{
		$this->setGenericFlag(EntityMetadataFlags::CRITICAL, $value);
	}

	public function getResultDamage() : int{
		$base = parent::getResultDamage();
		if($this->isCritical()){
			return ($base + mt_rand(0, (int) ($base / 2) + 1));
		}else{
			return $base;
		}
	}
	
    public function onHitBlock(Block $blockHit, RayTraceResult $hitResult): void{
		parent::onHitBlock($blockHit, $hitResult);
		$this->doExplosionAnimation();
		$this->flagForDespawn();
	}
	
	protected function onHitEntity(Entity $entityHit, RayTraceResult $hitResult) : void{
		parent::onHitEntity($entityHit, $hitResult);
		$this->doExplosionAnimation();
		$this->flagForDespawn();
	}

	protected function doExplosionAnimation(): void{
	    $this->explode(); 
		$explosionSize = 2 * 2;
		$minX = (int) floor($this->x - $explosionSize - 1);
		$maxX = (int) ceil($this->x + $explosionSize + 1);
		$minY = (int) floor($this->y - $explosionSize - 1);
		$maxY = (int) ceil($this->y + $explosionSize + 1);
		$minZ = (int) floor($this->z - $explosionSize - 1);
		$maxZ = (int) ceil($this->z + $explosionSize + 1);


		$explosionBB = new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ);

		$list = $this->world->getNearbyEntities($explosionBB, $this);
		foreach($list as $entity){
			$distance = $entity->distance($this->getPosition()->asVector3()) / $explosionSize;

			if($distance <= 2){
				if($entity instanceof  Player) {
					$motion = $entity->getPosition()->subtract($this->getPosition()->asVector3(), $this->getPosition()->asVector3(), $this->getPosition()->asVector3())->normalize();
					$ev = new EntityDamageByEntityEvent($this->getOwningEntity(), $entity, EntityDamageEvent::CAUSE_PROJECTILE, 3);
					$entity->attack($ev);
					$entity->setMotion($motion->multiply(2));
				}
			}
		}
	}
	
	public function explode(): void{
		$ev = new ExplosionPrimeEvent($this, 2);
		$ev->call();
		if(!$ev->isCancelled()){
			$explosion = new Explosion(Position::fromObject($this->add(0, $this->height / 2), $this->world), $ev->getForce(), $this);
			if($ev->isBlockBreaking()){
				$explosion->explodeA();
			}
			$explosion->explodeB();
		}
	}
} 