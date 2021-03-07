<?php

/**
 *
 *  ____  _             _         _____
 * | __ )| |_   _  __ _(_)_ __   |_   _|__  __ _ _ __ ___
 * |  _ \| | | | |/ _` | | '_ \    | |/ _ \/ _` | '_ ` _ \
 * | |_) | | |_| | (_| | | | | |   | |  __/ (_| | | | | | |
 * |____/|_|\__,_|\__, |_|_| |_|   |_|\___|\__,_|_| |_| |_|
 *                |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  Blugin team
 * @link    https://github.com/Blugin
 * @license https://www.gnu.org/licenses/lgpl-3.0 LGPL-3.0 License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 *
 * @noinspection PhpDocSignatureInspection
 * @noinspection PhpIllegalPsrClassPathInspection
 */

declare(strict_types=1);

namespace blugin\cropper;

use pocketmine\block\Block;
use pocketmine\block\Crops;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;

class Cropper extends PluginBase implements Listener{
    public function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    /** @priority HIGH */
    public function onBlockBreakEvent(BlockBreakEvent $event) : void{
        if(!self::isRipeCrop($block = $event->getBlock()))
            return;

        $player = $event->getPlayer();
        if(!$player->isSurvival())
            return;

        $seedItem = $block->getPickedItem();
        $drops = $event->getDrops();
        for($i = 0, $size = count($drops); $i < $size; ++$i){
            if($drops[$i]->equals($seedItem)){
                $drops[$i]->setCount($drops[$i]->getCount() - 1);
                if($drops[$i]->getCount() <= 0)
                    unset($drops[$i]);
            }
        }
        $event->setDrops($drops);

        //Run useItemOn() when after BlockBreakEvent processing.
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player, $block, $seedItem) : void{
            $world = $player->getWorld();
            $pos = $block->getPos();
            if(!$world->useItemOn($pos->down(), $seedItem, Facing::UP, new Vector3(0, 0, 0), $player)){
                $world->dropItem($pos, $seedItem);
            }
        }), 1);
    }

    /** @priority MONITOR */
    public function onPlayerInteractEvent(PlayerInteractEvent $event) : void{
        if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK || !self::isRipeCrop($block = $event->getBlock()))
            return;

        $player = $event->getPlayer();
        if(!$player->isSurvival())
            return;

        //Run breakBlock() when after PlayerInteractEvent processing.
        $player->breakBlock($block->getPos());
    }

    public static function isRipeCrop(Block $block) : bool{
        return $block instanceof Crops && $block->getMeta() >= 7;
    }
}
