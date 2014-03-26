<?php

class SectionController extends \BaseController {

    /**
     * View : all sections pour le poster $posterId
     * 
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index($posterId) {
        try {
            $poster = Poster::findOrFail($posterId);
            $sections = $poster->sections()->whereRaw("(status='publish' OR status='unpublish')")->orderBy('order', 'ASC')->paginate(10);
            $links = $sections->links();
            return View::make('admin.dashSection', array('sections' => $sections, 'poster' => $poster, 'title' => 'sections', 'links' => $links));
        } catch (ModelNotFoundException $e) {
            Session::flash('messageActionPoster', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove"></span>Erreur dans la requête.</p>');
            return Redirect::back();
        }
    }

    /**
     * UPDATE section order
     * 
     * @param type $sectionId
     * @return type
     */
    public function up($sectionId) {
        try {
            $section = Section::findOrFail($sectionId);
            if ($section['order'] > 1) {
                $section->order = $section['order'] - 1;
                $section->save();
            }
            return Redirect::back();
        } catch (ModelNotFoundException $e) {
            Session::flash('messageActionSections', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove"></span>Erreur dans la requête, si le problème persiste contactez un admin.</p>');
            return Redirect::back();
        }
    }

    /**
     * UPDATE section order 
     * 
     * @param type $sectionId
     * @return type
     */
    public function down($sectionId) {
        try {
            $section = Section::findOrFail($sectionId);
            $section->order = $section['order'] + 1;
            $section->save();

            return Redirect::back();
        } catch (ModelNotFoundException $e) {
            return Redirect::to('admin/home');
        }
    }

    /**
     * VIEW : création de section pour poster $posterId
     * 
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create($posterId) {
        return View::make('admin.createSection', array('title' => 'ajouter une section', 'posterId' => $posterId));
    }

    /**
     * INSERT : section 
     * 
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store($posterId) {
        $rules = array(
            'title' => 'required',
            'artShape' => 'required'
        );

        $validator = Validator::make(Input::all(), $rules);
        if ($validator->fails()) {
            Session::flash('messageCreateSection', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove" style="color:red;"></span>Formulaire incomplet</p>');
            return Redirect::back()->withInput()->withErrors($validator);
        }
        try {
            // TRANSACTION POUR CREATION DE LA SECTION ET DES ARTICLES ASSOCIES
            DB::transaction(function() use ($posterId) {
                $section = new Section;
                $section->title = Input::get('title');
                $section->poster()->associate(Poster::findOrFail($posterId));
                $section->first_level_pattern = Input::get('artShape');

                if (Input::get('secondLevel')) {
                    $section->second_level = 1;
                    if(Input::get('artShape2')){
                        $section->second_level_pattern = Input::get('artShape2');
                    }
                }
                
                if (Input::get('bg_box')) {
                    $section->background = 1;
                    if(Input::get('bg_parallax_box')){
                        $section->parallax = 1;
                    }
                }

                $section->status = 'unpublish';
                $section->created_at = Carbon::now();
                $section->save();

                if (Input::get('artShape')) {
                    $pattern1 = trim(Input::get('artShape'));
                    $pattern1 = explode('/', $pattern1);
                    foreach ($pattern1 as $k) {
                        $article = new Article;
                        $article->section()->associate($section);
                        $article->level = 0;
                        $article->pattern = $k;
                        $article->save();
                    }
                }

                if (Input::get('artShape2')) {
                    $pattern2 = trim(Input::get('artShape2'));
                    $pattern2 = explode('/', $pattern2);
                    foreach ($pattern2 as $k) {
                        $article = new Article;
                        $article->section()->associate($section);
                        $article->level = 1;
                        $article->pattern = $k;
                        $article->save();
                    }
                }
                    
                // GESTION DE L'UPLOAD DU BACKGROUND
                if(Input::hasfile('file') && Input::get('bg_box')) {
                    $file = Input::file('file');
                    $files = [$file];
                    $rules = ['image' => 'image|mime:jpg,png,gif, jpeg|max:3000'];
                    $validator = Validator::make($files, $rules);

                    $fileTrueName = $file->getClientOriginalName();
                    $fileExtension = $file->getClientOriginalExtension();
                    $fileThumb = $file;
                    
                    $destinationPath = 'uploads/';
                    $filename = str_random(15) . '.' . $fileExtension;

                    $image = new BackgroundImage;
                    $image->name = $filename;
                    $image->created_at = Carbon::now();
                    $image->save();
                    $thumbPath = $destinationPath . '/_min/' . $filename;
                    // fonction de resize des images
                    HelperImage::thumb($fileThumb, 50, 50, $thumbPath);

                    $upload_success = $file->move($destinationPath, $filename);

                    if ($upload_success) {
                        $bg = BackgroundImage::findOrFail($image->id);
                        $section->backgroundImages()->attach($image->id);
                    }else{
                        Session::flash('messageCreateSection', "<p class='error bg-danger'><span class='glyphicon glyphicon-remove' style='color:red;'></span>Problème d'upload.</p>");
                        return Redirect::back();
                    }
                }else if(Input::get('bg-selected') && Input::get('bg_box')) {
                    $bg = BackgroundImage::findOrFail(Input::get('bg-selected'));
                    $section->backgroundImages()->detach();
                    $section->backgroundImages()->attach($bg->id);
                }
            });    

            Session::flash('messageActionSections', '<p class="success bg-success"><span class="glyphicon glyphicon-ok" style="color:green;"></span>Section crée et article(s) généré(s).</p>');
            return Redirect::to('/admin/' . $posterId . '/dashSection');
        } catch (ModelNotFoundException $e) {
            Session::flash('messageCreateSection', "<p class='error bg-danger'><span class='glyphicon glyphicon-remove' style='color:red;'></span>Erreur lors de la requête.</p>");
            return Redirect::back();
        }
    }

    /**
     * VIEW : edition de section
     * 
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($posterId, $sectionId) {
        try {
            $section = Section::findOrFail($sectionId);
            return View::make('admin.editSection', array('title' => 'éditer une section', 'section' => $section, 'posterId' => $posterId));
        } catch (ModelNotFoundException $e) {
            Session::flash('messageActionsPoster', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove" style="color:red;"></span>Erreur dans la requête.</p>');
            return Redirect::back();
        }
    }

    /**
     * UPDATE : section $id pour poster $posterId
     * 
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($posterId, $id) {
        //var_dump(Input::all());die();
        $rules = array(
        );

        $validator = Validator::make(Input::all(), $rules);
        if ($validator->fails()) {
            Session::flash('messageUpdateSection', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove" style="color:red;"></span>Formulaire incomplet</p>');
            return Redirect::back()->withInput()->withErrors($validator);
        }
        try {
            DB::transaction(function() use ($id) {
                $section = Section::findOrFail($id);
                (Input::get('title'))? $section->title = Input::get('title') : '';

                if (Input::get('bg_box')) {
                    $section->background = 1;
                    (Input::get('bg_parallax_box'))? $section->parallax = 1 : $section->parallax = 0;
                }else{
                    $section->background =0;
                    $section->parallax = 0;
                }
                
                if (input::get('artShape')) {
                    $section->first_level_pattern = Input::get('artShape');
                    $articles = $section->articles()->firstLevel()->get();
                    foreach ($articles as $article) {
                        Article::findOrFail($article->id);
                        Article::destroy($article->id);
                    }
                }

                if (Input::get('secondLevel')) {
                    $section->second_level = 1;
                    $section->second_level_pattern = input::get('artShape2');
                    $articles = $section->articles()->secondLevel()->get();
                    foreach ($articles as $article) {
                        Article::findOrFail($article->id);
                        Article::destroy($article->id);
                    }
                }
                $section->updated_at = Carbon::now();
                $section->save();

                if (Input::get('artShape')) {
                    $pattern1 = trim(Input::get('artShape'));
                    $pattern1 = explode('/', $pattern1);
                    foreach ($pattern1 as $k) {
                        $article = new Article;
                        $article->section()->associate($section);
                        $article->level = 0;
                        $article->pattern = $k;
                        $article->save();
                    }
                }

                if (Input::get('artShape2')) {
                    $pattern2 = trim(Input::get('artShape2'));
                    $pattern2 = explode('/', $pattern2);
                    foreach ($pattern2 as $k) {
                        $article = new Article;
                        $article->section()->associate($section);
                        $article->level = 1;
                        $article->pattern = $k;
                        $article->save();
                    }
                }
                
                // GESTION DE L'UPLOAD DU BACKGROUND
                if(Input::hasfile('file') && Input::get('bg_box')) {
                    $file = Input::file('file');
                    $files = [$file];
                    $rules = ['image' => 'image|mime:jpg,png,gif, jpeg|max:3000'];
                    $validator = Validator::make($files, $rules);

                    $fileTrueName = $file->getClientOriginalName();
                    $fileExtension = $file->getClientOriginalExtension();
                    $fileThumb = $file;

                    if ($validator->fails()) {
                        Session::flash('messageUpdateSection', "<p class='error bg-danger'><span class='glyphicon glyphicon-remove' style='color:red;'></span>Problème d'upload de l'image.</p>");
                        return Redirect::back();
                    }
                    
                    $destinationPath = 'uploads/';
                    $filename = str_random(15) . '.' . $fileExtension;

                    $image = new BackgroundImage;
                    $image->name = $filename;
                    $image->created_at = Carbon::now();
                    $image->save();
                    $thumbPath = $destinationPath . '/_min/' . $filename;
                    HelperImage::thumb($fileThumb, 70, 70, $thumbPath);

                    $upload_success = $file->move($destinationPath, $filename);

                    if ($upload_success) {
                        $bg = BackgroundImage::findOrFail($image->id);
                        $section->backgroundImages()->detach();
                        $section->backgroundImages()->attach($image->id);
                    }else{
                        Session::flash('messageUpdateSection', "<p class='error bg-danger'><span class='glyphicon glyphicon-remove' style='color:red;'></span>Problème d'upload de l'image.</p>");
                        return Redirect::back();
                    }
                }
            });
            Session::flash('messageActionSections', '<p class="success bg-success"><span class="glyphicon glyphicon-ok" style="color:green;"></span>Section modifiée.</p>');
            return Redirect::to('/admin/' . $posterId . '/dashSection');
        } catch (ModelNotFoundException $e) {
            Session::flash('messageUpdateSection', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove" style="color:red;"></span>Erreur lors de la requête</p>');
            return Redirect::back();
        }
    }
    
    public function attachBg($sectionId, $id){
        try{
            $section = Section::findOrFail($sectionId);
            $bg = BackgroundImage::findOrFail($id);
            
            $section->backgroundImages()->detach();
            $section->backgroundImages()->attach($bg->id);
            Session::flash('messageUpadteSection', '<p class="success bg-success"><span class="glyphicon glyphicon-ok" style="color:green;"></span>Background modifié.</p>');
            return Redirect::back();
        } catch (ModelNotFoundException $ex) {
            Session::flash('messageUpdateSection', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove" style="color:red;"></span>Erreur lors de la requête</p>');
            return Redirect::back();
        }
    }

    /**
     * DELETE : section $id
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id) {
        try {
            DB::transaction(function() use($id) {
                Section::findOrFail($id);
                Section::destroy($id);
            });
            Session::flash('messageTrash', '<p class="success bg-success"><span class="glyphicon glyphicon-ok" style="color:green;"></span>Section supprimée avec succès.</p>');
            return Redirect::back();
        } catch (ModelNotFoundException $e) {
            Session::flash('messageTrash', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove" style="color:red;"></span>Erreur lors de la suppression.</p>');
            return Redirect::back();
        }
    }

    //STATUS
    // modification de statut individuelle
    /**
     * UPDATE : section status
     * 
     * @param type $id
     * @return type
     */
    public function status($id) {
        try {
            $section = Section::findOrFail($id);
            if ($section->status == 'publish') {
                $section->status = 'unpublish';
            } else if ($section->status == 'unpublish') {
                $section->status = 'publish';
            } else if ($section->status == 'trash') {
                $section->status = 'unpublish';
            }
            if ($section->save()) {
                Session::flash('messageActionSections', '<p class="success bg-success"><span class="glyphicon glyphicon-ok" style="color:green;"></span>Modification de statut réussie.</p>');
            } else {
                Session::flash('messageActionSections', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove" style="color:red;"></span>Echec du changement de statut.</p>');
            }
            return Redirect::back();
        } catch (ModelNotFoundException $e) {
            Session::flash('messageActionSections', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove" style="color:red;"></span>Erreur de sélection.</p>');
            return Redirect::back();
        }
    }

    /**
     * UPDATE : sections status
     * 
     * @return type
     */
    public function action() {
        $status = Input::get('action');
        DB::transaction(function() use ($status) {
            try {
                foreach (Input::all() as $k => $v) {
                    if ($v == 'on') {
                        $test = explode('_', $k);
                        if (($test[0] == 'section')) {
                            $section = Section::findOrFail($test[1]);
                            $section->status = $status;
                            $section->save();
                        }
                    }
                }
            } catch (ModelNotFoundException $e) {
                Session::flash('messageActionSections', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove" style="color:red;"></span>Echec des modifications.</p>');
                return Redirect::back();
            }
        });
        Session::flash('messageActionSections', '<p class="success bg-success"><span class="glyphicon glyphicon-ok" style="color:green;"></span>Modification(s) réussie(s).</p>');
        return Redirect::back();
    }
}
