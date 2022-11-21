<?php

namespace App\Jobs;

use App\Events\ExportEvent;
use App\Models\Listener;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;

class ExportListenersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $userId, $conferenceId;

    public function __construct($userId, $conferenceId)
    {
        $this->userId = $userId;
        $this->conferenceId = $conferenceId;
    }

    public function handle()
    {
        if (!File::exists(public_path() . "/exports")) {
            File::makeDirectory(public_path() . "/exports");
        }

        $filename =  public_path("exports/listeners" . $this->conferenceId . ".csv");

        //If file update date not under 15 min - creating new file
        if (!file_exists($filename) || time() - filemtime($filename) > 900) {

            $listeners = Listener::join('users', 'listeners.user_id', '=', 'users.id')
                ->select(
                    'firstname',
                    'lastname',
                    'birthdate',
                    'country',
                    'phone',
                    'email',
                )
                ->where('conference_id', $this->conferenceId)
                ->get();

            $columns = [
                'firstname',
                'lastname',
                'birthdate',
                'country',
                'phone',
                'email',
            ];

            $file = fopen($filename, 'w');

            fputcsv($file, $columns);
            foreach ($listeners as $listener) {
                fputcsv($file, $listener->toArray());
            }

            fclose($file);
        }

        $file = fopen($filename, 'r');

        $csv = '';
        while (true) {
            $buf = fgets($file);
            if (!$buf) {
                break;
            }
            $csv .= $buf;
        }

        fclose($file);

        event(new ExportEvent($csv, $this->userId, 'members.csv'));
    }
}
