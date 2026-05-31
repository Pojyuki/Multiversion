<?php

namespace MultiVersion\network\proto\v486\packets;

use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

class v486LevelSoundEventPacket extends LevelSoundEventPacket{
	public const NETWORK_ID = ProtocolInfo::LEVEL_SOUND_EVENT_PACKET;

	/** @see \pocketmine\network\mcpe\protocol\types\LevelSoundEvent */
	public int $sound;
	public Vector3 $position;
	public int $extraData = -1;
	public string $entityType = ":";
	public bool $isBabyMob = false;
	public bool $disableRelativeVolume = false;

	public static function fromLatest(LevelSoundEventPacket $pk) : self{
		$result = new self;
		$result->sound = $pk->sound;
		$result->position = $pk->position;
		$result->extraData = $pk->extraData;
		$result->entityType = $pk->entityType;
		$result->isBabyMob = $pk->isBabyMob;
		$result->disableRelativeVolume = $pk->disableRelativeVolume;
		return $result;
	}

	public static function nonActorSound(int $sound, Vector3 $position, bool $disableRelativeVolume, int $extraData = -1) : self{
		return self::create($sound, $position, $extraData, ":", false, $disableRelativeVolume);
	}

	protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{
		$in = \MultiVersion\network\proto\v486\v486PacketSerializer::reader($in, $protocolId);
		$this->sound = $in->getUnsignedVarInt();
		$this->position = $in->getVector3();
		$this->extraData = $in->getVarInt();
		$this->entityType = $in->getString();
		$this->isBabyMob = $in->getBool();
		$this->disableRelativeVolume = $in->getBool();
	}

	protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{
		$out = \MultiVersion\network\proto\v486\v486PacketSerializer::writer($out, $protocolId);
		$out->putUnsignedVarInt($this->sound);
		$out->putVector3($this->position);
		$out->putVarInt($this->extraData);
		$out->putString($this->entityType);
		$out->putBool($this->isBabyMob);
		$out->putBool($this->disableRelativeVolume);
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return $handler->handleLevelSoundEvent($this);
	}
}
