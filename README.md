# FYP Project

## Installation

1. Clone this repository to your local machine
2. Open a terminal and go to the project folder
3. Run `composer install`
4. Copy `.env.example` to `.env`
5. Open `.env` and set your database settings
6. Run `php artisan key:generate`
7. Run `php artisan migrate`
8. Run `php artisan db:seed`
9. Run `cd nuxt`
10. Run `npm install`

## Development

1. Open a terminal in the project folder and run `php artisan serve` (backend at http://localhost:8000)
2. Open another terminal, run `cd nuxt`
3. Run `npm run dev` (frontend at http://localhost:3000)
