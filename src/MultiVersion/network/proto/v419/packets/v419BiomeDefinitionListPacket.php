<?php

namespace MultiVersion\network\proto\v419\packets;

use pocketmine\network\mcpe\protocol\BiomeDefinitionListPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;

class v419BiomeDefinitionListPacket extends BiomeDefinitionListPacket {
    public const NETWORK_ID = ProtocolInfo::BIOME_DEFINITION_LIST_PACKET;

    /** @phpstan-var CacheableNbt<\pocketmine\nbt\tag\CompoundTag> */
    public CacheableNbt $definitions;

    /**
     * @generate-create-func
     * @phpstan-param CacheableNbt<\pocketmine\nbt\tag\CompoundTag> $definitions
     */
    public static function v419create(CacheableNbt $definitions) : self{
        $result = new self;
        $result->definitions = $definitions;
        return $result;
    }

    protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{

    	$in = \MultiVersion\network\proto\v419\v419PacketSerializer::reader($in, $protocolId);
        $this->definitions = new CacheableNbt($in->getNbtCompoundRoot());
    }

    protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{

    	$out = \MultiVersion\network\proto\v419\v419PacketSerializer::writer($out, $protocolId);
        $out->put($this->definitions->getEncodedNbt());
    }

    public function handle(PacketHandlerInterface $handler) : bool{
        return $handler->handleBiomeDefinitionList($this);
    }
}

