<?php

declare(strict_types=1);

namespace MultiVersion\network\proto\v419\packets\types;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;

class v419ReleaseItemTransactionData extends ReleaseItemTransactionData{
	use GetTypeIdFromConstTrait;

	public const ID = InventoryTransactionPacket::TYPE_RELEASE_ITEM;

	public const ACTION_RELEASE = 0;
	public const ACTION_CONSUME = 1;

	private int $actionType;
	private int $hotbarSlot;
	private ItemStackWrapper $itemInHand;
	private Vector3 $headPosition;

	public function getActionType() : int{
		return $this->actionType;
	}

	public function getHotbarSlot() : int{
		return $this->hotbarSlot;
	}

	public function getItemInHand() : ItemStackWrapper{
		return $this->itemInHand;
	}

	public function getHeadPosition() : Vector3{
		return $this->headPosition;
	}

	protected function decodeData(\pmmp\encoding\ByteBufferReader $in, int $protocolId = \pocketmine\network\mcpe\protocol\ProtocolInfo::CURRENT_PROTOCOL) : void{
		$stream = \MultiVersion\network\proto\v419\v419PacketSerializer::reader($in);
		$this->actionType = $stream->getUnsignedVarInt();
		$this->hotbarSlot = $stream->getVarInt();
		$this->itemInHand = $stream->getItemStackWrapper();
		$this->headPosition = $stream->getVector3();
	}

	protected function encodeData(\pmmp\encoding\ByteBufferWriter $out, int $protocolId = \pocketmine\network\mcpe\protocol\ProtocolInfo::CURRENT_PROTOCOL) : void{
		$stream = \MultiVersion\network\proto\v419\v419PacketSerializer::writer($out);
		$stream->putUnsignedVarInt($this->actionType);
		$stream->putVarInt($this->hotbarSlot);
		$stream->putItemStackWrapper($this->itemInHand);
		$stream->putVector3($this->headPosition);
	}
}
