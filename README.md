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

### Run the examples

```sh
php examples/v1/getdossierinfo.php {bsn}
php examples/v3/search.php client_bsn=987654321
```

## License

MIT. Please refer to the included [LICENSE.md](LICENSE.md) file
