<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Маршруты</title>
</head>
<body>
	<script>
		var p2 = new Promise((resolve, reject) => { 
		  setTimeout(resolve, 2000, "two"); 
		});
		var p1 = new Promise((resolve, reject) => { 
		  setTimeout(resolve, 1000, "one"); 
		}); 
		var p3 = new Promise((resolve, reject) => {
		  setTimeout(resolve, 3000, "three");
		});
		var p4 = new Promise((resolve, reject) => {
		  setTimeout(resolve, 4000, "four");
		});

		Promise.all([p2, p1, p3, p4]).then(value => { 
		  console.log(value);
		}, reason => {
		  console.log(reason)
		});

		//Выведет:
		//"reject"
	</script>
	
</body>
</html>