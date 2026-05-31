<?php

namespace MultiVersion\network\proto\v419\packets;

use pocketmine\network\mcpe\protocol\AnimatePacket;

class v419AnimatePacket extends AnimatePacket{
	private const LEGACY_ACTION_ROW_RIGHT = 128;
	private const LEGACY_ACTION_ROW_LEFT = 129;

	public static function fromLatest(AnimatePacket $pk) : self{
		$npk = new self();
		$npk->action = $pk->action;
		$npk->actorRuntimeId = $pk->actorRuntimeId;
		$npk->data = $pk->data;
		$npk->swingSource = null;
		return $npk;
	}

	protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{
		$in = \MultiVersion\network\proto\v419\v419PacketSerializer::reader($in, $protocolId);
		$this->action = $in->getVarInt();
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->data = 0.0;
		if($this->action === self::LEGACY_ACTION_ROW_LEFT || $this->action === self::LEGACY_ACTION_ROW_RIGHT){
			$this->data = $in->getLFloat();
		}
		$this->swingSource = null;
	}

	protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{
		$out = \MultiVersion\network\proto\v419\v419PacketSerializer::writer($out, $protocolId);
		$out->putVarInt($this->action);
		$out->putActorRuntimeId($this->actorRuntimeId);
		if($this->action === self::LEGACY_ACTION_ROW_LEFT || $this->action === self::LEGACY_ACTION_ROW_RIGHT){
			$out->putLFloat($this->data);
		}
	}
}
