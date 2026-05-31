<?php

namespace MultiVersion\network\proto\v419\packets\types\inventory\stackrequest;

use InvalidArgumentException;
use MultiVersion\network\proto\utils\ReflectionUtils;
use MultiVersion\network\proto\v419\packets\types\inventory\v419ContainerUIIds;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\BeaconPaymentStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftingConsumeInputStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftingCreateSpecificResultStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftRecipeAutoStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftRecipeStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CreativeCreateStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\DeprecatedCraftingNonImplementedStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\DeprecatedCraftingResultsStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\DestroyStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\DropStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\LabTableCombineStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\PlaceStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\SwapStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\TakeStackRequestAction;
use pocketmine\utils\BinaryDataException;
use ReflectionException;
use function count;

final class v419ItemStackRequest{
	private const LEGACY_TAKE = 0;
	private const LEGACY_PLACE = 1;
	private const LEGACY_SWAP = 2;
	private const LEGACY_DROP = 3;
	private const LEGACY_DESTROY = 4;
	private const LEGACY_CRAFTING_CONSUME_INPUT = 5;
	private const LEGACY_CRAFTING_MARK_SECONDARY_RESULT_SLOT = 6;
	private const LEGACY_LAB_TABLE_COMBINE = 7;
	private const LEGACY_BEACON_PAYMENT = 8;
	private const LEGACY_CRAFTING_RECIPE = 9;
	private const LEGACY_CRAFTING_RECIPE_AUTO = 10;
	private const LEGACY_CREATIVE_CREATE = 11;
	private const LEGACY_CRAFTING_NON_IMPLEMENTED_DEPRECATED = 12;
	private const LEGACY_CRAFTING_RESULTS_DEPRECATED = 13;

	/**
	 * @param ItemStackRequestAction[] $actions
	 * @param string[] $filterStrings
	 *
	 * @phpstan-param list<string> $filterStrings
	 */
	public function __construct(
		private int $requestId,
		private array $actions,
		private array $filterStrings,
		private int $filterStringCause
	){
	}

	public function getRequestId() : int{
		return $this->requestId;
	}

	/** @return ItemStackRequestAction[] */
	public function getActions() : array{
		return $this->actions;
	}

	/**
	 * @return string[]
	 * @phpstan-return list<string>
	 */
	public function getFilterStrings() : array{
		return $this->filterStrings;
	}

	public function getFilterStringCause() : int{
		return $this->filterStringCause;
	}

	/** @var array<class-string<ItemStackRequestAction>, bool> */
	private static array $nativeReadExpectsProtocolId = [];

	/**
	 * @param class-string<ItemStackRequestAction> $className
	 */
	private static function readNativeAction(string $className, ByteBufferReader $reader, int $protocolId) : ItemStackRequestAction{
		if(!isset(self::$nativeReadExpectsProtocolId[$className])){
			self::$nativeReadExpectsProtocolId[$className] = (new \ReflectionMethod($className, "read"))->getNumberOfParameters() >= 2;
		}

		return self::$nativeReadExpectsProtocolId[$className]
			? $className::read($reader, $protocolId)
			: $className::read($reader);
	}

