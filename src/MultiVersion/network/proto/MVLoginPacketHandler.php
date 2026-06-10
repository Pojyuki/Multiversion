<?php

namespace MultiVersion\network\proto;

use Closure;
use JsonMapper;
use JsonMapper_Exception;
use MultiVersion\MultiVersion;
use MultiVersion\network\MVNetworkSession;
use MultiVersion\network\proto\utils\ReflectionUtils;
use MultiVersion\network\proto\v419\packets\v419LoginPacket;
use pocketmine\entity\InvalidSkinException;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\network\mcpe\auth\ProcessLegacyLoginTask;
use pocketmine\network\mcpe\handler\LoginPacketHandler;
use pocketmine\network\mcpe\JwtException;
use pocketmine\network\mcpe\JwtUtils;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\NetworkSettingsPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\RequestNetworkSettingsPacket;
use pocketmine\network\mcpe\protocol\types\login\clientdata\ClientData;
use pocketmine\network\mcpe\protocol\types\login\clientdata\ClientDataToSkinDataHelper;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\Player;
use pocketmine\player\PlayerInfo;
use pocketmine\player\XboxLivePlayerInfo;
use pocketmine\Server;
use Ramsey\Uuid\Uuid;
use ReflectionException;

class MVLoginPacketHandler extends LoginPacketHandler{

	private MVNetworkSession $session;

	public function __construct(private Server $server, MVNetworkSession $session, private Closure $playerInfoConsumer, private Closure $authCallback, private \Closure $onSuccess){
		$this->session = $session;
		parent::__construct($server, $session, $playerInfoConsumer, $authCallback);
	}

	public function handleRequestNetworkSettings(RequestNetworkSettingsPacket $packet) : bool {
		$protocol = $packet->getProtocolVersion();
		if(!in_array($protocol, MultiVersion::getProtocols(), true)){
			// Native protocols (handled by underlying software) still need NetworkSettings response here,
			// because this plugin replaces the default session start handler.
			$this->session->setNativeProtocolId($protocol);
			$this->session->sendDataPacket(NetworkSettingsPacket::create(
				NetworkSettingsPacket::COMPRESS_EVERYTHING,
				$this->session->getCompressor()->getNetworkId(),
				false,
				0,
				0
			));
			($this->onSuccess)();

			return true;
		}

		$this->session->setPacketTranslator(MultiVersion::getTranslator($protocol));
		if($protocol !== ProtocolInfo::CURRENT_PROTOCOL){
			$this->session->getLogger()->info("Translating packets from protocol $protocol");
			ReflectionUtils::setProperty(RequestNetworkSettingsPacket::class, $packet, "protocolVersion", ProtocolInfo::CURRENT_PROTOCOL); // hack, jk this entire thing is a hack lmao
		}
		$this->session->sendDataPacket(NetworkSettingsPacket::create(
			NetworkSettingsPacket::COMPRESS_EVERYTHING,
			$this->session->getCompressor()->getNetworkId(),
			false,
			0,
			0
		));
		($this->onSuccess)();

		return true;
	}

	/**
	 * @throws ReflectionException
	 */
	public function handleLogin(LoginPacket $packet) : bool{
		$this->normalizeSelfSignedAuthInfo($packet);

		if(!in_array($packet->protocol, MultiVersion::getProtocols(), true)){
			// Let the underlying server handle protocols not explicitly translated by this plugin.
			$this->session->setNativeProtocolId($packet->protocol);
			return parent::handleLogin($packet);
		}

		$this->session->setPacketTranslator(MultiVersion::getTranslator($packet->protocol));
        $protocol = $packet->protocol;
		if($packet->protocol !== ProtocolInfo::CURRENT_PROTOCOL){
			$this->session->getLogger()->info("Translating packets from protocol $packet->protocol");
			$packet->protocol = ProtocolInfo::CURRENT_PROTOCOL; // hack, jk this entire thing is a hack lmao
		}

        if($protocol == 419) {
            return $this->v419handleLogin($packet);
        }

		// Old protocol logins (e.g. 486) may still send legacy auth chain JSON
		// without AuthenticationType. Route those through legacy login flow.
		if($packet instanceof login\LoginPacket){
			$legacyChain = $this->extractLegacyChainFromAuthInfoJson($packet->authInfoJson ?? "");
			if($legacyChain !== null){
				$packet->chainDataJwt = $legacyChain;
				return $this->v419handleLogin($packet);
			}
		}

        return parent::handleLogin($packet);
	}

