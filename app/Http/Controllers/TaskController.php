<?php

namespace App\Http\Controllers;

use App\TaskFilter;
use App\Task;
use App\TaskStatus;
use App\User;
use App\Tag;
use Auth;
use App\Http\Requests\StoreTaskPost;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $tasks = Task::with('creator', 'status', 'assignedTo', 'tags');

        $statuses = TaskStatus::all();
        $users = User::all();
        $tags = Tag::all();

        $tasks = (new TaskFilter($tasks, $request))->apply()->paginate();

        return view('task.index', compact('tasks', 'statuses', 'users', 'tags'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $task = new Task();
        $statuses = TaskStatus::all();
        $users = User::all();
        $tags = Tag::all();

        return view('task.create', compact('task', 'statuses', 'users', 'tags'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreTaskPost $request)
    {
        $request->validated();
        $task = new Task();

        $status = $request->input('status_id');
        $assignedToUser = $request->input('assigned_to_id');
        $task->status()->associate($status);
        $task->creator()->associate(\Auth::user());
        $task->assignedTo()->associate($assignedToUser);
        $task->fill($request->except(['tags']));
        $task->save();

        if ($request->input('tags')) {
            print_r($request->input('tags'));
            $tagsId = [];
            foreach ($request->input('tags') as $tag) {
                $tagData = Tag::firstOrCreate(['name' => $tag]);
                $tagsId[] = $tagData->id;
            }
            $task->tags()->sync($tagsId);
        }

        flash('Create sucessful!')->success();
        return redirect()
            ->route('tasks.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function show(Task $task)
    {
        return view('task.show', compact('task'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function edit(Task $task)
    {
        $statuses = TaskStatus::all();
        $users = User::all();
        $tags = Tag::all();
        $selectedTags = $task->tags->pluck('name', 'id')->all();

        //var_dump($selectedTags);
        return view('task.edit', compact('task', 'statuses', 'users', 'tags', 'selectedTags'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function update(StoreTaskPost $request, Task $task)
    {
        $request->validated();
        $task->fill($request->except(['tags']));
        $task->save();

        if ($request->input('tags')) {
            $tagsId = [];
            foreach ($request->input('tags') as $tag) {
                print_r($tag);
                $tagData = Tag::updateOrCreate(['name' => $tag]);
                $tagsId[] = $tagData->id;
            }
            $task->tags()->sync($tagsId);
        }

        if ($request->input('tags') === null) {
            $task->tags()->sync([]);
        }

        flash('Update sucessful!')->success();

        return redirect()
            ->route('tasks.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function destroy(Task $task)
    {
        if ($task) {
            $task->delete();
        }

        flash('Delete sucessful!')->success();
        return redirect()
            ->route('tasks.index');
    }
}
