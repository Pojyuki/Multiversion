<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\serializer;

use pmmp\encoding\BE;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\DataDecodeException;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\command\CommandOriginData;
use pocketmine\network\mcpe\protocol\types\entity\Attribute;
use pocketmine\network\mcpe\protocol\types\entity\EntityLink;
use pocketmine\network\mcpe\protocol\types\entity\MetadataProperty;
use pocketmine\network\mcpe\protocol\types\GameRule;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeIngredient;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\utils\Binary;
use Ramsey\Uuid\UuidInterface;
use function count;
use function func_num_args;
use function strlen;

class PacketSerializer{
	private const LEGACY_UNSIGNED_BLOCKPOS_Y_MAX_PROTOCOL = 486;
	private const BLOCKPOS_ARGMODE_NONE = 0;
	private const BLOCKPOS_ARGMODE_PROTOCOL_ID = 1;
	private const BLOCKPOS_ARGMODE_SIGNED_BOOL = 2;
	private static ?bool $gameRulesMethodsRequireProtocolId = null;
	private static ?bool $entityLinkMethodsRequireProtocolId = null;
	private static ?bool $commandOriginMethodsRequireProtocolId = null;
	private static ?int $blockPositionGetArgMode = null;
	private static ?int $blockPositionPutArgMode = null;
	private static ?bool $signedBlockPositionGetMethodAvailable = null;
	private static ?bool $signedBlockPositionPutMethodAvailable = null;
	private static ?bool $signedBlockPositionGetRequiresProtocolId = null;
	private static ?bool $signedBlockPositionPutRequiresProtocolId = null;

	private function __construct(
		private int $protocolId,
		private ?ByteBufferReader $reader,
		private ?ByteBufferWriter $writer
	){
	}

	public static function encoder(int $protocolId = ProtocolInfo::CURRENT_PROTOCOL) : static{
		return new static($protocolId, null, new ByteBufferWriter());
	}

	public static function decoder(int|string $protocolIdOrBuffer, string|int|null $bufferOrOffset = null, ?int $offset = null) : static{
		if(func_num_args() === 2 && is_string($protocolIdOrBuffer) && is_int($bufferOrOffset)){
			$protocolId = ProtocolInfo::CURRENT_PROTOCOL;
			$buffer = $protocolIdOrBuffer;
			$offset = $bufferOrOffset;
		}else{
			$protocolId = is_int($protocolIdOrBuffer) ? $protocolIdOrBuffer : ProtocolInfo::CURRENT_PROTOCOL;
			$buffer = is_string($bufferOrOffset) ? $bufferOrOffset : "";
			$offset ??= 0;
		}

		$reader = new ByteBufferReader($buffer);
		$reader->setOffset($offset);
		return new static($protocolId, $reader, null);
	}

	public static function reader(ByteBufferReader $reader, ?int $protocolId = null) : static{
		$protocolId ??= ProtocolInfo::CURRENT_PROTOCOL;
		return new static($protocolId, $reader, null);
	}

	public static function writer(ByteBufferWriter $writer, ?int $protocolId = null) : static{
		$protocolId ??= ProtocolInfo::CURRENT_PROTOCOL;
		return new static($protocolId, null, $writer);
	}

	public function getProtocolId() : int{
		return $this->protocolId;
	}

	public function getReader() : ByteBufferReader{
		return $this->reader ?? throw new \LogicException("PacketSerializer is not configured for reading");
	}

	public function getWriter() : ByteBufferWriter{
		return $this->writer ?? throw new \LogicException("PacketSerializer is not configured for writing");
	}

	public function getOffset() : int{
		return $this->reader !== null ? $this->reader->getOffset() : strlen($this->getBuffer());
	}

	public function setOffset(int $offset) : void{
		$this->getReader()->setOffset($offset);
	}

	public function getBuffer() : string{
		if($this->writer !== null){
			return $this->writer->getData();
		}
		return $this->getReader()->getData();
	}

	public function feof() : bool{
		return $this->reader === null || $this->reader->getUnreadLength() <= 0;
	}

	public function get(int $length) : string{
		return $this->getReader()->readByteArray($length);
	}

	public function put(string $value) : void{
		$this->getWriter()->writeByteArray($value);
	}

