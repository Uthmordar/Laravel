<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
//Importation du namespace servant à l'upload de nos classes
use Illuminate\Filesystem\Filesystem as File;

class MigrationGenerator extends Command {

    /**
     * $php artisan generate:migration create_table_newsletters --fields='title:string' 
     * The console command name.
     *
     * @var string
     */
    protected $name = 'generate:migration';

    /**
     * Instance de la classe File (effectuée lors du construct)
     * 
     * @var type 
     */
    protected $file = null;

    /**
     * The console command description.
     *
     * @var string
     */
    
    protected $namefields = [];
    
    protected $description = 'Command description.';

    /**
     * Blueprint de notre fichier de classe
     *  {{name}} : nom de la classe
     *  {{tablename}} : nom de la table 
     *  {{methode}} : ensemble des fields
     * 
     * @var type 
     */
    protected $migration = 
"<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class {{name}} extends Migration {
    public function up(){
        Schema::create({{tablename}}, function(\$table){
               \$table->increments('id')->unsigned;
               {{methode}}
               \$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(){
        Schema::dropifExists({{tablename}});
    }
}
?>";
    
    protected $seed =
"<?php

class {{name}} extends Seeder {
    public function run(){
        DB::table({{tablename}})->delete();
        DB::unprepared('ALTER TABLE articles AUTO_INCREMENT=1');
        DB::table({{tablename}})->insert(
            [
                {{array}}
            ]
        );
    }
}
?>";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        $this->file = new File;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire() {
        try {
            // arguments name obligatoire de la commande
            $name = $this->argument('name');

            $glob = glob(app_path() . '/database/migrations/*' . $name . '.php');
            
            if ($glob === FALSE || !empty($glob)) {
                throw new RunTimeException($name . ' est deja present dans le dossier de migrations.');
            }
            // vérification du name de la commande
            $reg = '/^create_table_(\w+)$/';
            if (preg_match($reg, $name) != TRUE) {
                throw new RuntimeException('Commande invalide : create_table_yourclass.');
            }

            // génération du nom standard de fichier
            $filename = date('Y_m_d_His') . '_' . $name . '.php';

            $tablename = substr($name, strrpos($name, 'create_table_') + 13);

            //Remplissage du Blueprint du fichier de class
            $stub = str_replace('{{name}}', \Str::studly($name), $this->migration);

            $stub = str_replace('{{tablename}}', "'" . $tablename . "'", $stub);

            //traitement des options de la commande
            $fields = $this->getFields($this->option('fields'));
            if ($fields == []) {
                throw new RuntimeException('Pas de champs a placer dans la classe, creation annulee.');
            }
            $methode = implode(';', $fields) . ';';
            $stub = str_replace('{{methode}}', $methode, $stub);

            // calcul du path du folders de migration
            $path = app_path() . '/database/migrations/' . $filename;

            // vérification de contenu du file
            if ($this->file->put($path, $stub) == 0) {
                throw new RuntimeException('Fichier vide, creation annulee.');
            }

            // vérification de la transcription du file
            if ($this->file->put($path, $stub) == FALSE) {
                throw new RuntimeException('Erreur dans le processus de creation.');
            }

            // composer dump-autoload => mise à jour de l'Autoloader
            $this->call('dump-autoload');

            if(!($this->confirm('Voulez-vous creer le fichier de seed ? [yes|no]'))){
                //message de réussite
                return $this->info("La migration $filename a bien ete cree et le dump-autoload effectue. Pas de seed cree.");
            }
            
            $nbentree = intval($this->ask('Nombre d\'element a entrer ? (nombre plz)'));
            
            if(!is_int($nbentree)){
                $nbentree = 0;
            }
            $seedname= \Str::studly($tablename) . 'Seeder';
            $seed = str_replace('{{name}}', $seedname, $this->seed);
            $seed = str_replace('{{tablename}}', "'" . $tablename . "'", $seed);
            
            $content = '';
            $elementseed = '[';
            foreach($this->namefields as $k){
                $elementseed .= $k . " => '',";
            }
            $elementseed = trim($elementseed, ',');
            $elementseed .= "],\n\t\t\t";
            
            var_dump($nbentree);
            for($i=0; $i<$nbentree; $i++){
                $content .= $elementseed;
            }
            
            $seed = str_replace('{{array}}', $content, $seed);
            
            $pathSeed = app_path() . '/database/seeds/' . $seedname .'.php';
            
            $this->file = new File;
            // vérification de contenu du file
            if ($this->file->put($pathSeed, $seed) == 0) {
                throw new RuntimeException("La migration $filename a bien ete cree et le dump-autoload effectue. Fichier de seed vide, creation annulee.");
            }

            // vérification de la transcription du file
            if ($this->file->put($pathSeed, $seed) == FALSE) {
                throw new RuntimeException("La migration $filename a bien ete cree et le dump-autoload effectue. Erreur dans le processus de creation du seed.");
            }
            
            $this->call('dump-autoload');
            
            return $this->info("La migration $filename a bien ete cree et le dump-autoload effectue. Le seed $seedname a ete cree.");
            
        } catch (\RuntimeException $e) {
            $message = 'Exception : ' . $e->getMessage();
            //message d'erreur
            return $this->error($message);
        }
    }

    /**
     * Get the console command arguments.
     * 
     * arguments : name
     * 
     * @return array
     */
    protected function getArguments() {
        return array(
            array('name', InputArgument::REQUIRED, 'nom du champ'),
        );
    }

    /**
     * Get the console command options.
     *
     * arguments : fields
     * 
     * @return array
     */
    protected function getOptions() {
        return array(
            array('fields', null, InputOption::VALUE_OPTIONAL, 'create table method', null),
        );
    }

    /**
     * getFields($str)
     * 
     * @param type $str
     * 
     * return type Array
     */
    protected function getFields($str) {
        $str = trim($str, ';');
        $str = trim($str, "'");
        $str = trim($str, ';');
        $explode = explode(';', $str);
        $arrayField = [];

        $field = function($str) {
            try {
                $args = [
                    'type' => '',
                    'opts' => []
                ];

                $reg = '/^(?P<opts1>\w+):(?P<type>\w+)(\[(?P<opts2>\w+)\])*$/';

                // $reg='/^(?P<type>\w+)(?P<value>\([\w\'\"\, ]+\))(:(?P<options>.+))*$/';
                $check = preg_match($reg, $str, $matches);

                if ($check != true) {
                    throw new RuntimeException('Erreur dans l\'ecriture des options de la commande : ' . $str);
                }

                $args['type'] = $matches['type'];
                $args['opts'][] = "'" . $matches['opts1'] . "'";

                (isset($matches['opts2'])) ? $args['opts'][] = $matches['opts2'] : '';

                return $args;

                /* $args=[];
                  $reg='/^(?P<type>\w+)(?P<value>\([\w\'\"\, ]+\))(:(?P<options>.+))*$/';

                  $check = preg_match($reg, $str, $matches);
                  if ($check != true) {
                  throw new RuntimeException('Erreur dans l\'ecriture des options de la commande : ' . $str);
                  }

                  $arg['type']=$matches['type'];
                  $arg['value']=$matches['value'];
                  (!isset($matches['options']))? $arg['options']=$matches['options']:'';

                  return $args; */
            } catch (\RuntimeException $e) {
                return $this->error($e->getMessage());
            }
        };

        foreach ($explode as $k) {
            $data = $field($k);
            $this->namefields[]=$data['opts'][0];
            ($data != FALSE) ? ($arrayField[] = '$table->' . $data['type'] . '(' . implode($data['opts'], ', ') . ')') : '';

            /* if(isset($data['options']){
              if(!preg_match('/\.+\(\.*\)', $matches['options'])){
              $data['options']=$data['options'] . '()';
             *   }
             *   $arrayField[] = '$table->' . $data['type'] . $data['value'] . '->' . $data['options'];
              } */
        }

        return $arrayField;
    }

}
