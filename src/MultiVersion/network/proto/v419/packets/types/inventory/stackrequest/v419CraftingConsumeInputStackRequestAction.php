<?php

namespace MultiVersion\network\proto\v419\packets\types\inventory\stackrequest;

use MultiVersion\network\proto\v419\packets\types\inventory\stackrequest\trait\v419DisappearStackRequestActionTrait;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestActionType;

final class v419CraftingConsumeInputStackRequestAction extends ItemStackRequestAction{
	use GetTypeIdFromConstTrait;
	use v419DisappearStackRequestActionTrait;

	public const ID = ItemStackRequestActionType::CRAFTING_CONSUME_INPUT;
}
