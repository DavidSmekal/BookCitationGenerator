<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use Exception;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use App\Template;
use App\bookcollection;



class TemplateController extends Controller
{
    public $template;
    function _construct() {
        $this->template = "";
    }

    function setGlobalVar($value) {
        session()->put('template', $value);
    }

    function getGlobalVar(){
        $array = session()->get($this->template);
        return $array['template'];
    }

    public function create()
    {
        return view('/templates/create');
    }

    // creates and adds a new template into the Database
    // COMPLETE
    public function store()
    {
        DB::table('templates')->insert(
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
        $template = DB::table('templates')->select('*')->where('tname', '=', $tname)->get();

        // show the edit form and pass the template
        return view('/templates/edit')->with('template', $template);
    }

    //  updates a specific template in the database
    // COMPLETE
    public function update($tname)
    {
        DB::table('templates')
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
        DB::table('templates')->where('tname','=', $tname)->delete();

        Session::flash('message', 'Successfully deleted the template!');
        return Redirect::to('/templates');

    }

    // shows a specific template
    // COMPLETE
    public function show($tname)
    {
        $template = DB::table('templates')->select('*')->where('tname', '=', $tname)->get();

        return view('/templates/show')->with('template', $template);
    }

      public function preview($tname)
    {
        $tem = DB::table('templates')->select('*')->where('tname', '=', $tname)->get();
        $temp = $tem[0];

        return view('/templates/tempreview')->with('temp', $temp);
    }

    public function export(Request $request){

            $template = Template::find($request->tname);
         $bookcollection = bookcollection::find($request->bookcol);
            $template->bookcollections()->attach($request->bookcol,[ 'templates_tname'=>$request->tname]);
          
         
         //$template->bookcollections()->sync($request->bookcol,false);

         return view('/newhome');
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
    public function applyTemplate($tname)
    {   
        
        
        $collections = bookcollection::all();
       
        $conten = DB::table('templates')->select('*')->where('tname', '=', $tname)->get()->toArray();
        

        $content = $conten[0];
        
       
    

        return view('/templates/apply',compact('template','collections','content'));
    }

    public function split_data_tags($string){
        $pattern = '(#data#|#/data#)';
        $tokenizedTemplate = preg_split($pattern, $string);
        return $tokenizedTemplate;
    }

    public function split_style_tags($tokenizedArray){
        $fullyTokenized = array();
        foreach($tokenizedArray as $string){
            $pattern = "#(<\w+>)([^<]*)(<\/\w+>)#";
            preg_match($pattern, $string, $matches);
            if(count($matches) > 0){
                for($i=0; $i<count($matches); $i++){
                    if($i != 0){
                        echo "Match: ".$matches[$i]."<br>";
                        array_push($fullyTokenized, $matches[$i]);
                    }
                }
            } else {
                array_push($fullyTokenized, $string);
            }
        }
        return $fullyTokenized;
    }

    public function ComputeLPSArray($pat, $m, &$lps){
        $len = 0;
        $i = 1;

        $lps[0] = 0;

        while ($i < $m)
        {
            if ($pat[$i] == $pat[$len])
            {
                $len++;
                $lps[$i] = $len;
                $i++;
            }
            else
            {
                if ($len != 0)
                {
                    $len = $lps[$len - 1];
                }
                else
                {
                    $lps[$i] = 0;
                    $i++;
                }
            }
        }
    }

    public function SearchString($str, $pat){
        $M = strlen($pat);
        $N = strlen($str);
        $i = 0;
        $j = 0;
        $lps = array();

        $this->ComputeLPSArray($pat, $M, $lps);

        while ($i < $N){
            if ($pat[$j] == $str[$i]){
                $j++;
                $i++;
            }

            if ($j == $M){
                $updatedString = substr($str,0,($i - $j))."#data#".$pat."#/data#";
                $restOfTheString = substr($str,$i);		// remaining part of the template string

                $i = strlen($updatedString); // update the position of current index in the search
                $this->setGlobalVar($updatedString.$restOfTheString);		// glue together the updated string and the remaining part

                $str = $this->getGlobalVar();		// update str with the updated template string
                $N = strlen($str);						// update length on str
                $j = $lps[$j - 1];
            } else if ($i < $N && $pat[$j] != $str[$i]){
                if ($j != 0)
                    $j = $lps[$j - 1];
                else
                    $i = $i + 1;
            }
        }
    }

    public function parseTemplate($templateContent, $tagsArray){
        $this->setGlobalVar($templateContent);
        // first parse
        foreach($tagsArray as $tag){
            $this->SearchString($this->getGlobalVar(), $tag);
        }

        // second parse
        $tokenizedArray = $this->split_data_tags($this->getGlobalVar());

        // third parse
        $fullyParsed = $this->split_style_tags($tokenizedArray);
        return $fullyParsed;
    }

    public function generateCitations(Request $request){
        $template = Template::find($request->tname);
        $template->bookcollections()->attach($request->bookcol,[ 'templates_tname'=>$request->tname]);

        $tname = $request->tname;
        $collections = $request->bookcol;

        $tags = array(0 => "title",1 => "codeNum",2 => "isbn",3 => "authorFirstName",4 => "authorLastName",5 => "price",6 => "translatorFirstName",7 => "translatorLastName",
            8 => "edition",9 => "illustratorFirstName",10 => "illustratorLastName",11 => "printdate",12 => "placeofpublication",13 => "publisher",14 => "copyright",
            15 => "legaldeposit",16 => "issn",17 => "partnercompanies",18 => "Printer",19 => "Printline",20 => "Printrun",21 => "foreword",22 => "Introduction",
            23 => "Preface",24 => "Frontmaterial",25 => "Artworkcredits",26 => "EditingCredits",27 => "layoutCredits",28 => "MapCredits",29 => "PhotoCredits",
            30 => "ProductionCredits",31 => "TranslationCredits",32 => "CompanionVolumes",33 => "series",34 => "SeriesEditor",35 => "Issue",36 => "CopiesExamined",
            37 => "Note",38 => "CopyNumber",39 => "Grade",40 => "Description",41 => "AssociationCopy", 42 => "TitleVariant", 43 => "AuthorLastNameVariant",
            44 => "AuthorFirstNameVariant", 45 => "IllustratorFirstNameVariant", 46 => "IllustratorLastNameVariant", 47 => "TranslatorFirstNameVariant",
            48 => "TranslatorLastNameVariant",49 => "PublisherVariant", 50 => "coverDesignCredit", 51 => "designCredit");

        /* get the content of the template based on a temaplateName */
        $templateStr = DB::table('templates')->select('content')->where('tname', '=', $tname)->get()->toArray();
        $templateStr = $templateStr[0];
        $templateString = $templateStr->content;
        /* parse the template and get an array of template elements broken down into positions */
        $parsedTemplateArray = $this->parseTemplate($templateString, $tags);

        /*get all books associated with collection */
        $allBooks = array();
        foreach($collections as $id){
            $books = bookcollection::find($id)->books;
            /* get all the books in each bookCollection */
            foreach($books as $book){
                array_push($allBooks, $book);
            }
        }

        $citationsArray = array();
        foreach($allBooks as $b){
            $tempJSON = DB::table('books')->select('bookAttr')->where('id', '=', $b->id)->get()->toArray();
            $tempJSON = $tempJSON[0]->bookAttr;
            $JSON = json_decode($tempJSON);
            $citation = "";
            foreach($parsedTemplateArray as $attr){
                if(in_array($attr, $tags)){
                    try{
                        $attrVal = $JSON->$attr;
                        $citation = $citation.$attrVal;
                    }catch(Exception $e){
                        $citation = $citation."N/A";
                    }
                } else {
                    $citation = $citation.$attr;
                }
            }
            array_push($citationsArray, $citation);
        }
        return view('/exportThis',compact('citationsArray'));
    }
}