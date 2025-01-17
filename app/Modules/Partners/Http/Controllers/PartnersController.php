<?php namespace App\Modules\Partners\Http\Controllers;

use App\Modules\Partners\Partner;
use Redirect, FrontController;

class PartnersController extends FrontController {

    public function index()
    {
        $partners = Partner::orderBy('position', 'ASC')->published()->get();

        $this->pageView('partners::index', compact('partners'));
    }

    /**
     * Show the website of a partner
     * 
     * @param  int $id The id of the partner
     * @return Redirect
     */
    public function show($id)
    {
        $partner = Partner::published()->findOrFail($id);

        $partner->access_counter++;
        $partner->save();

        return Redirect::to($partner->url); // Go to partner website
    }

}