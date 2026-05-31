<?php

/*
 * This file is part of BedrockProtocol.
 * Copyright (C) 2014-2022 PocketMine Team <https://github.com/pmmp/BedrockProtocol>
 *
 * BedrockProtocol is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace MultiVersion\network\proto\v419\packets;

use MultiVersion\network\proto\v419\packets\types\v419CreativeContentEntry;
use pocketmine\network\mcpe\protocol\CreativeContentPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v419CreativeContentPacket extends CreativeContentPacket {
    public const NETWORK_ID = ProtocolInfo::CREATIVE_CONTENT_PACKET;
    /** @var v419CreativeContentEntry[] */
    public array $entries;

    /**
     * @generate-create-func
     * @param CreativeContentPacket $packet
     * @return v419CreativeContentPacket
     */
    public static function fromLatest(CreativeContentPacket $packet) : self{
        $result = new self;
        $result->entries = [];
        foreach($packet->getItems() as $entry){
            if($entry instanceof v419CreativeContentEntry){
                $result->entries[] = $entry;
                continue;
            }
            if(is_object($entry) && method_exists($entry, "getEntryId") && method_exists($entry, "getItem")){
                /** @var object{getEntryId: callable(): int, getItem: callable(): \pocketmine\network\mcpe\protocol\types\inventory\ItemStack} $entry */
                $result->entries[] = new v419CreativeContentEntry($entry->getEntryId(), $entry->getItem());
                continue;
            }
            $result->entries[] = $entry;
        }
        return $result;
    }


    /** @return v419CreativeContentEntry[] */
    public function getEntries() : array{ return $this->entries; }

    protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{

    	$in = \MultiVersion\network\proto\v419\v419PacketSerializer::reader($in, $protocolId);
        $this->entries = [];
        for($i = 0, $len = $in->getUnsignedVarInt(); $i < $len; ++$i){
            $this->entries[] = v419CreativeContentEntry::read($in);
        }
    }

    protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{

    	$out = \MultiVersion\network\proto\v419\v419PacketSerializer::writer($out, $protocolId);
        $out->putUnsignedVarInt(count($this->entries));
        foreach($this->entries as $entry){
            $entry->write($out);
        }
    }

    public function handle(PacketHandlerInterface $handler) : bool{
        return $handler->handleCreativeContent($this);
    }
}

