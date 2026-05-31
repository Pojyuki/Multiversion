<?php

namespace MultiVersion\network\proto\v419;

use pocketmine\network\mcpe\protocol\PhotoTransferPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v419PhotoTransferPacket extends PhotoTransferPacket{

	public static function fromLatest(PhotoTransferPacket $pk) : self{
		$npk = new self();
		$npk->photoName = $pk->photoName;
		$npk->photoData = $pk->photoData;
		$npk->bookId = $pk->bookId;
		return $npk;
	}

	protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{

		$in = \MultiVersion\network\proto\v419\v419PacketSerializer::reader($in, $protocolId);
		$this->photoName = $in->getString();
		$this->photoData = $in->getString();
		$this->bookId = $in->getString();
	}

	protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{

		$out = \MultiVersion\network\proto\v419\v419PacketSerializer::writer($out, $protocolId);
		$out->putString($this->photoName);
		$out->putString($this->photoData);
		$out->putString($this->bookId);
	}
}

