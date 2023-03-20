<?php
declare(strict_types=1);

namespace WarningManager;

use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;

use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;

use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\permission\DefaultPermissions;

class EventListener implements Listener
{

  protected $plugin;

  public function __construct(WarningManager $plugin)
  {
    $this->plugin = $plugin;
  }
  public function OnJoin (PlayerJoinEvent $event)
  {
    $player = $event->getPlayer ();
    $name = $player->getName ();
    if (!isset($this->plugin->pldb [strtolower($name)])){
      $this->plugin->pldb [strtolower($name)] ["경고"] ["기간경고"] = [];
      $this->plugin->pldb [strtolower($name)] ["경고"] ["누적경고"] = [];
      $this->plugin->save ();
    }
    if (!isset($this->plugin->wdb [strtolower($name)])){
      $this->plugin->wdb [strtolower($name)] ["경고"] ["총경고"] = 0;
      $this->plugin->wdb [strtolower($name)] ["경고"] ["플레이어"] = "없음";
      $this->plugin->wdb [strtolower($name)] ["경고"] ["정보"] = "없음";
    }
  }
  public function OnMove (PlayerMoveEvent $event)
  {
    $player = $event->getPlayer ();
    $name = $player->getName ();
    if (isset($this->plugin->wdb [strtolower($name)])){
      if ($this->plugin->wdb [strtolower($name)] ["경고"] ["총경고"] >= 100) {
        $player->sendPopup ( "당신은 차단된 플레이어 입니다." );
        $event->cancel();
        return true;
      }
    }
  }
  public function OnInteract (PlayerInteractEvent $event)
  {
    $player = $event->getPlayer ();
    $name = $player->getName ();
    if (isset($this->plugin->wdb [strtolower($name)])){
      if ($this->plugin->wdb [strtolower($name)] ["경고"] ["총경고"] >= 100) {
        $player->sendPopup ( "당신은 차단된 플레이어 입니다." );
        $event->cancel();
        return true;
      }
    }
  }
  public function OnDropItem (PlayerDropItemEvent $event)
  {
    $player = $event->getPlayer ();
    $name = $player->getName ();
    if (isset($this->plugin->wdb [strtolower($name)])){
      if ($this->plugin->wdb [strtolower($name)] ["경고"] ["총경고"] >= 100) {
        $player->sendPopup ( "당신은 차단된 플레이어 입니다." );
        $event->cancel();
        return true;
      }
    }
  }
  public function onPlayerChat (PlayerChatEvent $event)
  {
    $player = $event->getPlayer ();
    $name = $player->getName ();
    if (isset($this->plugin->wdb [strtolower($name)])){
      if ($this->plugin->wdb [strtolower($name)] ["경고"] ["총경고"] >= 100) {
        $player->sendMessage ( $this->plugin->tag() . "당신은 차단된 플레이어로 해당 이벤트 사용이 불가능합니다." );
        $event->cancel();
        return true;
      }
    }
  }
  public function CommandCncell(PlayerCommandPreprocessEvent $event)
  {

    $player = $event->getPlayer();
    $name = $player->getName ();
    $message = $event->getMessage();
    $args = explode(" ", $message);
    $pos = strpos($args[0], $message);
    $check = str_replace('/','', $message);
    if (isset($this->plugin->wdb [strtolower($name)])){
      if ($this->plugin->wdb [strtolower($name)] ["경고"] ["총경고"] >= 100) {
        if($check != "경고"){
          if($pos !== false){
            $event->cancel();
            $player->sendMessage ( $this->plugin->tag() . "당신은 차단된 플레이어로 해당 이벤트 사용이 불가능합니다." );
          }
        }
      }
    }
  }

  public function onPlace(BlockPlaceEvent $event)
  {
    $player = $event->getPlayer();
    $name = $player->getName ();
    if (isset($this->plugin->wdb [strtolower($name)])){
      if ($this->plugin->wdb [strtolower($name)] ["경고"] ["총경고"] >= 100) {
        $player->sendMessage ( $this->plugin->tag() . "당신은 차단된 플레이어로 해당 이벤트 사용이 불가능합니다." );
        $event->cancel();
        return true;
      }
    }
  }

