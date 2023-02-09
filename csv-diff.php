<?php
/*Last updated: 2023-02-05
Developer: Alok Yadav(info@alokyadav.in)
*/
define('PRINT_STYLE_SUMMARY', 0);
define('PRINT_STYLE_JSON', 1);

class csv_diff {
	private $csv1;
	private $csv2;
	private $primary_key;
	private $style;
	private $ret;
	private $columns_added;
	private $columns_removed;
	private $rows_added;
	private $rows_removed;
	private $rows_changed;
	private $csv1_rows;
	private $csv2_rows;

	public $result_json;
	public $result_summary;
	public $version = "1.0.1";
	public $error;

	public function __construct($csv1, $csv2, $primary_key) {
		$this->csv1 = sprintf("%s", $csv1);
		$this->csv2 = sprintf("%s", $csv2);
		$this->primary_key = $primary_key;
		$this->columns_added = array();
		$this->columns_removed = array();
		$this->rows_added = array();
		$this->rows_removed = array();
		$this->rows_changed = array();
		$this->csv1_rows = [];
		$this->csv2_rows = [];

	}

	private function get_header($csv_file){
		$header = NULL;
		try{
			if(!file_exists($csv_file)){
				$this->error = '['.$csv_file.']File not found.';
    				throw new Exception('['.$csv_file.']File not found.');
  			}
			$fp = fopen($csv_file, "r");
			if(!$fp){
				$this->error = '['.$csv_file.']File open failed.';
    				throw new Exception('['.$csv_file.']File open failed.');
  			}  
			$header = fgetcsv($fp);
			fclose($fp);
		}catch(Exception $e){
			$this->error = $e;
  			throw new Exception('['.$csv_file.']Unable to get header.');
		} 
		return $header;
	}

	private function get_columns_added($header1, $header2){
		for($index = 0; $index < count($header2); $index++){
			if(in_array($header2[$index], $header1)){
				continue;
			}else{
				array_push($this->columns_added, $header2[$index]);
			}
		}
		return $this->columns_added;
	}

	private function get_columns_removed($header1, $header2){
		for($index = 0; $index < count($header1); $index++){
			if(in_array($header1[$index], $header2)){
				continue;
			}else{
				array_push($this->columns_removed, $header1[$index]);
			}
		}
		return $this->columns_removed;
	}

	private function get_rows_added($primary_key_index, $csv1_row_count, $csv2_row_count){
		for($csv2_row_index = 0; $csv2_row_index < $csv2_row_count; $csv2_row_index++){
			$key_value = $this->csv2_rows[$csv2_row_index][$primary_key_index];
			$row_found = 0;
			for($row_index = 0; $row_index < $csv1_row_count; $row_index++){
				if(strcmp($this->csv1_rows[$row_index][$primary_key_index], $key_value) == 0){
					$row_found = 1;
					break;
				}
			}
			if(!$row_found){
				array_push($this->rows_added, $this->csv2_rows[$csv2_row_index]);
				continue;
			}
		}
		return $this->rows_added;
	}

	private function get_rows_removed($primary_key_index, $csv1_row_count, $csv2_row_count){
		for($row_index = 0; $row_index < $csv1_row_count; $row_index++){
			$key_value = $this->csv1_rows[$row_index][$primary_key_index];
			$row_found = 0;
			for($csv2_row_index = 0; $csv2_row_index < $csv2_row_count; $csv2_row_index++){
				if(strcmp($this->csv2_rows[$csv2_row_index][$primary_key_index], $key_value) == 0){
					$row_found = 1;
					break;
				}
			}
			if(!$row_found){
				array_push($this->rows_removed, $this->csv1_rows[$row_index]);
				continue;
			}
		}
		return $this->rows_removed;
	}

	private function get_rows_changed($primary_key_index, $header1, $header2, $csv1_row_count, $csv2_row_count, $csv1_col_count, $csv2_col_count, $fill_rows_removed){
		for($row_index = 0; $row_index < $csv1_row_count; $row_index++){
			$key_value = $this->csv1_rows[$row_index][$primary_key_index];
			$row_found = 0;
			for($csv2_row_index = 0; $csv2_row_index < $csv2_row_count; $csv2_row_index++){
				if(strcmp($this->csv2_rows[$csv2_row_index][$primary_key_index], $key_value) == 0){
					$row_found = 1;
					break;
				}
			}
			if(!$row_found){
				if($fill_rows_removed){
					array_push($this->rows_removed, $this->csv1_rows[$row_index]);
				}
				continue;
			}
			for($col_index = 0; $col_index < $csv1_col_count; $col_index++){
				if(in_array($header1[$col_index], $header2)){
					$csv2_col_index = array_search($header1[$col_index], $header2);
					if(strcmp($this->csv1_rows[$row_index][$col_index], $this->csv2_rows[$csv2_row_index][$csv2_col_index]) != 0){
						$f_change = array(
								"from"  => $this->csv1_rows[$row_index][$col_index],
								"to"    => $this->csv2_rows[$csv2_row_index][$csv2_col_index]
								);
						$d_change = array(
								$header1[$col_index] => $f_change
								);
						$data = array(
								"fields"=> $d_change,
								"key"  => array($this->csv1_rows[$row_index][$primary_key_index])
							     );
						array_push($this->rows_changed, $data);
					}
				}
			}
		}
		return $this->rows_changed;
	}

