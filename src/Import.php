<?php namespace Ladybird\import;

use League\Csv\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Import implements ImportInterface
{
    protected $model;
    
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
        //Storage::disk('local')->delete(static::$saved_file);
        //dd($temp_file_contents);
        //dd($request->fields);

        



        foreach($temp_file_contents as $row) {

            foreach($request->fields as $key => $value) {

                $tempArray[$value] = $row[$key];

            } //foreach inner

            ksort($tempArray);
            array_push($final_json_array,$tempArray);
            $tempArray = array();

        } //foreach

        
        $csv = Writer::createFromString('');

        $csv->insertAll($final_json_array);

        Storage::disk('local')->put("file.csv",$csv->getContent());

        $query = sprintf("LOAD DATA LOCAL INFILE '%s' INTO TABLE '%s' LINES TERMINATED BY '\\n' FIELDS TERMINATED BY ','", addslashes(storage_path("app/file.csv")),$this->model->getTable());

        //$query = "LOAD DATA LOCAL INFILE '".addslashes(storage_path("app/file.csv"))."' INTO TABLE ".$this->model->getTable()." LINES TERMINATED BY '\\n' FIELDS TERMINATED BY ','";
        
        dd($query);
        //dd(DB::connection()->getpdo()->exec($query));

        return DB::connection()->getpdo()->exec($query);

    }


   
}

