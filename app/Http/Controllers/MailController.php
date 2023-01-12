<?php

namespace App\Http\Controllers;

use App\Jobs\MailJob;
use App\Mail\MailConferenceDeleted;
use Illuminate\Mail\Mailable;
use App\Mail\MailNewAnnouncer;
use App\Mail\MailNewComment;
use App\Mail\MailNewListener;
use App\Mail\MailReportDeleted;
use App\Mail\MailReportTimeChange;
use App\Models\Conferences;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class MailController extends Controller
{
    /**
     * Send email about new listener to all announcers on the conference
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user - user who joined as listener
     * @param int $conferenceId - joined conference id
     * 
     * @return void
     */
    public static function newListener(\Illuminate\Contracts\Auth\Authenticatable $user, int $conferenceId)
    {
        $announcers = Conferences::join('reports', 'conferences.id', '=', 'reports.conference_id')
            ->join('users', 'users.id', '=', 'reports.user_id')
            ->where('users.role', 'announcer')
            ->where('conferences.id', $conferenceId)
            ->select('users.email', 'users.role', 'conferences.title')
            ->get();

        if ($announcers->count() == 0) {
            return;
        }

        $mailData = [
            'userName' => $user->firstname,
            'conferenceTitle' => $announcers->first()->title,
            'conferenceLink' => env('FRONT_URL', 'http://127.0.0.1:8080') . '/conference/' . $conferenceId,
        ];
        $mail = new MailNewListener($mailData);
        foreach ($announcers as $announcer) {
            if ($announcer->role != 'admin') {
                MailJob::dispatch($announcer->email, $mail)->onQueue('mails');
            }
        }

        self::sendToAdmins($mail);
    }

    /**
     * Send email about new announcer to all listeners on the conference
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user - user who joined as announcer
     * @param int $reportId - id of new report
     * @param int $conferenceId - joined conference id
     *
     * @return void
     */
    public static function newAnnouncer(\Illuminate\Contracts\Auth\Authenticatable $user, int $reportId, int $conferenceId)
    {
        $listeners = Report::join('listeners', 'reports.conference_id', '=', 'listeners.conference_id')
            ->join('users', 'users.id', '=', 'listeners.user_id')
            ->where('reports.id', $reportId)
            ->select('users.email', 'users.role', 'reports.title', 'reports.start_time', 'reports.end_time')
            ->get();

        if ($listeners->count() == 0) {
            return;
        }

        $conference = Conferences::findOrFail($conferenceId);
        $mailData = [
            'userName' => $user->firstname,
            'conferenceTitle' => $conference->title,
            'conferenceLink' => env('FRONT_URL', 'http://127.0.0.1:8080') . '/conference/' . $conferenceId,
            'reportLink' => env('FRONT_URL', 'http://127.0.0.1:8080') . '/report/' . $reportId,
            'reportTitle' => $listeners->first()->title,
            'reportDuration' => (strtotime($listeners->first()->end_time) - strtotime($listeners->first()->start_time)) / 60,
        ];
        $mail = new MailNewAnnouncer($mailData);
        foreach ($listeners as $listener) {
            if ($listener->role != 'admin') {
                MailJob::dispatch($listener->email, $mail)->onQueue('mails');
            }
        }

        self::sendToAdmins($mail);
    }

    /**
     * Send email about report time change to all listeners on the conference
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user - report onwer 
     * @param int $reportId - id of changed report
     * @param int $conferenceId - report conference id
     *
     * @return void
     */
    public static function reportTimeChange(\Illuminate\Contracts\Auth\Authenticatable $user, int $reportId, int $conferenceId)
    {
        $listeners = Report::join('listeners', 'reports.conference_id', '=', 'listeners.conference_id')
            ->join('users', 'users.id', '=', 'listeners.user_id')
            ->where('reports.id', $reportId)
            ->select('users.email', 'users.role', 'reports.title', 'reports.start_time', 'reports.end_time')
            ->get();

        if ($listeners->count() == 0) {
            return;
        }

        $conference = Conferences::findOrFail($conferenceId);
        $mailData = [
            'userName' => $user->firstname,
            'conferenceTitle' => $conference->title,
            'conferenceLink' => env('FRONT_URL', 'http://127.0.0.1:8080') . '/conference/' . $conferenceId,
            'reportLink' => env('FRONT_URL', 'http://127.0.0.1:8080') . '/report/' . $reportId,
            'reportTitle' => $listeners->first()->title,
            'reportTime' => $listeners->first()->start_time . '-' . $listeners->first()->end_time,
        ];
        $mail = new MailReportTimeChange($mailData);
        foreach ($listeners as $listener) {
            if ($listener->role != 'admin') {
                MailJob::dispatch($listener->email, $mail)->onQueue('mails');
            }
        }

        self::sendToAdmins($mail);
    }

    /**
     * Send email about deleted report to report creator
     *
     * @param int $userId - report onwer`s id
     * @param int $conferenceId - id of report conference
     * 
     * @return void
     */
    public static function reportDeleted(int $userId, int $conferenceId)
    {
        $user = User::findOrFail($userId);

        $conference = Conferences::findOrFail($conferenceId);
        $mailData = [
            'conferenceTitle' => $conference->title,
            'conferenceLink' => env('FRONT_URL', 'http://127.0.0.1:8080') . '/conference/' . $conferenceId,
        ];

        $mail = new MailReportDeleted($mailData);
        if ($user->role != 'admin') {
            MailJob::dispatch($user->email, $mail)->onQueue('mails');
        }

        self::sendToAdmins($mail);
    }

    /**
     * Send email about new comment to report creator
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user - user who created comment
     * @param int $reportId - id of report with new comment
     * 
     * @return void
     */
    public static function newComment(\Illuminate\Contracts\Auth\Authenticatable $user, int $reportId)
    {
        $announcer = Report::join('users', 'users.id', '=', 'reports.user_id')
            ->where('reports.id', $reportId)
            ->select('users.email', 'users.role', 'reports.title AS reportTitle', 'reports.conference_id')
            ->first();

        if ($announcer->count() ===  null) {
            return;
        }

        $conference = Conferences::findOrFail($announcer->conference_id);
        $mailData = [
            'userName' => $user->firstname,
            'conferenceTitle' => $conference->title,
            'conferenceLink' => env('FRONT_URL', 'http://127.0.0.1:8080') . '/conference/' . $announcer->conference_id,
            'reportLink' => env('FRONT_URL', 'http://127.0.0.1:8080') . '/report/' . $reportId,
            'reportTitle' => $announcer->reportTitle,
        ];
        $mail = new MailNewComment($mailData);
        if ($announcer->role != 'admin') {
            MailJob::dispatch($announcer->email, $mail)->onQueue('mails');
        }

        self::sendToAdmins($mail);
    }

    /**
     * Send email about deleted conference to everyone involved
     *
     * @param int $conferenceId - id of conference to be deleted
     * 
     * @return void
     */
    public static function conferenceDeleted(int $conferenceId)
    {
        $recievers = Conferences::join('reports', 'conferences.id', '=', 'reports.conference_id')
            ->join('users', 'users.id', '=', 'reports.user_id')
            ->where('conferences.id', $conferenceId)
            ->where('users.role', '<>', 'admin')
            ->select('users.email')
            ->get();

        $recievers = $recievers->merge(
            Conferences::join('listeners', 'conferences.id', '=', 'listeners.conference_id')
                ->join('users', 'users.id', '=', 'listeners.user_id')
                ->where('conferences.id', $conferenceId)
                ->select('users.email')
                ->get()
        );
        $recievers = $recievers->merge(
            Conferences::join('users', 'users.id', '=', 'conferences.user_id')
                ->where('conferences.id', $conferenceId)
                ->where('users.role', '<>', 'admin')
                ->select('users.email')
                ->get()
        );
        $recievers = $recievers->unique('email');

        $conference = Conferences::findOrFail($conferenceId);

        $mailData = [
            'conferenceTitle' => $conference->title
        ];
        $mail = new MailConferenceDeleted($mailData);

        foreach ($recievers as $reciever) {
            MailJob::dispatch($reciever->email, $mail)->onQueue('mails');
        }

        self::sendToAdmins($mail);
    }

    /**
     * Send email to all admins
     *
     * @param Mailable $mail - mail to send
     *
     * @return void
     */
    protected static function sendToAdmins(Mailable $mail)
    {
        $admins = User::where('role', 'admin')->select('email')->get();
        foreach ($admins as $admin) {
            MailJob::dispatch($admin->email, $mail)->onQueue('mails');
        }
    }
}
