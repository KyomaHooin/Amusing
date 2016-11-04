<?php
//
// Parse local stored CSV to SQL database 
//
// muzeum/core/init.inc.php: "cronraw"=>true,
//
// CSV format: location-ISO_time.csv
// CSV data format: location;value_type;value;ISO_time
//
// CREATE TABLE `mapping` (
//	`s_lloc` char(1) NOT NULL,
//	`s_lname` varchar(128) NOT NULL,
//	`s_lid` int(10) unsigned NOT NULL,
//	PRIMARY KEY (`s_lloc`,`s_lname`)
// ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
//

//server-side callable
if($_SERVER['REMOTE_ADDR']!=$_SERVER['SERVER_ADDR']) exit();
//disable response header/footer [mandatory]
$_NOHEAD=true;
//response header [mandatory]
header("Content-type: text/plain");
//data absolute path
$data_path='/var/www/sensors/data/';
//cron locking
$locked=false;

//MAIN

do {
	//cron lock
	if(!cronlock()) {
		echo "Cron lock failed!..";
		break;
	}
	
	//parse CSV and store raw SQL data
	foreach(glob(realpath($data_path).'/*.csv') as $file) {
		if(is_file($file)) {
			switch(processfile($file)) {
				case -1: // error
					@rename($file,$file.".err");
					break;
				case 0: // success
					@rename($file,$file.".done");
					break;
				default: // default
					break;
			}
		freshlock();
		}
	}
} while(false);

//FUNC

function processfile($csv) {

	global $SQL;

	//test CSV name format
	if(!preg_match('/^[a-z0-9]+-\d+T\d+.csv$/i',basename($csv))) {
		logsys("Invalid file name: ". basename($csv));
//		echo "Invalid file name: ". basename($csv);
		return -1;
	}
	//Test CSV data and populate data array.
	$data=array();
	foreach(explode("\n",file_get_contents($csv)) as $raw) {
		$line=explode(";",trim($raw));
		if(count($line)==4) {
			//test location array
			if(!get_ind($data,$line[0])) $data[$line[0]]='';
			//test var code array
			if(!get_ind($data[$line[0]],$line[1])) $data[$line[0]][$line[1]]=array();
			//test data value
			if(!preg_match('/^\-?\d*\.?\d+$/',$line[2]) && !preg_match('/^[a-zA-Z0-9+\/=]+$/',$line[2])) {
				logsys("Invalid value ".$line[2]." in ".basename($csv));
//				echo "Invalid value ".$line[2]." in ".basename($csv);
				return -1;
			}
			//test date/time YYYYMMDDThhmmss -> HisnjY
			if(!preg_match('/^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})Z?$/',$line[3],$match)) {
				logsys("Invalid date and time ".$line[3]." in ".basename($csv));
//				echo "Invalid date and time ".$line[3]." in ".basename($csv);
				return -1;
			}
			$timestamp=gmmktime($match[4],$match[5],$match[6],$match[2],$match[3],$match[1]);
			//s_lloc->var_code->(time,value)
			$data[$line[0]][$line[1]][]=array($timestamp,$line[2]);
		}
	}
//	print_r($data);
	//Remap sensors name to ID.
	$raw_data=array();
	foreach($data as $key=>$value) {
		$map_query=$SQL->query("select * from mapping where s_lname='" . $key . "' and s_lloc='" . explode('-',basename($csv))[0]. "'");
		$fmap=$map_query->obj();
		if(!$fmap) {
			logsys("No mapping for: ".$key);
//			echo "No mapping for: ".$key."\n";
		} else {
			//s_lloc->var_code->(timestamp, value) => s_lid->var_code->(timestamp,value)
			if(!get_ind($raw_data,$fmap->s_lid)) $raw_data[$fmap->s_lid]='';
			foreach($value as $raw_value=>$raw_value_data) {
				if(!get_ind($raw_data[$fmap->s_lid],$raw_value)) $raw_data[$fmap->s_lid][$raw_value]=array();
				foreach($raw_value_data as $pure_data) $raw_data[$fmap->s_lid][$raw_value][]=$pure_data;
			}
		}
	}
//	print_r($raw_data);
	//Store data into SQL.
	foreach($raw_data as $key=>$value) {
		//Test "active" sensor.
		$active_query=$SQL->query("select * from sensor left join measuring on s_mid=m_id left join room on r_id=m_rid where s_id=\"".$SQL->escape($key)."\" && s_active='Y' && m_active='Y'");
		$fact=$active_query->obj();
		if(!$fact) {
//			logsys("Sensor not active: ".$key);
//			echo "No active sensor found!\n";
			continue;
		}

//	        $sdata=unserialize($fact->s_data);
//	        $absh=get_ind($sdata,"absh");
//        	if($absh) computeabsh($value);

		//Store that "shit".
		foreach($value as $value_key=>$value_value) {
			//value type check
			$type_query=$SQL->query("select * from variable left join varcodes on vc_text=var_code where var_code=\"".$SQL->escape($value_key)."\"");
			$ftype=$type_query->obj();
			if(!$ftype) {
				logsys("Invalid value type: ".$value_key);
//				echo "Invalid value type: ".$value_key."\n";
				return -1;
			}
			//Time validity & time sorting
			$to_store=array();
			//Binary division
			if($ftype->vc_bin=='Y') {
				foreach ($value_value as $store_value) {
					if ($store_value[0]>=$fact->m_validfrom && $store_value[0]<=$fact->m_validto) {
						$to_store[]=array($store_value[0],serialize(array("type"=>"jpeg","data"=>base64_decode($store_value[1]))));
					}
				}
				usort($to_store,"dbfsort");
				val_saveblobvalues($to_store,$ftype->var_id,$fact->m_id);
			} else {
				foreach($value_value as $store_value) {
					if($store_value[0]>=$fact->m_validfrom && $store_value[0]<=$fact->m_validto) {
						$to_store[]=$store_value;
					}
				}
				usort($to_store,"dbfsort");
				val_saverawvalues($to_store,$ftype->var_id,$fact->m_id,$fact->s_id,true);
			}
		}
	}
	return 0;
}

function computeabsh(&$v) {
    $h=get_ind($v,"humidity");
    $t=get_ind($v,"temperature");
    
    if($h && $t) {
        $tl=array();
        $ah=array();
        foreach($h as $val) {
            $tl[$val[0]]=$val[1];
        }
        foreach($t as $val) {
            $tv=get_ind($tl,$val[0]);
            if($tv!==false) $ah[]=array($val[0],abshum($val[1],$tv));
//          else "not paired values\n";
        }
        if(count($ah)) {
            $v['abshumidity']=$ah;
        }
    }
}

?>
