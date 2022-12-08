<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConferenceRequest;
use App\Models\Category;
use App\Models\Conferences;
use App\Models\Listener;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Request;

class ConferenceController extends Controller
{

    /**
     * Return conferences info at selected page
     *
     * @return Object
     */
    public function index($page, Request $request)
    {
        $user = Auth::user();
        $reportsAll = Report::select('*')->get();
        //Ids of conferences to which the user has joined
        $joinedConfIds = [];
        if ($user) {
            if ($user->role == 'listener') {
                $joinedConfIds = Listener::where('user_id', $user->id)->pluck('conference_id')->toArray();
            } else {
                $joinedConfIds = $reportsAll->where('user_id', $user->id)->pluck('conference_id')->toArray();
            }
        }

        $conferences = Conferences::select('*')->get();

        if (isset($request->reportsCount) && $request->reportsCount != -1) {
            $conferencesToAdd = new Collection();
            foreach ($conferences as $conference) {
                if ($reportsAll->where('conference_id', $conference->id)->count() ==  $request->reportsCount) {
                    $conferencesToAdd->push($conference);
                }
            }
            $conferences = $conferencesToAdd;
        }

        if (isset($request->startDate) && $request->startDate != '') {
            $conferencesToAdd = new Collection();
            foreach ($conferences as $conference) {
                if ($conference->date >= $request->startDate) {
                    $conferencesToAdd->push($conference);
                }
            }
            $conferences = $conferencesToAdd;
        }

        if (isset($request->endDate) && $request->endDate != '') {
            $conferencesToAdd = new Collection();
            foreach ($conferences as $conference) {
                if ($conference->date <= $request->endDate) {
                    $conferencesToAdd->push($conference);
                }
            }
            $conferences = $conferencesToAdd;
        }

        if (isset($request->categories) && count($request->categories) > 0) {
            $allCategories = Category::select('*')->get();
            $conferencesFiltered = new Collection();
            foreach ($request->categories as $categoryId) {
                $conferencesToAdd = $conferences->where('category_id', $categoryId);
                $this->addConferencesFromChildCategory($conferencesToAdd, $conferences, $allCategories, $categoryId);

                $conferencesFiltered = $conferencesFiltered->merge($conferencesToAdd);
            }
            $conferences = $conferencesFiltered;
        }
        $conferences = $conferences->unique('id');
        $conferences = new LengthAwarePaginator($conferences->forPage($page, 15), $conferences->count(), 15, $page);

        $conferencesInfoArray = [];
        $i = 0;
        foreach ($conferences as $conference) {
            $conferencesInfoArray[$i] = [];
            $conferencesInfoArray[$i]['title'] = $conference->title;
            $conferencesInfoArray[$i]['date'] = $conference->date;
            $conferencesInfoArray[$i]['id'] = $conference->id;
            $conferencesInfoArray[$i]['canEdit'] = UserController::canEdit($user, $conference->id);
            $conferencesInfoArray[$i]['participant'] = in_array($conference->id, $joinedConfIds);
            $i++;
        }

        $pageInfo = [];
        $pageInfo['conferences'] = $conferencesInfoArray;

        $pageInfo['maxPage'] = $conferences->lastPage();

        if ($user) {
            $pageInfo['isListener'] = $user->role == 'listener';
        }

        return json_encode($pageInfo);
    }

    /**
     * Add conferences to array 'conferences' which are in children categories of parent category
     * 
     * @param Collection &$conferences - array to which children will be added
     * @param Collection &$allConferences - array of all acceptable conferences
     * @param Collection &$allCategories - array of all categories
     * @param int $parentCategoryId - id of parent category
     * 
     * @return void
     */
    protected function addConferencesFromChildCategory(&$conferences, &$allConferences, &$allCategories, $parentCategoryId)
    {
        $categoriesChildren = $allCategories->where('parent_id', $parentCategoryId);
        foreach ($categoriesChildren as $childCategory) {
            $conferencesToAdd = $allConferences->where('category_id', $childCategory->id);
            foreach ($conferencesToAdd as $conference) {
                $conferences->push($conference);
            }
            $this->addConferencesFromChildCategory($conferences, $allConferences, $allCategories, $childCategory->id);
        }
    }

