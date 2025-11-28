<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Models\Notification;
use App\Events\NewsletterNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NewsletterController extends Controller
{
    /**
     * Display a listing of the subscribers.
     * 
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // This endpoint should be protected by admin middleware in routes
        $subscribers = NewsletterSubscriber::orderBy('created_at', 'desc')->get();
        
        return response()->json($subscribers);
    }

    /**
     * Store a newly created subscriber in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:newsletter_subscribers,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $subscriber = NewsletterSubscriber::create([
                'email' => $request->email,
                'subscribed_at' => now(),
            ]);

            // 🔔 Admin notification
            $notification = Notification::create([
                'created_by' => $request->user()?->id,            // null if public form
                'type'       => 'newsletter.subscribed',
                'title'      => 'New newsletter subscription',
                'message'    => "Email {$subscriber->email} subscribed to the newsletter.",
                'data'       => [
                    'subscriber_id' => $subscriber->id,
                    'email'         => $subscriber->email,
                    'ip'            => $request->ip(),
                    'user_agent'    => $request->userAgent(),
                ],
            ]);

            event(new NewsletterNotification($notification));

            return response()->json([
                'message' => 'Successfully subscribed to the newsletter',
                'subscriber' => $subscriber
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to subscribe',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unsubscribe from the newsletter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function unsubscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:newsletter_subscribers,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $subscriber = NewsletterSubscriber::where('email', $request->email)->first();

            if (!$subscriber) {
                return response()->json([
                    'message' => 'Email not found in our subscribers list'
                ], 404);
            }

            $subscriber->update(['is_active' => false]);

            // 🔔 Admin notification
            $notification = Notification::create([
                'created_by' => $request->user()?->id,           // null if public form
                'type'       => 'newsletter.unsubscribed',
                'title'      => 'Newsletter unsubscribe',
                'message'    => "Email {$subscriber->email} unsubscribed from the newsletter.",
                'data'       => [
                    'subscriber_id' => $subscriber->id,
                    'email'         => $subscriber->email,
                    'ip'            => $request->ip(),
                    'user_agent'    => $request->userAgent(),
                ],
            ]);

            event(new NewsletterNotification($notification));

            return response()->json([
                'message' => 'Successfully unsubscribed from the newsletter'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to unsubscribe',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified subscriber from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request)
    {
        try {
            $subscriber = NewsletterSubscriber::findOrFail($id);
            $email = $subscriber->email;
            $subscriber->delete();

            // 🔔 Admin notification (performed by an admin)
            $notification = Notification::create([
                'created_by' => $request->user()?->id,           // admin id
                'type'       => 'newsletter.deleted',
                'title'      => 'Subscriber removed',
                'message'    => "Subscriber {$email} was deleted by admin.",
                'data'       => [
                    'subscriber_id' => (int) $id,
                    'email'         => $email,
                ],
            ]);

            event(new NewsletterNotification($notification));

            return response()->json([
                'message' => 'Subscriber deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete subscriber',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
