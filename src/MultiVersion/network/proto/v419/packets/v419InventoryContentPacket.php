<?php

namespace MultiVersion\network\proto\v419\packets;

use MultiVersion\network\proto\static\IRuntimeBlockMapping;
use MultiVersion\network\proto\utils\NetItemConverter;
use MultiVersion\network\proto\v419\packets\types\inventory\v419ItemStackWrapper;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v419InventoryContentPacket extends InventoryContentPacket{

	private array $_items = [];

	public static function fromLatest(InventoryContentPacket $pk) : self{
		$npk = new self();
		$npk->windowId = $pk->windowId;
		foreach($pk->items as $key => $item){
			$npk->_items[$key] = new v419ItemStackWrapper($item->getStackId(), $item->getItemStack());
		}
		return $npk;
	}

	protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{

		$in = \MultiVersion\network\proto\v419\v419PacketSerializer::reader($in, $protocolId);
		$this->windowId = $in->getUnsignedVarInt();
		$count = $in->getUnsignedVarInt();
		for($i = 0; $i < $count; ++$i){
			$this->_items[] = v419ItemStackWrapper::read($in, true);
		}
	}

	protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{

		$out = \MultiVersion\network\proto\v419\v419PacketSerializer::writer($out, $protocolId);
		$out->putUnsignedVarInt($this->windowId);
		$out->putUnsignedVarInt(count($this->_items));
		foreach($this->_items as $item){
			$item->write($out, true);
		}
	}
}


