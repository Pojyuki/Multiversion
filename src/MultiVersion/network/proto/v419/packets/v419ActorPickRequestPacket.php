<?php

namespace MultiVersion\network\proto\v419\packets;

use pocketmine\network\mcpe\protocol\ActorPickRequestPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v419ActorPickRequestPacket extends ActorPickRequestPacket{

	protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{

		$in = \MultiVersion\network\proto\v419\v419PacketSerializer::reader($in, $protocolId);
		$this->actorUniqueId = $in->getLLong();
		$this->hotbarSlot = $in->getByte();
	}

	protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{

		$out = \MultiVersion\network\proto\v419\v419PacketSerializer::writer($out, $protocolId);
		$out->putLLong($this->actorUniqueId);
		$out->putByte($this->hotbarSlot);
	}
}


