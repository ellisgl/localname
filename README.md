# LocalName

Resolve hostnames on local networks via mDNS, LLMNR, NetBIOS, and reverse DNS. Includes ARP-based MAC address lookup with IEEE OUI vendor identification.

## Requirements

- PHP >= 8.1
- `ext-sockets`

## Installation

```bash
composer require geeklab/localname
```

## Quick Start

```php
use Geeklab\Localname\LocalName;

$resolver = LocalName::create();
$result   = $resolver->lookup('192.168.1.100');

echo $result->ip;       // 192.168.1.100
echo $result->name;     // my-laptop.local
echo $result->protocol; // mdns
echo $result->mac;      // AA:BB:CC:DD:EE:FF
echo $result->vendor;   // Apple, Inc.
```

## Resolvers

`LocalName::create()` tries each resolver in order and returns the first match:

| Resolver | Protocol | Method |
|----------|----------|--------|
| mDNS | Multicast DNS (224.0.0.251:5353) | PTR query |
| NetBIOS | NBSTAT (port 137) | Wildcard name query |
| LLMNR | Link-Local Multicast (224.0.0.252:5355) | PTR query |
| Reverse DNS | System DNS | `gethostbyaddr()` |

### Using Individual Resolvers

```php
use Geeklab\Localname\Resolvers\Mdns;
use Geeklab\Localname\Resolvers\Netbios;
use Geeklab\Localname\Resolvers\Llmnr;
use Geeklab\Localname\Resolvers\ReverseDns;

$mdns = new Mdns(timeout: 3.0);
$name = $mdns->resolve('192.168.1.100'); // returns string or null
```

### Getting All Results

```php
$results = $resolver->lookupAll('192.168.1.100');

foreach ($results as $r) {
    printf("[%s] %s\n", $r->protocol, $r->name);
}
```

## MAC Vendor Lookup

MAC vendor identification requires the IEEE OUI database. Download it with the included script:

```bash
php bin/update-oui.php
```

This saves the database to `data/oui.csv`. Without it, `lookup()` still works -- `vendor` will just be `null`.

You can also pass a custom path:

```php
$resolver = LocalName::create('/path/to/oui.csv');
```

## Running Tests

```bash
composer install
vendor/bin/phpunit
```

## License

BSD-4-Clause
