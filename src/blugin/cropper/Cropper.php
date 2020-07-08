<?php

/*
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
 */

declare(strict_types=1);

namespace blugin\cropper;

use pocketmine\block\Crops;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;

class Cropper extends PluginBase implements Listener{
    /**
     * Called when the plugin is enabled
     */
    public function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    /**
     * @priority HIGH
     *
     * @param BlockBreakEvent $event
     */
    public function onBlockBreakEvent(BlockBreakEvent $event) : void{
        if($event->isCancelled())
            return;

        $block = $event->getBlock();
        if(!$block instanceof Crops)
            return;

        $player = $event->getPlayer();
        if(!$player->isSurvival())
            return;

        if($block->getMeta() < 7)
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
            if(!$player->getWorld()->useItemOn($block->getPos()->down(), $seedItem, Facing::UP, new Vector3(0.0, 0.0, 0.0), $player)){
                $player->getWorld()->dropItem($block->getPos(), $seedItem);
            }
        }), 1);
    }

    /**
     * @priority MONITOR
     *
     * @param PlayerInteractEvent $event
     */
    public function onPlayerInteractEvent(PlayerInteractEvent $event) : void{
        if($event->isCancelled())
            return;

        if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK)
            return;

        $player = $event->getPlayer();
        if(!$player->isSurvival())
            return;

        $block = $event->getBlock();
        if(!$block instanceof Crops)
            return;

        if($block->getMeta() < 7)
            return;

        //Run breakBlock() when after PlayerInteractEvent processing.
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player, $block) : void{
            $player->breakBlock($block->getPos());
        }), 1);
    }
}
