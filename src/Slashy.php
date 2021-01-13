<?php

declare(strict_types=1);

namespace iggyvolz\Slashy;

use Amp\Promise;
use Closure;
use Amp\Loop;
use Elliptic\EdDSA;
use Generator;
use Amp\Http\Status;
use iggyvolz\Slashy\Handler\Command;
use iggyvolz\Slashy\Handler\Registerable;
use iggyvolz\Slashy\Model\Interaction;
use iggyvolz\Slashy\Model\InteractionResponse;
use LogicException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Amp\Socket\Server;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Throwable;

use function Amp\call;

class Slashy
{
    private LoggerInterface $logger;

    /**
     * Slashy constructor.
     * @param int $clientID
     * @param string $publicKey
     * @param string $botToken
     * @param list<string> $address
     * @param array<class-string<Registerable>, bool> $commands
     * @param list<int> $guilds
     * @param ?LoggerInterface $logger
     */
    public function __construct(
        private int $clientID,
        private string $publicKey,
        private string $botToken,
        private array $address,
        private array $commands,
        private array $guilds = [],
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? self::defaultLogger();
    }
    private static function defaultLogger(): LoggerInterface
    {
        $logger = new Logger("Slashy");
        $logger->pushHandler(new StreamHandler("php://stdout", Logger::DEBUG));
        return $logger;
    }

    public function run(): void
    {
        Loop::run(function (): Generator {
            // Auto-register commands which are marked as pre-registered
            foreach (array_keys(array_filter($this->commands)) as $command) {
                foreach ($this->guilds as $guild) {
                    $command::register($guild, $this->clientID, $this->botToken);
                }
            }
            $address = array_map([Server::class, "listen"], $this->address);
            $server = new HttpServer($address, new CallableRequestHandler(fn(Request $request): Promise => $this->handleRequest($request)), $this->logger);
            yield $server->start();
            Loop::onSignal(2, self::onSigint($server));
        });
    }

    private function handleRequest(Request $request): Promise
    {
        return call(function () use ($request): Generator {
            try {
                // Consume body once
                $body = yield $request->getBody()->read();
                $ok = yield self::verify($request, $body ?? "");
                if (!$ok) {
                    return new Response(Status::UNAUTHORIZED, stringOrStream: "invalid request signature");
                }
                /**
                 * @var mixed $body
                 */
                $body = json_decode($body ?? "{}", associative: true, flags: JSON_THROW_ON_ERROR);
                $response = yield Interaction::fromJson(self::assertAssocArray($body))->handle();
                $encresponse = json_encode($response, flags: JSON_THROW_ON_ERROR);
                if (!is_string($encresponse)) {
                    throw new LogicException();
                }
                return new Response(Status::OK, [
                    "content-type" => "application/json"
                ], $encresponse);
            } catch (Throwable $throwable) {
                $msg = get_class($throwable) . ": " . $throwable->getMessage() . " in " . $throwable->getFile() . ":" . $throwable->getLine();
                $this->logger->error($msg);
                $response = json_encode(InteractionResponse::response(
                    "Error: $msg",
                    showSource: true,
                ), flags: JSON_THROW_ON_ERROR);
                if ($response === false) {
                    throw new LogicException();
                }
                return new Response(Status::OK, [
                    "content-type" => "application/json"
                ], $response);
            }
        });
    }
    private function onSigint(HttpServer $server): Closure
    {
        return function (string $watcherId) use ($server): Generator {
            Loop::cancel($watcherId);
            foreach ($this->guilds as $guild) {
                yield Command::unregisterAll($guild, $this->clientID, $this->botToken);
            }
            yield $server->stop();
        };
    }

    /**
     * @param Request $request
     * @param string $body
     * @return Promise<bool>
     */
    private function verify(Request $request, string $body): Promise
    {
        return call(function () use ($request, $body): bool {
            $ec = new EdDSA("ed25519");
            $ts = $request->getHeader("X-Signature-Timestamp");
            if (!$ts) {
                return false;
            }
            $signature = $request->getHeader("X-Signature-Ed25519");
            if (!$signature) {
                return false;
            }
            $msg = bin2hex($ts . $body);
            /**
             * @var bool $ok
             */
            $ok = $ec->verify($msg, $signature, $this->publicKey);
            return $ok;
        });
    }



    // Utility functions:
    public static function assertInt(mixed $val): int
    {
        if(!is_int($val)) {
            throw new \TypeError("Expecting int, got " . get_debug_type($val));
        }
        return $val;
    }

    public static function assertString(mixed $val): string
    {
        if(!is_string($val)) {
            throw new \TypeError("Expecting string, got " . get_debug_type($val));
        }
        return $val;
    }

    /**
     * @param mixed $val
     * @return array<string, mixed>
     */
    public static function assertAssocArray(mixed $val): array
    {
        if(!is_array($val)) {
            throw new \TypeError("Expecting array, got " . get_debug_type($val));
        }
        foreach(array_keys($val) as $key) {
            if(!is_string($key)) {
                throw new \TypeError("Expecting associative array, got an integer key");
            }
        }
        /**
         * @var array<string,mixed> $val
         */
        return $val;
    }

    /**
     * @param mixed $val
     * @return array<string, mixed>|null
     */
    public static function assertAssocArrayOrNull(mixed $val): ?array
    {
        if(is_null($val)) {
            return null;
        }
        return self::assertAssocArray($val);
    }

    /**
     * @param mixed $val
     * @return int|string|bool|null
     */
    public static function assertIntStringBoolNull(mixed $val): int|string|bool|null
    {
        if(is_int($val) || is_string($val) || is_bool($val) || is_null($val)) {
            return $val;
        }
        throw new \TypeError("Expecting int|string|bool|null, got " . get_debug_type($val));
    }
}