	public function getBool() : bool{
		return CommonTypes::getBool($this->getReader());
	}

	public function putBool(bool $value) : void{
		CommonTypes::putBool($this->getWriter(), $value);
	}

	public function getByte() : int{
		return Byte::readUnsigned($this->getReader());
	}

	public function putByte(int $value) : void{
		Byte::writeUnsigned($this->getWriter(), $value);
	}

	public function getLShort() : int{
		return LE::readUnsignedShort($this->getReader());
	}

	public function getSignedLShort() : int{
		return LE::readSignedShort($this->getReader());
	}

	public function putLShort(int $value) : void{
		LE::writeSignedShort($this->getWriter(), $value);
	}

	public function getInt() : int{
		return BE::readSignedInt($this->getReader());
	}

	public function putInt(int $value) : void{
		BE::writeSignedInt($this->getWriter(), $value);
	}

	public function getLInt() : int{
		return LE::readSignedInt($this->getReader());
	}

	public function putLInt(int $value) : void{
		LE::writeSignedInt($this->getWriter(), $value);
	}

	public function getLLong() : int{
		return LE::readSignedLong($this->getReader());
	}

	public function putLLong(int $value) : void{
		LE::writeSignedLong($this->getWriter(), $value);
	}

	public function getLFloat() : float{
		return LE::readFloat($this->getReader());
	}

	public function putLFloat(float $value) : void{
		LE::writeFloat($this->getWriter(), $value);
	}

	public function getVarInt() : int{
		return VarInt::readSignedInt($this->getReader());
	}

	public function putVarInt(int $value) : void{
		VarInt::writeSignedInt($this->getWriter(), $value);
	}

	public function getUnsignedVarInt() : int{
		return VarInt::readUnsignedInt($this->getReader());
	}

	public function putUnsignedVarInt(int $value) : void{
		VarInt::writeUnsignedInt($this->getWriter(), $value);
	}

	public function getVarLong() : int{
		return VarInt::readSignedLong($this->getReader());
	}

	public function putVarLong(int $value) : void{
		VarInt::writeSignedLong($this->getWriter(), $value);
	}

	public function getUnsignedVarLong() : int{
		return VarInt::readUnsignedLong($this->getReader());
	}

	public function putUnsignedVarLong(int $value) : void{
		VarInt::writeUnsignedLong($this->getWriter(), $value);
	}

	public function getString() : string{
		return CommonTypes::getString($this->getReader());
	}

	public function putString(string $value) : void{
		CommonTypes::putString($this->getWriter(), $value);
	}

	public function getUUID() : UuidInterface{
		return CommonTypes::getUUID($this->getReader());
	}

	public function putUUID(UuidInterface $uuid) : void{
		CommonTypes::putUUID($this->getWriter(), $uuid);
	}

	public function getSkin() : SkinData{
		return CommonTypes::getSkin($this->getReader());
	}

	public function putSkin(SkinData $skin) : void{
		CommonTypes::putSkin($this->getWriter(), $skin);
	}

	public function getActorRuntimeId() : int{
		return CommonTypes::getActorRuntimeId($this->getReader());
	}

	public function putActorRuntimeId(int $eid) : void{
		CommonTypes::putActorRuntimeId($this->getWriter(), $eid);
	}

	public function getActorUniqueId() : int{
		return CommonTypes::getActorUniqueId($this->getReader());
	}

	public function putActorUniqueId(int $eid) : void{
		CommonTypes::putActorUniqueId($this->getWriter(), $eid);
	}

	public function getSignedBlockPosition() : BlockPosition{
		if(self::$signedBlockPositionGetMethodAvailable === null){
			self::$signedBlockPositionGetMethodAvailable = method_exists(CommonTypes::class, "getSignedBlockPosition");
		}

		if(self::$signedBlockPositionGetMethodAvailable){
			if(self::$signedBlockPositionGetRequiresProtocolId === null){
				self::$signedBlockPositionGetRequiresProtocolId = (new \ReflectionMethod(CommonTypes::class, "getSignedBlockPosition"))->getNumberOfParameters() >= 2;
			}

			if(self::$signedBlockPositionGetRequiresProtocolId){
				return CommonTypes::getSignedBlockPosition($this->getReader(), $this->protocolId);
			}

			return CommonTypes::getSignedBlockPosition($this->getReader());
		}

		// Some protocol branches don't expose signed helpers.
		return new BlockPosition($this->getVarInt(), $this->getVarInt(), $this->getVarInt());
	}

