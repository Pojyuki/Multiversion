<?php

namespace MultiVersion\network\proto\v486;

use MultiVersion\Loader;
use MultiVersion\network\MVNetworkSession;
use MultiVersion\network\proto\latest\LatestChunkSerializerWrapper;
use MultiVersion\network\proto\v419\packets\v419BiomeDefinitionListPacket;
use MultiVersion\network\proto\v486\packets\types\v486CreativeContentEntry;
use MultiVersion\network\proto\v486\packets\v486ActorEventPacket;
use MultiVersion\network\proto\v486\packets\v486BiomeDefinitionListPacket;
use MultiVersion\network\proto\v486\packets\v486CreativeContentPacket;
use MultiVersion\network\proto\v486\packets\v486MobArmorEquipmentPacket;
use MultiVersion\network\proto\v486\packets\v486MobEquipmentPacket;
use MultiVersion\network\proto\v486\packets\v486SetTitlePacket;
use MultiVersion\network\proto\v486\packets\v486TransferPacket;
use MultiVersion\network\proto\v486\packets\v486UpdateAttributesPacket;
use pocketmine\network\mcpe\protocol\ItemRegistryPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use MultiVersion\network\proto\PacketTranslator;
use MultiVersion\network\proto\static\MVRuntimeIDtoStateID;
use MultiVersion\network\proto\v486\packets\v486AddActorPacket;
use MultiVersion\network\proto\v486\packets\v486AddPlayerPacket;
use MultiVersion\network\proto\v486\packets\v486AddVolumeEntityPacket;
use MultiVersion\network\proto\v486\packets\v486AdventureSettingsPacket;
use MultiVersion\network\proto\v486\packets\v486AnimatePacket;
use MultiVersion\network\proto\v486\packets\v486AvailableCommandsPacket;
use MultiVersion\network\proto\v486\packets\v486ClientboundMapItemDataPacket;
use MultiVersion\network\proto\v486\packets\v486ContainerClosePacket;
use MultiVersion\network\proto\v486\packets\v486CraftingDataPacket;
use MultiVersion\network\proto\v486\packets\v486DisconnectPacket;
use MultiVersion\network\proto\v486\packets\v486EmotePacket;
use MultiVersion\network\proto\v486\packets\v486InventoryContentPacket;
use MultiVersion\network\proto\v486\packets\v486InventorySlotPacket;
use MultiVersion\network\proto\v486\packets\v486InventoryTransactionPacket;
use MultiVersion\network\proto\v486\packets\v486ItemStackResponsePacket;
use MultiVersion\network\proto\v486\packets\v486LevelChunkPacket;
use MultiVersion\network\proto\v486\packets\v486LevelSoundEventPacket;
use MultiVersion\network\proto\v486\packets\v486MobEffectPacket;
use MultiVersion\network\proto\v486\packets\v486ModalFormResponsePacket;
use MultiVersion\network\proto\v486\packets\v486NetworkChunkPublisherUpdatePacket;
use MultiVersion\network\proto\v486\packets\v486NetworkSettingsPacket;
use MultiVersion\network\proto\v486\packets\v486PacketPool;
use MultiVersion\network\proto\v486\packets\v486PlaySoundPacket;
use MultiVersion\network\proto\v486\packets\v486PlayerActionPacket;
use MultiVersion\network\proto\v486\packets\v486PlayerAuthInputPacket;
use MultiVersion\network\proto\v486\packets\v486PlayerListPacket;
use MultiVersion\network\proto\v486\packets\v486PlayerSkinPacket;
use MultiVersion\network\proto\v486\packets\v486RemoveVolumeEntityPacket;
use MultiVersion\network\proto\v486\packets\v486RequestChunkRadiusPacket;
use MultiVersion\network\proto\v486\packets\v486ResourcePacksInfoPacket;
use MultiVersion\network\proto\v486\packets\v486ResourcePackStackPacket;
use MultiVersion\network\proto\v486\packets\v486SetActorDataPacket;
use MultiVersion\network\proto\v486\packets\v486SetActorMotionPacket;
use MultiVersion\network\proto\v486\packets\v486SpawnParticleEffectPacket;
use MultiVersion\network\proto\v486\packets\v486StartGamePacket;
use MultiVersion\network\proto\v486\packets\v486StopSoundPacket;
use MultiVersion\network\proto\v486\packets\v486TextPacket;
use pmmp\encoding\ByteBufferWriter;
use MultiVersion\network\proto\v486\packets\v486UpdateBlockPacket;
use pocketmine\inventory\CreativeInventory;
use pocketmine\network\mcpe\compression\ZlibCompressor;
use pocketmine\network\mcpe\convert\ItemTranslator;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\handler\InGamePacketHandler;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\AddVolumeEntityPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\BiomeDefinitionListPacket;
use pocketmine\network\mcpe\protocol\ClientboundMapItemDataPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\CodeBuilderSourcePacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\CreativeContentPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ItemStackResponsePacket;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\mcpe\protocol\NetworkSettingsPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\network\mcpe\protocol\RemoveVolumeEntityPacket;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\serializer\NetworkNbtSerializer;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;
use pocketmine\network\mcpe\protocol\SetTitlePacket;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\StopSoundPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\TransferPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesLayer;
use pocketmine\network\mcpe\protocol\types\ActorEvent;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\network\mcpe\protocol\types\ParticleIds;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\network\mcpe\protocol\UpdateAdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\Server;
use pocketmine\utils\AssumptionFailedError;
use ReflectionException;
use RuntimeException;
use Symfony\Component\Filesystem\Path;

