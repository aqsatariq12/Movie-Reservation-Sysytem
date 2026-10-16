<?php

namespace App\Http\Controllers;

use App\Actions\Home\GetHomePageData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Public CineBook home page (Figma: "01 · Customer — Home").
     *
     * The controller stays thin: all data for the page comes from the
     * GetHomePageData action.
     */
    public function __invoke(Request $request, GetHomePageData $getHomePageData): View
    {
        return view('home.index', $getHomePageData->execute($request->query('date')));
    }
}
