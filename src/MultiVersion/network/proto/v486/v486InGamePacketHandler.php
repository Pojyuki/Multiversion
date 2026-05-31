<?php

namespace MultiVersion\network\proto\v486;

use MultiVersion\network\proto\utils\ReflectionUtils;
use MultiVersion\network\proto\v486\packets\types\v486ItemStackRequestExecutor;
use pocketmine\entity\Attribute;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\inventory\transaction\TransactionCancelledException;
use pocketmine\inventory\transaction\TransactionValidationException;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\handler\InGamePacketHandler;
use pocketmine\network\mcpe\handler\ItemStackContainerIdTranslator;
use pocketmine\network\mcpe\handler\ItemStackRequestExecutor;
use pocketmine\network\mcpe\handler\ItemStackRequestProcessException;
use pocketmine\network\mcpe\InventoryManager;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ItemStackRequestPacket;
use pocketmine\network\mcpe\protocol\ItemStackResponsePacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\inventory\MismatchTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequest;
use pocketmine\network\mcpe\protocol\types\inventory\stackresponse\ItemStackResponse;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Utils;
use ReflectionException;

class v486InGamePacketHandler extends InGamePacketHandler{
	private InventoryManager $inventoryManager;
	private static ?bool $worldCreateBlockUpdatePacketsExpectsTypeConverter = null;

	public function __construct(Player $player, NetworkSession $session, InventoryManager $inventoryManager){
		$this->inventoryManager = $inventoryManager;
		parent::__construct($player, $session, $inventoryManager);
	}

	public function handleInventoryTransaction(InventoryTransactionPacket $packet) : bool{
		$result = true;

		if(count($packet->trData->getActions()) > 50){
			throw new PacketHandlingException("Too many actions in inventory transaction");
		}
		if(count($packet->requestChangedSlots) > 10){
			throw new PacketHandlingException("Too many slot sync requests in inventory transaction");
		}

		$this->inventoryManager->setCurrentItemStackRequestId($packet->requestId);
		$this->inventoryManager->addRawPredictedSlotChanges($packet->trData->getActions());

		if($packet->trData instanceof NormalTransactionData){
			$result = ReflectionUtils::invoke(InGamePacketHandler::class, $this, "handleNormalTransaction", $packet->trData, $packet->requestId);
		}elseif($packet->trData instanceof MismatchTransactionData){
			$this->getSession()->getLogger()->debug("Mismatch transaction received");
			$this->inventoryManager->requestSyncAll();
		}elseif($packet->trData instanceof UseItemTransactionData){
			$result = $this->handleUseItemTransaction($packet->trData);
		}elseif($packet->trData instanceof UseItemOnEntityTransactionData){
			$result = ReflectionUtils::invoke(InGamePacketHandler::class, $this, "handleUseItemOnEntityTransaction", $packet->trData);
		}elseif($packet->trData instanceof ReleaseItemTransactionData){
			$result = ReflectionUtils::invoke(InGamePacketHandler::class, $this, "handleReleaseItemTransaction", $packet->trData);
		}

		$this->inventoryManager->syncMismatchedPredictedSlotChanges();

		foreach($packet->requestChangedSlots as $containerInfo){
			foreach($containerInfo->getChangedSlotIndexes() as $netSlot){
				[$windowId, $slot] = ItemStackContainerIdTranslator::translate($containerInfo->getContainerId(), $this->inventoryManager->getCurrentWindowId(), $netSlot);
				$inventoryAndSlot = $this->inventoryManager->locateWindowAndSlot($windowId, $slot);
				if($inventoryAndSlot !== null){
					$this->inventoryManager->onSlotChange($inventoryAndSlot[0], $inventoryAndSlot[1]);
				}
			}
		}

		$this->inventoryManager->setCurrentItemStackRequestId(null);
		return $result;
	}

	private function handleUseItemTransaction(UseItemTransactionData $data) : bool{
		$player = $this->getPlayer();
		$player->selectHotbarSlot($data->getHotbarSlot());

		switch($data->getActionType()){
			case UseItemTransactionData::ACTION_CLICK_BLOCK:
				$clickPos = $data->getClickPosition();
				$spamBug = ($this->lastRightClickData !== null &&
					microtime(true) - $this->lastRightClickTime < 0.1 &&
					$this->lastRightClickData->getPlayerPosition()->distanceSquared($data->getPlayerPosition()) < 0.00001 &&
					$this->lastRightClickData->getBlockPosition()->equals($data->getBlockPosition()) &&
					$this->lastRightClickData->getClickPosition()->distanceSquared($clickPos) < 0.00001
				);
				$this->lastRightClickData = $data;
				$this->lastRightClickTime = microtime(true);
				if($spamBug){
					return true;
				}

				self::validateFacing($data->getFace());

				$blockPos = $data->getBlockPosition();
				$vBlockPos = new Vector3($blockPos->getX(), $blockPos->getY(), $blockPos->getZ());
				// Protocol 486 keeps its predicted placement unless rejected interactions are explicitly rolled back.
				if(!$player->interactBlock($vBlockPos, $data->getFace(), $clickPos)){
					$this->syncBlocksNearby($vBlockPos, $data->getFace());
				}
				return true;
			case UseItemTransactionData::ACTION_BREAK_BLOCK:
				$blockPos = $data->getBlockPosition();
				$vBlockPos = new Vector3($blockPos->getX(), $blockPos->getY(), $blockPos->getZ());
				if(!$player->breakBlock($vBlockPos)){
					$this->syncBlocksNearby($vBlockPos, null);
				}
				return true;
			case UseItemTransactionData::ACTION_CLICK_AIR:
				if($player->isUsingItem()){
					if(!$player->consumeHeldItem()){
						$hungerAttr = $player->getAttributeMap()->get(Attribute::HUNGER) ?? throw new AssumptionFailedError();
						$hungerAttr->markSynchronized(false);
					}
					return true;
				}
				$player->useHeldItem();
				return true;
		}

		return false;
	}


