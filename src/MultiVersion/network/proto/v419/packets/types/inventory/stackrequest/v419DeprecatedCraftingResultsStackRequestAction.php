<?php

namespace MultiVersion\network\proto\v419\packets\types\inventory\stackrequest;

use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestActionType;
use function count;

/**
 * Legacy format used by protocol 419: uses v419 item stack serialization.
 */
final class v419DeprecatedCraftingResultsStackRequestAction extends ItemStackRequestAction{
	use GetTypeIdFromConstTrait;

	public const ID = ItemStackRequestActionType::CRAFTING_RESULTS_DEPRECATED_ASK_TY_LAING;

	/**
	 * @param ItemStack[] $results
	 */
	public function __construct(
		private array $results,
		private int $iterations
	){}

	/** @return ItemStack[] */
	public function getResults() : array{
		return $this->results;
	}

	public function getIterations() : int{
		return $this->iterations;
	}

	public static function read(PacketSerializer $in) : self{
		$results = [];
		for($i = 0, $len = $in->getUnsignedVarInt(); $i < $len; ++$i){
			$results[] = $in->getItemStackWithoutStackId();
		}
		$iterations = $in->getByte();
		return new self($results, $iterations);
	}

	public function write(ByteBufferWriter|PacketSerializer $out, int $protocolId = 419) : void{
		$serializer = $out instanceof PacketSerializer ? $out : PacketSerializer::writer($out);
		$serializer->putUnsignedVarInt(count($this->results));
		foreach($this->results as $result){
			$serializer->putItemStackWithoutStackId($result);
		}
		$serializer->putByte($this->iterations);
	}
}
