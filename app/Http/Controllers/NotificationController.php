<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request, NotificationService $notifications): View
    {
        $groups = $notifications->groups($request->user());
        $type = $request->string('type')->toString();
        $selected = collect($groups)->firstWhere('type', $type);

        return view('notifications.index', [
            'groups' => $groups,
            'total' => NotificationService::total($groups),
            'type' => $selected ? $type : null,
            'shown' => $selected ? [$selected] : $groups,
        ]);
    }
}
