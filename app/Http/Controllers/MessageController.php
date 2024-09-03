<?php

namespace App\Http\Controllers;

use App\Http\Resources\MessageResource;
use App\Models\MessageAttatchment;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Group;
use App\Models\Message;
use App\Models\Conversation;
use App\Http\Requests\StoreMessageRequest;

class MessageController extends Controller
{
    //To load messages by user
    public function byUser(User $user)
    {
        $messages = Message::where('sender_id', auth()->id())
            ->where('receiver_id', $user->id)
            ->orWhere('sender_id', $user->id)
            ->where('receiver_id', auth()->id())
            ->latest()
            ->paginate(10);

        return inertia('Home', [
            'selectedConversation' => $user->toConversationArray(),
            'messages' => MessageResource::collection($messages),
        ]);
    }

    //To load messages by group
    public function byGroup(Group $group)
    {
        $messages = Message::where('group_id', $group->id)
            ->latest()
            ->paginate(10);

        return inertia('Home', [
            'selectedConversation' => $group->toConversationArray(),
            'messages' => MessageResource::collection($messages),
        ]);
    }

    //To load older messages
    public function loadOlder(Message $message)
    {
        if ($message->group_id) {
            $messages = Message::where('created_at', '<', $message->created_at)
                ->where('group_id', $message->group_id)
                ->latest()
                ->paginate(10);
        } else {
            $messages = Message::where('created_at', '<', $message->created_at)
                ->where(function ($query) use ($message) {
                    $query->where('sender_id', $message->sender_id)
                        ->where('receiver_id', $message->receiver_id)
                        ->orWhere('sender_id', $message->receiver_id)
                        ->where('receiver_id', $message->sender_id);
                })
                ->latest()
                ->paginate(10);
        }
        return MessageResource::collection($messages);
    }

    //To store a newly created message
    public function store(StoreMessageRequest $request)
    {
        $data = $request->validated(); //retrieves validated data
        $data['sender_id'] = auth()->id();
        $receiverId = $data['receiver_id'] ?? null;
        $groupId = $data['group_id'] ?? null;

        $files = $data['attachments'] ?? [];

        $message = Message::create($data); //store in database

        //processing attatchments
        $attatchments = [];
        if ($files) {
            foreach ($files as $file) {
                $directory = 'attatchments/' . Str::random(32);
                Storage::makeDirectory($directory);

                $model = [
                    'message_id' => $message->id,
                    'name' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'path' => $file->store($directory, 'public'),
                ];
                $attatchment = MessageAttatchment::create($model);
                $attatchments[] = $attatchment;

            }
            $message->attatchments = $attatchments;
        }

        if ($receiverId) {
            Conversation::updateConversationWithMessage($receiverId, auth()->id(), $message);
        }
        if ($groupId) {
            Group::updateGroupWithMessage($groupId, $message);
        }

        SocketMessage::dispatch($message);

        return new MessageResource($message);
    }

    //To remove the message
    public function destroy(Message $message)
    {
        if ($message->sender_id !== auth()->id()) {
            return response()->json(
                ['message' => 'Forbidden'],
                403
            );
        }

        $message->delete();

        return response('', 204);
    }
}


