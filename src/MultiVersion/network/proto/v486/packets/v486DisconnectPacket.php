<?php

namespace MultiVersion\network\proto\v486\packets;

use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v486DisconnectPacket extends DisconnectPacket{

	public static function fromLatest(DisconnectPacket $pk) : self{
		$npk = new self();
		$npk->message = $pk->message;
		return $npk;
	}

	protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{

		$in = \MultiVersion\network\proto\v486\v486PacketSerializer::reader($in, $protocolId);
		$hideDisconnectionScreen = $in->getBool();
		if(!$hideDisconnectionScreen){
			$this->message = $in->getString();
		}
	}

	protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{

		$out = \MultiVersion\network\proto\v486\v486PacketSerializer::writer($out, $protocolId);
		$out->putBool($this->message === null);
		if($this->message !== null){
			$out->putString($this->message);
		}
	}
}

