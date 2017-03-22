<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use App\Template;




class TemplateController extends Controller
{


    public function create()
    {
        return view('/templates/create');
    }

    // creates and adds a new template into the Database
    // COMPLETE
    public function store()
    {
        DB::table('template')->insert(
            ['tname' => request('tname'), 'content' => request('content')]
        );

        // redirect
        // Session::flash('message', 'Successfully added the template!');
        return Redirect::to('/templates');
    }

    // shows the form for editing the specified template
    // COMPLETE
    public function edit($tname)
    {
        // get the template
        $template = DB::table('template')->select('*')->where('tname', '=', $tname)->get();

        // show the edit form and pass the template
        return view('/templates/edit')->with('template', $template);
    }

    //  updates a specific template in the database
    // COMPLETE
    public function update($tname)
    {
        DB::table('template')
            ->where('tname', request('tname'))
            ->update(['content' => request('content')]);

        // redirect
        Session::flash('message', 'Successfully updated the template!');
        return Redirect::to('/templates');
    }

    // deletes a specific template
    // COMPLETE
    public function destroy($tname)
    {
        DB::table('template')->where('tname','=', $tname)->delete();

        Session::flash('message', 'Successfully deleted the template!');
        return Redirect::to('/templates');

    }

    // shows a specific template
    // COMPLETE
    public function show($tname)
    {
        $template = DB::table('template')->select('*')->where('tname', '=', $tname)->get();

        return view('/templates/show')->with('template', $template);
    }

    // shows all templates
    // COMPLETE
    public function index()
    {
        //$templates = Template::all();
        //$templates = DB::table('template')->select('*')->get();
        // load the view and pass all templates
        //return view('/templates/index')->with('templates', $templates);
        return view('/templates/index');
    }
    public function applyTemplate()
    {
        return view('/templates/apply');
    }
}