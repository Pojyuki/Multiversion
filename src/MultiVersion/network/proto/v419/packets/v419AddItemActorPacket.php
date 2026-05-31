<?php

namespace MultiVersion\network\proto\v419\packets;

use pocketmine\network\mcpe\protocol\AddItemActorPacket;

class v419AddItemActorPacket extends AddItemActorPacket{

	public static function fromLatest(AddItemActorPacket $pk) : self{
		$result = new self();
		$result->actorUniqueId = $pk->actorUniqueId;
		$result->actorRuntimeId = $pk->actorRuntimeId;
		$result->item = $pk->item;
		$result->position = $pk->position;
		$result->motion = $pk->motion;
		$result->metadata = $pk->metadata;
		$result->isFromFishing = $pk->isFromFishing;
		return $result;
	}

	protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{
		$in = \MultiVersion\network\proto\v419\v419PacketSerializer::reader($in, $protocolId);
		$this->actorUniqueId = $in->getActorUniqueId();
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->item = $in->getItemStackWrapper();
		$this->position = $in->getVector3();
		$this->motion = $in->getVector3();
		$this->metadata = $in->getEntityMetadata();
		$this->isFromFishing = $in->getBool();
	}

	protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{
		$out = \MultiVersion\network\proto\v419\v419PacketSerializer::writer($out, $protocolId);
		$out->putActorUniqueId($this->actorUniqueId);
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putItemStackWrapper($this->item);
		$out->putVector3($this->position);
		$out->putVector3Nullable($this->motion);
		$out->putEntityMetadata($this->metadata);
		$out->putBool($this->isFromFishing);
	}
}