	private function normalizeSelfSignedAuthInfo(LoginPacket $packet) : void{
		try{
			$authInfo = json_decode($packet->authInfoJson, associative: true, flags: JSON_THROW_ON_ERROR);
		}catch(\JsonException){
			return;
		}
		if(!is_array($authInfo)){
			return;
		}
		if(($authInfo["AuthenticationType"] ?? null) !== 2){
			return;
		}
		if(isset($authInfo["Certificate"]) && is_string($authInfo["Certificate"]) && $authInfo["Certificate"] !== ""){
			return;
		}

		$token = $authInfo["Token"] ?? null;
		if(!is_string($token) || $token === ""){
			return;
		}

		try{
			$authInfo["Certificate"] = json_encode(["chain" => [$token]], flags: JSON_THROW_ON_ERROR);
			$packet->authInfoJson = json_encode($authInfo, flags: JSON_THROW_ON_ERROR);
		}catch(\JsonException){
			return;
		}
	}

	public function v419handleLogin(login\LoginPacket $packet) : bool{
		$chainData = null;
		if(isset($packet->chainDataJwt) && is_object($packet->chainDataJwt)){
			$chainData = $packet->chainDataJwt;
		}else{
			$chainData = $this->extractLegacyChainFromAuthInfoJson($packet->authInfoJson ?? "");
			if($chainData !== null){
				$packet->chainDataJwt = $chainData;
			}
		}
		if($chainData === null){
			throw new PacketHandlingException("Legacy chain data missing for protocol 419 login");
		}

		$extraData = $this->_v419fetchAuthData($chainData);

        if(!Player::isValidUserName($extraData->displayName)){
            $this->session->disconnectWithError(KnownTranslationFactory::disconnectionScreen_invalidName());

            return true;
        }

        $clientData = $this->parseClientData($packet->clientDataJwt);

        try{
            $skin = $this->session->getTypeConverter()->getSkinAdapter()->fromSkinData(ClientDataToSkinDataHelper::fromClientData($clientData));
        }catch(\InvalidArgumentException | InvalidSkinException $e){
            $this->session->disconnectWithError(
                reason: "Invalid skin: " . $e->getMessage(),
                disconnectScreenMessage: KnownTranslationFactory::disconnectionScreen_invalidSkin()
            );

            return true;
        }

        if(!Uuid::isValid($extraData->identity)){
            throw new PacketHandlingException("Invalid login UUID");
        }
        $uuid = Uuid::fromString($extraData->identity);
        $arrClientData = (array) $clientData;
        $arrClientData["TitleID"] = $extraData->titleId;

        if($extraData->XUID !== ""){
            $playerInfo = new XboxLivePlayerInfo(
                $extraData->XUID,
                $extraData->displayName,
                $uuid,
                $skin,
                $clientData->LanguageCode,
                $arrClientData
            );
        }else{
            $playerInfo = new PlayerInfo(
                $extraData->displayName,
                $uuid,
                $skin,
                $clientData->LanguageCode,
                $arrClientData
            );
        }
        ($this->playerInfoConsumer)($playerInfo);

        $ev = $this->createPlayerPreLoginEvent($playerInfo);
        if($this->server->getNetwork()->getValidConnectionCount() > $this->server->getMaxPlayers()){
            $ev->setKickFlag(PlayerPreLoginEvent::KICK_FLAG_SERVER_FULL, KnownTranslationFactory::disconnectionScreen_serverFull());
        }
        if(!$this->server->isWhitelisted($playerInfo->getUsername())){
            $ev->setKickFlag(PlayerPreLoginEvent::KICK_FLAG_SERVER_WHITELISTED, KnownTranslationFactory::pocketmine_disconnect_whitelisted());
        }

        $banMessage = null;
        if(($banEntry = $this->server->getNameBans()->getEntry($playerInfo->getUsername())) !== null){
            $banReason = $banEntry->getReason();
            $banMessage = $banReason === "" ? KnownTranslationFactory::pocketmine_disconnect_ban_noReason() : KnownTranslationFactory::pocketmine_disconnect_ban($banReason);
        }elseif(($banEntry = $this->server->getIPBans()->getEntry($this->session->getIp())) !== null){
            $banReason = $banEntry->getReason();
            $banMessage = KnownTranslationFactory::pocketmine_disconnect_ban($banReason !== "" ? $banReason : KnownTranslationFactory::pocketmine_disconnect_ban_ip());
        }
        if($banMessage !== null){
            $ev->setKickFlag(PlayerPreLoginEvent::KICK_FLAG_BANNED, $banMessage);
        }

        $ev->call();
        if(!$ev->isAllowed()){
            $this->session->disconnect($ev->getFinalDisconnectReason(), $ev->getFinalDisconnectScreenMessage());
            return true;
        }

        $this->_v419processLogin($packet, $ev->isAuthRequired());

        return true;
    }

