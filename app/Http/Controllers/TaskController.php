<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    //Display main Blade view 
    public function index()
    {
        return view('tasks.index');
    }
    // Fetch tasks
    public function fetchTasks(Request $request)
    {
        $query = Task::query();

        if ($request->has('search') && $request->search !== '') {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $tasks = $query->orderByDesc('created_at')->paginate(5);

        return response()->json([
            'tasks' => $tasks
        ]);
    }
    // Store a new task
    public function store(Request $request)
    {
        $validated = $request->validate(['title' => 'required|string|max:255', 'description' => 'nullable|string',]);
        $task = Task::create(['title' => $validated['title'], 'description' => $validated['description'] ?? '',]);
        return response()->json(['message' => 'Task created successfully', 'task' => $task]);
    }
    // Update existing task 
    public function update(Request $request, $id)
    {
        $validated = $request->validate(['title' => 'required|string|max:255', 'description' => 'nullable|string',]);
        $task = Task::findOrFail($id);
        $task->update($validated);
        return response()->json(['message' => 'Task updated successfully', 'task' => $task]);
    }
    // Delete task
    public function destroy($id)
    {
        $task = Task::findOrFail($id);
        $task->delete();
        return response()->json(['message' => 'Task deleted successfully']);
    }
    // Toggle completed status 
    public function toggleComplete($id)
    {
        $task = Task::findOrFail($id);
        $task->completed = !$task->completed;
        $task->save();
        return response()->json(['message' => 'Task status updated', 'completed' => $task->completed]);
    }
}
