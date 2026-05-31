<?php

namespace MultiVersion\network\proto;

use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

abstract class MVPacketSerializer extends PacketSerializer{

	final public static function newEncoder(int $protocolId = ProtocolInfo::CURRENT_PROTOCOL) : static{
		return static::encoder($protocolId);
	}

	final public static function newDecoder(string $buffer, int $offset, int $protocolId = ProtocolInfo::CURRENT_PROTOCOL) : static{
		return static::decoder($protocolId, $buffer, $offset);
	}
}
