<?php

namespace App\Jobs;


use App\Events\ExportEvent;
use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;

class ExportCommentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $userId, $reportId;

    public function __construct($userId, $reportId)
    {
        $this->userId = $userId;
        $this->reportId = $reportId;
    }

    public function handle()
    {
        if (!File::exists(public_path() . "/exports")) {
            File::makeDirectory(public_path() . "/exports");
        }

        $filename =  public_path("exports/comments" . $this->reportId . ".csv");

        //If file update date not under 15 min - creating new file
        if (!file_exists($filename) || time() - filemtime($filename) > 900) {

            $comments = Comment::join('users', 'comments.user_id', '=', 'users.id')
                ->select(
                    'firstname',
                    'lastname',
                    'comments.created_at',
                    'text',
                )
                ->where('report_id', $this->reportId)
                ->get();

            $columns = [
                'firstname',
                'lastname',
                'created_at',
                'content',
            ];

            $file = fopen($filename, 'w');

            fputcsv($file, $columns);
            foreach ($comments as $comment) {
                fputcsv($file, $comment->toArray());
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

        event(new ExportEvent($csv, $this->userId, 'comments.csv'));
    }
}
