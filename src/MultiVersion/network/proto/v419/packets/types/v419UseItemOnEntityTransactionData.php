<?php

declare(strict_types=1);

namespace MultiVersion\network\proto\v419\packets\types;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;

class v419UseItemOnEntityTransactionData extends UseItemOnEntityTransactionData{
	use GetTypeIdFromConstTrait;

	public const ID = InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY;

	public const ACTION_INTERACT = 0;
	public const ACTION_ATTACK = 1;
	public const ACTION_ITEM_INTERACT = 2;

	private int $actorRuntimeId;
	private int $actionType;
	private int $hotbarSlot;
	private ItemStackWrapper $itemInHand;
	private Vector3 $playerPosition;
	private Vector3 $clickPosition;

	public function getActorRuntimeId() : int{
		return $this->actorRuntimeId;
	}

	public function getActionType() : int{
		return $this->actionType;
	}

	public function getHotbarSlot() : int{
		return $this->hotbarSlot;
	}

	public function getItemInHand() : ItemStackWrapper{
		return $this->itemInHand;
	}

	public function getPlayerPosition() : Vector3{
		return $this->playerPosition;
	}

	public function getClickPosition() : Vector3{
		return $this->clickPosition;
	}

	protected function decodeData(\pmmp\encoding\ByteBufferReader $in, int $protocolId = \pocketmine\network\mcpe\protocol\ProtocolInfo::CURRENT_PROTOCOL) : void{
		$stream = \MultiVersion\network\proto\v419\v419PacketSerializer::reader($in);
		$this->actorRuntimeId = $stream->getActorRuntimeId();
		$this->actionType = $stream->getUnsignedVarInt();
		$this->hotbarSlot = $stream->getVarInt();
		$this->itemInHand = $stream->getItemStackWrapper();
		$this->playerPosition = $stream->getVector3();
		$this->clickPosition = $stream->getVector3();
	}

	protected function encodeData(\pmmp\encoding\ByteBufferWriter $out, int $protocolId = \pocketmine\network\mcpe\protocol\ProtocolInfo::CURRENT_PROTOCOL) : void{
		$stream = \MultiVersion\network\proto\v419\v419PacketSerializer::writer($out);
		$stream->putActorRuntimeId($this->actorRuntimeId);
		$stream->putUnsignedVarInt($this->actionType);
		$stream->putVarInt($this->hotbarSlot);
		$stream->putItemStackWrapper($this->itemInHand);
		$stream->putVector3($this->playerPosition);
		$stream->putVector3($this->clickPosition);
	}
}
