<?php

namespace MultiVersion\network\proto\v419\packets;

use MultiVersion\network\proto\v419\v419PacketSerializer;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;

class v419UpdateBlockPacket extends UpdateBlockPacket{

	public static function fromLatest(UpdateBlockPacket $packet) : self{
		$result = new self;
		$result->blockPosition = $packet->blockPosition;
		$result->blockRuntimeId = $packet->blockRuntimeId;
		$result->flags = $packet->flags;
		$result->dataLayerId = $packet->dataLayerId;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, ?int $protocolId = null) : void{
		$in = v419PacketSerializer::reader($in, $protocolId);
		$this->blockPosition = $in->getBlockPosition();
		$this->blockRuntimeId = $in->getUnsignedVarInt();
		$this->flags = $in->getUnsignedVarInt();
		$this->dataLayerId = $in->getUnsignedVarInt();
	}

	protected function encodePayload(ByteBufferWriter $out, ?int $protocolId = null) : void{
		$out = v419PacketSerializer::writer($out, $protocolId);
		$out->putBlockPosition($this->blockPosition);
		$out->putUnsignedVarInt($this->blockRuntimeId);
		$out->putUnsignedVarInt($this->flags);
		$out->putUnsignedVarInt($this->dataLayerId);
	}
}
