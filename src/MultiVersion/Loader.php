<?php

declare(strict_types=1);

namespace MultiVersion;

use MultiVersion\network\MVRakLibInterface;
use MultiVersion\network\proto\login\LoginPacket;
use pocketmine\event\EventPriority;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\NetworkInterfaceRegisterEvent;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\PacketViolationWarningPacket;
use pocketmine\network\mcpe\raklib\RakLibInterface;
use pocketmine\network\NetworkInterfaceStartException;
use pocketmine\network\query\DedicatedQueryNetworkInterface;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use ReflectionException;

final class Loader extends PluginBase{
	use SingletonTrait;

	private const PACKET_VIOLATION_WARNING_TYPE = [
		PacketViolationWarningPacket::TYPE_MALFORMED => "MALFORMED",
	];
	private const PACKET_VIOLATION_WARNING_SEVERITY = [
		PacketViolationWarningPacket::SEVERITY_WARNING => "WARNING",
		PacketViolationWarningPacket::SEVERITY_FINAL_WARNING => "FINAL WARNING",
		PacketViolationWarningPacket::SEVERITY_TERMINATING_CONNECTION => "TERMINATION",
	];

	public static function getPluginResourcePath() : string{
		return dirname(__DIR__, 2) . "/resources";
	}

	protected function onLoad() : void{
		self::setInstance($this);
	}

	/**
	 * @throws ReflectionException
	 */
	protected function onEnable() : void{
		$server = $this->getServer();

		$regInterface = function(Server $server, bool $ipv6) : void{
			$ip = $ipv6 ? (method_exists($server, "getIpV6") ? $server->getIpV6() : "::") : $server->getIp();
			$port = $ipv6 ? (method_exists($server, "getPortV6") ? $server->getPortV6() : $server->getPort()) : $server->getPort();
			try{
				$server->getNetwork()->registerInterface(new MVRakLibInterface($server, $ip, $port, $ipv6));
			}catch(NetworkInterfaceStartException $e){
				if($ipv6){
					$this->getLogger()->warning("IPv6 listener failed to start on [$ip]:$port: " . $e->getMessage());
					return;
				}
				throw $e;
			}
		};

		($regInterface)($server, false);
		if($server->getConfigGroup()->getConfigBool("enable-ipv6", true)){
			($regInterface)($server, true);
		}

        PacketPool::getInstance()->registerPacket(new LoginPacket());
		$server->getPluginManager()->registerEvent(NetworkInterfaceRegisterEvent::class, function(NetworkInterfaceRegisterEvent $event) : void{
			$interface = $event->getInterface();
			if($interface instanceof MVRakLibInterface || (!$interface instanceof RakLibInterface && !$interface instanceof DedicatedQueryNetworkInterface)){
				return;
			}
			$this->getLogger()->debug("Prevented network interface " . get_class($interface) . " from being registered");
			$event->cancel();
		}, EventPriority::NORMAL, $this);
        $server->getPluginManager()->registerEvent(DataPacketReceiveEvent::class, function(DataPacketReceiveEvent $event) : void{
            $packet = $event->getPacket();
            if($packet instanceof PacketViolationWarningPacket){
                $this->getLogger()->warning("Received " . (self::PACKET_VIOLATION_WARNING_TYPE[$packet->getType()] ?? "UNKNOWN [{$packet->getType()}]") . " Packet Violation (" . self::PACKET_VIOLATION_WARNING_SEVERITY[$packet->getSeverity()] . ") from {$event->getOrigin()->getIp()} message: '{$packet->getName()}' Packet ID: 0x" . str_pad(dechex($packet->getPacketId()), 2, "0", STR_PAD_LEFT));
            }
        }, EventPriority::NORMAL, $this);
	}
}
