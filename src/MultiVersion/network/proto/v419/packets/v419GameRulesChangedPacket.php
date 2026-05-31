<?php

declare(strict_types=1);

namespace MultiVersion\network\proto\v419\packets;

use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;

class v419GameRulesChangedPacket extends GameRulesChangedPacket{

	public static function fromLatest(GameRulesChangedPacket $pk) : self{
		$npk = new self();
		$npk->gameRules = $pk->gameRules;
		return $npk;
	}

	protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{
		$in = \MultiVersion\network\proto\v419\v419PacketSerializer::reader($in, $protocolId);
		$this->gameRules = $in->getGameRules();
	}

	protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{
		$out = \MultiVersion\network\proto\v419\v419PacketSerializer::writer($out, $protocolId);
		$out->putGameRules($this->gameRules);
	}
}

