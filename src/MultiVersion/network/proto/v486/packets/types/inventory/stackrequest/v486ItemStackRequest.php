<?php

namespace MultiVersion\network\proto\v486\packets\types\inventory\stackrequest;

use InvalidArgumentException;
use MultiVersion\network\proto\utils\ReflectionUtils;
use MultiVersion\network\proto\v486\packets\types\inventory\v486ContainerUIIds;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\inventory\FullContainerName;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\BeaconPaymentStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftingConsumeInputStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftingCreateSpecificResultStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftRecipeAutoStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftRecipeOptionalStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftRecipeStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\DeprecatedCraftingNonImplementedStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\DeprecatedCraftingResultsStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\DestroyStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\DropStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestSlotInfo;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\LabTableCombineStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\LoomStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\MineBlockStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\PlaceStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\SwapStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\TakeStackRequestAction;
use pocketmine\utils\BinaryDataException;
use ReflectionException;
use function count;

final class v486ItemStackRequest{
	/**
	 * @param ItemStackRequestAction[] $actions
	 * @param string[]                 $filterStrings
	 *
	 * @phpstan-param list<string>     $filterStrings
	 */
	public function __construct(
		private int $requestId,
		private array $actions,
		private array $filterStrings,
		private int $filterStringCause
	){
	}

	public function getRequestId() : int{ return $this->requestId; }

	/** @return ItemStackRequestAction[] */
	public function getActions() : array{ return $this->actions; }

	/**
	 * @return string[]
	 * @phpstan-return list<string>
	 */
	public function getFilterStrings() : array{ return $this->filterStrings; }

	public function getFilterStringCause() : int{ return $this->filterStringCause; }

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

	private static function remapContainerIdFromLegacy(int $containerId) : int{
		if($containerId >= v486ContainerUIIds::RECIPE_BOOK){
			$containerId++;
		}
		return $containerId;
	}

	private static function remapContainerIdToLegacy(int $containerId) : int{
		if($containerId > v486ContainerUIIds::RECIPE_BOOK){
			return $containerId - 1;
		}
		if($containerId === v486ContainerUIIds::RECIPE_BOOK){
			throw new InvalidArgumentException("Invalid container ID for protocol version 486");
		}
		return $containerId;
	}

	private static function remapLegacySlotInfoFromLegacy(v486ItemStackRequestSlotInfo $slot) : v486ItemStackRequestSlotInfo{
		$containerId = self::remapContainerIdFromLegacy($slot->getContainerId());
		if($containerId === $slot->getContainerId()){
			return $slot;
		}
		return new v486ItemStackRequestSlotInfo($containerId, $slot->getSlotId(), $slot->getStackId());
	}

	private static function remapLegacySlotInfoToLegacy(v486ItemStackRequestSlotInfo $slot) : v486ItemStackRequestSlotInfo{
		$containerId = self::remapContainerIdToLegacy($slot->getContainerId());
		if($containerId === $slot->getContainerId()){
			return $slot;
		}
		return new v486ItemStackRequestSlotInfo($containerId, $slot->getSlotId(), $slot->getStackId());
	}

	private static function remapCoreSlotInfoFromLegacy(ItemStackRequestSlotInfo $slot) : ItemStackRequestSlotInfo{
		$containerName = $slot->getContainerName();
		$containerId = self::remapContainerIdFromLegacy($containerName->getContainerId());
		if($containerId === $containerName->getContainerId()){
			return $slot;
		}
		return new ItemStackRequestSlotInfo(
			new FullContainerName($containerId, $containerName->getDynamicId()),
			$slot->getSlotId(),
			$slot->getStackId()
		);
	}

	private static function remapCoreSlotInfoToLegacy(ItemStackRequestSlotInfo $slot) : ItemStackRequestSlotInfo{
		$containerName = $slot->getContainerName();
		$containerId = self::remapContainerIdToLegacy($containerName->getContainerId());
		if($containerId === $containerName->getContainerId()){
			return $slot;
		}
		return new ItemStackRequestSlotInfo(
			new FullContainerName($containerId, $containerName->getDynamicId()),
			$slot->getSlotId(),
			$slot->getStackId()
		);
	}

