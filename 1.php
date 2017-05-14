<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'Classes/PHPExcel.php';

// Настройки
define('OUTPUT_FILES_DIR', 'Output files');

$matrix = json_decode($_POST['matrix_to_json'],true);

isset($matrix['fnum']) ? $excel_file_number = $matrix['fnum'] : $excel_file_number = false;

if ($excel_file_number) {

} else {
	// Открываем файл с номером последней пары экспортированных файлов и считываем этот номер
	$fp = fopen('Last file number (DO NOT DELETE).txt', "rt" );
	$last_file_number = fgets($fp, 10);
	fclose($fp);
	$last_file_number++; //Номер для нового файла
	$excel_file = OUTPUT_FILES_DIR.'/Matrix '.$last_file_number.'.xlsx';
	$txt_file = OUTPUT_FILES_DIR.'/Output file '.$last_file_number.'.txt'; 
	// $fp = fopen($excel_file, "w" );
	// fclose($fp);
	$phpexcel = new PHPExcel(); // Создаём объект PHPExcel
  /* Каждый раз делаем активной 1-ю страницу и получаем её, потом записываем в неё данные */
  $page = $phpexcel->setActiveSheetIndex(0); // Делаем активной первую страницу и получаем её
  $objWriter = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel2007');
  /* Записываем в файл */
  $objWriter->save($excel_file);
	makeExcelAndTxtFile( $excel_file, $txt_file, $matrix);
	// $fp = fopen('Last file number (DO NOT DELETE).txt', "w" );
	// fputs($fp, $last_file_number);
	// fclose($fp);
}

function makeExcelAndTxtFile( $file, $txt_file, $matrix ){

	// Загружаем файла в объект класса PHPExcel
	$virtual_file = PHPExcel_IOFactory::load($file);

	// Устанавливаем индекс активного листа для обоих файлов
	$virtual_file->setActiveSheetIndex(0);

	// Получаем активный лист файла
	$working_sheet = $virtual_file->getActiveSheet();

	

	static $counter = 0;

	foreach ($matrix['rows'] as $row_number => $row_array) {
		if (!isset($row_array['row_obj']['old_point'])) {
			$working_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex(0))->setAutoSize(true); // автоширина для первой колонки (колонка A). Для колонок нумерация начинается с нуля, для строк - с единицы.
			$working_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex($row_number))->setAutoSize(true); // автоширина остальных колонок, в которых будут данные
			$point_description = $row_array['row_obj']['address'] ? $row_array['row_obj']['address'] . ';' : '';
			$point_description = $point_description . $row_array['row_obj']['lat'] . ';' . $row_array['row_obj']['lng'];
			$working_sheet->setCellValueByColumnAndRow(0, $row_number+1, $point_description);
			$working_sheet->setCellValueByColumnAndRow($row_number, 1, $point_description);
			$point_description_for_txt_file = $point_description.';+'.PHP_EOL;
			$f=fopen($txt_file, 'a');
			fputs($f, $point_description_for_txt_file);
			fclose($f);
			

			$counter++;
		}
		foreach ($row_array as $col_number => $col_array) {
			// if($col_number != 'row_obj'){

			// }
			echo '<hr>$row_number: <br>';
			var_dump($row_number);
			echo '<br>$col_number: <br>';
			var_dump($col_number);
			echo '<br>$col_array: <br>';
			var_dump($col_array);
		}
	}

	

	$objWriter = new PHPExcel_Writer_Excel2007($virtual_file);
	// $objWriter = new PHPExcel_Writer_Excel5($virtual_file);
	$objWriter->save($file);
	// $objWriter = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel2007');
 //  /* Записываем в файл */
 //  $objWriter->save("test.xlsx");
	if ($counter) {
		echo "Обработка списка прошла успешно,<br>добавлено пунктов: $counter";
	} else {
		echo "Произошла непредвиденная ошибка";
	}
}




function exNumToStr($col, $row){
  return(PHPExcel_Cell::stringFromColumnIndex($col).$row);
}


// $excel_file = 'Matrix '.$matrix['fnum'].'xlsx';