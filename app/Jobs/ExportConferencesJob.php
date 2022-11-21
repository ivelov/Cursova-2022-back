<?php

namespace App\Jobs;

use App\Events\ExportEvent;
use App\Models\Conferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ExportConferencesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function handle()
    {
        if (!File::exists(public_path() . "/exports")) {
            File::makeDirectory(public_path() . "/exports");
        }

        $filename =  public_path("exports/conferences.csv");

        //If file update date not under 15 min - creating new file
        if (!file_exists($filename) || time() - filemtime($filename) > 900) {

            $conferences = Conferences::leftJoin('reports', 'conferences.id', '=', 'reports.conf_id')
                ->leftJoin('listeners', 'conferences.id', '=', 'listeners.conference_id')
                ->select(
                    array(
                        'conferences.title',
                        'conferences.date',
                        'conferences.time',
                        'latitude',
                        'longitude',
                        'country',
                        DB::raw('COUNT(reports.id) AS reports_count'),
                        DB::raw('COUNT(listeners.id) AS listeners_count'),
                    )
                )
                ->groupBy('conferences.id')
                ->get();


            $columns = [
                'title',
                'date',
                'time',
                'latitude',
                'longitude',
                'country',
                'reports_count',
                'listeners_count',
            ];

            $file = fopen($filename, 'w');

            fputcsv($file, $columns);

            foreach ($conferences as $conference) {
                fputcsv($file, $conference->toArray());
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

        event(new ExportEvent($csv, $this->userId, 'conferences.csv'));
    }
}
