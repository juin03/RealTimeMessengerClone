<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Group; // Add this line to import the 'Group' class
use App\Models\Message; // Add this line to import the 'Message' class
use Carbon\Carbon; // Add this line to import the 'Carbon' class
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Conversation; // Add this line to import the 'Conversation' class

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);
        User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create additional 10 random users
        User::factory(10)->create();

        // Create 5 groups with 2-5 random users
        for($i = 0; $i < 5; $i++) {
            $group = Group::factory()->create([
                'owner_id' => 1,
            ]);

        // Attach 2-5 random users to the group
            $users = User::inRandomOrder()->limit(rand(2, 5))->pluck('id');
            $group->users()->attach(array_unique([1, ...$users]));
        }

        Message::factory(1000)->create();
        $messages = Message::whereNull('group_id')->orderBy('created_at')->get();

        $conversations = $messages->groupBy(function($message){
            return collect ([$message->sender_id, $message->receiver_id])->sort()->implode('_');
        })->map(function($groupMessages){
            return [
                'user_id1' => $groupMessages->first()->sender_id,
                'user_id2' => $groupMessages->first()->receiver_id,
                'last_message_id' => $groupMessages->last()->id,
                'created_at' => new Carbon(),
                'updated_at' => new Carbon(),
            ];
        })->values();

        Conversation::insertOrIgnore($conversations->toArray());
    }
}