    /**
     * Return details of selected conference
     *
     * @return Object
     */
    public function show(int $conferenceId)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }
        //if conference in category
        if (Conferences::join('categories', 'categories.id', '=', 'conferences.category_id')->where('conferences.id', $conferenceId)->get()->count() > 0) {
            $conference = Conferences::join('categories', 'categories.id', '=', 'conferences.category_id')->select(
                'conferences.id',
                'conferences.title',
                'conferences.date',
                'conferences.time',
                'conferences.country',
                'conferences.latitude',
                'conferences.longitude',
                'conferences.category_id as categoryId',
                'categories.title as categoryTitle',
            )->where('conferences.id', $conferenceId)->first();
        } else {
            $conference = Conferences::select(
                'id',
                'title',
                'date',
                'time',
                'country',
                'latitude',
                'longitude',
            )->where('id', $conferenceId)->first();
        }
        $breadcrumbs = new Collection();
        if ($conference->categoryId) {
            $allCategories = Category::select('*')->get();
            $this->setBreadcrumbs($breadcrumbs, $allCategories, $conference->categoryId);
            $breadcrumbs->push([
                'text' => 'Conferences',
                'categoryId' => null
            ]);
            $breadcrumbs = $breadcrumbs->reverse();
        }

        $isListenter = $user->role == 'listener';
        $isParticipant = false;
        if ($isListenter) {
            $isParticipant = Listener::where('user_id', $user->id)->where('conference_id', $conference->id)->count() >= 1 ? true : false;
        } else {
            $isParticipant = Report::select('conference_id')->where('user_id', $user->id)->where('conference_id', $conference->id)->count() >= 1 ? true : false;
        }

        return json_encode([
            'conference' => $conference,
            'participant' => $isParticipant,
            'canUpdate' => UserController::canEdit(Auth::user(), $conference->id),
            'breadcrumbs' => array_values($breadcrumbs->toArray()),
            'isListener' => $isListenter
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
     * Delete conference from db
     *
     * @return bool
     */
    public function destroy(int $conferenceId)
    {
        if (!UserController::canEdit(Auth::user(), $conferenceId)) {
            abort(403);
        }

        MailController::conferenceDeleted($conferenceId);

        return Conferences::where('id', $conferenceId)->delete();;
    }

    /**
     * Update conference
     *
     * @return int number of affected rows
     */
    public function update(int $conferenceId, ConferenceRequest $request)
    {
        if (!UserController::canEdit(Auth::user(), $conferenceId)) {
            abort(403);
        }

        $values = [
            'title' => $request->title,
            'date' => $request->date,
            'time' => $request->time,
            'country' => $request->country,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'category_id' => $request->categoryId
        ];

        return Conferences::where('id', $conferenceId)->update($values);
    }

    /**
     * Add conference to db
     *
     * @return bool
     */
    public function create(ConferenceRequest $request)
    {
        if (!UserController::canEdit(Auth::user())) {
            abort(403);
        }

        Conferences::create([
            'title' => $request->title,
            'country' => $request->country,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'date' => $request->date,
            'time' => $request->time,
            'user_id' => Auth::user()->id,
            'category_id' => $request->categoryId,
        ]);
        return true;
    }

    /**
     * Return times when conference is busy
     *
     * @return Object
     */
    public function getBusyTimes(int $conferenceId, Request $request)
    {
        if (!$request->repId) {
            $allTimes = Conferences::join('reports', 'conferences.id', '=', 'reports.conference_id')
                ->select('reports.start_time', 'reports.end_time')
                ->where('conferences.id', $conferenceId)->get();
        } else {
            $allTimes = Conferences::join('reports', 'conferences.id', '=', 'reports.conference_id')
                ->select('reports.start_time', 'reports.end_time')
                ->where('conferences.id', $conferenceId)
                ->where('reports.id', '<>', $request->repId)->get();
        }
        $times = [
            'startTimes' => $allTimes->pluck('start_time')->toArray(),
            'endTimes' => $allTimes->pluck('end_time')->toArray(),
            'confStartTime' => Conferences::select('time')->where('id', $conferenceId)->first()->time
        ];

        return json_encode($times);
    }

    /**
     * Return category of selected conference
     *
     * @return Object
     */
    public function getCategory(int $conferenceId)
    {
        return json_encode(Conferences::where('id', $conferenceId)->first()->category_id);
    }

    /**
     * Return conferences where title is like request->searchText
     *
     * @return Object
     */
    public function search(Request $request)
    {
        $conferences = Conferences::select('id', 'title')
            ->where('date', '>=', date('Y-m-d'))
            ->where('title', 'like', '%' . $request->searchText . '%')
            ->get();
        foreach ($conferences as $key => $conference) {
            $conferences[$key]['path'] = '/conference/' . $conference->id;
        }
        return json_encode($conferences->toArray());
    }

    /**
     * Return conferences where title is like request->searchText
     *
     * @return Object
     */
    public function joinAsListener($conferenceId)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        $result = Listener::create([
            'user_id' => $user->id,
            'conference_id' => $conferenceId,
        ]) ? true : false;
        if ($result) {
            MailController::newListener($user, $conferenceId);
            $user->joins++;
            $user->save();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Delete conference from db
     *
     * @return bool
     */
    public function cancelJoin(int $conferenceId)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        $user->joins--;
        $user->save();
        return Listener::where('conference_id', $conferenceId)->where('user_id', $user->id)->delete();
    }
}
