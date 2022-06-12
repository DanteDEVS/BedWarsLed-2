<?php

namespace VietnamPMTeam\BedWars\math;

use pocketmine\block\Air;
use pocketmine\block\Block;
use VietnamPMTeam\BedWars\Game;
use VietnamPMTeam\BedWars\math\Vector3;
use pocketmine\block\BlockIds;
use pocketmine\player\Player;
use VietnamPMTeam\BedWars\BedWars;

class TowerNorth {

    private $arena;

    public function __construct(Game $arena)
    {
        $this->arena = $arena;
        
    }

    public function  Tower (Block $player,Player $p,$team) {
        $meta = [
            "red" => 14,
            "blue" => 11,
            "yellow" => 4,
            "green" => 5
        ];
        $ld1 = $player->getPosition();
        $ld2 = $player->getPosition()->add(0,1);
        $ld3 = $player->getPosition()->add(0,2);
        $ld4 = $player->getPosition()->add(0,3);
        $ld5 = $player->getPosition()->add(0,4);

        $list = [];
        $list[] = $player->getPosition()->add(-1, 1, -2);
        $list[] = $player->getPosition()->add(-2, 1, -1);
        $list[] = $player->getPosition()->add(-2, 1);
        $list[] = $player->getPosition()->add(-1, 1, 1);
        $list[] = $player->getPosition()->add(0, 1, 1);
        $list[] = $player->getPosition()->add(1, 1, 1);
        $list[] = $player->getPosition()->add(2, 1);
        $list[] = $player->getPosition()->add(2, 1, -1);
        $list[] = $player->getPosition()->add(1, 1, -2);
        //
        $list[] = $player->getPosition()->add(-1, 2, -2);
        $list[] = $player->getPosition()->add(-2, 2, -1);
        $list[] = $player->getPosition()->add(-2, 2);
        $list[] = $player->getPosition()->add(-1, 2, 1);
        $list[] = $player->getPosition()->add(0, 2, 1);
        $list[] = $player->getPosition()->add(1, 2, 1);
        $list[] = $player->getPosition()->add(2, 2);
        $list[] = $player->getPosition()->add(2, 2, -1);
        $list[] = $player->getPosition()->add(1, 2, -2);

        //
        $list[] = $player->getPosition()->add(0, 3, -2);
        $list[] = $player->getPosition()->add(-1, 3, -2);
        $list[] = $player->getPosition()->add(-2, 3, -1);
        $list[] = $player->getPosition()->add(-2, 3);
        $list[] = $player->getPosition()->add(-1, 3, 1);
        $list[] = $player->getPosition()->add(0, 3, 1);
        $list[] = $player->getPosition()->add(1, 3, 1);
        $list[] = $player->getPosition()->add(2, 3);
        $list[] = $player->getPosition()->add(2, 3, -1);
        $list[] = $player->getPosition()->add(1, 3, -2);
       
        //
        $list[] =  $player->getPosition()->add(-2, 4, 1);
        $list[] = $player->getPosition()->add(-2, 4);
        $list[] =  $player->getPosition()->add(-2, 4, -1);
        $list[] =  $player->getPosition()->add(-2, 4, -2);
        $list[] = $player->getPosition()->add(-1, 4, 1);
        $list[] = $player->getPosition()->add(-1, 4);
        $list[] = $player->getPosition()->add(-1, 4, -1);
        $list[] = $player->getPosition()->add(-1, 4, -2);
        $list[] = $player->getPosition()->add(0, 4, 1);
        $list[] = $player->getPosition()->add(0, 4, -1);
        $list[] = $player->getPosition()->add(0, 4, -2);
        $list[] = $player->getPosition()->add(1, 4, 1);
        $list[] = $player->getPosition()->add(1, 4);
        $list[] = $player->getPosition()->add(1, 4, -1);
        $list[] = $player->getPosition()->add(1, 4, -2);
        $list[] = $player->getPosition()->add(2, 4, 1);
        $list[] = $player->getPosition()->add(2, 4);
        $list[] = $player->getPosition()->add(2, 4, -1);
        $list[] = $player->getPosition()->add(2, 4, -2);

        //
        $list[] = $player->getPosition()->add(-3, 4, -2);
        $list[] = $player->getPosition()->add(-3, 5, -2);
        $list[] = $player->getPosition()->add(-3, 6, -2);
        $list[] = $player->getPosition()->add(-3, 5, -1);
        $list[] = $player->getPosition()->add(-3, 5);
        $list[] = $player->getPosition()->add(-3, 4, 1);
        $list[] = $player->getPosition()->add( -3, 5, 1);
        $list[] = $player->getPosition()->add(-3, 6, 1);
        $list[] = $player->getPosition()->add(3, 4, -2);
        $list[] = $player->getPosition()->add(3, 5, -2);
        $list[] = $player->getPosition()->add(3, 6, -2);
        $list[] = $player->getPosition()->add(3, 5, -1);
        $list[] = $player->getPosition()->add(3, 5);
        $list[] = $player->getPosition()->add(3, 4, 1);
        $list[] = $player->getPosition()->add(3, 5, 1);
        $list[] = $player->getPosition()->add(3, 6, 1);
        $list[] = $player->getPosition()->add(-2, 4, 2);
        $list[] = $player->getPosition()->add(-2, 5, 2);
        $list[] = $player->getPosition()->add(-2, 6, 2);
        $list[] = $player->getPosition()->add(-1, 5, 2);
        $list[] = $player->getPosition()->add(0, 4, 2);
        $list[] = $player->getPosition()->add(0, 5, 2);
        $list[] = $player->getPosition()->add(0, 6, 2);
        $list[] = $player->getPosition()->add(1, 5, 2);
        $list[] = $player->getPosition()->add(2, 4, 2);
        $list[] = $player->getPosition()->add(2, 5, 2);
        $list[] = $player->getPosition()->add(2, 6, 2);
        $list[] = $player->getPosition()->add(-2, 4, -3);
        $list[] = $player->getPosition()->add(-2, 5, -3);
        $list[] = $player->getPosition()->add(-2, 6, -3);
        $list[] = $player->getPosition()->add(-1, 5, -3);
        $list[] = $player->getPosition()->add(0, 4, -3);
        $list[] = $player->getPosition()->add(0, 5, -3);
        $list[] = $player->getPosition()->add(0, 6, -3);
        $list[] = $player->getPosition()->add(1, 5, -3);
        $list[] = $player->getPosition()->add(2, 4, -3);
        $list[] = $player->getPosition()->add(2, 5, -3);
         $ladermeta = 2;

                foreach($list as $pe){
                        if($player->getPosition()->getWorld()->getBlockAt($pe->getX(),$pe->getY(),$pe->getZ())->getId() == 0){
                             BedWars::getInstance()->getArenaByPlayer($p)->addPlacedBlock($p->getLevel()->getBlockAt($pe->getX(),$pe->getY(),$pe->getZ()));
                             $player->getPosition()->getWorld()->setBlock($pe,Block::get(BlockIds::WOOL,$meta[$team]));
                        }

                }

                 if($player->getPosition()->getWorld()->getBlockat($ld1->x,$ld1->y,$ld1->z)->getId() == 0){
                         $p->getPosition()->getWorld()->setBlock($ld1,Block::get(BlockIds::LADDER,$ladermeta),true,true);
                          BedWars::getInstance()->getArenaByPlayer($p)->addPlacedBlock($p->getPosition()->getWorld()->getBlockAt($ld1->x,$ld1->y,$ld1->z));

                     }
                 if($player->getPosition()->getWorld()->getBlockat($ld2->x,$ld2->y,$ld2->z)->getId() == 0){
                         $p->getPosition()->getWorld()->setBlock($ld2,Block::get(BlockIds::LADDER,$ladermeta),true,true);
                         BedWars::getInstance()->getArenaByPlayer($p)->addPlacedBlock($p->getPosition()->getWorld()->getBlockAt($ld2->x,$ld2->y,$ld2->z));
                 }
                 if($player->getPosition()->getWorld()->getBlockat($ld3->x,$ld3->y,$ld3->z)->getId() == 0){
                         $p->getPosition()->getWorld()->setBlock($ld3,Block::get(BlockIds::LADDER,$ladermeta),true,true);
                         BedWars::getInstance()->getArenaByPlayer($p)->addPlacedBlock($p->getPosition()->getWorld()->getBlockAt($ld3->x,$ld3->y,$ld3->z));

                 }
                 if($player->getPosition()->getWorld()->getBlockat($ld4->x,$ld4->y,$ld4->z)->getId() == 0){
                         $p->getPosition()->getWorld()->setBlock($ld4,Block::get(BlockIds::LADDER,$ladermeta),true,true);
                         BedWars::getInstance()->getArenaByPlayer($p)->addPlacedBlock($p->getPosition()->getWorld()->getBlockAt($ld4->x,$ld4->y,$ld4->z));
                 }
                 if($player->getPosition()->getWorld()->getBlockat($ld5->x,$ld5->y,$ld5->z)->getId() == 0){
                         $p->getPosition()->getWorld()->setBlock($ld5,Block::get(BlockIds::LADDER,$ladermeta),true,true);
                         BedWars::getInstance()->getArenaByPlayer($p)->addPlacedBlock($p->getPosition()->getWorld()->getBlockAt($ld5->x,$ld5->y,$ld5->z));
                 }


       }


}