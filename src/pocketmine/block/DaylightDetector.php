<?php

/*
 *
 *  _____   _____   __   _   _   _____  __    __  _____
 * /  ___| | ____| |  \ | | | | /  ___/ \ \  / / /  ___/
 * | |     | |__   |   \| | | | | |___   \ \/ /  | |___
 * | |  _  |  __|  | |\   | | | \___  \   \  /   \___  \
 * | |_| | | |___  | | \  | | |  ___| |   / /     ___| |
 * \_____/ |_____| |_|  \_| |_| /_____/  /_/     /_____/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author iTX Technologies
 * @link https://itxtech.org
 *
 */

namespace pocketmine\block;

use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;
use pocketmine\Player;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\tile\Tile;
use pocketmine\tile\DLDetector;

class DaylightDetector extends RedstoneSource{
    const DAYLIGHT_DETECTOR = 151;
    protected $id = self::DAYLIGHT_SENSOR;
	//protected $hasStartedUpdate = false;

	public function getName() : string{
		return "Daylight Sensor";
	}

	public function getBoundingBox(){
		if($this->boundingBox === null){
			$this->boundingBox = $this->recalculateBoundingBox();
		}
		return $this->boundingBox;
	}

	public function canBeFlowedInto(){
		return false;
	}

	public function canBeActivated() : bool {
		return true;
	}

	/**
	 * @return DLDetector
	 */
	protected function getTile(){
		$t = $this->getLevel()->getTile($this);
		if($t instanceof DLDetector){
			return $t;
		}else{
			$nbt = new CompoundTag("", [
				new StringTag("id", Tile::DAY_LIGHT_DETECTOR),
				new IntTag("x", $this->x),
				new IntTag("y", $this->y),
				new IntTag("z", $this->z)
			]);
			return Tile::createTile(Tile::DAY_LIGHT_DETECTOR, $this->getLevel()->getChunk($this->x >> 4, $this->z >> 4), $nbt);
		}
	}

    public function isRedstone(){
        return true;
    }


    public function isRedstoneSource(){
        return true;
    }

    public function isPowered(){
        return true;
    }

    public function place(Item $item, Block $block, Block $target, $face, $fx, $fy, $fz, Player $player = null){
        $this->getLevel()->setBlock($block, $this, true, false);
        $nbt = new CompoundTag("", [new StringTag("id", Tile::DAY_LIGHT_DETECTOR), new IntTag("x", $this->x), new IntTag("y", $this->y), new IntTag("z", $this->z)]);

        Tile::createTile(Tile::DAY_LIGHT_DETECTOR, $this->getLevel()->getChunk($this->x >> 4, $this->z >> 4), $nbt);
        return true;
    }

    public function onUpdate($type){
        if($type === Level::BLOCK_UPDATE_SCHEDULED || $type === Level::BLOCK_UPDATE_NORMAL){
            $this->getLevel()->scheduleUpdate($this, 50);
            $this->BroadcastRedstoneUpdate(Level::REDSTONE_UPDATE_PLACE, $this->getPower());
        }
        return false;
    }


    public function onActivate(Item $item, Player $player = null){
		$this->getLevel()->setBlock($this, new DaylightDetectorInverted(), true, true);
		$this->getTile()->onUpdate();
		return true;
	}

	public function isActivated(Block $from = null){
		return $this->getTile()->isActivated();
	}

	public function onBreak(Item $item){
		$this->getLevel()->setBlock($this, new Air());
		if($this->isActivated()) $this->deactivate();
	}

	public function getHardness() {
		return 0.2;
	}

	public function getResistance(){
		return 1;
	}

    public function getPower(){
        return $this->getLevel()->getTile($this) instanceof DLDetector?($this->getId() == self::DAYLIGHT_DETECTOR?$this->getLevel()->getTile($this)->getPower():16 - (16 - $this->getLevel()->getTile($this)->getPower())):0;
    }

    protected function recalculateBoundingBox(){
        return new AxisAlignedBB($this->x, $this->y, $this->z, $this->x + 1, $this->y + 0.375, $this->z + 1);
    }



    public function getDrops(Item $item) : array {
		return [
			[self::DAYLIGHT_SENSOR, 0, 1]
		];
	}
}