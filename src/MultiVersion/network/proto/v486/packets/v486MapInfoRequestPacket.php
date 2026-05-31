<?php

namespace MultiVersion\network\proto\v486\packets;

use pocketmine\network\mcpe\protocol\MapInfoRequestPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v486MapInfoRequestPacket extends MapInfoRequestPacket{

	protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{

		$in = \MultiVersion\network\proto\v486\v486PacketSerializer::reader($in, $protocolId);
		$this->mapId = $in->getActorUniqueId();
	}

	protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{

		$out = \MultiVersion\network\proto\v486\v486PacketSerializer::writer($out, $protocolId);
		$out->putActorUniqueId($this->mapId);
	}
}


