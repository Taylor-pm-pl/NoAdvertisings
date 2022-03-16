<?php

declare(strict_types=1);

namespace YTBJero\NoAdvertisings;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\command\{Command, CommandSender};
use pocketmine\block\utils\SignText;
use pocketmine\block\WallSign;
use function filter_var;

class Main extends PluginBase implements Listener{

    public $configversion = "0.0.4";
    /** @var Config $history */
    public $history;

    public function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveDefaultConfig();
        $this->history = new Config($this->getDataFolder()."history.yml", Config::YAML);
        if($this->getConfig()->get("Update-notice")){
            $this->checkUpdate();
        }
        $this->checkConfigUpdate();
    }

    /**
     * @param bool $isRetry
     */
    public function checkUpdate(bool $isRetry = false): void 
    {
        $this->getServer()->getAsyncPool()->submitTask(new CheckUpdateTask($this->getDescription()->getName(), $this->getDescription()->getVersion()));
    }

    /**
     * @return void
     */
    private function checkConfigUpdate(): void{
        $updateconfig = false;

        if(!$this->getConfig()->exists("config-version")){
            $updateconfig = true;
        }

        if($this->getConfig()->get("config-version") !== $this->configversion){
            $updateconfig = true;
        }

        if($updateconfig){
            @unlink($this->getDataFolder()."config.yml");
            $this->saveDefaultConfig();
        }
    }


    /**
     * @param  PlayerChatEvent $event
     */
    public function onChat(PlayerChatEvent $event) :void
    {
        $player = $event->getPlayer();
        $name = $player->getName();
        $msg = $event->getMessage();
        $domain = $this->getDomain();
        $allowed = $this->getAllowedDomain();
        foreach($allowed as $a){
            if(stripos($msg, $a) !== false){
                return;
            }
        }
        foreach($domain as $d){
            if((stripos($msg, $d) !== false) || filter_var($msg, FILTER_VALIDATE_IP) || (preg_match("([a-zA-Z0-9]+ *+[(\.|,)]+ *+[^\s]{2,}|\.[a-zA-Z0-9]+\.[^\s]{2,})", $msg))){
                    $event->cancel();
                    $player->sendMessage($this->getConfig()->get("Message"));
                    $time = date("D d/m/Y H:i:s(A)");
                    $this->history->set($time . ' : ' . $name, $msg);
                    $this->history->save();
                       
            }
        }
    }

    /**
     * @param  SignChangeEvent $event
     */
     public function onSign(SignChangeEvent $event): void 
     {
            $player = $event->getPlayer();
            $name = $player->getName();
			$sign = $this->getSignLines();
			$oldText = $event->getOldText();
			$newText = $event->getNewText();
            $lines = $event->getSign()->getText()->getLines();
            foreach($lines as $line){
                foreach($this->getAllowedDomain() as $a){
                    if(stripos($line, $a) !== false){
                        return;
                    }
                }
                foreach($this->getDomain() as $d){
                    if(stripos($line, $d) !== false || filter_var($line, FILTER_VALIDATE_IP)) {
                        for ($i = 0; $i < SignText::LINE_COUNT; $i++) {
                            $player->sendMessage($this->getConfig()->get("Message"));
                            $shopSignText = new SignText([
							isset($sign[0]) ? $sign[0] : '',
							isset($sign[1]) ? $sign[1] : '',
							isset($sign[2]) ? $sign[2] : '',
							isset($sign[3]) ? $sign[3] : ''
							]);
							$event->setNewText($shopSignText);
                $time = date("D d/m/Y H:i:s(A)");
                    $this->history->set($time . ' : ' . $name, $line);
                    $this->history->save();
                        }
                    }
                }
            }
        }

    /**
     * @param  PlayerCommandPreprocessEvent $event
     */
    public function onCmd(PlayerCommandPreprocessEvent $event){
        $msg = explode(' ', $event->getMessage());
        $cmd = array_shift($msg);
        $player = $event->getPlayer();
        $m = implode(' ', $msg);
        $name = $player->getName();
        foreach ($this->getAllowedDomain() as $a) {
            if (stripos($m, $a) !== false) {
                return;
            }
        }
        if(in_array($cmd, $this->getBlockedCmd())) {
            foreach ($this->getDomain() as $d) {
                if (stripos($m, $d) !== false  || filter_var($m, FILTER_VALIDATE_IP)) {
                    $event->cancel();
                    $player->sendMessage($this->getConfig()->get("Message"));
                    $time = date("D d/m/Y H:i:s(A)");
                    $this->history->set($time . ' : ' . $name, $m);
                    $this->history->save();
                }
            }
        }
    }

    /**
     * @param  CommandSender $sender 
     * @param  Command       $command
     * @param  String        $label  
     * @param  Array         $args   
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $command, String $label, Array $args): bool 
    {
        if($command->getName() == "noadvertisings"){
            if(!$sender->hasPermission("noadvertisings.blocked")){
                $sender->sendMessage("You don't have permission to use this command.");
                return false;
            }
                    if (isset($args[0])) {
						if($args[0] == "add"){
							if(isset($args[1])){
								return $this->addDomain($sender, $args[1]);
                            } else{
                                $sender->sendMessage("/noadvertisings add <domain>.");
                                return false;
                            }
						}
                        if($args[0] == "remove"){
							if(isset($args[1])){
								return $this->removeDomain($sender, $args[1]);
                            } else{
                                $sender->sendMessage("/noadvertisings remove <domain>.");
                                return false;
                            }
                        }
                        if($args[0] == "list"){
							return $this->listDomain($sender);
                        }
                    } else{
                        $sender->sendMessage("/noadvertisings <add/remove/list>");
                        return false;
                    }
                }
                return true;
        }

    /**
     * @return array
     */
    public function getDomain()
    {
    $domain = (array) $this->getConfig()->get("domain");
    return $domain;
    }

    /**
     * @return array
     */
    public function getAllowedDomain()
    {
        $allowed = (array) $this->getConfig()->get("allowed.domain");
        return $allowed;
    }

    /**
     * @param Player $player
     * @param $name
     * @return bool
     * @throws \JsonException
     */
    public function addDomain(Player $player, $name)
    {
    $domain = $this->getDomain();
    if(in_array($name, $domain)){
        $player->sendMessage($this->getConfig()->get("Domain-exists"));
        return false;
    }
    $domain[] = $name;
    $this->getConfig()->set("domain", $domain);
    $this->getConfig()->save();
    $m = $this->getConfig()->get("Domain-added-successfully");
    $m = str_replace(['{domain}'], [$name], $m);
    $player->sendMessage($m);
    return true;
    }

    /**
     * @param Player $player
     * @param $name
     * @return bool
     * @throws \JsonException
     */
    public function removeDomain(Player $player, $name){
        $domain = $this->getDomain();
        $key = array_search($name, $domain);
        if($key === false){
            $player->sendMessage($this->getConfig()->get("Domain-not-exists"));
            return false;
        }
        unset($domain[$key]);
        $this->getConfig()->set("domain", array_values($domain));
        $this->getConfig()->save();
        $m = $this->getConfig()->get("Domain-removed-successfully");
        $m = str_replace(['{domain}'], [$name], $m);
        $player->sendMessage($m);
        return true;
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function listDomain(Player $player){
    $domain = implode("\n" ."- ", $this->getDomain());
    $player->sendMessage("Available domain:");
    $player->sendMessage("- " . $domain);
    return true;
    }

    /**
     * @return array
     */
	public function getSignLines()
    {
    return (array) $this->getConfig()->get('lines');
    }

    /**
     * @return array
     */
    public function getBlockedCmd()
    {
    return (array) $this->getConfig()->get('blocked.cmd');
    }
}
