<?php

namespace MultiVersion\network\proto\v486\packets\types\inventory\stackrequest;

use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestActionType;

final class v486CraftingConsumeInputStackRequestAction extends ItemStackRequestAction{
	use GetTypeIdFromConstTrait;

	public const ID = ItemStackRequestActionType::CRAFTING_CONSUME_INPUT;

	public function __construct(
		private int $count,
		private v486ItemStackRequestSlotInfo $source
	){
	}

	public function getCount() : int{
		return $this->count;
	}

	public function getSource() : v486ItemStackRequestSlotInfo{
		return $this->source;
	}

	public static function read(PacketSerializer $in) : self{
		$count = $in->getByte();
		$source = v486ItemStackRequestSlotInfo::read($in);
		return new self($count, $source);
	}

	public function write(ByteBufferWriter|PacketSerializer $out, int $protocolId = 486) : void{
		$serializer = $out instanceof PacketSerializer ? $out : PacketSerializer::writer($out);
		$serializer->putByte($this->count);
		$this->source->write($serializer);
	}
}
