<?php
declare(strict_types=1);

namespace WarningManager\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\player\Player;
use WarningManager\WarningManager;

class OPCommand extends Command
{

  protected $plugin;

  public function __construct(WarningManager $plugin)
  {
    $this->plugin = $plugin;
    parent::__construct('경고관리', '경고관리 명령어.', '/경고관리');
  }

  public function execute(CommandSender $sender, string $commandLabel, array $args)
  {
    $encode = [
      'type' => 'form',
      'title' => '§c【 §fWarningManager §c】',
      'content' => '§7버튼을 눌러주세요.',
      'buttons' => [
        [
          'text' => "§7경고추가\n§7- 플레이어에게 경고를 지급합니다."
        ],
        [
          'text' => "§7경고제거\n§7- 플레이어 경고를 제거합니다."
        ]
      ]
    ];
    $packet = new ModalFormRequestPacket ();
    $packet->formId = 101010;
    $packet->formData = json_encode($encode);
    $sender->getNetworkSession()->sendDataPacket($packet);
  }
}
