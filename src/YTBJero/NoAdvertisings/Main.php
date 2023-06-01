<?php

declare(strict_types=1);

namespace YTBJero\NoAdvertisings;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\command\{Command, CommandSender};
use pocketmine\block\utils\SignText;
use function filter_var;

class Main extends PluginBase implements Listener
{
    public string $configversion = "0.0.4";
    /** @var Config $history */
    public Config $history;

    public function onEnable(): void
    {
        $this->getServer()
            ->getPluginManager()
            ->registerEvents($this, $this);
        $this->saveDefaultConfig();
        $this->history = new Config(
            $this->getDataFolder() . "history.yml",
            Config::YAML
        );
        if ($this->getConfig()->get("Update-notice")) {
            $this->checkUpdate();
        }
        $this->checkConfigUpdate();
    }

    /**
     * @param bool $isRetry
     */
    public function checkUpdate(bool $isRetry = false): void
    {
        $this->getServer()
            ->getAsyncPool()
            ->submitTask(
                new CheckUpdateTask(
                    $this->getDescription()->getName(),
                    $this->getDescription()->getVersion()
                )
            );
    }

    private function checkConfigUpdate(): void
    {
        $updateconfig = false;

        if (!$this->getConfig()->exists("config-version")) {
            $updateconfig = true;
        }

        if (
            $this->getConfig()->get("config-version") !== $this->configversion
        ) {
            $updateconfig = true;
        }

        if ($updateconfig) {
            @unlink($this->getDataFolder() . "config.yml");
            $this->saveDefaultConfig();
        }
    }

