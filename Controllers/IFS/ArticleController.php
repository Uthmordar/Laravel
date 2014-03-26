<?php

class ArticleController extends \BaseController {

     /**
     * VIEW : all articles pour la section $sectionId
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index($sectionId) {
        try{
            $section = Section::findOrFail($sectionId);
            $poster = $section->poster;
            $articlesFirstLevel = $section->articles()->firstLevel()->get();
            $articlesSecondLevel = $section->articles()->secondLevel()->get();
            return View::make('admin.dashArticle', array('articlesFirstLevel'=>$articlesFirstLevel, 'articlesSecondLevel'=>$articlesSecondLevel, 'section' => $section, 'poster' => $poster, 'title' => 'Articles section : '. $section->title));
        }catch(ModelNotFoundException $e){
            return Redirect::back();
        }
    }

    /**
     * VIEW : article
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($sectionId, $id) {
        try {
            $section = Section::findOrFail($sectionId);
            $poster = $section->poster;
            $article = Article::findOrFail($id);
            $animations = $article->animations;
            $animArray = [];
            foreach ($animations as $animation) {
                $animArray[] = $animation->name;
            }
            return View::make('admin.editArticle', array('title' => 'éditer un article', 'section' => $section, 'article' => $article, 'animations'=>$animArray, 'poster'=>$poster));
        } catch (ModelNotFoundException $e) {
            Session::flash('messageDashArticle', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove" style="color:red;"></span>Erreur dans l\'url</p>');
            return Redirect::back();
        }
    }

    /**
     * UPDATE : article
     * 
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($sectionId, $id) {
        //var_dump(Input::all());die();
        $rules = array(
        );

        $validator = Validator::make(Input::all(), $rules);
        if ($validator->fails()) {
            Session::flash('messageUpdateArticle', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove" style="color:red;"></span>Formulaire incomplet</p>');
            return Redirect::back()->withInput()->withErrors($validator);
        }
        try {
            $article = Article::findOrFail($id);
            if(Input::get('title'))
                 $article->title = Input::get('title');
            
            if(Input::get('title_color'))
                $article->title_color = Input::get('title_color');

            if(Input::get('contenu')){
                $article->content = Input::get('contenu');
                $article->extract = substr(strip_tags(Input::get('contenu'), '<p>'), 0, 200);
            }

            if(Input::get('bg_color_box')){
                $article->bg = 1;
                (Input::get('bg_color'))? $article->bg_color = Input::get('bg_color') : '';
            }else{
                $article->bg = 0;
            }

            if(Input::get('border_box')){
                $article->border = 1;
                (Input::get('border_color'))? $article->border_color = Input::get('border_color'): '';
            }else{
                $article->border = 0;
            }

            if(Input::get('animation') && Input::get('anim_box')){
                $article->animations()->detach();
                $article->animations()->attach(Input::get('animation'));
            }else{
                $article->animations()->detach();
            }
            $article->updated_at = Carbon::now();
            $article->save();

            Session::flash('messageDashArticle', '<p class="success bg-success"><span class="glyphicon glyphicon-ok" style="color:green;"></span>Article édité avec succès.</p>');
            return Redirect::to('/admin/'.$sectionId.'/dashArticle');

        } catch (ModelNotFoundException $e) {
            Session::flash('messageUpdateArticle', '<p class="error bg-danger"><span class="glyphicon glyphicon-remove" style="color:red;"></span>Erreur dans l\'url de la requête</p>');
            return Redirect::back();
        }
    }
}