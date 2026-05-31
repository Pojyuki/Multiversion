<?php

namespace MultiVersion\network\proto\v419\packets\types\inventory\stackrequest;

use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestActionType;

/**
 * Legacy format used by protocol 419: no repetitions field.
 */
final class v419GrindstoneStackRequestAction extends ItemStackRequestAction{
	use GetTypeIdFromConstTrait;

	public const ID = ItemStackRequestActionType::CRAFTING_GRINDSTONE;

	public function __construct(
		private int $recipeId,
		private int $repairCost
	){}

	public function getRecipeId() : int{
		return $this->recipeId;
	}

	public function getRepairCost() : int{
		return $this->repairCost;
	}

	public static function read(PacketSerializer $in) : self{
		$recipeId = $in->readRecipeNetId();
		$repairCost = $in->getVarInt();
		return new self($recipeId, $repairCost);
	}

	public function write(ByteBufferWriter|PacketSerializer $out, int $protocolId = 419) : void{
		$serializer = $out instanceof PacketSerializer ? $out : PacketSerializer::writer($out);
		$serializer->writeRecipeNetId($this->recipeId);
		$serializer->putVarInt($this->repairCost);
	}
}
