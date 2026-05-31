<?php
namespace MultiVersion\network\proto\v486\packets\types\inventory\stackrequest\trait;
use MultiVersion\network\proto\v486\packets\types\inventory\stackrequest\v486ItemStackRequestSlotInfo;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;

trait v486TakeOrPlaceStackRequestActionTrait{
	final public function __construct(
		private int $count,
		private v486ItemStackRequestSlotInfo $source,
		private v486ItemStackRequestSlotInfo $destination
	){}

	final public function getCount() : int{ return $this->count; }

	final public function getSource() : v486ItemStackRequestSlotInfo{ return $this->source; }

	final public function getDestination() : v486ItemStackRequestSlotInfo{ return $this->destination; }

	public static function read(PacketSerializer $in) : self{
		$count = $in->getByte();
		$src = v486ItemStackRequestSlotInfo::read($in);
		$dst = v486ItemStackRequestSlotInfo::read($in);
		return new self($count, $src, $dst);
	}

	public function write(ByteBufferWriter|PacketSerializer $out, int $protocolId = 486) : void{
		$serializer = $out instanceof PacketSerializer ? $out : PacketSerializer::writer($out);
		$serializer->putByte($this->count);
		$this->source->write($serializer);
		$this->destination->write($serializer);
	}
}
