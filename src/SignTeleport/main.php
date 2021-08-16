<?php

namespace SignTeleport;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\level\Position;
use pocketmine\event\block\BlockBreakEvent;

class main extends PluginBase implements Listener
{

    private Config $sign;

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this,$this);
        if (!file_exists($this->getDataFolder()))
        {
            @mkdir($this->getDataFolder(), 0755, true);
        }
        $this->sign = new Config($this->getDataFolder() . "sign.yml", Config::YAML);
    }

    public function onSignChange(SignChangeEvent $event)
    {
        $block = $event->getBlock();
        $player = $event->getPlayer();
        $x = $block->getX();
        $y = $block->getY();
        $z = $block->getZ();
        if($event->getLine(0) == "Teleport")
        {
            if (!$player->isOp())
            {
                $player->sendMessage("§cこの看板を設置する権限がありません");
                return;
            }
            if($event->getLine(3) == "ok" || $event->getLine(3) == "pok")
            {
                $place_before = $x . ":" . $y . ":" . $z . ":" . $block->getLevel()->getFolderName();
                $line1 = explode(",", $event->getLine(1));
                $x_after = $line1[0];
                $y_after = $line1[1];
                $z_after = $line1[2];
                $world_after = $line1[3];
                $msg = $event->getLine(2);
                if($event->getLine(3) == "pok"){
                    $DATA = $x_after . ":" . $y_after . ":" . $z_after . ":" . $world_after . ":" . $msg .":". "p";
                }else {
                    $DATA = $x_after . ":" . $y_after . ":" . $z_after . ":" . $world_after . ":" . $msg;
                }
                $this->sign->set($place_before, $DATA);
                $this->sign->save();
                $player->sendMessage("§bTeleport看板作成完了！!");
                $event->setLine(0, "§b【☆Teleport☆】");
                $event->setLine(1, "§6X:{$x_after} Y:{$y_after} Z:{$z_after}");
                $event->setLine(2, $msg);
                $event->setLine(3, "§a看板タップでテレポート！");
            }
        }

    }

    public function onTap(PlayerInteractEvent $event)
    {
        $player = $event->getPlayer();
        $name = $player->getName();
        $block = $event->getBlock();
        $x = $block->getX();
        $y = $block->getY();
        $z = $block->getZ();
        $place_before = $x.":".$y.":".$z.":".$block->getLevel()->getFolderName();
        if($this->sign->exists($place_before) && $player->getInventory()->getItemInHand()->getId() != 323)
        {
            $DATA = $this->sign->get($place_before);
            $DATAS = explode(":",$DATA);
            $x_after = (float)$DATAS[0];
            $y_after = (float)$DATAS[1];
            $z_after = (float)$DATAS[2];
            $world_after = $this->getServer()->getLevelByName("{$DATAS[3]}");
            if($DATAS[4] == NULL) {
                $msg = "";
            }else{
                if(strpos($DATAS[4],"%name") != false) {
                    $msg = str_replace("%name", $name, $DATAS[4]);
                }else{
                    $msg = $DATAS[4];
                }
            }
            $pos = new Position($x_after,$y_after,$z_after,$world_after);
            $player->teleport($pos);
            if($DATAS[5] == NULL) {
                $this->getServer()->broadcastMessage($msg);
            }else if($DATAS[5] == "p"){
                $player->sendMessage($msg);
            }
        }
    }

    public function onbreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        $name = $player->getName();
        $block = $event->getBlock();
        $x = $block->getX();
        $y = $block->getY();
        $z = $block->getZ();
        $place = $x . ":" . $y . ":" . $z . ":" . $block->getLevel()->getName();
        if ($this->sign->exists($place)) {
            if ($player->isOp()) {
                $this->sign->remove($place);
                $this->sign->save();
                $player->sendMessage("Teleport看板を解体しました");
            } else {
                $player->getServer()->broadcastMessage("Teleport看板を§c" . $name . "§6が破壊しようとしている！");
                $event->setCancelled();
            }
        }
    }

}