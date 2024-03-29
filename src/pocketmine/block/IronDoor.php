<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____  
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \ 
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/ 
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_| 
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 * 
 *
*/

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\level\Level;
use pocketmine\level\sound\DoorSound;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;

class IronDoor extends Door{

	protected $id = self::IRON_DOOR_BLOCK;

	public function __construct($meta = 0){
		$this->meta = $meta;
	}

    public function canBeActivated(): bool
    {
        return false;
    }

    public function isSolid(){
        return false;
    }

    private function getFullDamage(){
        $damage = $this->getDamage();
        $isUp = ($damage & 0x08) > 0;

        if($isUp){
            $down = $this->getSide(Vector3::SIDE_DOWN)->getDamage();
            $up = $damage;
        }
        else{
            $down = $damage;
            $up = $this->getSide(Vector3::SIDE_UP)->getDamage();
        }

        $isRight = ($up & 0x01) > 0;

        return $down & 0x07 | ($isUp?8:0) | ($isRight?0x10:0);
    }

    protected function recalculateBoundingBox(){
        $f = 0.1875;
        $damage = $this->getFullDamage();

        $bb = new AxisAlignedBB($this->x, $this->y, $this->z, $this->x + 1, $this->y + 2, $this->z + 1);

        $j = $damage & 0x03;
        $isOpen = (($damage & 0x04) > 0);
        $isRight = (($damage & 0x10) > 0);

        if($j === 0){
            if($isOpen){
                if(!$isRight){
                    $bb->setBounds($this->x, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + $f);
                }
                else{
                    $bb->setBounds($this->x, $this->y, $this->z + 1 - $f, $this->x + 1, $this->y + 1, $this->z + 1);
                }
            }
            else{
                $bb->setBounds($this->x, $this->y, $this->z, $this->x + $f, $this->y + 1, $this->z + 1);
            }
        }
        elseif($j === 1){
            if($isOpen){
                if(!$isRight){
                    $bb->setBounds($this->x + 1 - $f, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + 1);
                }
                else{
                    $bb->setBounds($this->x, $this->y, $this->z, $this->x + $f, $this->y + 1, $this->z + 1);
                }
            }
            else{
                $bb->setBounds($this->x, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + $f);
            }
        }
        elseif($j === 2){
            if($isOpen){
                if(!$isRight){
                    $bb->setBounds($this->x, $this->y, $this->z + 1 - $f, $this->x + 1, $this->y + 1, $this->z + 1);
                }
                else{
                    $bb->setBounds($this->x, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + $f);
                }
            }
            else{
                $bb->setBounds($this->x + 1 - $f, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + 1);
            }
        }
        elseif($j === 3){
            if($isOpen){
                if(!$isRight){
                    $bb->setBounds($this->x, $this->y, $this->z, $this->x + $f, $this->y + 1, $this->z + 1);
                }
                else{
                    $bb->setBounds($this->x + 1 - $f, $this->y, $this->z, $this->x + 1, $this->y + 1, $this->z + 1);
                }
            }
            else{
                $bb->setBounds($this->x, $this->y, $this->z + 1 - $f, $this->x + 1, $this->y + 1, $this->z + 1);
            }
        }

        return $bb;
    }

    public function onUpdate($type){
        if($type === Level::BLOCK_UPDATE_NORMAL){
            if($this->getSide(0)->getId() === self::AIR){ // Replace with common break method
                $this->getLevel()->setBlock($this->getSide(0), new Air(), false);
                if($this->getSide(1) instanceof Door){
                    $this->getLevel()->setBlock($this->getSide(1), new Air(), false);
                }

                return Level::BLOCK_UPDATE_NORMAL;
            }
        }

        return false;
    }

    public function toggleStatus(){
        if(($this->getDamage() & 0x08) === 0x08){ // Top
            $down = $this->getSide(0);
            if($down->getId() === $this->getId()){
                $meta = $down->getDamage() ^ 0x04;
                $this->getLevel()->setBlock($down, Block::get($this->getId(), $meta), true);
                $this->getLevel()->addSound(new DoorSound($this));
                return true;
            }
            return false;
        }
        else{
            $this->meta ^= 0x04;
            $this->getLevel()->setBlock($this, $this, true);
            $this->getLevel()->addSound(new DoorSound($this));
        }
        return $this;
    }

    public function onRedstoneUpdate($type, $power){
        if($this->getSide(0)->getId() === $this->getId()){
            $this_meta = $this->getSide(0)->meta;
        }
        else{
            $this_meta = $this->meta;
        }
        if($this->isPowered() and $this_meta < 4){
            $this->toggleStatus();
        }
        if(!$this->isPowered() and $this_meta >= 4){
            $this->toggleStatus();
        }
    }


    public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
        if($face === 1){
            $blockUp = $this->getSide(1);
            $blockDown = $this->getSide(0);
            if($blockUp->canBeReplaced() === false or ($blockDown->isTransparent() === true && !($blockDown instanceof Slab && ($blockDown->meta & 0x08) === 0x08) || ($blockDown instanceof WoodSlab && ($blockDown->meta & 0x08) === 0x08) || ($blockDown instanceof Stair && ($blockDown->meta & 0x04) === 0x04))){
                return false;
            }
            $direction = $player instanceof Player?$player->getDirection():0;
            $face = [0 => 3,1 => 4,2 => 2,3 => 5];
            $next = $this->getSide($face[(($direction + 2) % 4)]);
            $next2 = $this->getSide($face[$direction]);
            $metaUp = 0x08;
            if($next->getId() === $this->getId() or ($next2->isTransparent() === false and $next->isTransparent() === true)){ // Door hinge
                $metaUp |= 0x01;
            }

            $this->setDamage($player->getDirection() & 0x03);
            $this->getLevel()->setBlock($block, $this, true, true); // Bottom
            $this->getLevel()->setBlock($blockUp, $b = Block::get($this->getId(), $metaUp), true); // Top
            $this->onRedstoneUpdate(null, null);
            return true;
        }

        return false;
    }


    public function onBreak(Item $item){
        if(($this->getDamage() & 0x08) === 0x08){
            $down = $this->getSide(0);
            if($down->getId() === $this->getId()){
                $this->getLevel()->setBlock($down, new Air(), true);
            }
        }
        else{
            $up = $this->getSide(1);
            if($up->getId() === $this->getId()){
                $this->getLevel()->setBlock($up, new Air(), true);
            }
        }
        $this->getLevel()->setBlock($this, new Air(), true);

        return true;
    }

    public function getName() : string{
		return "Iron Door Block";
	}

	public function getToolType(){
		return Tool::TYPE_PICKAXE;
	}

	public function getHardness() {
		return 5;
	}

	public function getDrops(Item $item) : array {
		if($item->isPickaxe() >= 1){
			return [
				[Item::IRON_DOOR, 0, 1],
			];
		}else{
			return [];
		}
	}

	public function onActivate(Item $item, Player $player = null){
		if($player instanceof Player) return true;
		else return parent::onActivate($item, $player);
	}
}