class v486PacketTranslator extends PacketTranslator{

	public const PROTOCOL_VERSION = 486;
	public const RAKNET_VERSION = 10;
	public const OLD_COMPRESSION = true;
	public const ENCRYPTION_CONTEXT = true;

	private string $craftingDataCache;

	private CreativeContentPacket $creativeContent;

	private v486BiomeDefinitionListPacket $biomeDefs;

	private AvailableActorIdentifiersPacket $availableActorIdentifiers;

	public function __construct(){
		$typeConverter = v486TypeConverter::getInstance()->getTypeConverter();

		$craftingDP = new v486CraftingDataPacket();
		$craftingDP->cleanRecipes = true;
		$entries = [];
		foreach(CreativeInventory::getInstance()->getAll() as $k => $item){
			try{
				$entries[] = new v486CreativeContentEntry($k, $typeConverter->coreItemStackToNet($item));
			}catch(AssumptionFailedError){

			}
		}
        $this->creativeContent = new v486CreativeContentPacket();
        $this->creativeContent->entries = $entries;

		$this->biomeDefs = v486BiomeDefinitionListPacket::v486create(self::loadCompoundFromFile(Path::join(Loader::getPluginResourcePath(), "v486", "biome_definitions.nbt")), []);
		$this->availableActorIdentifiers = AvailableActorIdentifiersPacket::create(self::loadCompoundFromFile(Path::join(Loader::getPluginResourcePath(), "v486", "entity_identifiers.nbt")));

		parent::__construct($typeConverter, $factory = new v486PacketSerializerFactory(new LatestChunkSerializerWrapper()), new v486PacketPool(), ZlibCompressor::getInstance());

		$writer = new ByteBufferWriter();
		if((new \ReflectionMethod($craftingDP, "encode"))->getNumberOfParameters() >= 2){
			$craftingDP->encode($writer, self::PROTOCOL_VERSION);
		}else{
			$craftingDP->encode($writer);
		}
		$this->craftingDataCache = $writer->getData();
	}

	/**
	 * @param string $path
	 *
	 * @return CacheableNbt
	 */
	private static function loadCompoundFromFile(string $path) : CacheableNbt{
		$rawNbt = @file_get_contents($path);
		if($rawNbt === false){
			throw new RuntimeException("Failed to read file");
		}
		return new CacheableNbt((new NetworkNbtSerializer())->read($rawNbt)->mustGetCompoundTag());
	}

