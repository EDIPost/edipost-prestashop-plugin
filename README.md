# Edipost integration for PrestaShop

Edipost integration for PrestaShop

The code should go into the `modules` folder

## Run test environment

1. cd docker-prestashop
2. rename .env.example to .env (edit data if it needed)
2. cd ..
3. run docker-compose up

Four Prestashop instances will be runned (with default settings):
   - 1.6.1 http://localhost:8080/adminPS
   - 1.7.1 http://localhost:8081/adminPS
   - 1.7.2 http://localhost:8082/adminPS
   - 1.7.3 http://localhost:8083/adminPS
 
 Default credentials:
 
    - Email: demo@prestashop.com
    
    - Password: prestashop_demo

## Setup store
1. Setup country code in International -> Localization -> Configuration -> Default Country
2. Setup zip/postal code in Shop Parameters -> Contact -> Store menu.
3. Modules -> Search for "Edipost". Click Install.
4. Modules -> Installed modules. Click Configure on module Edipost.

## Publish store

https://validator.prestashop.com

Compress the folder edipost so the content of the zip folder will look something like this:
```
edipost
├── config.xml
├── controllers
│   ├── admin
│   │   ├── AdminEdipostController.php
│   │   └── index.php
│   └── index.php
├── edipost.php
├── helper.php
├── index.php
...
```
The zip file should be named with version numbering as the following: `edipost-prestashop-1.0.zip`

# Meta 

Vasil M – Skype: vasil.ysbm

Mathias Bjerke – [@mathbje](https://twitter.com/mathbje) – mathias@verida.no
 