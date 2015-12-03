<?php

include_once('./connectDatabase.php');
include_once('./fileWatcherConfig.php');
include_once('./loggerClass.php');

$logger = new eventLogger($mysqli);
// we should be able to start more processes here!

$fam_res = fam_open ();
$dir_res = fam_monitor_directory  ( $fam_res, $dicomConfig["biometry"]["inputFolder"]);
echo 'Monitoring '.$dicomConfig["biometry"]["inputFolder"]."\n\n";
//var_dump($dicomConfig["biometry"]["inputFolder"]);

checkExistingFiles();

while (TRUE)
{
	$newfile = false;
	//fam_resume_monitor($fam_res, $dir_res);
	while(fam_pending($fam_res)){
		$arr = fam_next_event($fam_res);
		
		// FAMCreated == 5
		if($arr["code"] == 5){
			echo 'New file arrived: '.$arr["filename"]."\n";
			$newfile = true;
			$filedata = stat($dicomConfig["biometry"]["inputFolder"]."/".$arr["filename"]);
			if(! $fileid = fileEntryExists($dicomConfig["biometry"]["inputFolder"]."/".$arr["filename"], $filedata)){
				$fileid = createFileEntry($dicomConfig["biometry"]["inputFolder"]."/".$arr["filename"], $filedata);
			}
			//var_dump("Fileid: ".$fileid);
			$mysqli->query("INSERT INTO dicom_file_queue (filename, detected_date, last_modified_date, status_id) VALUES ('".$dicomConfig["biometry"]["inputFolder"]."/".$arr["filename"]."', now(), now(), (SELECT id FROM dicom_process_status WHERE name = 'new'))");
			$logger->addLogEntry($dicomConfig["biometry"]["inputFolder"]."/".$arr["filename"], 'new', basename($_SERVER["SCRIPT_FILENAME"]));
			// add log entry
			
		}else if($arr["code"] == 1){
			echo 'File has been changed: '.$arr["filename"]."\n";
			echo "What we should do now? :)\n";
			var_dump(stat($dicomConfig["biometry"]["inputFolder"]."/".$arr["filename"]));
		}
	}
	
	// something changed so we run the queue processor in the background
	if($newfile){
		//$logger->addLogEntry($dicomConfig["biometry"]["inputFolder"]."/".$arr["filename"], 'processing', basename($_SERVER["SCRIPT_FILENAME"]));
		exec("cd ".$dicomConfig["general"]["PHPdir"]." && /usr/bin/php runQueueProcessor.php"." > /dev/null 2>&1 &");
		echo "Queue Processor has been started successfully\n";
		unset($arr);
	} 
	sleep(5);

}

function fileEntryExists($fullfilename, $filedata){
	global $mysqli;
	
	//var_dump($filedata);
	$checkQuery = $mysqli->query("SELECT id FROM dicom_files WHERE filename='".$fullfilename."' AND filesize ='".$filedata["size"]."' AND filedate='".date("Y-m-d H:i:s",$filedata["mtime"])."'");
	if($isFileEntryExists = $checkQuery->fetch_row()){
		return $isFileEntryExists[0];
	}else{
		return false;
	}
	
}

function createFileEntry($fullfilename, $filedata){
	global $mysqli;
	// todo: add insert here! :)
	$mysqli->query("INSERT INTO dicom_files (filename, filesize, filedate, processor_id) VALUES ('".$fullfilename."', '".$filedata["size"]."','".date("Y-m-d H:i:s",$filedata["mtime"])."','".php_uname("n")."')");
	return $mysqli->insert_id;
}


function checkExistingFiles(){
	global $fam_res;
	global $mysqli;
	global $logger;
	global $dicomConfig;
	
	$newfile = false;
	while(fam_pending($fam_res)){
		$arr = fam_next_event($fam_res);
		// FAMExists == 8
		if($arr["code"] == 8){
			if(is_file($dicomConfig["biometry"]["inputFolder"]."/".$arr["filename"])){
				$existingFiles[] = $dicomConfig["biometry"]["inputFolder"]."/".$arr["filename"];
			}
		}
	}

	if(is_array($existingFiles) && count($existingFiles) > 0){
		$processedFiles = array();
		$processedFilesQ = $mysqli->query("SELECT id,filename, filesize, filedate FROM dicom_files");
		while($filerow = $processedFilesQ->fetch_assoc()){
			$processedFiles[] = $filerow["filename"];
		}

		$notProcessed = array_diff($existingFiles, $processedFiles);
		if(count($notProcessed) > 0){
			$newfile = true;
			foreach($notProcessed as $fileEntry){
				echo 'New file arrived: '.$fileEntry."\n";
				$mysqli->query("INSERT INTO dicom_file_queue (filename, detected_date, last_modified_date, status_id) VALUES ('".$fileEntry."', now(), now(), (SELECT id FROM dicom_process_status WHERE name = 'new'))");
				$logger->addLogEntry($fileEntry, 'new', basename($_SERVER["SCRIPT_FILENAME"]));
			}
		}

	}	
}

fam_close($fam_res); 

?>
