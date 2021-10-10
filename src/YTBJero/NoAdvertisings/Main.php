<?php

declare(strict_types=1);

namespace YTBJero\NoAdvertisings;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\{Command, CommandSender};

class Main extends PluginBase implements Listener{

    public function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->saveDefaultConfig();
    }

    /**
     * @param  PlayerChatEvent $event
     */
    public function onChat(PlayerChatEvent $event) :void
    {
        $player = $event->getPlayer();
        $msg = $event->getMessage();
        $domain = $this->getDomain();
        $allowed = $this->getAllowedDomain();
        foreach($allowed as $a){
            if(stripos($msg, $a) !== false){
                return;
            }
        }
        foreach($domain as $d){
            if((stripos($msg, $d) !== false) || (preg_match("([a-zA-Z0-9]+ *+[(\.|,)]+ *+[^\s]{2,}|\.[a-zA-Z0-9]+\.[^\s]{2,})", $msg))){
                        $event->setCancelled(true);
                       
        }
    }
     $player->sendMessage($this->getConfig()->get("Message"));
    }

    /**
     * @param  SignChangeEvent $event
     */
     public function onSign(SignChangeEvent $event): void 
     {
            $lines = $event->getLines();
            $player = $event->getPlayer();
            $sign = $this->getSignLines();
            foreach($lines as $line){
                foreach($this->getAllowedDomain() as $a){
                    if(stripos($line, $a) !== false){
                        return;
                    }
                }
                foreach($this->getDomain() as $d){
                    if(stripos($line, $d) !== false) {
                        for ($i = 0; $i <= 3; $i++) {
                            $event->setLine($i, $sign[$i]);
                $player->sendMessage($this->getConfig()->get("Message"));
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
        foreach ($this->getAllowedDomain() as $a) {
            if (stripos($m, $a) !== false) {
                return;
            }
        }
        if(in_array($cmd, $this->getBlockedCmd())) {
            foreach ($this->getDomain() as $d) {
                if (stripos($m, $d) !== false) {
                    $event->setCancelled(true);
                    $player->sendMessage($this->getConfig()->get("Message"));
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
                    if (isset($args[0])) {
                        switch ($args[0]) {
                            case "add":
                                if(isset($args[1])){
                                    return $this->addDomain($sender, $args[1]);
                                } else{
                                    $sender->sendMessage("/noadvertisings add <domain>.");
                                    return false;
                                }
                                break;
                            case "remove":
                                if(isset($args[1])){
                                    return $this->removeDomain($sender, $args[1]);
                                }
                                else{
                                    $sender->sendMessage("/noadvertisings remove <domain>.");
                                    return false;
                                }
                                break;
                            case "list":
                                return $this->listDomain($sender);
                                break;
                        }
                    }
                    else{
                        $sender->sendMessage("/noadvertisings <add/remove/list>");
                        return false;
                    }
                }
                return true;
        }

    public function getDomain()
    {
    $domain = (array) $this->getConfig()->get("domain");
    return $domain;
    } 

    public function getAllowedDomain()
    {
        $allowed = (array) $this->getConfig()->get("allowed.domain");
        return $allowed;
    }

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

    public function listDomain(Player $player){
    $domain = implode("\n" ."- ", $this->getDomain());
    $player->sendMessage("Available domain:");
    $player->sendMessage("- " . $domain);
    return true;
    }

    public function getSignLines()
    {
    return (array) $this->getConfig()->get('lines');
    }

    public function getBlockedCmd()
    {
    return (array) $this->getConfig()->get('blocked.cmd');
    }
}
