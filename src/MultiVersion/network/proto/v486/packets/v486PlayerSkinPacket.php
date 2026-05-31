<?php

declare(strict_types=1);

namespace MultiVersion\network\proto\v486\packets;

use MultiVersion\network\proto\v486\v486PacketSerializer;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;

final class v486PlayerSkinPacket extends PlayerSkinPacket{

	public static function fromLatest(PlayerSkinPacket $pk) : self{
		$npk = new self();
		$npk->uuid = $pk->uuid;
		$npk->oldSkinName = $pk->oldSkinName;
		$npk->newSkinName = $pk->newSkinName;
		$npk->skin = $pk->skin;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in, ?int $protocolId = null) : void{
		$in = v486PacketSerializer::reader($in, $protocolId);
		$this->uuid = $in->getUUID();
		$this->skin = $in->getSkin();
		$this->newSkinName = $in->getString();
		$this->oldSkinName = $in->getString();
		$this->skin->setVerified($in->getBool());
	}

	protected function encodePayload(ByteBufferWriter $out, ?int $protocolId = null) : void{
		$out = v486PacketSerializer::writer($out, $protocolId);
		$out->putUUID($this->uuid);
		$out->putSkin($this->skin);
		$out->putString($this->newSkinName);
		$out->putString($this->oldSkinName);
		$out->putBool($this->skin->isVerified());
	}
}

