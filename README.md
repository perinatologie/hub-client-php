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
export HUB_URL="http://www.example.com/hub"
export HUB_USERNAME=joe
export HUB_PASSWORD=secret
```

### Run the examples

```sh
php examples/v1/getdossierinfo.php {bsn}
```
## License

MIT. Please refer to the included [LICENSE.md](LICENSE.md) file
