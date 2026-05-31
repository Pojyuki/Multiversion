<?php

namespace MultiVersion\network\proto\v419\packets\types\resourcepacks;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackInfoEntry;
use Ramsey\Uuid\Uuid;

class v419ResourcePackInfoEntry extends ResourcePackInfoEntry{

	public static function fromLatest(ResourcePackInfoEntry $entry) : self{
		$uuid = $entry->getPackId();
		$version = $entry->getVersion();
		$sizeBytes = $entry->getSizeBytes();
		$encryptionKey = $entry->getEncryptionKey();
		$subPackName = $entry->getSubPackName();
		$contentId = $entry->getContentId();
		$hasScripts = $entry->hasScripts();
		return new self($uuid, $version, $sizeBytes, $encryptionKey, $subPackName, $contentId, $hasScripts, false);
	}

	public function write(ByteBufferWriter|PacketSerializer $out, int $protocolId = ProtocolInfo::CURRENT_PROTOCOL) : void{
		$serializer = $out instanceof PacketSerializer ? $out : PacketSerializer::writer($out, $protocolId);
		$serializer->putString($this->getPackId()->toString());
		$serializer->putString($this->getVersion());
		$serializer->putLLong($this->getSizeBytes());
		$serializer->putString($this->getEncryptionKey());
		$serializer->putString($this->getSubPackName());
		$serializer->putString($this->getContentId());
		$serializer->putBool($this->hasScripts());
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
		return new self($uuid, $version, $sizeBytes, $encryptionKey, $subPackName, $contentId, $hasScripts, false);
	}
}
