<?php

namespace MultiVersion\network\proto\v419\packets;

use pocketmine\network\mcpe\protocol\HurtArmorPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v419HurtArmorPacket extends HurtArmorPacket{

	public static function fromLatest(HurtArmorPacket $pk) : self{
		$npk = new self();
		$npk->cause = $pk->cause;
		$npk->health = $pk->health;
		return $npk;
	}

	protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{

		$in = \MultiVersion\network\proto\v419\v419PacketSerializer::reader($in, $protocolId);
		$this->cause = $in->getVarInt();
		$this->health = $in->getVarInt();
	}

	protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{

		$out = \MultiVersion\network\proto\v419\v419PacketSerializer::writer($out, $protocolId);
		$out->putVarInt($this->cause);
		$out->putVarInt($this->health);
	}
}

