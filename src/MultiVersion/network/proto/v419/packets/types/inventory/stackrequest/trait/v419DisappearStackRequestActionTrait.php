<?php

namespace MultiVersion\network\proto\v419\packets\types\inventory\stackrequest\trait;

use MultiVersion\network\proto\v419\packets\types\inventory\stackrequest\v419ItemStackRequestSlotInfo;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

trait v419DisappearStackRequestActionTrait{
	final public function __construct(
		private int $count,
		private v419ItemStackRequestSlotInfo $source
	){}

	final public function getCount() : int{
		return $this->count;
	}

	final public function getSource() : v419ItemStackRequestSlotInfo{
		return $this->source;
	}

	public static function read(PacketSerializer $in) : self{
		$count = $in->getByte();
		$source = v419ItemStackRequestSlotInfo::read($in);
		return new self($count, $source);
	}

	public function write(ByteBufferWriter|PacketSerializer $out, int $protocolId = 419) : void{
		$serializer = $out instanceof PacketSerializer ? $out : PacketSerializer::writer($out);
		$serializer->putByte($this->count);
		$this->source->write($serializer);
	}
}
