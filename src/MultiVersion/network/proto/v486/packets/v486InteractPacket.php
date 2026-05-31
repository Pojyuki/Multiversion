<?php

namespace MultiVersion\network\proto\v486\packets;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\InteractPacket;

class v486InteractPacket extends InteractPacket{

	protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{
		$in = \MultiVersion\network\proto\v486\v486PacketSerializer::reader($in, $protocolId);

		$this->action = $in->getByte();
		$this->targetActorRuntimeId = $in->getActorRuntimeId();
		$this->position = null;

		if(
			($this->action === self::ACTION_MOUSEOVER || $this->action === self::ACTION_LEAVE_VEHICLE) &&
			$in->getReader()->getUnreadLength() >= 12
		){
			$this->position = $in->getVector3();
		}
	}

	protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{
		$out = \MultiVersion\network\proto\v486\v486PacketSerializer::writer($out, $protocolId);

		$out->putByte($this->action);
		$out->putActorRuntimeId($this->targetActorRuntimeId);

		if($this->action === self::ACTION_MOUSEOVER || $this->action === self::ACTION_LEAVE_VEHICLE){
			$out->putVector3($this->position ?? new Vector3(0, 0, 0));
		}
	}
}

