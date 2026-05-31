<?php

namespace MultiVersion\network\proto\v419\packets;

use MultiVersion\network\proto\utils\ReflectionUtils;
use MultiVersion\network\proto\v419\packets\types\inventory\v419InventoryTransactionChangedSlotsHack;
use MultiVersion\network\proto\v419\packets\types\inventory\v419NetworkInventoryAction;
use MultiVersion\network\proto\v419\packets\types\v419ReleaseItemTransactionData;
use MultiVersion\network\proto\v419\packets\types\v419UseItemOnEntityTransactionData;
use MultiVersion\network\proto\v419\packets\types\v419UseItemTransactionData;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\types\inventory\InventoryTransactionChangedSlotsHack;
use pocketmine\network\mcpe\protocol\types\inventory\MismatchTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;

class v419InventoryTransactionPacket extends InventoryTransactionPacket{

	public static function fromLatest(InventoryTransactionPacket $pk) : self{
		$npk = new self();
		$npk->requestId = $pk->requestId;
		$npk->requestChangedSlots = $pk->requestChangedSlots;
		$npk->trData = $pk->trData;
		return $npk;
	}

	protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{

		$in = \MultiVersion\network\proto\v419\v419PacketSerializer::reader($in, $protocolId);
		$this->requestId = $in->getVarInt();
		$this->requestChangedSlots = [];
		if($this->requestId !== 0){
			for($i = 0, $len = $in->getUnsignedVarInt(); $i < $len; ++$i){
				$requestChangedSlot = v419InventoryTransactionChangedSlotsHack::read($in);
				$this->requestChangedSlots[] = new InventoryTransactionChangedSlotsHack($requestChangedSlot->getContainerId(), $requestChangedSlot->getChangedSlotIndexes());
			}
		}

		$transactionType = $in->getUnsignedVarInt();

		$this->trData = match ($transactionType) {
			NormalTransactionData::ID => new NormalTransactionData(),
			MismatchTransactionData::ID => new MismatchTransactionData(),
			UseItemTransactionData::ID => new v419UseItemTransactionData(),
			UseItemOnEntityTransactionData::ID => new v419UseItemOnEntityTransactionData(),
			ReleaseItemTransactionData::ID => new v419ReleaseItemTransactionData(),
			default => throw new PacketDecodeException("Unknown transaction type $transactionType"),
		};

		$hasItemStackId = $in->getBool();

		$actions = [];
		$actionCount = $in->getUnsignedVarInt();
		for($i = 0; $i < $actionCount; ++$i){
			$actions[] = (new v419NetworkInventoryAction())->readWithItemStackIds($in->getReader(), $hasItemStackId);
		}

		ReflectionUtils::setProperty(get_class($this->trData), $this->trData, "actions", $actions);
		ReflectionUtils::invoke(get_class($this->trData), $this->trData, "decodeData", $in->getReader());
	}

	protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{

		$out = \MultiVersion\network\proto\v419\v419PacketSerializer::writer($out, $protocolId);
		$out->putVarInt($this->requestId);
		if($this->requestId !== 0){
			$out->putUnsignedVarInt(count($this->requestChangedSlots));
			foreach($this->requestChangedSlots as $changedSlots){
				(new v419InventoryTransactionChangedSlotsHack($changedSlots->getContainerId(), $changedSlots->getChangedSlotIndexes()))->write($out);
			}
		}

		$out->putUnsignedVarInt($this->trData->getTypeId());
		// v419 legacy transaction stream has a hasItemStackId flag before actions.
		// This implementation currently emits legacy actions without stack IDs.
		$out->putBool(false);

		$this->trData->encode($out->getWriter());
	}
}