	public function putSignedBlockPosition(BlockPosition $blockPosition) : void{
		if(self::$signedBlockPositionPutMethodAvailable === null){
			self::$signedBlockPositionPutMethodAvailable = method_exists(CommonTypes::class, "putSignedBlockPosition");
		}

		if(self::$signedBlockPositionPutMethodAvailable){
			if(self::$signedBlockPositionPutRequiresProtocolId === null){
				self::$signedBlockPositionPutRequiresProtocolId = (new \ReflectionMethod(CommonTypes::class, "putSignedBlockPosition"))->getNumberOfParameters() >= 3;
			}

			if(self::$signedBlockPositionPutRequiresProtocolId){
				CommonTypes::putSignedBlockPosition($this->getWriter(), $blockPosition, $this->protocolId);
				return;
			}

			CommonTypes::putSignedBlockPosition($this->getWriter(), $blockPosition);
			return;
		}

		// Some protocol branches don't expose signed helpers.
		$this->putVarInt($blockPosition->getX());
		$this->putVarInt($blockPosition->getY());
		$this->putVarInt($blockPosition->getZ());
	}

	public function getBlockPosition() : BlockPosition{
		if(self::$blockPositionGetArgMode === null){
			$method = new \ReflectionMethod(CommonTypes::class, "getBlockPosition");
			$parameters = $method->getParameters();
			if(!isset($parameters[1])){
				self::$blockPositionGetArgMode = self::BLOCKPOS_ARGMODE_NONE;
			}else{
				$type = $parameters[1]->getType();
				$typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;
				if($typeName === "int"){
					self::$blockPositionGetArgMode = self::BLOCKPOS_ARGMODE_PROTOCOL_ID;
				}elseif($typeName === "bool"){
					self::$blockPositionGetArgMode = self::BLOCKPOS_ARGMODE_SIGNED_BOOL;
				}elseif($parameters[1]->isDefaultValueAvailable() && is_bool($parameters[1]->getDefaultValue())){
					self::$blockPositionGetArgMode = self::BLOCKPOS_ARGMODE_SIGNED_BOOL;
				}else{
					self::$blockPositionGetArgMode = self::BLOCKPOS_ARGMODE_PROTOCOL_ID;
				}
			}
		}

		if(self::$blockPositionGetArgMode === self::BLOCKPOS_ARGMODE_PROTOCOL_ID){
			return CommonTypes::getBlockPosition($this->getReader(), $this->protocolId);
		}
		if(self::$blockPositionGetArgMode === self::BLOCKPOS_ARGMODE_SIGNED_BOOL){
			return CommonTypes::getBlockPosition($this->getReader(), $this->protocolId > self::LEGACY_UNSIGNED_BLOCKPOS_Y_MAX_PROTOCOL);
		}

		// BedrockProtocol-main no longer exposes protocol-aware block position codecs.
		// Legacy protocols (1.16/1.18 translators here) still encode Y as unsigned varint.
		if($this->protocolId <= self::LEGACY_UNSIGNED_BLOCKPOS_Y_MAX_PROTOCOL){
			$x = $this->getVarInt();
			$y = Binary::signInt($this->getUnsignedVarInt());
			$z = $this->getVarInt();
			return new BlockPosition($x, $y, $z);
		}

		return CommonTypes::getBlockPosition($this->getReader());
	}

