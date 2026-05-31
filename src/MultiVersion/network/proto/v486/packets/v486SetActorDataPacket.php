<?php

namespace MultiVersion\network\proto\v486\packets;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;

class v486SetActorDataPacket extends SetActorDataPacket{

	public static function fromLatest(SetActorDataPacket $pk) : self{
		$npk = new self();
		$npk->actorRuntimeId = $pk->actorRuntimeId;
		$npk->metadata = $pk->metadata;
		$npk->tick = $pk->tick;
		return $npk;
	}

	protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{

		$in = \MultiVersion\network\proto\v486\v486PacketSerializer::reader($in, $protocolId);
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->metadata = $in->getEntityMetadata();
		$this->tick = $in->getUnsignedVarLong();
	}

	protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{

		$out = \MultiVersion\network\proto\v486\v486PacketSerializer::writer($out, $protocolId);
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putEntityMetadata($this->metadata);
		$out->putUnsignedVarLong($this->tick);
	}
}


