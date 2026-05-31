<?php

namespace MultiVersion\network\proto\v486\packets;

use pocketmine\network\mcpe\protocol\PacketPool;

class v486PacketPool extends PacketPool{

	public function __construct(){
		parent::__construct();
		// override other packets
		$this->registerPacket(new v486ActorEventPacket());
		$this->registerPacket(new v486AdventureSettingsPacket());
		$this->registerPacket(new v486AnimatePacket());
		$this->registerPacket(new v486CommandRequestPacket());
        $this->registerPacket(new v486TickSyncPacket());
        $this->registerPacket(new v486ContainerClosePacket());
		$this->registerPacket(new v486EmotePacket());
		$this->registerPacket(new v486InteractPacket());
		$this->registerPacket(new v486InventoryTransactionPacket());
		$this->registerPacket(new v486ItemStackRequestPacket());
        $this->registerPacket(new v486TextPacket());
		$this->registerPacket(new v486MapInfoRequestPacket());
		$this->registerPacket(new v486ModalFormResponsePacket());
		$this->registerPacket(new v486PlayerActionPacket());
		$this->registerPacket(new v486PlayerAuthInputPacket());
		$this->registerPacket(new v486PlayerSkinPacket());
		$this->registerPacket(new v486RequestChunkRadiusPacket());
		$this->registerPacket(new v486SetActorDataPacket());
		$this->registerPacket(new v486SetActorMotionPacket());
		$this->registerPacket(new v486LevelSoundEventPacket());
		$this->registerPacket(new v486MobEquipmentPacket());
	}
}
