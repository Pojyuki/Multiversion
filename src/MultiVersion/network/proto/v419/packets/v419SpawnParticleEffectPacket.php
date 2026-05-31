<?php

namespace MultiVersion\network\proto\v419\packets;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;

class v419SpawnParticleEffectPacket extends SpawnParticleEffectPacket{

	public static function fromLatest(SpawnParticleEffectPacket $packet) : self{
		$npk = new self();
		$npk->dimensionId = $packet->dimensionId;
		$npk->actorUniqueId = $packet->actorUniqueId;
		$npk->position = $packet->position;
		$npk->particleName = $packet->particleName;
		return $npk;
	}

	protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{

		$in = \MultiVersion\network\proto\v419\v419PacketSerializer::reader($in, $protocolId);
		$this->dimensionId = $in->getByte();
		$this->actorUniqueId = $in->getActorUniqueId();
		$this->position = $in->getVector3();
		$this->particleName = $in->getString();
	}

	protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{

		$out = \MultiVersion\network\proto\v419\v419PacketSerializer::writer($out, $protocolId);
		$out->putByte($this->dimensionId);
		$out->putActorUniqueId($this->actorUniqueId);
		$out->putVector3($this->position);
		$out->putString($this->particleName);
	}
}

