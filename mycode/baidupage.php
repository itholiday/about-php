<?php
	$link = mysql_connect('localhost','root','12345');
	mysql_query('set names utf8');
	mysql_select_db('imp');
	$sql_all = 'select * from my_student';
	$result = mysql_query($sql_all);
	$column = mysql_num_fields($result);
	$count = mysql_num_rows($result);
	$pagenow = isset($_GET['page'])?$_GET['page'] : 1;
	$per = 10;
	$totalpage = ceil($count/$per);
	$offset = ($pagenow-1)*$per;
	$sql_page = "select * from my_student limit $offset,$per";
	$result_page = mysql_query($sql_page);
	// var_dump($result_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>百度分页</title>
	<style type="text/css">
		a:hover{
			color:red;
		}
		a:visited,a:link{
			color:blue;
		}
	</style>
</head>
<body>
	<?php
		echo '<table border="1" cellspacing="0">';
		for ($i=0; $i <$column ; $i++) { 
			$field_name = mysql_field_name($result, $i);
			echo "<th>$field_name</th>";
		}
		while($res = mysql_fetch_assoc($result_page)){
			echo '<tr>';
			foreach ($res as $value) {
				echo "<td>$value</td>";
			}
			echo '</tr>';
		}
		echo '</table>';
		//每行显示5页，当前页显示在中间
		if($pagenow<=3){
			$min = 1;
			$max = 5;
		}elseif($pagenow<=$totalpage-2){
			$min = $pagenow - 2;
			$max = $pagenow + 2;
		}else{
			$min = $totalpage - 4;
			$max = $totalpage;
		}
		$prev = $pagenow-1;
		$next = $pagenow+1;
		echo "<a href='?page=$prev'>上一页</a>&nbsp;&nbsp;";
		for ($j=$min; $j <=$max ; $j++) {
			if($j == $pagenow){
				echo "<a href='?page=$j' style='color:red;font-size:22px;'>$j</a>&nbsp;&nbsp;";
			}else{
				echo "<a href='?page=$j' style='text-decoration:none;'>$j</a>&nbsp;&nbsp;";
			}
		}
		echo "<a href='?page=$next'>下一页</a>&nbsp;&nbsp;";
	?>
</body>
</html>