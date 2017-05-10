<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'Classes/PHPExcel.php';

// Настройки
define('DOMAIN', 'http://prichal-nn.ru');
define('PHPEXCEL_TMP_DIR', 'phpexcel_tmp_dir');
$zip_file = 'Prichal_Optoviy_Price.zip'; // Входной архив
$file_types = array('xls', 'xlsx');// Допустимые расширения
$file_with_pictures = 'pictures-pr.xls'; // Входной файл с путями картинок

// Распаковываем архив
$zip = new ZipArchive;
$zip->open($zip_file);
$zip->extractTo(PHPEXCEL_TMP_DIR);
$zip->close();

if ($handle = opendir(PHPEXCEL_TMP_DIR)) {
    while (false !== ($file = readdir($handle))) { 
        $phpexcel_tmp_files[]=$file;
    }
    closedir($handle); 
}

foreach ($phpexcel_tmp_files as $tmp_file) {
	$extension = substr($tmp_file, (strpos($tmp_file, ".")+1));
	if(in_array($extension, $file_types))
		makeexcel($tmp_file);
}

// $output_file = 'Price list (XLS).xls'; // Выходной файл, название которого 


function makeexcel($file){
	global $file_with_pictures;

	// Загружаем оба файла в объекты  класса PHPExcel
	$main_file = PHPExcel_IOFactory::load(PHPEXCEL_TMP_DIR.'/'.$file);
	$file_w_pics = PHPExcel_IOFactory::load($file_with_pictures);

	// Устанавливаем индексы активных листов для обоих файлов
	$main_file->setActiveSheetIndex(0);
	$file_w_pics->setActiveSheetIndex(0);

	// Получаем активные листы обоих файлов
	$main_sheet = $main_file->getActiveSheet();
	$sheet_w_pics = $file_w_pics->getActiveSheet();

	$rows = $sheet_w_pics->toArray();

	unset($sheet_w_pics);

	//оптимизированный массив с картинками
	$rows_opt = array();

	foreach ($rows as $row) {
		if (is_float($row[0]) and gettype($row[2])!="NULL") {
			$rows_opt[(int)$row[0]]= array($row[1], $row[2]);
		}
	}

	// Старый массив картинок уже не нужен.
	unset($rows);

	$main_sheet->getColumnDimension(PHPExcel_Cell::stringFromColumnIndex(5))->setWidth(25);

	for ($row = 1; $row <= $main_sheet->getHighestRow(); $row++) {  
	  $artikul = (int)trim($main_sheet->getCellByColumnAndRow(0, $row)->getValue());
	  // Если первая ячейка содержит последовательность из как минимум 9 цифр, то выполняем код
	  if (preg_match('/[0-9]{9,}/', $artikul)) {
	  	// Добавляем ссылки к заголовкам товаров
	   	$main_sheet->getCellByColumnAndRow(2,$row)->getHyperlink()->setUrl(DOMAIN.'/search/?text='.$artikul);
	   	// Пути к маленьким и большим картинкам товаров
	   	if (isset($rows_opt[$artikul])) {
	   		$imagePath = dirname (dirname ( __FILE__ )).'/'.$rows_opt[$artikul][1];
	   		$bigImagePath = DOMAIN.'/'.$rows_opt[$artikul][0];
	   	}
	   	static $pics_counter = 0; // Будем считать, сколько картинок загрузили 

	   	// Добавляем маленькие картинки товаров в ячейки столбца 6		
			if (file_exists($imagePath)) {
				$logo = new PHPExcel_Worksheet_Drawing();
				$logo->setPath($imagePath);
				$logo->setCoordinates(exNumToStr(5, $row));				
				$logo->setOffsetX(5);
				$logo->setOffsetY(5);
				$main_sheet->getRowDimension($row)->setRowHeight(110);
				$main_sheet->setCellValueByColumnAndRow(5, $row, 'Открыть фото');
				// Добавляем ссылки к маленьким картинкам 
				$main_sheet->getCellByColumnAndRow(5,$row)->getHyperlink()->setUrl($bigImagePath);
				$logo->setWorksheet($main_sheet);
				$pics_counter++;
			}else{
				$main_sheet->setCellValueByColumnAndRow(5, $row, 'Нет фото');
			}			
	   }
	   unset($bigImagePath);
	   unset($imagePath);
	}

	$objWriter = new PHPExcel_Writer_Excel5($main_file);
	$objWriter->save($file);
	if ($pics_counter) {
		echo "Импорт прошёл успешно,<br>обработано ".$main_sheet->getHighestRow()." строк,<br>добавлено $pics_counter фотографий";
	} else {
		echo "Произошла непредвиденная ошибка";
	}
}




function exNumToStr($col, $row){
  return(PHPExcel_Cell::stringFromColumnIndex($col).$row);
}
?>
