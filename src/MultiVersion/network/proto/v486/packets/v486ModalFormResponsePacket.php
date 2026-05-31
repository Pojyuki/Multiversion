<?php

namespace MultiVersion\network\proto\v486\packets;

use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v486ModalFormResponsePacket extends ModalFormResponsePacket{

	protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{

		$in = \MultiVersion\network\proto\v486\v486PacketSerializer::reader($in, $protocolId);
		$this->formId = $in->getUnsignedVarInt();
		$this->formData = $in->getString();
		if(trim($this->formData) === "null"){
			$this->formData = null;
			$this->cancelReason = self::CANCEL_REASON_CLOSED;
		}else{
			$this->cancelReason = null;
		}
	}

	protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{

		$out = \MultiVersion\network\proto\v486\v486PacketSerializer::writer($out, $protocolId);
		$out->putUnsignedVarInt($this->formId);
		$out->putString($this->formData ?? "null");
	}
}


