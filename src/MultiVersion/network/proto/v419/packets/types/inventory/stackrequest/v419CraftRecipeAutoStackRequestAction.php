<?php

namespace MultiVersion\network\proto\v419\packets\types\inventory\stackrequest;

use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestActionType;

/**
 * Legacy format used by protocol 419: only recipe net ID is present.
 */
final class v419CraftRecipeAutoStackRequestAction extends ItemStackRequestAction{
	use GetTypeIdFromConstTrait;

	public const ID = ItemStackRequestActionType::CRAFTING_RECIPE_AUTO;

	public function __construct(
		private int $recipeId
	){}

	public function getRecipeId() : int{ return $this->recipeId; }

	public function getRepetitions() : int{
		return 1;
	}

	/** @return array<int, mixed> */
	public function getIngredients() : array{
		return [];
	}

	public static function read(PacketSerializer $in) : self{
		$recipeId = $in->getVarInt();
		return new self($recipeId);
	}

	public function write(ByteBufferWriter|PacketSerializer $out, int $protocolId = 419) : void{
		$serializer = $out instanceof PacketSerializer ? $out : PacketSerializer::writer($out);
		$serializer->putVarInt($this->recipeId);
	}
}
