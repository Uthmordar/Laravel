<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;

class PosterController extends \BaseController {

    /**
     * View : tous les posters
     * 
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index() {
        $poster = Poster::admin()->paginate(8);
        $links = $poster->links();
        return View::make('admin.home', array('title' => 'admin home', 'poster' => $poster, 'links' => $links));
    }
    
    public function indexAll() {
        $poster = Poster::admin()->get();
        return View::make('admin.home', array('title' => 'admin home', 'poster' => $poster));
    }

    /**
     * View : création Poster
     * 
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create() {
        return View::make('admin.createPoster', array('title' => 'ajouter un poster'));
    }

    /**
     * Insert : poster
     * 
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store() {
        // var_dump(Input::all()); die();
        $rules = array(
            'title' => 'required',
            'tpl_' => 'required'
        );

        $validator = Validator::make(Input::all(), $rules);
        if ($validator->fails()) {
            Session::flash('messageCreatePoster', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove" style="color:red;"></span>Formulaire incomplet</p>');
            return Redirect::back()->withInput()->withErrors($validator);
        } else {
            $poster = new Poster;
            $poster->title = Input::get('title');
            if(Poster::where('title', '=', Input::get('title'))->count() != 0){
                Session::flash('messageCreatePoster', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove" style="color:red;"></span>Un poster utilisant ce nom existe déjà.</p>');
                Return Redirect::back();
            }

            $tplId = substr(Input::get('tpl_'), strrpos(Input::get('tpl_'), '_') + 1);
            $tpl = Templateposter::find($tplId);
            $poster->templateposter()->associate($tpl);
            $poster->status = 'unpublish';
            $poster->created_at = Carbon::now();
            $poster->save();

            Session::flash('messageActionPoster', '<p class="success bg-success"><span class="glyphicon glyphicon-ok" style="color:green;"></span>Poster ajouté.</p>');
            return Redirect::to('/admin/home');
        }
        Session::flash('messageCreatePoster', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove" style="color:red;"></span>Une erreur est survenue.</p>');
        return Redirect::back();
    }

    /**
     * View : edition d'article
     * 
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id) {
        try {
            $poster = Poster::findOrFail($id);
            $template = $poster->templateposter;
            return View::make('admin.editPoster', array('title' => 'édition poster', 'poster' => $poster, 'template' => $template));
        } catch (ModelNotFoundException $e) {
            Session::flash('messageActionPoster', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove" style="color:red;"></span>Erreur dans la requête.</p>');
            return Redirect::back();
        }
    }

    /**
     * Update : poster
     * 
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($id) {
        try {
            $rules = array(
                'tpl_' => 'required'
            );

            $validator = Validator::make(Input::all(), $rules);
            if ($validator->fails()) {
                Session::flash('messageUpdatePoster', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove" style="color:red;"></span>Formulaire incomplet</p>');
                return Redirect::back()->withInput()->withErrors($validator);
            } else {
                $poster = Poster::findOrFail($id);
                if (Input::get('title')) {
                    $poster->title = Input::get('title');
                }
                if(Poster::where('title', '=', Input::get('title'))->count() != 0){
                    Session::flash('messageCreatePoster', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove" style="color:red;"></span>Un poster utilisant ce nom existe déjà.</p>');
                    Return Redirect::back();
                }

                $tplId = substr(Input::get('tpl_'), strrpos(Input::get('tpl_'), '_') + 1);
                $tpl = Templateposter::findOrFail($tplId);
                $poster->templateposter()->associate($tpl);
                $poster->updated_at = Carbon::now();
                $poster->save();

                Session::flash('messageActionPoster', '<p class="success bg-success"><span class="glyphicon glyphicon-ok" style="color:green;"></span>Poster édité.</p>');
                return Redirect::to('admin/home');
            }
            Session::flash('messageUpdatePoster', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove" style="color:red;"></span>Une erreur est survenue.</p>');
            return Redirect::back();
        } catch (ModelNotFoundException $e) {
            Session::flash('messageUpdatePoster', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove" style="color:red;"></span>Erreur dans la requête.</p>');
            return Redirect::back();
        }
    }

    /**
     * DELETE : poster
     * 
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id) {
        try {
            Poster::findOrFail($id);
            Poster::destroy($id);
            Session::flash('messageTrash', '<p class="success bg-success"><span class="glyphicon glyphicon-ok" style="color:green;"></span>Message supprimé avec succès.</p>');
            return Redirect::back();
        } catch (ModelNotFoundException $e) {
            Session::flash('messageTrash', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove" style="color:red;"></span>Erreur dans la suppression.</p>');
            return Redirect::back();
        }
    }

    //STATUS
    // modification de statut individuelle
    /**
     * UPDATE : poster status
     * 
     * @param type $id
     * @return type
     */
    public function status($id) {
        try {
            $article = Poster::findOrFail($id);
            if ($article->status == 'publish') {
                $article->status = 'unpublish';
            } else if ($article->status == 'unpublish') {
                $article->status = 'publish';
            } else if ($article->status == 'trash') {
                $article->status = 'unpublish';
            }
            if ($article->save()) {
                Session::flash('message', '<p class="success bg-success"><span class="glyphicon glyphicon-ok" style="color:green;"></span>Modification de statut réussie.</p>');
            } else {
                Session::flash('message', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove" style="color:red;"></span>Echec du changement de statut.</p>');
            }
            return Redirect::back();
        } catch (ModelNotFOundException $e) {
            Session::flash('message', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove" style="color:red;"></span>Echec du changement de statut.</p>');
            return Redirect::back();
        }
    }

    /**
     * UPDATE : posters status
     * 
     * @return type
     */
    public function action() {
        $status = Input::get('action');
        DB::transaction(function() use ($status) {
            foreach (Input::all() as $k => $v) {
                if ($v == 'on') {
                    $test = explode('_', $k);
                    if (($test[0] == 'poster')) {
                        $poster = Poster::find($test[1]);
                        if ($status == 'publish') {
                            $poster->status = 'publish';
                        } else if ($status == 'unpublish') {
                            $poster->status = 'unpublish';
                        } else if ($status == 'trash') {
                            $poster->status = 'trash';
                        }
                        $poster->save();
                    }
                }
            }

            Session::flash('messageActionPoster', '<p class="success bg-success"><span class="glyphicon glyphicon-ok" style="color:green;"></span>Modification(s) réussie(s).</p>');
            return Redirect::back();
        });
        Session::flash('messageActionPoster', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove" style="color:red;"></span>Echec des modifications.</p>');
        return Redirect::back();
    }

    /**
     * UPDATE : status trash poster
     * 
     * @param type $posterId
     * @return type
     */
    public function trash($posterId) {
        try {
            $poster = Poster::findOrFail($posterId);
            $poster->status = 'trash';
            $poster->save();
            Session::flash('messageActionPoster', '<p class="success bg-success"><span class="glyphicon glyphicon-ok" style="color:green;"></span>Mise à la poubelle réussie.</p>');
            return Redirect::back();
        } catch (ModelNotFoundException $e) {
            Session::flash('messageActionPoster', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove" style="color:red;"></span>Echec de la mise à la poubelle.</p>');
            return Redirect::back();
        }
    }

}
