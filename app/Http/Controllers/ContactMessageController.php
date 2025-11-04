<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    public function index(Request $request)
    {
        $query = ContactMessage::orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            if ($request->status === 'unread') {
                $query->unread();
            } elseif ($request->status === 'read') {
                $query->read();
            }
        }

        $messages = $query->paginate(20);
        $unreadCount = ContactMessage::unread()->count();

        return view('contact-messages.index', compact('messages', 'unreadCount'));
    }

    public function show(ContactMessage $contactMessage)
    {
        // Mark as read when viewing
        if (!$contactMessage->is_read) {
            $contactMessage->markAsRead();
        }

        return view('contact-messages.show', compact('contactMessage'));
    }

    public function markAsRead(ContactMessage $contactMessage)
    {
        $contactMessage->markAsRead();

        return back()->with('success', 'Message marqué comme lu');
    }

    public function destroy(ContactMessage $contactMessage)
    {
        $contactMessage->delete();

        return redirect()->route('contact-messages.index')
                        ->with('success', 'Message supprimé avec succès');
    }
}
