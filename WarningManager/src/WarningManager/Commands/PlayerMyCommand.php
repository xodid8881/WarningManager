<?php
declare(strict_types=1);

namespace WarningManager\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\player\Player;
use pocketmine\Server;
use WarningManager\WarningManager;

class PlayerMyCommand extends Command
{

  protected $plugin;

  public function __construct(WarningManager $plugin)
  {
    $this->plugin = $plugin;
    parent::__construct('경고', '경고 명령어.', '/경고');
  }

  public function execute(CommandSender $sender, string $commandLabel, array $args)
  {
    $tag = "§c【 §fWarningManager §c】";
    $name = $sender->getName ();
    $arr = [];
    foreach($this->plugin->getLists($sender) as $list){
      if (isset($this->plugin->pldb [strtolower($name)] ["경고"] ["누적경고"] [$list])){
        $count = $this->plugin->pldb [strtolower($name)] ["경고"] ["누적경고"] [$list] ["경고정도"];
        array_push($arr, array('text' => '- ' . $list . "\n{$count} 개의 누적경고 +"));
      } else if (isset($this->plugin->pldb [strtolower($name)] ["경고"] ["기간경고"] [$list])){
        $count = $this->plugin->pldb [strtolower($name)] ["경고"] ["기간경고"] [$list] ["경고정도"];
        array_push($arr, array('text' => '- ' . $list . "\n{$count} 개의 기간경고 +"));
      }
    }
    $encode = [
      'type' => 'form',
      'title' => '§c【 §fWarningManager §c】',
      'content' => '§7-경고목록',
      'buttons' => $arr
    ];
    $packet = new ModalFormRequestPacket();
    $packet->formId = 101014;
    $packet->formData = json_encode($encode);
    $sender->getNetworkSession()->sendDataPacket($packet);
    return true;
  }
}
