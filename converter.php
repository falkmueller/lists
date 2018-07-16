<?php

error_reporting(E_ALL);
ini_set("display_errors", 1);

if(empty($argv) || count($argv) < 5){
    echo "use Params: '[input_format] [input_file] [output_format] [output_file]'"; exit;
}
elseif (empty ($argv[1]) || empty ($argv[2]) || empty ($argv[3]) || empty ($argv[4])){
    echo "use Params: '[input_format] [input_file] [output_format] [output_file]'"; exit;
}
elseif (!file_exists($argv[2])){
     echo "File '$argv[2]' not found."; exit;
}
elseif (!in_array($argv[1], ["csv", "php", "json"]) || !in_array($argv[3], ["csv", "php", "json"])){
    echo "Only available for 'csv', 'json', 'php'"; exit;
}
elseif((file_exists($argv[4]) && !is_writable($argv[4])) || (!file_exists($argv[4]) && file_put_contents($argv[4], "") === false)){
    echo "File '$argv[4]' is not writable."; exit;
}

$csv_delimiter = ";";

if(!empty($argv[5])){
   $csv_delimiter = $argv[5]; 
}

$data = [];
switch ($argv[1]) {
    case "json":
        $data = readJson($argv[2]);
        break;
    case "csv":
       $data = readCsv($argv[2], $csv_delimiter);
        break;
    case "php":
       $data = readPhp($argv[2]);
        break;
    default:
       echo "unknown input format";
        exit;
}

switch ($argv[3]) {
    case "json":
        $data = toJson($argv[4], $data);
        break;
    case "csv":
       $data = toCsv($argv[4], $data, $csv_delimiter);
        break;
    case "php":
       $data = toPhp($argv[4], $data);
        break;
    default:
       echo "unknown output format";
        exit;
}


/******************************************************************************
 * Contert Functions
 */

function readJson($file){
    return json_decode(file_get_contents($file), true);
}

function readCsv($file, $delimiter = ";"){
    $data = array();
    $csvData = fopen($file, "r");
            while(($csvVal = fgetcsv($csvData, 0, $delimiter)) !== FALSE){
                $iso = $csvVal[0];
                
                $iso = _convertString($iso);
                

		if(count($csvVal) == 2){
			$value = _convertString($csvVal[1]);
		}
		else if (count($csvVal) > 2){
			$value = [];
			foreach($csvVal as $idx => $v){
				if($idx == 0){continue;}
				$value[] = _convertString($v);
			}	
		}
		else {
			$value = "";	
		}

                $data[strtoupper($iso)] = $value;
            }

            return $data;
}

function readPhp($file){
    return require $file;
}

function _convertString($string){
    if(!function_exists("mb_detect_encoding")){
        return $string;
    }
    
    if(!is_string($string)){
        return $string; 
    }
    
    if(mb_detect_encoding($string, 'UTF-8', true) != 'UTF-8'){
        $string = mb_convert_encoding(trim($string), "UTF-8");
    }
    
    return $string; 
}

/******************************************************************************
 * Output Functions
 */

function toJson($file, $array){
    $content = json_encode($array);
    file_put_contents($file, $content);
}

function toCsv($file, $array, $delimiter = ";" ){
    $fp = fopen($file, 'w');

    foreach ($array as $key => $value) {
        $row = [];
        if(is_array($value)){
            $row = array_values($value);
            array_unshift($row, $key);
        } else {
            $row[] = $key;
            $row[] = $value;
        }
        fputcsv($fp, $row, $delimiter);
    }

    fclose($fp);
}

function toPhp($file, $array){
    $content = "<?php return ".var_export($array, true).";";
    file_put_contents($file, $content);
}