	/**
	 * @throws BinaryDataException
	 * @throws PacketDecodeException
	 * @throws ReflectionException
	 */
	private static function readAction(PacketSerializer $serializer, ByteBufferReader $reader, int $typeId, int $protocolId = 486) : ItemStackRequestAction{
		$action = match ($typeId) {
			TakeStackRequestAction::ID => v486TakeStackRequestAction::read($serializer),
			PlaceStackRequestAction::ID => v486PlaceStackRequestAction::read($serializer),
			SwapStackRequestAction::ID => v486SwapStackRequestAction::read($serializer),
			DropStackRequestAction::ID => v486DropStackRequestAction::read($serializer),
			DestroyStackRequestAction::ID => v486DestroyStackRequestAction::read($serializer),
			CraftingConsumeInputStackRequestAction::ID => v486CraftingConsumeInputStackRequestAction::read($serializer),
			CraftingCreateSpecificResultStackRequestAction::ID => self::readNativeAction(CraftingCreateSpecificResultStackRequestAction::class, $reader, $protocolId),
			LabTableCombineStackRequestAction::ID => self::readNativeAction(LabTableCombineStackRequestAction::class, $reader, $protocolId),
			BeaconPaymentStackRequestAction::ID => self::readNativeAction(BeaconPaymentStackRequestAction::class, $reader, $protocolId),
			MineBlockStackRequestAction::ID => self::readNativeAction(MineBlockStackRequestAction::class, $reader, $protocolId),
			CraftRecipeStackRequestAction::ID => v486CraftRecipeStackRequestAction::read($serializer),
			CraftRecipeAutoStackRequestAction::ID => v486CraftRecipeAutoStackRequestAction::read($serializer),
			v486CreativeCreateStackRequestAction::ID => v486CreativeCreateStackRequestAction::read($serializer),
			CraftRecipeOptionalStackRequestAction::ID => self::readNativeAction(CraftRecipeOptionalStackRequestAction::class, $reader, $protocolId),
			v486GrindstoneStackRequestAction::ID => v486GrindstoneStackRequestAction::read($serializer),
			LoomStackRequestAction::ID => self::readNativeAction(LoomStackRequestAction::class, $reader, $protocolId),
			DeprecatedCraftingNonImplementedStackRequestAction::ID => self::readNativeAction(DeprecatedCraftingNonImplementedStackRequestAction::class, $reader, $protocolId),
			DeprecatedCraftingResultsStackRequestAction::ID => self::readNativeAction(DeprecatedCraftingResultsStackRequestAction::class, $reader, $protocolId),
            default => throw new PacketDecodeException("Unhandled item stack request action type $typeId"),
		};
			if($action instanceof v486SwapStackRequestAction){
				$slot1 = self::remapLegacySlotInfoFromLegacy($action->getSlot1());
				if($slot1 !== $action->getSlot1()){
					ReflectionUtils::setProperty(get_class($action), $action, "slot1", $slot1);
				}
				$slot2 = self::remapLegacySlotInfoFromLegacy($action->getSlot2());
				if($slot2 !== $action->getSlot2()){
					ReflectionUtils::setProperty(get_class($action), $action, "slot2", $slot2);
				}
			}elseif($action instanceof v486DropStackRequestAction){
				$source = self::remapLegacySlotInfoFromLegacy($action->getSource());
				if($source !== $action->getSource()){
					ReflectionUtils::setProperty(get_class($action), $action, "source", $source);
				}
			}elseif($action instanceof v486CraftingConsumeInputStackRequestAction || $action instanceof v486DestroyStackRequestAction){
				$source = self::remapLegacySlotInfoFromLegacy($action->getSource());
				if($source !== $action->getSource()){
					ReflectionUtils::setProperty(get_class($action), $action, "source", $source);
				}
			}elseif($action instanceof CraftingConsumeInputStackRequestAction || $action instanceof DestroyStackRequestAction){
				$source = self::remapCoreSlotInfoFromLegacy($action->getSource());
				if($source !== $action->getSource()){
					ReflectionUtils::setProperty(get_class($action), $action, "source", $source);
				}
			}elseif(
				$action instanceof v486PlaceStackRequestAction ||
				$action instanceof v486TakeStackRequestAction
			){
				$source = self::remapLegacySlotInfoFromLegacy($action->getSource());
				if($source !== $action->getSource()){
					ReflectionUtils::setProperty(get_class($action), $action, "source", $source);
				}
				$destination = self::remapLegacySlotInfoFromLegacy($action->getDestination());
				if($destination !== $action->getDestination()){
					ReflectionUtils::setProperty(get_class($action), $action, "destination", $destination);
				}
			}elseif($action instanceof v486CraftRecipeAutoStackRequestAction){
				$action = new CraftRecipeAutoStackRequestAction($action->getRecipeId(), $action->getRepetitions(), $action->getRepetitions(), $action->getIngredients());
			}
			return $action;
	}

	public static function read(ByteBufferReader|PacketSerializer $in, int $protocolId = 486) : self{
		$serializer = $in instanceof PacketSerializer ? $in : PacketSerializer::reader($in);
		$reader = $serializer->getReader();
		$requestId = $serializer->getVarInt();
		$actions = [];
		for($i = 0, $len = $serializer->getUnsignedVarInt(); $i < $len; ++$i){
			$typeId = $serializer->getByte();
			$actions[] = self::readAction($serializer, $reader, $typeId, $protocolId);
		}
		$filterStrings = [];
		if($reader->getUnreadLength() > 0){
			for($i = 0, $len = $serializer->getUnsignedVarInt(); $i < $len; ++$i){
				$filterStrings[] = $serializer->getString();
			}
		}
		return new self($requestId, $actions, $filterStrings, 0);
	}