    /**
     * TODO: This is separated for the purposes of allowing plugins (like Specter) to hack it and bypass authentication.
     * In the future this won't be necessary.
     *
     * @throws \InvalidArgumentException
     */
	protected function _v419processLogin(login\LoginPacket $packet, bool $authRequired) : void{
        $this->session->setHandler(null); //drop packets received during login verification
		$rootAuthKeyDer = base64_decode(ProcessLegacyLoginTask::LEGACY_MOJANG_ROOT_PUBLIC_KEY, true);
		if($rootAuthKeyDer === false){
			throw new \InvalidArgumentException("Failed to base64-decode hardcoded Mojang root public key");
		}
		$legacyCompatAuthCallback = function(bool $isAuthenticated, bool $taskAuthRequired, $error, ?string $clientPublicKey) : void{
			// Mirror legacy "1.16~1.26.10" behavior: if chain validation succeeds,
			// treat it as authenticated even when Mojang root key pinning doesn't match.
			if($error === null){
				$isAuthenticated = true;
			}
			($this->authCallback)($isAuthenticated, $taskAuthRequired, $error, $clientPublicKey);
		};
        $this->server->getAsyncPool()->submitTask(new ProcessLegacyLoginTask(
            $packet->chainDataJwt->chain,
            $packet->clientDataJwt,
            rootAuthKeyDer: $rootAuthKeyDer,
            authRequired: $authRequired,
            onCompletion: $legacyCompatAuthCallback
        ));
    }

	/**
	 * @throws PacketHandlingException
	 */
	protected function parseClientData(string $clientDataJwt) : ClientData{
		try{
			[, $clientDataClaims,] = JwtUtils::parse($clientDataJwt);
		}catch(JwtException $e){
			throw PacketDecodeException::wrap($e);
		}

		if($this->session->hasPacketTranslator()){
			$this->session->getPacketTranslator()->injectClientData($clientDataClaims);
		}

		$mapper = new JsonMapper;
		$mapper->bEnforceMapType = false; //TODO: we don't really need this as an array, but right now we don't have enough models
		$mapper->bExceptionOnMissingData = false;
		$mapper->bExceptionOnUndefinedProperty = false;
		try{
			$clientData = $mapper->map($clientDataClaims, new ClientData);
		}catch(JsonMapper_Exception $e){
			throw PacketDecodeException::wrap($e);
		}
		return $clientData;
	}

