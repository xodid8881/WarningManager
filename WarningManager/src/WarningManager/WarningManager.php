<?php
declare(strict_types=1);

namespace WarningManager;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use WarningManager\Commands\OPCommand;
use WarningManager\Commands\PlayerMyCommand;
use pocketmine\utils\Internet;
use pocketmine\scheduler\Task;
use pocketmine\permission\DefaultPermissions;


class WarningManager extends PluginBase
{
  protected $config;
  public $db;
  public $get = [];
  private static $instance = null;

  public static function getInstance(): WarningManager
  {
    return static::$instance;
  }

  public function onLoad():void
  {
    self::$instance = $this;
  }
  public function onEnable():void
  {
    $this->player = new Config ($this->getDataFolder() . "players.yml", Config::YAML);
    $this->pldb = $this->player->getAll();
    $this->warning = new Config ($this->getDataFolder() . "warnings.yml", Config::YAML);
    $this->wdb = $this->warning->getAll();
    $this->getServer()->getCommandMap()->register('WarningManager', new OPCommand($this));
    $this->getServer()->getCommandMap()->register('WarningManager', new PlayerMyCommand($this));
    $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    $this->getScheduler ()->scheduleRepeatingTask ( new CheckWarningTask ( $this, $this->player ), 20 );
  }
  public function tag() : string
  {
    return "§l§b[경고]§r§7 ";
  }
  public function CheckWarning($player){
    $name = $player->getName ();
    if (isset($this->pldb [strtolower($name)])){
      foreach($this->pldb [strtolower($name)] ["경고"] ["기간경고"] as $list => $v){
        $count = (int)$this->pldb [strtolower($name)] ["경고"] ["기간경고"] [$list] ["경고정도"];
        $time = $this->pldb [strtolower($name)] ["경고"] ["기간경고"] [$list] ["경고기간"];
        if ($time < date("YmdHis")){
          unset($this->pldb [strtolower($name)] ["경고"] ["기간경고"] [$list]);
          $this->wdb [strtolower($name)] ["경고"] ["총경고"] -= $count;
          $this->save ();
        }
      }
    }
  }

  public function BandGivePlugin ($name,$point,$message,$date) {
    if (isset($this->pldb [strtolower($name)])){
      $this->pldb [strtolower($name)] ["경고"] ["누적경고"] [date("Y년 m월 d일 H시 i분 s초")] ["경고이유"] = $message;
      $this->pldb [strtolower($name)] ["경고"] ["누적경고"] [date("Y년 m월 d일 H시 i분 s초")] ["경고정도"] = $point;
      $this->wdb [strtolower($name)] ["경고"] ["총경고"] += $$point;
      $this->save();
    }
  }

  public function BandTimeMessage ($name,$point,$message,$date,$countcheck) {
    $date = date("Y년 m월 d일 H시 i분 s초");
    $band = "{$name}님에게 기간경고가 추가 되었습니다\n\n사유 : {$message}\n지급된 기간경고 수 : {$point}\n처리 일자 : {$date}\n\n경고 제거 기간 {$countcheck}";
    if($this->wdb [strtolower($name)] ["경고"] ["총경고"] >= 100){
      $band .= "\n\n경고 수 100 (을)를 초과하여, 이용이 제한됩니다";
    }
    Internet::postURL("https://openapi.band.us/v2.2/band/post/create?access_token=". "#token", ['access_token' => "#token", 'band_key' => "#key", 'content' => $band]);
    return true;
  }

  public function BandMessage ($name,$point,$message,$date) {
    $band = "{$name}님에게 누적경고가 추가 되었습니다\n\n사유 : {$message}\n지급된 누적경고 수 : {$point}\n처리 일자 : {$date}";
    if($this->wdb [strtolower($name)] ["경고"] ["총경고"] >= 100){
      $band .= "\n\n경고 수 100 (을)를 초과하여, 이용이 제한됩니다";
    }
    Internet::postURL("https://openapi.band.us/v2.2/band/post/create?access_token=". "#token", ['access_token' => "#token", 'band_key' => "#key", 'content' => $band]);
    return true;
  }

  public function getLists($player) : array {
    $name = $player->getName ();
    $arr = [];
    foreach($this->pldb [strtolower($name)] ["경고"] as $list => $v){
      foreach($this->pldb [strtolower($name)] ["경고"] ["{$list}"] as $WarningManager => $v){
        array_push($arr, $WarningManager);
      }
    }
    return $arr;
  }

  public function getPlayerLists($name) : array {
    $arr = [];
    foreach($this->pldb [strtolower($name)] ["경고"] as $list => $v){
      foreach($this->pldb [strtolower($name)] ["경고"] ["{$list}"] as $WarningManager => $v){
        array_push($arr, $WarningManager);
      }
    }
    return $arr;
  }

  public function onDisable():void
  {
    $this->save();
  }
  public function save():void
  {
    $this->player->setAll($this->pldb);
    $this->player->save();
    $this->warning->setAll($this->wdb);
    $this->warning->save();
  }
}

class CheckWarningTask extends Task {
  protected $owner;
  protected $player;
  protected $pldb;
  public function __construct(WarningManager $owner, Config $player) {
    $this->owner = $owner;
    $this->player = $player;
    $this->pldb = $this->player->getAll ();
  }
  public function onRun():void {
    foreach ( $this->owner->getServer ()->getOnlinePlayers () as $player ) {
      $this->owner->CheckWarning ( $player );
    }
  }
}