	public function handleInGame(MVNetworkSession $session) : ?v486InGamePacketHandler{
		return new v486InGamePacketHandler($session->getPlayer(), $session, $session->getInvManager());
	}

	public function handleIncoming(ServerboundPacket $pk, MVNetworkSession $session = null) : ?ServerboundPacket{
		// Legacy clients may spam these client-origin packets; server logic doesn't need them.
		if($pk instanceof MovePlayerPacket || $pk instanceof LevelSoundEventPacket){
			return null;
		}
		if($pk instanceof v486DisconnectPacket){
			return DisconnectPacket::create(0, $pk->message, $pk->filteredMessage);
		}
		if($pk instanceof v486InventoryTransactionPacket){
			return InventoryTransactionPacket::create($pk->requestId, $pk->requestChangedSlots, $pk->trData);
		}
		if($pk instanceof v486ModalFormResponsePacket){
			if($pk->cancelReason !== null){
				return ModalFormResponsePacket::cancel($pk->formId, $pk->cancelReason);
			}
			return ModalFormResponsePacket::response($pk->formId, $pk->formData ?? "null");
		}
		if($pk instanceof v486PlayerActionPacket){
			return PlayerActionPacket::create($pk->actorRuntimeId, $pk->action, $pk->blockPosition, $pk->blockPosition, $pk->face);
		}
		if($pk instanceof v486RequestChunkRadiusPacket){
			return RequestChunkRadiusPacket::create($pk->radius, $pk->radius);
		}
		if($pk instanceof v486SetActorMotionPacket){
			return SetActorMotionPacket::create($pk->actorRuntimeId, $pk->motion, 0);
		}

        if($pk instanceof ContainerClosePacket){
            return v486ContainerClosePacket::fromLatest($pk);
        }
		return $pk;
	}

