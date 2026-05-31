<?php

namespace MultiVersion\network\proto\chunk\task;

use Closure;
use MultiVersion\MultiVersion;
use MultiVersion\network\proto\PacketTranslator;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\compression\CompressBatchPromise;
use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketBatch;
use pocketmine\network\mcpe\protocol\types\ChunkPosition;
use pocketmine\network\mcpe\serializer\ChunkSerializer;
use pocketmine\scheduler\AsyncTask;
use pocketmine\thread\NonThreadSafeValue;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\FastChunkSerializer;

class MVChunkRequestTask extends AsyncTask{

	private const TLS_KEY_PROMISE = "promise";
	private const TLS_KEY_ERROR_HOOK = "errorHook";

	protected string $chunk;
	protected int $chunkX;
	protected int $chunkZ;
	private int $dimensionId;
	/** @phpstan-var NonThreadSafeValue<Compressor> */
	protected NonThreadSafeValue $compressor;
	private string $tiles;

	private int $protocol;
	private static ?bool $packetEncodeExpectsProtocol = null;
	private static ?bool $serializeTilesExpectsTypeConverter = null;

	/**
	 * @param int                  $chunkX
	 * @param int                  $chunkZ
	 * @param Chunk                $chunk
	 * @param CompressBatchPromise $promise
	 * @param Compressor           $compressor
	 * @param PacketTranslator     $translator
	 * @param Closure|null         $onError
	 */
	public function __construct(int $chunkX, int $chunkZ, int $dimensionId, Chunk $chunk, CompressBatchPromise $promise, Compressor $compressor, PacketTranslator $translator, ?Closure $onError = null){
		$this->compressor = new NonThreadSafeValue($compressor);
		$this->chunk = FastChunkSerializer::serializeTerrain($chunk);
		$this->chunkX = $chunkX;
		$this->chunkZ = $chunkZ;
		$this->dimensionId = $dimensionId;
		$this->tiles = self::serializeTilesCompat($chunk, $translator);

		$this->protocol = $translator::PROTOCOL_VERSION;

		$this->storeLocal(self::TLS_KEY_PROMISE, $promise);
		$this->storeLocal(self::TLS_KEY_ERROR_HOOK, $onError);
	}

	public function onRun() : void{
		$translator = MultiVersion::getTranslator($this->protocol);

		$factory = $translator->getPacketSerializerFactory();
		$chunkSerializer = $factory->getChunkSerializer();

		$chunk = FastChunkSerializer::deserializeTerrain($this->chunk);
		$subCount = $chunkSerializer->getSubChunkCount($chunk, $this->dimensionId);
		$payload = $chunkSerializer->serializeFullChunk($chunk, $this->dimensionId, $translator->getTypeConverter()->getMVBlockTranslator(), $factory, $this->tiles);

		$packet = LevelChunkPacket::create(new ChunkPosition($this->chunkX, $this->chunkZ), $this->dimensionId, $subCount, false, null, $payload);
		if(($pk = $translator->handleOutgoing(clone $packet)) !== null){
			$packet = clone $pk;
		}

		$stream = new ByteBufferWriter();
		$serializer = new ByteBufferWriter();
		if(self::$packetEncodeExpectsProtocol === null){
			self::$packetEncodeExpectsProtocol = (new \ReflectionMethod($packet, "encode"))->getNumberOfParameters() >= 2;
		}
		if(self::$packetEncodeExpectsProtocol){
			$packet->encode($serializer, $translator::PROTOCOL_VERSION);
		}else{
			$packet->encode($serializer);
		}
		$packetBuffer = $serializer->getData();
		VarInt::writeUnsignedInt($stream, strlen($packetBuffer));
		$stream->writeByteArray($packetBuffer);

		$this->setResult((!$translator::OLD_COMPRESSION ? chr($this->compressor->deserialize()->getNetworkId()) : '') . $this->compressor->deserialize()->compress($stream->getData()));
	}

	private static function serializeTilesCompat(Chunk $chunk, PacketTranslator $translator) : string{
		if(self::$serializeTilesExpectsTypeConverter === null){
			self::$serializeTilesExpectsTypeConverter = (new \ReflectionMethod(ChunkSerializer::class, "serializeTiles"))->getNumberOfParameters() >= 2;
		}

		if(self::$serializeTilesExpectsTypeConverter){
			return ChunkSerializer::serializeTiles($chunk, $translator->getTypeConverter());
		}

		return ChunkSerializer::serializeTiles($chunk);
	}

	public function onError() : void{
		/**
		 * @var Closure|null                    $hook
		 * @phpstan-var (Closure() : void)|null $hook
		 */
		$hook = $this->fetchLocal(self::TLS_KEY_ERROR_HOOK);
		if($hook !== null){
			$hook();
		}
	}

	public function onCompletion() : void{
		/** @var CompressBatchPromise $promise */
		$promise = $this->fetchLocal(self::TLS_KEY_PROMISE);
		$promise->resolve($this->getResult());
	}
}
