<?php

namespace MultiVersion\network\proto\v486\packets;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;

class v486SetActorMotionPacket extends SetActorMotionPacket{

	public static function fromLatest(SetActorMotionPacket $pk) : self{
		$npk = new self();
		$npk->actorRuntimeId = $pk->actorRuntimeId;
		$npk->motion = $pk->motion;
		return $npk;
	}

	protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{

		$in = \MultiVersion\network\proto\v486\v486PacketSerializer::reader($in, $protocolId);
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->motion = $in->getVector3();
	}

	protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{

		$out = \MultiVersion\network\proto\v486\v486PacketSerializer::writer($out, $protocolId);
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putVector3($this->motion);
	}
}


