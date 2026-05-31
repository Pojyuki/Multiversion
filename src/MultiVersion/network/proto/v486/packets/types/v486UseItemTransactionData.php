<?php

/*
 * This file is part of BedrockProtocol.
 * Copyright (C) 2014-2022 PocketMine Team <https://github.com/pmmp/BedrockProtocol>
 *
 * BedrockProtocol is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace MultiVersion\network\proto\v486\packets\types;

use MultiVersion\network\proto\v486\v486PacketSerializer;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\inventory\PredictedResult;
use pocketmine\network\mcpe\protocol\types\inventory\TriggerType;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;

class v486UseItemTransactionData extends UseItemTransactionData {
	use GetTypeIdFromConstTrait;

	public const ID = InventoryTransactionPacket::TYPE_USE_ITEM;

	public const ACTION_CLICK_BLOCK = 0;
	public const ACTION_CLICK_AIR = 1;
	public const ACTION_BREAK_BLOCK = 2;

	private int $actionType;
	private BlockPosition $blockPosition;
	private int $face;
	private int $hotbarSlot;
	private ItemStackWrapper $itemInHand;
	private Vector3 $playerPosition;
	private Vector3 $clickPosition;
	private int $blockRuntimeId;

	public function getActionType() : int{
		return $this->actionType;
	}

	public function getBlockPosition() : BlockPosition{
		return $this->blockPosition;
	}

	public function getFace() : int{
		return $this->face;
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

	public function getBlockRuntimeId() : int{
		return $this->blockRuntimeId;
	}

	public function getTriggerType() : TriggerType{
		return TriggerType::PLAYER_INPUT;
	}

	public function getClientInteractPrediction() : PredictedResult{
		// Protocol 486 doesn't send modern client prediction fields.
		// Returning FAILURE prevents PM5.43+ from forcing legacy-unsafe block resync
		// on every right-click (which can cause ghost blocks / wrong visual placement).
		return PredictedResult::FAILURE;
	}

	protected function decodeData(ByteBufferReader $in, int $protocolId = \pocketmine\network\mcpe\protocol\ProtocolInfo::CURRENT_PROTOCOL) : void{
		$stream = v486PacketSerializer::reader($in);
		$this->actionType = $stream->getUnsignedVarInt();
		$this->blockPosition = $stream->getBlockPosition();
		$this->face = $stream->getVarInt();
		$this->hotbarSlot = $stream->getVarInt();
		$this->itemInHand = $stream->getItemStackWrapper();
		$this->playerPosition = $stream->getVector3();
		$this->clickPosition = $stream->getVector3();
		$this->blockRuntimeId = $stream->getUnsignedVarInt();
	}

	protected function encodeData(ByteBufferWriter $out, int $protocolId = \pocketmine\network\mcpe\protocol\ProtocolInfo::CURRENT_PROTOCOL) : void{
		$stream = v486PacketSerializer::writer($out);
		$stream->putUnsignedVarInt($this->actionType);
		$stream->putBlockPosition($this->blockPosition);
		$stream->putVarInt($this->face);
		$stream->putVarInt($this->hotbarSlot);
		$stream->putItemStackWrapper($this->itemInHand);
		$stream->putVector3($this->playerPosition);
		$stream->putVector3($this->clickPosition);
		$stream->putUnsignedVarInt($this->blockRuntimeId);
	}
}
