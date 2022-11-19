<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use App\Models\Topic;
use Illuminate\Database\Seeder;

class TopicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Topic::create( ['name' => ['fr' => 'Technologie', 'en' => 'technology']]);
        Topic::create(['name' => ['fr' => 'Films', 'en' => 'Movies']],);
        Topic::create(['name' => ['fr' => 'Sante', 'en' => 'Health']],);
        Topic::create(['name' => ['fr' => 'Nourriture', 'en' => 'Food']],);
        Topic::create(['name' => ['fr' => 'Musique', 'en' => 'Music']]);
        Topic::create(['name' => ['fr' => 'Livres', 'en' => 'Books']]);
        Topic::create(['name' => ['fr' => 'Others', 'en' => 'Autres']]);
        PaymentMethod::create([]);
    }
}
