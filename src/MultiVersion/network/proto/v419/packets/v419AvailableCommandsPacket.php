<?php

namespace MultiVersion\network\proto\v419\packets;

use LogicException;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\AvailableCommandsPacketDisassembler;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use pocketmine\network\mcpe\protocol\types\command\CommandData;
use pocketmine\network\mcpe\protocol\types\command\CommandHardEnum;
use pocketmine\network\mcpe\protocol\types\command\CommandOverload;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\network\mcpe\protocol\types\command\CommandPermissions;
use pocketmine\network\mcpe\protocol\types\command\CommandSoftEnum;
use pocketmine\network\mcpe\protocol\types\command\ConstrainedEnumValue;
use pocketmine\utils\BinaryDataException;
use function array_search;
use function array_values;
use function count;
use function dechex;

class v419AvailableCommandsPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::AVAILABLE_COMMANDS_PACKET;

	/**
	 * This flag is set on all types EXCEPT the POSTFIX type. Not completely sure what this is for, but it is required
	 * for the argtype to work correctly. VALID seems as good a name as any.
	 */
	public const ARG_FLAG_VALID = 0x100000;

	/**
	 * Basic parameter types. These must be combined with the ARG_FLAG_VALID constant.
	 * ARG_FLAG_VALID | (type const)
	 */
	public const ARG_TYPE_INT = 0x01;
	public const ARG_TYPE_FLOAT = 0x03;
	public const ARG_TYPE_VALUE = 0x04;
	public const ARG_TYPE_WILDCARD_INT = 0x05;
	public const ARG_TYPE_OPERATOR = 0x06;
	public const ARG_TYPE_COMPARE_OPERATOR = 0x07;
	public const ARG_TYPE_TARGET = 0x08;

	public const ARG_TYPE_WILDCARD_TARGET = 0x0a;

	public const ARG_TYPE_FILEPATH = 0x11;

	public const ARG_TYPE_FULL_INTEGER_RANGE = 0x17;

	public const ARG_TYPE_EQUIPMENT_SLOT = 0x26;
	public const ARG_TYPE_STRING = 0x27;

	public const ARG_TYPE_INT_POSITION = 0x2f;
	public const ARG_TYPE_POSITION = 0x30;

	public const ARG_TYPE_MESSAGE = 0x33;

	public const ARG_TYPE_RAWTEXT = 0x35;

	public const ARG_TYPE_JSON = 0x39;

	public const ARG_TYPE_BLOCK_STATES = 0x43;

	public const ARG_TYPE_COMMAND = 0x46;

	/**
	 * Enums are a little different: they are composed as follows:
	 * ARG_FLAG_ENUM | ARG_FLAG_VALID | (enum index)
	 */
	public const ARG_FLAG_ENUM = 0x200000;

	/** This is used for /xp <level: int>L. It can only be applied to integer parameters. */
	public const ARG_FLAG_POSTFIX = 0x1000000;

	public const HARDCODED_ENUM_NAMES = [
		"CommandName" => true
	];

	/**
	 * @var CommandData[]
	 */
	public array $commandData = [];

	/**
	 * @var CommandHardEnum[]
	 */
	public array $hardcodedEnums = [];

	/**
	 * @var CommandSoftEnum[]
	 */
	public array $softEnums = [];

	/**
	 * @var array<int, array{enum: CommandHardEnum, affectedValue: string, constraints: int[]}>
	 */
	public array $enumConstraints = [];

	public static function fromLatest(AvailableCommandsPacket $pk) : self{
		$result = new self;
		$disassembled = AvailableCommandsPacketDisassembler::disassemble($pk);
		$result->commandData = $disassembled->commandData;
		foreach($disassembled->unusedHardEnums as $enum){
			if(isset(self::HARDCODED_ENUM_NAMES[$enum->getName()])){
				$result->hardcodedEnums[] = $enum;
			}
		}
		$result->softEnums = array_values($disassembled->unusedSoftEnums);
		return $result;
	}

	protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{

		$in = \MultiVersion\network\proto\v419\v419PacketSerializer::reader($in, $protocolId);
		/** @var string[] $enumValues */
		$enumValues = [];
		for($i = 0, $enumValuesCount = $in->getUnsignedVarInt(); $i < $enumValuesCount; ++$i){
			$enumValues[] = $in->getString();
		}

		/** @var string[] $postfixes */
		$postfixes = [];
		for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
			$postfixes[] = $in->getString();
		}

		/** @var CommandHardEnum[] $enums */
		$enums = [];
		for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
			$enums[] = $enum = $this->getEnum($enumValues, $in);
			if(isset(self::HARDCODED_ENUM_NAMES[$enum->getName()])){
				$this->hardcodedEnums[] = $enum;
			}
		}

		for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
			$this->commandData[] = $this->getCommandData($enums, $postfixes, [], $in);
		}

		for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
			$this->softEnums[] = $this->getSoftEnum($in);
		}

		for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
			$this->enumConstraints[] = $this->getEnumConstraint($enums, $enumValues, $in);
		}
	}

	/**
	 * @param string[] $enumValueList
	 *
	 * @throws PacketDecodeException
	 * @throws BinaryDataException
	 */
	protected function getEnum(array $enumValueList, PacketSerializer $in) : CommandHardEnum{
		$enumName = $in->getString();
		$enumValues = [];

		$listSize = count($enumValueList);

		for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
			$index = $this->getEnumValueIndex($listSize, $in);
			if(!isset($enumValueList[$index])){
				throw new PacketDecodeException("Invalid enum value index $index");
			}
			$enumValues[] = $enumValueList[$index];
		}

		return new CommandHardEnum($enumName, $enumValues);
	}

	/**
	 * @throws BinaryDataException
	 */
	protected function getSoftEnum(PacketSerializer $in) : CommandSoftEnum{
		$enumName = $in->getString();
		$enumValues = [];

		for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
			$enumValues[] = $in->getString();
		}

		return new CommandSoftEnum($enumName, $enumValues);
	}

	/**
	 * @param int[] $enumValueMap
	 */
	protected function putEnum(CommandHardEnum|CommandSoftEnum $enum, array $enumValueMap, PacketSerializer $out) : void{
		$out->putString($enum->getName());

		$values = $enum->getValues();
		$out->putUnsignedVarInt(count($values));
		$listSize = count($enumValueMap);
		foreach($values as $value){
			$valueString = $value instanceof ConstrainedEnumValue ? $value->getValue() : $value;
			if(!isset($enumValueMap[$valueString])){
				throw new LogicException("Enum value '$valueString' doesn't have a value index");
			}
			$this->putEnumValueIndex($enumValueMap[$valueString], $listSize, $out);
		}
	}

	protected function putSoftEnum(CommandSoftEnum $enum, PacketSerializer $out) : void{
		$out->putString($enum->getName());

		$values = $enum->getValues();
		$out->putUnsignedVarInt(count($values));
		foreach($values as $value){
			$out->putString($value);
		}
	}

	/**
	 * @throws BinaryDataException
	 */
	protected function getEnumValueIndex(int $valueCount, PacketSerializer $in) : int{
		if($valueCount < 256){
			return $in->getByte();
		}elseif($valueCount < 65536){
			return $in->getLShort();
		}else{
			return $in->getLInt();
		}
	}

	protected function putEnumValueIndex(int $index, int $valueCount, PacketSerializer $out) : void{
		if($valueCount < 256){
			$out->putByte($index);
		}elseif($valueCount < 65536){
			$out->putLShort($index);
		}else{
			$out->putLInt($index);
		}
	}

	/**
	 * @param CommandHardEnum[] $enums
	 * @param string[]          $enumValues
	 *
	 * @return array{enum: CommandHardEnum, affectedValue: string, constraints: int[]}
	 *
	 * @throws PacketDecodeException
	 * @throws BinaryDataException
	 */
	protected function getEnumConstraint(array $enums, array $enumValues, PacketSerializer $in) : array{
		$valueIndex = $in->getLInt();
		if(!isset($enumValues[$valueIndex])){
			throw new PacketDecodeException("Enum constraint refers to unknown enum value index $valueIndex");
		}
		$enumIndex = $in->getLInt();
		if(!isset($enums[$enumIndex])){
			throw new PacketDecodeException("Enum constraint refers to unknown enum index $enumIndex");
		}
		$enum = $enums[$enumIndex];
		$enumValueStrings = [];
		foreach($enum->getValues() as $enumValue){
			$enumValueStrings[] = $enumValue instanceof ConstrainedEnumValue ? $enumValue->getValue() : $enumValue;
		}
		$valueOffset = array_search($enumValues[$valueIndex], $enumValueStrings, true);
		if($valueOffset === false){
			throw new PacketDecodeException("Value \"" . $enumValues[$valueIndex] . "\" does not belong to enum \"" . $enum->getName() . "\"");
		}

		$constraintIds = [];
		for($i = 0, $count = $in->getUnsignedVarInt(); $i < $count; ++$i){
			$constraintIds[] = $in->getByte();
		}

		return [
			"enum" => $enum,
			"affectedValue" => $enumValues[$valueIndex],
			"constraints" => $constraintIds
		];
	}

	/**
	 * @param int[]                                                   $enumIndexes string enum name -> int index
	 * @param int[]                                                   $enumValueIndexes string value -> int index
	 * @param array{enum: CommandHardEnum, affectedValue: string, constraints: int[]} $constraint
	 */
	protected function putEnumConstraint(array $constraint, array $enumIndexes, array $enumValueIndexes, PacketSerializer $out) : void{
		$out->putLInt($enumValueIndexes[$constraint["affectedValue"]] ?? -1);
		$out->putLInt($enumIndexes[$constraint["enum"]->getName()] ?? -1);
		$out->putUnsignedVarInt(count($constraint["constraints"]));
		foreach($constraint["constraints"] as $value){
			$out->putByte($value);
		}
	}

	/**
	 * @param CommandHardEnum[] $enums
	 * @param string[]          $postfixes
	 *
	 * @throws PacketDecodeException
	 * @throws BinaryDataException
	 */
	protected function getCommandData(array $enums, array $postfixes, array $allChainedSubCommandData, PacketSerializer $in) : CommandData{
		$name = $in->getString();
		$description = $in->getString();
		$flags = $in->getByte();
		$permission = $in->getByte();
		$aliases = $enums[$in->getLInt()] ?? null;
		$overloads = [];

		for($overloadIndex = 0, $overloadCount = $in->getUnsignedVarInt(); $overloadIndex < $overloadCount; ++$overloadIndex){
			$parameters = [];
			for($paramIndex = 0, $paramCount = $in->getUnsignedVarInt(); $paramIndex < $paramCount; ++$paramIndex){
				$parameter = new CommandParameter();
				$parameter->paramName = $in->getString();
				$parameter->paramType = $in->getLInt();
				$parameter->isOptional = $in->getBool();
				$parameter->flags = $in->getByte();

				if(($parameter->paramType & self::ARG_FLAG_ENUM) !== 0){
					$index = ($parameter->paramType & 0xffff);
					$parameter->enum = $enums[$index] ?? null;
					if($parameter->enum === null){
						throw new PacketDecodeException("deserializing $name parameter $parameter->paramName: expected enum at $index, but got none");
					}
				}elseif(($parameter->paramType & self::ARG_FLAG_POSTFIX) !== 0){
					$index = ($parameter->paramType & 0xffff);
					$parameter->postfix = $postfixes[$index] ?? null;
					if($parameter->postfix === null){
						throw new PacketDecodeException("deserializing $name parameter $parameter->paramName: expected postfix at $index, but got none");
					}
				}elseif(($parameter->paramType & self::ARG_FLAG_VALID) === 0){
					throw new PacketDecodeException("deserializing $name parameter $parameter->paramName: Invalid parameter type 0x" . dechex($parameter->paramType));
				}

				$parameters[$paramIndex] = $parameter;
			}
			$overloads[$overloadIndex] = new CommandOverload(false, $parameters);
		}

		return new CommandData($name, $description, $flags, $permission, $aliases, $overloads, []);
	}

	/**
	 * @param int[] $enumIndexes string enum name -> int index
	 * @param int[] $postfixIndexes
	 */
	protected function putCommandData(CommandData $data, array $enumIndexes, array $softEnumIndexes, array $postfixIndexes, array $chainedSubCommandDataIndexes, PacketSerializer $out) : void{
		$out->putString($data->getName());
		$out->putString($data->getDescription());
		$out->putByte($data->getFlags());
		try{
			$out->putByte(CommandPermissions::fromName($data->getPermission()));
		}catch(\InvalidArgumentException){
			$out->putByte(CommandPermissions::NORMAL);
		}

		$aliases = $data->getAliases();
		if($aliases !== null){
			$out->putLInt($enumIndexes[$aliases->getName()] ?? -1);
		}else{
			$out->putLInt(-1);
		}

		$out->putUnsignedVarInt(count($data->getOverloads()));
		foreach($data->getOverloads() as $overload){
			$out->putUnsignedVarInt(count($overload->getParameters()));
			foreach($overload->getParameters() as $parameter){
				$out->putString($parameter->paramName);

				if($parameter->enum !== null){
					$type = self::ARG_FLAG_ENUM | self::ARG_FLAG_VALID | ($enumIndexes[$parameter->enum->getName()] ?? -1);
				}elseif($parameter->postfix !== null){
					if(!isset($postfixIndexes[$parameter->postfix])){
						throw new LogicException("Postfix '$parameter->postfix' not in postfixes array");
					}
					$type = self::ARG_FLAG_POSTFIX | $postfixIndexes[$parameter->postfix];
				}else{
					$type = $parameter->paramType;
				}

				$out->putLInt($type);
				$out->putBool($parameter->isOptional);
				$out->putByte($parameter->flags);
			}
		}
	}

	protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{

		$out = \MultiVersion\network\proto\v419\v419PacketSerializer::writer($out, $protocolId);
		/** @var int[] $enumValueIndexes */
		$enumValueIndexes = [];
		/** @var int[] $postfixIndexes */
		$postfixIndexes = [];
		/** @var int[] $enumIndexes */
		$enumIndexes = [];
		/** @var array<int, CommandHardEnum|CommandSoftEnum> $enums */
		$enums = [];

		$addEnumFn = static function(CommandHardEnum|CommandSoftEnum $enum) use (&$enums, &$enumIndexes, &$enumValueIndexes) : void{
			if(!isset($enumIndexes[$enum->getName()])){
				$enums[$enumIndexes[$enum->getName()] = count($enumIndexes)] = $enum;
			}
			foreach($enum->getValues() as $value){
				$valueString = $value instanceof ConstrainedEnumValue ? $value->getValue() : $value;
				$enumValueIndexes[$valueString] = $enumValueIndexes[$valueString] ?? count($enumValueIndexes);
			}
		};
		foreach($this->hardcodedEnums as $enum){
			$addEnumFn($enum);
		}
		foreach($this->commandData as $commandData){
			$aliases = $commandData->getAliases();
			if($aliases !== null){
				$addEnumFn($aliases);
			}
			foreach($commandData->getOverloads() as $overload){
				foreach($overload->getParameters() as $parameter){
					if($parameter->enum !== null){
						$addEnumFn($parameter->enum);
					}

					if($parameter->postfix !== null){
						$postfixIndexes[$parameter->postfix] = $postfixIndexes[$parameter->postfix] ?? count($postfixIndexes);
					}
				}
			}
		}

		$out->putUnsignedVarInt(count($enumValueIndexes));
		foreach($enumValueIndexes as $enumValue => $index){
			$out->putString((string) $enumValue);
		}

		$out->putUnsignedVarInt(count($postfixIndexes));
		foreach($postfixIndexes as $postfix => $index){
			$out->putString((string) $postfix);
		}

		$out->putUnsignedVarInt(count($enums));
		foreach($enums as $enum){
			$this->putEnum($enum, $enumValueIndexes, $out);
		}

		$out->putUnsignedVarInt(count($this->commandData));
		foreach($this->commandData as $data){
			$this->putCommandData($data, $enumIndexes, [], $postfixIndexes, [], $out);
		}

		$out->putUnsignedVarInt(count($this->softEnums));
		foreach($this->softEnums as $enum){
			$this->putSoftEnum($enum, $out);
		}

		$out->putUnsignedVarInt(count($this->enumConstraints));
		foreach($this->enumConstraints as $constraint){
			$this->putEnumConstraint($constraint, $enumIndexes, $enumValueIndexes, $out);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return false;
	}
}
