<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\Conferences;
use App\Models\Report;

class CategoryObserver
{
    /**
     * Handle the Category "deleted" event.
     *
     * @param  \App\Models\Category  $category
     * @return void
     */
    public function deleted(Category $category)
    {
        $categories = Category::where('parent_id', $category->id)->get();
        foreach ($categories as $cat) {
            $cat->delete();
        }
        $conferences = Conferences::where('category_id', $category->id)->get();
        foreach ($conferences as $conference) {
            $conference->category_id = NULL;
            $conference->save();
        }
        $reports = Report::where('category_id', $category->id)->get();
        foreach ($reports as $report) {
            $report->category_id = NULL;
            $report->save();
        }
    }

}