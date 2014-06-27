<?php
/**
 * download window for queries generated by the query gui.
 * query_gui_data_loader receives an executeQuery command from the
 * query gui, then generates the query string and saves it into the
 * database.  we now fetch that query string and run it, dumping the
 * results in a file (CSV/TSV/XLS) and give it to the user to
 * download.
 * @package main
 * @subpackage query_gui
 */

// load the client
ini_set('default_charset', 'utf-8');
require_once 'NDB_Client.class.inc';
$client = new NDB_Client;

// TEMPORARY: disabling authentication simply to make testing easier
// and faster...  should be reenabled later for prod use (to protect
// data)
// $client->makeCommandLine();

$client->initialize();

$config =& NDB_Config::singleton();
$css = $config->getSetting('css');
$studyTitle = $config->getSetting('title');
$downloadPath= $config->getSetting('DownloadPath');

$query = "";

if(!empty($_GET['queryID'])) {
    // get the query
    $query = $DB->selectOne("SELECT query FROM query_gui_downloadable_queries WHERE queryID='$_GET[queryID]'");
    $format = $_GET['format'];
  
    // run the query
    $results = array();
    $DB->select($query, $results);
    if ($DB->isError($results) OR empty($results)) {
        echo "The result set could not be generated.<br><br> If you have defined a population there are 2 possible reasons why this could happen:<br><br> 1. you didn't actually select variables to be displayed. In that case go back and choose variables from 'Define Variables' <br> 2. no records matched your population definition. Go to 'Define Population' and modify your population definition.";
        exit;
    }
	// add the description as the second row
    $files_cols = array();
	foreach(array_keys($results[0]) AS $i => $key) {
        $parameter_type = $DB->pselectRow("SELECT Description, IsFile FROM parameter_type WHERE Name=:key", array('key' => $key));
	    $desc[$key] = $parameter_type['Description'];
        if($parameter_type['IsFile']) {
            $files_cols[] = $key;
        }
	}

	$newResults[0] = $desc;
    $files = array();
	for($row=0; $row < count($results); $row++) {
        foreach($files_cols as $col) {
            if(!empty($results[$row][$col]) && ($format=='html' || $format=='download_files')) {
                $file = $results[$row][$col];
                if($format == 'html') {
                    $results[$row][$col] = "<a href=\"mri/jiv/get_file.php?file=$file\">$file</a>";
                } else if($format == 'download_files') {
                    $files[] = $file;
                }
            }
        }
    }
	for($i=0; $i < count($results); $i++) {
	   $newResults[$i+1] = $results[$i];
	}
	$results = $newResults;

	// setup for download
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: public");
    
    // if NIHPD, do stupid dots
    if($config->getSetting('queryGuiFillDots') == 'true') {
       //print "Filling dots!!<br>\n";
       fillWithDots($results);
    }

    // build the file buffer
    
    $buffer = "Format: $format";
    if($format == 'download_files') {
        list($buffer, $format) = buildFileBuffer($files, $_GET['format'], $css, $studyTitle);
    } else {
        list($buffer, $format) = buildFileBuffer($results, $_GET['format'], $css, $studyTitle);
    }
    $filename = "requested_data.".time().".$format";

    // set the headers
    if($format != 'html' && $format != 'xls') {
        header('Content-Type: application/force-download');
        header('Content-Length: '.strlen($buffer));
        header('Content-disposition: attachment; filename='.$filename);
    }

    $loggedInUser =& User::singleton();
    $downloadingUserID = $loggedInUser->getData('ID');
    $downloadDate = date("Y-m-d G:i");
    $DB->update('query_gui_downloadable_queries', array('filename'=>$filename, 'userID'=>$downloadingUserID, 'downloadDate'=>$downloadDate), array('queryID'=>$_GET['queryID']));

    if($format != 'xls') print $buffer;
} else {
    print "Missing query ID";
}

