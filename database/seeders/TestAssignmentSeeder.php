<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Test;
use App\Models\TestAssignment;
use Illuminate\Support\Facades\Hash;

class TestAssignmentSeeder extends Seeder
{
    public function run()
    {
        // Create users directly
        $alice = User::create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => Hash::make('pass1234'),
            'role' => 'student',
            'is_admin' => 0,
            'is_active' => 1,
        ]);

        $bob = User::create([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => Hash::make('pass1234'),
            'role' => 'student',
            'is_admin' => 0,
            'is_active' => 1,
        ]);

        // Create tests directly
        $opiTest = Test::create(['name' => 'OPI Test']);
        $listeningTest = Test::create(['name' => 'Listening Test']);

        // Create assignments directly
        TestAssignment::create([
            'user_id' => $alice->id,
            'test_id' => $opiTest->id,
            'status'  => 'pending',
        ]);

        TestAssignment::create([
            'user_id' => $bob->id,
            'test_id' => $listeningTest->id,
            'status'  => 'completed',
        ]);
    }
}

