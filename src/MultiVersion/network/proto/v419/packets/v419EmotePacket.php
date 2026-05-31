<?php

namespace MultiVersion\network\proto\v419\packets;

use MultiVersion\network\proto\utils\ReflectionUtils;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use ReflectionException;

class v419EmotePacket extends EmotePacket{

	/**
	 * @throws ReflectionException
	 */
	public static function fromLatest(EmotePacket $pk) : self{
		$npk = new self();
		ReflectionUtils::setProperty(EmotePacket::class, $npk, "actorRuntimeId", $pk->getActorRuntimeId());
		ReflectionUtils::setProperty(EmotePacket::class, $npk, "emoteId", $pk->getEmoteId());
		ReflectionUtils::setProperty(EmotePacket::class, $npk, "flags", $pk->getFlags());
		return $npk;
	}

	/**
	 * @throws ReflectionException
	 */
	protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{
		$in = \MultiVersion\network\proto\v419\v419PacketSerializer::reader($in, $protocolId);
		ReflectionUtils::setProperty(EmotePacket::class, $this, "actorRuntimeId", $in->getActorRuntimeId());
		ReflectionUtils::setProperty(EmotePacket::class, $this, "emoteId", $in->getString());
		ReflectionUtils::setProperty(EmotePacket::class, $this, "flags", $in->getByte());
	}

	protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{

		$out = \MultiVersion\network\proto\v419\v419PacketSerializer::writer($out, $protocolId);
		$out->putActorRuntimeId($this->getActorRuntimeId());
		$out->putString($this->getEmoteId());
		$out->putByte($this->getFlags());
	}
}