	public function putBlockPosition(BlockPosition $blockPosition) : void{
		if(self::$blockPositionPutArgMode === null){
			$method = new \ReflectionMethod(CommonTypes::class, "putBlockPosition");
			$parameters = $method->getParameters();
			if(!isset($parameters[2])){
				self::$blockPositionPutArgMode = self::BLOCKPOS_ARGMODE_NONE;
			}else{
				$type = $parameters[2]->getType();
				$typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;
				if($typeName === "int"){
					self::$blockPositionPutArgMode = self::BLOCKPOS_ARGMODE_PROTOCOL_ID;
				}elseif($typeName === "bool"){
					self::$blockPositionPutArgMode = self::BLOCKPOS_ARGMODE_SIGNED_BOOL;
				}elseif($parameters[2]->isDefaultValueAvailable() && is_bool($parameters[2]->getDefaultValue())){
					self::$blockPositionPutArgMode = self::BLOCKPOS_ARGMODE_SIGNED_BOOL;
				}else{
					self::$blockPositionPutArgMode = self::BLOCKPOS_ARGMODE_PROTOCOL_ID;
				}
			}
		}

		if(self::$blockPositionPutArgMode === self::BLOCKPOS_ARGMODE_PROTOCOL_ID){
			CommonTypes::putBlockPosition($this->getWriter(), $blockPosition, $this->protocolId);
			return;
		}
		if(self::$blockPositionPutArgMode === self::BLOCKPOS_ARGMODE_SIGNED_BOOL){
			CommonTypes::putBlockPosition($this->getWriter(), $blockPosition, $this->protocolId > self::LEGACY_UNSIGNED_BLOCKPOS_Y_MAX_PROTOCOL);
			return;
		}

		// BedrockProtocol-main no longer exposes protocol-aware block position codecs.
		// Legacy protocols (1.16/1.18 translators here) still encode Y as unsigned varint.
		if($this->protocolId <= self::LEGACY_UNSIGNED_BLOCKPOS_Y_MAX_PROTOCOL){
			$this->putVarInt($blockPosition->getX());
			$this->putUnsignedVarInt(Binary::unsignInt($blockPosition->getY()));
			$this->putVarInt($blockPosition->getZ());
			return;
		}

		CommonTypes::putBlockPosition($this->getWriter(), $blockPosition);
	}

	public function getVector3() : Vector3{
		return CommonTypes::getVector3($this->getReader());
	}

	public function putVector3(Vector3 $vector) : void{
		CommonTypes::putVector3($this->getWriter(), $vector);
	}

	public function putVector3Nullable(?Vector3 $vector) : void{
		CommonTypes::putVector3Nullable($this->getWriter(), $vector);
	}

	public function getEntityMetadata() : array{
		return CommonTypes::getEntityMetadata($this->getReader());
	}

	public function putEntityMetadata(array $metadata) : void{
		CommonTypes::putEntityMetadata($this->getWriter(), $metadata);
	}

	public function getAttributeList() : array{
		$list = [];
		$count = $this->getUnsignedVarInt();
		for($i = 0; $i < $count; ++$i){
			$min = $this->getLFloat();
			$max = $this->getLFloat();
			$current = $this->getLFloat();
			$default = $this->getLFloat();
			$id = $this->getString();
			$list[] = new Attribute($id, $min, $max, $current, $default, []);
		}
		return $list;
	}

	public function putAttributeList(Attribute ...$attributes) : void{
		$this->putUnsignedVarInt(count($attributes));
		foreach($attributes as $attribute){
			$this->putLFloat($attribute->getMin());
			$this->putLFloat($attribute->getMax());
			$this->putLFloat($attribute->getCurrent());
			$this->putLFloat($attribute->getDefault());
			$this->putString($attribute->getId());
		}
	}

	public function getGameRules() : array{
		if(self::$gameRulesMethodsRequireProtocolId === null){
			self::$gameRulesMethodsRequireProtocolId = (new \ReflectionMethod(CommonTypes::class, "getGameRules"))->getNumberOfParameters() >= 3;
		}

		if(self::$gameRulesMethodsRequireProtocolId){
			return CommonTypes::getGameRules($this->getReader(), $this->protocolId, false);
		}

		return CommonTypes::getGameRules($this->getReader(), false);
	}

	public function putGameRules(array $rules) : void{
		if(self::$gameRulesMethodsRequireProtocolId === null){
			self::$gameRulesMethodsRequireProtocolId = (new \ReflectionMethod(CommonTypes::class, "putGameRules"))->getNumberOfParameters() >= 4;
		}

		if(self::$gameRulesMethodsRequireProtocolId){
			CommonTypes::putGameRules($this->getWriter(), $this->protocolId, $rules, false);
			return;
		}

		CommonTypes::putGameRules($this->getWriter(), $rules, false);
	}

