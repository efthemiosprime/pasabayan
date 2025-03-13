<?php

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('pasabay:reset-db', function () {
    $this->comment('Resetting database and seeding with fresh data...');
    
    $this->call('migrate:fresh');
    $this->info('Database reset complete.');
    
    $this->call('db:seed');
    $this->info('Database seeding complete.');
    
    $this->info('Database has been reset and seeded with fresh data!');
    $this->info('You can now login with these credentials:');
    $this->info('- Admin: admin@pasabay.com / password');
    $this->info('- Traveler: traveler@pasabay.com / password');
    $this->info('- Sender: sender@pasabay.com / password');
    $this->info('Additional travelers: juan@pasabay.com, maria@pasabay.com, pedro@pasabay.com (all with password "password")');
})->purpose('Reset the database and seed with fresh data');

Artisan::command('pasabay:seed-trips', function () {
    $this->comment('Seeding trips...');
    
    $this->call('db:seed', [
        '--class' => 'Database\\Seeders\\TripSeeder'
    ]);
    
    $this->info('Trips have been seeded successfully!');
    $this->info('You can test the trips at: http://localhost:8000/api/public/trips');
})->purpose('Seed the database with trips for testing');
