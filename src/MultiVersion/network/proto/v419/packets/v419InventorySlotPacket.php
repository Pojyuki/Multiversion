<?php

namespace MultiVersion\network\proto\v419\packets;

use MultiVersion\network\proto\static\IRuntimeBlockMapping;
use MultiVersion\network\proto\utils\NetItemConverter;
use MultiVersion\network\proto\v419\packets\types\inventory\v419ItemStackWrapper;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v419InventorySlotPacket extends InventorySlotPacket{

	private v419ItemStackWrapper $_item;

	public static function fromLatest(InventorySlotPacket $pk) : self{
		$npk = new self();
		$npk->windowId = $pk->windowId;
		$npk->inventorySlot = $pk->inventorySlot;
		// Keep the real tracked stack ID so v419 ItemStackRequest validation stays in sync.
		$npk->_item = new v419ItemStackWrapper($pk->item->getStackId(), $pk->item->getItemStack());
		return $npk;
	}

	protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{

		$in = \MultiVersion\network\proto\v419\v419PacketSerializer::reader($in, $protocolId);
		$this->windowId = $in->getUnsignedVarInt();
		$this->inventorySlot = $in->getUnsignedVarInt();
		$this->_item = v419ItemStackWrapper::read($in, true);
	}

	protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{

		$out = \MultiVersion\network\proto\v419\v419PacketSerializer::writer($out, $protocolId);
		$out->putUnsignedVarInt($this->windowId);
		$out->putUnsignedVarInt($this->inventorySlot);
		$this->_item->write($out, true);
	}
}