	private function get_csv_array_content($csv_file, &$csv_rows){
		$row_index = 0;
		try{
			if(!file_exists($csv_file)){
				$this->error = '['.$csv_file.']File not found.';
    				throw new Exception('['.$csv_file.']File not found.');
  			}
			$fp = fopen($csv_file, "r");
			if(!$fp){
				$this->error = '['.$csv_file.']File open failed.';
    				throw new Exception('['.$csv_file.']File open failed.');
  			}  
			while (($data = fgetcsv($fp)) !== FALSE){
				if($row_index == 0){
					/*Skip Header*/
					$row_index++; 
					continue;
				}
				$csv_rows[] = $data;
				$row_index++;
			}
			fclose($fp);
		}catch(Exception $e){
			$this->error = $e;
  			throw new Exception('['.$csv_file.']Unable to load csv content');
		} 
		return $csv_rows;
	}

	private function get_result_summary($csv1_row_count, $csv2_row_count, $csv_col_count){
		$summary = count($this->rows_removed)." rows removed (".number_format((float)(count($this->rows_removed)*100/$csv1_row_count), 2, '.', '')."%)\n";
		$summary .= count($this->rows_added)." rows added (".number_format((float)(count($this->rows_added)*100/$csv1_row_count), 2, '.', '')."%)\n";
		$summary .= count($this->rows_changed)." rows changed (".number_format((float)(count($this->rows_changed)*100/$csv1_row_count), 2, '.', '')."%)\n";
		$summary .= count($this->columns_added)." columns added (".number_format((float)(count($this->columns_added)*100/$csv_col_count), 2, '.', '')."%)\n";
		$summary .= count($this->columns_removed)." columns removed (".number_format((float)(count($this->columns_removed)*100/$csv_col_count), 2, '.', '')."%)\n";
		return $summary;
	}

	private function get_result_json(){
		$arr = array(
				"author"            => "Alok Yadav",
				"version"           => $this->version,
				"timestamp"         => time(),
				"_index"            => array($this->primary_key),
				"rows_added"        => $this->rows_added,
				"rows_changed"      => $this->rows_changed,
				"rows_removed"      => $this->rows_removed,
				"columns_added"     => $this->columns_added,
				"columns_removed"   => $this->columns_removed
			    );
		return json_encode($arr, JSON_PRETTY_PRINT);
	}

	public function get_diff($style){
		$this->style = $style;
		/*Get the header row from the csv1*/
		$header1 = $this->get_header($this->csv1);

		/*Get the header row from the csv2*/
		$header2 = $this->get_header($this->csv2);

		$csv1_col_count = count($header1);
		$csv2_col_count = count($header2);
		$min_col_count = $csv1_col_count>$csv2_col_count?$csv2_col_count:$csv1_col_count;

		/*Load csv1 into an array and skip header*/
		$this->get_csv_array_content($this->csv1, $this->csv1_rows);

		/*Load csv2 into an array and skip header*/
		$this->get_csv_array_content($this->csv2, $this->csv2_rows);

		$csv1_row_count = count($this->csv1_rows);
		$csv2_row_count = count($this->csv2_rows);
		$primary_key_index = array_search($this->primary_key, $header1);

		/*Get columns added in csv2*/
		$this->get_columns_added($header1, $header2);
		/*Get columns removed in csv2*/
		$this->get_columns_removed($header1, $header2);

		/*Get rows removed in csv2*/
		/*
		Disabled this get removed rows call as get_rows_changed can get both when fill_rows_removed parameter passed as true.
		$this->get_rows_removed($primary_key_index, $csv1_row_count, $csv2_row_count);
		*/
		/*Get rows changed in csv2*/
		$this->get_rows_changed($primary_key_index, $header1, $header2, $csv1_row_count, $csv2_row_count, $csv1_col_count, $csv2_col_count, true);
		/*Get rows added in csv2*/
		$this->get_rows_added($primary_key_index, $csv1_row_count, $csv2_row_count);
		
		$this->result_summary = $this->get_result_summary($csv1_row_count, $csv2_row_count, $csv1_col_count);
		$this->result_json = $this->get_result_json();

		if($this->style == PRINT_STYLE_JSON){
			$this->ret = $this->result_json;
		} else {
			$this->ret = $this->result_summary;
		}
		return $this->ret;
	}
}
?>
