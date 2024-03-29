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

namespace pocketmine\level\generator\normal\biome;

use pocketmine\block\Block;
use pocketmine\level\generator\populator\TallGrass;
use pocketmine\level\generator\populator\WaterPit;
use pocketmine\Player;

class IcePlainsBiome extends SnowyBiome
{

    public function __construct()
    {
        parent::__construct();

        $tallGrass = new TallGrass();
        $tallGrass->setBaseAmount(5);

        $this->addPopulator($tallGrass);

        $this->setElevation(63, 74);

        $this->temperature = 0.05;
        $this->rainfall = 0.8;
        /** @var Player $server */
        if ($this->temperature == 0.75 and $this->rainfall == 0.4 and  $server->getServer()->specialgenerator === True) {
            $waterpit = new WaterPit();
            $waterpit->setBaseAmount(1000);

            $this->setGroundCover([
                Block::get(Block::ICE)
            ]);
            $this->addPopulator($waterpit);

            $noise = 64;
            $noise2 = 64;
            $height = (int)($noise + 64 and $noise2 + 64);
            for ($y = 0; $y < 256; $y++) {
                if ($y < $height) {
                    $this->setGroundCover([
                        Block::get(Block::ICE)
                    ]);
                } else {
                    $this->setGroundCover([
                        Block::get(Block::SNOW_LAYER),
                        Block::get(Block::SNOW_BLOCK)
                    ]);
                }
            }
        }
    }

    public function getName(): string
    {
        return "Ice Plains";
    }
}