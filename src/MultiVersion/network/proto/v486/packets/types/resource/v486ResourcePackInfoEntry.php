<?php
namespace MultiVersion\network\proto\v486\packets\types\resource;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackInfoEntry;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use Ramsey\Uuid\Uuid;

class v486ResourcePackInfoEntry extends ResourcePackInfoEntry{
	public function write(ByteBufferWriter|PacketSerializer $out, int $protocolId = ProtocolInfo::CURRENT_PROTOCOL) : void{
		$serializer = $out instanceof PacketSerializer ? $out : PacketSerializer::writer($out, $protocolId);
		$serializer->putString($this->getPackId()->toString());
		$serializer->putString($this->getVersion());
		$serializer->putLLong($this->getSizeBytes());
		$serializer->putString($this->getEncryptionKey());
		$serializer->putString($this->getSubPackName());
		$serializer->putString($this->getContentId());
		$serializer->putBool($this->hasScripts());
		$serializer->putBool($this->isRtxCapable());
	}

	public static function read(ByteBufferReader|PacketSerializer $in, int $protocolId = ProtocolInfo::CURRENT_PROTOCOL) : self{
		$serializer = $in instanceof PacketSerializer ? $in : PacketSerializer::reader($in, $protocolId);
		$uuid = Uuid::fromString($serializer->getString());
		$version = $serializer->getString();
		$sizeBytes = $serializer->getLLong();
		$encryptionKey = $serializer->getString();
		$subPackName = $serializer->getString();
		$contentId = $serializer->getString();
		$hasScripts = $serializer->getBool();
		$rtxCapable = $serializer->getBool();
		return new self($uuid, $version, $sizeBytes, $encryptionKey, $subPackName,$contentId, $hasScripts, false, $rtxCapable);
	}
}
