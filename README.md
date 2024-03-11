## Installation

You must have PHP8.1 installed on your machine

Pull the repository down

Run composer install
Run npm install
run cp .env.example .env

Add your database details to the .env file
Add the BreathePay merchant ID and Secret (check Breathepay Documentation) to the env file:
BREATHEPAY_ID=
BREATHEPAY_3DS_ID=
BREATHEPAY_SECRET=

Run php artisan migrate

Visit the homepage of the application and make a payment using the test cards (check BreathePay Documentation)
