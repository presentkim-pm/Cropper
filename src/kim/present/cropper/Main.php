<?php

/**
 *  ____                           _   _  ___
 * |  _ \ _ __ ___  ___  ___ _ __ | |_| |/ (_)_ __ ___
 * | |_) | '__/ _ \/ __|/ _ \ '_ \| __| ' /| | '_ ` _ \
 * |  __/| | |  __/\__ \  __/ | | | |_| . \| | | | | | |
 * |_|   |_|  \___||___/\___|_| |_|\__|_|\_\_|_| |_| |_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  PresentKim (debe3721@gmail.com)
 * @link    https://github.com/PresentKim
 * @license https://www.gnu.org/licenses/lgpl-3.0 LGPL-3.0 License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 *
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace kim\present\cropper;

use pocketmine\block\Block;
use pocketmine\block\Crops;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\VanillaItems;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;

class Main extends PluginBase implements Listener{
    public function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    /**
     * @priority HIGHEST
     */
    public function onBlockBreakEvent(BlockBreakEvent $event) : void{
        $block = $event->getBlock();
        if(!self::isRipeCrop($block)){
            return;
        }

        $player = $event->getPlayer();
        if(!$player->isSurvival()){
            return;
        }

        $seedItem = $block->getPickedItem();
        $drops = $event->getDrops();
        for($i = 0, $size = count($drops); $i < $size; ++$i){
            if($drops[$i]->equals($seedItem)){
                $drops[$i]->setCount($drops[$i]->getCount() - 1);
                if($drops[$i]->getCount() <= 0){
                    unset($drops[$i]);
                }
                break;
            }
        }
        $event->setDrops($drops);

        //Run useItemOn() when after BlockBreakEvent processing.
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player, $block, $seedItem) : void{
            $world = $player->getWorld();
            $pos = $block->getPosition();
            if(!$world->useItemOn($pos->down(), $seedItem, Facing::UP, new Vector3(0, 0, 0), $player)){
                $world->dropItem($pos, $seedItem);
            }
        }), 1);
    }

    /**
     * @ignoreCancelled true
     * @priority MONITOR
     */
    public function onPlayerInteractEvent(PlayerInteractEvent $event) : void{
        if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK){
            return;
        }

        $block = $event->getBlock();
        if(!self::isRipeCrop($block)){
            return;
        }

        $player = $event->getPlayer();
        if(!$player->isSurvival() || $player->isSneaking()){
            return;
        }

        //Run useBreakOn() when after PlayerInteractEvent processing.
        $item = VanillaItems::AIR();
        if($player->getWorld()->useBreakOn($block->getPosition(), $item, $player, true)){
            $player->getHungerManager()->exhaust(0.025, PlayerExhaustEvent::CAUSE_MINING);
        }
    }

    public static function isRipeCrop(Block $block) : bool{
        return $block instanceof Crops && $block->getAge() >= $block::MAX_AGE;
    }
}