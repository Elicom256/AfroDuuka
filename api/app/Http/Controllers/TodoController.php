<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTodoRequest;
use App\Http\Requests\UpdateTodoRequest;
use App\Models\Todo;

class TodoController extends Controller
{
    public function index()
    {
        $todos = Todo::orderBy('created_at', 'desc')->get();
        return response()->json(['message' => 'Fetched todos', 'data' => $todos]);
    }

    public function store(StoreTodoRequest $request)
    {
        $todo = Todo::create($request->validated());
        return response()->json(['message' => 'Todo created', 'data' => $todo], 201);
    }

    public function show(Todo $todo)
    {
        return response()->json(['message' => 'Fetched todo', 'data' => $todo]);
    }

    public function update(UpdateTodoRequest $request, Todo $todo)
    {
        $todo->update($request->validated());
        return response()->json(['message' => 'Todo updated', 'data' => $todo]);
    }

    public function destroy(Todo $todo)
    {
        $todo->delete();
        return response()->json(['message' => 'Todo deleted']);
    }
}
