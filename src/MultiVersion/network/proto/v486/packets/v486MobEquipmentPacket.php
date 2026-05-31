<?php

namespace MultiVersion\network\proto\v486\packets;

use pocketmine\network\mcpe\protocol\MobEquipmentPacket;

class v486MobEquipmentPacket extends MobEquipmentPacket{

	public static function fromLatest(MobEquipmentPacket $pk) : self{
		$result = new self();
		$result->actorRuntimeId = $pk->actorRuntimeId;
		$result->item = $pk->item;
		$result->inventorySlot = $pk->inventorySlot;
		$result->hotbarSlot = $pk->hotbarSlot;
		$result->windowId = $pk->windowId;
		return $result;
	}

	protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{
		$in = \MultiVersion\network\proto\v486\v486PacketSerializer::reader($in, $protocolId);
		$this->actorRuntimeId = $in->getActorRuntimeId();
		$this->item = $in->getItemStackWrapper();
		$this->inventorySlot = $in->getByte();
		$this->hotbarSlot = $in->getByte();
		$this->windowId = $in->getByte();
	}

	protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{
		$out = \MultiVersion\network\proto\v486\v486PacketSerializer::writer($out, $protocolId);
		$out->putActorRuntimeId($this->actorRuntimeId);
		$out->putItemStackWrapper($this->item);
		$out->putByte($this->inventorySlot);
		$out->putByte($this->hotbarSlot);
		$out->putByte($this->windowId);
	}
}

