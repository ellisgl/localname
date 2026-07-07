<?php

declare(strict_types=1);

namespace Geeklab\Localname;

use Geeklab\Localname\Resolvers\Llmnr;
use Geeklab\Localname\Resolvers\Mdns;
use Geeklab\Localname\Resolvers\Netbios;
use Geeklab\Localname\Resolvers\ReverseDns;

final class LocalName
{
    /** @var ResolverInterface[] */
    private array $resolvers;

    private ?MacVendor $macVendor;

    public function __construct(?MacVendor $macVendor = null, ResolverInterface ...$resolvers)
    {
        $this->resolvers = $resolvers;
        $this->macVendor = $macVendor;
    }

    public static function create(?string $ouiDbPath = null): self
    {
        $dbPath    = $ouiDbPath ?? MacVendor::defaultDbPath();
        $macVendor = is_file($dbPath) ? new MacVendor($dbPath) : null;

        return new self(
            $macVendor,
            new Mdns(),
            new Netbios(),
            new Llmnr(),
            new ReverseDns(),
        );
    }

    public function lookup(string $ip): Result
    {
        $mac    = Arp::getMac($ip);
        $vendor = ($mac !== null && $this->macVendor !== null) ? $this->macVendor->lookup($mac) : null;

        foreach ($this->resolvers as $resolver) {
            $name = $resolver->resolve($ip);
            if ($name !== null) {
                return new Result($ip, $name, $resolver->getProtocol(), $mac, $vendor);
            }
        }

        return new Result($ip, null, null, $mac, $vendor);
    }

    /**
     * @return Result[]
     */
    public function lookupAll(string $ip): array
    {
        $mac    = Arp::getMac($ip);
        $vendor = ($mac !== null && $this->macVendor !== null) ? $this->macVendor->lookup($mac) : null;
        $results = [];

        foreach ($this->resolvers as $resolver) {
            $name = $resolver->resolve($ip);
            if ($name !== null) {
                $results[] = new Result($ip, $name, $resolver->getProtocol(), $mac, $vendor);
            }
        }

        return $results;
    }
}