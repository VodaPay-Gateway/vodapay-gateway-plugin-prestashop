# PrestaShop Plugin [![PHP](https://github.com/VodaPay-Gateway/vodapay-gateway-plugin-prestashop/actions/workflows/release.yml/badge.svg?branch=main)](https://github.com/VodaPay-Gateway/vodapay-gateway-plugin-prestashop/actions/workflows/release.yml)

## PrestaShop
[PrestaShop](https://prestashop.com/) is a popular open-source e-commerce platform used by online merchants to create and manage their online stores. It was first launched in 2007 and has since become a reliable and trusted solution for businesses of all sizes. The platform is written in PHP and uses a MySQL database to store product and customer information. 

---

## Table of contents

- [PrestaShop Plugin ](#prestashop-plugin-)
  - [PrestaShop](#prestashop)
  - [Table of contents](#table-of-contents)
  - [Getting Started](#getting-started)
  - [Prerequisites](#prerequisites)
  - [Installation](#installation)
  - [Usage](#usage)
    - [Install plugin](#install-plugin)
    - [Testing](#testing)
  - [Contributing](#contributing)
  - [Commit Messages](#commit-messages)
  - [Built With](#built-with)
  - [Tags](#tags)


---
## Getting Started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes. See [Commit Messages](#commit-messages) for notes on how to release the project.

---

## Prerequisites
This project requires [PHP 7.4](https://windows.php.net/download#php-7.4) and [git](https://git-scm.com/downloads) if not already installed. This project also requires copy of [PrestaShop](https://github.com/PrestaShop/PrestaShop/releases) platform to install the plugin on.                                            
To make sure you have it available on your machine,
try running the following command.

PHP 7.4 version check
```sh
$ php -v
```
git version check
```sh
$ git --version
```
---

## Installation

**BEFORE YOU INSTALL:** please read the [prerequisites](#prerequisites)

Start with cloning this repo on your local machine to make the necessary changes:

```sh
$ git clone https://github.com/VodaPay-Gateway/vodapay-gateway-plugin-prestashop.git
$ cd PROJECT
```

---
## Usage

### Install plugin

1. See [PrestaShop Plugin Installation](https://docs.vodapaygateway.vodacom.co.za/docs/plugins-sdks/plugins/PrestaShop/Installation)

### Testing

1. Head to the test site front end.
2. Select a product to add to the cart.
3. Head to checkout and follow the steps.
4. When at Payment Method select the **Vodapay Gateway** option and continue. 
5. Press the **continue** button and after completing the payment journey review the response.

## Contributing

Create a branch based on the change being made to the repository:
- Features: feature/new-feature
- Bug Fixes: fix/fix-problem


Steps to follow:
1.  Create your feature branch: `git checkout -b feature/my-new-feature`
2.  Add your changes: `git add .`
3.  Commit your changes: `git commit -m 'Why I did what I did'`
4.  Push to the branch: `git push origin feature/my-new-feature`
5.  Submit a pull request.
---


## Commit Messages
When committing changes, specific messages are required to create a release.

* `feat(scope): Why I did what I did` is for committing a feature added to the plugin.
* `fix(scope): Why I did what I did` is for committing a fix to the plugin.

The scope is an optional addition to specify what the change focused on.

---
## Built With

* PHP

## Tags

 * For the tags available, see the [tags on this repository](https://github.com/VodaPay-Gateway/vodapay-gateway-plugin-prestashop/tags).