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

namespace MultiVersion\network\proto\v419\packets\types;

use MultiVersion\network\proto\v419\v419PacketTranslator;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\inventory\InventoryTransactionChangedSlotsHack;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use function count;

final class v419ItemInteractionData{

	private int $requestId;
	/** @var InventoryTransactionChangedSlotsHack[] */
	private array $requestChangedSlots;
	private UseItemTransactionData $transactionData;

	/**
	 * @param InventoryTransactionChangedSlotsHack[] $requestChangedSlots
	 */
	public function __construct(int $requestId, array $requestChangedSlots, UseItemTransactionData $transactionData){
		$this->requestId = $requestId;
		$this->requestChangedSlots = $requestChangedSlots;
		$this->transactionData = $transactionData;
	}

	public function getRequestId() : int{
		return $this->requestId;
	}

	/**
	 * @return InventoryTransactionChangedSlotsHack[]
	 */
	public function getRequestChangedSlots() : array{
		return $this->requestChangedSlots;
	}

	public function getTransactionData() : UseItemTransactionData{
		return $this->transactionData;
	}

	public static function read(PacketSerializer $in, ?int $protocolId = null) : self{
		$protocolId ??= v419PacketTranslator::PROTOCOL_VERSION;
		$requestId = $in->getVarInt();
		$requestChangedSlots = [];
		if($requestId !== 0){
			$len = $in->getUnsignedVarInt();
			for($i = 0; $i < $len; ++$i){
				$requestChangedSlots[] = InventoryTransactionChangedSlotsHack::read($in->getReader());
			}
		}
		$transactionData = new v419UseItemTransactionData();
		self::decodeTransactionDataCompat($transactionData, $in->getReader(), $protocolId);
		return new self($requestId, $requestChangedSlots, $transactionData);
	}

	public function write(PacketSerializer $out, ?int $protocolId = null) : void{
		$protocolId ??= v419PacketTranslator::PROTOCOL_VERSION;
		$out->putVarInt($this->requestId);
		if($this->requestId !== 0){
			$out->putUnsignedVarInt(count($this->requestChangedSlots));
			foreach($this->requestChangedSlots as $changedSlot){
				$changedSlot->write($out->getWriter());
			}
		}
		self::encodeTransactionDataCompat($this->transactionData, $out->getWriter(), $protocolId);
	}

	private static function decodeTransactionDataCompat(UseItemTransactionData $transactionData, \pmmp\encoding\ByteBufferReader $in, int $protocolId) : void{
		$method = new \ReflectionMethod($transactionData, "decode");
		if($method->getNumberOfParameters() >= 2){
			$transactionData->decode($in, $protocolId);
		}else{
			$transactionData->decode($in);
		}
	}

	private static function encodeTransactionDataCompat(UseItemTransactionData $transactionData, \pmmp\encoding\ByteBufferWriter $out, int $protocolId) : void{
		$method = new \ReflectionMethod($transactionData, "encode");
		if($method->getNumberOfParameters() >= 2){
			$transactionData->encode($out, $protocolId);
		}else{
			$transactionData->encode($out);
		}
	}
}
