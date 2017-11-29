Hub Client for PHP
======

## Getting started

### Install dependencies:

Get PHP dependencies using Composer
```
composer install
```

## Examples

### Setup environment variables

Before running the examples, make sure the following environment variables have been configured:

```sh
export HUB_URL="http://127.0.0.1:8080"
export HUB_V1_USERNAME=joe
export HUB_V1_PASSWORD=secret
export HUB_V3_USERNAME=joe
export HUB_V3_PASSWORD=secret
```

Hub v1 and v3 use different authentication methods, therefor you can specify them separately.

Note, the v1 credentials are also used to access resources at providers for v1 security hashes.

You can use a `.env` file, by copying the `.env.dist` to `.env` and updating it
with your credentials.

### Run the examples

```sh
php examples/v1/getdossierinfo.php {bsn}
php examples/v3/search.php client_bsn=987654321
```

## Build the v4 API client

The v4 API Client authenticates itself with the Hub using a Json Web Token (JWT)
which it obtains from UserBase.  The username and password you usually use to
authenticate with the Hub are used to authenticate with UserBase and obtain a
JWT.  Thereafter, the v4 client is used in the same way as the v3 client.

```php
use Hub\Client\Exception\ClientCreationException;
use Hub\Client\Exception\ResponseException;
use Hub\Client\Factory\ApiClientFactory;

require_once '/path/to/vendor/autoload.php';

$userbaseUrl = 'http://userbase.example.com/auth';
$hubUrl = 'http://hub.example.com';
$username = 'my-username';
$password = 'my-password';

$clientFactory = new ApiClientFactory($hubUrl);
$clientFactory->setUserbaseJwtAuthenticatorUrl($userbaseUrl);
try {
    $client = $clientFactory->createV4Client($username, $password);
} catch (ClientCreationException $e) {
    // failed to create the client: probably failed to obtain a JWT
    echo $e->getMessage() . PHP_EOL;
    exit();
}
try {
    $resources = $client->findResources();
} catch (ResponseException $e) {
    echo $e->getMessage() . PHP_EOL;
    exit();
}
var_dump($resources);
```

## cacert.pem

For Guzzle versions > 4, curl is used to verify remote SSL connections.
In order for this to work, you'll need to pass a `verify` key to guzzle, pointing to
a .pem file containing all trusted CA's.

These are published by curl here:

* https://curl.haxx.se/docs/caextract.html

If needed, you can download new ca certificates from there, and put them in the root of this project.

## License

MIT. Please refer to the included [LICENSE.md](LICENSE.md) file
