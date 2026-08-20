<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class NotificationBell extends Component
{
    public function render(): View
    {
        $user = request()->user();

        return view('components.notification-bell', [
            'notifications' => $user->notifications()->latest()->limit(8)->get(),
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }
}
