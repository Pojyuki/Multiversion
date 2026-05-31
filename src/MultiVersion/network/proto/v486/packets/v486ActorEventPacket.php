<?php

namespace MultiVersion\network\proto\v486\packets;

use MultiVersion\network\proto\v486\v486PacketSerializer;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\ActorEventPacket;

class v486ActorEventPacket extends ActorEventPacket{

	public static function fromLatest(ActorEventPacket $packet, ?int $eventData = null) : self{
		$result = new self;
		$result->actorRuntimeId = $packet->actorRuntimeId;
		$result->eventId = $packet->eventId;
		$result->eventData = $eventData ?? $packet->eventData;
		if(property_exists($result, "firePosition")){
			$result->firePosition = null;
		}
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, ?int $protocolId = null) : void{
		$in = v486PacketSerializer::reader($in, $protocolId);
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->eventId = $in->getByte();
		$this->eventData = $in->getVarInt();
		if(property_exists($this, "firePosition")){
			$this->firePosition = null;
		}
	}

	protected function encodePayload(ByteBufferWriter $out, ?int $protocolId = null) : void{
		$out = v486PacketSerializer::writer($out, $protocolId);
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putByte($this->eventId);
		$out->putVarInt($this->eventData);
	}
}