	public function getEntityLink() : EntityLink{
		if(self::$entityLinkMethodsRequireProtocolId === null){
			self::$entityLinkMethodsRequireProtocolId = (new \ReflectionMethod(CommonTypes::class, "getEntityLink"))->getNumberOfParameters() >= 2;
		}

		if(self::$entityLinkMethodsRequireProtocolId){
			return CommonTypes::getEntityLink($this->getReader(), $this->protocolId);
		}

		return CommonTypes::getEntityLink($this->getReader());
	}

	public function putEntityLink(EntityLink $link) : void{
		if(self::$entityLinkMethodsRequireProtocolId === null){
			self::$entityLinkMethodsRequireProtocolId = (new \ReflectionMethod(CommonTypes::class, "putEntityLink"))->getNumberOfParameters() >= 3;
		}

		if(self::$entityLinkMethodsRequireProtocolId){
			CommonTypes::putEntityLink($this->getWriter(), $this->protocolId, $link);
			return;
		}

		CommonTypes::putEntityLink($this->getWriter(), $link);
	}

	public function getCommandOriginData() : CommandOriginData{
		if(self::$commandOriginMethodsRequireProtocolId === null){
			self::$commandOriginMethodsRequireProtocolId = (new \ReflectionMethod(CommonTypes::class, "getCommandOriginData"))->getNumberOfParameters() >= 2;
		}

		if(self::$commandOriginMethodsRequireProtocolId){
			return CommonTypes::getCommandOriginData($this->getReader(), $this->protocolId);
		}

		return CommonTypes::getCommandOriginData($this->getReader());
	}

	public function putCommandOriginData(CommandOriginData $data) : void{
		if(self::$commandOriginMethodsRequireProtocolId === null){
			self::$commandOriginMethodsRequireProtocolId = (new \ReflectionMethod(CommonTypes::class, "putCommandOriginData"))->getNumberOfParameters() >= 3;
		}

		if(self::$commandOriginMethodsRequireProtocolId){
			CommonTypes::putCommandOriginData($this->getWriter(), $data, $this->protocolId);
			return;
		}

		CommonTypes::putCommandOriginData($this->getWriter(), $data);
	}

	public function getNbtCompoundRoot() : CompoundTag{
		return CommonTypes::getNbtCompoundRoot($this->getReader());
	}

	public function putNbtCompoundRoot(CompoundTag $nbt) : void{
		$this->put((new NetworkNbtSerializer())->write(new TreeRoot($nbt)));
	}

	public function getItemStackWithoutStackId() : ItemStack{
		return CommonTypes::getItemStackWithoutStackId($this->getReader());
	}

	public function putItemStackWithoutStackId(ItemStack $itemStack) : void{
		CommonTypes::putItemStackWithoutStackId($this->getWriter(), $itemStack);
	}

	public function getItemStackWrapper() : ItemStackWrapper{
		return CommonTypes::getItemStackWrapper($this->getReader());
	}

	public function putItemStackWrapper(ItemStackWrapper $itemStackWrapper) : void{
		CommonTypes::putItemStackWrapper($this->getWriter(), $itemStackWrapper);
	}

	public function getRecipeIngredient() : RecipeIngredient{
		return CommonTypes::getRecipeIngredient($this->getReader());
	}

	public function putRecipeIngredient(RecipeIngredient $ingredient) : void{
		CommonTypes::putRecipeIngredient($this->getWriter(), $ingredient);
	}

	public function readRecipeNetId() : int{
		return $this->getUnsignedVarInt();
	}

	public function writeRecipeNetId(int $id) : void{
		$this->putUnsignedVarInt($id);
	}

	public function readCreativeItemNetId() : int{
		return $this->getUnsignedVarInt();
	}

	public function writeCreativeItemNetId(int $id) : void{
		$this->putUnsignedVarInt($id);
	}

	public function readItemStackNetIdVariant() : int{
		return $this->getVarInt();
	}

	public function writeItemStackNetIdVariant(int $id) : void{
		$this->putVarInt($id);
	}

	public function readOptional(\Closure $reader) : mixed{
		return $this->getBool() ? $reader() : null;
	}

	public function writeOptional(mixed $value, \Closure $writer) : void{
		if($value !== null){
			$this->putBool(true);
			$writer($value);
		}else{
			$this->putBool(false);
		}
	}
}
