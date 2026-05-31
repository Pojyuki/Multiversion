<?php

declare(strict_types=1);

namespace MultiVersion\network\proto\v486\packets;

use pocketmine\network\mcpe\protocol\CommandRequestPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\command\CommandOriginData;

class  v486CommandRequestPacket extends CommandRequestPacket{
	private const LEGACY_ORIGIN_ID_TO_STRING = [
		0 => CommandOriginData::ORIGIN_PLAYER,
		1 => CommandOriginData::ORIGIN_BLOCK,
		2 => CommandOriginData::ORIGIN_MINECART_BLOCK,
		3 => CommandOriginData::ORIGIN_DEV_CONSOLE,
		4 => CommandOriginData::ORIGIN_TEST,
		5 => CommandOriginData::ORIGIN_AUTOMATION_PLAYER,
		6 => CommandOriginData::ORIGIN_CLIENT_AUTOMATION,
		7 => CommandOriginData::ORIGIN_DEDICATED_SERVER,
		8 => CommandOriginData::ORIGIN_ENTITY,
		9 => CommandOriginData::ORIGIN_VIRTUAL,
		10 => CommandOriginData::ORIGIN_GAME_ARGUMENT,
		11 => CommandOriginData::ORIGIN_ENTITY_SERVER,
	];

	private const LEGACY_ORIGIN_STRING_TO_ID = [
		CommandOriginData::ORIGIN_PLAYER => 0,
		CommandOriginData::ORIGIN_BLOCK => 1,
		CommandOriginData::ORIGIN_MINECART_BLOCK => 2,
		CommandOriginData::ORIGIN_DEV_CONSOLE => 3,
		CommandOriginData::ORIGIN_TEST => 4,
		CommandOriginData::ORIGIN_AUTOMATION_PLAYER => 5,
		CommandOriginData::ORIGIN_CLIENT_AUTOMATION => 6,
		CommandOriginData::ORIGIN_DEDICATED_SERVER => 7,
		CommandOriginData::ORIGIN_ENTITY => 8,
		CommandOriginData::ORIGIN_VIRTUAL => 9,
		CommandOriginData::ORIGIN_GAME_ARGUMENT => 10,
		CommandOriginData::ORIGIN_ENTITY_SERVER => 11,
	];

	private static function getLegacyCommandOriginData(PacketSerializer $in) : CommandOriginData{
		$origin = new CommandOriginData();
		$legacyType = $in->getUnsignedVarInt();
		$origin->type = self::LEGACY_ORIGIN_ID_TO_STRING[$legacyType] ?? CommandOriginData::ORIGIN_PLAYER;
		$origin->uuid = $in->getUUID();
		$origin->requestId = $in->getString();
		if($legacyType === 3 || $legacyType === 4){
			$origin->playerActorUniqueId = $in->getVarLong();
		}else{
			$origin->playerActorUniqueId = 0;
		}
		return $origin;
	}

	private static function putLegacyCommandOriginData(PacketSerializer $out, CommandOriginData $origin) : void{
		$legacyType = self::LEGACY_ORIGIN_STRING_TO_ID[$origin->type] ?? 0;
		$out->putUnsignedVarInt($legacyType);
		$out->putUUID($origin->uuid);
		$out->putString($origin->requestId);
		if($legacyType === 3 || $legacyType === 4){
			$out->putVarLong($origin->playerActorUniqueId);
		}
	}

	protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{
		$in = \MultiVersion\network\proto\v486\v486PacketSerializer::reader($in, $protocolId);
		$this->command = $in->getString();
		$this->originData = self::getLegacyCommandOriginData($in);
		$this->isInternal = $in->getBool();
	}

	protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{

		$out = \MultiVersion\network\proto\v486\v486PacketSerializer::writer($out, $protocolId);
		$out->putString($this->command);
		self::putLegacyCommandOriginData($out, $this->originData);
		$out->putBool($this->isInternal);
	}
}