	/**
	 * @throws ReflectionException
	 */
	private static function writeAction(PacketSerializer $serializer, ItemStackRequestAction $action, int $protocolId = 486) : void{
		if($action instanceof SwapStackRequestAction){
			$slot1 = $action->getSlot1();
			$slot2 = $action->getSlot2();
			(new v486SwapStackRequestAction(
				new v486ItemStackRequestSlotInfo(
					self::remapContainerIdToLegacy($slot1->getContainerName()->getContainerId()),
					$slot1->getSlotId(),
					$slot1->getStackId()
				),
				new v486ItemStackRequestSlotInfo(
					self::remapContainerIdToLegacy($slot2->getContainerName()->getContainerId()),
					$slot2->getSlotId(),
					$slot2->getStackId()
				)
			))->write($serializer, $protocolId);
			return;
		}elseif($action instanceof DropStackRequestAction){
			$source = $action->getSource();
			(new v486DropStackRequestAction(
				$action->getCount(),
				new v486ItemStackRequestSlotInfo(
					self::remapContainerIdToLegacy($source->getContainerName()->getContainerId()),
					$source->getSlotId(),
					$source->getStackId()
				),
				$action->isRandomly()
			))->write($serializer, $protocolId);
			return;
		}elseif($action instanceof TakeStackRequestAction){
			$source = $action->getSource();
			$destination = $action->getDestination();
			(new v486TakeStackRequestAction(
				$action->getCount(),
				new v486ItemStackRequestSlotInfo(
					self::remapContainerIdToLegacy($source->getContainerName()->getContainerId()),
					$source->getSlotId(),
					$source->getStackId()
				),
				new v486ItemStackRequestSlotInfo(
					self::remapContainerIdToLegacy($destination->getContainerName()->getContainerId()),
					$destination->getSlotId(),
					$destination->getStackId()
				)
			))->write($serializer, $protocolId);
			return;
		}elseif($action instanceof PlaceStackRequestAction){
			$source = $action->getSource();
			$destination = $action->getDestination();
			(new v486PlaceStackRequestAction(
				$action->getCount(),
				new v486ItemStackRequestSlotInfo(
					self::remapContainerIdToLegacy($source->getContainerName()->getContainerId()),
					$source->getSlotId(),
					$source->getStackId()
				),
				new v486ItemStackRequestSlotInfo(
					self::remapContainerIdToLegacy($destination->getContainerName()->getContainerId()),
					$destination->getSlotId(),
					$destination->getStackId()
				)
			))->write($serializer, $protocolId);
			return;
		}elseif($action instanceof v486CraftingConsumeInputStackRequestAction || $action instanceof v486DestroyStackRequestAction){
			$source = self::remapLegacySlotInfoToLegacy($action->getSource());
			if($source !== $action->getSource()){
				ReflectionUtils::setProperty(get_class($action), $action, "source", $source);
			}
		}elseif($action instanceof CraftingConsumeInputStackRequestAction || $action instanceof DestroyStackRequestAction){
			$source = self::remapCoreSlotInfoToLegacy($action->getSource());
			if($source !== $action->getSource()){
				ReflectionUtils::setProperty(get_class($action), $action, "source", $source);
			}
		}elseif(
			$action instanceof v486PlaceStackRequestAction ||
			$action instanceof v486TakeStackRequestAction
		){
			$source = self::remapLegacySlotInfoToLegacy($action->getSource());
			if($source !== $action->getSource()){
				ReflectionUtils::setProperty(get_class($action), $action, "source", $source);
			}
			$destination = self::remapLegacySlotInfoToLegacy($action->getDestination());
			if($destination !== $action->getDestination()){
				ReflectionUtils::setProperty(get_class($action), $action, "destination", $destination);
			}
		}elseif($action instanceof v486CraftRecipeAutoStackRequestAction){
			$action = new CraftRecipeAutoStackRequestAction($action->getRecipeId(), $action->getRepetitions(), $action->getRepetitions(), $action->getIngredients());
		}
		if(
			$action instanceof v486TakeStackRequestAction ||
			$action instanceof v486PlaceStackRequestAction ||
			$action instanceof v486SwapStackRequestAction ||
			$action instanceof v486DropStackRequestAction ||
			$action instanceof v486DestroyStackRequestAction ||
			$action instanceof v486CraftingConsumeInputStackRequestAction ||
			$action instanceof v486CraftRecipeAutoStackRequestAction ||
			$action instanceof v486CraftRecipeStackRequestAction ||
			$action instanceof v486CreativeCreateStackRequestAction ||
			$action instanceof v486GrindstoneStackRequestAction
		){
			$action->write($serializer, $protocolId);
		}else{
			$action->write($serializer->getWriter(), $protocolId);
		}
	}

	public function write(ByteBufferWriter|PacketSerializer $out, int $protocolId = 486) : void{
		$serializer = $out instanceof PacketSerializer ? $out : PacketSerializer::writer($out);
		$serializer->putVarInt($this->requestId);
		$serializer->putUnsignedVarInt(count($this->actions));
		foreach($this->actions as $action){
			$serializer->putByte($action->getTypeId());
			self::writeAction($serializer, $action, $protocolId);
		}
		$serializer->putUnsignedVarInt(count($this->filterStrings));
		foreach($this->filterStrings as $string){
			$serializer->putString($string);
		}
	}
}
