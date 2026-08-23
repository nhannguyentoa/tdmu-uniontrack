<?php

namespace App\Http\Controllers;

use App\Http\Requests\Activities\StoreEvidenceRequest;
use App\Models\Activity;
use App\Models\ActivityEvidence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ActivityEvidenceController extends Controller
{
    public function store(StoreEvidenceRequest $request, Activity $activity): RedirectResponse
    {
        foreach ($request->file('files', []) as $file) {
            $path = $file->store("evidences/{$activity->id}", 'public');

            $activity->evidences()->create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => strtolower($file->getClientOriginalExtension()),
                'file_size' => $file->getSize(),
                'uploaded_by' => $request->user()->id,
                'description' => $request->input('description'),
            ]);
        }

        return redirect()->route('activities.show', $activity)->with('success', 'Đã tải lên minh chứng thành công.');
    }

    public function download(Activity $activity, ActivityEvidence $evidence): Response
    {
        $this->authorize('view', $activity);

        abort_unless($evidence->activity_id === $activity->id, 404);
        abort_unless(Storage::disk('public')->exists($evidence->file_path), 404);

        return Storage::disk('public')->download($evidence->file_path, $evidence->file_name);
    }

    public function destroy(Activity $activity, ActivityEvidence $evidence): RedirectResponse
    {
        $this->authorize('manageEvidences', $activity);

        abort_unless($evidence->activity_id === $activity->id, 404);

        Storage::disk('public')->delete($evidence->file_path);
        $evidence->delete();

        return redirect()->route('activities.show', $activity)->with('success', 'Đã xóa minh chứng.');
    }
}
