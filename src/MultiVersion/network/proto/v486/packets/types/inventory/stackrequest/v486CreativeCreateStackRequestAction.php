<?php

namespace MultiVersion\network\proto\v486\packets\types\inventory\stackrequest;

use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestActionType;

/**
 * Legacy format used by protocol 486: only creative item net ID is present.
 */
final class v486CreativeCreateStackRequestAction extends ItemStackRequestAction{
	use GetTypeIdFromConstTrait;

	public const ID = ItemStackRequestActionType::CREATIVE_CREATE;

	public function __construct(
		private int $creativeItemId
	){}

	public function getCreativeItemId() : int{
		return $this->creativeItemId;
	}

	public static function read(PacketSerializer $in) : self{
		$creativeItemId = $in->readCreativeItemNetId();
		return new self($creativeItemId);
	}

	public function write(ByteBufferWriter|PacketSerializer $out, int $protocolId = 486) : void{
		$serializer = $out instanceof PacketSerializer ? $out : PacketSerializer::writer($out);
		$serializer->writeCreativeItemNetId($this->creativeItemId);
	}
}
