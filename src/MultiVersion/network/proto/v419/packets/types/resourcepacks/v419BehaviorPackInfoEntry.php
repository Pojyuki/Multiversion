<?php
namespace MultiVersion\network\proto\v419\packets\types\resourcepacks;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackInfoEntry;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class v419BehaviorPackInfoEntry extends ResourcePackInfoEntry{
	public function __construct(UuidInterface|string $packId, string $version, int $sizeBytes, string $encryptionKey = "", string $subPackName = "", string $contentId = "", bool $hasScripts = false, bool $isAddonPack = false){
		parent::__construct(
			$packId instanceof UuidInterface ? $packId : Uuid::fromString($packId),
			$version,
			$sizeBytes,
			$encryptionKey,
			$subPackName,
			$contentId,
			$hasScripts,
			$isAddonPack
		);
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