	/**
	 * @throws ReflectionException
	 */
	public function handleOutgoing(ClientboundPacket $pk, MVNetworkSession $session = null) : ?ClientboundPacket{
		if($pk instanceof ActorEventPacket){
			$eventData = $pk->eventData;
			if($pk->eventId === ActorEvent::EATING_ITEM){
				$value = $eventData;
				$netId = $value >> 16;
				$netData = $value & 0xffff;
				[$netId, $netData] = v486TypeConverter::getInstance()->getTypeConverter()->getMVItemTranslator()->toNetworkId(TypeConverter::getInstance()->getItemTranslator()->fromNetworkId($netId, $netData, ItemTranslator::NO_BLOCK_RUNTIME_ID));
				$eventData = ($netId << 16) | $netData;
			}
			return v486ActorEventPacket::fromLatest($pk, $eventData);
		}
		if($pk instanceof SetTitlePacket) return v486SetTitlePacket::fromLatest($pk);
		if($pk instanceof MobArmorEquipmentPacket) return v486MobArmorEquipmentPacket::fromLatest($pk);
		if($pk instanceof MobEquipmentPacket) return v486MobEquipmentPacket::fromLatest($pk);
		if($pk instanceof AddActorPacket) return v486AddActorPacket::fromLatest($pk);
		if($pk instanceof AddPlayerPacket) return v486AddPlayerPacket::fromLatest($pk);
		if($pk instanceof AddVolumeEntityPacket) return v486AddVolumeEntityPacket::fromLatest($pk);
		if($pk instanceof AnimatePacket) return v486AnimatePacket::fromLatest($pk);
		if($pk instanceof AvailableActorIdentifiersPacket) return $this->availableActorIdentifiers;
		if($pk instanceof AvailableCommandsPacket) return v486AvailableCommandsPacket::fromLatest($pk);
		if($pk instanceof BiomeDefinitionListPacket) return $this->biomeDefs;
		if($pk instanceof ClientboundMapItemDataPacket) return v486ClientboundMapItemDataPacket::fromLatest($pk);
		if($pk instanceof CreativeContentPacket) return clone $this->creativeContent;
		if($pk instanceof DisconnectPacket) return v486DisconnectPacket::fromLatest($pk);
		//if($pk instanceof EmotePacket) return v486EmotePacket::fromLatest($pk);
		if($pk instanceof InventoryContentPacket) return v486InventoryContentPacket::fromLatest($pk);
		if($pk instanceof InventorySlotPacket) return v486InventorySlotPacket::fromLatest($pk);
		if($pk instanceof InventoryTransactionPacket) return v486InventoryTransactionPacket::fromLatest($pk);
		if($pk instanceof ItemStackResponsePacket) return v486ItemStackResponsePacket::fromLatest($pk);
		if($pk instanceof LevelChunkPacket) return v486LevelChunkPacket::fromLatest($pk);
		if($pk instanceof LevelEventPacket){
			if($pk->eventId === LevelEvent::PARTICLE_DESTROY || $pk->eventId === (LevelEvent::ADD_PARTICLE_MASK | ParticleIds::TERRAIN)){
				$pk->eventData = v486TypeConverter::getInstance()->getTypeConverter()->getMVBlockTranslator()->internalIdToNetworkId(MVRuntimeIDtoStateID::getInstance()->getStateIdFromRuntimeId($pk->eventData));

			}elseif($pk->eventId === LevelEvent::PARTICLE_PUNCH_BLOCK){
				$pk->eventData = v486TypeConverter::getInstance()->getTypeConverter()->getMVBlockTranslator()->internalIdToNetworkId(MVRuntimeIDtoStateID::getInstance()->getStateIdFromRuntimeId($pk->eventData & 0xFFFFFF));
			}
			$pk->eventId = self::downgradeParticleEventId($pk->eventId);
			return $pk;
		}
			if($pk instanceof LevelSoundEventPacket){
				if(($pk->sound === LevelSoundEvent::BREAK && $pk->extraData !== -1) || $pk->sound === LevelSoundEvent::PLACE || $pk->sound === LevelSoundEvent::HIT || $pk->sound === LevelSoundEvent::LAND || $pk->sound === LevelSoundEvent::ITEM_USE_ON){
					$pk->extraData = v486TypeConverter::getInstance()->getTypeConverter()->getMVBlockTranslator()->internalIdToNetworkId(MVRuntimeIDtoStateID::getInstance()->getStateIdFromRuntimeId($pk->extraData));
				}
				return v486LevelSoundEventPacket::fromLatest($pk);
			}
			if($pk instanceof PlaySoundPacket) return v486PlaySoundPacket::fromLatest($pk);
			if($pk instanceof StopSoundPacket) return v486StopSoundPacket::fromLatest($pk);
			if($pk instanceof EmotePacket) return v486EmotePacket::fromLatest($pk);
        if($pk instanceof ContainerClosePacket) return v486ContainerClosePacket::fromLatest($pk);
		if($pk instanceof MobEffectPacket) return v486MobEffectPacket::fromLatest($pk);
		if($pk instanceof NetworkChunkPublisherUpdatePacket) return v486NetworkChunkPublisherUpdatePacket::fromLatest($pk);
		if($pk instanceof NetworkSettingsPacket) return v486NetworkSettingsPacket::fromLatest($pk);
		if($pk instanceof PlayerListPacket){
			foreach($pk->entries as $key => $entry){
				if(!isset($entry->skinData)) continue;
				$pk->entries[$key]->skinData = $this->convertSkinData($entry->skinData);
			}
			return v486PlayerListPacket::fromLatest($pk);
		}
		if($pk instanceof PlayerSkinPacket){
			$pk->skin = $this->convertSkinData($pk->skin);
			return v486PlayerSkinPacket::fromLatest($pk);
		}
		if($pk instanceof RemoveVolumeEntityPacket) return v486RemoveVolumeEntityPacket::fromLatest($pk);
		if($pk instanceof ResourcePacksInfoPacket) return v486ResourcePacksInfoPacket::fromLatest($pk);
		if($pk instanceof ResourcePackStackPacket) return v486ResourcePackStackPacket::fromLatest($pk);
		if($pk instanceof SetActorDataPacket) return v486SetActorDataPacket::fromLatest($pk);
        if($pk instanceof TextPacket) return v486TextPacket::fromLatest($pk);
		if($pk instanceof SetActorMotionPacket) return v486SetActorMotionPacket::fromLatest($pk);
		if($pk instanceof SpawnParticleEffectPacket) return v486SpawnParticleEffectPacket::fromLatest($pk);
		if($pk instanceof StartGamePacket) return v486StartGamePacket::fromLatest($pk);
        if($pk instanceof UpdateAttributesPacket) return v486UpdateAttributesPacket::fromLatest($pk);
		if($pk instanceof UpdateAbilitiesPacket){
			foreach(Server::getInstance()->getWorldManager()->getWorlds() as $world){
				$player = $world->getPlayers()[$pk->getData()->getTargetActorUniqueId()] ?? null;
				if($player === null) continue;
				if($player->getId() === $pk->getData()->getTargetActorUniqueId()){
					$npk = v486AdventureSettingsPacket::create(0, $pk->getData()->getCommandPermission(), -1, $pk->getData()->getPlayerPermission(), 0, $pk->getData()->getTargetActorUniqueId());
					if(isset($pk->getData()->getAbilityLayers()[0])){
						$abilities = $pk->getData()->getAbilityLayers()[0]->getBoolAbilities();
						$npk->setFlag(v486AdventureSettingsPacket::WORLD_IMMUTABLE, $player->isSpectator());
						$npk->setFlag(v486AdventureSettingsPacket::NO_PVP, $player->isSpectator());
						$npk->setFlag(v486AdventureSettingsPacket::AUTO_JUMP, $player->hasAutoJump());
						$npk->setFlag(v486AdventureSettingsPacket::ALLOW_FLIGHT, $abilities[AbilitiesLayer::ABILITY_ALLOW_FLIGHT] ?? false);
						$npk->setFlag(v486AdventureSettingsPacket::NO_CLIP, $abilities[AbilitiesLayer::ABILITY_NO_CLIP] ?? false);
						$npk->setFlag(v486AdventureSettingsPacket::FLYING, $abilities[AbilitiesLayer::ABILITY_FLYING] ?? false);
					}
					return $npk;
				}
			}
		}
        if($pk instanceof TransferPacket) return v486TransferPacket::fromLatest($pk);
		if($pk instanceof CodeBuilderSourcePacket) return null;
		if($pk instanceof UpdateAdventureSettingsPacket) return null;
		if($pk instanceof ItemRegistryPacket) return null;
		if($pk instanceof UpdateBlockPacket){
			// Legacy sessions may still receive modern runtime IDs from core world updates.
			// Convert latest runtime ID -> state ID -> legacy runtime ID for protocol 486.
			$pk->blockRuntimeId = v486TypeConverter::getInstance()->getTypeConverter()->getMVBlockTranslator()->internalIdToNetworkId(
				MVRuntimeIDtoStateID::getInstance()->getStateIdFromRuntimeId($pk->blockRuntimeId)
			);
			return v486UpdateBlockPacket::fromLatest($pk);
		}
		return $pk;
	}

