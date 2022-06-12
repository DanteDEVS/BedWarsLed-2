<?php

namespace pocketmine\entity;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\math\Vector3;
use pocketmine\entity\Location;
use pocketmine\world\World;

class CustomEntity extends Location {
	
  /** @var int */
  public static $entityCount = 1;
  /**
  * @var string[]
  * @phpstan-var array<int|string, class-string<Entity>>
  */
  private static $knownEntities = [];
  /**
  * @var string[]
  * @phpstan-var array<class-string<Entity>, string>
  */
  private static $saveNames = [];	
  
  public static function createEntity($type, World $level, CompoundTag $nbt, ...$args) : ?Entity{
		if(isset(self::$knownEntities[$type])){
			$class = self::$knownEntities[$type];
			/** @see Entity::__construct() */
			return new $class($level, $nbt, ...$args);
		}

		return null;
	}

	public static function createBaseNBT(Vector3 $pos, ?Vector3 $motion = null, float $yaw = 0.0, float $pitch = 0.0) : CompoundTag{
		return new CompoundTag("", [
			new ListTag("Pos", [
				new DoubleTag("", $pos->x),
				new DoubleTag("", $pos->y),
				new DoubleTag("", $pos->z)
			]),
			new ListTag("Motion", [
				new DoubleTag("", $motion !== null ? $motion->x : 0.0),
				new DoubleTag("", $motion !== null ? $motion->y : 0.0),
				new DoubleTag("", $motion !== null ? $motion->z : 0.0)
			]),
			new ListTag("Rotation", [
				new FloatTag("", $yaw),
				new FloatTag("", $pitch)
			])
		]);
	}
  
	public static function registerEntity(string $className, bool $force = false, array $saveNames = []) : bool{
		$class = new \ReflectionClass($className);
		if(is_a($className, Entity::class, true) and !$class->isAbstract()){
			if($className::NETWORK_ID !== -1){
				self::$knownEntities[$className::NETWORK_ID] = $className;
			}elseif(!$force){
				return false;
			}

			$shortName = $class->getShortName();
			if(!in_array($shortName, $saveNames, true)){
				$saveNames[] = $shortName;
			}

			foreach($saveNames as $name){
				self::$knownEntities[$name] = $className;
			}

			self::$saveNames[$className] = reset($saveNames);

			return true;
		}

		return false;
	}
}
