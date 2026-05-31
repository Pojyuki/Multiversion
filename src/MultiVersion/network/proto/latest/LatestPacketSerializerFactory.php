<?php

namespace MultiVersion\network\proto\latest;

use MultiVersion\network\proto\chunk\serializer\MVChunkSerializer;
use MultiVersion\network\proto\MVPacketSerializer;
use MultiVersion\network\proto\PacketSerializerFactory;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

class LatestPacketSerializerFactory implements PacketSerializerFactory{

	public function __construct(
		private MVChunkSerializer $chunkSerializer
	){

	}

	public function newEncoder() : MVPacketSerializer{
		return LatestPacketSerializer::newEncoder(ProtocolInfo::CURRENT_PROTOCOL);
	}

	public function newDecoder(string $buffer, int $offset) : MVPacketSerializer{
		return LatestPacketSerializer::newDecoder($buffer, $offset, ProtocolInfo::CURRENT_PROTOCOL);
	}

	public function getChunkSerializer() : MVChunkSerializer{
		return $this->chunkSerializer;
	}
}
