<?php

declare(strict_types=1);

namespace MultiVersion\network\proto\v419\packets;

use MultiVersion\network\proto\utils\ReflectionUtils;
use pocketmine\network\mcpe\protocol\AddVolumeEntityPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;

class v419AddVolumeEntityPacket extends AddVolumeEntityPacket{

	public static function fromLatest(AddVolumeEntityPacket $pk) : self{
		$npk = new self();
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $npk, "entityNetId", $pk->getEntityNetId());
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $npk, "data", $pk->getData());
		return $npk;
	}

	protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{

		$in = \MultiVersion\network\proto\v419\v419PacketSerializer::reader($in, $protocolId);
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $this, "entityNetId", $in->getUnsignedVarInt());
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $this, "data", new CacheableNbt($in->getNbtCompoundRoot()));
	}

	protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{

		$out = \MultiVersion\network\proto\v419\v419PacketSerializer::writer($out, $protocolId);
		$out->putUnsignedVarInt($this->getEntityNetId());
		$out->put($this->getData()->getEncodedNbt());
	}

}