	/**
	 * @throws BinaryDataException
	 * @throws PacketDecodeException
	 * @throws ReflectionException
	 */
	private static function readAction(PacketSerializer $serializer, ByteBufferReader $reader, int $legacyTypeId, int $protocolId = 419) : ItemStackRequestAction{
		$action = match($legacyTypeId){
			self::LEGACY_TAKE => v419TakeStackRequestAction::read($serializer),
			self::LEGACY_PLACE => v419PlaceStackRequestAction::read($serializer),
			self::LEGACY_SWAP => v419SwapStackRequestAction::read($serializer),
			self::LEGACY_DROP => v419DropStackRequestAction::read($serializer),
			self::LEGACY_DESTROY => v419DestroyStackRequestAction::read($serializer),
			self::LEGACY_CRAFTING_CONSUME_INPUT => v419CraftingConsumeInputStackRequestAction::read($serializer),
			self::LEGACY_CRAFTING_MARK_SECONDARY_RESULT_SLOT => self::readNativeAction(CraftingCreateSpecificResultStackRequestAction::class, $reader, $protocolId),
			self::LEGACY_LAB_TABLE_COMBINE => self::readNativeAction(LabTableCombineStackRequestAction::class, $reader, $protocolId),
			self::LEGACY_BEACON_PAYMENT => self::readNativeAction(BeaconPaymentStackRequestAction::class, $reader, $protocolId),
			self::LEGACY_CRAFTING_RECIPE => v419CraftRecipeStackRequestAction::read($serializer),
			self::LEGACY_CRAFTING_RECIPE_AUTO => v419CraftRecipeAutoStackRequestAction::read($serializer),
			self::LEGACY_CREATIVE_CREATE => v419CreativeCreateStackRequestAction::read($serializer),
			self::LEGACY_CRAFTING_NON_IMPLEMENTED_DEPRECATED => self::readNativeAction(DeprecatedCraftingNonImplementedStackRequestAction::class, $reader, $protocolId),
			self::LEGACY_CRAFTING_RESULTS_DEPRECATED => v419DeprecatedCraftingResultsStackRequestAction::read($serializer),
			default => throw new PacketDecodeException("Unhandled legacy v419 item stack request action type $legacyTypeId")
		};

		if($action instanceof v419SwapStackRequestAction){
			if(($containerId = ($slot1 = $action->getSlot1())->getContainerId()) >= v419ContainerUIIds::RECIPE_BOOK){
				$containerId++;
				ReflectionUtils::setProperty(get_class($action), $action, "slot1", new v419ItemStackRequestSlotInfo($containerId, $slot1->getSlotId(), $slot1->getStackId()));
			}
			if(($containerId = ($slot2 = $action->getSlot2())->getContainerId()) >= v419ContainerUIIds::RECIPE_BOOK){
				$containerId++;
				ReflectionUtils::setProperty(get_class($action), $action, "slot2", new v419ItemStackRequestSlotInfo($containerId, $slot2->getSlotId(), $slot2->getStackId()));
			}
		}elseif(
			$action instanceof v419CraftingConsumeInputStackRequestAction ||
			$action instanceof v419DestroyStackRequestAction ||
			$action instanceof v419DropStackRequestAction
		){
			if($action instanceof v419CraftingConsumeInputStackRequestAction ||
				$action instanceof v419DestroyStackRequestAction ||
				$action instanceof v419DropStackRequestAction
			){
				if(($containerId = ($source = $action->getSource())->getContainerId()) >= v419ContainerUIIds::RECIPE_BOOK){
					$containerId++;
					ReflectionUtils::setProperty(get_class($action), $action, "source", new v419ItemStackRequestSlotInfo($containerId, $source->getSlotId(), $source->getStackId()));
				}
			}
		}elseif(
			$action instanceof v419PlaceStackRequestAction ||
			$action instanceof v419TakeStackRequestAction
		){
			if(($containerId = ($source = $action->getSource())->getContainerId()) >= v419ContainerUIIds::RECIPE_BOOK){
				$containerId++;
				ReflectionUtils::setProperty(get_class($action), $action, "source", new v419ItemStackRequestSlotInfo($containerId, $source->getSlotId(), $source->getStackId()));
			}
			if(($containerId = ($destination = $action->getDestination())->getContainerId()) >= v419ContainerUIIds::RECIPE_BOOK){
				$containerId++;
				ReflectionUtils::setProperty(get_class($action), $action, "destination", new v419ItemStackRequestSlotInfo($containerId, $destination->getSlotId(), $destination->getStackId()));
			}
		}elseif($action instanceof v419CraftRecipeAutoStackRequestAction){
			$action = new CraftRecipeAutoStackRequestAction($action->getRecipeId(), 1, 1, []);
		}

		return $action;
	}

	public static function read(ByteBufferReader|PacketSerializer $in, int $protocolId = 419) : self{
		$serializer = $in instanceof PacketSerializer ? $in : PacketSerializer::reader($in);
		$reader = $serializer->getReader();

		$requestId = $serializer->getVarInt();
		$actions = [];
		for($i = 0, $len = $serializer->getUnsignedVarInt(); $i < $len; ++$i){
			$actions[] = self::readAction($serializer, $reader, $serializer->getByte(), $protocolId);
		}
		return new self($requestId, $actions, [], 0);
	}