  public function onBreak(BlockBreakEvent $event)
  {
    $player = $event->getPlayer();
    $name = $player->getName ();
    if (isset($this->plugin->wdb [strtolower($name)])){
      if ($this->plugin->wdb [strtolower($name)] ["경고"] ["총경고"] >= 100) {
        $player->sendMessage ( $this->plugin->tag() . "당신은 차단된 플레이어로 해당 이벤트 사용이 불가능합니다." );
        $event->cancel();
        return true;
      }
    }
  }

  public function onTransaction(InventoryTransactionEvent $event) {
    $transaction = $event->getTransaction();
    $player = $transaction->getSource ();
    $name = $player->getName ();
    foreach($transaction->getActions() as $action){
      if($action instanceof SlotChangeAction){
        if (isset($this->plugin->wdb [strtolower($name)])){
          if ($this->plugin->wdb [strtolower($name)] ["경고"] ["총경고"] >= 100) {
            $player->sendMessage ( $this->plugin->tag() . "당신은 차단된 플레이어로 해당 이벤트 사용이 불가능합니다." );
            $event->cancel();
            return true;
          }
        }
      }
    }
  }

  public function onPacket(DataPacketReceiveEvent $event)
  {
    $packet = $event->getPacket();
    $player = $event->getOrigin()->getPlayer();
    if ($packet instanceof ModalFormResponsePacket) {
      $name = $player->getName();
      $id = $packet->formId;
      if($packet->formData == null) {
        return true;
      }
      $data = json_decode($packet->formData, true);
      if ($id === 101010) {
        if ($data === 0) {
          if (!$player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $player->sendMessage($this->plugin->tag() . "권한이 없습니다.");
            return true;
          }
          $this->addList ($player);
          return true;
        }
        if ($data === 1) {
          if (!$player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $player->sendMessage($this->plugin->tag() . "권한이 없습니다.");
            return true;
          }
          $this->GoingPlayerWarning ($player);
          return true;
        }
      }

      if ($id === 101011) {
        if ($data === 0) {
          if (!$player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $player->sendMessage($this->plugin->tag() . "권한이 없습니다.");
            return true;
          }
          $this->addCheckWarning ($player);
          return true;
        }
        if ($data === 1) {
          if (!$player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $player->sendMessage($this->plugin->tag() . "권한이 없습니다.");
            return true;
          }
          $this->addTimeWarning ($player);
          return true;
        }
      }

      if ($id === 101012) {
        if (!isset($data[0])) {
          $player->sendMessage( $this->plugin->tag() . '플레이어 이름을 적어주세요.');
          return;
        }
        if (!isset($data[1])) {
          $player->sendMessage( $this->plugin->tag() . '경고를 지급하는 이유를 적어주세요.');
          return;
        }
        if (!isset($data[2])) {
          $player->sendMessage( $this->plugin->tag() . '경고 정도를 적어주세요.');
          return;
        }
        if (! is_numeric ($data[2])) {
          $player->sendMessage ( $this->plugin->tag() . "숫자를 이용 해야됩니다. " );
          return;
        }
        if (!isset($data[3])) {
          $player->sendMessage( $this->plugin->tag() . '경고 기간을 적어주세요.');
          return;
        }
        $tagmessage = explode ( " ", $data[3]);
        if (!isset($tagmessage[1])){
          $player->sendMessage( $this->plugin->tag() . '경고 기간은 뛰어쓰기를 이용해서 적어주세요.');
          return;
        }
        if ($tagmessage[1] == "년"){
          $count = date("YmdHis",strtotime ("+{$tagmessage[0]} years"));
          $countcheck = date("Y년 m월 d일 H시 i분 s초",strtotime ("+{$tagmessage[0]} years"));
        } else if ($tagmessage[1] == "월"){
          $count = date("YmdHis",strtotime ("+{$tagmessage[0]} months"));
          $countcheck = date("Y년 m월 d일 H시 i분 s초",strtotime ("+{$tagmessage[0]} months"));
        } else if ($tagmessage[1] == "일"){
          $count = date("YmdHis",strtotime ("+{$tagmessage[0]} days"));
          $countcheck = date("Y년 m월 d일 H시 i분 s초",strtotime ("+{$tagmessage[0]} days"));
        } else if ($tagmessage[1] == "시"){
          $count = date("YmdHis",strtotime ("+{$tagmessage[0]} hours"));
          $countcheck = date("Y년 m월 d일 H시 i분 s초",strtotime ("+{$tagmessage[0]} hours"));
        } else if ($tagmessage[1] == "분"){
          $count = date("YmdHis",strtotime ("+{$tagmessage[0]} minutes"));
          $countcheck = date("Y년 m월 d일 H시 i분 s초",strtotime ("+{$tagmessage[0]} minutes"));
        } else if ($tagmessage[1] == "초"){
          $count = date("YmdHis",strtotime ("+{$tagmessage[0]} seconds"));
          $countcheck = date("Y년 m월 d일 H시 i분 s초",strtotime ("+{$tagmessage[0]} seconds"));
        }
        if (isset($this->plugin->pldb [strtolower($data[0])])){
          $this->plugin->pldb [strtolower($data[0])] ["경고"] ["기간경고"] [date("Y년 m월 d일 H시 i분 s초")] ["경고이유"] = $data[1];
          $this->plugin->pldb [strtolower($data[0])] ["경고"] ["기간경고"] [date("Y년 m월 d일 H시 i분 s초")] ["경고정도"] = $data[2];
          $this->plugin->pldb [strtolower($data[0])] ["경고"] ["기간경고"] [date("Y년 m월 d일 H시 i분 s초")] ["경고기간"] = $count;
          $this->plugin->pldb [strtolower($data[0])] ["경고"] ["기간경고"] [date("Y년 m월 d일 H시 i분 s초")] ["경고제거기간"] = $countcheck;
          $this->plugin->wdb [strtolower($data[0])] ["경고"] ["총경고"] += $data[2];
          $this->plugin->save();
          $player->sendMessage($this->plugin->tag() . "정상적으로 기간경고를 지급 했습니다.");
          $date = date("Y년 m월 d일 H시 i분 s초");
          $this->plugin->BandTimeMessage ($data[0],$data[2],$data[1],$date,$countcheck);
          return true;
        } else {
          $player->sendMessage($this->plugin->tag() . "{$data [0]} 의 플레이어의 정보는 존재하지 않습니다.");
          return true;
        }
      }

      if ($id === 101013) {
        if (!isset($data[0])) {
          $player->sendMessage( $this->plugin->tag() . '플레이어 이름을 적어주세요.');
          return;
        }
        if (!isset($data[1])) {
          $player->sendMessage( $this->plugin->tag() . '경고를 지급하는 이유를 적어주세요.');
          return;
        }
        if (!isset($data[2])) {
          $player->sendMessage( $this->plugin->tag() . '경고 정도를 적어주세요.');
          return;
        }
        if (! is_numeric ($data[2])) {
          $player->sendMessage ( $this->plugin->tag() . "숫자를 이용 해야됩니다. " );
          return;
        }
        if (isset($this->plugin->pldb [strtolower($data[0])])){
          $this->plugin->pldb [strtolower($data[0])] ["경고"] ["누적경고"] [date("Y년 m월 d일 H시 i분 s초")] ["경고이유"] = $data[1];
          $this->plugin->pldb [strtolower($data[0])] ["경고"] ["누적경고"] [date("Y년 m월 d일 H시 i분 s초")] ["경고정도"] = $data[2];
          $this->plugin->wdb [strtolower($data[0])] ["경고"] ["총경고"] += $data[2];
          $this->plugin->save();
          $player->sendMessage($this->plugin->tag() . "정상적으로 누적경고를 지급 했습니다.");
          $date = date("Y년 m월 d일 H시 i분 s초");
          $this->plugin->BandMessage ($data[0],$data[2],$data[1],$date);
          return true;
        } else {
          $player->sendMessage($this->plugin->tag() . "{$data [0]} 의 플레이어의 정보는 존재하지 않습니다.");
          return true;
        }
      }
      if ($id === 101014) {
        if($data !== null){
          $arr = [];
          foreach($this->plugin->getLists($player) as $WarningManager){
            array_push($arr, $WarningManager);
          }
          $this->MyWarning ($player, $arr[$data]);
        }
      }

      if ($id === 101016) {
        if (!isset($data[0])) {
          $player->sendMessage( $this->plugin->tag() . '플레이어 이름을 적어주세요.');
          return;
        }
        if (isset($this->plugin->pldb [strtolower($data[0])])){
          $this->plugin->wdb [strtolower($name)] ["경고"] ["플레이어"] = $data[0];
          $this->plugin->save ();
          $this->PlayerWarning ($player);
          return true;
        } else {
          $player->sendMessage($this->plugin->tag() . "{$data [0]} 의 플레이어의 정보는 존재하지 않습니다.");
          return true;
        }
      }

      if ($id === 101017) {
        if($data !== null){
          $arr = [];
          $playername = $this->plugin->wdb [strtolower($name)] ["경고"] ["플레이어"];
          foreach($this->plugin->getPlayerLists($playername) as $WarningManager){
            array_push($arr, $WarningManager);
          }
          $this->plugin->wdb [strtolower($name)] ["경고"] ["정보"] = $arr[$data];
          $this->plugin->save ();
          $this->PlayerRemoveWarning ($player, $arr[$data]);
        }
      }

      if ($id === 101018) {
        if ($data === 0) {
          if (!$player->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $player->sendMessage($this->plugin->tag() . "권한이 없습니다.");
            return true;
          }
          $playername = $this->plugin->wdb [strtolower($name)] ["경고"] ["플레이어"];
          $time = $this->plugin->wdb [strtolower($name)] ["경고"] ["정보"];
          if (isset($this->plugin->pldb [strtolower($playername)] ["경고"] ["누적경고"] [$time])){
            $count = $this->plugin->pldb [strtolower($playername)] ["경고"] ["누적경고"] [$time] ["경고정도"];
            $this->plugin->wdb [strtolower($playername)] ["경고"] ["총경고"] -= $count;
            unset($this->plugin->pldb [strtolower($playername)] ["경고"] ["누적경고"] [$time]);
            $this->plugin->save ();
            $player->sendMessage($this->plugin->tag() . "{$playername} 의 플레이어의 누적경고 를 제거했습니다.");
            return true;
          } else if (isset($this->plugin->pldb [strtolower($playername)] ["경고"] ["기간경고"] [$time])){
            $count = $this->plugin->pldb [strtolower($playername)] ["경고"] ["기간경고"] [$time] ["경고정도"];
            $this->plugin->wdb [strtolower($playername)] ["경고"] ["총경고"] -= $count;
            unset($this->plugin->pldb [strtolower($playername)] ["경고"] ["기간경고"] [$time]);
            $this->plugin->save ();
            $player->sendMessage($this->plugin->tag() . "{$playername} 의 플레이어의 기간경고 를 제거했습니다.");
            return true;
          }
        }

      }
    }
  }
  public function addList(Player $player)
  {
    $encode = [
      'type' => 'form',
      'title' => '§c【 §fWarningManager §c】',
      'content' => '버튼을 눌러주세요.',
      'buttons' => [
        [
          'text' => '누적경고'
        ],
        [
          'text' => '기간경고'
        ]
      ]
    ];
    $packet = new ModalFormRequestPacket ();
    $packet->formId = 101011;
    $packet->formData = json_encode($encode);
    $player->getNetworkSession()->sendDataPacket($packet);
    return true;
  }
  public function addTimeWarning(Player $player)
  {
    $encode = [
      'type' => 'custom_form',
      'title' => '§c【 §fWarningManager §c】 §7: 기간경고',
      'content' => [
        [
          'type' => 'input',
          'text' => '플레이어 이름 을 적어주세요.'
        ],
        [
          'type' => 'input',
          'text' => '경고 이유 를 적어주세요.'
        ],
        [
          'type' => 'input',
          'text' => '경고 의 정도 를 적어주세요.'
        ],
        [
          'type' => 'input',
          'text' => "경고 의 기간 을 적어주세요.\n경고 적을때에는 뒤에 년,월,일,시,분,초 중 선택해서 뛰어쓰기를 이용해서 적어주세요.\n예시 > 10 년"
        ]
      ]
    ];
    $packet = new ModalFormRequestPacket ();
    $packet->formId = 101012;
    $packet->formData = json_encode($encode);
    $player->getNetworkSession()->sendDataPacket($packet);
    return true;
  }
  public function addCheckWarning(Player $player)
  {
    $encode = [
      'type' => 'custom_form',
      'title' => '§c【 §fWarningManager §c】 §7: 누적경고',
      'content' => [
        [
          'type' => 'input',
          'text' => '플레이어 이름 을 적어주세요.'
        ],
        [
          'type' => 'input',
          'text' => '경고 이유 를 적어주세요.'
        ],
        [
          'type' => 'input',
          'text' => '경고 의 정도 를 적어주세요.'
        ]
      ]
    ];
    $packet = new ModalFormRequestPacket ();
    $packet->formId = 101013;
    $packet->formData = json_encode($encode);
    $player->getNetworkSession()->sendDataPacket($packet);
    return true;
  }
  public function MyWarning(Player $player,$list)
  {
    $name = $player->getName ();
    if (isset($this->plugin->pldb [strtolower($name)] ["경고"] ["누적경고"] [$list])){
      $why = $this->plugin->pldb [strtolower($name)] ["경고"] ["누적경고"] [$list] ["경고이유"];
      $count = $this->plugin->pldb [strtolower($name)] ["경고"] ["누적경고"] [$list] ["경고정도"];
      $message = "§6{$list} §f에 §6누적경고§f가 지급 되었습니다.\n\n§f이 경고가 존재하는 이유 §6> \n§7{$why}\n\n§f경고의 정도 §6> \n§7{$count} §7point\n\n§7해당 §6경고§7는 §6운영진§7의 §6권한§7으로 §6지급 §7되었습니다.\n7해당 §6경고§7의 §6건의사항§7이 있다면 §6문의 §7부탁드립니다.";
    } else if (isset($this->plugin->pldb [strtolower($name)] ["경고"] ["기간경고"] [$list])){
      $why = $this->plugin->pldb [strtolower($name)] ["경고"] ["기간경고"] [$list] ["경고이유"];
      $count = $this->plugin->pldb [strtolower($name)] ["경고"] ["기간경고"] [$list] ["경고정도"];
      $counttime = $this->plugin->pldb [strtolower($name)] ["경고"] ["기간경고"] [$list] ["경고제거기간"];
      $message = "§6{$list} §f에 §6기간경고§f가 지급 되었습니다.\n\n§f이 경고가 존재하는 이유 §6> \n§7{$why}\n\n§f경고의 정도 §6> \n§7{$count} §7point\n\n§f해당 경고가 삭제되는 기간 §6> \n§7{$counttime}\n\n§7해당 §6경고§7는 §6운영진§7의 §6권한§7으로 §6지급 §7되었습니다.\n§7해당 §6경고§7의 §6건의사항§7이 있다면 §6문의 §7부탁드립니다.";
    }

    $encode = [
      'type' => 'form',
      'title' => '§c【 §fWarningManager §c】',
      'content' => "{$message}",
      'buttons' => [
        [
          'text' => '나가기'
        ]
      ]
    ];
    $packet = new ModalFormRequestPacket ();
    $packet->formId = 101015;
    $packet->formData = json_encode($encode);
    $player->getNetworkSession()->sendDataPacket($packet);
    return true;
  }
  public function GoingPlayerWarning(Player $player)
  {
    $encode = [
      'type' => 'custom_form',
      'title' => '§c【 §fWarningManager §c】 §7: 경고제거',
      'content' => [
        [
          'type' => 'input',
          'text' => '플레이어 이름 을 적어주세요.'
        ]
      ]
    ];
    $packet = new ModalFormRequestPacket ();
    $packet->formId = 101016;
    $packet->formData = json_encode($encode);
    $player->getNetworkSession()->sendDataPacket($packet);
    return true;
  }
  public function PlayerWarning(Player $player)
  {
    $tag = "§c【 §fWarningManager §c】";
    $name = $player->getName ();
    $playername = $this->plugin->wdb [strtolower($name)] ["경고"] ["플레이어"];
    $arr = [];
    foreach($this->plugin->getPlayerLists($playername) as $list){
      if (isset($this->plugin->pldb [strtolower($playername)] ["경고"] ["누적경고"] [$list])){
        $count = $this->plugin->pldb [strtolower($playername)] ["경고"] ["누적경고"] [$list] ["경고정도"];
        array_push($arr, array('text' => '- ' . $list . "\n{$count} 개의 누적경고 +"));
      } else if (isset($this->plugin->pldb [strtolower($playername)] ["경고"] ["기간경고"] [$list])){
        $count = $this->plugin->pldb [strtolower($playername)] ["경고"] ["기간경고"] [$list] ["경고정도"];
        array_push($arr, array('text' => '- ' . $list . "\n{$count} 개의 기간경고 +"));
      }
    }
    $encode = [
      'type' => 'form',
      'title' => '§c【 §fWarningManager §c】',
      'content' => '[ 경고목록 ]',
      'buttons' => $arr
    ];
    $packet = new ModalFormRequestPacket();
    $packet->formId = 101017;
    $packet->formData = json_encode($encode);
    $player->getNetworkSession()->sendDataPacket($packet);
    return true;
  }
  public function PlayerRemoveWarning(Player $player,$list)
  {
    $name = $this->plugin->wdb [strtolower($player->getName ())] ["경고"] ["플레이어"];
    if (isset($this->plugin->pldb [strtolower($name)] ["경고"] ["누적경고"] [$list])){
      $why = $this->plugin->pldb [strtolower($name)] ["경고"] ["누적경고"] [$list] ["경고이유"];
      $count = $this->plugin->pldb [strtolower($name)] ["경고"] ["누적경고"] [$list] ["경고정도"];
      $message = "§6{$list} §f에 §6누적경고§f가 지급 되었습니다.\n\n§f이 경고가 존재하는 이유 §6> \n§7{$why}\n\n§f경고의 정도 §6> \n§7{$count} §7point\n\n§7제거하기를 누르면 해당 경고가 제거 됩니다.";
    } else if (isset($this->plugin->pldb [strtolower($name)] ["경고"] ["기간경고"] [$list])){
      $why = $this->plugin->pldb [strtolower($name)] ["경고"] ["기간경고"] [$list] ["경고이유"];
      $count = $this->plugin->pldb [strtolower($name)] ["경고"] ["기간경고"] [$list] ["경고정도"];
      $counttime = $this->plugin->pldb [strtolower($name)] ["경고"] ["기간경고"] [$list] ["경고제거기간"];
      $message = "§6{$list} §f에 §6기간경고§f가 지급 되었습니다.\n\n§f이 경고가 존재하는 이유 §6> \n§7{$why}\n\n§f경고의 정도 §6> \n§7{$count} §7point\n\n§f해당 경고가 삭제되는 기간 §6> \n§7{$counttime}\n\n§7제거하기를 누르면 해당 경고가 제거 됩니다.";
    }

    $encode = [
      'type' => 'form',
      'title' => '§c【 §fWarningManager §c】',
      'content' => "{$message}",
      'buttons' => [
        [
          'text' => '제거하기'
        ],
        [
          'text' => '나가기'
        ]
      ]
    ];
    $packet = new ModalFormRequestPacket ();
    $packet->formId = 101018;
    $packet->formData = json_encode($encode);
    $player->getNetworkSession()->sendDataPacket($packet);
    return true;
  }
}
