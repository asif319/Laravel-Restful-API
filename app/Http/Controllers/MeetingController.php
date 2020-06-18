<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Meeting;
use App\User;
use JWTAuth;

class MeetingController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', ['only' => [
            'update', 'store', 'destroy'
        ] ]);
    }

    public function index()
    {
        $meetings = Meeting::all();
        foreach ($meetings as $meeting) {
            $meeting->view_meeting = [
                'href' => 'api/v1/meeting/' . $meeting->id,
                'method' => 'GET'
            ];
        }

        $response = [
            'msg' => 'List of all meetings',
            'meetings' => $meetings
        ];

        return response()->json($response, 200);
    }


    public function store(Request $request)
    {
        $this->validate(request(), [
            'title' => 'required',
            'description' => 'required',
            'time' => 'required|date_format:YmdHie'
        ]);

        if (! $user = JWTAuth::parseToken()->authenticate()){
            return response()->json(['msg' => 'User not found'], 404);
        }

        if ($meeting = Meeting::create([
            'time' => Carbon::createFromFormat('YmdHie', request('time')) ,
            'title' => request('title'),
            'description' => request('description')
        ])) {
            $meeting->users()->attach($user->id);
            $meeting->view_meeting = [
                'href' => 'api/v1/meeting' . $meeting->id,
                'method' => 'GET'
            ];
            $response = [
                'msg' => 'Meeting created',
                'meeting' => $meeting
            ];
            return response()->json($response, 201);
        }

        $response = [
            'msg' => 'An error occurred'
        ];

        return response()->json($response, 404);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $meeting = Meeting::with('users')->where('id', $id)->firstOrFail();

        $meeting->view_meeting = [
            'href' => 'api/v1/meeting',
            'method' => 'GET'
        ];

        $response = [
            'msg' => 'Meeting information',
            'meetings' => $meeting
        ];

        return response()->json($response, 200);
    }

    public function update(Request $request, $id)
    {
        $this->validate(request(), [
            'title' => 'required',
            'description' => 'required',
            'time' => 'required|date_format:YmdHie'
        ]);

        if (! $user = JWTAuth::parseToken()->authenticate()){
            return response()->json(['msg' => 'User not found'], 404);
        }

        $meeting = Meeting::with('users')->where('id', $id)->firstOrFail();
        if (!$meeting->users()->where('users.id', $user->id)->first())
        {
            return response()->json(['msg' => 'User not registered for meeting, update not successful'], 401);
        }

        if ($meeting->update([
            'time' => Carbon::createFromFormat('YmdHie', request('time')) ,
            'title' => request('title'),
            'description' => request('description')
        ]))
        {
            $meeting->view_meeting = [
                'href' => 'api/v1/meeting' . $meeting->id,
                'method' => 'GET'
            ];
            $response = [
                'msg' => 'Meeting updated',
                'meeting' => $meeting
            ];
            return response()->json($response, 200);
        }
        $response = [
            'msg' => 'Error during update'
        ];

        return response()->json($response, 404);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
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
            return response()->json(['msg' => 'User not registered for meeting, update not successful'], 401);
        }
        $users = $meeting->users;
        $users->detach();
        if (!$meeting->delete()){
            foreach ($users as $user){
                $meeting->users()->attach($user);
            }
            return response()->json(['msg ' => 'Deletion Failed'], 404);
        }
        $response = [
            'msg' => 'Meeting deleted',
            'create' => [
                'href' => 'api/v1/meeting',
                'method' => 'POST',
                'params' => 'title, description, time'
            ]
        ];
        return response()->json($response, 200);
    }
}
