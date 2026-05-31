<?php

namespace MultiVersion\network\proto\v486\packets;

use MultiVersion\network\proto\utils\ReflectionUtils;
use pocketmine\network\mcpe\protocol\NetworkSettingsPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use ReflectionException;

class v486NetworkSettingsPacket extends NetworkSettingsPacket{

	/**
	 * @throws ReflectionException
	 */
	public static function fromLatest(NetworkSettingsPacket $packet) : self{
		$npk = new self();
		ReflectionUtils::setProperty(NetworkSettingsPacket::class, $npk, "compressionThreshold", $packet->getCompressionThreshold());
		return $npk;
	}

	/**
	 * @throws ReflectionException
	 */
	protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{
		$in = \MultiVersion\network\proto\v486\v486PacketSerializer::reader($in, $protocolId);
		ReflectionUtils::setProperty(NetworkSettingsPacket::class, $this, "compressionThreshold", $in->getLShort());
	}

	protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{

		$out = \MultiVersion\network\proto\v486\v486PacketSerializer::writer($out, $protocolId);
		$out->putLShort($this->getCompressionThreshold());
	}
}


