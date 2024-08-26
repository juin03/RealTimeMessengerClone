<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $senderID = $this->faker->randomElement([0,1]);
        if ($senderID === 0){
            $senderID = $this->faker->randomElement(\App\Models\User::where('id', '!=', 1)->pluck('id')->toArray());
            $receiverID = 1;
        } else {
            $receiverID = $this->faker->randomElement(\App\Models\User::pluck('id')->toArray());
        }

        $groupID = null;
        if ($this->faker->boolean(50)) {
            $groupID = $this->faker->randomElement(\App\Models\Group::pluck('id')->toArray());
            //Select group by group id
            $group = \App\Models\Group::find($groupID);
            $senderID = $this->faker->randomElement($group->users->pluck('id')->toArray());
            $receiverID = null; 
        }

        return [
            'sender_id' => $senderID,
            'receiver_id' => $receiverID,
            'group_id' => $groupID,
            'message' => $this->faker->realText(200),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'), // 1 year ago to now
        ];
    }
}
