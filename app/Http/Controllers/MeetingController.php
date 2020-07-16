<?php

namespace App\Http\Controllers;

use App\Meeting;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('jwt.auth',['except' => ['index','show']]);
    }
    
    public function index()
    {
        $meetings = Meeting::all();
        foreach ($meetings as $meeting) {
            $meeting->view_meeting = [
                'href' => 'api/v1/meeting/'. $meeting->id,
                'method' => 'GET',
            ];
        }

        $response = [
            'msg' => 'List of all Meetings',
            'meetings' => $meetings
        ];

        return response()->json($response,200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request,[
            'title' => 'required',
            'description' => 'required',
            'time' => 'required',
            'user_id' => 'required'
        ]);

        $meeting = Meeting::create([
            'title' => $request->title,
            'description' => $request->description,
            'time' => $request->time,
        ]);

        if($meeting->save())
        {
            $meeting->users()->attach($request->user_id);
            $meeting->view_meeting = [
                'href' => 'api/v1/meeting/'. $meeting->id,
                'method' => 'GET'
            ];

            $message = [
                'msg' => 'Meeting Created',
                'meeting' => $meeting
            ];
            return response()->json($message,201);
        }
        
        $response = [
            'msg' => 'Error create meeting',
        ];

        return response()->json($response,404);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $meeting = Meeting::with('users')->where('id',$id)->firstOrFail();
        $meeting->view_meetings = [
            'href' => 'api/v1/meeting',
            'method' => 'GET'
        ];

        $response = [
            'msg' => 'Meeting Information',
            'meeting' => $meeting
        ];

        return response()->json($response,200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request,[
            'title' => 'required',
            'description' => 'required',
            'time' => 'required',
            'user_id' => 'required'
        ]);

        $meeting = Meeting::with('users')->findOrFail($id);

        if(!$meeting->users()->where('users.id',$request->user_id)->first())
        {
            return response()->json(['msg' => 'User not registered for this meeting, update not successfull'],401);
        }

        $meeting->update([
            'title' => $request->title,
            'description' => $request->description,
            'time' => $request->time,
        ]);

        if($meeting->update())
        {
            $meeting->view_meeting = [
                'href' => 'api/v1/meeting/'. $meeting->id,
                'method' => 'GET'
            ];

            $response = [
                'msg' => 'Meeting updated Successfully',
                'meeting' => $meeting
            ];

            return response()->json($response, 200, );
        }

        return response()->json(['msg' => 'Error update'],400);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Meeting $meeting)
    {
        $users = $meeting->users;
        $meeting->users()->detach();
        
        if(!$meeting->delete())
        {
            foreach ($users as $user) {
                $meeting->users()->attach($user);
            }

            return response()->json(['msg' => 'Delete Failed'],404);
        }

        $response = [
            'msg' => 'Meeting Deleted',
            'create' => [
                'href' => 'api/v1/meeting',
                'method' => 'POST',
                'params' => 'title, description, time'
            ]
        ];

        return response()->json($response, 200);
    }
}
