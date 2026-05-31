<?php

namespace MultiVersion\network\proto\login;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\LoginPacket as LoginPacketPM;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\PacketSerializer;
use function strlen;

class LoginPacket extends LoginPacketPM
{
    public const NETWORK_ID = ProtocolInfo::LOGIN_PACKET;

    public int $protocol;
    public string $authInfoJson;
    public string $clientDataJwt;
    public object $chainDataJwt;

    /**
     * @generate-create-func
     */
    public static function create(int $protocol, string $authInfoJson, string $clientDataJwt) : self{
        $result = new self;
        $result->protocol = $protocol;
        $result->authInfoJson = $authInfoJson;
        $result->clientDataJwt = $clientDataJwt;
        return $result;
    }

    public function canBeSentBeforeLogin() : bool{
        return true;
    }

    protected function decodePayload(\pmmp\encoding\ByteBufferReader $in, ?int $protocolId = null) : void{
        // 419 uses legacy chain/clientData login format.
        // Newer protocols (e.g. 486+) must use PMMP upstream decoding so auth info
        // includes fields like AuthenticationType required by LoginPacketHandler.
        if($protocolId !== 419){
            parent::decodePayload($in, $protocolId);
            return;
        }

    	$in = PacketSerializer::reader($in, $protocolId);
        $this->protocol = $in->getInt();
        $this->_419decodeConnectionRequest($in->getString());
    }

    public function _419decodeConnectionRequest(string $binary) : void{
        $connRequestReader = new ByteBufferReader($binary);

        $chainDataJsonLength = LE::readUnsignedInt($connRequestReader);
        if($chainDataJsonLength <= 0){
            throw new PacketDecodeException("Length of chain data JSON must be positive");
        }
        try{
            $chainDataJson = json_decode($connRequestReader->readByteArray($chainDataJsonLength), associative: true, flags: JSON_THROW_ON_ERROR);
        }catch(\JsonException $e){
            throw new PacketDecodeException("Failed decoding chain data JSON: " . $e->getMessage());
        }
        if(!is_array($chainDataJson) || count($chainDataJson) !== 1 || !isset($chainDataJson["chain"])){
            throw new PacketDecodeException("Chain data must be a JSON object containing only the 'chain' element");
        }
        if(!is_array($chainDataJson["chain"])){
            throw new PacketDecodeException("Chain data 'chain' element must be a list of strings");
        }
        $jwts = [];
        foreach($chainDataJson["chain"] as $jwt){
            if(!is_string($jwt)){
                throw new PacketDecodeException("Chain data 'chain' must contain only strings");
            }
            $jwts[] = $jwt;
        }
        $this->chainDataJwt = (object) ["chain" => $jwts];

        $clientDataJwtLength = LE::readUnsignedInt($connRequestReader);
        if($clientDataJwtLength <= 0){
            throw new PacketDecodeException("Length of clientData JWT must be positive");
        }
        $this->clientDataJwt = $connRequestReader->readByteArray($clientDataJwtLength);
    }

    protected function decodeConnectionRequest(string $binary) : void{
        $connRequestReader = new ByteBufferReader($binary);

        $authInfoJsonLength = LE::readUnsignedInt($connRequestReader);
        if($authInfoJsonLength <= 0){
            throw new PacketDecodeException("Length of auth info JSON must be positive");
        }
        $this->authInfoJson = $connRequestReader->readByteArray($authInfoJsonLength);

        $clientDataJwtLength = LE::readUnsignedInt($connRequestReader);
        if($clientDataJwtLength <= 0){
            throw new PacketDecodeException("Length of clientData JWT must be positive");
        }
        $this->clientDataJwt = $connRequestReader->readByteArray($clientDataJwtLength);
    }

    protected function encodePayload(\pmmp\encoding\ByteBufferWriter $out, ?int $protocolId = null) : void{
        if($protocolId !== 419){
            parent::encodePayload($out, $protocolId);
            return;
        }

    	$out = PacketSerializer::writer($out, $protocolId);
        $out->putInt($this->protocol);
        $out->putString($this->encodeConnectionRequest());
    }

    protected function encodeConnectionRequest() : string{
        $connRequestWriter = new ByteBufferWriter();

        LE::writeUnsignedInt($connRequestWriter, strlen($this->authInfoJson));
        $connRequestWriter->writeByteArray($this->authInfoJson);

        LE::writeUnsignedInt($connRequestWriter, strlen($this->clientDataJwt));
        $connRequestWriter->writeByteArray($this->clientDataJwt);

        return $connRequestWriter->getData();
    }

    public function handle(PacketHandlerInterface $handler) : bool{
        return $handler->handleLogin($this);
    }
}

