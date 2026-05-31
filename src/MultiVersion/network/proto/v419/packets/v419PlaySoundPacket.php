<?php

namespace MultiVersion\network\proto\v419\packets;

use MultiVersion\network\proto\v419\v419PacketSerializer;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;

class v419PlaySoundPacket extends PlaySoundPacket{

	public static function fromLatest(PlaySoundPacket $packet) : self{
		$result = new self;
		$result->soundName = $packet->soundName;
		$result->x = $packet->x;
		$result->y = $packet->y;
		$result->z = $packet->z;
		$result->volume = $packet->volume;
		$result->pitch = $packet->pitch;
		$result->serverSoundHandle = null;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in, ?int $protocolId = null) : void{
		$in = v419PacketSerializer::reader($in, $protocolId);
		$this->soundName = $in->getString();
		$blockPosition = $in->getBlockPosition();
		$this->x = $blockPosition->getX() / 8;
		$this->y = $blockPosition->getY() / 8;
		$this->z = $blockPosition->getZ() / 8;
		$this->volume = $in->getLFloat();
		$this->pitch = $in->getLFloat();
		$this->serverSoundHandle = null;
	}

	protected function encodePayload(ByteBufferWriter $out, ?int $protocolId = null) : void{
		$out = v419PacketSerializer::writer($out, $protocolId);
		$out->putString($this->soundName);
		$out->putBlockPosition(new BlockPosition((int) ($this->x * 8), (int) ($this->y * 8), (int) ($this->z * 8)));
		$out->putLFloat($this->volume);
		$out->putLFloat($this->pitch);
	}
}

