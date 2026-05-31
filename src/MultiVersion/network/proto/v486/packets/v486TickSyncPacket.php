<?php

namespace MultiVersion\network\proto\v486\packets;

use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\mcpe\protocol\types\entity\Attribute;

class v486TickSyncPacket extends DataPacket implements ClientboundPacket, ServerboundPacket{
    public const NETWORK_ID = 0x17;

    private int $clientSendTime;
    private int $serverReceiveTime;

    private static function create(int $clientSendTime, int $serverReceiveTime) : self{
        $result = new self;
        $result->clientSendTime = $clientSendTime;
        $result->serverReceiveTime = $serverReceiveTime;
        return $result;
    }

    protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{

    	$in = \MultiVersion\network\proto\v486\v486PacketSerializer::reader($in, $protocolId);
        $this->clientSendTime = $in->getLLong();
        $this->serverReceiveTime = $in->getLLong();
    }

    protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{

    	$out = \MultiVersion\network\proto\v486\v486PacketSerializer::writer($out, $protocolId);
        $out->putLLong($this->clientSendTime);
        $out->putLLong($this->serverReceiveTime);
    }

    public function handle(PacketHandlerInterface $handler): bool{
        return true;
    }
}

