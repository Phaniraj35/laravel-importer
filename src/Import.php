<?php namespace Ladybird\import;

use League\Csv\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Ladybird\import\Dbseed;
use Ladybird\import\Jobs\DoImport;
use Validator;

class Import implements ImportInterface
{
    protected $model;

    protected $rules;
    
    public static $saved_file = "file.json";

    public function __construct(Model $model)
    {
        $this->model = $model;
        
    }

    public function getDbCols()
    {
        if(empty($this->model->getHidden())) {
            return array_unique($this->model->getFillable());
        } else {
            return(array_unique(array_merge($this->model->getFillable(),$this->model->getHidden())));
        }

        
    }

    public function parseImport(Request $request)
    {
        $returnArray = array();

        $request->validate([
            'csv_file' => "required|file|mimes:csv,txt"
        ]);
        
        $path = $request->file('csv_file')->getRealPath();
 
        
        $data = array_map('str_getcsv',file($path));
        
        if($request->has('header')) {
            array_shift($data);
        }

        
        

        Storage::disk('local')->put(static::$saved_file,json_encode($data));

        

        //$csv_data = $data[0];
        $db_cols = $this->getDbCols();

        $returnArray['csv_sample_row'] = $data[0];
        $returnArray['database_columns'] = $db_cols;



        return json_encode($returnArray);
    }


    public function processImport(Request $request)
    {
        

        $tempArray = array();
        $final_json_array = array();
        $temp_file_contents = Storage::disk('local')->get(static::$saved_file);
        $temp_file_contents = json_decode($temp_file_contents,true);
        

        

        $headers = array();

        foreach($temp_file_contents as $row) {

            foreach($request->fields as $key => $value) {

                $tempArray[$value] = $row[$key];

                
                

            } //foreach inner

            ksort($tempArray);

            if(empty($headers))
                $headers = array_keys($tempArray);

            array_push($final_json_array,$tempArray);
            $tempArray = array();

        } //foreach

        //dd($final_json_array);

        $db_cols = $this->getDbCols();
    

        // $validityCheckRows = $final_json_array[0];

        

        // $validator = Validator::make($validityCheckRows,$this->model->rules);

        // if($validator->fails()) {
        //     return $validator->messages()->getMessages();
        // } else {
            
            
        //     foreach(array_chunk($final_json_array,1000) as $t) {
        //         $this->model::insert($t);
        //     }
    
        //     return ["message" => "Successfully Inserted"];

        // }
       // $i=0;
        foreach(array_chunk($final_json_array,1000) as $t) {
            
            for($i=0;$i<1000;$i++) {

                $validator = Validator::make($t[$i],$this->model->rules);

                if($validator->fails()) 
                    return $validator->messages()->getMessages();
                else {
                    $this->model::insert($t[$i]);
                    
                }

            } //for
            
        } //foreach

        
        return ["message" => "Successfully Inserted"];
        
    }


   
}

