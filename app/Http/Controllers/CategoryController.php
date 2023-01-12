<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Conferences;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    /**
     * Return all categories of selected parent
     *
     * @return Object
     */
    public function index($parentId = null)
    {

        $categories = [];
        $categoriesEloq = Category::select('*')->get();
        if (!$parentId) {
            $rootCategories = $categoriesEloq->whereNull('parent_id');
        } else {
            $rootCategories = $categoriesEloq->where('id', $parentId);
            //Log::info($parentId);
        }

        $i = 0;
        foreach ($rootCategories as $category) {
            $categories[$i] = [
                'id' => $category->id,
                'name' => $category->title,
                'children' => []
            ];
            $this->setChildren($categories[$i], $categoriesEloq);
            $i++;
        }

        return json_encode($categories);
    }

    /**
     * Return all categories in list format
     *
     * @return Object
     */
    public function indexList()
    {
        $categories = [];
        $categories = Category::select('*')->get();
        $categoriesList = [];
        foreach ($categories as $category) {
            $categoriesList[] = [
                'state' => $category->title,
                'value' => $category->id
            ];
        }
        return json_encode($categoriesList);
    }
    /**
     * Set children category property
     *
     * @return void
     */
    protected function setChildren(&$category, &$categoriesEloq)
    {
        $categoriesChildren = $categoriesEloq->where('parent_id', $category["id"]);
        $i = 0;
        foreach ($categoriesChildren as $child) {
            $category['children'][] = [
                'id' => $child->id,
                'name' => $child->title,
                'children' => []
            ];
            $this->setChildren($category['children'][$i], $categoriesEloq);
            $i++;
        }
    }

    /**
     * Return category info
     *
     * @return Object
     */
    public function show($categoryId)
    {
        if (!Auth::user()) {
            abort(403);
        }

        $category = Category::select('*')->where('id', $categoryId)->first();

        $categoryPageInfo = [
            'category' => $category,
            'reportsCount' => Report::where('category_id', $category->id)->count(),
            'conferencesCount' => Conferences::where('category_id', $category->id)->count()
        ];

        return json_encode($categoryPageInfo);
    }

    /**
     * Add category to db
     *
     * @return bool
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }
        if ($user->role != 'admin') {
            abort(403);
        }
        if(!$request->title){
            abort(400);
        }
        
        $values = [
            'title' => $request->title,
            'parent_id' => $request->parentId
        ];

        return Category::create($values) ? true : false;
    }

    /**
     * Update category
     *
     * @return bool
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }
        if ($user->role != 'admin') {
            abort(403);
        }

        $category = Category::findOrFail($id);
        if($request->title){
            $category->title = $request->title;
            $category->save();
        }else{
            abort(400);
        }
    }

    /**
     * Delete category from db
     *
     * @return String
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }
        if ($user->role != 'admin') {
            abort(403);
        }

        if (Category::findOrFail($id)->delete()) {
            return 'Deleted:$id';
        } else {
            abort(500);
        }
    }
}
