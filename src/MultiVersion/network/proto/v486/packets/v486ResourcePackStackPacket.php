<?php

namespace MultiVersion\network\proto\v486\packets;

use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackStackEntry;

class v486ResourcePackStackPacket extends ResourcePackStackPacket{
	/** @var ResourcePackStackEntry[] */
	public array $behaviorPackStack = [];

	public static function fromLatest(ResourcePackStackPacket $pk) : self{
		$npk = new self();
		$npk->resourcePackStack = property_exists($pk, "resourcePackStack") ? $pk->resourcePackStack : [];
		$npk->behaviorPackStack = property_exists($pk, "behaviorPackStack") ? $pk->behaviorPackStack : [];
		$npk->mustAccept = property_exists($pk, "mustAccept") ? $pk->mustAccept : false;
		$npk->baseGameVersion = property_exists($pk, "baseGameVersion") ? $pk->baseGameVersion : "";
		$npk->experiments = property_exists($pk, "experiments") ? $pk->experiments : new Experiments([], false);
		return $npk;
	}

	protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{

		$in = \MultiVersion\network\proto\v486\v486PacketSerializer::reader($in, $protocolId);
		$this->mustAccept = $in->getBool();
		$behaviorPackCount = $in->getUnsignedVarInt();
		while($behaviorPackCount-- > 0){
			$this->behaviorPackStack[] = ResourcePackStackEntry::read($in->getReader());
		}

		$resourcePackCount = $in->getUnsignedVarInt();
		while($resourcePackCount-- > 0){
			$this->resourcePackStack[] = ResourcePackStackEntry::read($in->getReader());
		}

		$this->baseGameVersion = $in->getString();
		$this->experiments = Experiments::read($in->getReader());
	}

	protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{

		$out = \MultiVersion\network\proto\v486\v486PacketSerializer::writer($out, $protocolId);
		$out->putBool($this->mustAccept);

		$out->putUnsignedVarInt(count($this->behaviorPackStack));
		foreach($this->behaviorPackStack as $entry){
			$entry->write($out->getWriter());
		}

		$out->putUnsignedVarInt(count($this->resourcePackStack));
		foreach($this->resourcePackStack as $entry){
			$entry->write($out->getWriter());
		}

		$out->putString($this->baseGameVersion);
		$this->experiments->write($out->getWriter());
	}
}


