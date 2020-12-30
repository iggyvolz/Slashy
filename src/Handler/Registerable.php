<?php

declare(strict_types=1);

namespace iggyvolz\Slashy\Handler;

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Promise;
use iggyvolz\Slashy\Model\ApplicationCommandInteraction;
use iggyvolz\Slashy\Model\ApplicationCommandOption;
use iggyvolz\Slashy\Model\ApplicationCommandOptionAttribute;
use JetBrains\PhpStorm\ArrayShape;
use LogicException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;
use RestCord\Model\Guild\Guild;
use Generator;
use RuntimeException;

use function Amp\call;

abstract class Registerable
{
    protected function __construct()
    {
    }

    public static function unregisterAll(?int $guild, int $clientID, string $botToken): Promise
    {
        return call(function () use ($guild, $clientID, $botToken): Generator {
            foreach (yield self::getAllRegistered($guild, $clientID, $botToken) as $id => $name) {
                yield self::unregister($guild, $id, $name);
            }
        });
    }

    /**
     * @param int|null $guild
     * @param int $clientID
     * @param string $botToken
     * @return Promise<array<int,string>>
     */
    private static function getAllRegistered(?int $guild, int $clientID, string $botToken): Promise
    {
        return call(function () use ($guild, $clientID, $botToken): Generator {
            $client = HttpClientBuilder::buildDefault();
            $url = is_null($guild) ? "https://discord.com/api/v8/applications/$clientID/commands" : "https://discord.com/api/v8/applications/$clientID/guilds/$guild/commands";
            $request = new Request($url);
            $request->setHeader("Authorization", "Bot " . $botToken);
            $response = yield $client->request($request);
            $body = yield $response->getBody()->read();
            /**
             * @var mixed $body
             */
            $body = json_decode($body ?? "", true);
            if (!is_array($body)) {
                throw new RuntimeException("Invalid result received from Discord");
            }
            $keys = array_map(/** @param array{id: int} $arr */fn(array $arr): int => $arr["id"], $body);
            $values = array_map(/** @param array{name: string} $arr */fn(array $arr): string => $arr["name"], $body);
            $result = array_combine($keys, $values);
            if (!$result) {
                throw new LogicException();
            }
            return $result;
        });
    }
    abstract public static function getDescription(): string;
    abstract public static function getName(): string;

    /**
     * @param class-string $attributeClass
     * @return ReflectionProperty[]
     */
    public static function getOptionProperties(string $attributeClass): array
    {
        $props = (new ReflectionClass(static::class))->getProperties();
        return array_values(array_filter($props, fn(ReflectionProperty $rp): bool => !empty($rp->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF))));
    }

    /**
     * @return ApplicationCommandOption[]
     */
    public static function getOptions(): array
    {
        $props = static::getOptionProperties(ApplicationCommandOptionAttribute::class);
        return array_map(fn(ReflectionProperty $rp): ApplicationCommandOption => $rp->getAttributes(ApplicationCommandOptionAttribute::class, ReflectionAttribute::IS_INSTANCEOF)[0]->newInstance()->toOption($rp), $props);
    }

    /**
     * Class => guild (-1 for global) => id
     * @var array<string,array<int,int>>
     */
    private static array $registrations = [];
    /**
     * Class => guild (-1 for global) => name
     * @var array<string,array<int,string>>
     */
    private static array $registrationNames = [];

    public static function register(null | Guild | int $guild, int $clientID, string $botToken): Promise
    {
        return call(function () use ($guild, $clientID, $botToken): Generator {
            if ($guild instanceof Guild) {
                $guild = $guild->id;
            }
            ApplicationCommandInteraction::setHandlerClass($name = static::getName(), $guild ?? -1, static::class);
            self::$registrations[static::class] ??= [];
            self::$registrationNames[static::class] ??= [];
            $client = HttpClientBuilder::buildDefault();
            $url = is_null($guild) ? "https://discord.com/api/v8/applications/$clientID/commands" : "https://discord.com/api/v8/applications/$clientID/guilds/$guild/commands";
            $request = new Request($url, "POST");
            $request->setHeader("Content-Type", "Application/json");
            $request->setHeader("Authorization", "Bot " . $botToken);
            $body = self::getRegistrationBody();
            $request->setBody(json_encode($body));
            $response = yield $client->request($request);
            $body = yield $response->getBody()->read();
            $dbody = json_decode($body ?? "", true);
            if (!is_array($dbody)) {
                throw new RuntimeException("Unexpected type " . get_debug_type($body) . " from Discord");
            }
            $id = $dbody["id"] ?? null;
            if (!is_int($id)) {
                throw new RuntimeException("No ID returned from Discord");
            }
            self::$registrations[static::class][$guild ?? -1] = $id;
            self::$registrationNames[static::class][$guild ?? -1] = $name;
        });
    }
    public static function unregister(null | Guild | int $guild, int $clientID, string $botToken, ?int $id = null, ?string $name = null): Promise
    {
        return call(function () use ($guild, $clientID, $botToken, $id, $name): Generator {
            if ($guild instanceof Guild) {
                $guild = $guild->id;
            }
            if (is_null($id) || is_null($name)) {
                $id = self::$registrations[static::class][$guild ?? -1] ?? null;
                $name = self::$registrationNames[static::class][$guild ?? -1] ?? null;
                if (is_null($id) || is_null($name)) {
                    throw new RuntimeException("Could not unregister non-registered handler " . static::class);
                }
            }
            $client = HttpClientBuilder::buildDefault();
            $url = is_null($guild) ? "https://discord.com/api/v8/applications/$clientID/commands/$id" : "https://discord.com/api/v8/applications/$clientID/guilds/$guild/commands/$id";
            $request = new Request($url, "DELETE");
            $request->setHeader("Content-Type", "Application/json");
            $request->setHeader("Authorization", "Bot " . $botToken);
            yield $client->request($request);
        });
    }

    /**
     * Re-registers the command if it has been registered this session, otherwise does nothing
     * @param int|null $guild
     * @param int $clientID
     * @param string $botToken
     * @return Promise
     */
    public static function update(?int $guild, int $clientID, string $botToken): Promise
    {
        return call(function () use ($guild, $clientID, $botToken): Generator {
            if (!is_null(self::$registrations[static::class][$guild ?? -1] ?? null)) {
                yield static::register($guild, $clientID, $botToken);
            }
        });
    }

    /**
     * @return array{name: string, description: string, options: ApplicationCommandOption[]}
     * @phan-suppress PhanAbstractStaticMethodCallInStatic
     */
    #[ArrayShape(["name" => "string", "description" => "string", "options" => "\iggyvolz\Slashy\Model\ApplicationCommandOption[]"])]
    protected static function getRegistrationBody(): array
    {
        return [
            "name" => static::getName(),
            "description" => static::getDescription(),
            "options" => static::getOptions()
        ];
    }
}