	private function handleSingleItemStackRequest(ItemStackRequest $request) : ItemStackResponse{
		if(count($request->getActions()) > 60){
			throw new PacketHandlingException("Too many actions in ItemStackRequest");
		}
		$executor = new v486ItemStackRequestExecutor($this->getPlayer(), $this->getSession()->getInvManager(), $request);
		try{
			$transaction = $executor->generateInventoryTransaction();
			$result = $this->executeInventoryTransaction($transaction, $request->getRequestId());
		}catch(ItemStackRequestProcessException $e){
			$result = false;
			$this->getSession()->getLogger()->debug("ItemStackRequest #" . $request->getRequestId() . " failed: " . $e->getMessage());
			$this->getSession()->getLogger()->debug(implode("\n", Utils::printableExceptionInfo($e)));
			$this->inventoryManager->requestSyncAll();
		}

		if(!$result){
			return new ItemStackResponse(ItemStackResponse::RESULT_ERROR, $request->getRequestId());
		}
		return $executor->buildItemStackResponse();
	}

	private function executeInventoryTransaction(InventoryTransaction $transaction, int $requestId) : bool{
		$this->getPlayer()->setUsingItem(false);

		$this->inventoryManager->setCurrentItemStackRequestId($requestId);
		$this->inventoryManager->addTransactionPredictedSlotChanges($transaction);
		try{
			$transaction->execute();
		}catch(TransactionValidationException $e){
			$this->inventoryManager->requestSyncAll();
			$logger = $this->getSession()->getLogger();
			$logger->debug("Invalid inventory transaction $requestId: " . $e->getMessage());

			return false;
		}catch(TransactionCancelledException){
			$this->getSession()->getLogger()->debug("Inventory transaction $requestId cancelled by a plugin");

			return false;
		}finally{
			$this->inventoryManager->syncMismatchedPredictedSlotChanges();
			$this->inventoryManager->setCurrentItemStackRequestId(null);
		}

		return true;
	}

	public function handleItemStackRequest(ItemStackRequestPacket $packet) : bool{
		$responses = [];
		if(count($packet->getRequests()) > 80){
			//TODO: we can probably lower this limit, but this will do for now
			throw new PacketHandlingException("Too many requests in ItemStackRequestPacket");
		}
		foreach($packet->getRequests() as $request){
			$responses[] = $this->handleSingleItemStackRequest($request);
		}

		$this->getSession()->sendDataPacket(ItemStackResponsePacket::create($responses));

		return true;
	}

	public function handleEmote(EmotePacket $packet): bool{
		$this->getPlayer()->emote($packet->getEmoteId(), 20);
		return true;
	}

	public function handleModalFormResponse(ModalFormResponsePacket $packet) : bool{
		$player = $this->getPlayer();
		if(!$player->hasPendingForm($packet->formId) && ($packet->cancelReason !== null || self::isLegacyNullFormResponse($packet->formData))){
			// Legacy clients may submit stale "null" responses for already-closed forms.
			return true;
		}

		return parent::handleModalFormResponse($packet);
	}

	private static function isLegacyNullFormResponse(?string $formData) : bool{
		return $formData !== null && strtolower(trim($formData)) === "null";
	}

	private function syncBlocksNearby(Vector3 $blockPos, ?int $face) : void{
		$player = $this->getPlayer();
		if($blockPos->distanceSquared($player->getLocation()) < 10000){
			$blocks = $blockPos->sidesArray();
			if($face !== null){
				$sidePos = $blockPos->getSide($face);
				array_push($blocks, ...$sidePos->sidesArray());
			}else{
				$blocks[] = $blockPos;
			}
			foreach($this->createBlockUpdatePacketsCompat($blocks) as $packet){
				$this->getSession()->sendDataPacket($packet);
			}
		}
	}

	private function createBlockUpdatePacketsCompat(array $blocks) : array{
		$world = $this->getPlayer()->getWorld();
		if(self::$worldCreateBlockUpdatePacketsExpectsTypeConverter === null){
			$method = new \ReflectionMethod($world, "createBlockUpdatePackets");
			$parameters = $method->getParameters();
			self::$worldCreateBlockUpdatePacketsExpectsTypeConverter = isset($parameters[0]) &&
				($parameters[0]->getType() instanceof \ReflectionNamedType) &&
				is_a($parameters[0]->getType()->getName(), TypeConverter::class, true);
		}

		if(self::$worldCreateBlockUpdatePacketsExpectsTypeConverter){
			// Keep outgoing packets in the latest runtime-ID space. The session translator downgrades them once.
			return $world->createBlockUpdatePackets(self::getLatestTypeConverter(), $blocks);
		}

		return $world->createBlockUpdatePackets($blocks);
	}

	private static function getLatestTypeConverter() : TypeConverter{
		$getInstance = new \ReflectionMethod(TypeConverter::class, "getInstance");
		if($getInstance->getNumberOfRequiredParameters() >= 1){
			return TypeConverter::getInstance(ProtocolInfo::CURRENT_PROTOCOL);
		}

		return TypeConverter::getInstance();
	}

	private static function validateFacing(int $facing) : void{
		if(!in_array($facing, Facing::ALL, true)){
			throw new PacketHandlingException("Invalid facing value $facing");
		}
	}

	private function getSession() : NetworkSession{
		return ReflectionUtils::getProperty(InGamePacketHandler::class, $this, "session");
	}

	private function getPlayer() : Player{
		return ReflectionUtils::getProperty(InGamePacketHandler::class, $this, "player");
	}
}
