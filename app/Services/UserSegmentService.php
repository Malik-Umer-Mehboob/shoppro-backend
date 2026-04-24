<?php

namespace App\Services;

use App\Models\UserSegment;

class UserSegmentService
{
    public function createSegment(array $data)
    {
        return UserSegment::create($data);
    }

    public function updateSegment($segmentId, array $data)
    {
        $segment = UserSegment::findOrFail($segmentId);
        $segment->update($data);
        return $segment;
    }

    public function deleteSegment($segmentId)
    {
        $segment = UserSegment::findOrFail($segmentId);
        return $segment->delete();
    }

    public function getUsersInSegment($segmentId)
    {
        $segment = UserSegment::findOrFail($segmentId);
        return $segment->getMatchingUsers();
    }

    public function getAllSegments()
    {
        return UserSegment::all();
    }
}
