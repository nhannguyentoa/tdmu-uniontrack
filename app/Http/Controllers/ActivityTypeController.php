<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityTypes\StoreActivityTypeRequest;
use App\Http\Requests\ActivityTypes\UpdateActivityTypeRequest;
use App\Models\ActivityType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ActivityTypeController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ActivityType::class);

        $query = ActivityType::withCount('activities')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->orderBy('name');

        $activityTypes = $query->paginate(10)->withQueryString();

        return view('activity-types.index', compact('activityTypes'));
    }

    public function store(StoreActivityTypeRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']).'-'.Str::random(4);
        $data['is_active'] = $request->boolean('is_active', true);

        ActivityType::create($data);

        return back()->with('success', 'Đã thêm loại hoạt động thành công.');
    }

    public function update(UpdateActivityTypeRequest $request, ActivityType $activityType): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $activityType->update($data);

        return back()->with('success', 'Đã cập nhật loại hoạt động thành công.');
    }

    public function destroy(ActivityType $activityType): RedirectResponse
    {
        $this->authorize('delete', $activityType);

        if ($activityType->activities()->exists()) {
            return back()->with('error', 'Không thể xóa vì vẫn còn hoạt động thuộc loại này. Bạn có thể vô hiệu hóa thay vì xóa.');
        }

        $activityType->delete();

        return back()->with('success', 'Đã xóa loại hoạt động thành công.');
    }
}
