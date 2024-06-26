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
 * @author       PresentKim (debe3721@gmail.com)
 * @link         https://github.com/PresentKim
 * @license      https://www.gnu.org/licenses/lgpl-3.0 LGPL-3.0 License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 *
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace kim\present\cropper;

use kim\present\removeplugindatafolder\PluginDataFolderEraser;
use pocketmine\block\Block;
use pocketmine\block\Crops;
use pocketmine\block\Stem;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\VanillaItems;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;

use function count;

class Main extends PluginBase implements Listener{

    private bool $handleBreak = false;

    public function onEnable() : void{
        PluginDataFolderEraser::erase($this);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    /** @priority HIGHEST */
    public function onBlockBreakEvent(BlockBreakEvent $event) : void{
        if(!$this->handleBreak){
            return;
        }

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
        $found = false;
        for($i = 0, $size = count($drops); $i < $size; ++$i){
            if($drops[$i]->equals($seedItem)){
                $drops[$i]->setCount($drops[$i]->getCount() - 1);
                if($drops[$i]->getCount() <= 0){
                    unset($drops[$i]);
                }
                $found = true;
                break;
            }
        }
        if(!$found && count($player->getInventory()->removeItem($seedItem)) > 0){
            return;
        }

        //Run useItemOn() when after BlockBreakEvent processing.
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player, $block, $seedItem) : void{
            $world = $player->getWorld();
            $pos = $block->getPosition();
            if(!$world->useItemOn($pos->down(), $seedItem, Facing::UP, new Vector3(0, 0, 0), $player)){
                $world->dropItem($pos, $seedItem);
            }
        }), 1);

        $this->handleBreak = false;
    }

    /**
     * @handleCancelled
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
        $this->handleBreak = true;
        $item = VanillaItems::AIR();
        if($player->getWorld()->useBreakOn($block->getPosition(), $item, $player, true)){
            $player->getHungerManager()->exhaust(0.005, PlayerExhaustEvent::CAUSE_MINING);
        }else{
            $this->handleBreak = false;
        }
    }

    public static function isRipeCrop(Block $block) : bool{
        return $block instanceof Crops && !($block instanceof Stem) && $block->getAge() >= $block::MAX_AGE;
    }
}
