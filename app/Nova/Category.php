<?php

namespace App\Nova;

use App\Models\Category as ModelsCategory;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;

class Category extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Category::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'title';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'title',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),

            Text::make('Title')
                ->sortable()
                ->rules('required', 'max:255'),
                  
            BelongsTo::make('Parent Category', 'parent', Category::class)
                ->sortable()
                ->nullable(),

            Number::make('Children count', function ()
            {
                return $this->childrenCount(ModelsCategory::select('id','parent_id')->get(), $this->id);
            }),
        ];
    }

     /**
     * Get count of all children categories
     *
     * @param Collection &$allCategories array of all categories
     * @param int $parentId id of parent category
     * @return int
     */
    protected function childrenCount($allCategories, $parentId){
        $count = 0;
        $childrenCategories = $allCategories->where('parent_id', $parentId);
        foreach ($childrenCategories as $category) {
            $count += 1 + $this->childrenCount($allCategories, $category->id);
        }
        return $count;
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }
}
