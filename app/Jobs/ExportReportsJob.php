<?php

namespace App\Jobs;

use App\Events\ExportEvent;
use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class ExportReportsJob implements ShouldQueue
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

        $filename =  public_path("exports/reports" . $this->conferenceId . ".csv");

        //If file update date not under 15 min - creating new file
        if (!file_exists($filename) || time() - filemtime($filename) > 900) {

            $reports = Report::leftJoin('comments', 'reports.id', '=', 'comments.report_id')
                ->select(
                    array(
                        'title',
                        'start_time',
                        'end_time',
                        'description',
                        DB::raw('COUNT(comments.id) AS comments_count'),
                    )
                )
                ->where('conf_id', $this->conferenceId)
                ->groupBy('reports.id')
                ->get();

            $columns = [
                'title',
                'start_time',
                'end_time',
                'description',
                'comments_count',
            ];

            $file = fopen($filename, 'w');

            fputcsv($file, $columns);
            foreach ($reports as $report) {
                fputcsv($file, $report->toArray());
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

        event(new ExportEvent($csv, $this->userId, 'reports.csv'));
    }
}
