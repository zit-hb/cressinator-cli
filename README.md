cressinator-cli
===============

A CLI tool to send Yoctopuce sensor data and camera images to the Cressinator server.

## Requirements

  * [Composer](https://getcomposer.org/)
  * [Box](https://github.com/humbug/box)
  * PHP >= 7.2

## Installation

    composer install
    php bin/console
    box build

## Examples

    ./cressinator-cli.phar cressinator:store \
      --group 1 \
      --yoctopuce http://foo:bar@192.168.0.23:4444 \
      --cressinator https://cressinator.example.org \
      --token d69c8d58118bbe78dec56bc0b638ec344042ec011cdc75d0e9d11de7f765fb1f \
      --file examples/Devices.yaml
