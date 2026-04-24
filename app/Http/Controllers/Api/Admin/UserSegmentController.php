<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\UserSegmentService;
use Illuminate\Http\Request;

class UserSegmentController extends Controller
{
    protected $segmentService;

    public function __construct(UserSegmentService $segmentService)
    {
        $this->segmentService = $segmentService;
    }

    public function index()
    {
        return response()->json([
            'success' => true,
            'data'    => $this->segmentService->getAllSegments()
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string',
            'rule_set' => 'required|array',
        ]);

        $segment = $this->segmentService->createSegment($data);

        return response()->json(['success' => true, 'data' => $segment], 201);
    }

    public function show($id)
    {
        $segment = \App\Models\UserSegment::findOrFail($id);
        $userCount = $segment->getMatchingUsers()->count();

        return response()->json([
            'success'    => true,
            'data'       => $segment,
            'user_count' => $userCount
        ]);
    }

    public function update($id, Request $request)
    {
        $data = $request->validate([
            'name'     => 'string',
            'rule_set' => 'array',
        ]);

        $segment = $this->segmentService->updateSegment($id, $data);

        return response()->json(['success' => true, 'data' => $segment]);
    }

    public function destroy($id)
    {
        $this->segmentService->deleteSegment($id);
        return response()->json(['success' => true, 'message' => 'Segment deleted']);
    }
}