function buildFileBuffer($data, $format, $css, $studyTitle) {
    switch($format) {
    case 'download_files':
        $buffer = '';
        foreach($data as $file) {
            global $downloadPath;
            print "downloadPath: $downloadPath";
            chdir($downloadPath);
            $file = "./$file";
            //print $file;
            if(file_exists($file)) {
                `tar rvf /var/www/neurodb/htdocs/foo.tar $file`;
            }
            $buffer .= "$file\n";
        }
        $format = 'txt';
        break;
    case 'xls':
        $format = 'xls';
        require_once 'Spreadsheet/Excel/Writer.php';

        $maxColsPerWorksheet = 255;
        $maxWorksheetsPerWorkbook = 255;

        // Creating a workbook
        $workbook = new Spreadsheet_Excel_Writer();
        
        // Use Excel 97/2000 Binary File Format thereby allowing cells to contain more than 255 characters.
        $workbook->setVersion(8); // Use Excel97/2000 Format.
        
        $headerFormat =& $workbook->addFormat();
        $headerFormat->setBold();
        $headerFormat->setAlign('center');

        // divide the data array into several worksheets to limit the
        // number of columns per sheet
        $worksheetCount = 1;
        $worksheetData =& splitDataIntoWorksheets($data, $maxColsPerWorksheet);

        if(count($worksheetData) > $maxWorksheetsPerWorkbook) {
            $format = 'html';
            $buffer = 'Too many variables were selected for an Excel workbook.  Either use a different download format or reduce the number of variables selected.<br>';
            break;
        }

        for($i=0; $i < count($worksheetData); ++$i) {
            $worksheet[$i] =& $workbook->addWorksheet('Worksheet '.($i+1));
            $currentWorksheet =& $worksheet[$i];
            $currentWorksheet->writeRow(0,0,array_keys($worksheetData[$i]), $headerFormat);
            $currentWorksheet->writeRow(1,0,$worksheetData[$i]);
        }

        $workbook->send("requested_data.".time().".xls");
        $workbook->close();
        break;
        
    case 'tab':
        $format = 'tab';
        $buffer = join("\t", array_keys($data[0]))."\n";
        foreach($data AS $row) {
            $buffer .= join("\t", $row)."\n";
        }
        break;

    case 'csv':
        $format = 'csv';
        $buffer = join(',', array_keys($data[0]))."\n";
        foreach($data AS $row) {
            $buffer .= join(',', $row)."\n";
        }
        break;

    default:
    case 'html':
        $format = 'html';
        $buffer = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'.
            '<html xmlns="http://www.w3.org/1999/xhtml"><head><link rel="stylesheet" href="'. $css.
            '" type="text/css" /<TITLE>Result Display Data Query Gui - '.$studyTitle.'</TITLE></head><table class=fancytable border=1>';
        
        foreach($data AS $row) {
            if(!$first_row_done) {
                $buffer .= '<tr><th>'.implode('</th><th>', array_keys($row))."</th></tr>\n";
                $first_row_done = true;
            }
        
            $buffer .= "<tr><td>".implode('</td><td>', $row)."</td></tr>\n";
        }
        $buffer .= "</table>";
        break;
    }

    return array($buffer, $format);
}

function &splitDataIntoWorksheets(&$input, $maxColsPerWorksheet) {
    $transposed =& transposeArray($input);

    if(count($transposed) > $maxColsPerWorksheet) {
        // split the data up
        $output = array();
        $sheetNum = 0;
        $colNum = 0;
        foreach($transposed AS $key=>$column) {
            $output[$sheetNum][$key] = $column;
            $colNum++;
            if($colNum == $maxColsPerWorksheet) {
                $sheetNum++;
                $colNum = 0;
            }
        }
        return $output;
    } else {
        // we're not exceeding the max number of columns, so just
        // return the transpose of what we received
        return array($transposed);
    }
}

function &transposeArray(&$input) {
    $output = array();
    foreach($input AS $key => $row) {
        foreach($row AS $col => $value) {
            $output[$col][$key] = $value;
        }
    }
    return $output;
}

function fillWithDots(&$data) {
   foreach($data AS $rowNo=>$row) {
      foreach($row AS $colNo=>$value) {
         if(is_null($value) || ($value == 'NA'))
            $data[$rowNo][$colNo] = '.';
      }
   }
}
?>