    /**
     * @throws PacketHandlingException
     */
    protected function _v419fetchAuthData(object $chain) : object{
        $extraData = null;

        if(!isset($chain->chain) || !is_array($chain->chain)){
            throw new PacketHandlingException("'chain' not found or invalid in chain data");
        }

        foreach($chain->chain as $jwt){
            //validate every chain element
            try{
                [, $claims, ] = JwtUtils::parse($jwt);
            }catch(JwtException $e){
                throw PacketHandlingException::wrap($e);
            }
            if(isset($claims["extraData"])){
                if($extraData !== null){
                    throw new PacketHandlingException("Found 'extraData' more than once in chainData");
                }

                if(!is_array($claims["extraData"])){
                    throw new PacketHandlingException("'extraData' key should be an array");
                }

                $payload = $claims["extraData"];
                $extraData = (object) [
                    "identity" => (string) ($payload["identity"] ?? ""),
                    "displayName" => (string) ($payload["displayName"] ?? ""),
                    "XUID" => (string) ($payload["XUID"] ?? ($payload["xuid"] ?? "")),
                    "titleId" => (string) ($payload["titleId"] ?? ($payload["TitleID"] ?? "")),
                ];
            }
        }
        if($extraData === null){
            throw new PacketHandlingException("'extraData' not found in chain data");
        }
        return $extraData;
    }

	/**
	 * @return object{chain:list<string>}|null
	 */
	private function extractLegacyChainFromAuthInfoJson(string $authInfoJson) : ?object{
		if($authInfoJson === ""){
			return null;
		}

		try{
			$decoded = json_decode($authInfoJson, associative: true, flags: JSON_THROW_ON_ERROR);
		}catch(\JsonException){
			return null;
		}
		if(!is_array($decoded)){
			return null;
		}
		if(isset($decoded["AuthenticationType"])){
			return null;
		}
		if(!isset($decoded["chain"]) || !is_array($decoded["chain"])){
			return null;
		}

		$chain = [];
		foreach($decoded["chain"] as $entry){
			if(!is_string($entry)){
				return null;
			}
			$chain[] = $entry;
		}
		if($chain === []){
			return null;
		}

		return (object) ["chain" => $chain];
	}

	/**
	 * Build PlayerPreLoginEvent in a way that's compatible with different PMMP forks/signatures.
	 */
	private function createPlayerPreLoginEvent(PlayerInfo $playerInfo) : PlayerPreLoginEvent{
		$constructor = new \ReflectionMethod(PlayerPreLoginEvent::class, "__construct");
		$args = [];

		foreach($constructor->getParameters() as $index => $parameter){
			if($index === 0){
				$args[] = $playerInfo;
				continue;
			}

			$type = $parameter->getType();
			$typeName = $type instanceof \ReflectionNamedType ? ltrim($type->getName(), "\\") : null;
			$parameterName = strtolower($parameter->getName());

			if($typeName !== null && is_a($typeName, \pocketmine\network\mcpe\NetworkSession::class, true)){
				$args[] = $this->session;
				continue;
			}

			if($typeName === "string"){
				$args[] = (str_contains($parameterName, "ip") || str_contains($parameterName, "address")) ? $this->session->getIp() : "";
				continue;
			}

			if($typeName === "int"){
				$args[] = str_contains($parameterName, "port") ? $this->session->getPort() : 0;
				continue;
			}

			if($typeName === "bool"){
				$args[] = str_contains($parameterName, "auth") ? $this->server->requiresAuthentication() : false;
				continue;
			}

			if($parameter->isDefaultValueAvailable()){
				$args[] = $parameter->getDefaultValue();
				continue;
			}

			if($parameter->allowsNull()){
				$args[] = null;
				continue;
			}

			throw new \RuntimeException("Unsupported PlayerPreLoginEvent constructor parameter $" . $parameter->getName());
		}

		return new PlayerPreLoginEvent(...$args);
	}
}