	private static function toLegacyTypeId(ItemStackRequestAction $action) : int{
		return match($action->getTypeId()){
			TakeStackRequestAction::ID => self::LEGACY_TAKE,
			PlaceStackRequestAction::ID => self::LEGACY_PLACE,
			SwapStackRequestAction::ID => self::LEGACY_SWAP,
			DropStackRequestAction::ID => self::LEGACY_DROP,
			DestroyStackRequestAction::ID => self::LEGACY_DESTROY,
			CraftingConsumeInputStackRequestAction::ID => self::LEGACY_CRAFTING_CONSUME_INPUT,
			CraftingCreateSpecificResultStackRequestAction::ID => self::LEGACY_CRAFTING_MARK_SECONDARY_RESULT_SLOT,
			LabTableCombineStackRequestAction::ID => self::LEGACY_LAB_TABLE_COMBINE,
			BeaconPaymentStackRequestAction::ID => self::LEGACY_BEACON_PAYMENT,
			CraftRecipeStackRequestAction::ID => self::LEGACY_CRAFTING_RECIPE,
			CraftRecipeAutoStackRequestAction::ID => self::LEGACY_CRAFTING_RECIPE_AUTO,
			CreativeCreateStackRequestAction::ID => self::LEGACY_CREATIVE_CREATE,
			DeprecatedCraftingNonImplementedStackRequestAction::ID => self::LEGACY_CRAFTING_NON_IMPLEMENTED_DEPRECATED,
			DeprecatedCraftingResultsStackRequestAction::ID => self::LEGACY_CRAFTING_RESULTS_DEPRECATED,
			default => throw new InvalidArgumentException("Unsupported item stack request action type {$action->getTypeId()} for protocol 419")
		};
	}