    /**
     * @param PlayerChatEvent $event
     * @throws \JsonException
     */
    public function onChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();
        $name = $player->getName();
        $msg = $event->getMessage();
        $domain = $this->getDomain();
        $allowed = $this->getAllowedDomain();
        foreach ($allowed as $a) {
            if (stripos($msg, $a) !== false) {
                return;
            }
        }
        foreach ($domain as $d) {
            if (
                stripos($msg, $d) !== false ||
                filter_var($msg, FILTER_VALIDATE_IP) ||
                preg_match(
                    "([a-zA-Z0-9]+ *+[(\.|,)]+ *+[^\s]{2,}|\.[a-zA-Z0-9]+\.[^\s]{2,})",
                    $msg
                )
            ) {
                $event->cancel();
                $player->sendMessage($this->getConfig()->get("Message"));
                $time = date("D d/m/Y H:i:s(A)");
                $this->history->set($time . " : " . $name, $msg);
                $this->history->save();
            }
        }
    }

    /**
     * @param SignChangeEvent $event
     * @throws \JsonException
     */
    public function onSign(SignChangeEvent $event): void
    {
        $player = $event->getPlayer();
        $name = $player->getName();
        $sign = $this->getSignLines();
        $lines = $event
            ->getSign()
            ->getText()
            ->getLines();
        foreach ($lines as $line) {
            foreach ($this->getAllowedDomain() as $a) {
                if (stripos($line, $a) !== false) {
                    return;
                }
            }
            foreach ($this->getDomain() as $d) {
                if (
                    stripos($line, $d) !== false ||
                    filter_var($line, FILTER_VALIDATE_IP)
                ) {
                    for ($i = 0; $i < SignText::LINE_COUNT; $i++) {
                        $player->sendMessage(
                            $this->getConfig()->get("Message")
                        );
                        $signText = new SignText([
                            $sign[0] ?? "",
                            $sign[1] ?? "",
                            $sign[2] ?? "",
                            $sign[3] ?? "",
                        ]);
                        $event->setNewText($signText);
                        $time = date("D d/m/Y H:i:s(A)");
                        $this->history->set($time . " : " . $name, $line);
                        $this->history->save();
                    }
                }
            }
        }
    }

    /**
     * @param CommandEvent $event
     * @throws \JsonException
     */
    public function onCmd(CommandEvent $event): void
    {
        $msg = explode(" ", $event->getCommand());
        $cmd = array_shift($msg);
        $player = $event->getSender();
        $m = implode(" ", $msg);
        $name = $player->getName();
        foreach ($this->getAllowedDomain() as $a) {
            if (stripos($m, $a) !== false) {
                return;
            }
        }
        if (in_array($cmd, $this->getBlockedCmd())) {
            foreach ($this->getDomain() as $d) {
                if (
                    stripos($m, $d) !== false ||
                    filter_var($m, FILTER_VALIDATE_IP)
                ) {
                    $event->cancel();
                    $player->sendMessage($this->getConfig()->get("Message"));
                    $time = date("D d/m/Y H:i:s(A)");
                    $this->history->set($time . " : " . $name, $m);
                    $this->history->save();
                }
            }
        }
    }

    /**
     * @param CommandSender $sender
     * @param Command $command
     * @param String $label
     * @param array $args
     * @return bool
     * @throws \JsonException
     */
    public function onCommand(
        CommandSender $sender,
        Command $command,
        string $label,
        array $args
    ): bool {
        if ($command->getName() == "noadvertisings") {
            if (!$sender->hasPermission("noadvertisings.blocked")) {
                $sender->sendMessage(
                    "You don't have permission to use this command."
                );
                return false;
            }
            if (isset($args[0])) {
                if ($args[0] == "add") {
                    if (isset($args[1])) {
                        return $this->addDomain($sender, $args[1]);
                    } else {
                        $sender->sendMessage("/noadvertisings add <domain>.");
                        return false;
                    }
                }
                if ($args[0] == "remove") {
                    if (isset($args[1])) {
                        return $this->removeDomain($sender, $args[1]);
                    } else {
                        $sender->sendMessage(
                            "/noadvertisings remove <domain>."
                        );
                        return false;
                    }
                }
                if ($args[0] == "list") {
                    return $this->listDomain($sender);
                }
            } else {
                $sender->sendMessage("/noadvertisings <add/remove/list>");
                return false;
            }
        }
        return true;
    }

    /**
     * @return array
     */
    public function getDomain(): array
    {
        $domain = (array) $this->getConfig()->get("domain");
        return $domain;
    }

    /**
     * @return array
     */
    public function getAllowedDomain(): array
    {
        return (array) $this->getConfig()->get("allowed.domain");
    }

    /**
     * @throws \JsonException
     */
    public function addDomain(Player|CommandSender $player, $name): bool
    {
        $domain = $this->getDomain();
        if (in_array($name, $domain)) {
            $player->sendMessage($this->getConfig()->get("Domain-exists"));
            return false;
        }
        $domain[] = $name;
        $this->getConfig()->set("domain", $domain);
        $this->getConfig()->save();
        $m = $this->getConfig()->get("Domain-added-successfully");
        $m = str_replace(["{domain}"], [$name], $m);
        $player->sendMessage($m);
        return true;
    }

    public function removeDomain(Player|CommandSender $player, $name): bool
    {
        $domain = $this->getDomain();
        $key = array_search($name, $domain);
        if ($key === false) {
            $player->sendMessage($this->getConfig()->get("Domain-not-exists"));
            return false;
        }
        unset($domain[$key]);
        $this->getConfig()->set("domain", array_values($domain));
        $this->getConfig()->save();
        $m = $this->getConfig()->get("Domain-removed-successfully");
        $m = str_replace(["{domain}"], [$name], $m);
        $player->sendMessage($m);
        return true;
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function listDomain(Player|CommandSender $player): bool
    {
        $domain = implode("\n" . "- ", $this->getDomain());
        $player->sendMessage("Available domain:");
        $player->sendMessage("- " . $domain);
        return true;
    }

    /**
     * @return array
     */
    public function getSignLines(): array
    {
        return (array) $this->getConfig()->get("lines");
    }

    /**
     * @return array
     */
    public function getBlockedCmd(): array
    {
        return (array) $this->getConfig()->get("blocked.cmd");
    }
}
