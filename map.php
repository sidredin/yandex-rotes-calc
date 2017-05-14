<!DOCTYPE html>
<html lang="en">
<head>
	<title>Расчет маршрутов</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <script src="https://api-maps.yandex.ru/2.1/?mode=debug&lang=ru_RU" type="text/javascript"></script>
  <style>
  	p{
  		margin: 0;
  	}
  	.results{
  		display: flex;
  	}
  	.results div{
  		display: flex;
  		align-items: center;
  	}
  	#run{
  		margin: 20px 0;
  	}
  	#messages{
  		margin-bottom: 20px;
  	}
  </style>
</head>

<body>
	<div class="top">
		<?php

		$file_lines = [];
		$lines_counter = 0;
		if ( strstr ( $_FILES['uploadedfile']['name'] , 'Output file' ) ) {
			$file_name = $_FILES['uploadedfile']['name'];
			$file_name_exploded = explode ( ' ' , $file_name );
			$number_and_ext = $file_name_exploded[2];
			$number_and_ext_exploded = explode ( '.' , $number_and_ext );
			$file_number = $number_and_ext_exploded[0]; 
		}
		$fp = fopen($_FILES['uploadedfile']['tmp_name'], "r"); // Открываем файл в режиме чтения
		if ($fp) 
		{
			while (!feof($fp))
			{	
				
				$file_line = trim(fgets($fp, 999));
				// $file_line = mb_convert_encoding($file_line, 'UTF-8', 'cp1251');
				$file_line_exploded = explode ( ';', $file_line);
				switch (count($file_line_exploded)) {
					case 1:
						if ($file_line_exploded[0]==='') { //Пустая строка
							break;
						}else{
							$lines_counter++;
							$file_lines[$lines_counter] = [];
							$address = $file_line_exploded[0];
							$file_lines[$lines_counter]['address'] = $address;
							$address_for_geokoder = str_replace ( " " , "+" ,  $address);
							$geokoder_response = file_get_contents('https://geocode-maps.yandex.ru/1.x/?format=json&geocode='.$address_for_geokoder);
							$geokoder_response_decoded = json_decode ( $geokoder_response, true );
							$featureMember = $geokoder_response_decoded['response']['GeoObjectCollection']['featureMember'];
							echo '<hr><br><h2>Выберите нужный пункт по запросу "'.$address.'":</h2>';
							foreach ( $featureMember as $key => $result ) {
								$latlng = $result['GeoObject']['Point']['pos'];
								if ($key===0) {
									$is_checked =	'checked';
								} else {
									$is_checked =	'';
								}					
								echo '<div class="results">
												<div class="radio-btn-wrapper"><input '. $is_checked . '  type="radio"  name="point'. $lines_counter .'"  value="'.$latlng.'"/></div>
												<div class="results__description">
													<p>'.$result['GeoObject']['metaDataProperty']['GeocoderMetaData']['text'].'</p>
												</div>
											</div>
								';
							}
							
							break;
						}
						
					case 2:
						$lines_counter++;
						$file_lines[$lines_counter]['address'] = '';
						$file_lines[$lines_counter]['lat'] = $file_line_exploded[0];
						$file_lines[$lines_counter]['lng'] = $file_line_exploded[1];

						break;
					case 3:
						$lines_counter++;
						$file_lines[$lines_counter]['address'] = '';
						$file_lines[$lines_counter]['lat'] = $file_line_exploded[0];
						$file_lines[$lines_counter]['lng'] = $file_line_exploded[1];
						$file_lines[$lines_counter]['old_point'] = 'yes';

						break;
					case 4:
						$lines_counter++;
						$file_lines[$lines_counter]['address'] = $file_line_exploded[0];
						$file_lines[$lines_counter]['lat'] = $file_line_exploded[1];
						$file_lines[$lines_counter]['lng'] = $file_line_exploded[2];
						$file_lines[$lines_counter]['old_point'] = 'yes';
						break;
					
					default:
						echo "<hr><h2>На строке $lines_counter или строкой выше/ниже, возможно, произошла ошибка. <br>Проверьте формат строки (см. <a  type='application/file' href='/instructions.docx'>файл с инструкциями</a>)</h2>";
						break;
				}
			}
		}
		else echo "Ошибка при открытии файла";
		fclose($fp);
		?>
		<div>
			<button id="run">Поехали!</button>
		</div>
	</div>
  <div id="messages"></div>
  <div id="map" style="width: 600px; height: 400px"></div>
  <script src="http://code.jquery.com/jquery-1.8.3.js"></script>
	<!-- <script src="js/ymap-script.js"></script> -->
  <script type="text/javascript">
    ymaps.ready(init);
    var myMap, 
        myPlacemark,
        routes=[],
        distanses=[], 
				matrix = {};

    function init(){ 
      myMap = new ymaps.Map("map", {
          center: [55.76, 37.64],
          zoom: 7
      }); 

      var points = <?php echo json_encode($file_lines); ?>;
      $('#run').click(function () {
      	for (let point in points) {
      		if (points[point]['lat']) {
      		} else {
      			var latlng = $("input[name=point"+point+"]").val();
      			var latlng_arr = latlng.split(' ');
      			points[point]['lat'] = latlng_arr[1];
      			points[point]['lng'] = latlng_arr[0];
      		}
				}
				matrix.fnum = <?php echo $file_number !== NULL ? $file_number : 'false'; ?>;
				matrix.rows = {};
  			var array_with_rotes_promises = [];
				for (let matrix_row in points){
					matrix.rows[matrix_row] = {};
					matrix.rows[matrix_row]['row_obj'] = points[matrix_row];
					for (let matrix_col in points){
						if ( points[matrix_row]['old_point'] && points[matrix_col]['old_point'] ) continue;
						matrix.rows[matrix_row][matrix_col] = {} 
						matrix.rows[matrix_row][matrix_col]['col_obj'] = points[matrix_col];
						if ( matrix_row === matrix_col ) {
							matrix.rows[matrix_row][matrix_col]['distanse'] = 0;
						}else{
							array_with_rotes_promises.push(calcDistanse(matrix, matrix_row, matrix_col));     		
						}
					}
				}

		    Promise.all(array_with_rotes_promises).then(items => {
		      items.forEach(route => {
		        myMap.geoObjects.add(route);
		        distanses.push(route.getLength());
		      });
		      var counter = 0; 
		      for (let matrix_row in points){
						for (let matrix_col in points){
							if ( points[matrix_row]['old_point'] && points[matrix_col]['old_point'] ) continue;
							if ( matrix_row !== matrix_col ) {
								matrix.rows[matrix_row][matrix_col]['distanse'] = distanses[counter];
								counter++;
							}
						}
					}

		      matrix_to_json = JSON.stringify(matrix);

		      $.ajax({
				      url: "/1.php",
				      data: {matrix_to_json: matrix_to_json},
				      type: 'POST',
				      success: function(res){
				          if(!res) alert('Ошибка!');
				          $('#messages').html(res);
				      },
				      error: function(res){
				          alert('Произошла ошибка в процессе Ajax-запроса');
				      },
					  });
		    });
      });
    }
    function calcDistanse(matrix, matrix_row, matrix_col) {
    	var row_obj_lat = +matrix.rows[matrix_row]['row_obj']['lat'];
			var row_obj_lng = +matrix.rows[matrix_row]['row_obj']['lng'];
			var col_obj_lat = +matrix.rows[matrix_row][matrix_col]['col_obj']['lat'];
			var col_obj_lng = +matrix.rows[matrix_row][matrix_col]['col_obj']['lng'];
    	return ymaps.route([[row_obj_lat,row_obj_lng], [col_obj_lat, col_obj_lng]], {
        mapStateAutoApply: true
      });
    }
  </script>
</body>
</html>