	/**
	 * @throws ReflectionException
	 */
	private static function writeAction(PacketSerializer $serializer, ItemStackRequestAction $action, int $protocolId = 419) : void{
		if($action instanceof SwapStackRequestAction || $action instanceof v419SwapStackRequestAction){
			if($action instanceof v419SwapStackRequestAction){
				if(($containerId = ($slot1 = $action->getSlot1())->getContainerId()) > v419ContainerUIIds::RECIPE_BOOK){
					$containerId--;
					ReflectionUtils::setProperty(get_class($action), $action, "slot1", new v419ItemStackRequestSlotInfo($containerId, $slot1->getSlotId(), $slot1->getStackId()));
				}elseif($containerId === v419ContainerUIIds::RECIPE_BOOK){
					throw new InvalidArgumentException("Invalid container ID for protocol version 419");
				}
				if(($containerId = ($slot2 = $action->getSlot2())->getContainerId()) > v419ContainerUIIds::RECIPE_BOOK){
					$containerId--;
					ReflectionUtils::setProperty(get_class($action), $action, "slot2", new v419ItemStackRequestSlotInfo($containerId, $slot2->getSlotId(), $slot2->getStackId()));
				}elseif($containerId === v419ContainerUIIds::RECIPE_BOOK){
					throw new InvalidArgumentException("Invalid container ID for protocol version 419");
				}
			}else{
				if(($containerId = ($slot1 = $action->getSlot1())->getContainerName()->getContainerId()) > v419ContainerUIIds::RECIPE_BOOK){
					$containerId--;
					ReflectionUtils::setProperty(get_class($action), $action, "slot1", new v419ItemStackRequestSlotInfo($containerId, $slot1->getSlotId(), $slot1->getStackId()));
				}elseif($containerId === v419ContainerUIIds::RECIPE_BOOK){
					throw new InvalidArgumentException("Invalid container ID for protocol version 419");
				}
				if(($containerId = ($slot2 = $action->getSlot2())->getContainerName()->getContainerId()) > v419ContainerUIIds::RECIPE_BOOK){
					$containerId--;
					ReflectionUtils::setProperty(get_class($action), $action, "slot2", new v419ItemStackRequestSlotInfo($containerId, $slot2->getSlotId(), $slot2->getStackId()));
				}elseif($containerId === v419ContainerUIIds::RECIPE_BOOK){
					throw new InvalidArgumentException("Invalid container ID for protocol version 419");
				}
			}
		}elseif(
			$action instanceof CraftingConsumeInputStackRequestAction ||
			$action instanceof v419CraftingConsumeInputStackRequestAction ||
			$action instanceof DestroyStackRequestAction ||
			$action instanceof v419DestroyStackRequestAction ||
			$action instanceof DropStackRequestAction ||
			$action instanceof v419DropStackRequestAction
		){
			if($action instanceof v419CraftingConsumeInputStackRequestAction ||
				$action instanceof v419DestroyStackRequestAction ||
				$action instanceof v419DropStackRequestAction
			){
				if(($containerId = ($source = $action->getSource())->getContainerId()) > v419ContainerUIIds::RECIPE_BOOK){
					$containerId--;
					ReflectionUtils::setProperty(get_class($action), $action, "source", new v419ItemStackRequestSlotInfo($containerId, $source->getSlotId(), $source->getStackId()));
				}elseif($containerId === v419ContainerUIIds::RECIPE_BOOK){
					throw new InvalidArgumentException("Invalid container ID for protocol version 419");
				}
			}else{
				if(($containerId = ($source = $action->getSource())->getContainerName()->getContainerId()) > v419ContainerUIIds::RECIPE_BOOK){
					$containerId--;
					ReflectionUtils::setProperty(get_class($action), $action, "source", new v419ItemStackRequestSlotInfo($containerId, $source->getSlotId(), $source->getStackId()));
				}elseif($containerId === v419ContainerUIIds::RECIPE_BOOK){
					throw new InvalidArgumentException("Invalid container ID for protocol version 419");
				}
			}
		}elseif(
			$action instanceof PlaceStackRequestAction ||
			$action instanceof TakeStackRequestAction ||
			$action instanceof v419PlaceStackRequestAction ||
			$action instanceof v419TakeStackRequestAction
		){
			if($action instanceof v419PlaceStackRequestAction || $action instanceof v419TakeStackRequestAction){
				if(($containerId = ($source = $action->getSource())->getContainerId()) > v419ContainerUIIds::RECIPE_BOOK){
					$containerId--;
					ReflectionUtils::setProperty(get_class($action), $action, "source", new v419ItemStackRequestSlotInfo($containerId, $source->getSlotId(), $source->getStackId()));
				}elseif($containerId === v419ContainerUIIds::RECIPE_BOOK){
					throw new InvalidArgumentException("Invalid container ID for protocol version 419");
				}
				if(($containerId = ($destination = $action->getDestination())->getContainerId()) > v419ContainerUIIds::RECIPE_BOOK){
					$containerId--;
					ReflectionUtils::setProperty(get_class($action), $action, "destination", new v419ItemStackRequestSlotInfo($containerId, $destination->getSlotId(), $destination->getStackId()));
				}elseif($containerId === v419ContainerUIIds::RECIPE_BOOK){
					throw new InvalidArgumentException("Invalid container ID for protocol version 419");
				}
			}else{
				if(($containerId = ($source = $action->getSource())->getContainerName()->getContainerId()) > v419ContainerUIIds::RECIPE_BOOK){
					$containerId--;
					ReflectionUtils::setProperty(get_class($action), $action, "source", new v419ItemStackRequestSlotInfo($containerId, $source->getSlotId(), $source->getStackId()));
				}elseif($containerId === v419ContainerUIIds::RECIPE_BOOK){
					throw new InvalidArgumentException("Invalid container ID for protocol version 419");
				}
				if(($containerId = ($destination = $action->getDestination())->getContainerName()->getContainerId()) > v419ContainerUIIds::RECIPE_BOOK){
					$containerId--;
					ReflectionUtils::setProperty(get_class($action), $action, "destination", new v419ItemStackRequestSlotInfo($containerId, $destination->getSlotId(), $destination->getStackId()));
				}elseif($containerId === v419ContainerUIIds::RECIPE_BOOK){
					throw new InvalidArgumentException("Invalid container ID for protocol version 419");
				}
			}
		}

		if($action instanceof v419TakeStackRequestAction ||
			$action instanceof v419PlaceStackRequestAction ||
			$action instanceof v419SwapStackRequestAction ||
			$action instanceof v419DropStackRequestAction ||
			$action instanceof v419DestroyStackRequestAction ||
			$action instanceof v419CraftingConsumeInputStackRequestAction ||
			$action instanceof v419CraftRecipeAutoStackRequestAction ||
			$action instanceof v419CraftRecipeStackRequestAction ||
			$action instanceof v419CreativeCreateStackRequestAction ||
			$action instanceof v419DeprecatedCraftingResultsStackRequestAction){
			$action->write($serializer, $protocolId);
		}elseif($action instanceof CraftRecipeAutoStackRequestAction){
			(new v419CraftRecipeAutoStackRequestAction($action->getRecipeId()))->write($serializer, $protocolId);
		}elseif($action instanceof CraftRecipeStackRequestAction){
			(new v419CraftRecipeStackRequestAction($action->getRecipeId()))->write($serializer, $protocolId);
		}elseif($action instanceof CreativeCreateStackRequestAction){
			(new v419CreativeCreateStackRequestAction($action->getCreativeItemId()))->write($serializer, $protocolId);
		}elseif($action instanceof DeprecatedCraftingResultsStackRequestAction){
			(new v419DeprecatedCraftingResultsStackRequestAction($action->getResults(), $action->getIterations()))->write($serializer, $protocolId);
		}else{
			$action->write($serializer->getWriter(), $protocolId);
		}
	}

	/**
	 * @throws ReflectionException
	 */
	public function write(ByteBufferWriter|PacketSerializer $out, int $protocolId = 419) : void{
		$serializer = $out instanceof PacketSerializer ? $out : PacketSerializer::writer($out);

		$serializer->putVarInt($this->requestId);
		$serializer->putUnsignedVarInt(count($this->actions));
		foreach($this->actions as $action){
			$serializer->putByte(self::toLegacyTypeId($action));
			self::writeAction($serializer, $action, $protocolId);
		}
	}
}
