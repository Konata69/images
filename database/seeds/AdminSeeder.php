<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->addAdmin();
    }

    /**
     * adds test admin user if not exists
     */
    private function addAdmin() {
        if (DB::table('users')->where([
            'name' => 'admin@test.loc',
            'email' => 'admin@test.loc',
        ])->exists()) {
            return;
        }

        DB::table('users')->insert([
            'name' => 'admin@test.loc',
            'email' => 'admin@test.loc',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
