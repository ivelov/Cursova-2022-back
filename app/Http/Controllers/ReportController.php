<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Conferences;
use App\Models\Favorites;
use App\Models\Report;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    /**
     * Return reports info at selected page and filters
     *
     * @return Object
     */
    public function index($page, Request $request)
    {
        $user = Auth::user();

        if ($request->favorites) {
            if (!$user) {
                abort(403);
            }

            $reports = Report::join('conferences', 'conferences.id', '=', 'reports.conf_id')
                ->join('favorites', 'favorites.report_id', '=', 'reports.id')
                ->where('favorites.user_id', $user->id)
                ->select(
                    'reports.id',
                    'reports.title',
                    'reports.start_time AS startTime',
                    'reports.end_time AS endTime',
                    'reports.description',
                    'reports.category_id AS categoryId',
                    'conferences.title AS confTitle',
                    'conferences.date'
                )->get();
        } else {
            $reports = Report::join('conferences', 'conferences.id', '=', 'reports.conf_id')
                ->select(
                    'reports.id',
                    'reports.title',
                    'reports.start_time AS startTime',
                    'reports.end_time AS endTime',
                    'reports.description',
                    'reports.category_id AS categoryId',
                    'conferences.title AS confTitle',
                    'conferences.date'
                )->get();
        }

        if (isset($request->startTime) && $request->startTime != '') {
            $reportsToAdd = new Collection();
            foreach ($reports as $report) {
                if (substr($report->startTime, 0, 5) >= $request->startTime) {
                    $reportsToAdd->push($report);
                }
            }
            $reports = $reportsToAdd;
        }

        if (isset($request->endTime) && $request->endTime != '') {
            $reportsToAdd = new Collection();
            foreach ($reports as $report) {
                if (substr($report->endTime, 0, 5) <= $request->endTime) {
                    $reportsToAdd->push($report);
                }
            }
            $reports = $reportsToAdd;
        }

        if (isset($request->duration) && $request->duration != -1) {
            $reportsToAdd = new Collection();
            foreach ($reports as $report) {
                $duration = (strtotime($report->endTime) - strtotime($report->startTime)) / 60;
                if ($duration == $request->duration) {
                    $reportsToAdd->push($report);
                }
            }
            $reports = $reportsToAdd;
        }
        if (isset($request->categories) && count($request->categories) > 0) {
            $allCategories = Category::select('*')->get();
            $reportsFiltered = new Collection();
            foreach ($request->categories as $categoryId) {
                $reportsToAdd = $reports->where('categoryId', $categoryId);
                $this->addReportsFromChildCategory($reportsToAdd, $reports, $allCategories, $categoryId);
                $reportsFiltered = $reportsFiltered->merge($reportsToAdd);
            }
            $reports = $reportsFiltered;
        }

        $reports = $reports->unique('id');
        $reports = new LengthAwarePaginator($reports->forPage($page, 15), $reports->count(), 15, $page);

        if ($user) {
            $allFavorites = Favorites::select('user_id', 'report_id')->where('user_id', $user->id)->get();
        }
        $allComments =  Comment::select('report_id')->get();

        $reportsInfoArray = [];
        foreach ($reports as $key => $report) {
            $remainingTime = false;
            if (time() + 7200 > strtotime($report->date . ' ' . $report->endTime)) {
                $statusColor = 'grey'; //Ended
            } else if (time() + 7200 > strtotime($report->date . ' ' . $report->startTime)) {
                $statusColor = 'red';  //Started
            } else {
                $statusColor = 'yellow';  //Waiting
                $remainingTime = strtotime($report->date . ' ' . $report->startTime) - time() - 7200;
                //Log::info($remainingTime / 60);
            }

            $reportsInfoArray[$key] = [];
            $reportsInfoArray[$key]['id'] = $report->id;
            $reportsInfoArray[$key]['title'] = $report->title;
            $reportsInfoArray[$key]['startTime'] = $report->startTime;
            $reportsInfoArray[$key]['endTime'] = $report->endTime;
            $reportsInfoArray[$key]['description'] = $report->description;
            $reportsInfoArray[$key]['commentsCount'] = $allComments->where('report_id', $report->id)->count();
            $reportsInfoArray[$key]['conferenceTitle'] = $report->confTitle;
            $reportsInfoArray[$key]['statusColor'] = $statusColor;
            $reportsInfoArray[$key]['favLoading'] = false;
            if ($user) {
                $reportsInfoArray[$key]['favorite'] = $allFavorites->where('report_id', $report->id)->count() > 0;
            } else {
                $reportsInfoArray[$key]['favorite'] = false;
            }
            if ($remainingTime) {
                $reportsInfoArray[$key]['remainingTime'] = $remainingTime;
            }
        }

        $pageInfo = [];
        $pageInfo['reports'] = $reportsInfoArray;

        $pageInfo['maxPage'] = $reports->lastPage();

        return json_encode($pageInfo);
    }

    /**
     * Add reports to array 'reports' which are in children categories of parent category
     * 
     * @param Collection &$reports - array to which children will be added
     * @param Collection &$allReports - array of all acceptable reports
     * @param Collection &$allCategories - array of all categories
     * @param int $parentCategoryId - id of parent category
     * 
     * @return void
     */
    protected function addReportsFromChildCategory(&$reports, &$allReports, &$allCategories, $parentCategoryId)
    {
        $categoriesChildren = $allCategories->where('parent_id', $parentCategoryId);
        foreach ($categoriesChildren as $childCategory) {
            $reportsToAdd = $allReports->where('categoryId', $childCategory->id);
            foreach ($reportsToAdd as $report) {
                $reports->push($report);
            }
            $this->addReportsFromChildCategory($reports, $allReports, $allCategories, $childCategory->id);
        }
    }

    /**
     * Return selected report info
     *
     * @return Object
     */
    public function show($repId)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }
        if (Report::join('categories', 'categories.id', '=', 'reports.category_id')->where('reports.id', $repId)->get()->count() > 0) {
            $report = Report::join('categories', 'categories.id', '=', 'reports.category_id')
                ->join('conferences', 'conferences.id', '=', 'reports.conf_id')
                ->select(
                    'reports.id',
                    'reports.title',
                    'start_time as startTime',
                    'end_time as endTime',
                    'description',
                    'presentation',
                    'reports.user_id as userId',
                    'conf_id as conferenceId',
                    'reports.category_id as categoryId',
                    'meeting_id as meetingId',
                    'categories.title as categoryTitle',
                    'conferences.date',
                )->where('reports.id', $repId)->first();
        } else {
            $report = Report::join('conferences', 'conferences.id', '=', 'reports.conf_id')->select(
                'reports.id',
                'reports.title',
                'start_time as startTime',
                'end_time as endTime',
                'description',
                'presentation',
                'reports.user_id as userId',
                'meeting_id as meetingId',
                'conf_id as conferenceId',
                'reports.category_id as categoryId',
                'conferences.date',
            )->where('reports.id', $repId)->first();
        }

        $meetingJoinLink = false;
        $meetingStartLink = false;
        $remainingTime = false;

        $timezoneOffset = env('TIMEZONE_DIFF_SECONDS', 0);
        $startTime = strtotime($report->date . ' ' . $report->startTime);
        $endTime = strtotime($report->date . ' ' . $report->endTime);
        $currTime = time() + $timezoneOffset;
        if ($currTime + 600 < $startTime) {
            $remainingTime = $startTime - $currTime;
        } else if ($currTime < $endTime && $report->meetingId) {
            //For listeners link is available 10 min later
            if ($user->role == 'listener') {
                if ($currTime > $startTime) {
                    //if listener is participant
                    if (Report::where('reports.id', $report->id)
                        ->join('conferences', 'conferences.id', '=', 'reports.conf_id')
                        ->join('listeners', 'conferences.id', '=', 'listeners.conference_id')
                        ->where('listeners.user_id', $user->id)
                        ->count() > 0
                    ) {
                        $meetingJoinLink = ZoomController::getMeetingInfo($report->meetingId)->join_url;
                    }
                } else {
                    $remainingTime = $startTime - $currTime;
                }
            } else if ($user->role == 'announcer') {
                if ($report->userId == $user->id) {
                    $meetingStartLink = ZoomController::getMeetingInfo($report->meetingId)->start_url;
                }
            } else { //if admin
                $meetingInfo = ZoomController::getMeetingInfo($report->meetingId);
                $meetingJoinLink = $meetingInfo->join_url;
                $meetingStartLink = $meetingInfo->start_url;
            }
        }

        $presentationName = $report->presentation;
        $report->presentation = null;

        $breadcrumbs = new Collection();
        if ($report->categoryId) {
            $allCategories = Category::select('*')->get();
            $this->setBreadcrumbs($breadcrumbs, $allCategories, $report->categoryId);
            $breadcrumbs->push([
                'text' => 'Reports',
                'categoryId' => null
            ]);
            $breadcrumbs = $breadcrumbs->reverse();
        }

        return json_encode([
            'report' => $report,
            'canUpdate' => UserController::canReportEdit(Auth::user(), $report->id),
            'breadcrumbs' => array_values($breadcrumbs->toArray()),
            'meetingStartLink' => $meetingStartLink,
            'meetingJoinLink' => $meetingJoinLink,
            'presentationName' => $presentationName,
            'remainingTime' => $remainingTime,
        ]);
    }

    /**
     * Set breadcrumbs property
     * 
     * @param Collection &$breadcrumbs - array to which parents will be added
     * @param Collection &$allCategories - array of all categories
     * @param int $categoryId - id of category, parents of which will be added
     * 
     * @return void
     */
    protected function setBreadcrumbs(&$breadcrumbs, &$allCategories, $categoryId)
    {
        $category = $allCategories->where('id', $categoryId)->first();

        if (!$category) {
            return;
        }

        $breadcrumb = [
            'text' => $category->title,
            'categoryId' => $category->id
        ];
        $breadcrumbs->push($breadcrumb);

        if ($category->parent_id) {
            $this->setBreadcrumbs($breadcrumbs, $allCategories, $category->parent_id);
        }
    }

    /**
     * Delete report from db
     *
     * @return bool
     */
    public function destroy(int $confId, Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        $report = null;
        if (isset($request->reportId)) {
            $report = Report::findOrFail($request->reportId);
        } else {
            $report = Report::where('conf_id', $confId)->where('user_id', $user->id)->first();
        }

        $userId = $report->user_id;
        if ($report->delete()) {
            if ($user->role == 'admin' && $user->id != $userId)
                MailController::reportDeleted($userId, $confId);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Update report
     *
     * @return int number of affected rows
     */
    public function update(int $reportId, ReportRequest $request) //TODO create meeting
    {
        $user = Auth::user();
        if (!$user || !UserController::canReportEdit(Auth::user(), $reportId)) {
            abort(403);
        }

        $report = Report::findOrFail($reportId);
        $reportData = json_decode($request->report);

        $timeChanged = false;
        if (
            substr($report->start_time, 0, 5) != $reportData->startTime
            || substr($report->end_time, 0, 5) != $reportData->endTime
        ) {
            $timeChanged = true;
        }

        $report->title = $reportData->title;
        $report->description = isset($reportData->description) ? $reportData->description : $report->description;
        $report->start_time = $reportData->startTime;
        $report->end_time = $reportData->endTime;
        $report->category_id = isset($reportData->categoryId) ? $reportData->categoryId : $report->category_id;
        if (isset($request->presentation) && $request->presentation) {
            if (!File::exists(public_path() . "/presentations")) {
                File::makeDirectory(public_path() . "/presentations");
            }
            if ($request->presentation->move(public_path('presentations'), 'presentation' . $report->id . $request->type)) {
                $report->presentation = 'presentation' . $report->id . $request->type;
            }
        }

        if ($report->save()) {
            if ($timeChanged) {
                MailController::reportTimeChange($user, $reportId, $reportData->conferenceId);
            }
            return true;
        } else {
            abort(500);
        }
    }

    /**
     * Add report to db
     *
     * @return bool
     */
    public function create(ReportRequest $request)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        $reportData = json_decode($request->report);

        $meetingId = false;
        if (isset($reportData->isOnline) && $reportData->isOnline) {
            $conference = Conferences::findOrFail($reportData->conferenceId);
            $meetingTime = date('Y-m-d H:i:s', strtotime($conference->date . ' ' . $reportData->startTime) - 600);
            $duration = intval((strtotime($reportData->endTime) - strtotime($reportData->startTime)) / 60) + 10;
            $meetingId = ZoomController::createMeeting($reportData->title, $meetingTime, $duration);
            Cache::forget('meetings');
        }

        $res = Report::create([
            'title' => $reportData->title,
            'description' => isset($reportData->description) ? $reportData->description : null,
            'start_time' => $reportData->startTime,
            'end_time' => $reportData->endTime,
            'presentation' => null,
            'user_id' => $user->id,
            'conf_id' => $reportData->conferenceId,
            'category_id' => isset($reportData->categoryId) ? $reportData->categoryId : null,
            'meeting_id' => $meetingId ? $meetingId : null,
        ]);
        if (!$res) {
            abort(500);
        }

        if (isset($request->presentation) && $request->presentation) {
            if (!File::exists(public_path() . "/presentations")) {
                File::makeDirectory(public_path() . "/presentations");
            }
            if ($request->presentation->move(public_path('presentations'), 'presentation' . $res->id . $request->type)) {
                $res->presentation = 'presentation' . $res->id . $request->type;
                $res->save();
            }
        }

        MailController::newAnnouncer($user, $res->id, $res->conf_id);
        return true;
    }

    /**
     * Add report to user favorites
     *
     * @return bool
     */
    public function favorite($reportId)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        return Favorites::create([
            'user_id' => $user->id,
            'report_id' => $reportId,
        ]);
    }

    /**
     * Delete report from user favorites
     *
     * @return bool
     */
    public function unfavorite($reportId)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        return Favorites::where('report_id', $reportId)->where('user_id', $user->id)->delete();
    }

    /**
     * Return reports where title is like request->searchText
     *
     * @return Object
     */
    public function search(Request $request)
    {
        $reports = Report::select('reports.id', 'reports.title')
            ->join('conferences', 'conferences.id', '=', 'reports.conf_id')
            ->where('conferences.date', '>=', date('Y-m-d'))
            ->where('end_time', '>=', date('H:i:s', time() + env('TIMEZONE_DIFF_SECONDS', 0)))
            ->where('reports.title', 'like', '%' . $request->searchText . '%')
            ->get();
        foreach ($reports as $key => $report) {
            $reports[$key]['path'] = '/report/' . $report->id;
        }
        return json_encode($reports->toArray());
    }

    /**
     * Download presentation by name
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function downloadPresentation($presentationName)
    {
        return Storage::download('presentations/' . $presentationName);
    }
}
