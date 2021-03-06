<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Meeting;
use JWTAuth;

class RegistrationController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
    }

    public function store(Request $request)
    {
        $this->validate(request(),[
            'meeting_id' => 'required',
            'user_id' => 'required'
        ]);

        $meeting = Meeting::findOrFail(request('meeting_id'));
        $user = User::findOrFail(request('user_id'));

        $message = [
            'msg' => 'User is already registered for meeting',
            'user' => $user,
            'meeting' => $meeting,
            'unregister' =>[
                'href' => 'api/v1/meeting/registration/' . $meeting->id,
                'method' => 'DELETE'
            ]
        ];
        if ($meeting->users()->where('users.id', request('user_id'))->first())
        {
            return response()->json($message, 404);
        }
        $user->meetings()->attach($meeting);

        $response = [
            'msg' => 'User registered for the meeting',
            'meetings' => $meeting,
            'user' => $user,
            'unregister' => [
                'href' => 'api/v1/meeting/registration/' . $meeting->id,
                'method' => 'DELETE'
            ]
        ];

        return response()->json($response, 201);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $meeting = Meeting::findOrFail($id);
        if (! $user = JWTAuth::parseToken()->authenticate()){
            return response()->json(['msg' => 'User not found'], 404);
        }
        if (!$meeting->users()->where('users.id', $user->id)->first())
        {
            return response()->json(['msg' => 'User not registered for meeting, delete operation not successful'], 401);
        }
        $meeting->users()->detach($user->id);

        $response = [
            'msg' => 'User unregistered for the meeting',
            'meetings' => $meeting,
            'user' => $user,
            'register' => [
                'href' => 'api/v1/meeting/registration',
                'method' => 'POST',
                'params' => 'user_id, meeting_id'
            ]
        ];

        return response()->json($response, 200);
    }
}
