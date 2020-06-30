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
- Setup country code in International -> Localization -> Configuration
- Setup zip/postal code in Contact -> Store menu.

# Meta 

Vasil M – Skype: vasil.ysbm

Mathias Bjerke – [@mathbje](https://twitter.com/mathbje) – mathias@verida.no
 