	private static function downgradeParticleEventId(int $eventId) : int{
		if(($eventId & LevelEvent::ADD_PARTICLE_MASK) === 0){
			return $eventId;
		}

		$particleId = $eventId & ~LevelEvent::ADD_PARTICLE_MASK;
		// 1.18 already has candle flame, but predates the breeze-wind-explosion insertion.
		if($particleId >= ParticleIds::BREEZE_WIND_EXPLOSION){
			--$particleId;
		}

		return LevelEvent::ADD_PARTICLE_MASK | $particleId;
	}

	public function injectClientData(array &$data) : void{
		$data['MaxViewDistance'] = 7;
		$data["IsEditorMode"] = false;
		$data["TrustedSkin"] = true;
		$data["CompatibleWithClientSideChunkGen"] = false;
	}

	public function convertSkinData(SkinData $skin) : SkinData{
		return new SkinData(
			$skin->getSkinId(),
			$skin->getPlayFabId(),
			$skin->getResourcePatch(),
			$skin->getSkinImage(),
			$skin->getAnimations(),
			$skin->getCapeImage(),
			$skin->getGeometryData() !== "" && str_contains($skin->getGeometryData(), "format_version") ? $skin->getGeometryData() : '{"format_version":"1.12.0","minecraft:geometry":[{"bones":[{"name":"body","parent":"waist","pivot":[0,24,0]},{"name":"waist","pivot":[0,12,0]},{"cubes":[{"origin":[-5,8,3],"size":[10,16,1],"uv":[0,0]}],"name":"cape","parent":"body","pivot":[0,24,3],"rotation":[0,180,0]}],"description":{"identifier":"geometry.cape","texture_height":32,"texture_width":64}},{"bones":[{"name":"root","pivot":[0,0,0]},{"cubes":[{"origin":[-4,12,-2],"size":[8,12,4],"uv":[16,16]}],"name":"body","parent":"waist","pivot":[0,24,0]},{"name":"waist","parent":"root","pivot":[0,12,0]},{"cubes":[{"origin":[-4,24,-4],"size":[8,8,8],"uv":[0,0]}],"name":"head","parent":"body","pivot":[0,24,0]},{"name":"cape","parent":"body","pivot":[0,24,3]},{"cubes":[{"inflate":0.5,"origin":[-4,24,-4],"size":[8,8,8],"uv":[32,0]}],"name":"hat","parent":"head","pivot":[0,24,0]},{"cubes":[{"origin":[4,12,-2],"size":[4,12,4],"uv":[32,48]}],"name":"leftArm","parent":"body","pivot":[5,22,0]},{"cubes":[{"inflate":0.25,"origin":[4,12,-2],"size":[4,12,4],"uv":[48,48]}],"name":"leftSleeve","parent":"leftArm","pivot":[5,22,0]},{"name":"leftItem","parent":"leftArm","pivot":[6,15,1]},{"cubes":[{"origin":[-8,12,-2],"size":[4,12,4],"uv":[40,16]}],"name":"rightArm","parent":"body","pivot":[-5,22,0]},{"cubes":[{"inflate":0.25,"origin":[-8,12,-2],"size":[4,12,4],"uv":[40,32]}],"name":"rightSleeve","parent":"rightArm","pivot":[-5,22,0]},{"locators":{"lead_hold":[-6,15,1]},"name":"rightItem","parent":"rightArm","pivot":[-6,15,1]},{"cubes":[{"origin":[-0.1,0,-2],"size":[4,12,4],"uv":[16,48]}],"name":"leftLeg","parent":"root","pivot":[1.9,12,0]},{"cubes":[{"inflate":0.25,"origin":[-0.1,0,-2],"size":[4,12,4],"uv":[0,48]}],"name":"leftPants","parent":"leftLeg","pivot":[1.9,12,0]},{"cubes":[{"origin":[-3.9,0,-2],"size":[4,12,4],"uv":[0,16]}],"name":"rightLeg","parent":"root","pivot":[-1.9,12,0]},{"cubes":[{"inflate":0.25,"origin":[-3.9,0,-2],"size":[4,12,4],"uv":[0,32]}],"name":"rightPants","parent":"rightLeg","pivot":[-1.9,12,0]},{"cubes":[{"inflate":0.25,"origin":[-4,12,-2],"size":[8,12,4],"uv":[16,32]}],"name":"jacket","parent":"body","pivot":[0,24,0]}],"description":{"identifier":"geometry.humanoid.custom","texture_height":64,"texture_width":64,"visible_bounds_height":2,"visible_bounds_offset":[0,1,0],"visible_bounds_width":1}},{"bones":[{"name":"root","pivot":[0,0,0]},{"name":"waist","parent":"root","pivot":[0,12,0]},{"cubes":[{"origin":[-4,12,-2],"size":[8,12,4],"uv":[16,16]}],"name":"body","parent":"waist","pivot":[0,24,0]},{"cubes":[{"origin":[-4,24,-4],"size":[8,8,8],"uv":[0,0]}],"name":"head","parent":"body","pivot":[0,24,0]},{"cubes":[{"inflate":0.5,"origin":[-4,24,-4],"size":[8,8,8],"uv":[32,0]}],"name":"hat","parent":"head","pivot":[0,24,0]},{"cubes":[{"origin":[-3.9,0,-2],"size":[4,12,4],"uv":[0,16]}],"name":"rightLeg","parent":"root","pivot":[-1.9,12,0]},{"cubes":[{"inflate":0.25,"origin":[-3.9,0,-2],"size":[4,12,4],"uv":[0,32]}],"name":"rightPants","parent":"rightLeg","pivot":[-1.9,12,0]},{"cubes":[{"origin":[-0.1,0,-2],"size":[4,12,4],"uv":[16,48]}],"name":"leftLeg","parent":"root","pivot":[1.9,12,0]},{"cubes":[{"inflate":0.25,"origin":[-0.1,0,-2],"size":[4,12,4],"uv":[0,48]}],"name":"leftPants","parent":"leftLeg","pivot":[1.9,12,0]},{"cubes":[{"origin":[4,11.5,-2],"size":[3,12,4],"uv":[32,48]}],"name":"leftArm","parent":"body","pivot":[5,21.5,0]},{"cubes":[{"inflate":0.25,"origin":[4,11.5,-2],"size":[3,12,4],"uv":[48,48]}],"name":"leftSleeve","parent":"leftArm","pivot":[5,21.5,0]},{"name":"leftItem","parent":"leftArm","pivot":[6,14.5,1]},{"cubes":[{"origin":[-7,11.5,-2],"size":[3,12,4],"uv":[40,16]}],"name":"rightArm","parent":"body","pivot":[-5,21.5,0]},{"cubes":[{"inflate":0.25,"origin":[-7,11.5,-2],"size":[3,12,4],"uv":[40,32]}],"name":"rightSleeve","parent":"rightArm","pivot":[-5,21.5,0]},{"locators":{"lead_hold":[-6,14.5,1]},"name":"rightItem","parent":"rightArm","pivot":[-6,14.5,1]},{"cubes":[{"inflate":0.25,"origin":[-4,12,-2],"size":[8,12,4],"uv":[16,32]}],"name":"jacket","parent":"body","pivot":[0,24,0]},{"name":"cape","parent":"body","pivot":[0,24,-3]}],"description":{"identifier":"geometry.humanoid.customSlim","texture_height":64,"texture_width":64,"visible_bounds_height":2,"visible_bounds_offset":[0,1,0],"visible_bounds_width":1}}]}',
			$skin->getGeometryDataEngineVersion(),
			$skin->getAnimationData(),
			$skin->getCapeId(),
			$skin->getFullSkinId(),
			$skin->getArmSize(),
			$skin->getSkinColor(),
			$skin->getPersonaPieces(),
			$skin->getPieceTintColors(),
			$skin->isVerified(),
			$skin->isPremium(),
			$skin->isPersona(),
			$skin->isPersonaCapeOnClassic(),
			$skin->isPrimaryUser(),
			$skin->isOverride(),
		);
	}

	public function getCraftingDataCache() : string{
		return $this->craftingDataCache;
	}
}
