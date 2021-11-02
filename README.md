<div align="center">
<h1>NoAdvertisings| v0.0.3<h1>
<p>Block ads for other servers.</p>
</div>
 ---
[![](https://poggit.pmmp.io/shield.state/NoAdvertisings)](https://poggit.pmmp.io/p/NoAdvertisings) [![](https://poggit.pmmp.io/shield.api/NoAdvertisings)](https://poggit.pmmp.io/p/NoAdvertisings) [![](https://poggit.pmmp.io/shield.dl.total/NoAdvertisings)](https://poggit.pmmp.io/p/NoAdvertisings) [![](https://poggit.pmmp.io/shield.dl/NoAdvertisings)](https://poggit.pmmp.io/p/NoAdvertisings)
  
## Features
- Block server ads.
- Easy to setup.
- Block server address ads when chatting, using commands, using sign.

## All NoAdvertisings Commands:

| **Command** | **Description** |
| --- | --- |
| **/noadvertisings** | **NoAdvertisings Control** |
- Aliases:
  - /na
  - /noads

## 📃  Permissions:

- You can use permission `noadvertisings.blocked` for command /noadvertisings
## Configs
 ```
 ---
# Main config for NoAdvertisings
# Message when players advertise
Message: "Please don't ads."
# Messages when adding domain
Domain-exists: "That domain already exist!."
Domain-added-successfully: "Successfully added {domain} into config."
# Messages when removing domain
Domain-not-exists: "That domain not exist!."
Domain-removed-successfully: "Successfully removed {domain} from config."
# The domains that allowed to use
allowed.domain:
  - "youripserver.net"
  - "yourip.net"
# Blocked domain names
domain:
  - ".net"
  - ".com"
  - ".tk"
  - ".ddns.net"
# Lines that will change if player advertise on sign
lines:
  - '============='
  - 'No Advertising!'
  - '============='
  - ''
# The command that will protected from advertising
blocked.cmd:
  - "/me"
  - "/tell"
  - "/w"
...
 ```
## History
 ```
---
#This is where the system will save the ads player history.
'Wed 18/08/2021 03:57:24(AM) : Console': example.net.
'Wed 18/08/2021 03:57:31(AM) : Console': play.example.org
...
 ```
## Project Infomation

| Plugin Version | Pocketmine API | PHP Version | Plugin Status |
|---|---|---|---|
| 0.0.3 | 3.x.x | 7.4 | Completed |
 
