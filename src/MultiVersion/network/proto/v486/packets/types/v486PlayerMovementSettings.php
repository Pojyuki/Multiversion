<?php

namespace MultiVersion\network\proto\v486\packets\types;

use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\PlayerMovementSettings;

final class v486PlayerMovementSettings{

	public static function fromLatest(PlayerMovementSettings $pk) : self{
		return new self(
			2, // SERVER_AUTHORITATIVE_V2_REWIND in 1.18.10
			$pk->getRewindHistorySize(),
			$pk->isServerAuthoritativeBlockBreaking()
		);
	}

	public function __construct(
		private readonly int $movementType,
		private readonly int $rewindHistorySize,
		private readonly bool $serverAuthoritativeBlockBreaking
	){
	}

	public static function read(PacketSerializer $in) : self{
		$movementType = $in->getVarInt();
		$rewindHistorySize = $in->getVarInt();
		$serverAuthBlockBreaking = $in->getBool();
		return new self($movementType, $rewindHistorySize, $serverAuthBlockBreaking);
	}

	public function write(PacketSerializer $out) : void{
		$out->putVarInt($this->movementType);
		$out->putVarInt($this->rewindHistorySize);
		$out->putBool($this->serverAuthoritativeBlockBreaking);
	}
}
