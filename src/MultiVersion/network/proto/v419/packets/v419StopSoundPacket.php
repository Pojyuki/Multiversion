<?php

namespace MultiVersion\network\proto\v419\packets;

use MultiVersion\network\proto\v419\v419PacketSerializer;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\StopSoundPacket;

class v419StopSoundPacket extends StopSoundPacket{

	public static function fromLatest(StopSoundPacket $packet) : self{
		$result = new self;
		$result->soundName = $packet->soundName;
		$result->stopAll = $packet->stopAll;
		$result->stopLegacyMusic = false;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, ?int $protocolId = null) : void{
		$in = v419PacketSerializer::reader($in, $protocolId);
		$this->soundName = $in->getString();
		$this->stopAll = $in->getBool();
		$this->stopLegacyMusic = false;
	}

	protected function encodePayload(ByteBufferWriter $out, ?int $protocolId = null) : void{
		$out = v419PacketSerializer::writer($out, $protocolId);
		$out->putString($this->soundName);
		$out->putBool($this->stopAll);
	}
}

