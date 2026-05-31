<?php

namespace MultiVersion\network\proto\v419\packets;

use MultiVersion\network\proto\utils\ReflectionUtils;
use pocketmine\network\mcpe\protocol\CameraShakePacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

class v419CameraShakePacket extends CameraShakePacket{

	public static function fromLatest(CameraShakePacket $pk) : self{
		$npk = new self();
		ReflectionUtils::setProperty(CameraShakePacket::class, $npk, "intensity", $pk->getIntensity());
		ReflectionUtils::setProperty(CameraShakePacket::class, $npk, "duration", $pk->getDuration());
		ReflectionUtils::setProperty(CameraShakePacket::class, $npk, "shakeType", $pk->getShakeType());
		return $npk;
	}

	protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{

		$in = \MultiVersion\network\proto\v419\v419PacketSerializer::reader($in, $protocolId);
		ReflectionUtils::setProperty(CameraShakePacket::class, $this, "intensity", $in->getLFloat());
		ReflectionUtils::setProperty(CameraShakePacket::class, $this, "duration", $in->getLFloat());
		ReflectionUtils::setProperty(CameraShakePacket::class, $this, "shakeType", $in->getByte());
	}

	protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{

		$out = \MultiVersion\network\proto\v419\v419PacketSerializer::writer($out, $protocolId);
		$out->putLFloat($this->getIntensity());
		$out->putLFloat($this->getDuration());
		$out->putByte($this->getShakeType());
	}
}


